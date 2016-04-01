<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceMaintenance.php');

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
		if(is_numeric($tid)){
	 		$sql = 'UPDATE taxa t SET t.SecurityStatus = 1 WHERE (t.tid = '.$tid.')';
	 		//echo $sql;
			$this->con->query($sql);
			//Update specimen records
			$occurMain = new OccurrenceMaintenance();
			$occurMain->protectGloballyRareSpecies();
		}
	}

	public function deleteSpecies($tid){
		if(is_numeric($tid)){
			$sql = 'UPDATE taxa t SET t.SecurityStatus = 0 WHERE (t.tid = '.$tid.')';
	 		//echo $sql;
			$this->con->query($sql);
			//Update specimen records
			$sql2 = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxa t ON ts2.tid = t.tid '.
				'SET o.LocalitySecurity = 0 '.
				'WHERE (t.tid = '.$tid.')';
			//echo $sql2; exit;
			$this->con->query($sql2);
			$occurMain = new OccurrenceMaintenance();
			$occurMain->protectGloballyRareSpecies();
		}
	}
}
?>