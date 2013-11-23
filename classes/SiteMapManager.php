<?php
include_once($serverRoot.'/config/dbconnection.php');

class SiteMapManager{
	
	private $conn;
	private $collArr = array();
	private $obsArr = array();
	private $genObsArr = array();
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollectionList(){
		global $userRights, $isAdmin;
		$adminArr = array();
		$editorArr = array();
		$sql = 'SELECT c.collid, CONCAT_WS(":",c.institutioncode, c.collectioncode) AS ccode, c.collectionname, c.colltype '.
			'FROM omcollections c ';
		if(!$isAdmin){
			if(array_key_exists("CollAdmin",$userRights)){
				$adminArr = $userRights['CollAdmin'];
			}
			if(array_key_exists("CollEditor",$userRights)){
				$editorArr = $userRights['CollEditor'];
			}
			if($adminArr || $editorArr){
				$sql .= 'WHERE (c.collid IN('.implode(',',array_merge($adminArr,$editorArr)).')) ';
			}
			else{
				$sql = '';
			}
		}
		if($sql){
			$sql .= "ORDER BY c.collectionname";
			//echo "<div>".$sql."</div>";
			$rs = $this->conn->query($sql);
			if($rs){
				while($row = $rs->fetch_object()){
					$name = $row->collectionname.($row->ccode?" (".$row->ccode.")":"");
					$isCollAdmin = ($isAdmin||in_array($row->collid,$adminArr)?1:0);
					if($row->colltype == 'Observations'){
						$this->obsArr[$row->collid]['name'] = $name;
						$this->obsArr[$row->collid]['isadmin'] = $isCollAdmin; 
					}
					elseif($row->colltype == 'General Observations'){
						$this->genObsArr[$row->collid]['name'] = $name;
						$this->genObsArr[$row->collid]['isadmin'] = $isCollAdmin; 
					}
					else{
						$this->collArr[$row->collid]['name'] = $name;
						$this->collArr[$row->collid]['isadmin'] = $isCollAdmin; 
					}
				}
				$rs->close();
			}
		}
	}
	
	public function getCollArr(){
		return $this->collArr;
	}

	public function getObsArr(){
		return $this->obsArr;
	}

	public function getGenObsArr(){
		return $this->genObsArr;
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
	
	/**
	 * 
	 * Determine the version number of the underlying schema.
	 * 
	 * @return string representation of the most recently applied schema version
	 */
	public function getSchemaVersion() {
		$result = "No Schema Version Found"; 
		$sql = "select versionnumber, dateapplied from schemaversion order by dateapplied desc limit 1 ";
		$statement = $this->conn->prepare($sql);
		$statement->execute();
		$statement->bind_result($version,$dateapplied);
		while ($statement->fetch())  { 
			$result = $version;
		}
		$statement->close();
		return $result;		
	}
	
}
?>