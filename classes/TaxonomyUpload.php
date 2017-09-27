<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class TaxonomyUpload{
	
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
		if(($fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r')) !== FALSE){
			$headerArr = fgetcsv($fh);
			$uploadTaxaFieldArr = $this->getUploadTaxaFieldArr();
			$taxonUnitArr = $this->getTaxonUnitArr();
			$uploadTaxaIndexArr = array();		//Array of index values associated with uploadtaxa table; array(index => targetName)
			$taxonUnitIndexArr = array();		//Array of index values associated with taxonunits table;
			foreach($headerArr as $k => $sourceName){
				$sourceName = trim(strtolower($sourceName));
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
				$recordCnt = 1;
				asort($taxonUnitIndexArr);
				$childParentArr = array();		//array(taxon => array('p'=>parentStr,'r'=>rankid)
				//$this->conn->query('SET autocommit=0');
				//$this->conn->query('SET unique_checks=0');
				//$this->conn->query('SET foreign_key_checks=0');
				//$sqlBase = "INSERT INTO uploadtaxa(".implode(",",$uploadTaxaIndexArr).") ";
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
						$inputArr = array();
						foreach($uploadTaxaIndexArr as $recIndex => $targetField){
							$valIn = $this->cleanInStr($this->encodeString($recordArr[$recIndex]));
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
							if($valIn) $inputArr[$targetField] = $valIn;
						}
						if(array_key_exists('scinameinput', $inputArr)){
							$sciArr = TaxonomyUtilities::parseScientificName($inputArr['scinameinput'],$this->conn,(isset($inputArr['rankid'])?$inputArr['rankid']:0));
							foreach($sciArr as $sciKey => $sciValue){
								if(!array_key_exists($sciKey, $inputArr)) $inputArr[$sciKey] = $sciValue;
							}
							$sql1 = ''; $sql2 = '';
							unset($inputArr['identificationqualifier']);
							foreach($inputArr as $k => $v){
								$sql1 .= ','.$k;
								$inValue = $this->cleanInStr($v);
								$sql2 .= ','.($inValue?'"'.$inValue.'"':'NULL');
							}
							$sql = 'INSERT INTO uploadtaxa('.substr($sql1,1).') VALUES('.substr($sql2,1).')';
							//echo "<div>".$sql."</div>";
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
						unset($inputArr);
					}
					$recordCnt++;
				}
				//$this->conn->query('COMMIT');
				//$this->conn->query('SET autocommit=1');
				//$this->conn->query('SET unique_checks=1');
				//$this->conn->query('SET foreign_key_checks=1');
				
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
		else{
			echo 'ERROR thrown opening input file: '.$this->uploadTargetPath.$this->uploadFileName.'<br/>';
			if(!is_writable($this->uploadTargetPath)) echo '<b>Target upload path is not writable. File permissions need to be adjusted</b>';
			exit;
		}
	}

	public function loadItisFile(){
		$this->outputMsg('Starting Upload');
		//Initiate upload process
		$extraArr = array();
		$authArr = array();
		$this->conn->query('DELETE FROM uploadtaxa');
		$this->conn->query('OPTIMIZE TABLE uploadtaxa');
		if(($fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r')) !== FALSE){
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
		else{
			echo 'ERROR thrown opening input file: '.$this->uploadTargetPath.$this->uploadFileName.'<br/>';
			if(!is_writable($this->uploadTargetPath)) echo '<b>Target upload path is not writable. File permissions need to be adjusted</b>';
			exit;
		}
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
		
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceParentId = u2.sourceId '.
			'SET u.parentstr = u2.sciname '.
			'WHERE (u.parentstr IS NULL) AND (u.sourceParentId IS NOT NULL) AND (u2.sourceId IS NOT NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.acceptedstr = u2.sciname '.
			'WHERE (u.acceptedstr IS NULL) AND (u.sourceAcceptedId IS NOT NULL) AND (u2.sourceId IS NOT NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Delete taxa where sciname can't be inserted. These are taxa where sciname already exists
		$sql = 'DELETE FROM uploadtaxa WHERE (sciname IS NULL)';
		$this->conn->query($sql);

		//Link names already in theusaurus 
		$this->outputMsg('Linking names already in thesaurus... ');
		$sql = 'UPDATE uploadtaxa u INNER JOIN taxa t ON u.sciname = t.sciname SET u.tid = t.tid WHERE u.tid IS NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		//Update accepted name tid by matching on input sciname and then direct match to taxa table
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedstr = u2.scinameinput '.
			'SET u1.tidaccepted = u2.tid '.
			'WHERE u1.tidaccepted IS NULL AND u2.tid IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u INNER JOIN taxa t ON u.acceptedstr = t.sciname '.
				'SET u.tidaccepted = t.tid '.
				'WHERE u.tidaccepted IS NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		//Populate null family values
		$this->outputMsg('Populating null family values... ');
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.family = ts.family '.
			'WHERE ts.taxauthid = '.$this->taxAuthId.' AND (ut.rankid > 140) AND (t.rankid = 180) AND (ts.family IS NOT NULL) AND (ut.family IS NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceParentId = u2.sourceId '.
			'SET u1.family = u2.sciname '.
			'WHERE u2.sourceId IS NOT NULL AND u1.sourceParentId IS NOT NULL AND u2.rankid = 140 AND u1.family IS NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname '.
			'SET u1.family = u2.family '.
			'WHERE u1.family IS NULL AND u2.family IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceAcceptedId = u2.sourceId '.
			'SET u1.family = u2.family '.
			'WHERE u1.sourceAcceptedId IS NOT NULL AND  u2.sourceId IS NOT NULL AND u1.family IS NULL AND u2.family IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname '.
			'INNER JOIN uploadtaxa u3 ON u2.sourceParentId = u3.sourceId '.
			'SET u1.family = u3.sciname '.
			'WHERE u2.sourceParentId IS NOT NULL AND u3.sourceId IS NOT NULL '.
			'AND u1.family IS NULL AND u1.rankid > 140 AND u2.rankid = 180 AND u3.rankid = 140';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.sourceAcceptedId = u1.sourceid '.
			'SET u0.family = u1.family '.
			'WHERE u0.sourceParentId IS NOT NULL AND u1.sourceId IS NOT NULL '.
			'AND u0.family IS NULL AND u0.rankid > 140 AND u1.family IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.scinameinput = u1.acceptedstr '.
			'SET u0.family = u1.family '.
			'WHERE u0.family IS NULL AND u0.rankid > 140 AND u1.family IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET family = NULL WHERE family = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		$this->outputMsg('Loading vernaculars... ');
		//This is first pass for all taxa that have non-null tids just before they are removed
		$this->transferVernaculars();
		
		$sql = 'DELETE FROM uploadtaxa WHERE tid IS NOT NULL';
		//$this->conn->query($sql);

		$this->outputMsg('Set null author values... ');
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LENGTH(sciname)+1)) '.
			'WHERE (author IS NULL) AND (rankid <= 220)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(CONCAT_WS(" ",unitind3,unitname3)))) '.
			'WHERE (author IS NULL) AND rankid > 220';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET author = NULL WHERE author = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		$this->outputMsg('Populating and mapping parent taxon... ');
		$sql = 'UPDATE uploadtaxa '.
			'SET parentstr = CONCAT_WS(" ", unitname1, unitname2) '.
			'WHERE ((parentstr IS NULL) OR (parentstr LIKE "PENDING:%")) AND (rankid > 220)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = unitname1 '.
			'WHERE ((parentstr IS NULL) OR (parentstr LIKE "PENDING:%")) AND (rankid = 220)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = family '.
			'WHERE ((parentstr IS NULL) OR (parentstr LIKE "PENDING:%")) AND (rankid = 180)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = SUBSTRING(parentstr,9) '.
			'WHERE (parentstr LIKE "PENDING:%")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceAcceptedID = u2.sourceId '.
			'SET u1.sourceParentId = u2.sourceParentId, u1.parentStr = u2.parentStr '.
			'WHERE (u1.sourceParentId IS NULL) AND (u1.sourceAcceptedID IS NOT NULL) AND (u2.sourceParentId IS NOT NULL) AND (u1.rankid < 220) ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET parenttid = t.tid WHERE (parenttid IS NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Load into uploadtaxa parents of infrasp not yet in taxa table 
		$this->outputMsg('Add parents that are not yet in uploadtaxa table... ');
		$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput, SciName, family, RankId, UnitName1, UnitName2, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.family, 220 as r, ut.unitname1, ut.unitname2, ut.unitname1, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE (ut.parentstr <> "") AND (ut.parentstr IS NOT NULL) AND (ut.parenttid IS NULL) AND (ut.rankid > 220) AND (ut2.sciname IS NULL) ';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE (up.parenttid IS NULL)';
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

		//Set acceptance to 0 where sciname <> acceptedstr
		$sql = 'UPDATE uploadtaxa '.
			'SET acceptance = 0 '.
			'WHERE (acceptedstr IS NOT NULL) AND (sciname IS NOT NULL) AND (sciname <> acceptedstr)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET acceptance = 1 '.
			'WHERE (ISNULL(acceptedstr)) AND (ISNULL(TidAccepted))';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET acceptance = 1 '.
			'WHERE (ISNULL(sciname)) AND (sciname = acceptedstr)';
		$this->conn->query($sql);
        $sql = 'UPDATE uploadtaxa AS u LEFT JOIN taxonunits AS t ON u.RankName = t.rankname '.
            'SET u.RankId = t.rankid '.
            'WHERE (ISNULL(u.RankId)) AND (t.rankid IS NOT NULL)';
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
		$sql4 = 'UPDATE uploadtaxa SET ErrorStatus = "FAILED: Unable to parse input scientific name" WHERE sciname IS NULL';
		if(!$this->conn->query($sql4)){
			$this->outputMsg('ERROR tagging non-parsed names: '.$this->conn->error,1);
		}
		
		//Tag non-accepted taxa linked to non-existent taxon
		$sql5 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Non-accepted taxa linked to non-existent taxon" '.
			'WHERE (u1.acceptance = 0) AND (u1.tidAccepted IS NULL) AND (u2.sciname IS NULL)';
		if(!$this->conn->query($sql5)){
			$this->outputMsg('ERROR tagging non-accepted taxon linked to non-existent taxon: '.$this->conn->error,1);
		}
		
		//Tag non-accepted linked to other non-accepted taxa
		$sql6a = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Non-accepted linked to another non-accepted taxon" '.
			'WHERE (u1.acceptance = 0) AND (u2.acceptance = 0)';
		if(!$this->conn->query($sql6a)){
			$this->outputMsg('ERROR tagging non-accepted linked to non-accepted (#1): '.$this->conn->error,1);
		}
		$sql6b = 'UPDATE uploadtaxa u INNER JOIN taxstatus ts ON u.tidaccepted = ts.tid '.
			'SET u.ErrorStatus = "FAILED: Non-accepted linked to another non-accepted taxon already within database" '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (u.acceptance = 0) AND (ts.tid <> ts.tidaccepted)';
		if(!$this->conn->query($sql6b)){
			$this->outputMsg('ERROR tagging non-accepted linked to non-accepted (#2): '.$this->conn->error,1);
		}
		
		//Tag taxa with non-existent parents (parent not being added and does not exist within database
		$sql6 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Taxa with non-existent parent taxon" '.
			'WHERE (u1.RankId > 10) AND (u1.tid IS NULL) AND (u1.parentTid IS NULL) AND (u2.sciname IS NULL) ';
		if(!$this->conn->query($sql6)){
			$this->outputMsg('ERROR tagging taxa with non-existent parent taxon: '.$this->conn->error,1);
		}
		
		//Tag taxa with a FAILED parent
		$loopCnt = 0;
		do{
			$sql8 = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
				'SET u1.ErrorStatus = "FAILED: Taxa linked to a FAILED parent" '.
				'WHERE (u2.ErrorStatus LIKE "FAILED%") AND ((u1.ErrorStatus IS NULL) OR (u1.ErrorStatus NOT LIKE "FAILED%"))';
			if(!$this->conn->query($sql8)){
				$this->outputMsg('ERROR tagging taxa with FAILED parents: '.$this->conn->error,1);
				break;
			}
			$loopCnt++;
			if($loopCnt > 20){
				$this->outputMsg('ERROR looping: too many parent loops',1);
				break;
			}
		}while($this->conn->affected_rows);

		//Tag non-accepted taxa linked to FAILED taxon
		$sql9 = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Non-accepted taxa linked to a FAILED name" '.
			'WHERE (u1.acceptance = 0) AND (u1.ErrorStatus NOT LIKE "FAILED%") AND (u2.ErrorStatus LIKE "FAILED%")';
		if(!$this->conn->query($sql9)){
			$this->outputMsg('ERROR tagging non-accepeted linked to FAILED name: '.$this->conn->error,1);
		}

		//Get bad counts
		$sql = 'SELECT errorstatus, count(*) as cnt FROM uploadtaxa WHERE ErrorStatus LIKE "FAILED%" GROUP BY ErrorStatus';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->statArr['bad'][substr($r->errorstatus,7)] = $r->cnt;
		}
		$rs->free();

		return $retArr;
	}

	public function transferUpload(){
		$this->outputMsg('Starting data transfer...');
		//Prime table with kingdoms that are not yet in table
		$sql = 'INSERT INTO taxa(SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes) '.
			'SELECT DISTINCT SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes '.
			'FROM uploadtaxa '.
			'WHERE (TID IS NULL) AND (rankid = 10)';
		if($this->conn->query($sql)){
			$sql = 'INSERT INTO taxstatus(tid, tidaccepted, taxauthid, parenttid) '.
				'SELECT DISTINCT t.tid, t.tid, '.$this->taxAuthId.', t.tid '.
				'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (t.rankid = 10) AND (ts.tid IS NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
		}
		else{
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		
		//Loop through and transfer taxa to taxa table
		$loopCnt = 0;
		do{
			$this->outputMsg('Starting loop '.$loopCnt);
			$this->outputMsg('Transferring taxa to taxon table... ',1);
			$sql = 'INSERT IGNORE INTO taxa(SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes) '.
				'SELECT DISTINCT SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes '.
				'FROM uploadtaxa '.
				'WHERE (tid IS NULL) AND (parenttid IS NOT NULL) AND (rankid IS NOT NULL) AND (ErrorStatus IS NULL) '.
				'ORDER BY RankId ASC ';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR loading taxa: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.sciname = t.sciname '.
				'SET ut.tid = t.tid WHERE (ut.tid IS NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR populating TIDs: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa ut1 INNER JOIN uploadtaxa ut2 ON ut1.sourceacceptedid = ut2.sourceid '.
				'INNER JOIN taxa t ON ut2.sciname = t.sciname '.
				'SET ut1.tidaccepted = t.tid '.
				'WHERE (ut1.acceptance = 0) AND (ut1.tidaccepted IS NULL) AND (ut1.sourceacceptedid IS NOT NULL) AND (ut2.sourceid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.acceptedstr = t.sciname '.
				'SET ut.tidaccepted = t.tid '.
				'WHERE (ut.acceptance = 0) AND (ut.tidaccepted IS NULL) AND (ut.acceptedstr IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa SET tidaccepted = tid '.
				'WHERE (acceptance = 1) AND (tidaccepted IS NULL) AND (tid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			$this->outputMsg('Create parent and accepted links... ',1);
			$sql = 'INSERT IGNORE INTO taxstatus(TID, TidAccepted, taxauthid, ParentTid, Family, UnacceptabilityReason) '.
				'SELECT DISTINCT TID, TidAccepted, '.$this->taxAuthId.', ParentTid, Family, UnacceptabilityReason '.
				'FROM uploadtaxa '.
				'WHERE (tid IS NOT NULL) AND (TidAccepted IS NOT NULL) AND (parenttid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR creating taxstatus links: '.$this->conn->error,1);
			}

			$this->outputMsg('Transferring vernaculars for new taxa... ',1);
			//Covers taxa with newly assigned tids just before they are removed
			$this->transferVernaculars(1);
			
			$this->outputMsg('Preparing for next round... ',1);
			$sql = 'DELETE FROM uploadtaxa WHERE (tid IS NOT NULL) AND (tidaccepted IS NOT NULL) AND (parenttid IS NOT NULL)';
			$this->conn->query($sql);
			if(!$this->conn->affected_rows) break;

			//Update parentTids
			$sql = 'UPDATE uploadtaxa ut1 INNER JOIN uploadtaxa ut2 ON ut1.sourceparentid = ut2.sourceid '.
				'INNER JOIN taxa AS t ON ut2.sciname = t.sciname '.
				'SET ut1.parenttid = t.tid '.
				'WHERE (ut1.parenttid IS NULL) AND (ut1.sourceparentid IS NOT NULL) AND (ut2.sourceid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR populating parent TIDs based on sourceIDs: '.$this->conn->error,1);
			}
			
			$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
				'SET up.parenttid = t.tid '.
				'WHERE (up.parenttid IS NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR populating parent TIDs: '.$this->conn->error,1);
			}
			$loopCnt++;
		}while($loopCnt < 30);

		$this->outputMsg('House cleaning... ');
		TaxonomyUtilities::buildHierarchyEnumTree($this->conn, $this->taxAuthId);
		
		//$this->setKingdom();

		//Update occurrences with new tids
		$sql1 = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE (ISNULL(o.TidInterpreted))';
		$this->conn->query($sql1);
		
		//Update occurrence images with new tids
		$sql2 = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'SET i.tid = o.TidInterpreted '.
			'WHERE (ISNULL(i.tid)) AND (o.TidInterpreted IS NOT NULL)';
		$this->conn->query($sql2);
		
		//Update geo lookup table 
		$sql3 = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '. 
			'SELECT DISTINCT tidinterpreted, round(decimallatitude,3), round(decimallongitude,3) '. 
			'FROM omoccurrences '.
			'WHERE (tidinterpreted IS NOT NULL) AND (ISNULL(cultivationStatus) OR cultivationStatus <> 1) AND (decimallatitude IS NOT NULL) AND (decimallongitude IS NOT NULL)';
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

	private function setKingdom(){
		$status = true;
		//Seed taxaenumtree table
		$sql = 'UPDATE taxa t INNER JOIN taxaenumtree AS te ON t.TID = te.tid '.
			'INNER JOIN taxa t2 ON te.parenttid = t2.TID '.
			'INNER JOIN taxonunits tu ON t2.SciName = tu.kingdomName '.
			'SET t.kingdomName = tu.kingdomName '.
			'WHERE (te.taxauthid = '.$this->taxAuthId.') AND (t2.RankId = 10) AND (t.KingdomID IS NULL) AND (tu.rankid = 10) ';
		//echo $sql;
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
			'securityStatus','source','notes','vernacular','vernlang','sourceId','sourceAcceptedId','sourceParentId','errorStatus');
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
		if(($fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r')) !== FALSE){
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
		}
		else{
			echo 'ERROR thrown opening input file: '.$this->uploadTargetPath.$this->uploadFileName.'<br/>';
			if(!is_writable($this->uploadTargetPath)) echo '<b>Target upload path is not writable. File permissions need to be adjusted</b>';
			exit;
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

	public function getKindomNames(){
		$retArr = array();
		$defaultTid = 0;
		$defaultCnt = 0;
		$sql = 'SELECT t.tid, t.sciname, count(e.tid) as cnt '.
			'FROM taxa t LEFT JOIN taxaenumtree e ON t.tid = e.parenttid '.
			'WHERE t.rankid = 10 '.
			'GROUP BY t.tid, t.sciname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->tid] = $r->sciname;
				if($r->cnt > $defaultCnt){
					$defaultCnt = $r->cnt;
					$defaultTid = $r->tid;
				}
			}
			$rs->free();
			$retArr['default'] = $defaultTid;
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
		//Get rid of Windows curly (smart) quotes
		$search = array(chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151));
		$replace = array("'","'",'"','"','*','-','-');
		$inStr = str_replace($search, $replace, $inStr);
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