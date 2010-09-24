<?php
include_once($serverRoot.'/config/dbconnection.php');
 
 class RareSpeciesManager {
    
 	private $con;
    
    function __construct(){
		$this->con = MySQLiConnectionFactory::getCon("write");
    }
    
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
    
	public function getRareSpeciesList(){
 		$returnArr = Array();
		$sql = "SELECT t.tid, ts.Family, t.SciName, t.Author ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid ".
			"WHERE ((t.SecurityStatus = 1) AND (ts.taxauthid = 1)) ".
			"ORDER BY ts.Family, t.SciName";
		//echo $sql;
 		$result = $this->con->query($sql);
		if($result) {
			while($row = $result->fetch_object()){
				$returnArr[$row->Family][$row->tid] = "<i>".$row->SciName."</i>&nbsp;&nbsp;".$row->Author;
			}
		}
		$result->close();
		return $returnArr;
 	}
 	
 	public function addSpecies($tid){
 		$sql = "UPDATE taxa t SET t.SecurityStatus = 1 WHERE t.tid = ".$tid;
 		//echo $sql;
		$this->con->query($sql);
		//Update specimen records
		$sql2 = "UPDATE omoccurrences o SET o.LocalitySecurity = 1 WHERE o.tidinterpreted = ".$tid;
		$this->con->query($sql2);
	}
 }
?>