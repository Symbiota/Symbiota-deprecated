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
	private $source;
	private $notes;
	private $hierarchy;
	private $securityStatus;
	private $isAccepted = -1;			// 1 = accepted, 0 = not accepted, -1 = not assigned, -2 in conflict
	private $acceptedArr = Array();
	private $synonymArr = Array();

	function __construct($target) {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
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
			$this->parentName = "<i>".$row->sciname."</i> ".$row->author;
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

	public function echoUpperTaxonomySelect(){
		$sql = "SELECT DISTINCT ts.uppertaxonomy FROM taxstatus ts ".
			"WHERE ts.taxauthid = ".$this->taxAuthId." AND ts.uppertaxonomy IS NOT NULL ORDER BY ts.uppertaxonomy ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option ".($this->upperTaxon==$row->uppertaxonomy?"SELECTED":"").">".$row->uppertaxonomy."</option>\n";
		}
		$rs->close();
	}  

	public function echoFamilySelect(){
		$sql = "SELECT t.unitname1 FROM taxa t ".
			"WHERE t.rankid = 140 ORDER BY t.unitname1 ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option ".($this->family==$row->unitname1?"SELECTED":"").">".$row->unitname1."</option>\n";
		}
		$rs->close();
	}

	public function echoParentTidSelect(){
		$sql = "SELECT t.tid, t.sciname ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid = ts.tidaccepted) ";
		if($this->rankId < 220){
			$sql .= "AND (t.rankid < ".$this->rankId.") ";
		}
		elseif($this->rankId == 220){
			$sql .= "AND (t.rankid = 180) AND (t.unitname1 = '".$this->unitName1."') ";
		}
		elseif($this->rankId > 220 && $this->family){
			$sql .= "AND (t.rankid = 220) AND (t.unitname1 = '".$this->unitName1."') ";
		}
		$sql .= "ORDER BY t.sciname ";
		//echo $sql;
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->tid."' ".($this->parentTid==$row->tid?"SELECTED":"").">".$row->sciname."</option>\n";
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

	public function echoAcceptedTaxaSelect(){
		$sql = "SELECT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = ".$this->taxAuthId." AND ts.tid = ts.tidaccepted ";
		if($this->family){
			$sql .= "AND ts.family = '".$this->family."' ";
		}
		if($this->rankId < 220){
			$sql .= "AND t.rankid < 220 ";
		}
		else{
			$sql .= "AND t.rankid >= 220 ";
		}
		$sql .= "ORDER BY t.sciname ";
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->tid."'>".$row->sciname."</option>\n";
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
		$con = MySQLiConnectionFactory::getCon("write");
		$status = $con->query($sql);
		$con->close();
		
		return $status;
	}
	
	public function submitTaxstatusEdits($tsArr){
		//See if parent changed
		$currentParentTid = 0;
		$sqlParent = "SELECT ts.parenttid FROM taxstatus ts WHERE ts.tid = ".$tsArr["tid"];
		$rs = $this->conn->query($sqlParent);
		if($row = $rs->fetch_object()){
			$currentParentTid = $row->parenttid;
		}
		$rs->close();
		
		$sql = "UPDATE taxstatus ".
			"SET family = '".trim($tsArr["family"])."',uppertaxonomy = '".trim($tsArr["uppertaxonomy"])."', parenttid = ".$tsArr["parenttid"]." ".
			"WHERE taxauthid = ".$this->taxAuthId." AND tid = ".$tsArr["tid"]." AND tidaccepted = ".$tsArr["tidaccepted"];
		$con = MySQLiConnectionFactory::getCon("write");
		$status = $con->query($sql);
		$con->close();
		
		if($currentParentTid != $tsArr["parenttid"]){
			$this->rebuildHierarchy($tsArr["tid"]);
		}
		
		return $status;
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
		$con = MySQLiConnectionFactory::getCon("write");
		$status = $con->query($sql);
		$con->close();
		return $status;
	}
	
	public function submitAddAcceptedLink($tid, $tidAcc, $deleteOther = true){
		$con = MySQLiConnectionFactory::getCon("write");
		
		$upperTax = "";$family = "";$parentTid = 0;$hierarchyStr = "";
		$sqlFam = "SELECT ts.uppertaxonomy, ts.family, ts.parenttid, ts.hierarchystr ".
			"FROM taxstatus ts WHERE ts.tid = $tid AND ts.taxauthid = ".$this->taxAuthId;
		$rs = $con->query($sqlFam);
		if($row = $rs->fetch_object()){
			$upperTax = $row->uppertaxonomy;
			$family = $row->family;
			$parentTid = $row->parenttid;
			$hierarchyStr = $row->hierarchystr;
		}
		$rs->close();
		
		if($deleteOther){
			$sqlDel = "DELETE FROM taxstatus WHERE tid = $tid AND taxauthid = ".$this->taxAuthId;
			$con->query($sqlDel);
		}
		$sql = "INSERT INTO taxstatus (tid,tidaccepted,taxauthid,uppertaxonomy,family,parenttid,hierarchystr) ".
			"VALUES ($tid, $tidAcc, $this->taxAuthId,".($upperTax?"\"".$upperTax."\"":"NULL").",".
			($family?"\"".$family."\"":"NULL").",".$parentTid.",'".$hierarchyStr."') ";
		//echo $sql;
		$status = $con->query($sql);
		$con->close();
		return $status;
	}
	
	public function submitChangeToAccepted($tid,$tidAccepted,$switchAccpetance = true){
		$con = MySQLiConnectionFactory::getCon("write");
		
		$sql = "UPDATE taxstatus SET tidaccepted = $tid WHERE tid = $tid AND taxauthid = $this->taxAuthId";
		$status = $con->query($sql);

		if($switchAccpetance){
			$sqlSwitch = "UPDATE taxstatus SET tidaccepted = $tid WHERE tidaccepted = $tidAccepted AND taxauthid = $this->taxAuthId";
			$status = $con->query($sqlSwitch);
			
			$this->updateDependentData($tidAccepted,$tid);
		}
		$con->close();
		return $status;
	}
	
	public function submitChangeToNotAccepted($tid,$tidAccepted){
		$con = MySQLiConnectionFactory::getCon("write");
		
		//Change subject taxon to Not Accepted
		$sql = "UPDATE taxstatus SET tidaccepted = $tidAccepted WHERE tid = $tid AND taxauthid = $this->taxAuthId";
		$status = $con->query($sql);

		//Switch synonyms of subject to Accepted Taxon 
		$sqlSyns = "UPDATE taxstatus SET tidaccepted = $tidAccepted WHERE tidaccepted = $tid AND taxauthid = $this->taxAuthId";
		$status = $con->query($sqlSyns);
		
		$con->close();
		
		$this->updateDependentData($tid,$tidAccepted);
		
		return $status;
	}
	
	public function rebuildHierarchy($tid){
		$parentArr = Array();
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.tid = ".$targetTid;
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
		if($parentArr){
			$con = MySQLiConnectionFactory::getCon("write");
			$sqlInsert = "UPDATE taxstatus ts SET ts.hierarchystr = '".$hierarchyStr."' WHERE ts.tid = ".$tid;
			$con->query($sqlInsert);
			$con->close();
		}
	}

	public function updateDependentData($tid, $tidNew){
		//method to update descr, vernaculars,
		$con = MySQLiConnectionFactory::getCon("write");

		$con->query("UPDATE fmdescr SET tid = ".$tidNew." WHERE tid = ".$tid);
		$con->query("DELETE FROM fmdescr WHERE tid = ".$tid);
		
		$sqlVerns = "UPDATE taxavernaculars SET tid = ".$tidNew." WHERE tid = ".$tid;
		$con->query($sqlVerns);
		
		$sqlTest = "SELECT tid FROM taxadescriptions WHERE tid = ".$tidNew;
		$rsTest = $con->query($sqlTest);
		if($rsTest->num_rows == 0){
			$sqltd = "UPDATE taxadescriptions SET tid = ".$tidNew." WHERE tid = ".$tid;
			$con->query($sqltd);
		}
		
		$sqltl = "UPDATE taxalinks SET tid = ".$tidNew." WHERE tid = ".$tid;
		$con->query($sqltl);
		
		$con->close();
		
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