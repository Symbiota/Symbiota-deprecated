<?php
require_once($serverRoot.'/config/dbconnection.php');
require_once($serverRoot.'/classes/OccurrenceUtilities.php');

class ImageProcessor {

	private $conn;

	private $collid = 0;
	private $sprid;
	private $collArr;

	private $logMode = 0;		//0 = silent, 1 = html, 2 = log file, 3 = both html & log
	private $logFH;

	function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
		if($this->conn === false) exit("ABORT: Image upload aborted: Unable to establish connection to database");
	}

	function __destruct(){
		//Close connection
		if(!($this->conn === false)) $this->conn->close();

		//Close log file
		if($this->logFH) fclose($this->logFH);
	}

	private function initProcessor($processorType){
		//Close log file
		if($this->logFH) fclose($this->logFH);
		if($this->logMode > 1){
			//Create log File
			$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/';
			if($processorType) $logPath .= $processorType.'/';
			if(file_exists($logPath)){
				$logFile = $logPath.$this->collid.'_'.$this->collArr['instcode'];
				if($this->collArr['collcode']) $logFile .= '-'.$this->collArr['collcode'];
				$logFile .= '_'.date('Y-m-d').".log";
				$this->logFH = fopen($logFile, 'a');
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$logPath."\n";
			}
		}
	}

	public function batchProcessIPlantImages(){
		//Start processing images for each day from the start date to the current date
		$status = false;
		if($this->logMode == 1) echo '<ul>';
		$processList = array();
		$sql = 'SELECT collid, speckeypattern, source FROM specprocessorprojects WHERE (title = "IPlant Image Processing") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->collid = $r->collid;
			$this->setLogMode(2);
			$status = $this->processIPlantImages();
			if($status){
				$processList[] = $this->collid;
			}
		}
		$rs->free();
		if($status) $this->cleanHouse($processList);
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1) echo '</ul>';
	}

	//iPlant functions
	public function processIPlantImages($pmTerm, $lastRunDate){
		set_time_limit(1000);
		if($this->collid){
			$this->initProcessor('iplant');
			$iPlantDataUrl = 'http://bovary.iplantcollaborative.org/data_service/';
			$iPlantImageUrl = 'http://bovary.iplantcollaborative.org/image_service/image/';
			$collStr = $this->collArr['instcode'].($this->collArr['collcode']?'-'.$this->collArr['collcode']:'');
			$this->logOrEcho('Starting image processing: '.$collStr.' ('.date('Y-m-d h:i:s A').')');
			
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
			if(!$lastRunDate) $lastRunDate = '2015-04-01';
			while(strtotime($lastRunDate) < strtotime('now')){
				$url = $iPlantDataUrl.'image?value=*/sernec/'.$this->collArr['instcode'].'/*&tag_query=upload_datetime:'.$lastRunDate.'*';
				$contents = @file_get_contents($url);
				//check if response is received from iPlant
				if(!empty($http_response_header)) {
					$result = $http_response_header;
					//check if response is 200
					if(strpos($result[0],'200') !== false) {
						$xml = '';
						try { 
							$xml = new SimpleXMLElement($contents);
						} 
						catch (Exception $e) { 
							$this->logOrEcho('ABORTED: bad content received from iPlant: '.$contents);
							return false; 
						} 
						if(count($xml->image)){
							$this->logOrEcho('Starting to process '.count($xml->image).' images uploaded on '.$lastRunDate,1);
							$cnt = 0;
							foreach($xml->image as $i){
								$fileName = $i['name'];
								if(preg_match($pmTerm,$fileName,$matchArr)){
									if(array_key_exists(1,$matchArr) && $matchArr[1]){
										$specPk = $matchArr[1];
										/*
										if(isset($cArr['pattreplace'])){ 				
											$specPk = preg_replace($cArr['pattreplace'],$cArr['replacestr'],$specPk);
										}
										*/
										$guid = $i['resource_uniq'];
										if($occid = $this->getOccid($specPk,$guid,$fileName)){
											$webUrl = $iPlantImageUrl.$guid.'?resize=1250&format=jpeg';
											$tnUrl = $iPlantImageUrl.$guid.'?thumbnail=200,200';
											$lgUrl = $iPlantImageUrl.$guid.'?resize=4000&format=jpeg';
											$archiveUrl = $iPlantImageUrl.$guid;
											
											$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,$this->collArr['collname'],$guid.'; filename: '.$fileName);
											//$this->logOrEcho("Image processed successfully (".date('Y-m-d h:i:s A').")!",2);
										}
									}
									else{
										$this->logOrEcho("NOTICE: File skipped due to being unable to extract specimen identifier (".$sourcePathFrag.$fileName.")",2);
									}
								}
								$cnt++;
							}
						}
						else{
							$this->logOrEcho('No images were loaded on this date: '.$lastRunDate,1);
							}
					}
					else{
						$this->logOrEcho("ERROR: bad response status code returned for $url (code: $result[0])",1);
					}
				}
				else{
					$this->logOrEcho("ERROR: failed to obtain response from iPlant (".$url.")",1);
					return false;
				}
				$this->updateLastRunDate($lastRunDate);
				$lastRunDate = date('Y-m-d', strtotime($lastRunDate. ' + 1 days'));
			}
			$this->cleanHouse(array($this->collid));
			$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		}
		return true;
	}

	//iDigBio Image ingestion processing functions
	public function processiDigBioOutput($pmTerm){
		global $serverRoot;
		$status = '';
		$this->initProcessor('idigbio');
		$fullPath = $serverRoot.(substr($serverRoot,-1) != '/'?'/':'').'temp/data/idigbio_'.time().'.csv';
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
								$occid = $this->getOccid($specPk,$data[$mediaGuidIndex]);
								if($occid){
									//Image hasn't been loaded, thus insert image urls into image table
									$tnUrl = 'http://media.idigbio.org/lookup/images/'.$data[$mediaMd5Index].'?size=thumbnail';
									$webUrl = 'http://media.idigbio.org/lookup/images/'.$data[$mediaMd5Index].'?size=webview';
									$lgUrl = 'http://media.idigbio.org/lookup/images/'.$data[$mediaMd5Index].'?size=fullsize';
									$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,'',$data[$mediaGuidIndex]);
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
				$status = "Can't open file.";
			}
		}
		return $status;
	}

	//Shared functions 
	private function getOccid($specPk,$sourceIdentifier,$fileName = ''){
		$occid = 0;
		if($this->collid){
			//Check to see if record with pk already exists
			$sql = 'SELECT occid FROM omoccurrences '.
				'WHERE (catalognumber IN("'.$specPk.'"'.(substr($specPk,0,1)=='0'?',"'.ltrim($specPk,'0 ').'"':'').')) '.
				'AND (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$occid = $row->occid;
			}
			$rs->free();
			if($occid){
				$occLink = '<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>';
				if($fileName){
					//Check to see if image has already been linked
					$fileBaseName = $fileName;
					$fileExt = '';
					$dotPos = strrpos($fileName,'.');
					if($dotPos){
						$fileBaseName = substr($fileName,0,$dotPos);
						$fileExt = strtolower(substr($fileName,$dotPos+1));
					}
					//Grab existing images for that occurrence
					$imgArr = array();
					$sqlTest = 'SELECT imgid, sourceidentifier FROM images WHERE (occid = '.$occid.') ';
					$rsTest = $this->conn->query($sqlTest);
					while($rTest = $rsTest->fetch_object()){
						$imgArr[$rTest->imgid] = $rTest->sourceidentifier;
					}
					$rsTest->free();
					//Process images to determine if new images should be added
					foreach($imgArr as $imgId => $sourceId){
						if($sourceId){
							if($p = strpos($sourceId,'; filename: ')){
								$source = substr($sourceId,$p+12);
								$pos = strrpos($source,'.');
								$ext = strtolower(substr($source,$pos+1));
								if($source == $fileName){
									//Image file already loaded, thus abort and don't reload 
									$occid = false;
									$this->logOrEcho('NOTICE: Image mapping skipped due to image already being mapped ('.$fileName.'; #'.$occLink.')',2); 
									break;
								}
								elseif($ext == 'cr2' || $ext == 'dng' || $ext == 'tiff' || $ext == 'tif' || $ext == 'nef'){
									//High res already mapped, thus abort and don't reload 
									$occid = false;
									//$this->logOrEcho('NOTICE: Image mapping skipped due to high-res image with same name already being mapped ('.$fileName.'; '.$occLink.')',2); 
									break;
								}
								elseif($ext == 'jpg' && ($fileExt == 'cr2' || $fileExt == 'dng' || $fileExt == 'tif' || $fileExt == 'tiff' || $fileExt == 'nef')){
									//$this->logOrEcho('NOTICE: Replacing exist map of low-res with this high-res version of image ('.$fileName.'; #'.$occLink.')',2); 
									//Replace low res source with high res source by deleteing current low res source 
									$this->conn->query('DELETE FROM image WHERE imgid = '.$imgId);
								}
							}
						}
					}
				}
				else{
					//Check to see if urls are already in system
					$sql1 = 'SELECT i.imgid '.
						'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
						'WHERE o.collid = '.$this->collid.' AND i.sourceIdentifier = "'.$sourceIdentifier.'"';
					//echo $sql1;
					$rs1 = $this->conn->query($sql1);
					if($rs1->num_rows){
						$this->logOrEcho('NOTICE: Image already mapped in system (#'.$occLink.')',2);
						$occid = false;
					}
					$rs1->free();
				}
				if($occid) $this->logOrEcho('Image linked to existing record ('.($fileName?$fileName.'; ':'').'#'.$occLink.') ',2);
			}
			else{
				//Records does not exist, create a new one to which image will be linked
				$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber,processingstatus,dateentered) '.
					'VALUES('.$this->collid.',"'.$specPk.'","unprocessed","'.date('Y-m-d H:i:s').'")';
				if($this->conn->query($sql2)){
					$occid = $this->conn->insert_id;
					$this->logOrEcho('Image linked to a new "unprocessed" specimen record (#<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>) ',2);
				}
				else{
					$this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,2);
				}
			}
		}
		return $occid;
	}
	
	private function databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,$ownerStr,$sourceIdentifier){
		$status = true;
		if($occid){
			//$this->logOrEcho("Preparing to load record into database",2);
			$sql = 'INSERT images(occid,url,thumbnailurl,originalurl,archiveurl,owner,sourceIdentifier) '.
				'VALUES ('.$occid.',"'.$webUrl.'",'.($tnUrl?'"'.$tnUrl.'"':'NULL').','.($lgUrl?'"'.$lgUrl.'"':'NULL').','.
				($archiveUrl?'"'.$archiveUrl.'"':'NULL').','.($ownerStr?'"'.$this->cleanInStr($ownerStr).'"':'NULL').','.
				($sourceIdentifier?'"'.$this->cleanInStr($sourceIdentifier).'"':'NULL').')';
			if($this->conn->query($sql)){
				//$this->logOrEcho('Image loaded into database (<a href="../individual/index.php?occid='.$occid.'" target="_blank">#'.$occid.($sourceIdentifier?'</a>: '.$sourceIdentifier:'').')',2);
			}
			else{
				$status = false;
				$this->logOrEcho("ERROR: Unable to load image record into database: ".$this->conn->error,2);
				//$this->logOrEcho($sql);
			}
		}
		else{
			$status = false;
			$this->logOrEcho("ERROR: Missing occid (omoccurrences PK), unable to load record ",2);
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
		
		$this->logOrEcho('Protecting sensitive species...',2);
		if(!$occurUtil->protectRareSpecies()){
			$errorArr = $occurUtil->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->logOrEcho($errorStr,1);
			}
		}

		if($collList){
			$this->logOrEcho('Updating collection statistics...',2);
			foreach($collList as $collid){
				if(!$occurUtil->updateCollectionStats($collid)){
					$errorArr = $occurUtil->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
		}

		$this->logOrEcho('Populating global unique identifiers (GUIDs) for all records...',2);
		$uuidManager = new UuidFactory();
		$uuidManager->setSilent(1);
		$uuidManager->populateGuids();
	}

	private function updateLastRunDate($date){
		if($this->spprid){
			$sql = 'UPDATE specprocessorprojects SET source = "'.$date.'" WHERE spprid = '.$this->spprid;
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR updating last run date: '.$this->conn->error);
			}
		}
	}

	//Set and Get functions
	private function setCollArr(){
		if($this->collid){
			$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype '.
				'FROM omcollections '.
				'WHERE (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr['instcode'] = $r->institutioncode;
				$this->collArr['collcode'] = $r->collectioncode;
				$this->collArr['collname'] = $r->collectionname;
				$this->collArr['managementtype'] = $r->managementtype;
			}
			$rs->free();
		}
	}

	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$this->setCollArr();
		}
	}
	
	public function setSpprid($spprid){
		if(is_numeric($spprid)){
			$this->spprid = $spprid;
		}
	}

	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}
	
	//Misc functions
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
		if($this->logMode > 1){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,strip_tags($str)."\n");
			}
		}
		if($this->logMode == 1 || $this->logMode == 3){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
			ob_flush();
			flush();
		}
	}
}
?>