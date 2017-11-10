<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceAccessStats {

	private $conn;
	private $errorMessage;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function batchRecordEvents($occidStr, $accessType){
		$status = true;
		if(!preg_match('/^[\d,]+$/', $occidStr)) return false;
		$occidArr = explode(',',$occidStr);
		foreach($occidArr as $occid){
			if(!$this->recordAccessEvent($occid, $accessType)){
				$status = false;
			}
		}
		return $status;
	}
	
	public function batchRecordEventsBySql($sqlFrag,$accessType){
		$status = true;
		$sql = 'INSERT INTO omoccuraccessstatstemp (occid,accessdate,ipaddress,cnt,accesstype) '.
			'SELECT o.occid, "'.date('Y-m-d').'", "'.$this->cleanInStr($_SERVER['REMOTE_ADDR']).'", 1, "'.$this->cleanInStr($accessType).'" ';
		$sql .= $sqlFrag;
		$sql .= 'ON DUPLICATE KEY UPDATE cnt=cnt+1';
		if(!$this->conn->query($sql)){
			$this->errorMessage = 'ERROR batch recording access event by SQL: '.$con->error;
		}
		return $status;
	}

	public function recordAccessEvent($occid,$accessType){
		$status = false;
		if(is_numeric($occid)){
			$sql = 'INSERT INTO omoccuraccessstatstemp '.
				'SET occid='.$occid.', accessdate="'.date('Y-m-d').'", ipaddress="'.$this->cleanInStr($_SERVER['REMOTE_ADDR']).'", '.
				'cnt=1, accesstype="'.$this->cleanInStr($accessType).'" ON DUPLICATE KEY UPDATE cnt=cnt+1';
			//echo $sql;
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorMessage = 'ERROR recording access event: '.$con->error;
			}
		}
		return $status;
	}

	public function compileMonthlyStatistics(){
		
	}

	//Reports
	public function getReport(){
		
	}

	//Setters and getters
	public function getErrorStr(){
		return $this->errorMessage;
	}

	//Misc fucntions
	private function cleanInStr($str){
		$newStr = trim($str);
		if($newStr){
			$newStr = preg_replace('/\s\s+/', ' ',$newStr);
			$newStr = $this->conn->real_escape_string($newStr);
		}
		return $newStr;
	}
}
?>