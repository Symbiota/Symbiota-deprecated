<?php
include_once($serverRoot.'/config/dbconnection.php');

class KeyAdmin{

	private $conn;
	private $collId = 0;
	private $cId = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}
	
	public function getCharList(){
		$retArr = array();
		$sql = 'SELECT cid, charname '.
			'FROM kmcharacters '.
			'ORDER BY charname ASC';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->cid]['charname'] = $r->charname;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function createCharacter($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO kmcharacters(charname,enteredby) '.
			'VALUES("'.$this->cleanString($pArr['charname']).'","'.$this->cleanString($pArr['enteredby']).'") ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->cId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	

	//Get and set functions 
	public function getHeadingArr(){
		$retArr = array();
		$sql = 'SELECT hid, headingname, language '. 
			'FROM kmcharheading '. 
			'ORDER BY hid';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->hid] = $r->headingname;
				$retArr[$r->hid] = $r->language;
			}
		}
		return $retArr;
	}
	
	public function setCollId($c){
		$this->collId = $c;
	}
	
	public function getcId(){
		return $this->cId;
	}
	
	protected function cleanString($inStr){
		$retStr = trim($inStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>