<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class InventoryDynSqlManager {

	private $conn;
	private $clid;
	private $clName;
	
	function __construct($id) {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		if(is_numeric($id)){
			$this->clid = $id;
		}
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function getDynamicSql(){
		$sqlStr = "";
		if($this->clid){
			$sql = "SELECT c.dynamicsql FROM fmchecklists c WHERE (c.clid = ".$this->clid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$sqlStr = $row->dynamicsql;
			}
			$rs->close();
		}
		return $sqlStr;
	}
	
	public function testSql($strFrag){
		$sql = "SELECT * FROM omoccurrences o WHERE ".$strFrag;
		if($this->conn->query($sql)){
			return true;
		}
		return false;
	}
	
	public function saveSql($sqlFrag){
		$sql = "UPDATE fmchecklists c SET c.dynamicsql = \"".trim($sqlFrag)."\" WHERE (c.clid = ".$this->clid.')';
		$this->conn->query($sql);
	}

	public function getClName(){
		return $this->clName;
	}
}
?> 