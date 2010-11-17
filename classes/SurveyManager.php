<?php
/*
 * Created on May 16, 2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
include_once($serverRoot.'/config/dbconnection.php');

class SurveyManager {

	private $conn;
	private $surveyId;
	private $surveyName;
	private $metaData = Array();
	private $language = "English";
	private $thesFilter = 1;
	private $taxonFilter;
	private $showAuthors;
	private $showCommon;
	private $showImages;
	private $searchCommon;
	private $searchSynonyms;
	private $sqlBase = "";

	private $imageLimit = 100;
	private $taxaLimit = 500;
	
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	
	function __construct($sId) {
		$this->surveyId = $sId;
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function echoFilterList(){
		$sql = "SELECT DISTINCT ts.uppertaxonomy, ts.family, t.unitname1 ".
			"FROM ((omsurveyoccurlink sol INNER JOIN omoccurrences o ON sol.occid = o.occid) ".
			"INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid) ".
			"INNER JOIN taxa t ON ts.tidaccepted = t.tid ".
			"WHERE sol.surveyid = ".$this->surveyId." AND ts.taxauthid = ".$this->thesFilter." ";
		//echo $sql;
		$uArr = Array(); $fArr = Array(); $gArr = Array(); 
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$uArr[$row->uppertaxonomy] = "";
			$fArr[$row->family] = "";
			$gArr[] = $row->unitname1;
		}
		$rs->close();
		echo "'".implode("',\n'",array_keys($uArr))."',\n";
		echo "'".implode("',\n'",array_keys($fArr))."',\n";
		echo "'".implode("',\n'",$gArr)."'";
	}
	
	public function getMetaData(){
		if(!$this->metaData){
			$sql = "SELECT s.projectname, s.locality, s.managers, s.latcentroid, s.longcentroid, s.notes ".
				"FROM omsurveys s WHERE s.surveyid = ".$this->surveyId;
	 		$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->surveyName = $row->projectname;
				$this->metaData["locality"] = $row->locality;
				$this->metaData["managers"] = $row->managers;
				$this->metaData["latcentroid"] = $row->latcentroid;
				$this->metaData["longcentroid"] = $row->longcentroid;
				$this->metaData["notes"] = $row->notes;
	    	}
	    	$result->close();
		}
		return $this->metaData;
	}
	
	public function editMetaData($editArr){
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ", ".$key." = '".$value."'";
			}
			else{
				$setSql .= ", ".$key." = NULL";
			}
		}
		$sql = "UPDATE omsurveys SET ".substr($setSql,2)." WHERE surveyid = ".$this->surveyId;
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
 		$rs = $this->conn->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $row->name;
		}
		$rs->close();
		return $taxonAuthList;
	}

	//return an array: [family][sciname][occid] => collector
	public function getTaxaList($pageNumber = 0){
		$sql = $this->getClSql();
		//echo $sql;
		$result = $this->conn->query($sql);
		$itemLimit = ($this->showImages?$this->imageLimit:$this->taxaLimit);
		$taxaArr = Array();
		$activeTids = Array();
		$genusPrev="";$speciesPrev="";$taxonPrev="";$tidPrev=0;
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			$tid = $row->tid;
			if($tid != $tidPrev){
				$sciName = $row->sciname;
				$taxonTokens = explode(" ",$sciName);
				if(strtolower($taxonTokens[0]) == "x") array_shift($taxonTokens);
				if(count($taxonTokens) > 1 && strtolower($taxonTokens[1]) == "x") array_splice($taxonTokens,1,1);

				if($this->taxaCount >= ($pageNumber*$itemLimit) && $this->taxaCount <= ($pageNumber+1)*$itemLimit){
					//taxaCount is within range for being displayed
					if(count($taxonTokens) == 1) $sciName .= " sp.";
					$taxaArr[$family][$tid]["sciname"] = $sciName;
					if($this->showAuthors) $taxaArr[$family][$tid]["author"] = $row->author; 
					if($this->showCommon && $row->vernacularname) $taxaArr[$family][$tid]["vern"] = $row->vernacularname;
					$activeTids[] = $tid;
				}
				
	    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
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
			$tidPrev = $tid;
	    	if(array_key_exists($family,$taxaArr) && array_key_exists($tid,$taxaArr[$family])){
				$taxaArr[$family][$tid]["vs"][$row->occid] = $row->collstr;
	    	}
	    }
		$result->close();
		$this->familyCount = count($taxaArr);
		
		//User is asking for too high of a page number, thus return first page only
		if(($pageNumber*$itemLimit) > $this->taxaCount){
			$this->taxaCount = 0;
			return $this->getTaxaList(0);
		}
		//Grab images, if requested
		if($this->showImages && $activeTids){
			$imgArr = Array();
			$sql = "SELECT DISTINCT ts2.tid, i.url, i.thumbnailurl ".
				"FROM (images i INNER JOIN taxstatus ts1 ON i.tid = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted ".
				"WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND i.sortsequence = 1 AND ts1.tid IN (".implode(",",$activeTids).")";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$imgArr[$row->tid]["url"] = ($row->thumbnailurl?$row->thumbnailurl:$row->url); 
			}
			$rs->close();
			foreach($taxaArr as $family => $tidArr){
				foreach($tidArr as $t => $v){
					if(array_key_exists($t,$imgArr)){
						$taxaArr[$family][$t]["url"] = $imgArr[$t]["url"];
					} 
				}
			}
		}
		return $taxaArr;
	}

    public function downloadChecklistCsv(){
    	$sql = $this->getClSql();
		$hasVernacular = (stripos($sql,"vernacularname")?true:false);
    	$sql = "SELECT DISTINCT family, tid, sciname, author ".($hasVernacular?", vernacularname ":"").
    		"FROM (".$this->getClSql().") inntab";
		//echo $sql;
    	//Output checklist
    	$fileName = $this->surveyName."_".time().".csv";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
			echo "Family,ScientificName,ScientificNameAuthorship,";
			echo ($hasVernacular?"CommonName,":"")."TaxonId\n";
			while($row = $result->fetch_object()){
				echo "\"".$row->family."\",\"".$row->sciname."\",\"".$row->author."\",";
				echo ($hasVernacular?"\"".$row->vernacularname."\",":"")."\"".$row->tid."\"\n";
			}
        	$result->close();
		}
		else{
			echo "Recordset is empty.\n";
		}
    }

	private function getClSql(){
		$sql = "SELECT ts.family, t.tid, t.sciname, t.author, o.occid, CONCAT_WS(' ',o.recordedby,o.recordnumber) as collstr ";
		if($this->showCommon) $sql .= ", v.vernacularname ";
		$sql .= "FROM (((omsurveyoccurlink sol INNER JOIN omoccurrences o ON sol.occid = o.occid) ".
			"INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid) ".
			"INNER JOIN taxa t ON ts.tidaccepted = t.tid) ";
		if($this->showCommon){
			$sql .= "LEFT JOIN (SELECT vern.tid, vern.vernacularname FROM taxavernaculars vern WHERE vern.Language = '".$this->language.
				"' AND vern.SortSequence = 1) v ON t.Tid = v.tid ";
		}
		$sql .= "WHERE sol.surveyid = ".$this->surveyId." AND ts.taxauthid = ".$this->thesFilter." ";
		if($this->taxonFilter){
			if($this->searchCommon){
				$sql .= "AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE v.VernacularName LIKE '%".$this->taxonFilter."%')) ";
			}
			else{
				$sql .= "AND (ts.UpperTaxonomy = '".$this->taxonFilter."' OR t.SciName Like '".$this->taxonFilter."%' ".
					"OR ts.Family = '".$this->taxonFilter."' ";
				if($this->searchSynonyms){
					$sql .= "OR (t.tid IN(SELECT tsa.tidaccepted FROM taxstatus tsa INNER JOIN taxa ta ON tsa.tid = ta.tid ".
						"WHERE ta.SciName Like '".$this->taxonFilter."%'))";
				}
				$sql .= ")";
			}
		}
		$sql .= " ORDER BY ts.family, t.SciName";
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

	public function setSearchCommon($value = 1){
		$this->searchCommon = $value;
		if($value && $this->taxonFilter) $this->showCommon = 1;
	}

	public function setSearchSynonyms($value = 1){
		$this->searchSynonyms = $value;
	}

	public function getSurveyId(){
		return $this->surveyId;
	}

	public function getSurveyName(){
		return $this->surveyName;
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
}
?>
 