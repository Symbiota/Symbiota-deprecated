<?php
/*
 * Rebuilt 29 Jan 2010
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistManager {

	private $clCon;
	private $clid;
	private $dynClid;
	private $clName;
	private $clMetaData = Array();
	private $language = "English";
	private $voucherArr = Array();
	private $thesFilter = 0;
	private $taxonFilter;
	private $showAuthors;
	private $showCommon;
	private $showImages;
	private $showVouchers;
	private $searchCommon;
	private $searchSynonyms;
	private $filterArr = Array();
	private $imageLimit = 100;
	private $taxaLimit = 500;
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	private $editable = false;
	
	function __construct() {
		$this->clCon = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->clCon === false)) $this->clCon->close();
	}

	public function echoFilterList(){
		echo "'".implode("',\n'",$this->filterArr)."'";
	}
	
	public function echoSpeciesAddList(){
		$sql = "SELECT DISTINCT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 ";
		if($this->taxonFilter){
			$sql .= "AND t.rankid > 140 AND (ts.family = '".$this->taxonFilter."' OR t.sciname LIKE '".$this->taxonFilter."%') ";
		}
		else{
			$sql .= "AND (t.rankid = 140 OR t.rankid = 180) ";
		}
		$sql .= "ORDER BY t.sciname";
		//echo $sql;
		$result = $this->clCon->query($sql);
        while ($row = $result->fetch_object()){
        	if($this->taxonFilter){
        		echo "<option value='".$row->tid."'>".$row->sciname."</option>\n";
        	}
        	else{
        		echo "<option>".$row->sciname."</option>\n";
        	}
       	}
	}
	
	public function addNewSpecies($dataArr){
		$insertStatus = false;
		$colSql = "";
		$valueSql = "";
		foreach($dataArr as $k =>$v){
			$colSql .= ",".$k;
			if($v){
				$valueSql .= ",'".$v."'";
			}
			else{
				$valueSql .= ",NULL";
			}
		}
		$sql = "INSERT INTO fmchklsttaxalink (clid".$colSql.") ".
			"VALUES (".$this->clid.$valueSql.")";
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		if($con->query($sql)){
			$insertStatus = true;
		}
		$con->close();
		return $insertStatus;
	}
	
	public function setClValue($clValue){
		if(is_numeric($clValue)){
			$this->clid = $clValue;
		}
		else{
			$sql = "SELECT c.clid FROM fmchecklists c WHERE (c.Name = '".$clValue."')";
			$rs = $this->clCon->query($sql);
			if($row = $rs->fetch_object()){
				$this->clid = $row->clid;
			}
		}
	}

	public function setDynClid($did){
		$this->dynClid = $did;
	}
	
	public function getClMetaData($fieldName = ""){
		if(!$this->clMetaData){
			$this->setClMetaData();
		}
		if($fieldName){
			return $this->clMetaData[$fieldName];
		}
		return $this->clMetaData;
	}
	
	private function setClMetaData(){
		$sql = "";
		if($this->clid){
			$sql = "SELECT c.clid, c.name, c.locality, c.publication, ".
				"c.abstract, c.authors, c.parentclid, c.notes, ".
				"c.latcentroid, c.longcentroid, c.pointradiusmeters, c.access, ".
				"c.datelastmodified, c.uid, c.initialtimestamp ".
				"FROM fmchecklists c WHERE c.clid = ".$this->clid;
		}
		elseif($this->dynClid){
			$sql = "SELECT c.dynclid AS clid, c.name, c.details AS locality, c.notes, c.uid, c.initialtimestamp ".
				"FROM fmdynamicchecklists c WHERE c.dynclid = ".$this->dynClid;
		}
 		$result = $this->clCon->query($sql);
		if($row = $result->fetch_object()){
			$this->clName = $row->name;
			$this->clMetaData["locality"] = $row->locality; 
			$this->clMetaData["notes"] = $row->notes;
			if($this->clid){
				$this->clMetaData["publication"] = $row->publication;
				$this->clMetaData["abstract"] = $row->abstract;
				$this->clMetaData["authors"] = $row->authors;
				$this->clMetaData["parentclid"] = $row->parentclid;
				$this->clMetaData["uid"] = $row->uid;
				$this->clMetaData["latcentroid"] = $row->latcentroid;
				$this->clMetaData["longcentroid"] = $row->longcentroid;
				$this->clMetaData["pointradiusmeters"] = $row->pointradiusmeters;
				$this->clMetaData["access"] = $row->access;
				$this->clMetaData["datelastmodified"] = $row->datelastmodified;
			}
    	}
    	$result->close();
	}
	
	public function editMetaData($editArr){
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ", ".$key." = \"".$value."\"";
			}
			else{
				$setSql .= ", ".$key." = NULL";
			}
		}
		$sql = "UPDATE fmchecklists SET ".substr($setSql,2)." WHERE clid = ".$this->clid;
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		$con->query($sql);
		$con->close();
	}
	
	public function echoEditorList(){
		$sql = "SELECT FROM users";
	}

	public function getTaxonAuthorityList(){
    	$taxonAuthList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
 		$rs = $this->clCon->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $row->name;
		}
		$rs->close();
		return $taxonAuthList;
	}

	//return an array: family => array(TID => sciName)
	public function getTaxaList($pageNumber = 0){
		if($this->showImages) return $this->getTaxaImageList($pageNumber);
		//Get list that shows which taxa have vouchers; note that dynclid list won't have vouchers
		if($this->showVouchers){
			$vSql = "SELECT DISTINCT v.tid, v.occid, v.collector, v.notes FROM fmvouchers v WHERE (v.CLID = $this->clid)";
	 		$vResult = $this->clCon->query($vSql);
			while ($row = $vResult->fetch_object()){
				$this->voucherArr[$row->tid][] = "<a style='cursor:pointer' onclick=\"openPopup('../collections/individual/individual.php?occid=".$row->occid."','individwindow')\">".$row->collector."</a>\n";
			}
			$vResult->close();
		}
		//Get species list
		$sql = $this->getClSql();
		$result = $this->clCon->query($sql);
		$taxaList = Array();
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		while($row = $result->fetch_object()){
			$this->filterArr[$row->uppertaxonomy] = "";
			$family = strtoupper($row->family);
			$this->filterArr[$family] = "";
			$tid = $row->tid;
			$sciName = $row->sciname;
			$taxonTokens = explode(" ",$sciName);
			if(in_array("x",$taxonTokens) || in_array("X",$taxonTokens)){
				if(in_array("x",$taxonTokens)) unset($taxonTokens[array_search("x",$taxonTokens)]);
				if(in_array("X",$taxonTokens)) unset($taxonTokens[array_search("X",$taxonTokens)]);
				$newArr = array();
				foreach($taxonTokens as $v){
					$newArr[] = $v;
				}
				$taxonTokens = $newArr;
			}
			if($this->taxaCount >= ($pageNumber*$this->taxaLimit) && $this->taxaCount <= ($pageNumber+1)*$this->taxaLimit){
				if(count($taxonTokens) == 1) $sciName .= " sp.";
				if($this->showVouchers){
					$clStr = "";
					if($row->habitat) $clStr = ", ".$row->habitat;
					if($row->abundance) $clStr .= ", ".$row->abundance;
					if($row->notes) $clStr .= ", ".$row->notes;
					if($row->source) $clStr .= ", <u>source</u>: ".$row->source;
					if(array_key_exists($tid,$this->voucherArr)){
						$clStr .= "; ".(is_array($this->voucherArr[$tid])?implode(", ",$this->voucherArr[$tid]):$this->voucherArr[$tid]);
					}
					if($clStr){
						$this->voucherArr[$tid] = substr($clStr,1);
					}
				}
				$author = $row->author;
				$sciName = "<i><b>".$sciName."</b></i> ";
				if($this->showAuthors) $sciName .= $author;
				if($this->showCommon && $row->vernacularname) $sciName .= "<br />&nbsp;&nbsp;&nbsp;<b>[".$row->vernacularname."]</b>"; 
				$taxaList[$family][$tid] = $sciName;
    		}
    		if($family != $familyPrev) $this->familyCount++;
    		$familyPrev = $family;
    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
			$this->filterArr[$taxonTokens[0]] = "";
    		$genusPrev = $taxonTokens[0];
    		if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev){
    			$this->speciesCount++;
    			$speciesPrev = $taxonTokens[0]." ".$taxonTokens[1];
    		}
    		if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
    			$this->taxaCount++;
    		}
    		$taxonPrev = implode(" ",$taxonTokens);
		}
		$this->filterArr = array_keys($this->filterArr);
		sort($this->filterArr);
		$result->close();
		if($this->taxaCount < ($pageNumber*$this->taxaLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaList(0);
		}
		return $taxaList;
	}

	private function getTaxaImageList($pageNumber){
		//Get species list
		$sql = "";
		if($this->clid){
			if($this->thesFilter){
				$sql = "SELECT DISTINCT ts.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, ".
					"t.sciname, t.author, imgs.url, imgs.thumbnailurl ".
					"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
					"INNER JOIN fmchklsttaxalink ctl ON ctl.tid = ts.tid) ".
					"LEFT JOIN (SELECT DISTINCT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
					"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
					"WHERE ts2.taxauthid = $this->thesFilter AND ti.sortsequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ".
	    	  		"WHERE ctl.clid = ".$this->clid." AND ts.taxauthid = ".$this->thesFilter;
			}
			else{
				$sql = "SELECT DISTINCT t.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, ".
					"t.sciname, t.author, imgs.url, imgs.thumbnailurl ".
					"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
					"INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid) ".
					"LEFT JOIN (SELECT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
					"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
					"WHERE ts2.taxauthid = 1 AND ti.sortsequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ".
					"WHERE (ts.taxauthid = 1) AND ctl.clid = ".$this->clid;
			}
		}
		else{
			if($this->thesFilter > 1){
				$sql = "SELECT DISTINCT ts.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author, imgs.url, imgs.thumbnailurl ".
					"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
					"INNER JOIN fmdyncltaxalink ctl ON ctl.tid = ts.tid) ".
					"LEFT JOIN (SELECT DISTINCT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
					"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
					"WHERE ts2.taxauthid = $this->thesFilter AND ti.sortsequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ".
	    	  		"WHERE ctl.dynclid = ".$this->dynClid." AND ts.taxauthid = ".$this->thesFilter;
			}
			else{
				$sql = "SELECT DISTINCT t.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author, imgs.url, imgs.thumbnailurl ".
					"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
					"INNER JOIN fmdyncltaxalink ctl ON ctl.tid = t.tid) ".
					"LEFT JOIN (SELECT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
					"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
					"WHERE ts2.taxauthid = 1 AND ti.sortsequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ".
					"WHERE (ts.taxauthid = 1) AND ctl.dynclid = ".$this->dynClid;
			}
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$sql .= " AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE v.vernacularname LIKE '%".$this->taxonFilter."%')) ";
			}
			else{
				$sql .= " AND ((ts.uppertaxonomy = '".$this->taxonFilter."') ";
				if($this->clid){
					$sql .= "OR (IFNULL(ctl.familyoverride,ts.family) = '".$this->taxonFilter."') ";
				}
				else{
					$sql .= "OR (family = '".$this->taxonFilter."') ";
				}
				if($this->searchSynonyms){
					$sql .= "OR (t.tid IN(SELECT tsb.tid FROM (taxa ta INNER JOIN taxstatus tsa ON ta.tid = tsa.tid) ".
						"INNER JOIN taxstatus tsb ON tsa.tidaccepted = tsb.tidaccepted ".
						"WHERE (tsa.uppertaxonomy = '".$this->taxonFilter."') OR (ta.sciname Like '".$this->taxonFilter."%')))) ";
				}
				else{
					$sql .= "OR (t.sciname Like '".$this->taxonFilter."%')) ";
				}
			}
		}
		if($this->showCommon){
			$sql = "SELECT DISTINCT it.tid, it.uppertaxonomy, it.family, it.sciname, it.author, ".
				"v.vernacularname, imgs.url, imgs.thumbnailurl ".
				"FROM ((".$sql.") it INNER JOIN taxstatus ts ON it.tid=ts.tid) ".
				"LEFT JOIN (SELECT vern.tid, vern.vernacularname FROM taxavernaculars vern WHERE vern.language = '".$this->language.
				"' AND vern.sortsequence = 1) v ON ts.tidaccepted = v.tid ".
				"LEFT JOIN (SELECT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
				"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
				"WHERE ts2.taxauthid = 1 AND ti.sortsequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ";
		}
		$sql .= " ORDER BY family, sciname";
		//echo $sql;
		$result = $this->clCon->query($sql);
		$taxaList = Array();$upperTaxArr = Array();
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		while ($row = $result->fetch_object()){
			$upperTaxArr[$row->uppertaxonomy] = "";
			$family = strtoupper($row->family);
			$tid = $row->tid;
			$sciName = $row->sciname;
			$taxonTokens = explode(" ",$sciName);
			if(in_array("x",$taxonTokens) || in_array("X",$taxonTokens)){
				if(in_array("x",$taxonTokens)) unset($taxonTokens[array_search("x",$taxonTokens)]);
				if(in_array("X",$taxonTokens)) unset($taxonTokens[array_search("X",$taxonTokens)]);
				$newArr = array();
				foreach($taxonTokens as $v){
					$newArr[] = $v;
				}
				$taxonTokens = $newArr;
			}
			if($this->taxaCount >= ($pageNumber*$this->imageLimit) && $this->taxaCount < ($pageNumber+1)*$this->imageLimit){
				if(count($taxonTokens) == 1) $sciName .= " sp.";
				$author = $row->author;
				$sciName = "<i>".$sciName."</i> ";
				if($this->showAuthors) $sciName .= $author;
				if($this->showCommon && $row->vernacularname) $sciName .= "<br /><b>[".$row->vernacularname."]</b>";
				$taxaList[$family][$tid]["sciname"] = $sciName;
				$taxaList[$family][$tid]["url"] = $row->url;
				$taxaList[$family][$tid]["tnurl"] = $row->thumbnailurl;
			}
    		if($family != $familyPrev) $this->familyCount++;
    		$familyPrev = $family;
    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
    		$genusPrev = $taxonTokens[0];
    		if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev) $this->speciesCount++;
    		$speciesPrev = $taxonTokens[0]." ".(count($taxonTokens) > 1?$taxonTokens[1]:"");
    		if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
    			$this->taxaCount++;
    		}
    		$taxonPrev = implode(" ",$taxonTokens);
		}
		$result->close();
		ksort($upperTaxArr);
		$this->filterArr = array_merge(array_keys($this->filterArr),array_keys($taxaList));
		if($this->taxaCount < ($pageNumber*$this->imageLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaImageList(0);
		}
		return $taxaList;
	}

    public function downloadChecklistCsv(){
    	$sql = $this->getClSql();
		//Output checklist
    	$fileName = $this->clName."_".time().".csv";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		//echo $sql;
		$result = $this->clCon->query($sql);
		//Write column names out to file
		if($result){
			$hasVernacular = (stripos($sql,"vernacularname")?true:false);
			echo "Family,ScientificName,ScientificNameAuthorship,";
			echo ($hasVernacular?"CommonName,":"")."TaxonId\n";
			while($row = $result->fetch_object()){
				echo "\"".$row->family."\",\"".$row->sciname."\",\"".$row->author."\",";
				echo ($hasVernacular?"\"".$row->vernacularname."\",":"")."\"".$row->tid."\"\n";
			}
		}
		else{
			echo "Recordset is empty.\n";
		}
        $result->close();
    }

	private function getClSql(){
		$sql = "";
		if($this->clid){
			if($this->thesFilter){
				$sql = "SELECT DISTINCT ts.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, ". 
					"t.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source ".
					"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
					"INNER JOIN fmchklsttaxalink ctl ON ctl.tid = ts.tid ".
	    	  		"WHERE ctl.clid = ".$this->clid." AND ts.taxauthid = ".$this->thesFilter;
			} 
			else{
				$sql = "SELECT DISTINCT t.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, ".
					"t.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source ".
					"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
					"INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid ".
	    	  		"WHERE (ts.taxauthid = 1) AND ctl.clid = ".$this->clid;
			}
		}
		else{
			if($this->thesFilter > 1){
				$sql = "SELECT ts.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author ".
					"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
					"INNER JOIN fmdyncltaxalink ctl ON ctl.tid = ts.tid ".
	    	  		"WHERE ctl.dynclid = ".$this->dynClid." AND ts.taxauthid = ".$this->thesFilter;
			}
			else{
				$sql = "SELECT t.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author ".
					"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
					"INNER JOIN fmdyncltaxalink ctl ON ctl.tid = t.tid ".
	    	  		"WHERE (ts.taxauthid = 1) AND ctl.dynclid = ".$this->dynClid;
			}
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$sql .= " AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE v.vernacularname LIKE '%".$this->taxonFilter."%')) ";
			}
			else{
				$sql .= " AND ((ts.uppertaxonomy = '".$this->taxonFilter."') ";
				if($this->clid){
					$sql .= "OR (IFNULL(ctl.familyoverride,ts.family) = '".$this->taxonFilter."') ";
				}
				else{
					$sql .= "OR (family = '".$this->taxonFilter."') ";
				}
				if($this->searchSynonyms){
					$sql .= "OR (t.tid IN(SELECT tsb.tid FROM (taxa ta INNER JOIN taxstatus tsa ON ta.tid = tsa.tid) ".
						"INNER JOIN taxstatus tsb ON tsa.tidaccepted = tsb.tidaccepted ".
						"WHERE (tsa.uppertaxonomy = '".$this->taxonFilter."') OR (ta.sciname Like '".$this->taxonFilter."%')))) ";
				}
				else{
					$sql .= "OR (t.SciName Like '".$this->taxonFilter."%')) ";
				}
			}
		}
		if($this->showCommon){
			if($this->clid){
				$sql = "SELECT DISTINCT it.tid, it.uppertaxonomy, it.family, v.vernacularname, it.sciname, it.author, ".
					"it.habitat, it.abundance, it.notes, it.source ".
					"FROM ((".$sql.") it INNER JOIN taxstatus ts ON it.tid = ts.tid) ".
					"LEFT JOIN (SELECT vern.tid, vern.vernacularname FROM taxavernaculars vern WHERE vern.language = '".$this->language.
					"' AND vern.sortsequence = 1) v ON ts.tidaccepted = v.tid WHERE ts.taxauthid = 1";
			}
			else{
				$sql = "SELECT DISTINCT it.tid, it.uppertaxonomy, it.family, it.sciname, it.author, v.vernacularname ".
					"FROM ((".$sql.") it INNER JOIN taxstatus ts ON it.tid = ts.tid) ".
					"LEFT JOIN (SELECT vern.tid, vern.vernacularname FROM taxavernaculars vern WHERE vern.language = '".$this->language.
					"' AND vern.sortsequence = 1) v ON ts.tidaccepted = v.tid WHERE ts.taxauthid = 1";
			}
		}
		$sql .= " ORDER BY family, sciname";
		//echo $sql;
		return $sql;
	}
    
    public function setThesFilter($filt){
		$this->thesFilter = $filt;
	}

	public function getThesFilter(){
		return $this->thesFilter;
	}

	public function setTaxonFilter($tFilter){
		$this->taxonFilter = $tFilter;
	}
	
	public function setShowAuthors($value = 1){
		$this->showAuthors = $value;
	}

	public function setShowCommon($value = 1){
		$this->showCommon = $value;
	}

	public function setShowImages($value = 1){
		$this->showImages = $value;
	}

	public function setShowVouchers($value = 1){
		$this->showVouchers = $value;
	}

	public function setSearchCommon($value = 1){
		$this->searchCommon = $value;
	}

	public function setSearchSynonyms($value = 1){
		$this->searchSynonyms = $value;
	}

	public function getClid(){
		return $this->clid;
	}

	public function getClName(){
		return $this->clName;
	}
	
	public function setLanguage($l){
		$this->language = $l;
	}
	
	public function setImageLimit($cnt){
		$this->imageLimit = $cnt;
	}
	
	public function getImageLimit(){
		return $this->imageLimit;
	}
	
	public function setTaxaLimit($cnt){
		$this->taxaLimit = $cnt;
	}
	
	public function getTaxaLimit(){
		return $this->taxaLimit;
	}
	
	public function setEditable($e){
		$this->editable = $e;
	}
	
	public function getEditable(){
		return $this->editable;
	}
	
	public function getVoucherArr(){
		return $this->voucherArr;
	}
	
	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function getFamilyCount(){
		return $this->familyCount;
	}

	public function getGenusCount(){
		return $this->genusCount;
	}

	public function getSpeciesCount(){
		return $this->speciesCount;
	}

	public function echoParentSelect(){
		$sql = "SELECT c.clid, c.name FROM fmchecklists c ORDER BY c.name";
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."' ".($this->clMetaData["parentclid"]==$row->clid?" selected":"").">".$row->name."</option>";
		}
		$rs->close();
	}
}
?>
 