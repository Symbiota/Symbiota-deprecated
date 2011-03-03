<?php
/*
 * Created on 24 Aug 2009
 * E.E. Gilbert
 */

include_once($serverRoot.'/config/dbconnection.php');

class TaxonomyEditorManager{

	private $conn;
	private $taxAuthId = 1;
	private $tid = 0;
	private $upperTaxon;
	private $family;
	private $sciName;
	private $kingdomId;
	private $rankId = 0;
	private $rankName;
	private $unitInd1;
	private $unitName1;
	private $unitInd2;
	private $unitName2;
	private $unitInd3;
	private $unitName3;
	private $author;
	private $parentTid = 0;
	private $parentName;
	private $parentNameFull;
	private $source;
	private $notes;
	private $hierarchy;
	private $securityStatus;
	private $isAccepted = -1;			// 1 = accepted, 0 = not accepted, -1 = not assigned, -2 in conflict
	private $acceptedArr = Array();
	private $synonymArr = Array();

	function __construct($target) {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		if(is_numeric($target)){
			$this->tid = $target;
		}
		else{
			$sql = "SELECT T.tid FROM taxa t WHERE t.sciname = '".$target."'";
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->tid = $row->tid;
			}
			$rs->close();
		}
	}
	
	function __destruct(){
		if($this->conn) $this->conn->close();
	}
	
	public function setTaxon(){
		
		$sqlTaxon = "SELECT t.tid, t.kingdomid, t.rankid, tu.rankname, t.sciname, t.unitind1, t.unitname1, ".
			"t.unitind2, t.unitname2, t.unitind3, t.unitname3, t.author, ts.parenttid, t.source, t.notes, ts.hierarchystr, ".
			"t.securitystatus, t.initialtimestamp, ts.tidaccepted, ts.unacceptabilityreason, ".
			"ts.uppertaxonomy, ts.family, t2.sciname AS accsciname, t2.author AS accauthor, t2.notes AS accnotes, ts.sortsequence ".
			"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid) ".
			"LEFT JOIN taxonunits tu ON t.rankid = tu.rankid AND t.kingdomid = tu.kingdomid ".
			"WHERE ts.taxauthid = ".$this->taxAuthId." AND t.tid = ".$this->tid;
		//echo $sqlTaxon;
		$rs = $this->conn->query($sqlTaxon); 
		if($row = $rs->fetch_object()){
			$this->upperTaxon = $row->uppertaxonomy;
			$this->family = $row->family;
			$this->sciName = $row->sciname;
			$this->kingdomId = $row->kingdomid;
			$this->rankId = $row->rankid;
			$this->rankName = $row->rankname;
			$this->unitInd1 = $row->unitind1;
			$this->unitName1 = $row->unitname1;
			$this->unitInd2 = $row->unitind2;
			$this->unitName2 = $row->unitname2;
			$this->unitInd3 = $row->unitind3;
			$this->unitName3 = $row->unitname3;
			$this->author = $row->author;
			$this->parentTid = $row->parenttid;
			$this->source = $row->source;
			$this->notes = $row->notes;
			$this->hierarchy = $row->hierarchystr;
			$this->securityStatus = $row->securitystatus;

			//Deal with TaxaStatus table stuff
			do{
				$tidAccepted = $row->tidaccepted;
				if($this->tid == $tidAccepted){
					if($this->isAccepted == -1 || $this->isAccepted == 1){
						$this->isAccepted = 1;
					}
					else{
						$this->isAccepted = -2;
					}
				}
				else{
					if($this->isAccepted == -1 || $this->isAccepted == 0){
						$this->isAccepted = 0;
					}
					else{
						$this->isAccepted = -2;
					}
					$this->acceptedArr[$tidAccepted]["unacceptabilityreason"] = $row->unacceptabilityreason;
					$this->acceptedArr[$tidAccepted]["sciname"] = $row->accsciname;
					$this->acceptedArr[$tidAccepted]["author"] = $row->accauthor;
					$this->acceptedArr[$tidAccepted]["usagenotes"] = $row->accnotes;
					$this->acceptedArr[$tidAccepted]["sortsequence"] = $row->sortsequence;
				}
			}while($row = $rs->fetch_object());
		}
		if($this->isAccepted == 1) $this->setSynonyms();
		if($this->parentTid) $this->setParentName();
		$rs->close();
	}
	
	private function setSynonyms(){
		$sql = "SELECT t.tid, t.sciname, t.author, ts.unacceptabilityreason, ts.notes, ts.sortsequence ".
			"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid ".
			"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid <> ts.tidaccepted) AND (ts.tidaccepted = ".$this->tid.") ".
			"ORDER BY ts.sortsequence,t.sciname";
		//echo $sql."<br>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$this->synonymArr[$row->tid]["sciname"] = $row->sciname;
			$this->synonymArr[$row->tid]["author"] = $row->author;
			$this->synonymArr[$row->tid]["unacceptabilityreason"] = $row->unacceptabilityreason;
			$this->synonymArr[$row->tid]["notes"] = $row->notes;
			$this->synonymArr[$row->tid]["sortsequence"] = $row->sortsequence;
		}
		$result->close();
	}

	private function setParentName(){
		$sql = "SELECT t.sciname, t.author ".
			"FROM taxa t ".
			"WHERE (t.tid = ".$this->parentTid.")";
		//echo $sql."<br>";
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$this->parentNameFull = '<i>'.$row->sciname.'</i> '.$row->author;
			$this->parentName = $row->sciname;
		}
		$result->close();
	}
	
	//Misc methods for retrieving field data
	public function echoTaxonomicThesaurusIds(){
		//For now, just return the default taxonomy (taxauthid = 1)
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta INNER JOIN taxstatus ts ON ta.taxauthid = ts.taxauthid ".
			"WHERE ta.isactive = 1 AND ts.tid = ".$this->tid." AND ta.taxauthid = 1 ORDER BY ta.taxauthid ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value=".$row->taxauthid." ".($this->taxAuthId==$row->taxauthid?"SELECTED":"").">".$row->name."</option>\n";
		}
		$rs->close();
	}

	public function echoRankIdSelect(){
		$sql = "SELECT tu.rankid, tu.rankname FROM taxonunits tu ".
			"WHERE tu.kingdomid = ".$this->kingdomId." ORDER BY tu.rankid ";
		$rs = $this->conn->query($sql); 
		echo "<option value='0'>Select Taxon Rank</option>\n";
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->rankid."' ".($this->rankId==$row->rankid?"SELECTED":"").">".$row->rankname."</option>\n";
		}
		$rs->close();
	}  

	public function echoHierarchy(){
		if($this->hierarchy){
			$sql = "SELECT t.tid, t.sciname FROM taxa t ".
				"WHERE t.tid IN(".$this->hierarchy.") ORDER BY t.rankid, t.sciname ";
			$rs = $this->conn->query($sql); 
			$indent = 0;
			while($row = $rs->fetch_object()){
				echo "<div style='margin-left:".$indent.";'><a href='taxonomyeditor.php?target=".$row->tid."'>".$row->sciname."</a></div>\n";
				$indent += 10;
			}
			$rs->close();
		}
		else{
			echo "<div style='margin:10px;'>Empty</div>";
		}
	}
	
	//Edit Functions
	public function submitTaxonEdits($taxonEditArr){
		$tid = $taxonEditArr["tid"];
		unset($taxonEditArr["tid"]);

		//Update taxa record
		$sql = "UPDATE taxa SET ";
		foreach($taxonEditArr as $key => $value){
			$v = trim($value);
			if($v === ""){
				$sql .= $key." = NULL,";
			}
			else{
				$sql .= $key." = \"".$v."\",";
			}
		}
		$sql .= "sciname = \"".($taxonEditArr["unitind1"]?$taxonEditArr["unitind1"]." ":"").
			$taxonEditArr["unitname1"].($taxonEditArr["unitind2"]?" ".$taxonEditArr["unitind2"]:"").
			($taxonEditArr["unitname2"]?" ".$taxonEditArr["unitname2"]:"").
			($taxonEditArr["unitind3"]?" ".$taxonEditArr["unitind3"]:"").
			($taxonEditArr["unitname3"]?" ".$taxonEditArr["unitname3"]:"")."\"";
		$sql .= " WHERE tid = ".$tid;
		//echo $sql;
		$status = $this->conn->query($sql);
		
		return $status;
	}
	
	public function submitTaxstatusEdits($tsArr){
		//See if parent changed
		$currentParentTid = 0;
		$sqlParent = "SELECT ts.parenttid FROM taxstatus ts WHERE ts.taxauthid = ".$this->taxAuthId." AND ts.tid = ".$tsArr["tid"];
		$rs = $this->conn->query($sqlParent);
		if($row = $rs->fetch_object()){
			$currentParentTid = $row->parenttid;
		}
		$rs->close();

		$famStr = '';
		if($currentParentTid != $tsArr["parenttid"]){
			$famStr = $this->rebuildHierarchy($tsArr["tid"],$tsArr["parenttid"]);
		}
		$sql = 'UPDATE taxstatus '.
			'SET uppertaxonomy = "'.trim($tsArr['uppertaxonomy']).'",parenttid = '.$tsArr["parenttid"].($famStr?',family = "'.$famStr.'" ':' ').
			"WHERE taxauthid = ".$this->taxAuthId." AND tid = ".$tsArr["tid"]." AND tidaccepted = ".$tsArr["tidaccepted"];
		$status = $this->conn->query($sql);
		return $status;
	}
	
	public function rebuildHierarchy($tid, $pTid = 0){
		$parentArr = Array();
		$parCnt = 0;
		$targetTid = $tid;
		if($pTid){
			$parentArr[$pTid] = $pTid;
			$targetTid = $pTid;
		}
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.taxauthid = ".$this->taxAuthId." AND ts.tid = ".$targetTid;
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$parentTid = $rowParent->parenttid;
				if($parentTid) {
					$parentArr[$parentTid] = $parentTid;
				}
			}
			else{
				break;
			}
			$resultParent->close();
			$parCnt++;
			if($targetTid == $parentTid) break;
			$targetTid = $parentTid;
		}while($targetTid && $parCnt < 16);
		//Add hierarchy string to taxa table
		$hierarchyStr = implode(",",array_reverse($parentArr));
		$oldHierarchy = '';
		$sqlOld = 'SELECT hierarchystr FROM taxstatus WHERE taxauthid = '.$this->taxAuthId.' AND tid = '.$tid;
		$rsOld = $this->conn->query($sqlOld);
		if($r = $rsOld->fetch_object()){
			$oldHierarchy = $r->hierarchystr;
		}
		$rsOld->close();
		if($hierarchyStr && $oldHierarchy && $hierarchyStr <> $oldHierarchy){
			//Reset hierarchy for target taxon and all children
			$sqlUpdate = 'UPDATE taxstatus SET hierarchystr = REPLACE(hierarchystr,"'.$oldHierarchy.'","'.$hierarchyStr.'") '.
				'WHERE taxauthid = '.$this->taxAuthId.' AND (hierarchystr LIKE "'.$oldHierarchy.','.$tid.'%" OR tid = '.$tid.')';
			$this->conn->query($sqlUpdate);
		}
		//Return family
		$retStr = '';
		$sqlFam = 'SELECT sciname FROM taxa WHERE tid IN('.$hierarchyStr.') AND rankid = 140';
		$rsFam = $this->conn->query($sqlFam);
		if($r = $rsFam->fetch_object()){
			$retStr = $r->sciname;
		}
		return $retStr;
	}

	public function submitSynEdits($synEditArr){
		$tid = $synEditArr["tid"];
		unset($synEditArr["tid"]);
		$tidAccepted = $synEditArr["tidaccepted"];
		unset($synEditArr["tidaccepted"]);
		$sql = "UPDATE taxstatus SET ";
		$sqlSet = "";
		foreach($synEditArr as $key => $value){
			$sqlSet .= ",".$key." = '".trim($value)."'";
		}
		$sql .= substr($sqlSet,1);
		$sql .= " WHERE taxauthid = ".$this->taxAuthId." AND tid = ".$tid." AND tidaccepted = ".$tidAccepted;
		//echo $sql;
		$status = $this->conn->query($sql);
		return $status;
	}
	
	public function submitAddAcceptedLink($tid, $tidAcc, $deleteOther = true){
		$upperTax = "";$family = "";$parentTid = 0;$hierarchyStr = "";
		$sqlFam = "SELECT ts.uppertaxonomy, ts.family, ts.parenttid, ts.hierarchystr ".
			"FROM taxstatus ts WHERE ts.tid = $tid AND ts.taxauthid = ".$this->taxAuthId;
		$rs = $this->conn->query($sqlFam);
		if($row = $rs->fetch_object()){
			$upperTax = $row->uppertaxonomy;
			$family = $row->family;
			$parentTid = $row->parenttid;
			$hierarchyStr = $row->hierarchystr;
		}
		$rs->close();
		
		if($deleteOther){
			$sqlDel = "DELETE FROM taxstatus WHERE tid = $tid AND taxauthid = ".$this->taxAuthId;
			$this->conn->query($sqlDel);
		}
		$sql = "INSERT INTO taxstatus (tid,tidaccepted,taxauthid,uppertaxonomy,family,parenttid,hierarchystr) ".
			"VALUES ($tid, $tidAcc, $this->taxAuthId,".($upperTax?"\"".$upperTax."\"":"NULL").",".
			($family?"\"".$family."\"":"NULL").",".$parentTid.",'".$hierarchyStr."') ";
		//echo $sql;
		$status = $this->conn->query($sql);
		return $status;
	}
	
	public function submitChangeToAccepted($tid,$tidAccepted,$switchAcceptance = true){
		
		$sql = "UPDATE taxstatus SET tidaccepted = $tid WHERE tid = $tid AND taxauthid = $this->taxAuthId";
		$status = $this->conn->query($sql);

		if($switchAcceptance){
			$sqlSwitch = 'UPDATE taxstatus SET tidaccepted = '.$tid.' WHERE tidaccepted = '.$tidAccepted.' AND taxauthid = '.$this->taxAuthId;
			$status = $this->conn->query($sqlSwitch);
			
			$this->updateDependentData($tidAccepted,$tid);
		}
		return $status;
	}
	
	public function submitChangeToNotAccepted($tid,$tidAccepted){
		//Change subject taxon to Not Accepted
		$sql = "UPDATE taxstatus SET tidaccepted = $tidAccepted WHERE tid = $tid AND taxauthid = $this->taxAuthId";
		$status = $this->conn->query($sql);

		//Switch synonyms of subject to Accepted Taxon 
		$sqlSyns = "UPDATE taxstatus SET tidaccepted = $tidAccepted WHERE tidaccepted = $tid AND taxauthid = $this->taxAuthId";
		$status = $this->conn->query($sqlSyns);
		
		$this->updateDependentData($tid,$tidAccepted);
		
		return $status;
	}
	
	private function updateDependentData($tid, $tidNew){
		//method to update descr, vernaculars,

		$this->conn->query("DELETE FROM kmdescr WHERE inherited IS NOT NULL AND tid = ".$tid);
		$this->conn->query("UPDATE IGNORE kmdescr SET tid = ".$tidNew." WHERE tid = ".$tid);
		$this->conn->query("DELETE FROM kmdescr WHERE tid = ".$tid);
		$this->resetCharStateInheritance($tidNew);
		
		$sqlVerns = "UPDATE taxavernaculars SET tid = ".$tidNew." WHERE tid = ".$tid;
		$this->conn->query($sqlVerns);
		
		$sqltd = 'UPDATE taxadescrblock tb LEFT JOIN (SELECT DISTINCT caption FROM taxadescrblock WHERE tid = '.$tidNew.') lj ON tb.caption = lj.caption '.
			'SET tid = '.$tidNew.' WHERE tid = '.$tid.' AND lj.caption IS NULL';
		$this->conn->query($sqltd);

		$sqltl = "UPDATE taxalinks SET tid = ".$tidNew." WHERE tid = ".$tid;
		$this->conn->query($sqltl);
		
	}
	
	private function resetCharStateInheritance($tid){
		//set inheritance for target only
		$sqlAdd1 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
			"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
			"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
			"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
			"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
			"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
			"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
			"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
			"AND (t2.tid = $tid) And (d2.CID Is Null)";
		$this->conn->query($sqlAdd1);

		//Set inheritance for all children of target
		if($this->rankId == 140){
			$sqlAdd2a = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 180) AND (t1.tid = $tid) AND (d2.CID Is Null)";
			//echo $sqlAdd2a;
			$this->conn->query($sqlAdd2a);
			$sqlAdd2b = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.family = '".$this->sciName."') AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 220) AND (d2.CID Is Null)";
			$this->conn->query($sqlAdd2b);
		}

		if($this->rankId > 140 && $this->rankId < 220){
			$sqlAdd3 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 220) AND (t1.tid = $tid) AND (d2.CID Is Null)";
			//echo $sqlAdd2b;
			$this->conn->query($sqlAdd3);
		}
	}
	
	//Regular getter functions for this class
	public function getTargetName(){
		return $this->targetName;
	}

	public function getTid(){
		return $this->tid;
	}
	
	public function setTaxAuthId($taid){
		if($taid){
			$this->taxAuthId = $taid;
		}
	}
	
	public function getTaxAuthId(){
		return $this->taxAuthId;
	}

	public function getUpperTaxon(){
		return $this->upperTaxon;
	}

	public function getFamily(){
		return $this->family;
	}

	public function getSciName(){
		return $this->sciName;
	}

	public function getKingdomId(){
		return $this->kingdomId;
	}

	public function getRankId(){
		return $this->rankId;
	}
	
	public function getRankName(){
		return $this->rankName;
	}

	public function getUnitInd1(){
		return $this->unitInd1;
	}

	public function getUnitName1(){
		return $this->unitName1;
	}

	public function getUnitInd2(){
		return $this->unitInd2;
	}

	public function getUnitName2(){
		return $this->unitName2;
	}

	public function getUnitInd3(){
		return $this->unitInd3;
	}

	public function getUnitName3(){
		return $this->unitName3;
	}

	public function getAuthor(){
		return $this->author;
	}

	public function getParentTid(){
		return $this->parentTid;
	}

	public function getParentName(){
		return $this->parentName;
	}

	public function getParentNameFull(){
		return $this->parentNameFull;
	}

	public function getSource(){
		return $this->source;
	}

	public function getNotes(){
		return $this->notes;
	}

	public function getHierarchy(){
		return $this->hierarchy;
	}

	public function getSecurityStatus(){
		return $this->securityStatus;
	}

	public function getIsAccepted(){
		return $this->isAccepted;
	}

	public function getAcceptedArr(){
		return $this->acceptedArr;
	}
	
	public function getSynonyms(){
		return $this->synonymArr;
	}
}
?>