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
			$sql = "SELECT c.CollectionCode, c.CollectionName ".
				"FROM omcollections ".
				"LEFT JOIN institutions i ON c.iid = i.iid ".
				"WHERE (c.collid = ".$this->collId.") ORDER BY c.SortSeq";
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

	private function getEditors(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT up.uid, up.pname, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
				'FROM userpermissions up INNER JOIN users u ON up.uid = u.uid '.
				'WHERE up.pname = "CollAdmin-'.$this->collId.'" OR up.pname = "CollEditor-'.$this->collId.'" ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$returnArr[$r->pname][$r->uid] = $r->uname;
			}
			$rs->close();
		}
		return $returnArr;
	}

	private function cleanStr($inStr){
		$outStr = trim($inStr);
		$outStr = str_replace('"',"'",$inStr);
		$outStr = $this->conn->real_escape_string($outStr);
		return $outStr;
	}
}

 ?>