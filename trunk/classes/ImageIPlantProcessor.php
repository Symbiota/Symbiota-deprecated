<?php
// Used by /trunk/collections/specprocessor/standalone_scripts/ImageIPlantHandler.php
require_once($serverRoot.'/classes/OccurrenceUtilities.php');

if(isset($serverRoot)){
	//Use Symbiota connection factory
	if(file_exists($serverRoot.'/config/dbconnection.php')){ 
		include_once($serverRoot.'/config/dbconnection.php');
	}
}

class ImageIPlantProcessor {

	private $iPlantDataUrl = 'http://bovary.iplantcollaborative.org/data_service/';
	private $iPlantImageUrl = 'http://bovary.iplantcollaborative.org/image_service/image/';
	
	private $conn;

	private $collArr = array();
	private $activeCollid = null;
	private $collProcessedArr = array();

	private $createNewRec = true;

	private $logMode = 0;			//0 = silent, 1 = html, 2 = log file
	private $logFH;
	private $logPath;

	function __construct(){
		//Set connection
		if(class_exists('ImageBatchConnectionFactory')){
			$this->conn = ImageBatchConnectionFactory::getCon('write');
		}
		elseif(class_exists('MySQLiConnectionFactory')){
			//Try getting connection through portals central connection factory
			$this->conn = MySQLiConnectionFactory::getCon('write');
		}
		if(!$this->conn){
			$this->logOrEcho("Image upload aborted: Unable to establish connection to ".$collName." database");
			exit("ABORT: Image upload aborted: Unable to establish connection to ".$collName." database");
		}
	}

	function __destruct(){
		//Close connection
		if(!($this->conn === false)) $this->conn->close();

		//Close log file
		if($this->logFH) fclose($this->logFH);
	}

	public function initProcessor($logTitle = ''){
		if($this->logPath && $this->logMode == 2){
			//Create log File
			if(!file_exists($this->logPath)){
				if(!mkdir($this->logPath,0,true)){
					echo("Warning: unable to create log file: ".$this->logPath);
				}
			}
			if(file_exists($this->logPath)){
				$titleStr = str_replace(' ','_',$logTitle);
				if(strlen($titleStr) > 50) $titleStr = substr($titleStr,0,50);
				$logFile = $this->logPath.$titleStr."_".date('Ymd').".log";
				$this->logFH = fopen($logFile, 'a');
				$this->logOrEcho("\nDateTime: ".date('Y-m-d h:i:s A'));
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$this->logPath."\n";
			}
		}
	}

	public function batchProcessImages(){
		//Start processing images for each day from the start date to the current date
		if($this->logMode == 1){
			echo '<ul>';
		}
		foreach($this->collArr as $collid => $cArr){
			$this->activeCollid = $collid;
			$collStr = $cArr['instcode'].($cArr['collcode']?'-'.$cArr['collcode']:'');
			$this->logOrEcho('Starting image processing: '.$collStr.' ('.date('Y-m-d h:i:s A').')');
			//Get start date
			$targetDate = strtotime('2015-04-01');
			$sql = 'SELECT max(i.initialtimestamp) as maxdate '.
				'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'WHERE i.url LIKE "'.$iPlantImageUrl.'%" AND o.collid = '.$collid;
			$rs = $this->conn->query();
			if($r = $rs->fetch_object()){
				$dateStr = substr($r->maxdate,0,10); 
			}
			$rs->free();
			while($targetDate < strtotime('now')){
				$this->processImages($instCode, date('Y-m-d', $targetDate));
				$targetDate = strtotime($targetDate . ' + 1 day');
			}

			$this->logOrEcho('Done uploading '.$sourcePathFrag.' ('.date('Y-m-d h:i:s A').')');
		}
		if($this->collProcessedArr){
			//Update Statistics
			$this->updateCollectionStats();
		}
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1){
			echo '</ul>';
		}
	}

	//iPlant functions
	private function processImages($instCode, $dateStr){
		$status = array();
		if($instCode && $dateStr){
			$url = $this->iPlantDataUrl.'image?value=*/sernec/'.$instCode.'/*&tag_query=upload_datetime:'.$dateStr.'*';
			$contents = @file_get_contents($url);
			//check if response is received from iPlant
			if(!empty($http_response_header)) {
				$result = $http_response_header;
				//check if response is 200
				if(strpos($result[0],'200') !== false) {
					$xml = new SimpleXMLElement($contents);
					if(count($xml->image)){
						$this->logOrEcho('Processing '.count($xml->image).' image loaded '.$dateStr.' ('.date('Y-m-d h:i:s A').')!',1);
						foreach($xml->image as $i){
							$fileName = $i['name'];
							if($specPk = $this->getPrimaryKey($fileName)){
								$id = $i['resource_uniq'];
								//if($this->checkImageExistance($id)) return false;
								if($occid = $this->getOccId($specPk)){
									$webUrl = $this->iPlantImageUrl.$id.'?resize=1250&format=jpeg';
									$tnUrl = $this->iPlantImageUrl.$id.'?thumbnail=200,200';
									$lgUrl = $this->iPlantImageUrl.$id.'?resize=4000&format=jpeg';
									
									$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl);
									$this->logOrEcho("Image processed successfully (".date('Y-m-d h:i:s A').")!",2);
								}
							}
							else{
								$this->logOrEcho("File skipped (".$sourcePathFrag.$fileName."), unable to extract specimen identifier",2);
							}
						}
						$retStr = $xml->resource->tag['value'];
					}
					else{
						$this->logOrEcho('No images were loaded '.$dateStr,1);
					}
				}
				else{
					$this->logOrEcho("ERROR: bad response status code returned for $url (code: $result[0])",1);
				}
			}
			else{
				$this->logOrEcho("ERROR: failed to obtain response from iPlant (".$url.")",1);
			}
		}
		return $status;
	}
	
	/**
	 * Extract a primary key (catalog number) from a string (e.g file name, catalogNumber field), 
	 * applying patternMatchingTerm, and, if they apply, patternReplacingTerm, and 
	 * replacement.  If patternMatchingTerm contains a backreference, 
	 * and there is a match, the return value is the backreference.  If 
	 * patternReplacingTerm and replacement are modified, they are applied 
	 * before the result is returned. 
	 * 
	 * @param str  String from which to extract the catalogNumber
	 * @return an empty string if there is no match of patternMatchingTerm on
	 *        str, otherwise the match as described above. 
	 */ 
	private function getPrimaryKey($str){
		$specPk = '';
		if(isset($this->collArr[$this->activeCollid]['pmterm'])){
			$pmTerm = $this->collArr[$this->activeCollid]['pmterm'];
			if(substr($pmTerm,0,1) != '/' || substr($pmTerm,-1) != '/'){
				$this->logOrEcho("PROCESS ABORTED: Regular Expression term illegal due to missing forward slashes: ".$pmTerm);
				exit;
			}
			if(!strpos($pmTerm,'(') || !strpos($pmTerm,')')){
				$this->logOrEcho("PROCESS ABORTED: Regular Expression term illegal due to missing capture term: ".$pmTerm);
				exit;
			}
			if(preg_match($pmTerm,$str,$matchArr)){
				if(array_key_exists(1,$matchArr) && $matchArr[1]){
					$specPk = $matchArr[1];
				}
				if (isset($this->collArr[$this->activeCollid]['prpatt'])) { 				
					$specPk = preg_replace($this->collArr[$this->activeCollid]['prpatt'],$this->collArr[$this->activeCollid]['prrepl'],$specPk);
				}
			}
		}
		return $specPk;
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

	private function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE (catalognumber IN("'.$specPk.'"'.(substr($specPk,0,1)=='0'?',"'.ltrim($specPk,'0 ').'"':'').')) '.
			'AND (collid = '.$this->activeCollid.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->free();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber,processingstatus,dateentered) '.
				'VALUES('.$this->activeCollid.',"'.$specPk.'","unprocessed","'.date('Y-m-d H:i:s').'")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
				$this->logOrEcho("Specimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.") ",1);
			}
			else{
				$this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,1);
			}
		}
		if(!$occId){
			$this->logOrEcho("ERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") ",1);
		}
		return $occId;
	}
	
	private function databaseImage($occid,$webUrl,$tnUrl,$lgUrl){
		$status = true;
		if($occid){
			$this->logOrEcho("Preparing to load record into database",1);

			$sql = 'INSERT images(occid,url,thumbnailurl,originalurl,imagetype,owner) '.
				'VALUES ('.$occId.',"'.$webUrl.'",'.($tnUrl?'"'.$tnUrl.'"':'NULL').','.($lgUrl?'"'.$lgUrl.'"':'NULL').
				',"specimen","'.$this->collArr[$this->activeCollid]['collname'].'")';
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
		$this->logOrEcho('Updating collection statistics...');

		$occurUtil = new OccurrenceUtilities();
		//$occurUtil->generalOccurrenceCleaning();
		
		foreach($this->collProcessedArr as $collid){
			$occurUtil->updateCollectionStats($collid);
		}
		$this->logOrEcho("Stats update completed");
	}

	//Set and Get functions
	public function setCollArr($cArr){
		if($cArr){
			if(is_array($cArr)){
				$this->collArr = $cArr;
				//Set additional collection info
				//Get Metadata
				$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype FROM omcollections '.
					'WHERE (collid IN('.implode(',',array_keys($cArr)).'))';
				if($rs = $this->conn->query($sql)){
					if($rs->num_rows){
						while($r = $rs->fetch_object()){
							$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
							$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
							$this->collArr[$r->collid]['collname'] = $r->collectionname;
							$this->collArr[$r->collid]['managementtype'] = $r->managementtype;
						}
					}
					else{
						$this->logOrEcho('ABORT: unable to get collection metadata from database (collids might be wrong) ');
						exit('ABORT: unable to get collection metadata from database');
					}
					$rs->free();
				}
				else{
					$this->logOrEcho('ABORT: unable run SQL to obtain additional collection metadata: '.$this->conn->error);
					exit('ABORT: unable run SQL to obtain additional collection metadata'.$this->conn->error);
					}
			}
		}
		else{
			$this->logOrEcho("Error: collection array does not exist");
			exit("ABORT: collection array does not exist");
		}
	}
	
	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}

	public function setLogPath($path){
		if($path && substr($path,-1) != '/' && substr($path,-1) != "\\") $path .= '/';
		$this->logPath = $path;
	}

	private function cleanInString($inStr){
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
		}
	}
}
?>