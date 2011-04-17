<?php
include_once($serverRoot.'/config/dbconnection.php');

class KeyMassUpdateManager{
	
	private $con;
	private $taxonNameFilter;
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
 		$this->con = MySQLiConnectionFactory::getCon("write");
 	}

 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setTaxonFilter($name){
		$this->taxonNameFilter = $name;
	}
	
	public function setClFilter($clid){
		$this->clidFilter = $clid;
	}

	public function setGeneraOnly($genOnly){
		$this->generaOnly = $genOnly;
	}

	public function setCID($c){
		$this->cid = $c;
	}
	
	public function setProj($p){
		$sql = 'SELECT pid, projname FROM fmprojects WHERE pid = "'.$p.'" OR projname = "'.$p.'"';
		$rs = $this->con->query($sql);
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
		$sql = "SELECT cl.CLID, cl.Name FROM fmchecklists cl ";
		if($this->pid) {
			$sql .= "INNER JOIN fmchklstprojlink cpl ON cl.CLID = cpl.clid ".
				"WHERE cpl.pid = ".$this->pid." ";
		}
		$sql .= "ORDER BY cl.Name";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$returnList[$row->CLID] = $row->Name;
		}
		$result->close();
		return $returnList;
	}
	
	public function getTaxaQueryList(){
		$returnList = Array();
		$sql = "SELECT DISTINCT ts.UpperTaxonomy, ts.Family, t.UnitName1 
			FROM ((fmchklstprojlink cpl INNER JOIN fmchklsttaxalink ctl ON cpl.clid = ctl.CLID) ".
			"INNER JOIN taxstatus ts ON ctl.tid = ts.tid) 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID ";
		$sqlWhere = "";
		if($this->clidFilter && $this->clidFilter != "all") $sqlWhere .= "ctl.CLID = ".$this->clidFilter." ";
		if($this->pid) $sqlWhere .= ($sqlWhere?"AND ":"")."(cpl.pid = '".$this->pid."') ";
		if($sqlWhere) $sql = $sql."WHERE ".$sqlWhere;
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$upper = $row->UpperTaxonomy;
			$fam = $row->Family;
			$genus = $row->UnitName1;
			if(!in_array($upper,$returnList)) $returnList[] = $upper;
			if(!in_array($fam,$returnList)) $returnList[] = $fam;
			if(!in_array($genus,$returnList)) $returnList[] = $genus;
		}
		$result->close();
		sort($returnList);
		return $returnList;
	}

	public function getCharList(){
		$headingArray = Array();		//Heading => Array(CID => CharName)
		if($this->taxonNameFilter){
			$strFrag = implode(",",$this->getParents($this->taxonNameFilter));
			/*$sql = "SELECT DISTINCT charnames.Heading, charnames.CID, charnames.CharName ".
				"FROM ((chartaxalink INNER JOIN characters ON chartaxalink.CID = characters.CID) INNER JOIN charnames ON characters.CID = charnames.CID) ".
				"LEFT JOIN chardependance ON characters.CID = chardependance.CID ".
				"WHERE (chartaxalink.Relation = 'include') ".
				"AND (characters.Type='UM' Or characters.Type='OM') AND (charnames.Language='".$this->lang."') ".
				"AND (chartaxalink.TID In ($strFrag)) ".
				"ORDER BY charnames.Heading, characters.SortSequence";*/
			$sql = "SELECT DISTINCT ch.headingname, c.CID, c.CharName ".
				"FROM ((kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID) ".
				"INNER JOIN kmcharheading ch ON c.hid = ch.hid) ".
				"LEFT JOIN kmchardependance cd ON c.CID = cd.CID ".
				"WHERE ch.language = 'English' AND (ctl.Relation = 'include') ".
				"AND (c.chartype='UM' Or c.chartype='OM') AND (c.defaultlang='".$this->lang."') ".
				"AND (ctl.TID In ($strFrag)) ".
				"ORDER BY c.hid, c.SortSequence";
			//echo $sql;
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$headingArray[$row->headingname][$row->CID] = $row->CharName;
			}
			$result->free();
		}
		return $headingArray;
	}
	
	private function getParents($t){
		//Returns a list of parent TIDs, including target 
 		$returnList = Array();
		$targetTaxon = $t;
		while($targetTaxon){
			$sql = "SELECT t.TID, ts.ParentTID FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE ts.taxauthid = 1 AND ".(intval($targetTaxon)?"t.TID = $targetTaxon":"t.SciName = '".$t."'");
			$result = $this->con->query($sql);
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
		$sql = "SELECT kmcs.CharStateName, kmcs.CS FROM kmcs ".
			"WHERE kmcs.Language = '".$this->lang."' AND kmcs.CID = $this->cid ORDER BY kmcs.SortSequence";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$stateArr[$row->CS] = $row->CharStateName;
		}
		$rs->free();
		return $stateArr;
	}
	
	public function getTaxaList(){
		//Get list of Char States
 		$stateList = Array();
		$sql = "SELECT kmcs.CS, kmcs.CharStateName FROM kmcs WHERE kmcs.CID = $this->cid";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$stateList[$row->CS] = $row->CharStateName; 
	    }
		$result->free();
		
		//Get all Taxa found in checklist 
		$taxaList = Array();
		$parArr = Array();
		$famArr = Array();
		$sql = "SELECT DISTINCT t.TID, ts.Family, t.SciName, ts.ParentTID, t.RankId, d.CID, d.CS, d.Inherited ".
			"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
			"LEFT JOIN (SELECT d.TID, d.CID, d.CS, d.Inherited FROM kmdescr d WHERE (d.CID=".$this->cid.")) AS d ON t.TID = d.TID) ";
		if($this->clidFilter && $this->clidFilter != "all"){
			$sql .= "INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid ";
		}
		$sql .= "WHERE (t.RankId = 220) AND (ts.taxauthid = 1) AND (ts.Family='".$this->taxonNameFilter."' OR t.SciName Like '".$this->taxonNameFilter." %') ";
		if($this->clidFilter && $this->clidFilter != "all"){
			$sql .= "AND (ctl.CLID = $this->clidFilter)" ;
		}
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row1 = $rs->fetch_object()){
			$sciName = $row1->SciName;
			$sciNameDisplay = "<div style='margin-left:6px'>$sciName</div>";
			$family = $row1->Family;
			if(!$this->generaOnly){
				$taxaList[$family][$sciName]["TID"] = $row1->TID;
				$taxaList[$family][$sciName]["display"] = $sciNameDisplay;
				$taxaList[$family][$sciName]["csArray"][$row1->CS] = $row1->Inherited;
			}
			$parTID = $row1->ParentTID;
			if(!in_array($parTID,$parArr)) $parArr[] = $parTID;
			if(!in_array($family,$famArr)) $famArr[] = $family;
		}
		$rs->close();

		//Get all genera and family and add them to list
		$taxaStr = implode(",",$parArr);
		$famStr = implode("','",$famArr);
		$sql = "SELECT DISTINCT t.TID, ts.Family, t.SciName, t.RankId, ts.ParentTID, d.CID, d.CS, d.Inherited ".
		"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
		"LEFT JOIN (SELECT di.TID, di.CID, di.CS, di.Inherited FROM kmdescr di ".
		"WHERE (di.CID=$this->cid)) AS d ON t.TID = d.TID ".
		"WHERE (ts.taxauthid = 1 AND (((t.RankId = 180) AND (t.TID IN(".$taxaStr."))) OR (t.SciName IN('$famStr'))))";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$sciName = $row->SciName;
			$rankId = $row->RankId;
			$family = ($rankId == 140?$sciName:$row->Family);
			$sciNameDisplay = "<div style='margin-left:3px'>$sciName</div>";
			$taxaList[$family][$sciName]["TID"] = $row->TID;
			$taxaList[$family][$sciName]["display"] = $sciNameDisplay;
			$taxaList[$family][$sciName]["csArray"][$row->CS] = $row->Inherited;
		}
		$rs->close();
		return $taxaList;
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
			$this->con->query($sqlTrans);
			
			//delete value from descr
			$sqlStr = "DELETE d.* FROM kmdescr d WHERE ".implode(" OR ",$this->removes);
			$this->con->query($sqlStr);
		}
		
		foreach($this->adds as $v){
			$this->con->query($v);
 		}
	}

	public function deleteInheritance(){
		//delete all inherited children traits for CIDs that will be modified
		$this->setChildrenList();
		$sqlDel = "DELETE FROM kmdescr ".
			"WHERE (TID IN(".$this->childrenStr.")) ".
			"AND (CID = ".$this->cid.") AND (Inherited Is Not Null AND Inherited <> '')";
		$this->con->query($sqlDel);
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
		$this->con->query($sqlAdd1);
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
			$this->con->query($sqlAdd2);
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
				"WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND t.RankId > 140 AND t.RankId <= 220 AND ts.ParentTID In ($targetStr)";
			//echo $sql."<br/>";
			$result = $this->con->query($sql);
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
}
?>

	