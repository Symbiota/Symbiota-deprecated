<?php
require_once($serverRoot.'/config/dbconnection.php');
require_once($serverRoot.'/classes/OccurrenceUtilities.php');

class ImageIPlantProcessor {

	private $iPlantDataUrl = 'http://bovary.iplantcollaborative.org/data_service/';
	private $iPlantImageUrl = 'http://bovary.iplantcollaborative.org/image_service/image/';
	
	private $conn;

	private $collProcessedArr = array();

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

	public function batchProcessImages(){
		//Start processing images for each day from the start date to the current date
		$collArr = $this->getCollArr();
		if($this->logMode == 1) echo '<ul>';
		foreach($collArr as $collid => $cArr){
			$this->processCollection($cArr);
		}
		$this->updateCollectionStats();
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1) echo '</ul>';
	}

	//iPlant functions
	private function processCollection($collid,$cArr){
		$status = false;
		$this->logOrEcho('Starting image processing: '.$collStr.' ('.date('Y-m-d h:i:s A').')');
		$collStr = $cArr['instcode'].($cArr['collcode']?'-'.$cArr['collcode']:'');
		
		$pmTerm = $cArr['pmterm'];
		if(!$cArr['pmterm']){
			$this->logOrEcho('COLLECTION SKIPPED: Pattern matching term is NULL');
			return false;
		}
		if(substr($cArr['pmterm'],0,1) != '/' || substr($cArr['pmterm'],-1) != '/'){
			$this->logOrEcho("COLLECTION SKIPPED: Regular Expression term illegal due to missing forward slashes: ".$cArr['pmterm']);
			return false;
		}
		if(!strpos($cArr['pmterm'],'(') || !strpos($cArr['pmterm'],')')){
			$this->logOrEcho("COLLECTION SKIPPED: Regular Expression term illegal due to missing capture term: ".$cArr['pmterm']);
			return false;
		}
		//Get start date
		$targetDate = $cArr['lastrundate'];
		if(!$targetDate) $targetDate = strtotime('2015-04-01');
		while($targetDate < strtotime('now')){
			$url = $this->iPlantDataUrl.'image?value=*/sernec/'.$cArr['instcode'].'/*&tag_query=upload_datetime:'.$targetDate.'*';
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
							if(preg_match($cArr['pmterm'],$str,$matchArr)){
								if(array_key_exists(1,$matchArr) && $matchArr[1]){
									$specPk = $matchArr[1];
									/*
									if(isset($cArr['pattreplace'])){ 				
										$specPk = preg_replace($cArr['pattreplace'],$cArr['replacestr'],$specPk);
									}
									*/
									if($occid = $this->getOccId($collid,$specPk,$fileName)){
										$id = $i['resource_uniq'];
										//if($this->checkImageExistance($id)) return false;
										$webUrl = $this->iPlantImageUrl.$id.'?resize=1250&format=jpeg';
										$tnUrl = $this->iPlantImageUrl.$id.'?thumbnail=200,200';
										$lgUrl = $this->iPlantImageUrl.$id.'?resize=4000&format=jpeg';
										
										$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$cArr['collname'],$fileName);
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
		if($status) $this->collProcessedArr[] = $collid;
		return $status;
	}
	
	private function checkImageExistance($id){
		//Check to see if image url already exists for that occid
		$imgExists = false;
		$sql = 'SELECT imgid FROM images WHERE (url LIKE "'.$this->iPlantImageUrl.$id.'%") ';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $imgExists = true;
		$rs->free();
		return $imgExists;
	}

	private function getOccId($collid,$specPk,$fileNameSearch){
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
			//Check to see if file was already added
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
	
	private function databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$ownerStr,$fileName){
		$status = true;
		if($occid){
			$this->logOrEcho("Preparing to load record into database",1);

			$sql = 'INSERT images(occid,url,thumbnailurl,originalurl,imagetype,owner,sourceIdentifier) '.
				'VALUES ('.$occId.',"'.$webUrl.'",'.($tnUrl?'"'.$tnUrl.'"':'NULL').','.($lgUrl?'"'.$lgUrl.'"':'NULL').
				',"specimen","'.$this->cleanInStr($ownerStr).'","'.$fileName.'")';
			if($this->conn->query($sql)){
				$this->logOrEcho("SUCCESS: Image record loaded into database",1);
			}
			else{
				$status = false;
				$this->logOrEcho("ERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql,1);
			}
		}
		else{
			$status = false;
			$this->logOrEcho("ERROR: Missing occid (omoccurrences PK), unable to load record ");
		}
		return $status;
	}

	private function updateCollectionStats(){
		if($this->collProcessedArr){
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
	
			$this->logOrEcho('<li style="margin-left:10px;">Updating statistics...</li>');
			foreach($this->collProcessedArr as $collid){
				if(!$occurUtil->updateCollectionStats($collid)){
					$errorArr = $occurUtil->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
	
			$this->logOrEcho('Populating global unique identifiers (GUIDs) for all records...');
			$uuidManager = new UuidFactory();
			$uuidManager->setSilent(1);
			$uuidManager->populateGuids();
		}
	}

	//Set and Get functions
	public function setCollArr($collid){
		$collArr = array();
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.managementtype, s.speckeypattern, s.source '.
			'FROM omcollections c INNER JOIN specprocessorprojects s ON c.collid = s.collid '.
			'WHERE (s.title = "IPlant Image Processing") ';
		if($collid) $sql .= 'AND (collid = '.$collid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$collArr[$r->collid]['instcode'] = $r->institutioncode;
			$collArr[$r->collid]['collcode'] = $r->collectioncode;
			$collArr[$r->collid]['collname'] = $r->collectionname;
			$collArr[$r->collid]['managementtype'] = $r->managementtype;
			$collArr[$r->collid]['speckeypattern'] = $r->speckeypattern;
			//$collArr[$r->collid]['pattreplace'] = $r->pattreplace;
			//$collArr[$r->collid]['replacestr'] = $r->replacestr;
			$collArr[$r->collid]['lastrundate'] = $r->source;
			//$collArr[$r->collid]['lastrundate'] = $r->lastrundate;
		}
		$rs->free();
		return $collArr;
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