<?php
/*
 * Built 26 Jan 2011
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');
 
class PopulusManager {

	private $conn;
	private $collId;
	private $recDelimiter = '/--END--/';
	private $pkPattern = '/(ASU\d{7})/';
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id) {
		$this->collId = $id;
	}

	public function loadLabelFile(){
		$statusArr = Array();
	 	$fileName = basename($_FILES['abbyyfile']['name']);
	 	$filePath = $GLOBALS['tempDirRoot'];
	 	if(substr($filePath,-1) != '/') $filePath .= '/';
	 	$filePath .= 'downloads/';
	 	if(move_uploaded_file($_FILES['abbyyfile']['tmp_name'], $filePath.$fileName)){
	 		$fh = fopen($filePath.$fileName,'rb') or die("Can't open file");
			if($fh){
				$statusArr = $this->parseAbbyyFile($fh);
			}
			fclose($fh);
			unlink($filePath.$fileName);
	 	}
	 	return $statusArr;
	}
	
	private function parseAbbyyFile($fh){
		$statusArr = Array();
		$labelBlock = '';
		$lineCnt = 0;
		while(!feof($fh)){
			$buffer = fgets($fh);
			if(preg_match($this->recDelimiter,$buffer)){
				$labelBlock = trim($labelBlock);
				if($labelBlock){
					if($statusStr = $this->loadRecord(trim($labelBlock))){
						$statusArr[] = $statusStr;
					}
				}
				$labelBlock = '';
				$lineCnt = 0;
			}
			else{
				$labelBlock .= $buffer;
				$lineCnt++;
			}
		}
		return $statusArr;
	}

	private function loadRecord($labelBlock){
		$status = '';
		if(preg_match($this->pkPattern, $labelBlock, $matches)){
			$pkStr = $matches[1];
			//Check to see if record with pk already exists
			$occId = 0;
			$sql = 'SELECT occid FROM omoccurrences WHERE dbpk = "'.$pkStr.'"';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$occId = $row->occid;
			}
			$rs->close();
			//load new, empty occurrence record
			if(!$occId){
				if($this->conn->query('INSERT INTO omoccurrences(collid,dbpk,catalognumber,populusstatus) VALUES('.$this->collId.',"'.$pkStr.'","'.$pkStr.'","unparsed")')){
					$occId = $this->conn->insert_id;
				} 
			}
			if($occId){
				//load raw label record
				$sql = 'INSERT INTO populusrawlabels(occid,rawstr) VALUES('.$occId.',"'.$this->cleanStr($labelBlock).'")';
				if(!$this->conn->query($sql)){
					$status = 'ERROR: unable to insert raw label record #'.$occId.'; SQL ERR: '.$this->conn->error;
					$status .= 'SQL: '.$sql;
				}
			}
			else{
				$status = 'ERROR: unable identify or create occurrence primary key (occid)';
			}
		}
		else{
			$status = 'ERROR: pkPattern not found, unable to extract primary key';
		}
		return $status;
	}

	public function getCollectionList(){
		global $isAdmin, $userRights;
		$returnArr = Array();
		if($isAdmin || array_key_exists("CollAdmin",$userRights)){
			$sql = 'SELECT DISTINCT c.CollID, c.CollectionName, c.icon '.
				'FROM omcollections c ';
			if(array_key_exists('CollAdmin',$userRights)){
				$sql .= 'WHERE c.collid IN('.implode(',',$userRights['CollAdmin']).') '; 
			}
			$sql .= 'ORDER BY c.CollectionName';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$collId = $row->CollID;
				$returnArr[$collId] = $row->CollectionName;
			}
			$result->close();
		}
		return $returnArr;
	}
	
	private function cleanStr($str){
		$str = str_replace('"','',$str);
		return $str;
	}
}
?>
 