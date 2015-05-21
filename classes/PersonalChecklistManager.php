<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once('ProfileManager.php');

class PersonalChecklistManager{

	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getManagementLists($uid){
		$returnArr = Array();
		//Get project and checklist IDs from userpermissions
		$clStr = '';
		$projStr = '';
		$sql = 'SELECT role,tablepk FROM userroles '.
			'WHERE (uid = '.$uid.') AND (role = "ClAdmin" OR role = "ProjAdmin") ';
		//$sql = 'SELECT pname FROM userpermissions '.
		//	'WHERE (uid = '.$uid.') AND (pname LIKE "ClAdmin-%" OR pname LIKE "ProjAdmin-%") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->role == 'ClAdmin') $clStr .= ','.$r->tablepk;
			if($r->role == 'ProjAdmin') $projStr .= ','.$r->tablepk;
			//$pArr = explode('-',$r->pname);
			//if(count($pArr) == 2){
			//	if($pArr[0] == 'ClAdmin') $clStr .= ','.$pArr[1];
			//	if($pArr[0] == 'ProjAdmin') $projStr .= ','.$pArr[1];
			//}
		}
		if($clStr){
			//Get checklists
			$sql = 'SELECT clid, name FROM fmchecklists '.
				'WHERE (clid IN('.substr($clStr,1).')) '.
				'ORDER BY name';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['cl'][$row->clid] = $row->name;
			}
			$rs->close();
		}
		if($projStr){
			//Get projects
			$sql = 'SELECT pid, projname '.
				'FROM fmprojects '.
				'WHERE (pid IN('.substr($projStr,1).')) '.
				'ORDER BY projname';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['proj'][$row->pid] = $row->projname;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function createChecklist($newClArr){
		$sqlInsert = "";
		$sqlValues = "";
		foreach($newClArr as $k => $v){
			$sqlInsert .= ','.$k;
			if($v){
				$sqlValues .= ',"'.$this->cleanInStr($v).'"';
			}
			else{
				$sqlValues .= ',NULL';
			}
		}
		$sql = "INSERT INTO fmchecklists (".substr($sqlInsert,1).") VALUES (".substr($sqlValues,1).")";
		//echo $sql; exit;
		
		$newClId = 0;
		if($this->conn->query($sql)){
			$newClId = $this->conn->insert_id;
			//Set permissions to allow creater to be an editor
		 	$this->conn->query('INSERT INTO userroles (uid, role, tablename, tablepk) VALUES('.$GLOBALS["SYMB_UID"].',"ClAdmin","fmchecklists",'.$newClId.') ');
		 	//$this->conn->query("INSERT INTO userpermissions (uid, pname) VALUES(".$GLOBALS["symbUid"].",'ClAdmin-".$newClId."') ");
		 	$newPManager = new ProfileManager();
		 	$newPManager->setUserName($GLOBALS['USERNAME']);
		 	$newPManager->authenticate();
		}
		return $newClId;
	}

	public function echoParentSelect(){
		$sql = 'SELECT clid, name '.
			'FROM fmchecklists '.
			'WHERE access = "public" ';
		$clArr = array();
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin'])) $clArr = $GLOBALS['USER_RIGHTS']['ClAdmin'];
		if($clArr){
			$sql .= 'OR clid IN('.implode(',',$clArr).') ';
		}
		$sql .= 'ORDER BY name';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."'>".$row->name."</option>";
		}
		$rs->close();
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>