<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class PersonalSpecimenManager {

	private $conn;
	private $obsProjArr = array();

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	private function setObsProjArr(){
		$sql = '';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->obsProjArr[$r->collid] = $r->collectionname;
			}
			$rs->close();
		}
	}

	public function getObsProjArr(){
		return $obsProjArr;
	}
}
?> 