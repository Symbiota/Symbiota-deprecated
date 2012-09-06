<?php
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

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
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
			"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (t.tid = ".$this->tid.')';
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
			"WHERE ta.isactive = 1 AND (ts.tid = ".$this->tid.") AND ta.taxauthid = 1 ORDER BY ta.taxauthid ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value=".$row->taxauthid." ".($this->taxAuthId==$row->taxauthid?"SELECTED":"").">".$row->name."</option>\n";
		}
		$rs->close();
	}

	public function echoRankIdSelect(){
		$sql = "SELECT tu.rankid, tu.rankname FROM taxonunits tu ".
			"WHERE (tu.kingdomid = ".$this->kingdomId.") ORDER BY tu.rankid ";
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
				"WHERE (t.tid IN(".$this->hierarchy.")) ORDER BY t.rankid, t.sciname ";
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
				$sql .= $key.' = "'.$v.'",';
			}
		}
		$sql .= 'sciname = "'.$this->conn->real_escape_string(($taxonEditArr["unitind1"]?$taxonEditArr["unitind1"]." ":"").
			$taxonEditArr["unitname1"].($taxonEditArr["unitind2"]?" ".$taxonEditArr["unitind2"]:"").
			($taxonEditArr["unitname2"]?" ".$taxonEditArr["unitname2"]:"").
			($taxonEditArr["unitind3"]?" ".$taxonEditArr["unitind3"]:"").
			($taxonEditArr["unitname3"]?" ".$taxonEditArr["unitname3"]:"")).'"';
		$sql .= " WHERE (tid = ".$tid.')';
		//echo $sql;
		$status = $this->conn->query($sql);
		
		//If SecurityStatus was changed, set security status within omoccurrence table 
		if($taxonEditArr['securitystatus'] != $_REQUEST['securitystatusstart']){
			if(is_numeric($taxonEditArr['securitystatus'])){
				$sql2 = 'UPDATE omoccurrences SET localitysecurity = '.$taxonEditArr['securitystatus'].
					' WHERE (tidinterpreted = '.$tid.')';
				$this->conn->query($sql2);
			}
		}
		return $status;
	}
	
	public function submitTaxstatusEdits($tsArr){
		$status = '';
		$this->setTaxon();
		$sql = 'UPDATE taxstatus '.
			'SET uppertaxonomy = "'.$this->conn->real_escape_string(trim($tsArr['uppertaxonomy'])).'",parenttid = '.
			$this->conn->real_escape_string($tsArr["parenttid"]).' '.
			'WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tsArr['tid'].') AND (tidaccepted = '.$tsArr['tidaccepted'].')';
		if($this->conn->query($sql)){
			$this->rebuildHierarchy($tsArr["tid"]);
		}
		else{
			$status = 'Unable to edit taxonomic placement'; 
		}
		return $status;
	}

	public function submitSynEdits($synEditArr){
		$tid = $this->conn->real_escape_string($synEditArr["tid"]);
		unset($synEditArr["tid"]);
		$tidAccepted = $this->conn->real_escape_string($synEditArr["tidaccepted"]);
		unset($synEditArr["tidaccepted"]);
		$sql = "UPDATE taxstatus SET ";
		$sqlSet = "";
		foreach($synEditArr as $key => $value){
			$sqlSet .= ",".$this->conn->real_escape_string($key)." = '".$this->conn->real_escape_string(trim($value))."'";
		}
		$sql .= substr($sqlSet,1);
		$sql .= " WHERE (taxauthid = ".$this->taxAuthId.
			") AND tid = ".$tid." AND (tidaccepted = ".$tidAccepted.')';
		//echo $sql;
		$status = $this->conn->query($sql);
		return $status;
	}
	
	public function submitAddAcceptedLink($tid, $tidAcc, $deleteOther = true){
		$upperTax = "";$family = "";$parentTid = 0;$hierarchyStr = "";
		$status = '';
		$tid = $this->conn->real_escape_string($tid);
		if(is_numeric($tid)){
			$sqlFam = 'SELECT ts.uppertaxonomy, ts.family, ts.parenttid, ts.hierarchystr '.
				'FROM taxstatus ts WHERE (ts.tid = '.$tid.') AND (ts.taxauthid = '.$this->taxAuthId.')';
			$rs = $this->conn->query($sqlFam);
			if($row = $rs->fetch_object()){
				$upperTax = $row->uppertaxonomy;
				$family = $row->family;
				$parentTid = $row->parenttid;
				$hierarchyStr = $row->hierarchystr;
			}
			$rs->close();
			
			if($deleteOther){
				$sqlDel = "DELETE FROM taxstatus WHERE (tid = ".$tid.") AND (taxauthid = ".$this->taxAuthId.')';
				$this->conn->query($sqlDel);
			}
			$sql = "INSERT INTO taxstatus (tid,tidaccepted,taxauthid,uppertaxonomy,family,parenttid,hierarchystr) ".
				"VALUES (".$tid.", ".$tidAcc.", ".$this->taxAuthId.",".
				($upperTax?"\"".$upperTax."\"":"NULL").",".
				($family?"\"".$family."\"":"NULL").",".
				$parentTid.",'".$hierarchyStr."') ";
			//echo $sql;
			$status = $this->conn->query($sql);
		}
		return $status;
	}
	
	public function submitChangeToAccepted($tid,$tidAccepted,$switchAcceptance = true){
		$status = '';
		$tid = $this->conn->real_escape_string($tid);
		if(is_numeric($tid)){
			$sql = "UPDATE taxstatus SET tidaccepted = ".$tid.
				" WHERE (tid = ".$tid.") AND (taxauthid = ".$this->taxAuthId.')';
			$status = $this->conn->query($sql);
	
			if($switchAcceptance){
				$sqlSwitch = 'UPDATE taxstatus SET tidaccepted = '.$this->conn->real_escape_string($tid).
					' WHERE (tidaccepted = '.$tidAccepted.') AND (taxauthid = '.$this->taxAuthId.')';
				$status = $this->conn->query($sqlSwitch);
				
				$this->updateDependentData($tidAccepted,$tid);
			}
		}
		return $status;
	}
	
	public function submitChangeToNotAccepted($tid,$tidAccepted,$reason,$notes){
		$status = '';
		$tid = $this->conn->real_escape_string($tid);
		if(is_numeric($tid)){
			//Change subject taxon to Not Accepted
			$sql = 'UPDATE taxstatus '.
				'SET tidaccepted = '.$tidAccepted.', unacceptabilityreason = '.($reason?'"'.$reason.'"':'NULL').
				', notes = '.($notes?'"'.$notes.'"':'NULL').' '.
				'WHERE (tid = '.$tid.') AND (taxauthid = '.$this->taxAuthId.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR: unable to switch acceptance; '.$this->conn->error;
				$status .= '<br/>SQL: '.$sql;
			}
			else{
				//Switch synonyms of subject to Accepted Taxon
				$sqlSyns = 'UPDATE taxstatus SET tidaccepted = '.$tidAccepted.' WHERE (tidaccepted = '.$tid.') AND (taxauthid = '.$this->taxAuthId.')';
				if(!$this->conn->query($sqlSyns)){
					$status = 'ERROR: unable to transfer linked synonyms to accepted taxon; '.$this->conn->error;
				}
				
				$this->updateDependentData($tid,$tidAccepted);
			}
		}
		return $status;
	}
	
	private function updateDependentData($tid, $tidNew){
		$tid = $this->conn->real_escape_string($tid);
		if(is_numeric($tid) && is_numeric($tidNew)){
			//method to update descr, vernaculars,
			$this->conn->query('DELETE FROM kmdescr WHERE inherited IS NOT NULL AND (tid = '.$tid.')');
			$this->conn->query('UPDATE IGNORE kmdescr SET tid = '.$tidNew.' WHERE (tid = '.$tid.')');
			$this->conn->query('DELETE FROM kmdescr WHERE (tid = '.$tid.')');
			$this->resetCharStateInheritance($tidNew);
			
			$sqlVerns = 'UPDATE taxavernaculars SET tid = '.$tidNew.' WHERE (tid = '.$tid.')';
			$this->conn->query($sqlVerns);
			
			$sqltd = 'UPDATE taxadescrblock tb LEFT JOIN (SELECT DISTINCT caption FROM taxadescrblock WHERE (tid = '.
				$tidNew.')) lj ON tb.caption = lj.caption '.
				'SET tid = '.$tidNew.' WHERE (tid = '.$tid.') AND lj.caption IS NULL';
			$this->conn->query($sqltd);
	
			$sqltl = 'UPDATE taxalinks SET tid = '.$tidNew.' WHERE (tid = '.$tid.')';
			$this->conn->query($sqltl);
		}		
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
			"AND (t2.tid = ".$tid.") And (d2.CID Is Null)";
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
				"AND (t2.RankId = 180) AND (t1.tid = ".$tid.") AND (d2.CID Is Null)";
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
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.family = '".
				$this->sciName."') AND (ts2.tid = ts2.tidaccepted) ".
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
				"AND (t2.RankId = 220) AND (t1.tid = ".$tid.") AND (d2.CID Is Null)";
			//echo $sqlAdd2b;
			$this->conn->query($sqlAdd3);
		}
	}

	public function rebuildHierarchy($tid){
		if(!$this->rankId) $this->setTaxon(); 
		$parentArr = Array();
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = 'SELECT IFNULL(ts.parenttid,0) AS parenttid, hierarchystr '.
				'FROM taxstatus ts WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$targetTid.')';
			//echo $sqlParents;
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$hStr = $rowParent->hierarchystr;
				if($targetTid <> $tid && $hStr){
					$parentArr[] = $hStr;
					break;
				}
				else{
					$parentTid = $rowParent->parenttid;
					if($parentTid) {
						$parentArr[$parentTid] = $parentTid;
					}
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
		if($hierarchyStr <> $this->hierarchy){
			//First, reset hierarchy for all children
			if($hierarchyStr && $this->hierarchy){
				$sqlUpdate = 'UPDATE taxstatus SET hierarchystr = REPLACE(hierarchystr,"'.$this->hierarchy.'","'.$hierarchyStr.'") '.
					'WHERE (taxauthid = '.$this->taxAuthId.') AND (hierarchystr LIKE "'.$this->hierarchy.','.$tid.'%")';
				$this->conn->query($sqlUpdate);
			}
			//Reset hierarchy for target taxon
			$sqlUpdate = 'UPDATE taxstatus SET hierarchystr = "'.$hierarchyStr.'" '.
				'WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tid.')';
			$this->conn->query($sqlUpdate);
			
		}
		if($this->rankId > 140){
			//Update family in taxstatus table
			$newFam = '';
			$sqlFam1 = 'SELECT sciname FROM taxa WHERE (tid IN('.$hierarchyStr.')) AND rankid = 140';
			$rsFam1 = $this->conn->query($sqlFam1);
			if($r1 = $rsFam1->fetch_object()){
				$newFam = $r1->sciname;
			}
			$rsFam1->close();
			
			$sqlFam2 = 'SELECT family FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tid.')';
			$rsFam2 = $this->conn->query($sqlFam2);
			if($r2 = $rsFam2->fetch_object()){
				if($newFam <> $r2->family){
					//reset family of target and all it's children
					$sql = 'UPDATE taxstatus SET family = '.($newFam?'"'.$this->conn->real_escape_string($newFam).'"':'Not assigned').' '.
						'WHERE (taxauthid = '.$this->taxAuthId.') AND '.
						'((tid = '.$tid.') OR (hierarchystr LIKE "%,'.$tid.'") OR (hierarchystr LIKE "%,'.$tid.',%" ))';
					//echo $sql;
					$this->conn->query($sql);
				}
			}
			$rsFam2->close();
		}
	}
	
	//Load Taxon functions
	public function getKingdomIds(){
		$retArr = array();
		$sql = 'SELECT DISTINCT tu.kingdomid FROM taxonunits tu ORDER BY tu.kingdomid';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->kingdomid] = $row->kingdomid;
		}
		return $retArr;
	}
	
	public function getTaxonRanks(){
		$retArr = array();
		$sql = 'SELECT DISTINCT tu.rankid, tu.rankname FROM taxonunits tu ORDER BY tu.rankid';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->rankid] = $row->rankname;
		}
		return $retArr;
	}
	
	public function loadNewName($dataArr){
		//Load new name into taxa table
		$tid = 0;
		$sqlTaxa = "INSERT INTO taxa(sciname, author, kingdomid, rankid, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, ".
			"source, notes, securitystatus) ".
			"VALUES (\"".$this->conn->real_escape_string($dataArr["sciname"])."\",".($dataArr["author"]?"\"".$this->conn->real_escape_string($dataArr["author"])."\"":"NULL").
			",".$dataArr["kingdomid"].
			",".$dataArr["rankid"].
			",".($dataArr["unitind1"]?"\"".$this->conn->real_escape_string($dataArr["unitind1"])."\"":"NULL").
			",\"".$this->conn->real_escape_string($dataArr["unitname1"])."\",".($dataArr["unitind2"]?"\"".$this->conn->real_escape_string($dataArr["unitind2"])."\"":"NULL").
			",".($dataArr["unitname2"]?"\"".$this->conn->real_escape_string($dataArr["unitname2"])."\"":"NULL").
			",".($dataArr["unitind3"]?"\"".$this->conn->real_escape_string($dataArr["unitind3"])."\"":"NULL").
			",".($dataArr["unitname3"]?"\"".$this->conn->real_escape_string($dataArr["unitname3"])."\"":"NULL").
			",".($dataArr["source"]?"\"".$this->conn->real_escape_string($dataArr["source"])."\"":"NULL").",".
			($dataArr["notes"]?"\"".$this->conn->real_escape_string($dataArr["notes"])."\"":"NULL").
			",".$this->conn->real_escape_string($dataArr["securitystatus"]).")";
		//echo "sqlTaxa: ".$sqlTaxa;
		if($this->conn->query($sqlTaxa)){
			$tid = $this->conn->insert_id;
		 	//Load accepteance status into taxstatus table
			$tidAccepted = ($dataArr["acceptstatus"]?$tid:$dataArr["tidaccepted"]);
			$parTid = $this->conn->real_escape_string($dataArr["parenttid"]);
			if(!$parTid && $dataArr["rankid"] == 10) $parTid = $tid; 
			if($parTid){ 
				if($dataArr["rankid"] > 10) $hierarchy = $this->buildHierarchy($dataArr["parenttid"]);
				//Get family from hierarchy
				$family = '';
				$sqlFam = 'SELECT sciname FROM taxa WHERE (tid IN('.$hierarchy.')) AND rankid = 140 ';
				$rsFam = $this->conn->query($sqlFam);
				if($rsFam){
					if($r = $rsFam->fetch_object()){
						$family = $r->sciname;
					}
				}
				
				//Load new record into taxstatus table
				$sqlTaxStatus = "INSERT INTO taxstatus(tid, tidaccepted, taxauthid, family, uppertaxonomy, parenttid, unacceptabilityreason, hierarchystr) ".
					"VALUES (".$tid.",".$tidAccepted.",1,".($family?"\"".$this->conn->real_escape_string($family)."\"":"NULL").",".
					($dataArr["uppertaxonomy"]?"\"".$this->conn->real_escape_string($dataArr["uppertaxonomy"])."\"":"NULL").
					",".($parTid?$parTid:"NULL").",\"".
					$this->conn->real_escape_string($dataArr["unacceptabilityreason"])."\",\"".$hierarchy."\") ";
				//echo "sqlTaxStatus: ".$sqlTaxStatus;
				if(!$this->conn->query($sqlTaxStatus)){
					return "ERROR: Taxon loaded into taxa, but falied to load taxstatus: sql = ".$sqlTaxa;
				}
			}
		 	
			//Link new name to existing specimens and set locality secirity if needed
			$sql1 = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid ';
			if($dataArr['securitystatus'] == 1) $sql1 .= ',o.localitysecurity = 1 '; 
			$sql1 .= 'WHERE (o.sciname = "'.$this->conn->real_escape_string($dataArr["sciname"]).'") ';
			$this->conn->query($sql1);
			//Link occurrence images to the new name
			$sql2 = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'SET i.tid = o.tidinterpreted '.
				'WHERE i.tid is null AND o.tidinterpreted IS NOT NULL';
			$this->conn->query($sql2);
			//Add their geopoints to omoccurgeoindex 
			$sql3 = "INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) ".
				"SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) ".
				"FROM omoccurrences o ".
				"WHERE (o.tidinterpreted = ".$tid.") AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL";
			$this->conn->query($sql3);
			
		}
		else{
			return 'Taxon Insert FAILED: '.$this->conn->error.'; SQL = '.$sqlTaxa;
		}
		return $tid;
	}
	
	private function buildHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $this->conn->real_escape_string($tid);
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE (ts.tid = ".$targetTid.')';
			//echo "<div>".$sqlParents."</div>";
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
		
		return implode(",",array_reverse($parentArr));
	}

	//Delete taxon functions
	public function verifyDeleteTaxon(){
		$retArr = array();
		
		//Field images
		$sql ='SELECT COUNT(imgid) AS cnt FROM images WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['img'] = $r->cnt;
		}
		$rs->free();
		
		//Vernaculars
		$sql ='SELECT vernacularname FROM taxavernaculars WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['vern'][] = $r->vernacularname;
		}
		$rs->free();
		
		//Text Descriptions
		$sql ='SELECT tdbid,caption FROM taxadescrblock WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['tdesc'][$r->tdbid] = $r->caption;
		}
		$rs->free();
		
		//Checklists and Vouchers
		$sql ='SELECT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
			'WHERE cl.tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['cl'][$r->clid] = $r->name;
		}
		$rs->free();
		
		//Key descriptions
		$sql ='SELECT COUNT(*) AS cnt FROM kmdescr WHERE inherited IS NULL AND tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['kmdesc'] = $r->cnt;
		}
		$rs->free();
		
		//Taxon links
		$sql ='SELECT title FROM taxalinks WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['link'][] = $r->title;
		}
		$rs->free();
		
		return $retArr;
	}
	
	public function transferResources($targetTid){
		if($targetTid){
			//Field images
			$sql ='UPDATE IGNORE images SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			$this->conn->query($sql);
			
			//Vernaculars
			$sql ='UPDATE IGNORE taxavernaculars SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			$this->conn->query($sql);
			
			//Text Descriptions
			$sql ='UPDATE IGNORE taxadescrblock SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			$this->conn->query($sql);
			
			//Checklists and Vouchers
			$sql ='UPDATE IGNORE fmchklsttaxalink SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			$this->conn->query($sql);
			
			//Key descriptions
			$sql ='UPDATE IGNORE kmdescr SET tid = '.$targetTid.' WHERE inherited IS NULL AND tid = '.$this->tid;
			$this->conn->query($sql);
			
			//Taxon links
			$sql ='UPDATE IGNORE taxalinks SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			$this->conn->query($sql);
			
			return $this->deleteTaxon();
		}
	}
	
	public function deleteTaxon(){
		//Field images
		$sql ='DELETE FROM images WHERE tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Vernaculars
		$sql ='DELETE FROM taxavernaculars WHERE tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Text Descriptions
		$sql ='DELETE FROM taxadescrblock WHERE tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Vouchers
		$sql ='DELETE FROM fmvouchers WHERE tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Checklists
		$sql ='DELETE FROM fmchklsttaxalink WHERE tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Key descriptions
		$sql ='DELETE FROM kmdescr WHERE inherited IS NULL AND tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Taxon links
		$sql ='DELETE FROM taxalinks WHERE tid = '.$this->tid;
		$this->conn->query($sql);

		//Taxon status
		$sql ='DELETE FROM taxstatus WHERE tid = '.$this->tid;
		$this->conn->query($sql);
		
		//Delete taxon
		$sql ='DELETE FROM taxa WHERE tid = '.$this->tid;
		if($this->conn->query($sql)){
			return 'SUCCESS: taxon deleted!<br/><a href="taxonomydisplay.php">Return to taxonomy display page</a>';
		}
		return 0;
	}
	
	//Regular getter functions for this class
	public function getTargetName(){
		return $this->targetName;
	}

	public function setTid($tid){
		$this->tid = $tid;
	}
	
	public function getTid(){
		return $this->tid;
	}
	
	public function setTaxAuthId($taid){
		if(is_numeric($taid)){
			$this->taxAuthId = $this->conn->real_escape_string($taid);
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