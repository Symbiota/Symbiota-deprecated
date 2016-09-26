<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
 
class InventoryProjectManager {

	private $conn;
	private $pid;
	private $googleUrl;
	private $researchCoord = Array();
	private $isPublic = 1;
	private $errorStr;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
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
			$returnArr[$projId]["projname"] = $this->cleanOutStr($row->projname);
			$returnArr[$projId]["managers"] = $this->cleanOutStr($row->managers);
			$returnArr[$projId]["descr"] = $this->cleanOutStr($row->fulldescription);
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
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->pid = $row->pid;
				$returnArr['projname'] = $this->cleanOutStr($row->projname);
				$returnArr['managers'] = $this->cleanOutStr($row->managers);
				$returnArr['fulldescription'] = $this->cleanOutStr($row->fulldescription);
				$returnArr['notes'] = $this->cleanOutStr($row->notes);
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
		$conn = MySQLiConnectionFactory::getCon("write");
		$fieldArr = array('projname', 'displayname', 'managers', 'fulldescription', 'notes', 'ispublic', 'parentpid', 'SortSequence');
		$sql = "";
		foreach($projArr as $field => $value){
			if(in_array($field,$fieldArr)){
				$v = $this->cleanInStr($value);
				$sql .= ','.$field.' = "'.$v.'"';
			}
		}
		$sql = 'UPDATE fmprojects SET '.substr($sql,1).' WHERE (pid = '.$this->pid.')';
		//echo $sql; exit;
		$conn->query($sql);
		$conn->close();
	}
	
	public function addNewProject($projArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO fmprojects(projname,managers,fulldescription,notes,ispublic,sortsequence) '.
			'VALUES("'.$this->cleanInStr($projArr['projname']).'","'.$this->cleanInStr($projArr['managers']).'","'.
			$this->cleanInStr($projArr['fulldescription']).'","'.
			$this->cleanInStr($projArr['notes']).'",'.$projArr['ispublic'].','.
			($projArr['sortsequence']?$projArr['sortsequence']:'50').')';
		//echo $sql;
		if($conn->query($sql)){
			$this->pid = $conn->insert_id;
			
		}
		$conn->close();
		return $this->pid;
	}
	
	public function getResearchChecklists(){
		global $userRights;
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name, c.latcentroid, c.longcentroid, c.access '.
			'FROM fmchklstprojlink cpl INNER JOIN fmchecklists c ON cpl.clid = c.clid '.
			'WHERE (cpl.pid = '.$this->pid.') AND ((c.access != "private")';
		if(array_key_exists('ClAdmin',$userRights)){
			$sql .= ' OR (c.clid IN ('.implode(',',$userRights['ClAdmin']).'))) ';
		}
		else{
			$sql .= ') ';
		}
		$sql .= "ORDER BY c.SortSequence, c.name";
		//echo $sql;
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $this->cleanOutStr($row->name).($row->access == 'private'?' <span title="Viewable only to editors">(private)</span>':'');
			if($cnt < 50 && $row->latcentroid){
				$this->researchCoord[] = $row->latcentroid.','.$row->longcentroid;
			}
			$cnt++;
		}
		$rs->free();
		return $returnArr;
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
		return $retArr;
	} 
	
	public function addManager($uid){
		$status = true;
		if(is_numeric($uid) && $this->pid){
			$conn = MySQLiConnectionFactory::getCon("write");
			$sql = 'INSERT INTO userroles(role,tablename,tablepk,uid) '.
				'VALUES("ProjAdmin","fmprojects",'.$this->pid.','.$uid.') ';
			if(!$conn->query($sql)){
				$this->errorStr = 'ERROR adding manager: '.$conn->error;
				$status = false;
			}
			if(!($conn === null)) $conn->close();
		}
		return $status;
	} 
	
	public function deleteManager($uid){
		$status = true;
		if(is_numeric($uid) && $this->pid){
			$conn = MySQLiConnectionFactory::getCon("write");
			$sql = 'DELETE FROM userroles '.
				'WHERE (role = "ProjAdmin") AND (tablepk = '.$this->pid.') AND (uid = '.$uid.') ';
			if(!$conn->query($sql)){
				$this->errorStr = 'ERROR removing manager: '.$conn->error;
				$status = false;
			}
			if(!($conn === null)) $conn->close();
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
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO fmchklstprojlink(pid,clid) VALUES('.$this->pid.','.$clid.') ';
		if($conn->query($sql)){
			return 'SUCCESS: Checklist has been added to project';
		}
		else{
			return 'FAILED: Unable to add checklist to project';
		}
		if(!($conn === null)) $conn->close();
	}

	public function deleteChecklist($clid){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'DELETE FROM fmchklstprojlink WHERE (pid = '.$this->pid.') AND (clid = '.$clid.')';
		if($conn->query($sql)){
			return 'SUCCESS: Checklist has been deleted from project';
		}
		else{
			return 'FAILED: Unable to checklist from project';
		}
		if(!($conn === null)) $conn->close();
	}

	//Misc functions
	public function getClAddArr(){
		global $userRights;
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name, c.access '.
			'FROM fmchecklists c LEFT JOIN (SELECT clid FROM fmchklstprojlink WHERE (pid = '.$this->pid.')) pl ON c.clid = pl.clid '.
			'WHERE (pl.clid IS NULL) AND (c.access = "public" ';
		if(array_key_exists('ClAdmin',$userRights)){
			$sql .= ' OR (c.clid IN ('.implode(',',$userRights['ClAdmin']).'))) ';
		}
		else{
			$sql .= ') ';
		}
		$sql .= 'ORDER BY name';

		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $this->cleanOutStr($row->name).($row->access == 'private'?' (private)':'');
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
			$returnArr[$row->clid] = $this->cleanOutStr($row->name);
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
	
	public function setProj($proj){
		if($proj){
			if(is_numeric($proj)){
				$this->pid = $proj;
			}
			else{
				$sql = "SELECT pid FROM fmprojects WHERE (projname = '".$proj."')";
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$this->pid = $row->pid;
				}
				$rs->free();
			}
		}
		return $this->pid;
	}
	
	public function getErrorStr(){
		return $this->errorStr;
	}

	//Misc functions
 	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		//$newStr = str_replace(array(chr(10),chr(11),chr(13)),' ',$newStr);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>