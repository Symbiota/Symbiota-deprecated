<?php
/*
 * Created on 26 Feb 2009
 * By E.E. Gilbert
*/
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
		$sql = 'SELECT pname FROM userpermissions WHERE (uid = '.$uid.') AND (pname LIKE "ClAdmin-%" OR pname LIKE "ProjAdmin-%")';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$pArr = explode('-',$r->pname);
			if(count($pArr) == 2){
				if($pArr[0] == 'ClAdmin') $clStr .= ','.$pArr[1];
				if($pArr[0] == 'ProjAdmin') $projStr .= ','.$pArr[1];
			}
		}
		if($clStr){
			//Get checklists
			$sql = "SELECT clid, name FROM fmchecklists WHERE (clid IN(".substr($clStr,1).'))';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['cl'][$row->clid] = $row->name;
			}
			$rs->close();
		}
		if($projStr){
			//Get projects
			$sql = "SELECT pid, projname FROM fmprojects WHERE (pid IN(".substr($projStr,1).'))';
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
				$sqlValues .= ',"'.$this->cleanStr($v).'"';
			}
			else{
				$sqlValues .= ',NULL';
			}
		}
		$sql = "INSERT INTO fmchecklists (".substr($sqlInsert,1).") VALUES (".substr($sqlValues,1).")";
		//echo $sql;
		$newClId = 0;
		if($this->conn->query($sql)){
			$newClId = $this->conn->insert_id;
			//Set permissions to allow creater to be an editor
		 	$this->conn->query("INSERT INTO userpermissions (uid, pname) VALUES(".$GLOBALS["symbUid"].",'ClAdmin-".$newClId."') ");
		 	$newPManager = new ProfileManager();
		 	$newPManager->authenticate($GLOBALS["paramsArr"]["un"]);
		}
		return $newClId;
	}

	public function deleteChecklist($clidDel){
		$status = '';
		$sql = "DELETE FROM fmchklsttaxalink WHERE (clid = ".$clidDel.')';
		$this->conn->query($sql);
		$sql = "DELETE FROM fmchecklists WHERE (clid = ".$clidDel.')';
		if($this->conn->query($sql)){
			$sql = 'DELETE FROM userpermissions WHERE (pname = "ClAdmin-'.$clidDel.'")';
			$this->conn->query($sql);
		}
		else{
			$status = 'Checklist Deletion falsed. Please contact data administrator.';
		}
		return $status;
	}
	
	public function echoParentSelect(){
		$sql = "SELECT c.clid, c.name FROM fmchecklists c ORDER BY c.name";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."'>".$row->name."</option>";
		}
		$rs->close();
	}

	private function cleanStr($inStr){
		$outStr = trim($inStr);
		$outStr = str_replace('"',"''",$outStr);
		return $outStr;
	}
}
?>