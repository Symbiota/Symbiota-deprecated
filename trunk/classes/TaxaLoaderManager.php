<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/TaxaLoaderItisManager.php');

class TaxaLoaderManager{
	
	protected $conn;
	protected $uploadFileName;
	protected $uploadTargetPath;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
 		$this->setUploadTargetPath();
 		set_time_limit(3000);
		ini_set("max_input_time",120);
  		ini_set('auto_detect_line_endings', true);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function setFileName($fName){
		$this->uploadFileName = $fName;
	}

	public function getFileName(){
		return $this->uploadFileName;
	}

	public function setUploadFile($ulFileName = ""){
		if($ulFileName){
			//URL to existing file  
			if(file_exists($ulFileName)){
				$pos = strrpos($ulFileName,"/");
				if(!$pos) $pos = strrpos($ulFileName,"\\");
				$this->uploadFileName = substr($ulFileName,$pos+1);
				echo $this->uploadFileName;
				copy($ulFileName,$this->uploadTargetPath.$this->uploadFileName);
			}
		}
		elseif(array_key_exists('uploadfile',$_FILES)){
			$this->uploadFileName = $_FILES['uploadfile']['name'];
	        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $this->uploadTargetPath.$this->uploadFileName);
		}
        if(file_exists($this->uploadTargetPath.$this->uploadFileName) && substr($this->uploadFileName,-4) == ".zip"){
			$zip = new ZipArchive;
			$zip->open($this->uploadTargetPath.$this->uploadFileName);
			$zipFile = $this->uploadTargetPath.$this->uploadFileName;
			$this->uploadFileName = $zip->getNameIndex(0);
			$zip->extractTo($this->uploadTargetPath);
			$zip->close();
			unlink($zipFile);
        }
	}

	public function loadFile($fieldMap){
		//fieldMap = array(source field => target field)
		echo "<li>Starting Upload</li>";
		ob_flush();
		flush();
		$this->conn->query("DELETE FROM uploadtaxa");
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'rb') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		$uploadTaxaArr = $this->getUploadTaxaArr();
		$taxonUnitArr = $this->getTaxonUnitArr();
		$uploadTaxaIndexArr = array();		//Array of index values associated with uploadtaxa table; array(index => targetName)
		$taxonUnitIndexArr = array();		//Array of index values associated with taxonunits table;
		foreach($headerArr as $k => $sourceName){
			$sourceName = strtolower($sourceName);
			if(array_key_exists($sourceName,$fieldMap)){
				$targetName = $fieldMap[$sourceName];
				if(in_array($targetName,$uploadTaxaArr)) $uploadTaxaIndexArr[$k] = $targetName;
				if($targetName == 'unitname1') $targetName = 'genus';
				if(in_array($targetName,$taxonUnitArr)){
					$taxonUnitIndexArr[$k] = array_search($targetName,$taxonUnitArr);  //array(recIndex => rankid)
					asort($taxonUnitIndexArr);
				}
			}
		}
		$familyIndex = 0; 
		if(in_array('family',$fieldMap)) $familyIndex = array_search(array_search('family',$fieldMap),$headerArr);
		$recordCnt = 0;
		//scinameinput field is required
		if(in_array("scinameinput",$fieldMap) || count($taxonUnitIndexArr) > 2){
			$childParentArr = array();		//array(taxon => array('p'=>parentStr,'r'=>rankid)
			$sqlBase = "INSERT INTO uploadtaxa(".implode(",",$uploadTaxaIndexArr).") ";
			while($recordArr = fgetcsv($fh)){
				//Load taxonunits fields into Array which will be loaded into taxon table at the end
				$parentStr = '';
				foreach($taxonUnitIndexArr as $index => $rankId){
					$taxonStr = $recordArr[$index];
					if($taxonStr){
						if(!array_key_exists($taxonStr,$childParentArr)){
							if($rankId == 10){
								//For kingdom taxa, parents are themselves
								$childParentArr[$taxonStr]['p'] = $taxonStr;
								$childParentArr[$taxonStr]['r'] = $rankId;
							}
							elseif($parentStr){
								$childParentArr[$taxonStr]['p'] = $parentStr;
								$childParentArr[$taxonStr]['r'] = $rankId;
								if($rankId > 140 && $familyIndex && $recordArr[$familyIndex]){
									$childParentArr[$taxonStr]['f'] = $recordArr[$familyIndex];
								}
							}
						}
						$parentStr = $taxonStr;
					}
				}
				
				if(in_array("scinameinput",$fieldMap)){
					//Load relavent fields into uploadtaxa table
					$sql = $sqlBase;
					$valueSql = "";
					foreach($uploadTaxaIndexArr as $recIndex => $targetField){
						$valIn = $this->encodeString($recordArr[$recIndex]);
						if($targetField == 'acceptance' && !is_numeric($valIn)){
							$valInTest = strtolower($valIn);
							if($valInTest == 'accepted' || $valInTest == 'valid'){
								$valIn = 1;
							}
							elseif($valInTest == 'not accepted' || $valInTest == 'synonym'){
								$valIn = 0;
							}
							else{
								$valIn = '';
							}
						}
						$valueSql .= ','.($valIn?'"'.$valIn.'"':'NULL');
					}
					$sql .= 'VALUES ('.substr($valueSql,1).')';
					//echo "<div>".$sql."</div>";
					if(!$this->conn->query($sql)){
						echo '<li>ERROR loading taxon: '.$this->conn->error.'</li>';
					}
				}
				$recordCnt++;
			}
			//Process and load taxon units data ($childParentArr)
			foreach($childParentArr as $taxon => $tArr){
				$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput,rankid,parentstr,family,acceptance) '.
					'VALUES ("'.$taxon.'",'.$tArr['r'].',"'.$tArr['p'].'",'.(array_key_exists('f',$tArr)?'"'.$tArr['f'].'"':'NULL').',1)';
				if(!$this->conn->query($sql)){
					echo '<li>ERROR loading taxonunit: '.$this->conn->error.'</li>';
				}
			}
			
			echo '<li>'.$recordCnt.' taxon records pre-processed</li>';
			ob_flush();
			flush();
		}
		else{
			echo '<li>ERROR: Scientific name is not mapped to &quot;scinameinput&quot;</li>';
			ob_flush();
			flush();
		}
		fclose($fh);
		$this->cleanUpload();
		if(file_exists($this->uploadTargetPath.$this->uploadFileName)){
			unlink($this->uploadTargetPath.$this->uploadFileName);
		}
	}

	public function cleanUpload(){
		
		$sspStr = 'ssp.';$inSspStr = 'subsp.';
		$sql = 'SELECT unitind3 FROM taxa WHERE rankid = 230 AND unitind3 LIKE "s%" LIMIT 1';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$sspStr = $r->unitind3;
		}
		$rs->close();
		if($sspStr == 'subsp.') $inSspStr = 'ssp.';
		
		echo '<li>Starting data cleaning... ';
		echo '<li style="margin-left:10px;">Cleaning AcceptedStr... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," '.$inSspStr.' "," '.$sspStr.' ") WHERE (AcceptedStr LIKE "% '.$inSspStr.' %")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," var "," var. ") WHERE AcceptedStr LIKE "% var %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") WHERE (AcceptedStr LIKE "% '.substr($sspStr,0,strlen($sspStr)-1).' %")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," sp.") WHERE AcceptedStr LIKE "% sp."';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = trim(AcceptedStr) WHERE AcceptedStr LIKE "% " OR AcceptedStr LIKE " %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr,"  "," ") WHERE AcceptedStr LIKE "%  %"';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		//Value if only a Source Id was supplied
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.AcceptedStr = u2.scinameinput '.
			'WHERE u.sourceAcceptedId IS NOT NULL AND u2.sourceId IS NOT NULL AND u.AcceptedStr IS NULL';
		$this->conn->query($sql);
		
		//Insert into uploadtaxa table all accepted taxa not already present in scinameinput. If they turn out to be in taxa table, they will be deleted later 
		echo '<li style="margin-left:10px;">Appending accepted taxa not present in scinameinput... ';
		ob_flush();
		flush();
		$sql = 'INSERT INTO uploadtaxa(scinameinput) '.
			'SELECT DISTINCT u.AcceptedStr '.
			'FROM uploadtaxa u LEFT JOIN uploadtaxa ul2 ON u.AcceptedStr = ul2.scinameinput '.
			'WHERE u.AcceptedStr IS NOT NULL AND ul2.scinameinput IS NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		//Clean sciname field (sciname input gets cleaned later) 
		echo '<li style="margin-left:10px;">Cleaning sciname field... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," '.$inSspStr.' "," '.$sspStr.' ") WHERE (sciname like "% '.$inSspStr.' %")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," var "," var. ") WHERE sciname like "% var %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") WHERE (sciname like "% '.substr($sspStr,0,strlen($sspStr)-1).' %")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," cf. "," ") WHERE sciname like "% cf. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," cf "," ") WHERE sciname like "% cf %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff. "," ") WHERE sciname like "% aff. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff "," ") WHERE sciname like "% aff %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," sp.","") WHERE sciname like "% sp."';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," sp","") WHERE sciname like "% sp"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = trim(sciname) WHERE sciname like "% " OR sciname like " %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname,"  "," ") WHERE sciname like "%  %"';
		$this->conn->query($sql);
		echo 'Done!</li>';

		//Clean scinameinput field
		echo '<li style="margin-left:10px;">Cleaning scinameinput field... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," '.$inSspStr.' "," '.$sspStr.' ") '.
			'WHERE (scinameinput like "% '.$inSspStr.' %")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," var "," var. ") WHERE scinameinput like "% var %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") '.
			'WHERE (scinameinput like "% '.substr($sspStr,0,strlen($sspStr)-1).' %")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," cf. "," ") WHERE scinameinput like "% cf. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," cf "," ") WHERE scinameinput like "% cf %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff. "," ") WHERE scinameinput like "% aff. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff "," ") WHERE scinameinput like "% aff %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," sp.","") WHERE scinameinput like "% sp."';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," sp","") WHERE scinameinput like "% sp"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = trim(scinameinput) WHERE scinameinput like "% " OR scinameinput like " %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput,"  "," ") WHERE scinameinput like "%  %"';
		$this->conn->query($sql);
		echo 'Done!</li>';

		//Parse scinameinput into unitind and unitname fields 
		echo '<li style="margin-left:10px;">Parse scinameinput field into unitind and unitname fields... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET unitind1 = "x" WHERE unitind1 IS NULL AND scinameinput LIKE "x %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind2 = "x" WHERE unitind2 IS NULL AND scinameinput LIKE "% x %" AND scinameinput NOT LIKE "% % x %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitname1 = TRIM(substring(scinameinput,3,LOCATE(" ",scinameinput,3)-3)) '.
			'WHERE unitname1 IS NULL and scinameinput LIKE "x %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname1 = TRIM(substring(scinameinput,1,LOCATE(" ",CONCAT(scinameinput," ")))) '.
			'WHERE unitname1 IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname2 = TRIM(substring(scinameinput,LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2,LOCATE(" ",CONCAT(scinameinput," "),LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2)-(LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2))) '.
			'WHERE unitname2 IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitname2 = NULL WHERE unitname2 = ""';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind3 = "f.", rankid = 260 '.
			'WHERE unitind3 IS NULL AND (scinameinput LIKE "% f. %" OR scinameinput LIKE "% forma %") AND (rankid IS NULL OR rankid = 260)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind3 = "var.", rankid = 240 '.
			'WHERE unitind3 IS NULL AND scinameinput LIKE "% var. %" AND (rankid IS NULL OR rankid = 240)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind3 = "'.$sspStr.'", rankid = 230 '.
			'WHERE unitind3 IS NULL AND (scinameinput LIKE "% '.$sspStr.' %") AND (rankid IS NULL OR rankid = 230)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname3 = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(unitind3)+1, '.
			'LOCATE(" ",CONCAT(scinameinput," "),LOCATE(unitind3,scinameinput)+LENGTH(unitind3)+1)-LOCATE(unitind3,scinameinput)-LENGTH(unitind3))) '.
			'WHERE unitname3 IS NULL AND rankid > 220 ';
		$this->conn->query($sql);
		$sql = 'UPDATE IGNORE uploadtaxa '.
			'SET sciname = CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3, unitname3) '.
			'WHERE sciname IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceParentId = u2.sourceId '.
			'SET u.parentstr = u2.sciname '.
			'WHERE u.parentstr IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.acceptedstr = u2.sciname '.
			'WHERE u.acceptedstr IS NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';

		//Delete taxa where sciname can't be inserted. These are taxa where sciname already exists
		$sql = 'DELETE FROM uploadtaxa WHERE sciname IS NULL';
		$this->conn->query($sql);

		//Link names already in theusaurus 
		echo '<li style="margin-left:10px;">Linking names already in thesaurus... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa u INNER JOIN taxa t ON u.sciname = t.sciname '.
			'SET u.tid = t.tid WHERE u.tid IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedstr = u2.scinameinput '.
			'SET u1.tidaccepted = u2.tid '. 
			'WHERE u1.tidaccepted IS NULL AND u2.tid IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		echo '<li style="margin-left:10px;">Populating null family values... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceParentId = u2.sourceId '.
			'SET u1.family = u2.sciname '.
			'WHERE u2.rankid = 140 AND u1.family is null ';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname '.
			'SET u1.family = u2.family '.
			'WHERE u1.family IS NULL AND u2.family IS NOT NULL ';
		$this->conn->query($sql);
		$sql = 'UPDATE (uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceAcceptedId = u2.sourceId) '.
			'SET u1.family = u2.family '.
			'WHERE u1.family IS NULL AND u2.family IS NOT NULL ';
		$this->conn->query($sql);
		$sql = 'UPDATE (uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname) '.
			'INNER JOIN uploadtaxa u3 ON u2.sourceParentId = u3.sourceId '.
			'SET u1.family = u3.sciname '.
			'WHERE u2.sourceParentId IS NOT NULL AND u3.sourceId IS NOT NULL '.
			'AND u1.family is null AND u1.rankid > 140 AND u2.rankid = 180 AND u3.rankid = 140';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.sourceAcceptedId = u1.sourceid '.
			'SET u0.family = u1.family '.
			'WHERE u0.sourceParentId IS NOT NULL AND u1.sourceId IS NOT NULL AND '.
			'u0.family IS NULL AND u0.rankid > 140 AND u1.family IS NOT NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.scinameinput = u1.acceptedstr '.
			'SET u0.family = u1.family '.
			'WHERE u0.family IS NULL AND u0.rankid > 140 AND u1.family IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';

		echo '<li style="margin-left:10px;">Loading vernaculars... ';
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO taxavernaculars (tid, VernacularName, Language, Source) '.
			'SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		$sql = 'DELETE * FROM uploadtaxa WHERE tid IS NOT NULL';
		$this->conn->query($sql);

		echo '<li style="margin-left:10px;">Set null rankid values... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET rankid = 140 WHERE rankid IS NULL AND (sciname like "%aceae" || sciname like "%idae")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET rankid = 220 WHERE rankid IS NULL AND unitname1 is not null AND unitname2 is not null';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET rankid = 180 WHERE rankid IS NULL AND unitname1 is not null AND unitname2 is null';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		echo '<li style="margin-left:10px;">Set null author values... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LENGTH(sciname)+1)) '.
			'WHERE (author IS NULL) AND rankid <= 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(CONCAT_WS(" ",unitind3,unitname3)))) '.
			'WHERE (author IS NULL) AND rankid > 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET author = NULL WHERE author = ""';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		echo '<li style="margin-left:10px;">Populating null family values (part 2)... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET family = NULL WHERE family = ""';
		$this->conn->query($sql);
		$sql = 'UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND u1.rankid > 140 AND t.rankid = 180 AND ts.family is not null AND (ut.family IS NULL OR ts.family <> ut.family)';
		$this->conn->query($sql);
		$sql = 'UPDATE ((uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
			'INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid '.
			'SET ut.family = t2.sciname '.
			'WHERE ts.taxauthid = 1 AND u1.rankid > 140 AND t.rankid = 140 AND ts.tid <> ts.tidaccepted';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		echo '<li style="margin-left:10px;">Populating and mapping parent taxon... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadtaxa SET parentstr = CONCAT_WS(" ", unitname1, unitname2) WHERE (parentstr IS NULL OR parentstr = "") AND rankid > 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET parentstr = unitname1 WHERE (parentstr IS NULL OR parentstr = "") AND rankid = 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET parentstr = family WHERE (parentstr IS NULL OR parentstr = "") AND rankid = 180';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceAcceptedID = u2.sourceId '.
			'SET u1.sourceParentId = u2.sourceParentId, u1.parentStr = u2.parentStr '.
			'WHERE u1.sourceParentId IS NULL AND u1.sourceAcceptedID IS NOT NULL AND u2.sourceParentId IS NOT NULL AND u1.rankid < 220 ';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET parenttid = t.tid WHERE parenttid IS NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
		echo '<li style="margin-left:10px;">Populating null upper taxonomy and kingdom values (part 2)... ';
		ob_flush();
		flush();
		$sql = 'UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.uppertaxonomy = ts.uppertaxonomy '.
			'WHERE ts.taxauthid = 1 AND t.rankid = 180 AND ts.uppertaxonomy IS NOT NULL AND ut.uppertaxonomy IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.uppertaxonomy = ts.uppertaxonomy '.
			'WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.uppertaxonomy is not null AND ut.uppertaxonomy IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname '.
			'SET ut.kingdomid = t.kingdomid '.
			'WHERE t.rankid = 180 AND t.kingdomid IS NOT NULL AND ut.kingdomid IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname '.
			'SET ut.kingdomid = t.kingdomid '.
			'WHERE t.rankid = 140 AND t.kingdomid is not null AND ut.kingdomid IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.parenttid = t.tid '. 
			'SET ut.kingdomid = t.kingdomid '.
			'WHERE ut.kingdomid IS NULL AND t.kingdomid IS NOT NULL AND ut.parenttid IS NOT NULL ';
		$this->conn->query($sql);
		echo 'Done!</li>';

		//Load into uploadtaxa parents of infrasp not yet in taxa table 
		echo '<li style="margin-left:10px;">Add parents that are not yet in uploadtaxa table... ';
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, UnitName2, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 220 as r, ut.unitname1, ut.unitname2, ut.unitname1, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid > 220 AND ut2.sciname IS NULL ';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE up.parenttid IS NULL';
		$this->conn->query($sql);
		
		//Load into uploadtaxa parents of species not yet in taxa table 
		$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 180 as r, ut.unitname1, ut.family, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.family IS NOT NULL AND ut.rankid = 220 AND ut2.sciname IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE up.parenttid IS NULL';
		$this->conn->query($sql);

		//Load into uploadtaxa parents of genera not yet in taxa table 
		$defaultParent = 0;
		if(!$defaultParent){
			$sql = 'SELECT tid FROM taxa WHERE rankid = 10 LIMIT 1';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$defaultParent = $r->tid;
			}
			$rs->close();
		}
		$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, parenttid, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 140 as r, ut.parentstr, '.$defaultParent.', ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid = 180 AND ut2.sciname IS NULL';
		$this->conn->query($sql);

		//Set acceptance to 0 where sciname <> acceptedstr
		$sql = 'UPDATE uploadtaxa '.
			'SET acceptance = 0 '.
			'WHERE acceptedstr IS NOT NULL AND sicname IS NOT NULL AND sciname <> acceptedstr';
		$this->conn->query($sql);
		echo 'Done!</li>';
		echo '<li>Done data cleaning</li>';
		ob_flush();
		flush();
	}

	public function transferUpload($taxAuthId = 1){
		$startLoadCnt = -1;
		$endLoadCnt = 0;
		$loopCnt = 0;
		echo '<li>Starting data transfer</li>';
		ob_flush();
		flush();

		
		//Prime table with kingdoms that are not yet in table
		$sql = 'INSERT IGNORE INTO taxa ( SciName, KingdomID, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes ) '.
			'SELECT DISTINCT ut.SciName, ut.KingdomID, ut.RankId, ut.UnitInd1, ut.UnitName1, ut.UnitInd2, ut.UnitName2, ut.UnitInd3, '.
			'ut.UnitName3, ut.Author, ut.Source, ut.Notes '.
			'FROM uploadtaxa AS ut '.
			'WHERE (ut.TID Is Null AND rankid = 10)';
		$this->conn->query($sql);
		$sql = 'INSERT IGNORE INTO taxstatus (tid, tidaccepted, taxauthid, parenttid) '.
			'SELECT DISTINCT t.tid, t.tid, 1 AS taxauthid, t.tid '.
			'FROM uploadtaxa AS ut INNER JOIN taxa t ON ut.sciname = t.sciname '.
			'WHERE (ut.TID Is Null AND ut.rankid = 10)';
		$this->conn->query($sql);
		
		//Loop through and transfer taxa to taxa table
		WHILE(($endLoadCnt > 0 || $startLoadCnt <> $endLoadCnt) && $loopCnt < 30){
			$sql = 'SELECT COUNT(*) AS cnt FROM uploadtaxa';
			$rs = $this->conn->query($sql);
			$r = $rs->fetch_object();
			$startLoadCnt = $r->cnt;

			echo '<li>Starting loop '.$loopCnt.'</li>';
			echo '<li>Transferring taxa to taxon table... ';
			ob_flush();
			flush();
			$sql = 'INSERT IGNORE INTO taxa ( SciName, KingdomID, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes ) '.
				'SELECT DISTINCT ut.SciName, ut.KingdomID, ut.RankId, ut.UnitInd1, ut.UnitName1, ut.UnitInd2, ut.UnitName2, ut.UnitInd3, '.
				'ut.UnitName3, ut.Author, ut.Source, ut.Notes '.
				'FROM uploadtaxa AS ut '.
				'WHERE (ut.TID Is Null AND ut.parenttid IS NOT NULL AND kingdomid IS NOT NULL AND rankid IS NOT NULL )';
			$this->conn->query($sql);
			
			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.sciname = t.sciname '.
				'SET ut.tid = t.tid WHERE ut.tid IS NULL';
			$this->conn->query($sql);
			
			$sql = 'UPDATE uploadtaxa ut1 INNER JOIN uploadtaxa ut2 ON ut1.sourceacceptedid = ut2.sourceid '.
				'INNER JOIN taxa t ON ut2.scinameinput = t.sciname '.
				'SET ut1.tidaccepted = t.tid '.
				'WHERE ut1.acceptance = 0 AND ut1.tidaccepted IS NULL AND ut1.sourceacceptedid IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.acceptedstr = t.sciname '.
				'SET ut.tidaccepted = t.tid '.
				'WHERE ut.acceptance = 0 AND ut.tidaccepted IS NULL AND ut.acceptedstr IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'UPDATE uploadtaxa SET tidaccepted = tid '.
				'WHERE tidaccepted IS NULL AND tid IS NOT NULL';
			$this->conn->query($sql);
			echo 'Done!</li>';

			echo '<li>Creating taxonomic hierarchy... ';
			ob_flush();
			flush();
			$sql = 'INSERT IGNORE INTO taxstatus ( TID, TidAccepted, taxauthid, ParentTid, Family, UpperTaxonomy, UnacceptabilityReason ) '.
				'SELECT DISTINCT ut.TID, ut.TidAccepted, '.$taxAuthId.' AS taxauthid, ut.ParentTid, ut.Family, ut.UpperTaxonomy, ut.UnacceptabilityReason '.
				'FROM uploadtaxa AS ut '.
				'WHERE (ut.TID IS NOT NULL AND ut.TidAccepted IS NOT NULL AND ut.parenttid IS NOT NULL)';
			$this->conn->query($sql);
			echo 'Done!</li>';

			echo '<li>Transferring vernaculars for new taxa... ';
			ob_flush();
			flush();
			$sql = 'INSERT IGNORE INTO taxavernaculars (tid, VernacularName, Language, Source) '.
				'SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL';
			$this->conn->query($sql);
			echo 'Done!</li>';

			echo '<li>Preparing for next round... ';
			ob_flush();
			flush();
			$sql = 'DELETE FROM uploadtaxa WHERE tid IS NOT NULL AND tidaccepted IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.parenttid = t.tid '. 
				'SET ut.kingdomid = t.kingdomid '.
				'WHERE ut.kingdomid IS NULL AND t.kingdomid IS NOT NULL AND ut.parenttid IS NOT NULL ';
			$this->conn->query($sql);
			
			//Update parentTids
			$sql = 'UPDATE uploadtaxa ut1 INNER JOIN uploadtaxa ut2 ON ut1.sourceparentid = ut2.sourceid '.
				'INNER JOIN taxa t ON ut2.scinameinput = t.sciname '.
				'SET ut1.parenttid = t.tid '.
				'WHERE ut1.parenttid IS NULL AND ut1.sourceparentid IS NOT NULL';
			$this->conn->query($sql);
			
			$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
				'SET up.parenttid = t.tid '.
				'WHERE up.parenttid IS NULL';
			$this->conn->query($sql);

			
			$sql = 'SELECT COUNT(*) as cnt FROM uploadtaxa';
			$rs = $this->conn->query($sql);
			$r = $rs->fetch_object();
			$endLoadCnt = $r->cnt;
			echo 'Done!</li>';
			
			$loopCnt++;
		}

		
		echo '<li>Finishing up with some house cleaning... ';
		ob_flush();
		flush();
		//Something is wrong with the buildHierarchy method. Needs to be fixed. 
		$this->buildHierarchy($taxAuthId);
		
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL';
		$this->conn->query($sql);

		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '. 
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '. 
			'FROM omoccurrences o LEFT JOIN omoccurgeoindex g ON o.tidinterpreted = g.tid '.
			'WHERE g.tid IS NULL AND o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li>';
		
	}

	protected function buildHierarchy($taxAuthId){
		$sqlHier = 'SELECT ts.tid FROM taxstatus ts WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.hierarchystr IS NULL)';
		//echo $sqlHier;
		$resultHier = $this->conn->query($sqlHier);
		
		
		while($rowHier = $resultHier->fetch_object()){
			$tid = $rowHier->tid;
			$parentArr = Array();
			$targetTid = $tid;
			$parCnt = 0;
			do{
				$sqlParents = 'SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts '.
					'WHERE (ts.taxauthid = '.$taxAuthId.') AND ts.tid = '. $targetTid;
				$targetTid = 0;
				//echo "<div>".$sqlParents."</div>";
				$resultParent = $this->conn->query($sqlParents);
				if($rowParent = $resultParent->fetch_object()){
					$parentTid = $rowParent->parenttid;
					if($parentTid) {
						$parentArr[$parentTid] = $parentTid;
					}
					$targetTid = $parentTid;
				}
				$resultParent->close();
				$parCnt++;
				if($parCnt > 35) break;
			}while($targetTid);
			
			//Add hierarchy string to taxstatus table
			if($parentArr){
				$sqlInsert = 'UPDATE taxstatus ts SET ts.hierarchystr = "'.implode(',',array_reverse($parentArr)).'" '.
					'WHERE (ts.taxauthid = '.$taxAuthId.') AND ts.tid = '.$tid;
				//echo "<div>".$sqlInsert."</div>";
				$this->conn->query($sqlInsert);
			}
			unset($parentArr);
		}
		
		
		$resultHier->close();
	}
	
	protected function buildHierarchy_old($taxAuthId){
		do{
			unset($hArr);
			$hArr = Array();
			$tempArr = Array();
			$sql = 'SELECT ts.tid FROM taxstatus ts WHERE (taxauthid = '.$taxAuthId.') AND ts.hierarchystr IS NULL LIMIT 100';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				while($row = $rs->fetch_object()){
					$hArr[$row->tid] = $row->tid;
				}
				do{
					unset($tempArr);
					$tempArr = Array();
					$sql2 = "SELECT IFNULL(ts.parenttid,0) AS parenttid, ts.tid ".
						"FROM taxstatus ts WHERE (taxauthid = ".$taxAuthId.") AND (ts.tid IN(".implode(",",array_keys($hArr))."))";
					//echo $sql2."<br />";
					$rs2 = $this->conn->query($sql2);
					while($row2 = $rs2->fetch_object()){
						$tid = $row2->tid;
						$pTid = $row2->parenttid;
						if($pTid && $tid != $pTid){
							if(array_key_exists($tid,$hArr)){
								$tempArr[$pTid][$tid] = $hArr[$tid];
								unset($hArr[$tid]);
							}
						}
					}
					foreach($tempArr as $p => $c){
						$hArr[$p] = $c;
					}
					$rs2->close();
				}while($tempArr);
				//Process hierarchy strings
				$finalArr = Array();
				$finalArr = $this->getLeaves($hArr);
				foreach($finalArr as $hStr => $taxaArr){
					$sqlInsert = "UPDATE taxstatus ts ".
						"SET ts.hierarchystr = '".$hStr."' ".
						"WHERE (ts.taxauthid = ".$taxAuthId.") AND (ts.tid IN(".implode(",",$taxaArr).")) AND (ts.hierarchystr IS NULL)";
					//echo "<div>".$sqlInsert."</div>";
					$this->conn->query($sqlInsert);
				}
			}
		}while($rs->num_rows);
	}
	
	private function getLeaves($inArr,$seed=""){
		$retArr = Array();
		foreach($inArr as $p => $t){
			if(is_array($t)){
				$newSeed = $seed.",".$p;
				$retArr = array_merge($retArr,$this->getLeaves($t,$newSeed));
			}
			else{
				if(!$seed) $seed = $p;
				$retArr[substr($seed,1)][] = $t;
			}
		}
		return $retArr;
	}

	protected function setUploadTargetPath(){
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp";
		}
		$this->uploadTargetPath = $tPath."/"; 
    }

    public function getTargetArr(){
		$retArr = $this->getUploadTaxaArr();
		unset($retArr['unitind1']);
		unset($retArr['unitind2']);
		$retArr['unitname1'] = 'genus';
		unset($retArr['genus']);
		$retArr['unitname2'] = 'specificepithet';
		$retArr['unitind3'] = 'taxonrank';
		$retArr['unitname3'] = 'infraspecificepithet';
		$tUnitArr = $this->getTaxonUnitArr();
		foreach($tUnitArr as $k => $v){
			$retArr[$v] = $v;
		}
		return $retArr;
    }
    
	private function getUploadTaxaArr(){
		//Get metadata
		$targetArr = array();
		$sql = "SHOW COLUMNS FROM uploadtaxa";
		$rs = $this->conn->query($sql);
    	while($row = $rs->fetch_object()){
    		$field = strtolower($row->Field);
    		if(strtolower($field) != 'tid' && strtolower($field) != 'tidaccepted' && strtolower($field) != 'parenttid'){
				$targetArr[$field] = $field;
    		}
    	}
    	$rs->close();
		
		return $targetArr;
	}

	private function getTaxonUnitArr(){
		//Get metadata
		$retArr = array();
		$sql = "SELECT rankid, rankname FROM taxonunits WHERE rankid < 220";
		$rs = $this->conn->query($sql);
    	while($r = $rs->fetch_object()){
			$retArr[$r->rankid] = strtolower($r->rankname);
    	}
    	$rs->close();
		return $retArr;
	}

	public function getSourceArr(){
		$sourceArr = array();
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'rb') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$sourceArr[] = $fieldStr;
			}
			else{
				break;
			}
		}
		return $sourceArr;
	}
	
	public function getTaxAuthorityArr(){
		$retArr = array();
		$sql = 'SELECT taxauthid, name FROM taxauthority ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->taxauthid] = $r->name;
			}
			$rs->close();
		} 
		return $retArr;
	}
	
	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	protected function encodeString($inStr){
 		global $charset;
 		$retStr = trim($inStr);
		$retStr = str_replace("\"","'",$retStr);
 		if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
				$retStr = utf8_encode($retStr);
				//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
			}
		}
		elseif(strtolower($charset) == "iso-8859-1"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
				$retStr = utf8_decode($retStr);
				//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
			}
		}
		return $retStr;
	}
}
?>
