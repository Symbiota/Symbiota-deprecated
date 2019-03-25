<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');

class OmProtectedSpecies extends OccurrenceMaintenance {

 	private $taxaArr = array();

 	function __construct($conType = 'readonly'){
		parent::__construct(null,$conType);
    }

 	function __destruct(){
 		parent::__destruct();
	}

	public function getProtectedSpeciesList(){
 		$returnArr = Array();
 		//1 = protect locality details; 2 = protect by fussing taxonomy; 3 = protect locality details and taxonomy; 4 = hide occurrence completely
		$sql = 'SELECT DISTINCT  t.tid, ts.Family, t.SciName, t.Author, t.SecurityStatus FROM taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid ';
		if($this->taxaArr) $sql .= 'INNER JOIN taxaenumtree e ON t.tid = e.tid ';
		$sql .= 'WHERE (ts.taxauthid = 1) AND (t.SecurityStatus > 0) ';
		if($this->taxaArr) $sql .= 'AND e.parenttid IN('.implode(',', $this->taxaArr).') ';
		$sql .= 'ORDER BY ts.Family, t.SciName';
		//echo $sql;
		$returnArr['stats']['L'] = 0;
		$returnArr['stats']['T'] = 0;
		$returnArr['stats']['D'] = 0;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->Family][$row->tid]['sciname'] = $row->SciName;
			$returnArr[$row->Family][$row->tid]['author'] = $row->Author;
			$returnArr[$row->Family][$row->tid]['status'] = $row->SecurityStatus;
			if($row->SecurityStatus == 1) $returnArr['stats']['L']++;
			elseif($row->SecurityStatus == 2) $returnArr['stats']['T']++;
			elseif($row->SecurityStatus == 3) $returnArr['stats']['D']++;
		}
		$rs->free();
		foreach($returnArr['stats'] as $k => $v){
			if(!$v) unset($returnArr['stats'][$k]);
		}
		return $returnArr;
	}

	public function addSpecies($tid, $securityCode){
		$protectCnt = 0;
		if(is_numeric($tid) && is_numeric($securityCode)){
	 		$sql = 'UPDATE taxa t SET t.SecurityStatus = '.$securityCode.' WHERE (t.tid = '.$tid.')';
	 		//echo $sql;
			$this->conn->query($sql);
			//Update specimen records
			$protectCnt = $this->protectGlobalSpecies();
		}
		return $protectCnt;
	}

	public function deleteSpecies($tid){
		$protectCnt = 0;
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
			$protectCnt = $this->protectGlobalSpecies();
		}
		return $protectCnt;
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
		while($r = $rs->fetch_object()){
			$retArr[$r->clid]['name'] = $r->name;
			$retArr[$r->clid]['locality'] = $r->locality;
			$retArr[$r->clid]['authors'] = $r->authors;
			$retArr[$r->clid]['access'] = $r->access;
		}
		$rs->free();
		return $retArr;
	}

	public function setTaxonFilter($searchTaxon){
		$sql = 'SELECT ts.tidaccepted FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname = "'.$searchTaxon.'" AND ts.taxauthid = 1';
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

	public function getSpecimenCnt(){
		$retCnt;
		//Get number of specimens protected
		$sql = 'SELECT COUNT(*) AS cnt FROM omoccurrences WHERE (LocalitySecurity > 0)';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}
}
?>