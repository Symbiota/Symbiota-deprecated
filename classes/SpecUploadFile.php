<?php
class SpecUploadFile extends SpecUploadManager{
	
	private $ulFileName;
	private $zipFileName;
	private $uploadTargetPath;
	private $delimiter = ",";
	private $isCsv = false;

	function __construct() {
 		parent::__construct();
 		set_time_limit(600);
	}

	public function analyzeFile(){
	 	$this->readUploadParameters();
		//Just read first line of file to report what fields will be loaded, ignored, and required fulfilled
	 	$targetPath = $this->getUploadTargetPath();
		if(!$this->ulFileName){
		 	$this->ulFileName = $_FILES['uploadfile']['name'];
	        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $targetPath."/".$this->ulFileName);
	        if(substr($this->ulFileName,-4) == ".zip"){
	        	$this->zipFileName = $this->ulFileName;
				$zip = new ZipArchive;
				$zip->open($targetPath."/".$this->ulFileName);
				$this->ulFileName = $zip->getNameIndex(0);
				$zip->extractTo($targetPath);
				$zip->close();
	        }
		}
		$fullPath = $targetPath."/".$this->ulFileName;
		$fh = fopen($fullPath,'rb') or die("Can't open file");
		$this->sourceArr = $this->getHeaderArr($fh);
	}
 	
	public function uploadData($finalTransfer,$delimiter="\t"){
		if($this->ulFileName){
		 	$this->readUploadParameters();
			set_time_limit(200);
			ini_set("max_input_time",120);
			ini_set("upload_max_filesize",10);
	
			//First, delete all records in uploadspectemp table associated with this collection
			$sqlDel = "DELETE FROM uploadspectemp WHERE collid = ".$this->collId;
			$this->conn->query($sqlDel);
			
			$fullPath = $this->getUploadTargetPath()."/".$this->ulFileName;
	 		$fh = fopen($fullPath,'rb') or die("Can't open file");
			
			$headerArr = $this->getHeaderArr($fh);
			
			$colCnt = 0;
			$sourceArr = Array();
			$targetArr = Array();
			$tempSourceArr = Array();
			//Load sourceArr with mapped fields (map should contain all matching and unmatcing fields)
			foreach($this->fieldMap as $symbField => $detailArr){
				$sourceField = $detailArr["field"];
				if(in_array($sourceField,$headerArr)){
					$sourceArr[$symbField] = $sourceField;
				}
			}
			
			//Set $sqlBase values (all specimen field names that are mapped to headerArr) 
			foreach($headerArr as $k => $fieldStr){
				$kArr = array_keys($sourceArr,$fieldStr);
				foreach($kArr as $v){
					$targetArr[] = $v;
				}
				$colCnt++;
			}
			//Grab data 
			$sqlBase = "INSERT INTO uploadspectemp(collid,".implode(",",$targetArr).") ";
			$this->transferCount = 1;
			$reqFieldsNullCnt = 0;
			while($recordArr = $this->getRecordArr($fh)){
				if(count($recordArr) == count($headerArr)){
					//If there is no sciname, see if you can populate by family
					if(!$recordArr[array_search($sourceArr["sciname"],$headerArr)]){
						if(array_key_exists("family",$sourceArr) && $recordArr[array_search($sourceArr["family"],$headerArr)]){
							$recordArr[array_search($sourceArr["sciname"],$headerArr)] = $recordArr[array_search($sourceArr["family"],$headerArr)];
						}
					}
					
					//Load only if there is a scientific name in the record
					if($recordArr[array_search($this->fieldMap["sciname"]["field"],$headerArr)]){
						$sqlValues = "";
						$headCnt = count($headerArr);
						for($x=0;$x<$headCnt;$x++){
							//Iterate through record values and use to build SQL statement
							if($spKeys = array_keys($sourceArr,$headerArr[$x])){
								//A header field may be linked to multiple Symbiota fields
								foreach($spKeys as $specName){
									$valueStr = trim($recordArr[$x]);
									//If value is encloded by quotes, remove the quotes
									if(substr($valueStr,0,1) == "\"" && substr($valueStr,-1) == "\""){
										$valueStr = substr($valueStr,1,strlen($valueStr)-2);
									}
									$valueStr = $this->cleanString($valueStr);
									$valueStr = $this->encodeString($valueStr);
									//Load data
									$type = $this->fieldMap[$specName]["type"];
									$size = (array_key_exists("size",$this->fieldMap[$specName])?$this->fieldMap[$specName]["size"]:0);
									switch($type){
										case "numeric":
											if(!$valueStr) $valueStr = "NULL";
											$sqlValues .= ",".$valueStr;
											break;
										case "date":
											if(($dateStr = strtotime($valueStr))){
												$sqlValues .= ",\"".date('Y-m-d H:i:s', $dateStr)."\"";
											} 
											else{
												$sqlValues .= ",NULL";
											}
											break;
										default:	//string
											if($size && strlen($valueStr) > $size){
												$valueStr = substr($valueStr,0,$size);
											}
											if($valueStr){
												$sqlValues .= ",\"".$valueStr."\"";
											}
											else{
												$sqlValues .= ",NULL";
											}
									}
								}
							}
						}
						
						$sql = $sqlBase."VALUES(".$this->collId.",".substr($sqlValues,1).")";
						//echo "<div>".$recordCnt.": ".$sql."</div>";
						
						$status = $this->conn->query($sql);
						if($status){
							//echo "<li>";
							//echo "Appending/Replacing observation #".$this->transferCount.": SUCCESS";
							//echo "</li>";
						}
						else{
							echo "<li>FAILED adding record #".$this->transferCount."</li>";
							echo "<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>";
							echo "<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>";
						}
						$this->transferCount++;
					}
					else{
						echo "<li>Record skipped due to lack of scientific name</li>";
					}
				}
				else{
					echo "<li>Record skipped: ".count($recordArr)." columns of data exist, but ".count($headerArr)." columns were excepted; may be due to tab, return, or new line characters enbedded in data record.";
					//print_r($recordArr);
					echo "</li>";
				}
			}
			fclose($fh);
			//Delete upload file 
			if(file_exists($fullPath)) unlink($fullPath);
			if($this->zipFileName) unlink($this->getUploadTargetPath()."/".$this->zipFileName);
			
			$this->finalUploadSteps($finalTransfer);
		}
		else{
			echo "<li>File Upload FAILED: unable to locate file</li>";
		}
    }
    
    private function getHeaderArr($fHandler){
		$headerData = fgets($fHandler);
		//Check to see if we can figure out the delimiter
		if(strpos($headerData,",") === false){
			if(strpos($headerData,"\t") !== false){
				$this->delimiter = "\t";
			}
		}
		//Check to see if file is csv\
        if(substr($this->ulFileName,-4) == ".csv" || strpos($headerData,$this->delimiter.'"') !== false){
        	$this->isCsv = true;
        }
        //Grab header terms
        $headerArr = Array();
		if($this->isCsv){
			rewind($fHandler);
			$headerArr = fgetcsv($fHandler,0,$this->delimiter);
		}
		else{
			$headerArr = explode($this->delimiter,$headerData);
		}
		foreach($headerArr as $k => $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$headerArr[$k] = $fieldStr;
			}
			else{
				break;
			}
		}
		return $headerArr;
    }

    private function getRecordArr($fHandler){
    	$recordArr = Array();
    	if($this->isCsv){
			$recordArr = fgetcsv($fHandler,0,$this->delimiter);
    	}
    	else{
	    	$record = fgets($fh);
    		$recordArr = explode($this->delimiter,$record);
    	}
    	return $recordArr;
    }

    private function getUploadTargetPath(){
    	if($this->uploadTargetPath) return $this->uploadTargetPath;
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp";
		}
		if(!file_exists($tPath."/downloads")){
			mkdir($tPath."/downloads");
		}
		if(file_exists($tPath."/downloads")){
			$tPath .= "/downloads";
		}
		$this->uploadTargetPath = $tPath;
    	return $tPath;
    }
    
    public function setUploadFileName($ulFile){
    	$this->ulFileName = $ulFile;
    }
    
    public function getUploadFileName(){
    	return $this->ulFileName;
    }
    
    public function setDelimiter($dlimit){
		$this->delimiter = $dlimit;
    }
}
	
?>
