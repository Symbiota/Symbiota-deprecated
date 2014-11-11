<?php
include_once($serverRoot.'/config/dbconnection.php');

class KeyMassUpdate{
	
	private $conn;
	private $tidFilter;
	private $clidFilter;
	private $generaOnly;
	private $cid;
	private $adds = Array();
	private $removes = Array();
	private $childrenStr;
	private $tidUsed = Array();
	private $pid;
	private $projName;
	private $lang = "English";
  	private $username;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function getCharList(){
		$headingArray = Array();		//Heading => Array(CID => CharName)
		$sql = "SELECT DISTINCT ch.headingname, c.CID, c.CharName ".
			"FROM kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID ".
			"INNER JOIN kmcharheading ch ON c.hid = ch.hid ".
			"LEFT JOIN kmchardependance cd ON c.CID = cd.CID ".
			"WHERE ch.language = '".$this->lang."' AND (ctl.Relation = 'include') ".
			"AND (c.chartype='UM' OR c.chartype='OM') AND (c.defaultlang='".$this->lang."') ";
		if($this->tidFilter){
			$strFrag = implode(",",$this->getParents($this->tidFilter));
			$sql .= "AND (ctl.TID In ($strFrag)) ";
		}
		$sql .= "ORDER BY c.hid, c.SortSequence, c.CharName";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$headingArray[$row->headingname][$row->CID] = $row->CharName;
		}
		$result->free();
		return $headingArray;
	}
	
	private function getParents($t){
		//Returns a list of parent TIDs, including target 
 		$returnList = Array();
		$targetTaxon = $t;
		while($targetTaxon){
			$sql = "SELECT t.TID, ts.ParentTID FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE ts.taxauthid = 1 AND (t.TID = ".$targetTaxon.")";
			$result = $this->conn->query($sql);
		    if ($row = $result->fetch_object()){
				$targetTaxon = $row->ParentTID;
				$tid = $row->TID;
				if(in_array($tid, $returnList)) break;
				$returnList[] = $tid;
		    }
		    else{
		    	break;
		    }
			$result->free();
		}
		return $returnList;
	}

	public function getStates(){
		$stateArr = Array();
		$sql = 'SELECT kmcs.CharStateName, kmcs.CS FROM kmcs '.
			'WHERE (kmcs.CID = '.$this->cid.') '.
			'ORDER BY kmcs.SortSequence, (kmcs.CS + 1)';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$stateArr[$row->CS] = $row->CharStateName;
		}
		$rs->free();
		return $stateArr;
	}

	public function getTaxaList(){
		//Get all Taxa found in checklist 
		$retArr = Array();
		$tidArr = Array();
		$sqlBase = '';
		$sqlWhere = '';
		if($this->clidFilter){
			$sqlBase .= 'INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid ';
			$sqlWhere .= 'AND (ctl.CLID = '.$this->clidFilter.') ' ;
		}
		if($this->tidFilter){
			$sqlBase .= 'INNER JOIN taxaenumtree e ON ts.tid = e.tid ';
			$sqlWhere .= 'AND (e.taxauthid = 1) AND (e.parenttid = '.$this->tidFilter.') ';
		}
		//Get accepted taxa
		if(!$this->generaOnly){
			$sql = 'SELECT DISTINCT t.tid, ts.family, t.sciname, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.$sqlBase.
				'WHERE (ts.taxauthid = 1) '.$sqlWhere;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->rankid == 220){
					$tidArr[] = $r->tid;
					$retArr[$r->family][$r->sciname] = $r->tid;
				}
			}
			$rs->free();
		}
		
		//Get parents
		$sql = 'SELECT DISTINCT t.tid, ts.family, t.sciname, t.rankid '.
			'FROM taxa t INNER JOIN taxaenumtree et ON t.tid = et.parenttid '.
			'INNER JOIN taxstatus ts ON et.tid = ts.tidaccepted '.$sqlBase.
			'WHERE (ts.taxauthid = 1) AND (t.rankid IN(140,180,220)) '.$sqlWhere;
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->rankid == 220 || !$this->generaOnly){
				$tidArr[] = $r->tid;
				$family = $r->family;
				if($r->rankid == 140 && !$family) $family = $r->sciname;
				$retArr[$family][$r->sciname] = $r->tid;
			}
		}
		$rs->free();

		//Get character states
		if($tidArr){
			$sql = 'SELECT tid, cid, cs, inherited FROM kmdescr '.
				'WHERE (cid='.$this->cid.') AND (tid IN('.implode(',',$tidArr).'))';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$i = 0;
				if($r->inherited) $i = 1;
				$retArr['cs'][$r->tid][$r->cs] = $i;
			}
			$rs->free();
		}

		return $retArr;
	}

	public function setRemoves($removeArr){
 		foreach($removeArr as $v){
 			if(strlen($v) > 0){
				$t = explode("-",$v);
 				$this->removes[] = "((d.TID = ".$t[0].") AND (d.CID = ".$this->cid.") AND (d.CS = '".$t[1]."'))";
				$this->tidUsed[] = $t[0];
 			}
 		}
	}
	
	public function setAdds($addArr){
 		foreach($addArr as $v){
 			if(strlen($v) > 0){
 				$t = explode("-",$v);
				$tid = $t[0];
				$cs = $t[1];
				$this->tidUsed[] = $tid;
				$this->adds[] = "INSERT INTO kmdescr (TID, CID, CS, Source) VALUES (".$t[0].",".$this->cid.",'".$t[1]."','".$this->username."')";
 			}
 		}
	}
		
	public function processAttrs(){
		if($this->removes){
			//transfer deletes to the descrdeletions table
			$sqlTrans = "INSERT INTO kmdescrdeletions ( TID, CID, Modifier, CS, X, TXT, Inherited, Source, Seq, Notes, InitialTimeStamp, DeletedBy ) ".
			"SELECT d.TID, d.CID, d.Modifier, d.CS, d.X, d.TXT, d.Inherited, ".
			"d.Source, d.Seq, d.Notes, d.DateEntered, '".$this->username."' ".
			"FROM kmdescr d WHERE ".implode(" OR ",$this->removes);
			$this->conn->query($sqlTrans);
			
			//delete value from descr
			$sqlStr = "DELETE d.* FROM kmdescr d WHERE ".implode(" OR ",$this->removes);
			$this->conn->query($sqlStr);
		}
		
		foreach($this->adds as $v){
			$this->conn->query($v);
 		}
	}

	public function deleteInheritance(){
		//delete all inherited children traits for CIDs that will be modified
		$this->setChildrenList();
		$sqlDel = "DELETE FROM kmdescr ".
			"WHERE (TID IN(".$this->childrenStr.")) ".
			"AND (CID = ".$this->cid.") AND (Inherited Is Not Null AND Inherited <> '')";
		$this->conn->query($sqlDel);
	}
		
	public function resetInheritance(){
		//set inheritance for target only
		$sqlAdd1 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
			"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
			"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
			"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
			"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
			"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
			"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
			"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (t2.rankid > 140) AND (ts2.tid = ts2.tidaccepted) ".
			"AND (t2.tid IN(".implode(",",$this->tidUsed).")) AND (d1.cid = $this->cid) AND (d2.CID Is Null)";
		$this->conn->query($sqlAdd1);
		//echo $sqlAdd1."<br />";

		//Set inheritance for all children of target
		$count = 0;
		do{
			$count++;
			$sqlAdd2 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (d1.cid = $this->cid) AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId >= 180 OR t2.RankId <= 220) AND (t2.tid IN($this->childrenStr)) AND (d2.CID Is Null)";
			//echo $sqlAdd2;
			$this->conn->query($sqlAdd2);
		}while($count < 2);
	}

	public function setChildrenList(){
 		//Returns a list of children TID, excluding target TIDs
		$childrenArr = Array();
 		$childrenArr = $this->tidUsed;
		$targetStr = implode(",",$this->tidUsed);
		do{
			//unset($targetList);
			$targetList = Array();
			$sql = "SELECT DISTINCT t.TID FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND t.RankId > 140 AND t.RankId <= 220 AND (ts.ParentTID In ($targetStr))";
			//echo $sql."<br/>";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$targetList[] = $row->TID;
		    }
			if($targetList){
				$targetStr = implode(",", $targetList);
				$childrenArr = array_merge($childrenArr, $targetList);
			}
		}while($targetList);
		$result->close();
		$this->childrenStr = implode(",",array_unique($childrenArr));
	}
	
	//Setter and getters
	public function setTaxonFilter($tf){
		if(is_numeric($tf)){
			$this->tidFilter = $tf;
		}
	}
	
	public function setClFilter($clid){
		if(is_numeric($clid)){
			$this->clidFilter = $clid;
		}
	}

	public function setGeneraOnly($genOnly){
		$this->generaOnly = $genOnly;
	}

	public function setCID($c){
		$this->cid = $c;
	}
	
	public function setProj($p){
		$sql = 'SELECT pid, projname FROM fmprojects WHERE (pid = "'.$p.'") OR (projname = "'.$p.'")';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->pid = $r->pid;
			$this->projName = $r->projname;
		}
		$rs->close();
	}
	
	public function setProjName($p){
		$this->projName = $p;
	}
	
	public function setPid($p){
		$this->pid = $p;
	}
	
	public function setLang($l){
		$this->lang = $l;
	}

	public function setUsername($uname){
    	$this->username = $uname;
  	}

	public function getClQueryList(){
		$returnList = Array();
		$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ";
		if($this->pid) {
			$sql .= "INNER JOIN fmchklstprojlink cpl ON cl.clid = cpl.clid ";
		}
		$sql .= "WHERE cl.access = 'public' ";
		if($this->pid) {
			$sql .= "AND (cpl.pid = ".$this->pid.") ";
		}
		$sql .= "ORDER BY cl.name";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->name) $returnList[$r->clid] = $r->name;
		}
		$rs->free();
		return $returnList;
	}

	public function getTaxaQueryList(){
		$retArr = Array();
		$sql = 'SELECT t.tid, t.sciname '. 
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE t.rankid IN(140,180) AND ts.tid = ts.tidaccepted '.
			'ORDER BY t.rankid, t.sciname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}
}
?>