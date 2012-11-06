<?php
class SpecUploadFile extends SpecUploadManager{
	
	private $ulFileName;
	private $uploadTargetPath;
	private $delimiter = ",";
	private $isCsv = false;

	function __construct() {
 		parent::__construct();
  		ini_set('auto_detect_line_endings', true);
	}

	public function __destruct(){
 		parent::__destruct();
	}
	
	public function analyzeFile(){
	 	$this->readUploadParameters();
		//Just read first line of file to report what fields will be loaded, ignored, and required fulfilled
	 	$targetPath = $this->getUploadTargetPath();
	 	$fullPath = '';
		if(!$this->ulFileName){
		 	$this->ulFileName = $_FILES['uploadfile']['name'];
			$fullPath = $targetPath.$this->ulFileName;
		 	move_uploaded_file($_FILES['uploadfile']['tmp_name'], $fullPath);
		}
		elseif(strpos($this->ulFileName,'/') !== false || strpos($this->ulFileName,'\\') !== false){
			if(file_exists($this->ulFileName)){
				$extStr = substr($this->ulFileName,-4);
				$tempFileName = 'transCollId'.$this->collId.$extStr;
				$fullPath = $targetPath.$tempFileName;
				if(copy($this->ulFileName,$fullPath)){
					unlink($this->ulFileName);
					$this->ulFileName = $tempFileName;
				}
				else{
					echo '<li>ERROR: Unable to copy file to temp locality</li>';
					echo '<li style="margin-left:10px;">Source File: '.$this->ulFileName.'</li>';
					echo '<li style="margin-left:10px;">Target Path: '.$fullPath.'</li>';
				}
			}
			else{
				echo '<li>ERROR: File not found ('.$this->ulFileName.')</li>';
			}
		}
		else{
			$fullPath = $targetPath.$this->ulFileName;
		}
		if($fullPath){
	        if(substr($fullPath,-4) == ".zip"){
	        	$zipFilePath = $fullPath;
				$zip = new ZipArchive;
				$zip->open($fullPath);
				$this->ulFileName = $zip->getNameIndex(0);
				$fullPath = $targetPath.$this->ulFileName; 
				$zip->extractTo($targetPath);
				$zip->close();
				unlink($zipFilePath);
	        }
			$fh = fopen($fullPath,'rb') or die("Can't open file");
			$this->sourceArr = $this->getHeaderArr($fh);
		}
	}
 	
	public function uploadData($finalTransfer,$delimiter="\t"){
		if($this->ulFileName){
		 	$this->readUploadParameters();
			set_time_limit(7200);
		 	ini_set("max_input_time",240);
	
			//First, delete all records in uploadspectemp table associated with this collection
			$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
			$this->conn->query($sqlDel);
			
			$fullPath = $this->getUploadTargetPath().$this->ulFileName;
	 		$fh = fopen($fullPath,'rb') or die("Can't open file");
			
			$headerArr = $this->getHeaderArr($fh);
			
			//Grab data 
			$this->transferCount = 0;
			while($recordArr = $this->getRecordArr($fh)){
				$recMap = Array();
				foreach($this->fieldMap as $symbField => $sMap){
					$indexArr = array_keys($headerArr,$sMap['field']);
					$index = array_shift($indexArr);
					if(array_key_exists($index,$recordArr)){
						$valueStr = $recordArr[$index];
						//If value is enclosed by quotes, remove quotes
						if(substr($valueStr,0,1) == '"' && substr($valueStr,-1) == '"'){
							$valueStr = substr($valueStr,1,strlen($valueStr)-2);
						}
						$recMap[$symbField] = $valueStr;
					}
				}
				$this->loadRecord($recMap);
				unset($recMap);
			}
			fclose($fh);

			//Delete upload file 
			if(file_exists($fullPath)) unlink($fullPath);
			
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
		$retArr = array();
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$retArr[] = $fieldStr;
			}
			else{
				break;
			}
		}
		return $retArr;
    }

    private function getRecordArr($fHandler){
    	$recordArr = Array();
    	if($this->isCsv){
			$recordArr = fgetcsv($fHandler,0,$this->delimiter);
    	}
    	else{
			$record = fgets($fHandler);
			if($record) $recordArr = explode($this->delimiter,$record);
    	}
    	return $recordArr;
    }

    private function getUploadTargetPath(){
    	if($this->uploadTargetPath) return $this->uploadTargetPath;
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp";
		}
		if(file_exists($tPath."/downloads")){
			$tPath .= "/downloads";
		}
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
			$tPath .= '/';
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
