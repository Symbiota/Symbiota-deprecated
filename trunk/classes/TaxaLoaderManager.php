<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/TaxaLoaderItisManager.php');

class TaxaLoaderManager{
	
	protected $conn;
	protected $sourceArr = Array();
	protected $targetArr = Array();
	protected $fieldMap = Array();	//target field => source field
	private $uploadFilePath; 
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
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
		$this->cleanUpload();
		return $statusStr;
	}

	protected function cleanUpload(){
		$sql = "CALL uploadTaxaClean(0)";
		$this->conn->query($sql);
	}

	public function transferUpload(){
		$sql = "CALL uploadTaxaTransfer()";
		$this->conn->query($sql);
		$this->buildHierarchy();
	}

	protected function buildHierarchy($taxAuthId = 1){
		do{
			unset($hArr);
			$hArr = Array();
			$tempArr = Array();
			$sql = "SELECT ts.tid FROM taxstatus ts WHERE taxauthid = $taxAuthId AND ts.hierarchystr IS NULL LIMIT 100";
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				while($row = $rs->fetch_object()){
					$hArr[$row->tid] = $row->tid;
				}
				do{
					unset($tempArr);
					$tempArr = Array();
					$sql2 = "SELECT IFNULL(ts.parenttid,0) AS parenttid, ts.tid ".
						"FROM taxstatus ts WHERE taxauthid = ".$taxAuthId." AND ts.tid IN(".implode(",",array_keys($hArr)).")";
					//echo $sql2."<br />";
					$rs2 = $this->conn->query($sql2);
					while($row2 = $rs2->fetch_object()){
						$tid = $row2->tid;
						$pTid = $row2->parenttid;
						if($pTid && $tid != $pTid){
							if(array_key_exists($tid,$hArr)){
								$tempArr[$pTid][$tid] = $hArr[$tid];
								unset($hArr[$tid]);
							}
						}
					}
					foreach($tempArr as $p => $c){
						$hArr[$p] = $c;
					}
					$rs2->close();
				}while($tempArr);
				//Process hierarchy strings
				$finalArr = Array();
				$finalArr = $this->getLeaves($hArr);
				foreach($finalArr as $hStr => $taxaArr){
					$sqlInsert = "UPDATE taxstatus ts ".
						"SET ts.hierarchystr = '".$hStr."' ".
						"WHERE ts.taxauthid = ".$taxAuthId." AND ts.tid IN(".implode(",",$taxaArr).") AND (ts.hierarchystr IS NULL)";
					//echo "<div>".$sqlInsert."</div>";
					$this->conn->query($sqlInsert);
				}
			}
		}while($rs->num_rows);
	}
	
	private function getLeaves($inArr,$seed=""){
		$retArr = Array();
		foreach($inArr as $p => $t){
			if(is_array($t)){
				$newSeed = $seed.",".$p;
				$retArr = array_merge($retArr,$this->getLeaves($t,$newSeed));
			}
			else{
				if(!$seed) $seed = $p;
				$retArr[substr($seed,1)][] = $t;
			}
		}
		return $retArr;
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
	
	public function getFieldMap(){
		return $this->fieldMap;
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
