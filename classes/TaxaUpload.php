<?php
include_once($serverRoot.'/config/dbconnection.php');

class TaxaUpload{
	
	private $conn;
	private $uploadFileName;
	private $uploadTargetPath;
	private $taxAuthId = 1;
	private $statArr = array();
	
	private $verboseMode = 1; // 0 = silent, 1 = echo only, 2 = echo and log
	private $logFH;
	private $errorStr = '';

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
 		$this->setUploadTargetPath();
 		set_time_limit(3000);
		ini_set("max_input_time",120);
  		ini_set('auto_detect_line_endings', true);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
		if($this->verboseMode == 2){
			if($this->logFH) fclose($this->logFH);
		}
	}
	
	public function setUploadFile($ulFileName = ""){
		if($ulFileName){
			//URL to existing file  
			if(file_exists($ulFileName)){
				$pos = strrpos($ulFileName,"/");
				if(!$pos) $pos = strrpos($ulFileName,"\\");
				$this->uploadFileName = substr($ulFileName,$pos+1);
				//$this->outputMsg($this->uploadFileName;
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
		$this->outputMsg('Starting Upload',0);
		$this->conn->query("DELETE FROM uploadtaxa");
		$this->conn->query("OPTIMIZE TABLE uploadtaxa");
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		$uploadTaxaFieldArr = $this->getUploadTaxaFieldArr();
		$taxonUnitArr = $this->getTaxonUnitArr();
		$uploadTaxaIndexArr = array();		//Array of index values associated with uploadtaxa table; array(index => targetName)
		$taxonUnitIndexArr = array();		//Array of index values associated with taxonunits table;
		foreach($headerArr as $k => $sourceName){
			$sourceName = TRIM(strtolower($sourceName));
			if(array_key_exists($sourceName,$fieldMap)){
				$targetName = $fieldMap[$sourceName];
				if(in_array($targetName,$uploadTaxaFieldArr)){
					//Is a taxa table target field 
					$uploadTaxaIndexArr[$k] = $targetName;
				}
				if($targetName == 'unitname1') $targetName = 'genus';
				if(in_array($targetName,$taxonUnitArr)){
					$taxonUnitIndexArr[$k] = array_search($targetName,$taxonUnitArr);  //array(recIndex => rankid)
				}
			}
		}
		$parentIndex = 0; 
		if(!in_array('parentstr',$uploadTaxaIndexArr)){
			$parentIndex = max(array_keys($uploadTaxaIndexArr))+1;
			$uploadTaxaIndexArr[$parentIndex] = 'parentstr';
		}
		$familyIndex = 0; 
		if(in_array('family',$fieldMap)) $familyIndex = array_search(array_search('family',$fieldMap),$headerArr);
		//scinameinput field is required
		if(in_array("scinameinput",$fieldMap) || count($taxonUnitIndexArr) > 2){
			$recordCnt = 0;
			asort($taxonUnitIndexArr);
			$childParentArr = array();		//array(taxon => array('p'=>parentStr,'r'=>rankid)
			$this->conn->query('SET autocommit=0');
			$this->conn->query('SET unique_checks=0');
			$this->conn->query('SET foreign_key_checks=0');
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
				if($parentIndex){
					$recordArr[$parentIndex] = 'PENDING:'.$parentStr;  
				}
				if(in_array("scinameinput",$fieldMap)){
					//Load relavent fields into uploadtaxa table
					$sql = $sqlBase;
					$valueSql = "";
					foreach($uploadTaxaIndexArr as $recIndex => $targetField){
						$valIn = $this->cleanInStr($this->encodeString($recordArr[$recIndex]));
						if($targetField == 'scinameinput' && !$valIn){
							$valueSql = '';
							$recordCnt--;
							break;
						} 
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
					if($valueSql){
						$sql .= 'VALUES ('.substr($valueSql,1).')';
						//echo "<div>".$sql."</div>"; exit;
						if($this->conn->query($sql)){
							if($recordCnt%1000 == 0){
								$this->outputMsg('Upload count: '.$recordCnt,1);
								ob_flush();
								flush();
							}
						}
						else{
							$this->outputMsg('ERROR loading taxon: '.$this->conn->error);
						}
					}
				}
				$recordCnt++;
			}
			$this->conn->query('COMMIT');
			$this->conn->query('SET autocommit=1');
			$this->conn->query('SET unique_checks=1');
			$this->conn->query('SET foreign_key_checks=1');
			
			//Process and load taxon units data ($childParentArr)
			foreach($childParentArr as $taxon => $tArr){
				$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput,rankid,parentstr,family,acceptance) '.
					'VALUES ("'.$taxon.'",'.$tArr['r'].',"'.$tArr['p'].'",'.(array_key_exists('f',$tArr)?'"'.$tArr['f'].'"':'NULL').',1)';
				if(!$this->conn->query($sql)){
					$this->outputMsg('ERROR loading taxonunit: '.$this->conn->error);
				}
			}
			$this->outputMsg($recordCnt.' taxon records pre-processed');
			$this->removeUploadFile();
		}
		else{
			$this->outputMsg('ERROR: Scientific name is not mapped to &quot;scinameinput&quot;');
		}
		fclose($fh);
		$this->setUploadCount();
	}

	public function loadItisFile(){
		$this->outputMsg('Starting Upload');
		//Initiate upload process
		$extraArr = array();
		$authArr = array();
		$this->conn->query('DELETE FROM uploadtaxa');
		$this->conn->query('OPTIMIZE TABLE uploadtaxa');
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r') or die("Can't open file");
		$this->outputMsg('Taxa file uploaded and successfully opened');
		
		//First run through file and grab and store Authors, Synonyms, and Vernaculars
		$delimtStr = "";
		$this->outputMsg('Harvesting authors, synonyms, and vernaculars');
		while($record = fgets($fh)){
			if(!$delimtStr){
				$delimtStr = "|";
				if(!strpos($record,"|") && strpos($record,",")){
					$delimtStr = ",";
				}
			}
			if(substr($record,4) != '[TU]'){
				$recordArr = explode($delimtStr,$record);
				$this->cleanInArr($recordArr);
				$this->encodeArr($recordArr);
				if($recordArr[0] == "[SY]"){
					$extraArr[$recordArr[2]]['s'] = $recordArr[3];
				}
				elseif($recordArr[0] == "[TA]"){
					$authArr[$recordArr[1]] = $recordArr[2];
				}
				elseif($recordArr[0] == "[VR]"){
					$extraArr[$recordArr[4]]['v'] = $recordArr[3];
					$extraArr[$recordArr[4]]['l'] = $recordArr[5];
				}
			}
		}
		if($authArr){
			$this->outputMsg('Authors mapped');
		}
		if($extraArr){
			$this->outputMsg('Synonyms and Vernaculars mapped');
		}

		//Load taxa records
		$this->outputMsg('Harvest and loading Taxa... ');
		$recordCnt = 0;
		rewind($fh);
		
		$this->conn->query('SET autocommit=0');
		$this->conn->query('SET unique_checks=0');
		$this->conn->query('SET foreign_key_checks=0');
		while($record = fgets($fh)){
			$recordArr = explode($delimtStr,$record);
			if($recordArr[0] == "[TU]"){
				$this->cleanInArr($recordArr);
				$this->encodeArr($recordArr);
				$this->loadItisTaxonUnit($recordArr,$extraArr,$authArr);
				$recordCnt++;
			}
		}
		$this->deleteIllegalHomonyms();
		$this->conn->query('COMMIT');
		$this->conn->query('SET autocommit=1');
		$this->conn->query('SET unique_checks=1');
		$this->conn->query('SET foreign_key_checks=1');
		
		$this->outputMsg($recordCnt.' records loaded');
		fclose($fh);
		$this->setUploadCount();
		$this->removeUploadFile();
	}

	private function loadItisTaxonUnit($tuArr,$extraArr,$authArr){
		if(count($tuArr) > 24){
			
			$unitInd3 = ($tuArr[8]?$tuArr[8]:$tuArr[6]);
			$unitName3 = ($tuArr[9]?$tuArr[9]:$tuArr[7]);
			$sciName = TRIM($tuArr[2]." ".$tuArr[3].($tuArr[4]?" ".$tuArr[4]:"")." ".$tuArr[5]." ".$unitInd3." ".$unitName3);
			$sciName = preg_REPLACE('/\s\s+/', ' ',$sciName);
			$author = '';
			if($tuArr[20] && array_key_exists($tuArr[20],$authArr)){
				$author = $authArr[$tuArr[20]];
				unset($authArr[$tuArr[20]]);
			}
			$sourceId = $tuArr[1];
			$sourceAcceptedId = '';
			$acceptance = '1';
			$vernacular = '';
			$vernlang = '';
			if(array_key_exists($sourceId,$extraArr)){
				$eArr = $extraArr[$sourceId];
				if(array_key_exists('s',$eArr)){
					$sourceAcceptedId = $eArr['s'];
					$acceptance = '0';
				}
				if(array_key_exists('v',$eArr)){
					$vernacular = $eArr['v'];
					$vernlang = $eArr['l'];
				}
				unset($extraArr[$sourceId]);
			}
			$sql = "INSERT INTO uploadtaxa(SourceId,scinameinput,sciname,unitind1,unitname1,unitind2,unitname2,unitind3,".
				"unitname3,SourceParentId,author,rankid,SourceAcceptedId,acceptance,vernacular,vernlang) ".
				"VALUES (".$sourceId.',"'.$sciName.'","'.$sciName.'",'.
				($tuArr[2]?'"'.$tuArr[2].'"':"NULL").",".
				($tuArr[3]?'"'.$tuArr[3].'"':"NULL").",".
				($tuArr[4]?'"'.$tuArr[4].'"':"NULL").",".
				($tuArr[5]?'"'.$tuArr[5].'"':"NULL").",".
				($unitInd3?'"'.$unitInd3.'"':"NULL").",".($unitName3?'"'.$unitName3.'"':"NULL").",".
				($tuArr[18]?$tuArr[18]:"NULL").",".
				($author?'"'.$author.'"':"NULL").",".
				($tuArr[24]?$tuArr[24]:"NULL").",".
				($sourceAcceptedId?$sourceAcceptedId:'NULL').','.$acceptance.','.
				($vernacular?'"'.$vernacular.'"':'NULL').','.
				($vernlang?'"'.$vernlang.'"':'NULL').')';
			//echo '<div>'.$sql.'</div>';
			if(!$this->conn->query($sql)){
				//Failed because name is already in table, thus replace if this one is accepted
				if($acceptance){
					$sql = 'REPLACE'.substr($sql,6);
					if(!$this->conn->query($sql)){
						$this->outputMsg('ERROR loading ITIS taxon: '.$this->conn->error);
					}
				}
			}
		}
	}
	
	private function removeUploadFile(){
		if($this->uploadTargetPath && $this->uploadFileName){
			if(file_exists($this->uploadTargetPath.$this->uploadFileName)){
				unlink($this->uploadTargetPath.$this->uploadFileName);
			}
		}
	}

	private function deleteIllegalHomonyms(){
		$homonymArr = array();
		//Grab homonyms
		$sql = 'SELECT sciname, COUNT(*) cnt FROM uploadtaxa GROUP BY sciname HAVING cnt > 1';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$homonymArr[] = $r->sciname;
		}
		$rs->free();
		//Remove unaccepted, illegal homonyms
		if($homonymArr){
			$sql2 = 'DELETE FROM uploadtaxa '.
				'WHERE (sciname IN("'.implode('","',$homonymArr).'")) AND (acceptance = 0) ';
			$this->conn->query($sql2);
		}		
	}

	public function cleanUpload(){
		$sspStr = 'subsp.';
		$inSspStr = 'ssp.';
		$sql = 'SELECT unitind3 FROM taxa WHERE rankid = 230 AND unitind3 LIKE "s%" LIMIT 1';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$sspStr = $r->unitind3;
		}
		$rs->free();
		if($sspStr == 'ssp.') $inSspStr = 'subsp.';
		
		$this->outputMsg('Cleaning AcceptedStr... ');
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = REPLACE(AcceptedStr," '.$inSspStr.' "," '.$sspStr.' ") '.
			'WHERE (AcceptedStr LIKE "% '.$inSspStr.' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = REPLACE(AcceptedStr," notho'.$inSspStr.' "," '.$sspStr.' ") '.
			'WHERE (AcceptedStr LIKE "% notho'.$inSspStr.' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = REPLACE(AcceptedStr," var "," var. ") WHERE AcceptedStr LIKE "% var %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = REPLACE(AcceptedStr," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") '.
			'WHERE (AcceptedStr LIKE "% '.substr($sspStr,0,strlen($sspStr)-1).' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = REPLACE(AcceptedStr," sp.","") WHERE AcceptedStr LIKE "% sp."';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = TRIM(AcceptedStr) WHERE AcceptedStr LIKE "% " OR AcceptedStr LIKE " %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error.'... ',1);
		}
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = REPLACE(AcceptedStr,"  "," ") WHERE AcceptedStr LIKE "%  %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error.'... ',1);
		}
		
		//Value if only a Source Id was supplied
		$sql = 'UPDATE uploadtaxa AS u LEFT JOIN uploadtaxa AS u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.AcceptedStr = u2.scinameinput '.
			'WHERE u.sourceAcceptedId IS NOT NULL AND u2.sourceId IS NOT NULL AND ISNULL(u.AcceptedStr)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error.'... ',1);
		}
		
		//Insert into uploadtaxa table all accepted taxa not already present in scinameinput. If they turn out to be in taxa table, they will be deleted later
		/*
		 * Let's require existance of accepted names
		$this->outputMsg('Appending accepted taxa not present in scinameinput... ',1);
		$sql = 'INSERT INTO uploadtaxa(scinameinput) '.
			'SELECT DISTINCT u.AcceptedStr '.
			'FROM uploadtaxa u LEFT JOIN uploadtaxa ul2 ON u.AcceptedStr = ul2.scinameinput '.
			'WHERE u.AcceptedStr IS NOT NULL AND ul2.scinameinput IS NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		*/

		//Clean sciname field (sciname input gets cleaned later) 
		$this->outputMsg('Cleaning sciname fields... ');
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," '.$inSspStr.' "," '.$sspStr.' ") WHERE (sciname LIKE "% '.$inSspStr.' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," notho'.$inSspStr.' "," '.$sspStr.' ") WHERE (sciname LIKE "% notho'.$inSspStr.' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," var "," var. ") WHERE sciname LIKE "% var %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") WHERE (sciname LIKE "% '.substr($sspStr,0,strlen($sspStr)-1).' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," cf. "," ") WHERE sciname LIKE "% cf. %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," cf "," ") WHERE sciname LIKE "% cf %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff. "," ") WHERE sciname LIKE "% aff. %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff "," ") WHERE sciname LIKE "% aff %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," sp.","") WHERE sciname LIKE "% sp."';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," sp","") WHERE sciname LIKE "% sp"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = TRIM(sciname) WHERE sciname LIKE "% " OR sciname LIKE " %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname,"  "," ") WHERE sciname LIKE "%  %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Clean scinameinput field
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," '.$inSspStr.' "," '.$sspStr.' ") '.
			'WHERE (scinameinput LIKE "% '.$inSspStr.' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," notho'.$inSspStr.' "," '.$sspStr.' ") '.
			'WHERE (scinameinput LIKE "% notho'.$inSspStr.' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," var "," var. ") WHERE scinameinput LIKE "% var %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") '.
			'WHERE (scinameinput LIKE "% '.substr($sspStr,0,strlen($sspStr)-1).' %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," cf. "," ") WHERE scinameinput LIKE "% cf. %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," cf "," ") WHERE scinameinput LIKE "% cf %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff. "," ") WHERE scinameinput LIKE "% aff. %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff "," ") WHERE scinameinput LIKE "% aff %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," sp.","") WHERE scinameinput LIKE "% sp."';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," sp","") WHERE scinameinput LIKE "% sp"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = TRIM(scinameinput) WHERE scinameinput LIKE "% " OR scinameinput LIKE " %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput,"  "," ") WHERE scinameinput LIKE "%  %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Parse scinameinput into unitind and unitname fields 
		$this->outputMsg('Parse scinameinput field...');
		$sql = 'UPDATE uploadtaxa SET unitind1 = "x" WHERE ISNULL(unitind1) AND scinameinput LIKE "x %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET unitind2 = "x" WHERE ISNULL(unitind2) AND scinameinput LIKE "% x %" AND scinameinput NOT LIKE "% % x %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET unitname1 = TRIM(substring(scinameinput,3,LOCATE(" ",scinameinput,3)-3)) '.
			'WHERE ISNULL(unitname1) and scinameinput LIKE "x %"';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname1 = TRIM(substring(scinameinput,1,LOCATE(" ",CONCAT(scinameinput," ")))) '.
			'WHERE ISNULL(unitname1) ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname2 = TRIM(substring(scinameinput,LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2,LOCATE(" ",CONCAT(scinameinput," "),LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2)-(LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2))) '.
			'WHERE ISNULL(unitname2)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET unitname2 = NULL WHERE unitname2 = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET unitind3 = "f.", rankid = 260 '.
			'WHERE ISNULL(unitind3) AND (scinameinput LIKE "% f. %" OR scinameinput LIKE "% forma %") AND (ISNULL(rankid) OR rankid = 260)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET unitind3 = "var.", rankid = 240 '.
			'WHERE ISNULL(unitind3) AND scinameinput LIKE "% var. %" AND (ISNULL(rankid) OR rankid = 240)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET unitind3 = "'.$sspStr.'", rankid = 230 '.
			'WHERE ISNULL(unitind3) AND (scinameinput LIKE "% '.$sspStr.' %") AND (ISNULL(rankid) OR rankid = 230)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname3 = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(unitind3)+1, '.
			'LOCATE(" ",CONCAT(scinameinput," "),LOCATE(unitind3,scinameinput)+LENGTH(unitind3)+1)-LOCATE(unitind3,scinameinput)-LENGTH(unitind3))) '.
			'WHERE ISNULL(unitname3) AND rankid > 220 ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE IGNORE uploadtaxa '.
			'SET sciname = scinameinput '.
			'WHERE ISNULL(sciname) AND ((scinameinput LIKE "% x %" AND unitind3 IS NOT NULL) OR scinameinput LIKE "% % x %")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE IGNORE uploadtaxa '.
			'SET sciname = CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3, unitname3) '.
			'WHERE ISNULL(sciname)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u LEFT JOIN uploadtaxa u2 ON u.sourceParentId = u2.sourceId '.
			'SET u.parentstr = u2.sciname '.
			'WHERE ISNULL(u.parentstr) AND u.sourceParentId IS NOT NULL AND u2.sourceId IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u LEFT JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.acceptedstr = u2.sciname '.
			'WHERE ISNULL(u.acceptedstr) AND u.sourceAcceptedId IS NOT NULL AND u2.sourceId IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Delete taxa where sciname can't be inserted. These are taxa where sciname already exists
		$sql = 'DELETE FROM uploadtaxa WHERE ISNULL(sciname)';
		$this->conn->query($sql);

		//Link names already in theusaurus 
		$this->outputMsg('Linking names already in thesaurus... ');
		$sql = 'UPDATE uploadtaxa u LEFT JOIN taxa t ON u.sciname = t.sciname '.
			'SET u.tid = t.tid WHERE ISNULL(u.tid)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.acceptedstr = u2.scinameinput '.
			'SET u1.tidaccepted = u2.tid '. 
			'WHERE ISNULL(u1.tidaccepted) AND u2.tid IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		$sql = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.sourceParentId = u2.sourceId '.
			'SET u1.family = u2.sciname '.
			'WHERE u2.sourceId IS NOT NULL AND u1.sourceParentId IS NOT NULL AND u2.rankid = 140 AND ISNULL(u1.family) ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname '.
			'SET u1.family = u2.family '.
			'WHERE ISNULL(u1.family) AND u2.family IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE (uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.sourceAcceptedId = u2.sourceId) '.
			'SET u1.family = u2.family '.
			'WHERE u1.sourceAcceptedId IS NOT NULL AND  u2.sourceId IS NOT NULL AND ISNULL(u1.family) AND u2.family IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE (uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname) '.
			'LEFT JOIN uploadtaxa u3 ON u2.sourceParentId = u3.sourceId '.
			'SET u1.family = u3.sciname '.
			'WHERE u2.sourceParentId IS NOT NULL AND u3.sourceId IS NOT NULL '.
			'AND ISNULL(u1.family) AND u1.rankid > 140 AND u2.rankid = 180 AND u3.rankid = 140';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u0 LEFT JOIN uploadtaxa u1 ON u0.sourceAcceptedId = u1.sourceid '.
			'SET u0.family = u1.family '.
			'WHERE u0.sourceParentId IS NOT NULL AND u1.sourceId IS NOT NULL AND '.
			'ISNULL(u0.family) AND u0.rankid > 140 AND u1.family IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u0 LEFT JOIN uploadtaxa u1 ON u0.scinameinput = u1.acceptedstr '.
			'SET u0.family = u1.family '.
			'WHERE ISNULL(u0.family) AND u0.rankid > 140 AND u1.family IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		$this->outputMsg('Loading vernaculars... ');
		//This is first pass for all taxa that have non-null tids just before they are removed
		$this->transferVernaculars();
		
		$sql = 'DELETE * FROM uploadtaxa WHERE tid IS NOT NULL';
		//$this->conn->query($sql);

		$this->outputMsg('Set null rankid values... ');
		$sql = 'UPDATE uploadtaxa SET rankid = 140 '.
			'WHERE (ISNULL(rankid)) AND (sciname NOT LIKE "% %") AND (sciname LIKE "%aceae" || sciname LIKE "%idae")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET rankid = 220 WHERE ISNULL(rankid) AND unitname1 IS NOT NULL AND unitname2 IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET rankid = 180 WHERE ISNULL(rankid) AND unitname1 IS NOT NULL AND ISNULL(unitname2)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		$this->outputMsg('Set null author values... ');
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LENGTH(sciname)+1)) '.
			'WHERE (ISNULL(author)) AND rankid <= 220';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(CONCAT_WS(" ",unitind3,unitname3)))) '.
			'WHERE (ISNULL(author)) AND rankid > 220';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET author = NULL WHERE author = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		$this->outputMsg('Populating null family values... ');
		$sql = 'UPDATE uploadtaxa SET family = NULL WHERE family = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE (uploadtaxa ut LEFT JOIN taxa t ON ut.unitname1 = t.sciname) '.
			'LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.family = ts.family '.
			'WHERE ts.taxauthid = '.$this->taxAuthId.' AND ut.rankid > 140 AND t.rankid = 180 AND ts.family IS NOT NULL AND (ISNULL(ut.family))';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE ((uploadtaxa ut LEFT JOIN taxa t ON ut.family = t.sciname) '.
			'LEFT JOIN taxstatus ts ON t.tid = ts.tid) '.
			'LEFT JOIN taxa t2 ON ts.tidaccepted = t2.tid '.
			'SET ut.family = t2.sciname '.
			'WHERE ts.taxauthid = '.$this->taxAuthId.' AND ut.rankid > 140 AND t.rankid = 140 AND ts.tid <> ts.tidaccepted AND ISNULL(ut.family) ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		$this->outputMsg('Populating and mapping parent taxon... ');
		$sql = 'UPDATE uploadtaxa '.
			'SET parentstr = CONCAT_WS(" ", unitname1, unitname2) '.
			'WHERE (ISNULL(parentstr) OR parentstr LIKE "PENDING:%") AND rankid > 220';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = unitname1 '.
			'WHERE (ISNULL(parentstr) OR parentstr LIKE "PENDING:%") AND rankid = 220';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = family '.
			'WHERE (ISNULL(parentstr) OR parentstr LIKE "PENDING:%") AND rankid = 180';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = SUBSTRING(parentstr,9) '.
			'WHERE (parentstr LIKE "PENDING:%")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.sourceAcceptedID = u2.sourceId '.
			'SET u1.sourceParentId = u2.sourceParentId, u1.parentStr = u2.parentStr '.
			'WHERE ISNULL(u1.sourceParentId) AND u1.sourceAcceptedID IS NOT NULL AND u2.sourceParentId IS NOT NULL AND u1.rankid < 220 ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		$sql = 'UPDATE uploadtaxa up LEFT JOIN taxa t ON up.parentstr = t.sciname '.
			'SET parenttid = t.tid WHERE ISNULL(parenttid)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		//Load into uploadtaxa parents of infrasp not yet in taxa table 
		$this->outputMsg('Add parents that are not yet in uploadtaxa table... ');
		$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput, SciName, family, RankId, UnitName1, UnitName2, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.family, 220 as r, ut.unitname1, ut.unitname2, ut.unitname1, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid > 220 AND ISNULL(ut2.sciname) ';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up LEFT JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE ISNULL(up.parenttid)';
		$this->conn->query($sql);
		
		//Load into uploadtaxa parents of species not yet in taxa table 
		$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, family, RankId, UnitName1, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.family, 180 as r, ut.unitname1, ut.family, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ISNULL(ut.parenttid) AND ut.family IS NOT NULL AND ut.rankid = 220 AND ISNULL(ut2.sciname)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up LEFT JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE ISNULL(up.parenttid)';
		$this->conn->query($sql);

		//Load into uploadtaxa parents of genera not yet in taxa table
		/* 
		$defaultParent = 0;
		$defaultParentStr = '';
		if(!$defaultParent){
			$sql = 'SELECT tid, sciname FROM taxa WHERE rankid <= 10';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$defaultParent = $r->tid;
				$defaultParentStr = $r->sciname;
			}
			$rs->free();
		}
		if($defaultParent){
			$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, family, RankId, UnitName1, parenttid, parentstr, source) '.
				'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.family, 140 as r, ut.parentstr,'.$defaultParent.',"'.$defaultParentStr.'",ut.source '.
				'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
				'WHERE ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid = 180 AND ut2.sciname IS NULL';
			$this->conn->query($sql);
		}
		*/

		//Set acceptance to 0 where sciname <> acceptedstr
		$sql = 'UPDATE uploadtaxa '.
			'SET acceptance = 0 '.
			'WHERE acceptedstr IS NOT NULL AND sciname IS NOT NULL AND sciname <> acceptedstr';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET acceptance = 1 '.
			'WHERE ISNULL(acceptedstr) AND ISNULL(TidAccepted)';
		$this->conn->query($sql);
		$this->outputMsg('Done processing taxa');
	}
	
	public function analysisUpload(){
		$retArr = array();
		//Get total number
		$sql1 = 'SELECT count(*) as cnt FROM uploadtaxa';
		$rs1 = $this->conn->query($sql1);
		while($r1 = $rs1->fetch_object()){
			$this->statArr['total'] = $r1->cnt;
		}
		$rs1->free();

		//Get number matching existing taxa and number of new
		$sql2 = 'SELECT count(*) as cnt FROM uploadtaxa WHERE tid IS NOT NULL';
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			$this->statArr['exist'] = $r2->cnt;
			$this->statArr['new'] = $this->statArr['total'] - $this->statArr['exist'];
		}
		$rs2->free();

		//Get acceptance count
		$sql3 = 'SELECT acceptance, count(*) AS cnt '.
			'FROM uploadtaxa '.
			'GROUP BY acceptance';
		$rs3 = $this->conn->query($sql3);
		while($r3 = $rs3->fetch_object()){
			if($r3->acceptance == 0) $this->statArr['nonaccepted'] = $r3->cnt;
			if($r3->acceptance == 1) $this->statArr['accepted'] = $r3->cnt;
		}
		$rs3->free();

		//Tag bad taxa that didn't parse correctly
		$sql4 = 'UPDATE uploadtaxa SET notes = "FAILED: Unable to parse input scientific name" WHERE sciname IS NULL';
		if(!$this->conn->query($sql4)){
			$this->outputMsg('ERROR tagging non-parsed names: '.$this->conn->error,1);
		}
		
		//Tag non-accepted taxa linked to non-existent taxon
		$sql5 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.notes = "FAILED: Non-accepted taxa linked to non-existent taxa" '.
			'WHERE (u1.acceptance = 0) AND ((ISNULL(u1.tidAccepted) AND ISNULL(u2.sciname)) OR (ISNULL(u2.sciname))) ';
		if(!$this->conn->query($sql5)){
			$this->outputMsg('ERROR tagging non-accepted taxon linked to non-existent taxon: '.$this->conn->error,1);
		}
		
		//Tag non-accepted linked to other non-accepted taxa
		$sql6a = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.notes = "FAILED: Non-accepted linked to another non-accepted taxa" '.
			'WHERE (u1.acceptance = 0) AND ((ISNULL(u1.tidAccepted) AND ISNULL(u2.sciname)) OR (u2.acceptance = 0))';
		if(!$this->conn->query($sql6a)){
			$this->outputMsg('ERROR tagging non-accepted linked to non-accepted (#1): '.$this->conn->error,1);
		}
		$sql6b = 'UPDATE uploadtaxa u LEFT JOIN taxstatus ts ON u.tidaccepted = ts.tid '.
			'SET u.notes = "FAILED: Non-accepted linked to another non-accepted taxa" '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (u.acceptance = 0) AND (u.tidAccepted IS NOT NULL) AND (ts.tid <> ts.tidaccepted)';
		if(!$this->conn->query($sql6b)){
			$this->outputMsg('ERROR tagging non-accepted linked to non-accepted (#2): '.$this->conn->error,1);
		}
		
		//Tag taxa with non-existent parents
		$sql6 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
			'SET u1.notes = "FAILED: Taxa with non-existent parent taxon" '.
			'WHERE u1.RankId > 10 AND (ISNULL(u1.tid)) AND ((ISNULL(u1.parentTid) AND ISNULL(u2.sciname)) OR (ISNULL(u2.sciname))) ';
		if(!$this->conn->query($sql6)){
			$this->outputMsg('ERROR tagging taxa with non-existent parent taxon: '.$this->conn->error,1);
		}
		
		//Tag taxa with a FAILED parent
		$sql8a = 'SELECT COUNT(u1.sciname) as cnt '.
			'FROM uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
			'WHERE (u2.notes LIKE "FAILED%") AND (ISNULL(u1.notes) OR u1.notes NOT LIKE "FAILED%")';
		$rs8a = $this->conn->query($sql8a);
		$loopCnt = 0;
		while($r8a = $rs8a->fetch_object()){
			if($r8a->cnt == 0) break;
			$sql8b = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
				'SET u1.notes = "FAILED: Taxa linked to a FAILED parent" '.
				'WHERE (u2.notes LIKE "FAILED%") AND (ISNULL(u1.notes) OR u1.notes NOT LIKE "FAILED%")';
			if(!$this->conn->query($sql8b)){
				$this->outputMsg('ERROR tagging taxa with FAILED parents: '.$this->conn->error,1);
			}
			$rs8a->free();
			$rs8a = $this->conn->query($sql8a);
			$loopCnt++;
			if($loopCnt > 20){
				$this->outputMsg('ERROR looping: too many parent loops',1);
				break;
			}
		}
		if($rs8a) $rs8a->free();

		//Tag non-accepted taxa linked to FAILED taxon
		$sql9 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.notes = "FAILED: Non-accepted taxa linked to a FAILED name" '.
			'WHERE u1.acceptance = 0 AND u1.notes NOT LIKE "FAILED%" AND u2.notes LIKE "FAILED%"';
		if(!$this->conn->query($sql9)){
			$this->outputMsg('ERROR tagging non-accepeted linked to FAILED name: '.$this->conn->error,1);
		}

		//Get bad counts
		$sql = 'SELECT notes, count(*) as cnt FROM uploadtaxa WHERE notes LIKE "FAILED%" GROUP BY notes';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->statArr['bad'][substr($r->notes,7)] = $r->cnt;
		}
		$rs->free();

		return $retArr;
	}

	public function transferUpload(){
		$startLoadCnt = -1;
		$endLoadCnt = 0;
		$loopCnt = 0;
		$this->outputMsg('Starting data transfer...');
		//Prime table with kingdoms that are not yet in table
		$sql = 'INSERT IGNORE INTO taxa(SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes) '.
			'SELECT DISTINCT ut.SciName, ut.RankId, ut.UnitInd1, ut.UnitName1, ut.UnitInd2, ut.UnitName2, ut.UnitInd3, '.
			'ut.UnitName3, ut.Author, ut.Source, ut.Notes '.
			'FROM uploadtaxa AS ut '.
			'WHERE (ISNULL(ut.TID) AND rankid = 10)';
		if($this->conn->query($sql)){
			$sql = 'INSERT IGNORE INTO taxstatus(tid, tidaccepted, taxauthid, parenttid) '.
				'SELECT DISTINCT t.tid, t.tid, '.$this->taxAuthId.', t.tid '.
				'FROM uploadtaxa AS ut LEFT JOIN taxa AS t ON ut.sciname = t.sciname '.
				'WHERE (ISNULL(ut.TID) AND ut.rankid = 10)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
		}
		else{
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		//Loop through and transfer taxa to taxa table
		WHILE(($endLoadCnt > 0 || $startLoadCnt <> $endLoadCnt) && $loopCnt < 30){
			$sql = 'SELECT COUNT(*) AS cnt FROM uploadtaxa';
			$rs = $this->conn->query($sql);
			$r = $rs->fetch_object();
			$startLoadCnt = $r->cnt;

			$this->outputMsg('Starting loop '.$loopCnt);
			$this->outputMsg('Transferring taxa to taxon table... ');
			$sql = 'INSERT IGNORE INTO taxa(SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes) '.
				'SELECT DISTINCT ut.SciName, ut.RankId, ut.UnitInd1, ut.UnitName1, ut.UnitInd2, ut.UnitName2, ut.UnitInd3, '.
				'ut.UnitName3, ut.Author, ut.Source, ut.Notes '.
				'FROM uploadtaxa AS ut '.
				'WHERE (ISNULL(ut.TID) AND ut.parenttid IS NOT NULL AND rankid IS NOT NULL ) '.
				'ORDER BY ut.RankId ASC ';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa AS ut LEFT JOIN taxa AS t ON ut.sciname = t.sciname '.
				'SET ut.tid = t.tid WHERE ISNULL(ut.tid)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa AS ut1 LEFT JOIN uploadtaxa AS ut2 ON ut1.sourceacceptedid = ut2.sourceid '.
				'LEFT JOIN taxa AS t ON ut2.sciname = t.sciname '.
				'SET ut1.tidaccepted = t.tid '.
				'WHERE (ut1.acceptance = 0) AND (ISNULL(ut1.tidaccepted)) AND (ut1.sourceacceptedid IS NOT NULL) AND (ut2.sourceid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa AS ut LEFT JOIN taxa AS t ON ut.acceptedstr = t.sciname '.
				'SET ut.tidaccepted = t.tid '.
				'WHERE ut.acceptance = 0 AND ISNULL(ut.tidaccepted) AND ut.acceptedstr IS NOT NULL';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa SET tidaccepted = tid '.
				'WHERE ISNULL(tidaccepted) AND tid IS NOT NULL';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			$this->outputMsg('Create parent and accepted links... ');
			$sql = 'INSERT IGNORE INTO taxstatus(TID, TidAccepted, taxauthid, ParentTid, Family, UnacceptabilityReason) '.
				'SELECT DISTINCT ut.TID, ut.TidAccepted, '.$this->taxAuthId.', ut.ParentTid, ut.Family, ut.UnacceptabilityReason '.
				'FROM uploadtaxa AS ut '.
				'WHERE (ut.TID IS NOT NULL AND ut.TidAccepted IS NOT NULL AND ut.parenttid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			$this->outputMsg('Transferring vernaculars for new taxa... ');
			//Covers taxa with newly assigned tids just before they are removed
			$this->transferVernaculars(1);
			
			$this->outputMsg('Preparing for next round... ');
			$sql = 'DELETE FROM uploadtaxa WHERE tid IS NOT NULL AND tidaccepted IS NOT NULL';
			$this->conn->query($sql);

			//Update parentTids
			$sql = 'UPDATE uploadtaxa ut1 LEFT JOIN uploadtaxa AS ut2 ON ut1.sourceparentid = ut2.sourceid '.
				'LEFT JOIN taxa AS t ON ut2.sciname = t.sciname '.
				'SET ut1.parenttid = t.tid '.
				'WHERE ISNULL(ut1.parenttid) AND ut1.sourceparentid IS NOT NULL AND ut2.sourceid IS NOT NULL';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa up LEFT JOIN taxa AS t ON up.parentstr = t.sciname '.
				'SET up.parenttid = t.tid '.
				'WHERE ISNULL(up.parenttid)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			
			$sql = 'SELECT COUNT(*) as cnt FROM uploadtaxa';
			$rs = $this->conn->query($sql);
			$r = $rs->fetch_object();
			$endLoadCnt = $r->cnt;
			
			$loopCnt++;
		}

		$this->outputMsg('House cleaning... ');
		//Something is wrong with the buildHierarchy method. Needs to be fixed. 
		$this->buildHierarchy();
		
		$this->setKingdom();

		//Update occurrences with new tids
		$sql1 = 'UPDATE omoccurrences AS o LEFT JOIN taxa AS t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE ISNULL(o.TidInterpreted)';
		$this->conn->query($sql1);
		
		//Update occurrence images with new tids
		$sql2 = 'UPDATE images AS i LEFT JOIN omoccurrences AS o ON i.occid = o.occid '.
			'SET i.tid = o.TidInterpreted '.
			'WHERE ISNULL(i.tid) AND o.TidInterpreted IS NOT NULL';
		$this->conn->query($sql2);
		
		//Update geo lookup table 
		$sql3 = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '. 
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '. 
			'FROM omoccurrences AS o LEFT JOIN omoccurgeoindex AS g ON o.tidinterpreted = g.tid '.
			'WHERE ISNULL(g.tid) AND o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL';
		$this->conn->query($sql3);
	}

	private function transferVernaculars($secondRound = 0){
		$sql = 'SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL ';
		if($secondRound) $sql .= 'AND tidaccepted IS NOT NULL';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()){
			$vernArr = array();
			$vernStr = $r->vernacular;
			if(strpos($vernStr,"\t")) {
				$vernArr = explode("\t",$vernStr); 
			}
			elseif(strpos($vernStr,"|")){
				$vernArr = explode("|",$vernStr); 
			}
			elseif(strpos($vernStr,";")){
				$vernArr = explode(";",$vernStr); 
			}
			elseif(strpos($vernStr,",")){
				$vernArr = explode(",",$vernStr); 
			}
			else{
				$vernArr[] = $vernStr;
			}
			$langStr = $r->vernlang;
			if(!$langStr) $langStr = 'en';
			foreach($vernArr as $vStr){
				if($vStr){
					$sqlInsert = 'INSERT INTO taxavernaculars(tid, VernacularName, Language, Source) '.
						'VALUES('.$r->tid.',"'.$vStr.'","'.$langStr.'",'.($r->source?'"'.$r->source.'"':'NULL').')';
					if(!$this->conn->query($sqlInsert)){
						if(substr($this->conn->error,0,9) != 'Duplicate') $this->outputMsg('ERROR: '.$this->conn->error,1);
					}
				}
			}
		}
	}

	private function buildHierarchy(){
		$status = true;
		//Seed taxaenumtree table
		$sql = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
			'SELECT DISTINCT ts.tid, ts.parenttid, ts.taxauthid '. 
			'FROM taxstatus AS ts '. 
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND ts.tid NOT IN(SELECT tid FROM taxaenumtree WHERE taxauthid = '.$this->taxAuthId.')';
		//$this->outputMsg($sql;
		if($this->conn->query($sql)){
			//Continue building taxaenumtree  
			$sql2 = 'SELECT DISTINCT e.tid, ts.parenttid, ts.taxauthid '. 
				'FROM taxaenumtree AS e LEFT JOIN taxstatus AS ts ON e.parenttid = ts.tid AND e.taxauthid = ts.taxauthid '.
				'LEFT JOIN taxaenumtree AS e2 ON e.tid = e2.tid AND ts.parenttid = e2.parenttid AND e.taxauthid = e2.taxauthid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND ISNULL(e2.tid)';
			//$this->outputMsg($sql;
			$cnt = 0;
			$targetCnt = 0;
			do{
				if(!$this->conn->query('INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.$sql2)){
					$status = false;
					$this->errorStr = 'ERROR building taxaenumtree: '.$this->conn->error;
				}
				$rs = $this->conn->query($sql2);
				$targetCnt = $rs->num_rows;
				$cnt++;
			}while($status && $targetCnt && $cnt < 30);
		}
		else{
			$status = false;
			$this->errorStr = 'ERROR seeding taxaenumtree: '.$this->conn->error;
		}
		return $status;
	}
	
	private function setKingdom(){
		$status = true;
		//Seed taxaenumtree table
		$sql = 'UPDATE taxa AS t LEFT JOIN taxaenumtree AS te ON t.TID = te.tid '.
			'LEFT JOIN taxa AS t2 ON te.parenttid = t2.TID '.
			'LEFT JOIN taxonunits AS tu ON t2.SciName = tu.kingdomName '.
			'SET t.kingdomName = tu.kingdomName '.
			'WHERE te.taxauthid = '.$this->taxAuthId.' AND t2.RankId = 10 AND ISNULL(t.KingdomID) AND tu.rankid = 10 ';
		//$this->outputMsg($sql;
		if($this->conn->query($sql)){
			$status = true;
		}
		else{
			$status = false;
			$this->errorStr = 'ERROR setting kingdom: '.$this->conn->error;
		}
		return $status;
	}

	public function exportUploadTaxa(){
		$fieldArr = array('tid','family','scinameInput','sciname','author','rankId','unitInd1','unitName1','unitInd2','unitName2',
			'unitInd3','unitName3,parentTid','parentStr','acceptance','tidAccepted','acceptedStr','unacceptabilityReason',
			'securityStatus','source','notes','vernacular','vernlang','sourceId','sourceAcceptedId','sourceParentId');
		$fileName = 'taxaUpload_'.time().'.csv';
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$sql = 'SELECT '.implode(',',$fieldArr).' FROM uploadtaxa ';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$out = fopen('php://output', 'w');
			echo implode(',',$fieldArr)."\n";
			while($r = $rs->fetch_assoc()){
				fputcsv($out, $r);
			}
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
		$rs->free();
	}

	//Misc get data functions
	private function setUploadCount(){
		$sql = 'SELECT count(*) as cnt FROM uploadtaxa';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->statArr['upload'] = $r->cnt;
		}
		$rs->free();
	}

	public function getTargetArr(){
		$retArr = $this->getUploadTaxaFieldArr();
		unset($retArr['unitind1']);
		unset($retArr['unitind2']);
		$retArr['unitname1'] = 'genus';
		//unset($retArr['genus']);
		$retArr['unitname2'] = 'specificepithet';
		$retArr['unitind3'] = 'taxonrank';
		$retArr['unitname3'] = 'infraspecificepithet';
		$tUnitArr = $this->getTaxonUnitArr();
		foreach($tUnitArr as $k => $v){
			if($v != 'genus') $retArr[$v] = $v;
		}
		return $retArr;
	}
	
	private function getUploadTaxaFieldArr(){
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
		$rs->free();
		
		return $targetArr;
	}

	private function getTaxonUnitArr(){
		//Get metadata
		$retArr = array();
		$sql = "SELECT DISTINCT rankid, rankname FROM taxonunits WHERE rankid < 220";
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->rankid] = strtolower($r->rankname);
		}
		$rs->free();
		return $retArr;
	}

	public function getSourceArr(){
		$sourceArr = array();
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		foreach($headerArr as $field){
			$fieldStr = strtolower(TRIM($field));
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
			$rs->free();
		} 
		return $retArr;
	}

	//Setters and getters
	private function setUploadTargetPath(){
		$tPath = '';
		if(!$tPath && isset($GLOBALS["TEMP_DIR_ROOT"])){
			$tPath = $GLOBALS['TEMP_DIR_ROOT'];
			if(substr($tPath,-1) != '/') $tPath .= "/"; 
			if(file_exists($tPath.'downloads')) $tPath .= 'downloads/';
		}
		elseif(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS['SERVER_ROOT'];
			if(substr($tPath,-1) != '/') $tPath .= "/"; 
			$tPath .= "temp/downloads/";
		}
		if(substr($tPath,-1) != '/') $tPath .= '/';
		$this->uploadTargetPath = $tPath; 
	}

	public function setFileName($fName){
		$this->uploadFileName = $fName;
	}

	public function getFileName(){
		return $this->uploadFileName;
	}
	
	public function setTaxaAuthId($id){
		if(is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}
	
	public function getStatArr(){
		return $this->statArr;
	}
	
	public function getErrorStr(){
		return $this->errorStr;
	}

	public function setVerboseMode($vMode){
		global $SERVER_ROOT;
		if(is_numeric($vMode)){
			$this->verboseMode = $vMode;
			if($this->verboseMode == 2){
				//Create log File
				$logPath = $SERVER_ROOT;
				if(substr($SERVER_ROOT,-1) != '/' && substr($SERVER_ROOT,-1) != '\\') $logPath .= '/';
				$logPath .= "temp/logs/taxaloader_".date('Ymd').".log";
				$this->logFH = fopen($logPath, 'a');
				fwrite($this->logFH,"Start time: ".date('Y-m-d h:i:s A')."\n");
			}
		}
	}

	//Misc functions
	private function outputMsg($str, $indent = 0){
		if($this->verboseMode > 0 || substr($str,0,5) == 'ERROR'){
			echo '<li style="margin-left:'.(10*$indent).'px;'.(substr($str,0,5)=='ERROR'?'color:red':'').'">'.$str.'</li>';
			ob_flush();
			flush();
		}
		if($this->verboseMode == 2){
			if($this->logFH) fwrite($this->logFH,($indent?str_repeat("\t",$indent):'').strip_tags($str)."\n");
		}
	}

	private function cleanInArr(&$inArr){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->cleanInStr($v);
		}
	}

	private function cleanInStr($str){
		$newStr = TRIM($str);
		$newStr = preg_REPLACE('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function encodeArr(&$inArr){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->encodeString($v);
		}
	}

	private function encodeString($inStr){
		global $charset;
		$retStr = $inStr;
		//Get rid of curly (smart) quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_REPLACE($search, $replace, $inStr);
		//Get rid of UTF-8 curly smart quotes and dashes 
		$badwordchars=array("\xe2\x80\x98", // left single quote
							"\xe2\x80\x99", // right single quote
							"\xe2\x80\x9c", // left double quote
							"\xe2\x80\x9d", // right double quote
							"\xe2\x80\x94", // em dash
							"\xe2\x80\xa6" // elipses
		);
		$fixedwordchars=array("'", "'", '"', '"', '-', '...');
		$inStr = str_REPLACE($badwordchars, $fixedwordchars, $inStr);
		
		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				//$this->outputMsg($inStr.': '.mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true);
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
 		}
		return $retStr;
	}
}
?>