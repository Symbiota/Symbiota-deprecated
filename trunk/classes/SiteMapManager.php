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
			$sql .= "WHERE (c.collid IN(".implode(",",$collArr).")) ";
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
		$sql = 'SELECT cl.clid, cl.name FROM fmchecklists cl WHERE cl.access LIKE "public%"';
		if(!$isAdmin && $clArr){
			$sql .= 'AND (cl.clid IN('.implode(',',$clArr).')) ';
		}
		$sql .= 'ORDER BY cl.name';
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
		$sql = 'SELECT p.pid, p.projname, p.managers FROM fmprojects p '.
			'WHERE p.ispublic = 1 ';
		if($projArr){
			$sql .= 'AND (p.pid IN('.implode(',',$projArr).')) ';
		}
		$sql .= 'ORDER BY p.projname';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		if($rs){
			while($row = $rs->fetch_object()){
				$returnArr[$row->pid]['name'] = $row->projname;
				$returnArr[$row->pid]['managers'] = $row->managers;
			}
			$rs->close();
		}
		return $returnArr;
	}
	
	public function getTaxaWithoutImages($clid, $fieldImagesOnly=false){
		$retArr = Array();
		if($clid){
			$sql = 'SELECT DISTINCT t.tid, t.sciname '.
				'FROM taxa t INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
				'LEFT JOIN (SELECT ts2.tid '.
				'FROM images ii INNER JOIN taxstatus ts1 ON ii.tid = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
				($fieldImagesOnly?'AND imagetype NOT LIKE "%specimen%" ':'').
				') i ON t.tid = i.tid '.
				'WHERE (ctl.clid = '.$clid.') AND i.tid IS NULL '.
				'ORDER BY t.sciname';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			if($rs){
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->close();
			}
		}
		return $retArr;
	}
}
?>