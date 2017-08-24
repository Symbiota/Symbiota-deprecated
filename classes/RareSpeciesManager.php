<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');

class RareSpeciesManager {
    
 	private $conn;
 	private $taxaArr = array();
    
    function __construct($collType = 'readonly'){
		$this->conn = MySQLiConnectionFactory::getCon($collType);
    }
    
 	function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
    
	public function getRareSpeciesList(){
 		$returnArr = Array();
		$sql = 'SELECT t.tid, ts.Family, t.SciName, t.Author '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid '.
			'WHERE ((t.SecurityStatus = 1) AND (ts.taxauthid = 1)) ';
		if($this->taxaArr){
			$sql .= 'AND t.tid IN('.implode(',', $this->taxaArr).') ';
		}
		$sql .= 'ORDER BY ts.Family, t.SciName';
		//echo $sql;
 		$result = $this->conn->query($sql);
		if($result) {
			while($row = $result->fetch_object()){
				$returnArr[$row->Family][$row->tid] = "<i>".$row->SciName."</i>&nbsp;&nbsp;".$row->Author;
			}
		}
		$result->free();
		return $returnArr;
	}

	public function addSpecies($tid){
		if(is_numeric($tid)){
	 		$sql = 'UPDATE taxa t SET t.SecurityStatus = 1 WHERE (t.tid = '.$tid.')';
	 		//echo $sql;
			$this->conn->query($sql);
			//Update specimen records
			$occurMain = new OccurrenceMaintenance($this->conn);
			$occurMain->protectGloballyRareSpecies();
		}
	}

	public function deleteSpecies($tid){
		if(is_numeric($tid)){
			$sql = 'UPDATE taxa t SET t.SecurityStatus = 0 WHERE (t.tid = '.$tid.')';
	 		//echo $sql;
			$this->conn->query($sql);
			//Update specimen records
			$sql2 = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxa t ON ts2.tid = t.tid '.
				'SET o.LocalitySecurity = 0 '.
				'WHERE (t.tid = '.$tid.') AND (o.localitySecurityReason IS NULL) ';
			//echo $sql2; exit;
			$this->conn->query($sql2);
			$occurMain = new OccurrenceMaintenance($this->conn);
			$occurMain->protectGloballyRareSpecies();
		}
	}

	public function getStateList(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT c.clid, c.name, c.locality, c.authors, c.access '.
			'FROM fmchecklists c INNER JOIN fmchklsttaxalink l ON c.clid = l.clid '.
			'WHERE c.type = "rarespp" ';
		if($this->taxaArr){
			$sql .= 'AND l.tid IN('.implode(',', $this->taxaArr).') ';
		}
		$sql .= 'ORDER BY c.locality';
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				$retArr[$r->clid]['name'] = $r->name;
				$retArr[$r->clid]['locality'] = $r->locality;
				$retArr[$r->clid]['authors'] = $r->authors;
				$retArr[$r->clid]['access'] = $r->access;
			}
		}
		$rs->free();
		return $retArr;
	}
	
	public function setSearchTaxon($searchTaxon){
		$sql = 'SELECT ts.tidaccepted '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE t.sciname = "'.$searchTaxon.'" AND ts.taxauthid = 1';
		$rs = $this->conn->query($sql);
		if($rs) {
			while($r = $rs->fetch_object()){
				$this->taxaArr[] = $r->tidaccepted;
			}
		}
		$rs->free();

		//Get synonyms
		$sql = 'SELECT tid  FROM taxstatus  WHERE tidaccepted IN('.implode(',',$this->taxaArr).")";
		$rs = $this->conn->query($sql);
		if($rs) {
			while($r = $rs->fetch_object()){
				$this->taxaArr[] = $r->tid;
			}
		}
		$rs->free();
	}
}
?>