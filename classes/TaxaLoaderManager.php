<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/TaxaLoaderManager.php');

class TaxaLoaderItisManager{
	
	protected $conn;
	protected $sourceArr = Array();
	protected $targetArr = Array();
	protected $fieldMap = Array();	//target field => source field
	private $uploadFilePath; 
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 		set_time_limit(600);
		ini_set("max_input_time",120);
		ini_set("upload_max_filesize",10);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setUploadFile($ulFileName = ""){
		//Just read first line of file in order to map fields to uploadtaxa table
	 	$targetPath = $this->getUploadTargetPath();
		if(!$ulFileName){
		 	$ulFileName = $_FILES['uploadfile']['name'];
	        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $targetPath."/".$ulFileName);
		}
		$fullPath = $targetPath."/".$ulFileName;
		$this->uploadFilePath = $fullPath;
	}

	public function uploadFile($ulFileName){
		$statusStr = "<li>Starting Upload</li>";
		$this->conn->query("DELETE FROM uploadtaxa");
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'rb') or die("Can't open file");
		$recordCnt = 0;
		while($record = fgets($fh)){
			$recordArr = explode("|",$record);
		}
		$statusStr .= '<li>'.$recordCnt.' taxon records uploaded</li>';
		fclose($fh);
		return $statusStr;
	}

    protected function getUploadTargetPath(){
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
    	return $tPath;
    }

	protected function setFieldMap($fm){
		$this->fieldMap = $fm;
	}

	private function setTargetArr(){
		//Get metadata
		$sql = "SHOW COLUMNS FROM uploadtaxa";
		$rs = $this->conn->query($sql);
    	while($row = $rs->fetch_object()){
    		$field = strtolower($row->Field);
			$this->targetArr[] = $field;
    	}
    	$rs->close();
	}
	
	private function setSourceArr(){
		$fh = fopen($this->uploadFilePath,'rb') or die("Can't open file");
		$headerData = fgets($fh);
		$headerArr = explode("\t",$headerData);
		$sourceArr = Array();
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$sourceArr[] = $fieldStr;
			}
			else{
				break;
			}
		}
		$this->sourceArr = $sourceArr;
	}
    
	protected function getTargetArr(){
		if(!$this->targetArr){
			$this->setTargetArr();
		}
		return $this->targetArr;
	}

	protected function getSourceArr(){
		if(!$this->sourceArr){
			$this->setSourceArr();
		}
		return $this->sourceArr;
	}
	
 	protected function cleanField($field){
		$rStr = str_replace("\"","'",$rStr);
		return $rStr;
	}
}
?>
