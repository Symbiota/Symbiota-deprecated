<?php
require_once($serverRoot.'/config/dbconnection.php');
require_once($serverRoot.'/classes/OccurrenceUtilities.php');

class ImageProcessor {

	private $conn;

	private $logMode = 0;			//0 = silent, 1 = html, 2 = log file
	private $logFH;

	function __construct(){
	}

	function __destruct(){
		//Close connection
		if(!($this->conn === false)) $this->conn->close();

		//Close log file
		if($this->logFH) fclose($this->logFH);
	}

	public function initProcessor(){
		if($this->logMode == 2){
			//Create log File
			$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/iplant';
			if(file_exists($logPath)){
				$logFile = $logPath.'general_'.date('Ymd').".log";
				$this->logFH = fopen($logFile, 'a');
				$this->logOrEcho("\nDateTime: ".date('Y-m-d h:i:s A'));
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$this->logPath."\n";
			}
		}
		//Set connection
		$this->conn = MySQLiConnectionFactory::getCon('write');
		if(!$this->conn){
			$this->logOrEcho("Image processor aborted: Unable to establish connection to ".$collName." database");
			exit("ABORT: Image upload aborted: Unable to establish connection to ".$collName." database");
		}
	}

	public function batchProcessIPlantImages(){
		//Start processing images for each day from the start date to the current date
		if($this->logMode == 1) echo '<ul>';
		$processList = array();
		$sql = 'SELECT collid, speckeypattern, source FROM specprocessorprojects WHERE (title = "IPlant Image Processing") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$status = $this->processIPlantImages($collid);
			if($status) $processList[] = $collid;
		}
		$rs->free();
		
		$this->cleanHouse($processList);
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");

		if($this->logMode == 1) echo '</ul>';
	}

	//iPlant functions
	public function processIPlantImages($collid){
		$status = false;
		$iPlantDataUrl = 'http://bovary.iplantcollaborative.org/data_service/';
		$iPlantImageUrl = 'http://bovary.iplantcollaborative.org/image_service/image/';
		
		$cArr = $this->getCollArr($collid);
		$pArr = $this->getProjArr($collid);
		$this->logOrEcho('Starting image processing: '.$collStr.' ('.date('Y-m-d h:i:s A').')');
		$collStr = $cArr['instcode'].($cArr['collcode']?'-'.$cArr['collcode']:'');
		
		$pmTerm = $pArr['speckeypattern'];
		if(!$pmTerm){
			$this->logOrEcho('COLLECTION SKIPPED: Pattern matching term is NULL');
			return false;
		}
		if(substr($pmTerm,0,1) != '/' || substr($pmTerm,-1) != '/'){
			$this->logOrEcho("COLLECTION SKIPPED: Regular Expression term illegal due to missing forward slashes: ".$pmTerm);
			return false;
		}
		if(!strpos($pmTerm,'(') || !strpos($pmTerm,')')){
			$this->logOrEcho("COLLECTION SKIPPED: Regular Expression term illegal due to missing capture term: ".$pmTerm);
			return false;
		}
		//Get start date
		$targetDate = $pArr['lastrundate'];
		if(!$targetDate) $targetDate = strtotime('2015-04-01');
		while($targetDate < strtotime('now')){
			$url = $iPlantDataUrl.'image?value=*/sernec/'.$cArr['instcode'].'/*&tag_query=upload_datetime:'.$targetDate.'*';
			$contents = @file_get_contents($url);
			//check if response is received from iPlant
			if(!empty($http_response_header)) {
				$result = $http_response_header;
				//check if response is 200
				if(strpos($result[0],'200') !== false) {
					$xml = new SimpleXMLElement($contents);
					if(count($xml->image)){
						$this->logOrEcho('Processing '.count($xml->image).' image loaded '.$targetDate.' ('.date('Y-m-d h:i:s A').')!',1);
						foreach($xml->image as $i){
							$fileName = $i['name'];
							if(preg_match($pmTerm,$str,$matchArr)){
								if(array_key_exists(1,$matchArr) && $matchArr[1]){
									$specPk = $matchArr[1];
									/*
									if(isset($cArr['pattreplace'])){ 				
										$specPk = preg_replace($cArr['pattreplace'],$cArr['replacestr'],$specPk);
									}
									*/
									$guid = $i['resource_uniq'];
									if($occid = $this->getOccId($collid,$specPk,$guid,$fileName)){
										$webUrl = $iPlantImageUrl.$guid.'?resize=1250&format=jpeg';
										$tnUrl = $iPlantImageUrl.$guid.'?thumbnail=200,200';
										$lgUrl = $iPlantImageUrl.$guid.'?resize=4000&format=jpeg';
										$archiveUrl = $iPlantImageUrl.$guid;
										
										$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,$cArr['collname'],$guid,$fileName);
										$this->logOrEcho("Image processed successfully (".date('Y-m-d h:i:s A').")!",1);
										$status = true;
									}
								}
								else{
									$this->logOrEcho("File skipped (".$sourcePathFrag.$fileName."), unable to extract specimen identifier",2);
								}
							}
						}
						$retStr = $xml->resource->tag['value'];
					}
					else{
						$this->logOrEcho('No images were loaded on this date: '.$targetDate,1);
					}
				}
				else{
					$this->logOrEcho("ERROR: bad response status code returned for $url (code: $result[0])",1);
				}
			}
			else{
				$this->logOrEcho("ERROR: failed to obtain response from iPlant (".$url.")",1);
			}
			$targetDate = strtotime($targetDate . ' + 1 day');
		}
		return $status;
	}
	
	//iDigBio Image ingestion processing functions
	public function processiDigBioOutput($pArr){
		global $serverRoot;
		$statusStr = '';
		$collid = $pArr['collid'];
		$fullPath = $serverRoot.(substr($serverRoot,-1) != '/'?'/':'').'temp/logs/idigbio_'.time().'.csv';
		$pmTerm = $pArr['speckeypattern'];
		if(move_uploaded_file($_FILES['idigbiofile']['tmp_name'],$fullPath)){
			if($fh = fopen($fullPath,'rb')){
				$headerArr = fgetcsv($fh,0,',');
				$mediaGuidIndex = array_search('MediaGUID',$headerArr);
				$mediaMd5Index = array_search('MediaMD5',$headerArr);
				if(is_numeric($mediaGuidIndex) && is_numeric($mediaMd5Index)){
					while(($data = fgetcsv($fh,1000,",")) !== FALSE){
						if(preg_match($pmTerm,$data[$mediaGuidIndex],$matchArr)){
							if(array_key_exists(1,$matchArr) && $matchArr[1]){
								$specPk = $matchArr[1];
								$occid = $this->getOccId($collid,$specPk,$data[$mediaGuidIndex]);
								if($occid){
									//Image hasn't been loaded, thus insert image urls into image table
									$tnUrl = 'http://media.idigbio.org/lookup/images/'.$data[$mediaMd5Index].'?size=thumbnail';
									$webUrl = 'http://media.idigbio.org/lookup/images/'.$data[$mediaMd5Index].'?size=webview';
									$lgUrl = 'http://media.idigbio.org/lookup/images/'.$data[$mediaMd5Index].'?size=fullsize';
									$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,'',$data[$mediaGuidIndex],'iDigBio Image Ingestion Tool');
								}
								else{
									echo 'ERROR bad occid: '.$occid;
								}
							}
							else{
								//Output to error log file
								echo 'ERROR: failed to extract match term';
								print_r($matchArr);
							}
						}
						else{
							//Output to error log file
							echo 'ERROR: unable to extract catalogNumber using pattern matching terms (subject: '.$data[$mediaGuidIndex].', pmTerm: '.$pmTerm.')';
						}
					}
				}
				else{
					//Output to error log file
					echo 'Bad input fields: '.$mediaGuidIndex.', '.$mediaMd5Index;
				}
				fclose($fh);
			}
			else{
				$statusStr = "Can't open file.";
			}
		}
		
		return $statusStr;
	}

	//Shared functions 
	private function getOccId($collid,$specPk,$sourceIdentifier,$fileName = ''){
		$occid = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE (catalognumber IN("'.$specPk.'"'.(substr($specPk,0,1)=='0'?',"'.ltrim($specPk,'0 ').'"':'').')) '.
			'AND (collid = '.$collid.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occid = $row->occid;
		}
		$rs->free();
		if($occid){
			if($fileName){
				//Check to see if image has already been linked
				$fileBaseName = $fileName;
				$fileExt = '';
				$dotPos = strrpos($fileName,'.');
				if($dotPos){
					$fileBaseName = substr($fileName,0,$dotPos);
					$fileExt = strtolower(substr($fileName,$dotPos));
					echo $fileBaseName.'; '.$fielExt; exit;
				}
				$sqlTest = 'SELECT imgid, source FROM images WHERE (occid = '.$occid.') AND (source LIKE "'.$fileBaseName.'%") ';
				$rsTest = $this->conn->query($sqlTest);
				while($rTest = $rsTest->fetch_object()){
					$pos = strrpos($rTest->source,'.');
					$ext = strtolower(substr($rTest->source,$pos));
					if($rTest->source = $fileName){
						//Image file already loaded, thus abort and don't reload 
						$occid = 0;
					}
					elseif($ext == 'cr2' || $ext == 'dng' || $ext == 'tiff' || $ext == 'tif'){
						//High res already mapped, thus abort and don't reload 
						$occid = 0;
					}
					elseif($ext == 'jpg' && ($fileExt == 'cr2' || $fileExt == 'dng' || $fileExt == 'tif' || $fileExt == 'tiff')){
						//Replace low res source with high res source by deleteing current low res source 
						$this->conn->query('DELETE FROM image WHERE imgid = '.$rTest->imgId);
					}
				}
				$rsTest->free();
			}
			else{
				//Check to see if urls are already in system
				$sql1 = 'SELECT i.imgid '.
					'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'WHERE o.collid = '.$collid.' AND i.sourceIdentifier = "'.$sourceIdentifier.'"';
				//echo $sql1;
				$rs1 = $this->conn->query($sql1);
				if($rs1->num_rows){
					$this->logOrEcho('NOTICE: Image already mapped in system');
				}
				$rs1->free();
			}
		}
		else{
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber,processingstatus,dateentered) '.
				'VALUES('.$collid.',"'.$specPk.'","unprocessed","'.date('Y-m-d H:i:s').'")';
			if($this->conn->query($sql2)){
				$occid = $this->conn->insert_id;
				$this->logOrEcho("Specimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occid.") ",1);
			}
			else{
				$this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,1);
			}
		}
		if(!$occid){
			$this->logOrEcho("ERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") ",1);
		}
		return $occid;
	}
	
	private function databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,$ownerStr,$sourceIdentifier,$fileName = ''){
		$status = true;
		if($occid){
			$this->logOrEcho("Preparing to load record into database",1);

			$sql = 'INSERT images(occid,url,thumbnailurl,originalurl,archiveurl,owner,sourceIdentifier,source) '.
				'VALUES ('.$occId.',"'.$webUrl.'",'.($tnUrl?'"'.$tnUrl.'"':'NULL').','.($lgUrl?'"'.$lgUrl.'"':'NULL').','.
				($ownerStr?'"'.$this->cleanInStr($ownerStr).'"':'NULL').','.
				($sourceIdentifier?'"'.$this->cleanInStr($sourceIdentifier).'"':'NULL').','.
				($fileName?'"'.$this->cleanInStr($fileName).'"':'NULL').')';
			if($this->conn->query($sql)){
				$this->logOrEcho('SUCCESS: Image (#'.$occid.($sourceIdentifier?': '.$sourceIdentifier:'').') loaded into database',2);
			}
			else{
				$status = false;
				$this->logOrEcho("ERROR: Unable to load image record into database: ".$this->conn->error,2);
			}
		}
		else{
			$status = false;
			$this->logOrEcho("ERROR: Missing occid (omoccurrences PK), unable to load record ",1);
		}
		return $status;
	}

	private function cleanHouse($collList){
		$this->logOrEcho('Updating collection statistics...');
		$occurUtil = new OccurrenceUtilities();

		$this->logOrEcho('General cleaning...');
		if(!$occurUtil->generalOccurrenceCleaning()){
			$errorArr = $occurUtil->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->logOrEcho($errorStr,1);
			}
		}
		
		$this->logOrEcho('Protecting sensitive species...');
		if(!$occurUtil->protectRareSpecies()){
			$errorArr = $occurUtil->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->logOrEcho($errorStr,1);
			}
		}

		if($collList){
			$this->logOrEcho('<li style="margin-left:10px;">Updating collection statistics...</li>');
			foreach($collList as $collid){
				if(!$occurUtil->updateCollectionStats($collid)){
					$errorArr = $occurUtil->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
		}

		$this->logOrEcho('Populating global unique identifiers (GUIDs) for all records...');
		$uuidManager = new UuidFactory();
		$uuidManager->setSilent(1);
		$uuidManager->populateGuids();
	}

	//Set and Get functions
	private function getCollArr($collid){
		$collArr = array();
		if($collid){
			$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype '.
				'FROM omcollections '.
				'WHERE (collid = '.$collid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr['instcode'] = $r->institutioncode;
				$collArr['collcode'] = $r->collectioncode;
				$collArr['collname'] = $r->collectionname;
				$collArr['managementtype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $collArr;
	}
	
	private function getProjArr($collid){
		$projArr = array();
		if($collid){
			$sql = 'SELECT speckeypattern, source FROM specprocessorprojects WHERE (collid = '.$collid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$projArr['speckeypattern'] = $r->speckeypattern;
				//$projArr['pattreplace'] = $r->pattreplace;
				//$projArr['replacestr'] = $r->replacestr;
				$projArr['lastrundate'] = $r->source;
				//$projArr['lastrundate'] = $r->lastrundate;
			}
			$rs->free();
		}
		return $projArr;
	}
	
	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}

	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}

	private function logOrEcho($str,$indent = 0){
		if($this->logMode == 2){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,$str."\n");
			}
		}
		elseif($this->logMode == 1){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
			ob_flush();
			flush();
		}
	}
}
?>