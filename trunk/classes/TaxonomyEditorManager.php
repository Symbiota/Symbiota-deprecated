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
	private $hierarchyArr;
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
			"t.unitind2, t.unitname2, t.unitind3, t.unitname3, t.author, t.source, t.notes, t.securitystatus, t.initialtimestamp ".
			"FROM taxa t LEFT JOIN taxonunits tu ON t.rankid = tu.rankid AND t.kingdomid = tu.kingdomid ".
			"WHERE (t.tid = ".$this->tid.')';
		//echo $sqlTaxon;
		$rs = $this->conn->query($sqlTaxon);
		if($r = $rs->fetch_object()){
			$this->sciName = $r->sciname;
			$this->kingdomId = $r->kingdomid;
			$this->rankId = $r->rankid;
			$this->rankName = $r->rankname;
			$this->unitInd1 = $r->unitind1;
			$this->unitName1 = $r->unitname1;
			$this->unitInd2 = $r->unitind2;
			$this->unitName2 = $r->unitname2;
			$this->unitInd3 = $r->unitind3;
			$this->unitName3 = $r->unitname3;
			$this->author = $r->author;
			$this->source = $r->source;
			$this->notes = $r->notes;
			$this->securityStatus = $r->securitystatus;

			$sqlTs = "SELECT ts.parenttid, ts.tidaccepted, ts.unacceptabilityreason, ".
				"ts.uppertaxonomy, ts.family, t.sciname, t.author, t.notes, ts.sortsequence ".
				"FROM taxstatus ts INNER JOIN taxa t ON ts.tidaccepted = t.tid ".
				"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid = ".$this->tid.')';
			//echo $sqlTs;
			$rsTs = $this->conn->query($sqlTs);
			if($row = $rsTs->fetch_object()){
				$this->parentTid = $row->parenttid;
				$this->upperTaxon = $row->uppertaxonomy;
				$this->family = $row->family;
				
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
						$this->acceptedArr[$tidAccepted]["sciname"] = $row->sciname;
						$this->acceptedArr[$tidAccepted]["author"] = $row->author;
						$this->acceptedArr[$tidAccepted]["usagenotes"] = $row->notes;
						$this->acceptedArr[$tidAccepted]["sortsequence"] = $row->sortsequence;
					}
				}while($row = $rsTs->fetch_object());
			}
			else{
				//Name has become unlinked to taxstatus table, thus we need to remap
				$sqlPar = 'SELECT t.tid, ts.uppertaxonomy, ts.family '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
					'WHERE ts.taxauthid = '.$this->taxAuthId.' AND ';
				if($this->rankId > 220){
					//Species is parent
					$sqlPar .= 't.rankid = 220 AND t.unitName1 = "'.$this->unitName1.'" AND t.unitName2 = "'.$this->unitName2.'" ';
				}
				elseif($this->rankId > 180){
					//Genus is parent
					$sqlPar .= 't.rankid = 180 AND t.unitName1 = "'.$this->unitName1.'" ';
				}
				else{
					//Kingdom is parent
					$sqlPar .= 't.rankid = 10 AND t.kingdomid = '.$this->kingdomId;
				}
				$rsPar = $this->conn->query($sqlPar);
				if($rPar = $rsPar->fetch_object()){
					$sqlIns = 'INSERT INTO taxstatus(tid, tidaccepted, taxauthid, parenttid, uppertaxonomy, family) '.
						'VALUES('.$this->tid.','.$this->tid.','.$this->taxAuthId.','.$rPar->tid.','.
						($rPar->uppertaxonomy?'"'.$rPar->uppertaxonomy.'"':'NULL').','.
						($rPar->family?'"'.$rPar->family.'"':'NULL').')';
					if($this->conn->query($sqlIns)){
						$this->parentTid = $rPar->tid;
						$this->upperTaxon = $rPar->uppertaxonomy;
						$this->family = $rPar->family;
						$this->isAccepted = 1;
					}
				}
				$rsPar->free();
			}
			$rsTs->close();
			//Set hierarchy array
			$sql2 = 'SELECT parenttid FROM taxaenumtree '.
				'WHERE (tid = '.$this->tid.') AND (taxauthid = '.$this->taxAuthId.')';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$this->hierarchyArr[] = $r2->parenttid;
			}
			$rs2->free();

			if($this->isAccepted == 1) $this->setSynonyms();
			if($this->parentTid) $this->setParentName();
		}
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

	//Edit Functions
	public function submitTaxonEdits($taxonEditArr){
		$statusStr = '';
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
		$sql .= 'sciname = "'.$this->cleanInStr(($taxonEditArr["unitind1"]?$taxonEditArr["unitind1"]." ":"").
			$taxonEditArr["unitname1"].($taxonEditArr["unitind2"]?" ".$taxonEditArr["unitind2"]:"").
			($taxonEditArr["unitname2"]?" ".$taxonEditArr["unitname2"]:"").
			($taxonEditArr["unitind3"]?" ".$taxonEditArr["unitind3"]:"").
			($taxonEditArr["unitname3"]?" ".$taxonEditArr["unitname3"]:"")).'"';
		$sql .= " WHERE (tid = ".$tid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR editing taxon: '.$this->conn->error;
		}
		
		//If SecurityStatus was changed, set security status within omoccurrence table 
		if($taxonEditArr['securitystatus'] != $_REQUEST['securitystatusstart']){
			if(is_numeric($taxonEditArr['securitystatus'])){
				$sql2 = 'UPDATE omoccurrences SET localitysecurity = '.$taxonEditArr['securitystatus'].
					' WHERE (tidinterpreted = '.$tid.')';
				$this->conn->query($sql2);
			}
		}
		return $statusStr;
	}
	
	public function submitTaxstatusEdits($tsArr){
		$status = '';
		$this->setTaxon();
		$sql = 'UPDATE taxstatus '.
			'SET uppertaxonomy = "'.$this->cleanInStr($tsArr['uppertaxonomy']).'",parenttid = '.$tsArr["parenttid"].' '.
			'WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tsArr['tid'].') AND (tidaccepted = '.$tsArr['tidaccepted'].')';
		if($this->conn->query($sql)){
			$this->rebuildHierarchy($tsArr["tid"]);
		}
		else{
			$status = 'Unable to edit taxonomic placement. SQL: '.$sql; 
		}
		return $status;
	}

	public function submitSynEdits($synEditArr){
		$statusStr = '';
		$tid = $synEditArr["tid"];
		unset($synEditArr["tid"]);
		$tidAccepted = $synEditArr["tidaccepted"];
		unset($synEditArr["tidaccepted"]);
		$sql = "UPDATE taxstatus SET ";
		$sqlSet = "";
		foreach($synEditArr as $key => $value){
			$sqlSet .= ",".$key." = '".$this->cleanInStr($value)."'";
		}
		$sql .= substr($sqlSet,1);
		$sql .= " WHERE (taxauthid = ".$this->taxAuthId.
			") AND tid = ".$tid." AND (tidaccepted = ".$tidAccepted.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR editing taxon: '.$this->conn->error;
		}
		return $statusStr;
	}
	
	public function submitAddAcceptedLink($tid, $tidAcc, $deleteOther = true){
		$upperTax = "";$family = "";$parentTid = 0;
		$statusStr = '';
		$tid = $tid;
		if(is_numeric($tid)){
			$sqlFam = 'SELECT ts.uppertaxonomy, ts.family, ts.parenttid '.
				'FROM taxstatus ts WHERE (ts.tid = '.$tid.') AND (ts.taxauthid = '.$this->taxAuthId.')';
			$rs = $this->conn->query($sqlFam);
			if($row = $rs->fetch_object()){
				$upperTax = $row->uppertaxonomy;
				$family = $row->family;
				$parentTid = $row->parenttid;
			}
			$rs->free();
			
			if($deleteOther){
				$sqlDel = "DELETE FROM taxstatus WHERE (tid = ".$tid.") AND (taxauthid = ".$this->taxAuthId.')';
				$this->conn->query($sqlDel);
			}
			$sql = "INSERT INTO taxstatus (tid,tidaccepted,taxauthid,uppertaxonomy,family,parenttid) ".
				"VALUES (".$tid.", ".$tidAcc.", ".$this->taxAuthId.",".
				($upperTax?"\"".$upperTax."\"":"NULL").",".
				($family?"\"".$family."\"":"NULL").",".
				$parentTid.") ";
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR editing taxon: '.$this->conn->error;
			}
		}
		return $statusStr;
	}
	
	public function submitChangeToAccepted($tid,$tidAccepted,$switchAcceptance = true){
		$statusStr = '';
		if(is_numeric($tid)){
			$sql = "UPDATE taxstatus SET tidaccepted = ".$tid.
				" WHERE (tid = ".$tid.") AND (taxauthid = ".$this->taxAuthId.')';
			$status = $this->conn->query($sql);
	
			if($switchAcceptance){
				$sqlSwitch = 'UPDATE taxstatus SET tidaccepted = '.$tid.
					' WHERE (tidaccepted = '.$tidAccepted.') AND (taxauthid = '.$this->taxAuthId.')';
				if(!$this->conn->query($sqlSwitch)){
					$statusStr = 'ERROR editing taxon: '.$this->conn->error;
				}
				
				$this->updateDependentData($tidAccepted,$tid);
			}
		}
		return $statusStr;
	}
	
	public function submitChangeToNotAccepted($tid,$tidAccepted,$reason,$notes){
		$status = '';
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
		if(is_numeric($tid) && is_numeric($tidNew)){
			//method to update descr, vernaculars,
			$this->conn->query('DELETE FROM kmdescr WHERE inherited IS NOT NULL AND (tid = '.$tid.')');
			$this->conn->query('UPDATE IGNORE kmdescr SET tid = '.$tidNew.' WHERE (tid = '.$tid.')');
			$this->conn->query('DELETE FROM kmdescr WHERE (tid = '.$tid.')');
			$this->resetCharStateInheritance($tidNew);
			
			$sqlVerns = 'UPDATE taxavernaculars SET tid = '.$tidNew.' WHERE (tid = '.$tid.')';
			$this->conn->query($sqlVerns);
			
			//$sqltd = 'UPDATE taxadescrblock tb LEFT JOIN (SELECT DISTINCT caption FROM taxadescrblock WHERE (tid = '.
			//	$tidNew.')) lj ON tb.caption = lj.caption '.
			//	'SET tid = '.$tidNew.' WHERE (tid = '.$tid.') AND lj.caption IS NULL';
			//$this->conn->query($sqltd);
	
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
			$sql1 = 'SELECT DISTINCT ts.parenttid '.
				'FROM taxstatus ts WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$targetTid.')';
			//echo $sqlParents;
			$targetTid = 0;
			$rs1 = $this->conn->query($sql1);
			if($r1 = $rs1->fetch_object()){
				if($r1->parenttid){
					if(in_array($r1->parenttid,$parentArr)) break;
					$parentArr[] = $r1->parenttid;
					$targetTid = $r1->parenttid;
				}
			}
			$rs1->free();
			$parCnt++;
		}while($targetTid && $parCnt < 16);

		//Add hierarchy to taxaenumtree table
		$trueHierarchyStr = implode(",",array_reverse($parentArr));
		if($parentArr != $this->hierarchyArr){
			//Reset hierarchy for all children
			$delArr = array($tid);
			$sql2 = 'SELECT DISTINCT tid FROM taxaenumtree WHERE parenttid = '.$tid;
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$delArr[] = $r2->tid;
			}
			$rs2->free();
			if($this->hierarchyArr){
				//Delete children hierachy 
				$sql2a = 'DELETE FROM taxaenumtree '.
					'WHERE parenttid IN('.implode(',',$this->hierarchyArr).') '.
					'AND (tid IN ('.implode(',',$delArr).')) ';
				//echo $sql2a; exit;
				$this->conn->query($sql2a);
			}

			$sql3 = 'INSERT IGNORE INTO taxaenumtree(tid,parenttid,taxauthid) ';
			foreach($parentArr as $pid){
				//Reset hierarchy for children taxa
				$sql3a = $sql3.'SELECT DISTINCT tid,'.$pid.','.$this->taxAuthId.' FROM taxaenumtree WHERE parenttid = '.$tid;
				$this->conn->query($sql3a);
				//Reset hierarchy for target taxon
				$sql3b = $sql3.'VALUES('.$tid.','.$pid.','.$this->taxAuthId.')';
				$this->conn->query($sql3b);
			}
		}

		if($this->rankId > 140){
			//Update family in taxstatus table
			$newFam = '';
			$sqlFam1 = 'SELECT sciname FROM taxa WHERE (tid IN('.$trueHierarchyStr.')) AND rankid = 140';
			$rsFam1 = $this->conn->query($sqlFam1);
			if($r1 = $rsFam1->fetch_object()){
				$newFam = $r1->sciname;
			}
			$rsFam1->close();
			
			$sqlFam2 = 'SELECT family FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tid.')';
			$rsFam2 = $this->conn->query($sqlFam2);
			if($rFam2 = $rsFam2->fetch_object()){
				if($newFam <> $rFam2->family){
					//reset family of target and all it's children
					$sql = 'UPDATE taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
						'SET ts.family = '.($newFam?'"'.$this->cleanInStr($newFam).'"':'Not assigned').' '.
						'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (e.taxauthid = '.$this->taxAuthId.') AND '.
						'((ts.tid = '.$tid.') OR (e.parenttid = '.$tid.'))';
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
		$sqlTaxa = 'INSERT INTO taxa(sciname, author, kingdomid, rankid, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, '.
			'source, notes, securitystatus) '.
			'VALUES ("'.$this->cleanInStr($dataArr["sciname"]).'",'.($dataArr["author"]?'"'.$this->cleanInStr($dataArr["author"]).'"':'NULL').
			",".$dataArr["kingdomid"].
			",".$dataArr["rankid"].
			",".($dataArr["unitind1"]?"\"".$this->cleanInStr($dataArr["unitind1"])."\"":"NULL").
			",\"".$this->cleanInStr($dataArr["unitname1"])."\",".($dataArr["unitind2"]?"\"".$this->cleanInStr($dataArr["unitind2"])."\"":"NULL").
			",".($dataArr["unitname2"]?"\"".$this->cleanInStr($dataArr["unitname2"])."\"":"NULL").
			",".($dataArr["unitind3"]?"\"".$this->cleanInStr($dataArr["unitind3"])."\"":"NULL").
			",".($dataArr["unitname3"]?"\"".$this->cleanInStr($dataArr["unitname3"])."\"":"NULL").
			",".($dataArr["source"]?"\"".$this->cleanInStr($dataArr["source"])."\"":"NULL").",".
			($dataArr["notes"]?"\"".$this->cleanInStr($dataArr["notes"])."\"":"NULL").
			",".$this->cleanInStr($dataArr["securitystatus"]).")";
		//echo "sqlTaxa: ".$sqlTaxa;
		if($this->conn->query($sqlTaxa)){
			$tid = $this->conn->insert_id;
		 	//Load accepteance status into taxstatus table
			$tidAccepted = ($dataArr["acceptstatus"]?$tid:$dataArr["tidaccepted"]);
			$parTid = $this->cleanInStr($dataArr["parenttid"]);
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
				$sqlTaxStatus = "INSERT INTO taxstatus(tid, tidaccepted, taxauthid, family, uppertaxonomy, parenttid, unacceptabilityreason) ".
					"VALUES (".$tid.",".$tidAccepted.",".$this->taxAuthId.",".($family?"\"".$this->cleanInStr($family)."\"":"NULL").",".
					($dataArr["uppertaxonomy"]?"\"".$this->cleanInStr($dataArr["uppertaxonomy"])."\"":"NULL").
					",".($parTid?$parTid:"NULL").",\"".
					$this->cleanInStr($dataArr["unacceptabilityreason"])."\") ";
				//echo "sqlTaxStatus: ".$sqlTaxStatus;
				if(!$this->conn->query($sqlTaxStatus)){
					return "ERROR: Taxon loaded into taxa, but failed to load taxstatus: sql = ".$sqlTaxa;
				}
				
				//Load hierarchy into taxaenumtree table
				$hierarchyArr = explode(',',$hierarchy);
				if($hierarchyArr){
					$sqlEnumTree = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) VALUES';
					foreach($hierarchyArr as $pTid){
						$sqlEnumTree .= '('.$tid.','.$pTid.','.$this->taxAuthId.'),';
					}
					if(!$this->conn->query(trim($sqlEnumTree,','))){
						echo 'WARNING: Taxon loaded into taxa, but failed to populate taxaenumtree: '.$this->conn->error;
					}
				}
			}
		 	
			//Link new name to existing specimens and set locality secirity if needed
			$sql1 = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid ';
			if($dataArr['securitystatus'] == 1) $sql1 .= ',o.localitysecurity = 1 '; 
			$sql1 .= 'WHERE (o.sciname = "'.$this->cleanInStr($dataArr["sciname"]).'") ';
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
		$targetTid = $tid;
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

		//Children taxa
		$sql ='SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '. 
			'WHERE ts.parenttid = '.$this->tid.' ORDER BY t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['child'][$r->tid] = $r->sciname;
		}
		$rs->free();
		
		//Synonym taxa
		$sql ='SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '. 
			'WHERE ts.tidaccepted = '.$this->tid.' AND ts.tid <> ts.tidaccepted ORDER BY t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['syn'][$r->tid] = $r->sciname;
		}
		$rs->free();
		
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
		
		//Occurrence records
		$sql ='SELECT occid FROM omoccurrences WHERE tidinterpreted = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['occur'][] = $r->occid;
		}
		$rs->free();
		
		//Occurrence determinations
		$sql ='SELECT occid FROM omoccurdeterminations WHERE tidinterpreted = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['dets'][] = $r->occid;
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
		$statusStr = '';
		if($targetTid){
			//Remap occurrence records
			$sql ='UPDATE omoccurrences SET tidinterpreted = '.$targetTid.' WHERE tidinterpreted = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring occurrence records ('.$this->conn->error.')<br/>';
			}
			$sql ='UPDATE omoccurdeterminations SET tidinterpreted = '.$targetTid.' WHERE tidinterpreted = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring occurrence determination records ('.$this->conn->error.')<br/>';
			}

			//Field images
			$sql ='UPDATE IGNORE images SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring image links ('.$this->conn->error.')<br/>';
			}
			
			//Vernaculars
			$sql ='UPDATE IGNORE taxavernaculars SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring vernaculars ('.$this->conn->error.')<br/>';
			}
			
			//Text Descriptions
			$sql ='UPDATE IGNORE taxadescrblock SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring taxadescblocks ('.$this->conn->error.')<br/>';
			}
			
			//Vouchers and checklists
			$sql ='UPDATE IGNORE fmvouchers SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring vouchers ('.$this->conn->error.')<br/>';
			}
			$sql ='DELETE FROM fmvouchers WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR deleting leftover vouchers ('.$this->conn->error.')<br/>';
			}
			$sql ='UPDATE IGNORE fmchklsttaxalink SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring checklist links ('.$this->conn->error.')<br/>';
			}

			//Key descriptions
			$sql ='UPDATE IGNORE kmdescr SET tid = '.$targetTid.' WHERE inherited IS NULL AND tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring morphology for ID key ('.$this->conn->error.')<br/>';
			}
			
			//Taxon links
			$sql ='UPDATE IGNORE taxalinks SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStr .= 'ERROR transferring taxa links ('.$this->conn->error.')<br/>';
			}

			$delStatusStr = $this->deleteTaxon(); 
			if($statusStr) $delStatusStr .= $statusStr;
			return $delStatusStr;
		}
	}

	public function deleteTaxon(){
		$statusStr = '';
		//Specimen images
		$sql ='UPDATE images SET tid = NULL WHERE occid IS NOT NULL AND tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR setting tid to NULL for occurrence images in deleteTaxon method ('.$this->conn->error.')<br/>';
		}
		$sql ='DELETE FROM images WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting remaining links in deleteTaxon method ('.$this->conn->error.')<br/>';
		}
		
		//Vernaculars
		$sql ='DELETE FROM taxavernaculars WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting vernaculars in deleteTaxon method ('.$this->conn->error.')<br/>';
		}
		
		//Text Descriptions
		$sql ='DELETE FROM taxadescrblock WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting taxa description blocks in deleteTaxon method ('.$this->conn->error.')<br/>';
		}

		//Occurrences
		$sql = 'UPDATE omoccurrences SET tidinterpreted = NULL WHERE tidinterpreted = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR setting tidinterpreted to NULL in deleteTaxon method ('.$this->conn->error.')<br/>';
		}
		
		//Vouchers
		$sql ='DELETE FROM fmvouchers WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting voucher links in deleteTaxon method ('.$this->conn->error.')<br/>';
		}
		
		//Checklists
		$sql ='DELETE FROM fmchklsttaxalink WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting checklist links in deleteTaxon method ('.$this->conn->error.')<br/>';
		}
		
		//Key descriptions
		$sql ='DELETE FROM kmdescr WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting morphology for ID Key in deleteTaxon method ('.$this->conn->error.')<br/>';
		}

		//Taxon links
		$sql ='DELETE FROM taxalinks WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)){
			$statusStr .= 'ERROR deleting taxa links in deleteTaxon method ('.$this->conn->error.')<br/>';
		}

		//Get taxon status details so if taxa removal fails, we can still initiate old name
		$taxStatusArr = array();
		$sqlTS = 'SELECT tidaccepted, parenttid, uppertaxonomy, family, unacceptabilityreason, notes, sortsequence '.
			'FROM taxstatus WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sqlTS);
		if($r = $rs->fetch_object()){
			$taxStatusArr[0]['tidaccepted'] = $r->tidaccepted;
			$taxStatusArr[0]['parenttid'] = $r->parenttid;
			$taxStatusArr[0]['uppertaxonomy'] = $r->uppertaxonomy;
			$taxStatusArr[0]['family'] = $r->family;
			$taxStatusArr[0]['unacceptabilityreason'] = $r->unacceptabilityreason;
			$taxStatusArr[0]['notes'] = $r->notes;
			$taxStatusArr[0]['sortsequence'] = $r->sortsequence;
		}
		$rs->free();

		//Delete taxon
		$statusStrFinal = 'SUCCESS: taxon deleted!<br/>';
		$sql ='DELETE FROM taxstatus WHERE tid = '.$this->tid;
		if($this->conn->query($sql)){
			//Delete taxon
			$sql ='DELETE FROM taxa WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)){
				$statusStrFinal = 'ERROR attempting to delete taxon: '.$this->conn->error.'<br/>';
				//Reinstate taxstatus record
				$tsNewSql = 'INSERT INTO taxstatus(tid,taxauthid,tidaccepted, parenttid, uppertaxonomy, family, unacceptabilityreason, notes, sortsequence) '.
					'VALUES('.$this->tid.','.$this->taxAuthId.','.$taxStatusArr[0]['tidaccepted'].','.$taxStatusArr[0]['parenttid'].',"'.
					$taxStatusArr[0]['uppertaxonomy'].'","'.$taxStatusArr[0]['family'].'","'.$taxStatusArr[0]['unacceptabilityreason'].'","'.
					$taxStatusArr[0]['unacceptabilityreason'].'",'.$taxStatusArr[0]['sortsequence'].')';
				$this->conn->query($tsNewSql);
			}
		}
		else{
			$statusStrFinal = 'ERROR attempting to delete taxon status: '.$this->conn->error.'<br/>';
		}

		if($statusStr){
			$statusStrFinal .= $statusStr;
		}
		return $statusStrFinal;
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

	public function getHierarchyArr(){
		$retArr = array();
		$sql = 'SELECT t.tid, t.sciname FROM taxa t ';
		if($this->hierarchyArr){
			$sql .= 'WHERE (t.tid IN('.implode(',',$this->hierarchyArr).')) ';
		}
		else{
			return $retArr;
		}
		$sql .= 'ORDER BY t.rankid, t.sciname ';
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			$retArr[$row->tid] = $row->sciname;
		}
		$rs->close();
		return $retArr;
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

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>