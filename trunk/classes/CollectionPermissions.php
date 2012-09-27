<?php
include_once($serverRoot.'/config/dbconnection.php');

class CollectionPermissions {

	private $conn;
	private $collId;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollectionId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
		}
	}

	public function getCollectionData(){
		$returnArr = Array();
		if($this->collId){
			$sql = "SELECT CollectionCode, CollectionName ".
				"FROM omcollections ".
				"WHERE (collid = ".$this->collId.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['collectioncode'] = $row->CollectionCode;
				$returnArr['collectionname'] = $row->CollectionName;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function getEditors(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT up.uid, up.pname, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
				'FROM userpermissions up INNER JOIN users u ON up.uid = u.uid '.
				'WHERE up.pname = "CollAdmin-'.$this->collId.'" OR up.pname = "CollEditor-'.$this->collId.'" '. 
				'OR up.pname = "RareSppReader-'.$this->collId.'" ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$pGroup = 'rarespp';
				if(substr($r->pname,0,9) == 'CollAdmin') $pGroup = 'admin';
				elseif(substr($r->pname,0,10) == 'CollEditor') $pGroup = 'editor'; 
				$returnArr[$pGroup][$r->uid] = $r->uname;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function getUsers(){
		$returnArr = Array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
			'FROM users u '.
			'ORDER BY u.lastname,u.firstname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$returnArr[$r->uid] = $r->uname;
		}
		$rs->close();
		return $returnArr;
	}

	public function deletePermission($uid,$ur){
		$userRight = '';
		if($ur == 'admin'){
			$userRight = 'CollAdmin-'.$this->collId;
		}
		elseif($ur == 'editor'){
			$userRight = 'CollEditor-'.$this->collId;
		}
		elseif($ur == 'rare'){
			$userRight = 'RareSppReader-'.$this->collId;
		}
		if($userRight){
			$sql = 'DELETE FROM userpermissions WHERE uid = '.$uid.' AND pname = "'.$userRight.'"';
			//echo $sql;
			$this->conn->query($sql);
		}
	}
	
	public function addUser($uid,$ur){
		$userRight = '';
		if($ur == 'admin'){
			$userRight = 'CollAdmin-'.$this->collId;
		}
		elseif($ur == 'editor'){
			$userRight = 'CollEditor-'.$this->collId;
		}
		elseif($ur == 'rare'){
			$userRight = 'RareSppReader-'.$this->collId;
		}
		if($userRight){
			$sql = 'INSERT INTO userpermissions(uid,pname) VALUES('.$uid.',"'.$userRight.'") ';
			//echo $sql;
			$this->conn->query($sql);
		}
	}
	
	private function cleanStr($inStr){
		$outStr = trim($inStr);
		$outStr = str_replace('"',"'",$inStr);
		$outStr = $this->conn->real_escape_string(htmlspecialchars($outStr));
		return $outStr;
	}
}

 ?>