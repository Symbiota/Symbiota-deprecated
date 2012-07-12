<?php
/*
 * Built 20 Oct 2010
 * E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');

class DynamicChecklistManager {

	private $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function createChecklist($lat, $lng, $radius, $tid){
		global $uid;
		//set_time_limit(120);
		$sql = "Call DynamicChecklist(".$lat.",".$lng.",".$radius.",".$tid.",".($uid?$uid:"NULL").")";
		echo $sql;
		$result = $this->conn->query($sql);
		if($row = $result->fetch_row()){
			$dynPk = $row[0];
		}
		$result->close();
		return $dynPk;
	}
	
	public function getFilterTaxa(){
		$retArr = Array();
		$sql = "SELECT t.tid, t.sciname FROM taxa t WHERE t.rankid <= 140 ORDER BY t.sciname ";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->tid] = $row->sciname;
		}
		return $retArr;
	}
}

 ?>