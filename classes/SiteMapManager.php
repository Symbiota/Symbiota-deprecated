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

	public function getCollectionList($userRights){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.collectioncode, c.collectionname FROM omcollections c ";
		$collArr = Array();
		if(array_key_exists("CollAdmin",$userRights)){
			$collArr = $userRights['CollAdmin'];
		}
		if(array_key_exists("CollEditor",$userRights)){
			$collArr = array_merge($collArr,$userRights['CollEditor']);
		}
		if($collArr){
			$sql .= "WHERE c.collid IN(".implode(",",$collArr).") ";
		}
		$sql .= "ORDER BY c.collectionname";
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($rs){
			while($row = $rs->fetch_object()){
				$returnArr[$row->collid] = $row->collectionname.($row->collectioncode?" (".$row->collectioncode.")":"");
			}
			$rs->close();
		}
		return $returnArr;
	}
	
	public function getChecklistList($isAdmin, $clArr){
		$returnArr = Array();
		$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ".
			"WHERE cl.access = 'public' ";
		if(!$isAdmin && $clArr){
			$sql .= "AND cl.clid IN(".implode(",",$clArr).") ";
		}
		$sql .= "ORDER BY cl.name";
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($rs){
			while($row = $rs->fetch_object()){
				$returnArr[$row->clid] = $row->name;
			}
			$rs->close();
		}
		return $returnArr;
	}
	
	public function getProjectList($projArr = ""){
		$returnArr = Array();
		$sql = "SELECT p.pid, p.projname, p.managers FROM fmprojects p ";
		if($projArr){
			$sql .= "WHERE p.pid IN(".implode(",",$projArr).") ";
		}
		$sql .= "ORDER BY p.projname";
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($rs){
			while($row = $rs->fetch_object()){
				$returnArr[$row->pid]["name"] = $row->projname;
				$returnArr[$row->pid]["managers"] = $row->managers;
			}
			$rs->close();
		}
		return $returnArr;
	}
}
?>