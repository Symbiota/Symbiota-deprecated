<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
 
class InventoryProjectManager {

	private $conn;
	private $pid;
	private $googleUrl;
	private $researchCoord = Array();
	private $isPublic = 1;
	private $errorStr;

	public function __construct($connType = 'readonly'){
		$this->conn = MySQLiConnectionFactory::getCon($connType);
		$this->googleUrl = "http://maps.google.com/maps/api/staticmap?size=120x150&maptype=terrain";
		if(array_key_exists('GOOGLE_MAP_KEY',$GLOBALS) && $GLOBALS['GOOGLE_MAP_KEY']) $this->googleUrl .= '&key='.$GLOBALS['GOOGLE_MAP_KEY'];
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getProjectList(){
		$returnArr = Array();
		$sql = 'SELECT pid, projname, managers, fulldescription '.
			'FROM fmprojects '.
			'WHERE ispublic = 1 '.
			'ORDER BY projname';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$projId = $row->pid;
			$returnArr[$projId]["projname"] = $row->projname;
			$returnArr[$projId]["managers"] = $row->managers;
			$returnArr[$projId]["descr"] = $row->fulldescription;
		}
		$rs->free();
		return $returnArr;
	}

	public function getProjectData(){
		$returnArr = Array();
		if($this->pid){
			$sql = 'SELECT pid, projname, managers, fulldescription, notes, '.
				'occurrencesearch, ispublic, sortsequence '.
				'FROM fmprojects '.
				'WHERE (pid = '.$this->pid.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->pid = $row->pid;
				$returnArr['projname'] = $row->projname;
				$returnArr['managers'] = $row->managers;
				$returnArr['fulldescription'] = $row->fulldescription;
				$returnArr['notes'] = $row->notes;
				$returnArr['occurrencesearch'] = $row->occurrencesearch;
				$returnArr['ispublic'] = $row->ispublic;
				$returnArr['sortsequence'] = $row->sortsequence;
				if($row->ispublic == 0){
					$this->isPublic = 0;
				}
			}
			$rs->free();
		}
		return $returnArr;
	}

	public function submitProjEdits($projArr){
		$fieldArr = array('projname', 'displayname', 'managers', 'fulldescription', 'notes', 'ispublic', 'parentpid', 'sortsequence');
		$sql = "";
		foreach($projArr as $field => $value){
			if(in_array($field,$fieldArr)){
				$sql .= ','.$field.' = "'.$this->cleanInStr($value).'"';
			}
		}
		$sql = 'UPDATE fmprojects SET '.substr($sql,1).' WHERE (pid = '.$this->pid.')';
		//echo $sql; exit;
		$this->conn->query($sql);
	}

	public function deleteProject($projID){
		$status = true;
		if($projID && is_numeric($projID)){
			$sql = 'DELETE FROM fmprojects WHERE pid = '.$projID;
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				$status = false;
				$this->errorStr = 'ERROR deleting inventory project: '.$this->conn->error;
			}
		}
		return $status;
	}

	public function addNewProject($projArr){
		$sql = 'INSERT INTO fmprojects(projname,managers,fulldescription,notes,ispublic) '.
			'VALUES("'.$this->cleanInStr($projArr['projname']).'",'.
			($projArr['managers']?'"'.$this->cleanInStr($projArr['managers']).'"':'NULL').','.
			($projArr['fulldescription']?'"'.$this->cleanInStr($projArr['fulldescription']).'"':'NULL').','.
			($projArr['notes']?'"'.$this->cleanInStr($projArr['notes']).'"':'NULL').','.
			(is_numeric($projArr['ispublic'])?$projArr['ispublic']:'0').')';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->pid = $this->conn->insert_id;
		}
		else{
			$this->errorStr = 'ERROR creating new project: '.$this->conn->error;
		}
		return $this->pid;
	}
	
	public function getResearchChecklists(){
		global $USER_RIGHTS;
		$retArr = Array();
		if($this->pid){
			$sql = 'SELECT c.clid, c.name, c.latcentroid, c.longcentroid, c.access '.
				'FROM fmchklstprojlink cpl INNER JOIN fmchecklists c ON cpl.clid = c.clid '.
				'WHERE (cpl.pid = '.$this->pid.') AND ((c.access != "private")';
			if(array_key_exists('ClAdmin',$USER_RIGHTS)){
				$sql .= ' OR (c.clid IN ('.implode(',',$USER_RIGHTS['ClAdmin']).'))) ';
			}
			else{
				$sql .= ') ';
			}
			$sql .= "ORDER BY c.SortSequence, c.name";
			//echo $sql;
			$rs = $this->conn->query($sql);
			$cnt = 0;
			while($row = $rs->fetch_object()){
				$retArr[$row->clid] = $row->name.($row->access == 'private'?' <span title="Viewable only to editors">(private)</span>':'');
				if($cnt < 50 && $row->latcentroid){
					$this->researchCoord[] = $row->latcentroid.','.$row->longcentroid;
				}
				$cnt++;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function getGoogleStaticMap(){
		$googleUrlLocal = $this->googleUrl;
		//$googleUrlLocal .= "&zoom=6";
		$coordStr = implode('%7C',$this->researchCoord);
		if(!$coordStr) return ""; 
		$googleUrlLocal .= "&markers=size:tiny%7C".$coordStr;
		return $googleUrlLocal;
	}
	
	//User management functions
	public function getManagers(){
		$retArr = array();
		if($this->pid){
			$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as fullname, l.username '.
				'FROM userroles r INNER JOIN users u ON r.uid = u.uid '.
				'INNER JOIN userlogin l ON u.uid = l.uid '.
				'WHERE r.role = "ProjAdmin" AND r.tablepk = '.$this->pid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->fullname.' ('.$r->username.')';
			}
			$rs->free();
			asort($retArr);
		}
		return $retArr;
	} 
	
	public function addManager($uid){
		$status = false;
		if(is_numeric($uid) && $this->pid){
			$sql = 'INSERT INTO userroles(role,tablename,tablepk,uid) '.
				'VALUES("ProjAdmin","fmprojects",'.$this->pid.','.$uid.') ';
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR adding manager: '.$this->conn->error;
			}
		}
		return $status;
	} 
	
	public function deleteManager($uid){
		$status = true;
		if(is_numeric($uid) && $this->pid){
			$sql = 'DELETE FROM userroles WHERE (role = "ProjAdmin") AND (tablepk = '.$this->pid.') AND (uid = '.$uid.') ';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR removing manager: '.$this->conn->error;
			}
		}
		return $status;
	}

	public function getPotentialManagerArr(){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as fullname, l.username '.
			'FROM users u INNER JOIN userlogin l ON u.uid = l.uid '.
			'ORDER BY u.lastname, u.firstname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->fullname.' ('.$r->username.')';
		}
		$rs->free();
		//asort($retArr);
		return $retArr;
	}
	
	//Checklist management functions
	public function addChecklist($clid){
		if(!is_numeric($clid)) return false; 
		$sql = 'INSERT INTO fmchklstprojlink(pid,clid) VALUES('.$this->pid.','.$clid.') ';
		if(!$this->conn->query($sql)){
			return 'ERROR adding checklist to project: '.$this->conn->error;
		}
	}

	public function deleteChecklist($clid){
		if(!is_numeric($clid)) return false; 
		$sql = 'DELETE FROM fmchklstprojlink WHERE (pid = '.$this->pid.') AND (clid = '.$clid.')';
		if($this->conn->query($sql)){
			return 'ERROR deleting checklist from project';
		}
	}

	//Misc functions
	public function getClAddArr(){
		global $USER_RIGHTS;
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name, c.access '.
			'FROM fmchecklists c LEFT JOIN (SELECT clid FROM fmchklstprojlink WHERE (pid = '.$this->pid.')) pl ON c.clid = pl.clid '.
			'WHERE (pl.clid IS NULL) AND (c.access = "public" ';
		if(array_key_exists('ClAdmin',$USER_RIGHTS)){
			$sql .= ' OR (c.clid IN ('.implode(',',$USER_RIGHTS['ClAdmin']).'))) ';
		}
		else{
			$sql .= ') ';
		}
		$sql .= 'ORDER BY name';

		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name.($row->access == 'private'?' (private)':'');
		}
		$rs->free();
		return $returnArr;
	}

	public function getClDeleteArr(){
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink pl ON c.clid = pl.clid '.
			'WHERE (pl.pid = '.$this->pid.') '.
			'ORDER BY name';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		$rs->free();
		return $returnArr;
	}

	//Setter and getters
	public function getPid(){
		return $this->pid;
	}
	
	public function setPid($pid){
		if(is_numeric($pid)) $this->pid = $pid;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		//$newStr = str_replace(array(chr(10),chr(11),chr(13)),' ',$newStr);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>