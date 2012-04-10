<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class PersonalSpecimenManager {

	private $conn;
	private $collId;
	private $collName;
	private $collType;
	private $uid;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
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
				'FROM omcollections WHERE '; 
			if($isAdmin){
				$sql .= 'colltype = "general observations" ';
				if($collIdStr){
					$sql .= 'OR collid IN('.substr($collIdStr,1).') ';
				}
			}
			else{
				$sql .= 'collid IN('.substr($collIdStr,1).') ';
			}
			$sql .= 'ORDER BY collectionname';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname.($r->instcode?' ('.$r->instcode.')':'');
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getRecordCount(){
		$retCnt = 0;
		if($this->uid){
			$sql = 'SELECT count(*) AS reccnt FROM omoccurrences WHERE observeruid = '.$this->uid.' AND collid = '.$this->collId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retCnt = $r->reccnt;
				}
				$rs->close();
			}
		}
		return $retCnt;
	}

	public function setUid($id){
		$this->uid = $id;
	}

	public function setCollId($collId){
		$this->collId = $collId;
		$sql = 'SELECT collectionname, colltype FROM omcollections WHERE collid = '.$collId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->collName = $r->collectionname;
				$this->collType = $r->colltype;
			}
			$rs->close();
		}
	}

	public function getCollName(){
		return $this->collName;
	}

	public function getCollType(){
		return $this->collType;
	}

}
?> 