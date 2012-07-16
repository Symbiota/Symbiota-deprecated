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

	//General look up functions
	public function getInstitutionArr(){
		$retArr = array();
		$sql = 'SELECT i.iid, IFNULL(c.institutioncode,i.institutioncode) as institutioncode, '. 
			'i.institutionname '. 
			'FROM institutions i LEFT JOIN (SELECT iid, institutioncode, collectioncode, collectionname '. 
			'FROM omcollections WHERE colltype = "Preserved Specimens") c ON i.iid = c.iid '. 
			'ORDER BY i.institutioncode,c.institutioncode,c.collectionname,i.institutionname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->iid] = $r->institutioncode.' - '.$r->institutionname;
			}
		}
		return $retArr;
	} 
	
	//Get and set functions 
	public function setCollId($c){
		$this->collId = $c;
	}
	
	protected function cleanString($inStr){
		$retStr = trim($inStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>