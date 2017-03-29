<?php
include_once($SERVER_ROOT.'/classes/SpecUploadBase.php');
class SpecUploadFile extends SpecUploadBase{
	
	private $ulFileName;
	private $delimiter = ",";
	private $isCsv = false;

	function __construct() {
 		parent::__construct();
		$this->setUploadTargetPath();
  		ini_set('auto_detect_line_endings', true);
	}

	public function __destruct(){
 		parent::__destruct();
	}
	
	public function uploadFile(){
		if(!$this->ulFileName){
			if(array_key_exists("ulfnoverride",$_POST) && $_POST['ulfnoverride']){
				$this->ulFileName = $_POST['ulfnoverride'];
			}
			elseif(array_key_exists("uploadfile",$_FILES)){
				$this->ulFileName = $_FILES['uploadfile']['name'];
				$fullPath = $this->uploadTargetPath.$this->ulFileName;
				if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $fullPath)){
					$fullPath = $this->uploadTargetPath.$this->ulFileName;
					//If a zip file, unpackage and assume that first and/or only file is the occurrrence file
			        if(substr($fullPath,-4) == ".zip"){
			        	$zipFilePath = $fullPath;
						$zip = new ZipArchive;
						$res = $zip->open($fullPath);
						if ($res === TRUE) {
							$this->ulFileName = $zip->getNameIndex(0);
							$fullPath = $this->uploadTargetPath.$this->ulFileName; 
							$zip->extractTo($this->uploadTargetPath);
							$zip->close();
							unlink($zipFilePath);
						}
						else{
							echo 'failed, code:' . $res;
			 				return false;
						}
			        }
				}
				else{
					echo '<div style="margin:15px;">';
					echo '<div style="font-weight:bold;font-size:120%;color:red;">ERROR: unable to upload file; '.$_FILES['uploadfile']['error'].' </div>';
					echo '<div style="font-weight:bold;font-size:120%;">The zip file may be too large for the upload limits set within the PHP configurations (upload_max_filesize = '.ini_get("upload_max_filesize").'; post_max_size = '.ini_get("post_max_size").')</div>';
					echo '</div>';
					return false;
				}
			}
		}
		return $this->ulFileName;
	}
 	
	public function analyzeUpload(){
		//Just read first line of file to report what fields will be loaded, ignored, and required fulfilled
	 	$fullPath = '';
		if(strpos($this->ulFileName,'/') !== false || strpos($this->ulFileName,'\\') !== false){
			//File was placed on server by hand (typically done by portal if file is too large for upload)
			$fullPath = $this->ulFileName;
		}
		else{
			//File was already uploaded to tempory folder
			$fullPath = $this->uploadTargetPath.$this->ulFileName;
		}
		if($fullPath){
	        //Open and grab header fields
			$fh = fopen($fullPath,'rb') or die("Can't open file");
			$this->sourceArr = $this->getHeaderArr($fh);
			fclose($fh);
		}
	}

	public function uploadData($finalTransfer){
		if($this->ulFileName){
			set_time_limit(7200);
		 	ini_set("max_input_time",240);

			$this->outputMsg('<li>Initiating data upload for file: '.$this->ulFileName.'</li>');
		 	//First, delete all records in uploadspectemp table associated with this collection
			$this->prepUploadData();
			
			$fullPath = $this->uploadTargetPath.$this->ulFileName;
	 		$fh = fopen($fullPath,'rb') or die("Can't open file");
			
			$headerArr = $this->getHeaderArr($fh);
			
			//Grab data 
			$this->transferCount = 0;
			$this->outputMsg('<li>Beginning to load records...</li>',1);
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
				$goodToLoad = true;
				if($this->uploadType == $this->SKELETAL){
					if(!array_key_exists('catalognumber',$recMap) || !$recMap['catalognumber']) $goodToLoad = false;
				}
				if($goodToLoad) $this->loadRecord($recMap);
				unset($recMap);
			}
			fclose($fh);

			//Delete upload file 
			if(file_exists($fullPath)) unlink($fullPath);
			
			$this->cleanUpload();

			if($finalTransfer){
				$this->transferOccurrences();
				$this->finalCleanup();
			}
			else{
				$this->outputMsg('<li>Record upload complete, ready for final transfer and activation</li>');
			}
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

    public function setUploadFileName($ulFile){
		$this->ulFileName = $ulFile;
	}

	public function getDbpkOptions(){
		$sFields = $this->sourceArr;
		sort($sFields);
		return $sFields;
	}
}
?>