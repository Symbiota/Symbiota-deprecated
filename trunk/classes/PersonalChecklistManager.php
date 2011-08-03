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
	
	public function getChecklists($uid){
		$returnArr = Array();
		$sql = "SELECT c.clid, c.name FROM fmchecklists c WHERE (uid = ".$uid.')';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		return $returnArr;
	}
	
	public function createChecklist($newClArr){
		$sqlInsert = "";
		$sqlValues = "";
		foreach($newClArr as $k => $v){
			$sqlInsert .= ",".$k;
			if($v){
				$sqlValues .= ",\"".$v."\"";
			}
			else{
				$sqlValues .= ",NULL";
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
		$sql = "DELETE FROM fmchklsttaxalink WHERE (clid = ".$clidDel.')';
		$this->conn->query($sql);
		$sql = "DELETE FROM fmchecklists WHERE (clid = ".$clidDel.')';
		//echo $sql;
		return $this->conn->query($sql);
	}
	
	public function echoParentSelect(){
		$sql = "SELECT c.clid, c.name FROM fmchecklists c ORDER BY c.name";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."'>".$row->name."</option>";
		}
		$rs->close();
	}
}
?>