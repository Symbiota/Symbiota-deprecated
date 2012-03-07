<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class PersonalSpecimenManager {

	private $conn;
	private $uid;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	private function setCollId($collId){
		$sql = '';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->obsProjArr[$r->collid] = $r->collectionname;
			}
			$rs->close();
		}
	}
	
	
	public function getRecordCount(){
		$retCnt = 0;
		if($this->uid){
			$sql = 'SELECT count(*) AS reccnt FROM omoccurrences WHERE observeruid = '.$this->uid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retCnt = $r->reccnt;
				}
				$rs->close();
			}
		}
		return $retCnt;
	}
	
	public function getObservationArr(){
		global $userRights;
		$retArr = array();
		if($this->uid){
			$isAdmin = 0;
			$collIdStr = '';
			foreach($userRights as $k => $v){
				if($k == 'SuperAdmin'){
					$isAdmin = 1;
				}
				elseif($k == 'CollAdmin'){
					$collIdStr .= ','.implode(',',$v);
				}
				elseif($k == 'CollEditor'){
					$collIdStr .= ','.implode(',',$v);
				}
			}
			$sql = 'SELECT collid, collectionname, CONCAT_WS(" ",institutioncode,collectioncode) AS instcode '.
				'FROM omcollections '.
				'WHERE colltype LIKE "%observations%" '; 
			if(!$isAdmin){
				$sql .= 'AND collid IN('.substr($collIdStr,1).') ';
			}
			$sql .= 'ORDER BY collectionname';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname.($r->instcode?' ('.$r->instcode.')':'');
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function setUid($id){
		$this->uid = $id;
	}
	
}
?> 