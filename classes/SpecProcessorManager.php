<?php
/*
 * Built 26 Jan 2011
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecProcessorAbbyy.php');
include_once($serverRoot.'/classes/SpecProcessorImage.php');

class SpecProcessorManager {

	protected $conn;
	protected $collId;

	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->logPath = $GLOBALS['logPath'];
		if(substr($this->logPath,1) != '/') $this->logPath .= '/';
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id) {
		if($id) $this->collId = $id;
	}

	protected function loadRecord($labelBlock){
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
				if($this->conn->query('INSERT INTO omoccurrences(collid,dbpk,catalognumber,processingstatus) VALUES('.$this->collId.',"'.$pkStr.'","'.$pkStr.'","unparsed")')){
					$occId = $this->conn->insert_id;
				} 
			}
			if($occId){
				//load raw label record
				$sql = 'INSERT INTO specprocessorrawlabels(occid,rawstr) VALUES('.$occId.',"'.$this->cleanStr($labelBlock).'")';
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

	public function getLogPath(){
		return $this->logPath;
	}

	public function getErrLogPath(){
		return $this->logErrPath;
	}

	protected function cleanStr($str){
		$str = str_replace('"','',$str);
		return $str;
	}
}
?>
 