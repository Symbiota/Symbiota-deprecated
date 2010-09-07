<?php
include_once($serverRoot.'/config/dbconnection.php');

class SiteMapManager{
	
	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getCollectionList($collArr = ""){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.collectioncode, c.collectionname FROM omcollections c ";
		if($collArr){
			$sql .= "WHERE c.collid IN(".implode(",",$collArr).")";
		}
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid] = $row->collectionname.($row->collectioncode?" (".$row->collectioncode.")":"");
		}
		return $returnArr;
	}
	
	public function getChecklistList($clArr = ""){
		$returnArr = Array();
		$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ";
		if($clArr){
			$sql .= "WHERE cl.clid IN(".implode(",",$this->clList).")";
		}
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		return $returnArr;
	}
	
	public function getProjectList($projArr = ""){
		$returnArr = Array();
		$sql = "SELECT p.pid, p.projname, p.managers FROM fmprojects p ";
		if($projArr){
			$sql .= "WHERE p.pid IN(".implode(",",$projArr).")";
		}
		$sql .= "ORDER BY p.projname";
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->pid]["name"] = $row->projname;
			$returnArr[$row->pid]["managers"] = $row->managers;
		}
		return $returnArr;
	}
}
?>