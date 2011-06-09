<?php
/*
 * Rebuilt 29 Jan 2010
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistManager {

	private $clCnn;
	private $clid;
	private $dynClid;
	private $clName;
	private $taxaList = Array();
	private $clMetaData = Array();
	private $language = "English";
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
	private $sqlFrag;
	private $editable = false;
	
	function __construct() {
		$this->clCon = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->clCon === false)) $this->clCon->close();
	}

	public function echoFilterList(){
		echo "'".implode("','",$this->filterArr)."'";
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
		$colSql = '';
		$valueSql = '';
		foreach($dataArr as $k =>$v){
			$colSql .= ','.$k;
			if($v){
				$valueSql .= ',"'.$this->cleanStr($v).'"';
			}
			else{
				$valueSql .= ',NULL';
			}
		}
		$sql = 'INSERT INTO fmchklsttaxalink (clid'.$colSql.') '.
			'VALUES ('.$this->clid.$valueSql.')';
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
			$sql = 'SELECT c.clid FROM fmchecklists c WHERE (c.Name = "'.$clValue.'")';
			$rs = $this->clCon->query($sql);
			if($row = $rs->fetch_object()){
				$this->clid = $row->clid;
			}
		}
	}

	public function setDynClid($did){
		$this->dynClid = $did;
	}
	
	public function getClMetaData($fieldName = ''){
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
				"c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp ".
				"FROM fmchecklists c WHERE c.clid = ".$this->clid;
		}
		elseif($this->dynClid){
			$sql = "SELECT c.dynclid AS clid, c.name, c.details AS locality, c.notes, c.uid, c.type, c.initialtimestamp ".
				"FROM fmdynamicchecklists c WHERE c.dynclid = ".$this->dynClid;
		}
 		$result = $this->clCon->query($sql);
		if($row = $result->fetch_object()){
			$this->clName = $row->name;
			$this->clMetaData["locality"] = $row->locality; 
			$this->clMetaData["notes"] = $row->notes;
			$this->clMetaData["type"] = $row->type;
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
				$this->clMetaData["dynamicsql"] = $row->dynamicsql;
				$this->clMetaData["datelastmodified"] = $row->datelastmodified;
			}
    	}
    	$result->close();
	}
	
	public function editMetaData($editArr){
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ', '.$key.' = "'.$this->cleanStr($value).'"';
			}
			else{
				$setSql .= ', '.$key.' = NULL';
			}
		}
		$sql = 'UPDATE fmchecklists SET '.substr($setSql,2).' WHERE clid = '.$this->clid;
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
		//Get list that shows which taxa have vouchers; note that dynclid list won't have vouchers
		$voucherArr = Array();
		if($this->showVouchers){
			$vSql = 'SELECT DISTINCT v.tid, v.occid, v.collector, v.notes FROM fmvouchers v WHERE v.clid = '.$this->clid;
	 		$vResult = $this->clCon->query($vSql);
			while ($row = $vResult->fetch_object()){
				$voucherArr[$row->tid][$row->occid] = $row->collector;
				//$this->voucherArr[$row->tid][] = "<a style='cursor:pointer' onclick=\"openPopup('../collections/individual/index.php?occid=".
				//	$row->occid."','individwindow')\">".$row->collector."</a>\n";
			}
			$vResult->close();
		}
		//Get species list
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		$tidReturn = Array();
		$retLimit = ($this->showImages?$this->imageLimit:$this->taxaLimit);
		$sql = $this->getClSql();
		$result = $this->clCon->query($sql);
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
			if($this->taxaCount >= ($pageNumber*$retLimit) && $this->taxaCount <= ($pageNumber+1)*$retLimit){
				if(count($taxonTokens) == 1) $sciName .= " sp.";
				if($this->showVouchers){
					$clStr = "";
					if($row->habitat) $clStr = ", ".$row->habitat;
					if($row->abundance) $clStr .= ", ".$row->abundance;
					if($row->notes) $clStr .= ", ".$row->notes;
					if($row->source) $clStr .= ", <u>source</u>: ".$row->source;
					if($clStr) $this->taxaList[$tid]["notes"] = substr($clStr,2);
					if(array_key_exists($tid,$voucherArr)){
						$this->taxaList[$tid]["vouchers"] = $voucherArr[$tid];  
					}
				}
				$this->taxaList[$tid]["sciname"] = $sciName;
				$this->taxaList[$tid]["family"] = $family;
				$tidReturn[] = $tid;
				if($this->showAuthors){
					$this->taxaList[$tid]["author"] = $row->author;
				}
				if($this->showCommon && $row->vernacularname){
					$this->taxaList[$tid]["vern"] = $row->vernacularname;
				}
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
		if($this->taxaCount < ($pageNumber*$retLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaList(0);
		}
		if($this->showImages) $this->setImages($tidReturn);
		return $this->taxaList;
	}
	
	private function setImages($tidReturn){
		$sql = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
			'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '. 
			'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN images i ON ts2.tid = i.tid '.
			'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts1.tid IN('.implode(',',$tidReturn).') '.
			'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
		//echo $sql;
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$this->taxaList[$row->tid]["url"] = $row->url;
			$this->taxaList[$row->tid]["tnurl"] = $row->thumbnailurl;
		}
		$rs->close();
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
				$sql = "SELECT DISTINCT t.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, ". 
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
				$sql = "SELECT t.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author ".
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

	//Voucher Maintenance functions
	public function getDynamicSql(){
		if(!$this->sqlFrag){
			$sql = "SELECT c.dynamicsql FROM fmchecklists c WHERE c.clid = ".$this->clid;
			//echo $sql;
			$rs = $this->clCon->query($sql);
			while($row = $rs->fetch_object()){
				$this->sqlFrag = $row->dynamicsql;
			}
			$rs->close();
		}
		return $this->sqlFrag;
	}
	
	public function saveSql($sqlFragArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sqlFrag = "";
		if(array_key_exists('country',$sqlFragArr)){
			$sqlFrag = 'AND (o.country = "'.$this->cleanStr($sqlFragArr['country']).'") ';
		}
		if(array_key_exists('state',$sqlFragArr)){
			$sqlFrag .= 'AND (o.stateprovince = "'.$this->cleanStr($sqlFragArr['state']).'") ';
		}
		if(array_key_exists('county',$sqlFragArr)){
			$sqlFrag .= 'AND (o.county LIKE "%'.$this->cleanStr($sqlFragArr['county']).'%") ';
		}
		if(array_key_exists('locality',$sqlFragArr)){
			$sqlFrag .= 'AND (o.locality LIKE "%'.$this->cleanStr($sqlFragArr['locality']).'%") ';
		}
		$llStr = '';
		if(array_key_exists('latnorth',$sqlFragArr) && array_key_exists('latsouth',$sqlFragArr)){
			$llStr .= 'AND (o.decimallatitude BETWEEN '.$conn->real_escape_string($sqlFragArr['latsouth']).
			' AND '.$conn->real_escape_string($sqlFragArr['latnorth']).') ';
		}
		if(array_key_exists('lngwest',$sqlFragArr) && array_key_exists('lngeast',$sqlFragArr)){
			$llStr .= 'AND (o.decimallongitude BETWEEN '.$conn->real_escape_string($sqlFragArr['lngwest']).
			' AND '.$conn->real_escape_string($sqlFragArr['lngeast']).') ';
		}
		if($sqlFragArr['latlngor']) $llStr = 'OR ('.trim(substr($llStr,3)).')';
		$sqlFrag .= $llStr;
		if($sqlFrag){
			$sql = "UPDATE fmchecklists c SET c.dynamicsql = '".trim(substr($sqlFrag,3))."' WHERE c.clid = ".$conn->real_escape_string($this->clid);
			//echo $sql;
			$conn->query($sql);
		}
		$conn->close();
	}

	public function getVoucherCnt(){
		$vCnt = 0;
		$sql = 'SELECT count(*) AS vcnt FROM fmvouchers WHERE clid = '.$this->clid;
		$rs = $this->clCon->query($sql);
		while($r = $rs->fetch_object()){
			$vCnt = $r->vcnt;
		}
		$rs->close();
		return $vCnt;
	}

	public function getNonVoucheredCnt(){
		$uvCnt = 0;
		$sql = 'SELECT count(t.tid) AS uvcnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
			'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
			'WHERE v.clid IS NULL AND ctl.clid = '.$this->clid.' AND ts.taxauthid = 1 ';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$uvCnt = $row->uvcnt;
		}
		$rs->close();
		return $uvCnt;
	}

	public function getNonVoucheredTaxa($startLimit){
		$retArr = Array();
		$sql = 'SELECT t.tid, ts.family, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
			'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
			'WHERE v.clid IS NULL AND ctl.clid = '.$this->clid.' AND ts.taxauthid = 1 '.
			'ORDER BY ts.family, t.sciname '.
			'LIMIT '.($startLimit?$startLimit.',':'').'100';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->family][$row->tid] = $row->sciname;
		}
		$rs->close();
		return $retArr;
	}

	public function getConflictVouchers(){
		$retArr = Array();
		$sql = 'SELECT t.tid, t.sciname AS listid, o.recordedby, o.recordnumber, o.sciname, o.identifiedby, o.dateidentified '.
			'FROM taxstatus ts1 INNER JOIN omoccurrences o ON ts1.tid = o.tidinterpreted '.
			'INNER JOIN fmvouchers v ON o.occid = v.occid '.
			'INNER JOIN taxstatus ts2 ON v.tid = ts2.tid '.
			'INNER JOIN taxa t ON v.tid = t.tid '.
			'WHERE v.clid = '.$this->clid.' AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts1.tidaccepted <> ts2.tidaccepted '.
			'ORDER BY t.sciname ';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->tid]['listid'] = $row->listid;
			$collStr = $row->recordedby;
			if($row->recordnumber) $collStr .= ' ('.$row->recordnumber.')';
			$retArr[$row->tid]['recordnumber'] = $collStr;
			$retArr[$row->tid]['specid'] = $row->sciname;
			$idBy = $row->identifiedby;
			if($row->dateidentified) $idBy .= ' ('.$row->dateidentified.')';
			$retArr[$row->tid]['identifiedby'] = $idBy;
		}
		$rs->close();
		return $retArr;
	}

	public function getMissingTaxa($startLimit){
		$retArr = Array();
		if($this->sqlFrag){
			$sql = 'SELECT DISTINCT o.tidinterpreted, o.sciname FROM omoccurrences o LEFT JOIN '.
				'(SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
				'WHERE ctl.clid = '.$this->clid.' AND ts1.taxauthid = 1 AND ts2.taxauthid = 1) intab ON o.tidinterpreted = intab.tid '.
				'INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'WHERE t.rankid >= 220 AND intab.tid IS NULL AND '.
				'('.$this->sqlFrag.') '.
				'ORDER BY o.sciname '.
				'LIMIT '.($startLimit?$startLimit.',':'').'100';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->clCon->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[$row->tidinterpreted] = $row->sciname;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function hasChildrenChecklists(){
		$hasChildren = false;
		$sql = 'SELECT count(*) AS clcnt FROM fmchecklists WHERE parentclid = '.$this->clid;
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			if($row->clcnt > 0) $hasChildren = true;
		}
		$rs->close();
		return $hasChildren;
	}

	public function getChildTaxa(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT t.tid, t.sciname, c.name '.
			'FROM taxa t INNER JOIN fmchklsttaxalink ctl1 ON t.tid = ctl1.tid '.
			'INNER JOIN fmchecklists c ON ctl1.clid = c.clid '.
			'LEFT JOIN (SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid WHERE ctl.clid = '.$this->clid.') intab ON ctl1.tid = intab.tid '.
			'WHERE c.parentclid = '.$this->clid.' AND intab.tid IS NULL '.
			'ORDER BY t.sciname';
		$rs = $this->clCon->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid]['sciname'] = $r->sciname;
			$retArr[$r->tid]['cl'] = $r->name;
		}
		$rs->close();
		return $retArr;
	}

	//Misc set/get functions
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
		$sql = 'SELECT c.clid, c.name FROM fmchecklists c WHERE type = "static" AND access <> "private" ORDER BY c.name';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."' ".($this->clMetaData["parentclid"]==$row->clid?" selected":"").">".$row->name."</option>";
		}
		$rs->close();
	}

	private function cleanStr($str){
 		$newStr = trim($str);
 		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
 		$newStr = str_replace('"',"'",$newStr);
 		$newStr = $this->clCon->real_escape_string($newStr);
 		return $newStr;
 	}
}
?>
 