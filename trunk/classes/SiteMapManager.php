<?php
include_once($serverRoot.'/config/dbconnection.php');

class SiteMapManager{
	
	private $conn;
	private $isSuperAdmin = false;
	private $collList = Array();
	private $clList = Array();
	private $projList = Array();
	
	function __construct() {
		global $isAdmin;
		$this->isSuperAdmin = $isAdmin;
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->setPermissions();
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getCollectionList(){
		$returnArr = Array();
		if($this->isSuperAdmin || $this->collList){
			$sql = "SELECT c.collid, c.collectioncode, c.collectionname FROM omcollections c ";
			if(!$this->isSuperAdmin && $this->collList){
				$sql .= "WHERE c.collid IN(".implode(",",$this->collList).")";
			}
			//echo "<div>".$sql."</div>";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr[$row->collid] = $row->collectionname.($row->collectioncode?" (".$row->collectioncode.")":"");
			}
		}
		return $returnArr;
	}
	
	public function getChecklistList(){
		$returnArr = Array();
		if($this->isSuperAdmin || $this->clList){
			$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ";
			if(!$this->isSuperAdmin && $this->clList){
				$sql .= "WHERE cl.clid IN(".implode(",",$this->clList).")";
			}
			//echo "<div>".$sql."</div>";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr[$row->clid] = $row->name;
			}
		}
		return $returnArr;
	}
	
	public function getProjectList(){
		$returnArr = Array();
		if($this->isSuperAdmin || $this->projList){
			$sql = "SELECT p.pid, p.projname FROM fmprojects p ";
			if(!$this->isSuperAdmin && $this->projList){
				$sql .= "WHERE p.pid IN(".implode(",",$this->projList).")";
			}
			//echo "<div>".$sql."</div>";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr[$row->pid] = $row->projname;
			}
		}
		return $returnArr;
	}
	
	private function setPermissions(){
		global $userRights;
		if(array_key_exists("CollAdmin",$userRights)){
			$this->collList = $userRights["CollAdmin"];
		}
		if(array_key_exists("ClAdmin",$userRights)){
			$this->clList = $userRights["ClAdmin"];
		}
		if(array_key_exists("ProjAdmin",$userRights)){
			$this->projList = $userRights["ProjAdmin"];
		}
	}
}
?>