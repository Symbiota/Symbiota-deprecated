<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class GamesManager {

	private $conn;
	private $clid;
	private $clName;

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setChecklist($clValue){
		if(!$clValue) return;
		$sql = "SELECT c.clid, c.name ".
			"FROM fmchecklists c ";
		if(is_numeric($clValue)){
			$sql .= 'WHERE clid = '.$clValue;
		}
		else{
			$sql .= 'WHERE clname = "'.$clValue.'"';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->clid = $row->clid;
		}
		$rs->close();
	}
	
	public function getClid(){
		return $this->clid;
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	public function getChecklistArr($projId = 0){
		$retArr = Array();
		$sql = 'SELECT DISTINCT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink plink ON c.clid = plink.clid ';
		if($projId){
			$sql .= 'WHERE c.type = "static" AND plink.pid = '.$projId.' ';
		}
		else{
			$sql .= 'INNER JOIN fmprojects p ON plink.pid = p.pid WHERE c.type = "static" AND p.ispublic = 1 ';
		}
		$sql .= 'ORDER BY c.name';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid] = $row->name;
		}
		$rs->close();
		return $retArr;
	}
}
?>