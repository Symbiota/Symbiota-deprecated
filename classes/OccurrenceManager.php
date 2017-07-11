<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

class OccurrenceManager{

	protected $conn;
	protected $taxaArr = Array();
	private $taxaSearchType;
	protected $searchTermsArr = Array();
	protected $localSearchArr = Array();
	protected $reset = 0;
	private $clName;
	private $collArrIndex = 0;
	private $occurSearchProjectExists = 0;

 	public function __construct($readVariables = true){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		if(array_key_exists("reset",$_REQUEST) && $_REQUEST["reset"])  $this->reset();
		if($readVariables) $this->readRequestVariables();
 	}

	public function __destruct(){
 		if(!($this->conn === false)){
 			$this->conn->close();
 			$this->conn = null;
 		}
	}

	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
	}

	public function reset(){
		global $clientRoot;
		$domainName = $_SERVER['HTTP_HOST'];
		if(!$domainName) $domainName = $_SERVER['SERVER_NAME'];
 		$this->reset = 1;
		if(isset($this->searchTermsArr['db']) || isset($this->searchTermsArr['oic'])){
			//reset all other search terms except maintain the db terms
			$dbsTemp = "";
			if(isset($this->searchTermsArr['db'])) $dbsTemp = $this->searchTermsArr["db"];
			$clidTemp = "";
			if(isset($this->searchTermsArr['clid'])) $clidTemp = $this->searchTermsArr["clid"];
			unset($this->searchTermsArr);
			if($dbsTemp) $this->searchTermsArr["db"] = $dbsTemp;
			if($clidTemp) $this->searchTermsArr["clid"] = $clidTemp;
		}
	}

	public function getSearchTerms(){
		return $this->searchTermsArr;
	}

	public function getSearchTerm($k){
		if(array_key_exists($k,$this->searchTermsArr)){
			return $this->searchTermsArr[$k];
		}
		else{
			return "";
		}
	}

	public function getSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists('clid',$this->searchTermsArr)){
			$sqlWhere .= "AND (v.clid IN(".$this->searchTermsArr['clid'].")) ";
		}
		elseif(array_key_exists("db",$this->searchTermsArr) && $this->searchTermsArr['db']){
			//Do nothing if db = all
			if($this->searchTermsArr['db'] != 'all'){
				if($this->searchTermsArr['db'] == 'allspec'){
					$sqlWhere .= 'AND (o.collid IN(SELECT collid FROM omcollections WHERE colltype = "Preserved Specimens")) ';
				}
				elseif($this->searchTermsArr['db'] == 'allobs'){
					$sqlWhere .= 'AND (o.collid IN(SELECT collid FROM omcollections WHERE colltype IN("General Observations","Observations"))) ';
				}
				else{
					$dbArr = explode(';',$this->searchTermsArr["db"]);
					$dbStr = '';
					if(isset($dbArr[0]) && $dbArr[0]){
						$dbStr = "(o.collid IN(".trim($dbArr[0]).")) ";
					}
					if(isset($dbArr[1]) && $dbArr[1]){
						//$dbStr .= ($dbStr?'OR ':'').'(o.CollID IN(SELECT collid FROM omcollcatlink WHERE (ccpk IN('.$dbArr[1].')))) ';
					}
					$sqlWhere .= 'AND ('.$dbStr.') ';
				}
			}
		}

		if(array_key_exists("taxa",$this->searchTermsArr)){
			$sqlWhereTaxa = "";
			$useThes = (array_key_exists("usethes",$this->searchTermsArr)?$this->searchTermsArr["usethes"]:0);
			$this->taxaSearchType = $this->searchTermsArr["taxontype"];
			$taxaArr = explode(";",trim($this->searchTermsArr["taxa"]));
			//Set scientific name
			$this->taxaArr = Array();
			foreach($taxaArr as $sName){
				$this->taxaArr[trim($sName)] = Array();
			}
			if($this->taxaSearchType == 5){
				//Common name search
				$this->setSciNamesByVerns();
			}
			else{
				if($useThes){
					$this->setSynonyms();
				}
			}

			//Build sql
			foreach($this->taxaArr as $key => $valueArray){
				if($this->taxaSearchType == 4){
					//Class, order, or other higher rank
					$rs1 = $this->conn->query("SELECT ts.tidaccepted FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid WHERE (t.sciname = '".$key."')");
					if($r1 = $rs1->fetch_object()){
						$sqlWhereTaxa = 'OR ((o.sciname = "'.$key.'") OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid IN('.$r1->tidaccepted.')))) ';
					}
				}
				else{
					if($this->taxaSearchType == 5){
						$famArr = array();
						if(array_key_exists("families",$valueArray)){
							$famArr = $valueArray["families"];
						}
						if(array_key_exists("tid",$valueArray)){
							$tidArr = $valueArray['tid'];
							$sql = 'SELECT DISTINCT t.sciname '.
								'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
								'WHERE t.rankid = 140 AND e.taxauthid = 1 AND e.parenttid IN('.implode(',',$tidArr).')';
							$rs = $this->conn->query($sql);
							while($r = $rs->fetch_object()){
								$famArr[] = $r->family;
							}
						}
						if($famArr){
							$famArr = array_unique($famArr);
							$sqlWhereTaxa .= 'OR (o.family IN("'.implode('","',$famArr).'")) ';
						}
						if(array_key_exists("scinames",$valueArray)){
							foreach($valueArray["scinames"] as $sciName){
								$sqlWhereTaxa .= "OR (o.sciname Like '".$sciName."%') ";
							}
						}
						//echo $sqlWhereTaxa; exit;
					}
					else{
						if($this->taxaSearchType == 2 || ($this->taxaSearchType == 1 && (strtolower(substr($key,-5)) == "aceae" || strtolower(substr($key,-4)) == "idae"))){
							$sqlWhereTaxa .= "OR (o.family = '".$key."') ";
						}
						if($this->taxaSearchType == 3 || ($this->taxaSearchType == 1 && strtolower(substr($key,-5)) != "aceae" && strtolower(substr($key,-4)) != "idae")){
							$sqlWhereTaxa .= "OR (o.sciname LIKE '".$key."%') ";
						}
					}
					if(array_key_exists("synonyms",$valueArray)){
						$synArr = $valueArray["synonyms"];
						if($synArr){
							if($this->taxaSearchType == 1 || $this->taxaSearchType == 2 || $this->taxaSearchType == 5){
								foreach($synArr as $synTid => $sciName){
									if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
										$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
									}
								}
							}
							$sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',array_keys($synArr)).')) ';
						}
						/*
						foreach($synArr as $sciName){
							if($this->taxaSearchType == 1 || $this->taxaSearchType == 2 || $this->taxaSearchType == 5){
								$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
							}
							if($this->taxaSearchType == 2){
								$sqlWhereTaxa .= "OR (o.sciname = '".$sciName."') ";
							}
							else{
								$sqlWhereTaxa .= "OR (o.sciname Like '".$sciName."%') ";
							}
						}
						*/
					}
				}
			}
			$sqlWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
		}

		if(array_key_exists("country",$this->searchTermsArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermsArr["country"]);
			$countryArr = explode(";",$searchStr);
			$tempArr = Array();
			foreach($countryArr as $k => $value){
				if($value == 'NULL'){
					$countryArr[$k] = 'Country IS NULL';
					$tempArr[] = '(o.Country IS NULL)';
				}
				else{
					$tempArr[] = '(o.Country = "'.trim($value).'")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->localSearchArr[] = implode(' OR ',$countryArr);
		}
		if(array_key_exists("state",$this->searchTermsArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermsArr["state"]);
			$stateAr = explode(";",$searchStr);
			$tempArr = Array();
			foreach($stateAr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.StateProvince IS NULL)';
					$stateAr[$k] = 'State IS NULL';
				}
				else{
					$tempArr[] = '(o.StateProvince = "'.trim($value).'")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->localSearchArr[] = implode(' OR ',$stateAr);
		}
		if(array_key_exists("county",$this->searchTermsArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermsArr["county"]);
			$countyArr = explode(";",$searchStr);
			$tempArr = Array();
			foreach($countyArr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.county IS NULL)';
					$countyArr[$k] = 'County IS NULL';
				}
				else{
					$value = trim(str_ireplace(' county',' ',$value));
					$tempArr[] = '(o.county LIKE "'.trim($value).'%")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->localSearchArr[] = implode(' OR ',$countyArr);
		}
		if(array_key_exists("local",$this->searchTermsArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermsArr["local"]);
			$localArr = explode(";",$searchStr);
			$tempArr = Array();
			foreach($localArr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.locality IS NULL)';
					$localArr[$k] = 'Locality IS NULL';
				}
				else{
					$tempArr[] = '(o.municipality LIKE "'.trim($value).'%" OR o.Locality LIKE "%'.trim($value).'%")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->localSearchArr[] = implode(' OR ',$localArr);
		}
		if(array_key_exists("elevlow",$this->searchTermsArr) || array_key_exists("elevhigh",$this->searchTermsArr)){
			$elevlow = 0;
			$elevhigh = 30000;
			if (array_key_exists("elevlow",$this->searchTermsArr))  { $elevlow = $this->searchTermsArr["elevlow"]; }
			if (array_key_exists("elevhigh",$this->searchTermsArr))  { $elevhigh = $this->searchTermsArr["elevhigh"]; }
			$tempArr = Array();
			$sqlWhere .= "AND ( " .
						 "	  ( minimumElevationInMeters >= $elevlow AND maximumElevationInMeters <= $elevhigh ) OR " .
						 "	  ( maximumElevationInMeters is null AND minimumElevationInMeters >= $elevlow AND minimumElevationInMeters <= $elevhigh ) ".
						 "	) ";
		}
		if(array_key_exists("llbound",$this->searchTermsArr)){
			$llboundArr = explode(";",$this->searchTermsArr["llbound"]);
			if(count($llboundArr) == 4){
				$sqlWhere .= "AND (o.DecimalLatitude BETWEEN ".$llboundArr[1]." AND ".$llboundArr[0]." AND ".
					"o.DecimalLongitude BETWEEN ".$llboundArr[2]." AND ".$llboundArr[3].") ";
				$this->localSearchArr[] = "Lat: >".$llboundArr[1].", <".$llboundArr[0]."; Long: >".$llboundArr[2].", <".$llboundArr[3];
			}
		}
		if(array_key_exists("llpoint",$this->searchTermsArr)){
			$pointArr = explode(";",$this->searchTermsArr["llpoint"]);
			if(count($pointArr) == 3){
				//Formula approximates a bounding box; bounding box is for efficiency, will test practicality of doing a radius query in future
				$latRadius = $pointArr[2] / 69.1;
				$longRadius = cos($pointArr[0]/57.3)*($pointArr[2]/69.1);
				$lat1 = $pointArr[0] - $latRadius;
				$lat2 = $pointArr[0] + $latRadius;
				$long1 = $pointArr[1] - $longRadius;
				$long2 = $pointArr[1] + $longRadius;
				$sqlWhere .= "AND ((o.DecimalLatitude BETWEEN ".$lat1." AND ".$lat2.") AND ".
					"(o.DecimalLongitude BETWEEN ".$long1." AND ".$long2.")) ";
			}
			$this->localSearchArr[] = "Point radius: ".$pointArr[0].", ".$pointArr[1].", within ".$pointArr[2]." miles";
		}
		if(array_key_exists("collector",$this->searchTermsArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermsArr["collector"]);
			$collectorArr = explode(";",$searchStr);
			$tempArr = Array();
			if(count($collectorArr) == 1){
				if($collectorArr[0] == 'NULL'){
					$tempArr[] = '(o.recordedBy IS NULL)';
					$collectorArr[] = 'Collector IS NULL';
				}
				else{
					$tempInnerArr = array();
					$collValueArr = explode(" ",trim($collectorArr[0]));
					foreach($collValueArr as $collV){
						if(strlen($collV) < 4 || strtolower($collV) == 'best'){
							//Need to avoid FULLTEXT stopwords interfering with return
							$tempInnerArr[] = '(o.recordedBy LIKE "%'.$collV.'%")';
						}
						else{
							$tempInnerArr[] = '(MATCH(f.recordedby) AGAINST("'.$collV.'")) ';
						}
					}
					$tempArr[] = implode(' AND ', $tempInnerArr);
				}
			}
			elseif(count($collectorArr) > 1){
				$collStr = current($collectorArr);
				if(strlen($collStr) < 4 || strtolower($collStr) == 'best'){
					//Need to avoid FULLTEXT stopwords interfering with return
					$tempInnerArr[] = '(o.recordedBy LIKE "%'.$collStr.'%")';
				}
				else{
					$tempArr[] = '(MATCH(f.recordedby) AGAINST("'.$collStr.'")) ';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->localSearchArr[] = implode(', ',$collectorArr);
		}
		if(array_key_exists("collnum",$this->searchTermsArr)){
			$collNumArr = explode(";",$this->searchTermsArr["collnum"]);
			$rnWhere = '';
			foreach($collNumArr as $v){
				$v = trim($v);
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$rnIsNum = true;
						$rnWhere .= 'OR (o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
					}
					else{
						if(strlen($term2) > strlen($term1)) $term1 = str_pad($term1,strlen($term2),"0",STR_PAD_LEFT);
						$catTerm = '(o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'")';
						$catTerm .= ' AND (length(o.recordnumber) <= '.strlen($term2).')';
						$rnWhere .= 'OR ('.$catTerm.')';
					}
				}
				else{
					$rnWhere .= 'OR (o.recordNumber = "'.$v.'") ';
				}
			}
			if($rnWhere){
				$sqlWhere .= "AND (".substr($rnWhere,3).") ";
				$this->localSearchArr[] = implode(", ",$collNumArr);
			}
		}
		if(array_key_exists('eventdate1',$this->searchTermsArr)){
			$dateArr = array();
			if(strpos($this->searchTermsArr['eventdate1'],' to ')){
				$dateArr = explode(' to ',$this->searchTermsArr['eventdate1']);
			}
			elseif(strpos($this->searchTermsArr['eventdate1'],' - ')){
				$dateArr = explode(' - ',$this->searchTermsArr['eventdate1']);
			}
			else{
				$dateArr[] = $this->searchTermsArr['eventdate1'];
				if(isset($this->searchTermsArr['eventdate2'])){
					$dateArr[] = $this->searchTermsArr['eventdate2'];
				}
			}
			if($dateArr[0] == 'NULL'){
				$sqlWhere .= 'AND (o.eventdate IS NULL) ';
				$this->localSearchArr[] = 'Date IS NULL';
			}
			elseif($eDate1 = $this->formatDate($dateArr[0])){
				$eDate2 = (count($dateArr)>1?$this->formatDate($dateArr[1]):'');
				if($eDate2){
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.$eDate1.'" AND "'.$eDate2.'") ';
				}
				else{
					if(substr($eDate1,-5) == '00-00'){
						$sqlWhere .= 'AND (o.eventdate LIKE "'.substr($eDate1,0,5).'%") ';
					}
					elseif(substr($eDate1,-2) == '00'){
						$sqlWhere .= 'AND (o.eventdate LIKE "'.substr($eDate1,0,8).'%") ';
					}
					else{
						$sqlWhere .= 'AND (o.eventdate = "'.$eDate1.'") ';
					}
				}
				$this->localSearchArr[] = $this->searchTermsArr['eventdate1'].(isset($this->searchTermsArr['eventdate2'])?' to '.$this->searchTermsArr['eventdate2']:'');
			}
		}
		if(array_key_exists('catnum',$this->searchTermsArr)){
			$catStr = $this->searchTermsArr['catnum'];
			$includeOtherCatNum = array_key_exists('othercatnum',$this->searchTermsArr)?true:false;

			$catArr = explode(',',str_replace(';',',',$catStr));
			$betweenFrag = array();
			$inFrag = array();
			foreach($catArr as $v){
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$betweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
						if($includeOtherCatNum){
							$betweenFrag[] = '(o.othercatalognumbers BETWEEN '.$term1.' AND '.$term2.')';
						}
					}
					else{
						$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2);
						$betweenFrag[] = '('.$catTerm.')';
						if($includeOtherCatNum){
							$betweenFrag[] = '(o.othercatalognumbers BETWEEN "'.$term1.'" AND "'.$term2.'")';
						}
					}
				}
				else{
					$vStr = trim($v);
					$inFrag[] = $vStr;
					if(is_numeric($vStr) && substr($vStr,0,1) == '0'){
						$inFrag[] = ltrim($vStr,0);
					}
				}
			}
			$catWhere = '';
			if($betweenFrag){
				$catWhere .= 'OR '.implode(' OR ',$betweenFrag);
			}
			if($inFrag){
				$catWhere .= 'OR (o.catalogNumber IN("'.implode('","',$inFrag).'")) ';
				if($includeOtherCatNum){
					$catWhere .= 'OR (o.othercatalognumbers IN("'.implode('","',$inFrag).'")) ';
					if(strlen($inFrag[0]) == 36){
						$guidOccid = $this->queryRecordID($inFrag);
						if($guidOccid){
							$catWhere .= 'OR (o.occid IN('.implode(',',$guidOccid).')) ';
							$catWhere .= 'OR (o.occurrenceID IN("'.implode('","',$inFrag).'")) ';
						}
					}
				}
			}
			$sqlWhere .= 'AND ('.substr($catWhere,3).') ';
			$this->localSearchArr[] = $this->searchTermsArr['catnum'];
		}
		if(array_key_exists("typestatus",$this->searchTermsArr)){
			$sqlWhere .= "AND (o.typestatus IS NOT NULL) ";
			$this->localSearchArr[] = 'is type';
		}
		if(array_key_exists("hasimages",$this->searchTermsArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM images)) ";
			$this->localSearchArr[] = 'has images';
		}
		if(array_key_exists("targetclid",$this->searchTermsArr)){
			$clid = $this->searchTermsArr["targetclid"];
			if(is_numeric($clid)){
				$voucherManager = new ChecklistVoucherAdmin($this->conn);
				$voucherManager->setClid($clid);
				$voucherManager->setCollectionVariables();
				$this->clName = $voucherManager->getClName();
				$sqlWhere .= 'AND ('.$voucherManager->getSqlFrag().') '.
					'AND (o.occid NOT IN(SELECT occid FROM fmvouchers WHERE clid = '.$clid.')) ';
				$this->localSearchArr[] = $voucherManager->getQueryVariableStr();
			}
		}
		$retStr = '';
		if($sqlWhere){
			$retStr = 'WHERE '.substr($sqlWhere,4);
		}
		else{
			//Make the sql valid, but return nothing
			$retStr = 'WHERE o.occid IS NULL ';
		}
		//echo $retStr; exit;
		return $retStr;
	}
	
	private function queryRecordID($idArr){
		$retArr = array();
		if($idArr){
			$sql = 'SELECT occid FROM guidoccurrences WHERE guid IN("'.implode('","', $idArr).'")';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->occid;
			}
			$rs->free();
		}
		return $retArr;
	}
	
    protected function formatDate($inDate){
		$retDate = OccurrenceUtilities::formatDate($inDate);
		return $retDate;
	}

	protected function setSciNamesByVerns(){
		$sql = "SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid ".
			"FROM (taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID) ".
			"INNER JOIN taxa t ON t.TID = ts.tidaccepted ";
		$whereStr = "";
		foreach($this->taxaArr as $key => $value){
			$whereStr .= "OR v.VernacularName = '".$key."' ";
		}
		$sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
		//echo "<div>sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		if($result->num_rows){
			while($row = $result->fetch_object()){
				$vernName = strtolower($row->VernacularName);
				if($row->rankid < 140){
					$this->taxaArr[$vernName]["tid"][] = $row->tid;
				}
				elseif($row->rankid == 140){
					$this->taxaArr[$vernName]["families"][] = $row->sciname;
				}
				else{
					$this->taxaArr[$vernName]["scinames"][] = $row->sciname;
				}
			}
		}
		else{
			$this->taxaArr["no records"]["scinames"][] = "no records";
		}
		$result->free();
	}

	protected function setSynonyms(){
		foreach($this->taxaArr as $key => $value){
			if(array_key_exists("scinames",$value)){
				if(!in_array("no records",$value["scinames"])){
					$synArr = $this->getSynonyms($value["scinames"]);
					if($synArr) $this->taxaArr[$key]["synonyms"] = $synArr;
				}
			}
			else{
				$synArr = $this->getSynonyms($key);
				if($synArr) $this->taxaArr[$key]["synonyms"] = $synArr;
			}
		}
	}

	public function getFullCollectionList($catId = ''){
		if($catId && !is_numeric($catId)) $catId = ''; 
		//Set collection array
		$collIdArr = array();
		$catIdArr = array();
		if(isset($this->searchTermsArr['db']) && array_key_exists('db',$this->searchTermsArr)){
			$cArr = explode(';',$this->searchTermsArr['db']);
			$collIdArr = explode(',',$cArr[0]);
			if(isset($cArr[1])) $catIdStr = $cArr[1];
		}
		//Set collections
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, '.
			'cat.category, cat.icon AS caticon, cat.acronym '.
			'FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid '.
			'LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
			'LEFT JOIN omcollcategories cat ON ccl.ccpk = cat.ccpk '.
			'WHERE s.recordcnt > 0 AND (cat.inclusive IS NULL OR cat.inclusive = 1 OR cat.ccpk = 1) '.
			'ORDER BY ccl.sortsequence, cat.category, c.sortseq, c.CollectionName ';
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$collArr = array();
		while($r = $result->fetch_object()){
			$collType = '';
			if(stripos($r->colltype, "observation") !== false) $collType = 'obs';
			if(stripos($r->colltype, "specimen")) $collType = 'spec';
			if($collType){
				if($r->ccpk){
					if(!isset($collArr[$collType]['cat'][$r->ccpk]['name'])){
						$collArr[$collType]['cat'][$r->ccpk]['name'] = $r->category;
						$collArr[$collType]['cat'][$r->ccpk]['icon'] = $r->caticon;
						$collArr[$collType]['cat'][$r->ccpk]['acronym'] = $r->acronym;
						//if(in_array($r->ccpk,$catIdArr)) $retArr[$collType]['cat'][$catId]['isselected'] = 1;
					}
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["instcode"] = $r->institutioncode;
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["collcode"] = $r->collectioncode;
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["collname"] = $r->collectionname;
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["icon"] = $r->icon;
				}
				else{
					$collArr[$collType]['coll'][$r->collid]["instcode"] = $r->institutioncode;
					$collArr[$collType]['coll'][$r->collid]["collcode"] = $r->collectioncode;
					$collArr[$collType]['coll'][$r->collid]["collname"] = $r->collectionname;
					$collArr[$collType]['coll'][$r->collid]["icon"] = $r->icon;
				}
			}
		}
		$result->free();
		
		$retArr = array();
		//Modify sort so that default catid is first
		if(isset($collArr['spec']['cat'][$catId])){
			$retArr['spec']['cat'][$catId] = $collArr['spec']['cat'][$catId];
			unset($collArr['spec']['cat'][$catId]);
		}
		elseif(isset($collArr['obs']['cat'][$catId])){
			$retArr['obs']['cat'][$catId] = $collArr['obs']['cat'][$catId];
			unset($collArr['obs']['cat'][$catId]);
		}
		foreach($collArr as $t => $tArr){
			foreach($tArr as $g => $gArr){
				foreach($gArr as $id => $idArr){
					$retArr[$t][$g][$id] = $idArr;
				}
			}
		}
		return $retArr;
	}

	public function outputFullCollArr($occArr, $targetCatID = 0){
		global $DEFAULTCATID, $LANG;
        if(!$targetCatID && $DEFAULTCATID) $targetCatID = $DEFAULTCATID;
        $collCnt = 0;
		echo '<div style="position:relative">';
        if(isset($occArr['cat'])){
			$categoryArr = $occArr['cat'];
			?>
			<div style="float:right;margin-top:20px;">
				<input type="submit" class="nextbtn searchcollnextbtn" value="<?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?>"  />
			</div>
			<table style="float:left;width:80%;">
				<?php
				$cnt = 0;
				foreach($categoryArr as $catid => $catArr){
					$name = $catArr['name'];
					if($catArr['acronym']) $name .= ' ('.$catArr['acronym'].')';
					$catIcon = $catArr['icon'];
					unset($catArr['name']);
					unset($catArr['acronym']);
					unset($catArr['icon']);
					$idStr = $this->collArrIndex.'-'.$catid;
					?>
					<tr>
						<td style="<?php echo ($catIcon?'width:40px':''); ?>">
							<?php
							if($catIcon){
								$catIcon = (substr($catIcon,0,6)=='images'?'../':'').$catIcon;
								echo '<img src="'.$catIcon.'" style="border:0px;width:30px;height:30px;" />';
							}
							?>
						</td>
						<td style="padding:6px;width:25px;">
							<input id="cat-<?php echo $idStr; ?>-Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" checked />
						</td>
						<td style="padding:9px 5px;width:10px;">
							<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
								<img id="plus-<?php echo $idStr; ?>" src="../images/plus_sm.png" style="<?php echo ($targetCatID != $catid?'':'display:none;') ?>" /><img id="minus-<?php echo $idStr; ?>" src="../images/minus_sm.png" style="<?php echo ($targetCatID != $catid?'display:none;':'') ?>" />
							</a>
						</td>
						<td style="padding-top:8px;">
							<div class="categorytitle">
								<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
									<?php echo $name; ?>
								</a>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="4">
							<div id="cat-<?php echo $idStr; ?>" style="<?php echo ($targetCatID && $targetCatID != $catid?'display:none;':'') ?>margin:10px;padding:10px 20px;border:inset">
								<table>
									<?php
									foreach($catArr as $collid => $collName2){
										?>
										<tr>
											<td style="width:40px;">
												<?php
												if($collName2["icon"]){
													$cIcon = (substr($collName2["icon"],0,6)=='images'?'../':'').$collName2["icon"];
													?>
													<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'><img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" /></a>
													<?php
												}
												?>
											</td>
											<td style="padding:6px;width:25px;">
												<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat-<?php echo $idStr; ?>-Input')" checked />
											</td>
											<td style="padding:6px">
												<div class="collectiontitle">
													<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'>
														<?php
														$codeStr = ' ('.$collName2['instcode'];
														if($collName2['collcode']) $codeStr .= '-'.$collName2['collcode'];
														$codeStr .= ')';
														echo $collName2["collname"].$codeStr;
														?>
													</a>
													<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
														more info
													</a>
												</div>
											</td>
										</tr>
										<?php
										$collCnt++;
									}
									?>
								</table>
							</div>
						</td>
					</tr>
					<?php
					$cnt++;
				}
				?>
			</table>
			<?php
		}
		if(isset($occArr['coll'])){
			$collArr = $occArr['coll'];
			?>
			<table style="float:left;width:80%;">
				<?php
				foreach($collArr as $collid => $cArr){
					?>
					<tr>
						<td style="<?php ($cArr["icon"]?'width:35px':''); ?>">
							<?php
							if($cArr["icon"]){
								$cIcon = (substr($cArr["icon"],0,6)=='images'?'../':'').$cArr["icon"];
								?>
								<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'><img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" /></a>
								<?php
							}
							?>
							&nbsp;
						</td>
						<td style="padding:6px;width:25px;">
							<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll()" checked />
						</td>
						<td style="padding:6px">
							<div class="collectiontitle">
								<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'>
									<?php
									$codeStr = ' ('.$cArr['instcode'];
									if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
									$codeStr .= ')';
									echo $cArr["collname"].$codeStr;
									?>
								</a>
								<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
									more info
								</a>
							</div>
						</td>
					</tr>
					<?php
					$collCnt++;
				}
				?>
			</table>
			<?php
			if(!isset($occArr['cat'])){
				?>
				<div style="float:right;position:absolute;top:<?php echo count($collArr)*5; ?>px;right:0px;">
					<input type="submit" class="nextbtn searchcollnextbtn" value="<?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?>" />
				</div>
				<?php
			}
			if(count($collArr) > 40){
				?>
				<div style="float:right;position:absolute;top:<?php echo count($collArr)*15; ?>px;right:0px;">
					<input type="submit" class="nextbtn searchcollnextbtn" value="<?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?>" />
				</div>
				<?php
			}
		}
		echo '</div>';
		$this->collArrIndex++;
	}

	public function getCollectionList($collIdArr){
		$retArr = array();
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, cat.category '.
			'FROM omcollections c LEFT JOIN omcollcatlink l ON c.collid = l.collid '.
			'LEFT JOIN omcollcategories cat ON l.ccpk = cat.ccpk '.
			'WHERE c.collid IN('.implode(',',$collIdArr).') ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['instcode'] = $r->institutioncode;
			$retArr[$r->collid]['collcode'] = $r->collectioncode;
			$retArr[$r->collid]['name'] = $r->collectionname;
			$retArr[$r->collid]['icon'] = $r->icon;
			$retArr[$r->collid]['category'] = $r->category;
		}
		$rs->free();
		return $retArr;
	}

	public function getOccurVoucherProjects(){
		$retArr = Array();
		$titleArr = Array();
		$sql = 'SELECT p2.pid AS parentpid, p2.projname as catname, p1.pid, p1.projname, '.
			'c.clid, c.name as clname '.
			'FROM fmprojects p1 INNER JOIN fmprojects p2 ON p1.parentpid = p2.pid '.
			'INNER JOIN fmchklstprojlink cl ON p1.pid = cl.pid '.
			'INNER JOIN fmchecklists c ON cl.clid = c.clid '.
			'WHERE p2.occurrencesearch = 1 AND p1.ispublic = 1 ';
		//echo "<div>$sql</div>";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if(!isset($titleArr['cat'][$r->parentpid])) $titleArr['cat'][$r->parentpid] = $r->catname;
			if(!isset($titleArr['proj'][$r->pid])) $titleArr[$r->parentpid]['proj'][$r->pid] = $r->projname;
			$retArr[$r->pid][$r->clid] = $r->clname;
		}
		$rs->free();
		if($titleArr) $retArr['titles'] = $titleArr;
		return $retArr;
	}

	public function getDatasetSearchStr(){
		$retStr ="";
		if(!array_key_exists('db',$this->searchTermsArr) || $this->searchTermsArr['db'] == 'all'){
			$retStr = "All Collections";
		}
		elseif($this->searchTermsArr['db'] == 'allspec'){
			$retStr = "All Specimen Collections";
		}
		elseif($this->searchTermsArr['db'] == 'allobs'){
			$retStr = "All Observation Projects";
		}
		else{
			$cArr = explode(';',$this->searchTermsArr['db']);
			if($cArr[0]){
				$sql = 'SELECT collid, CONCAT_WS("-",institutioncode,collectioncode) as instcode '.
					'FROM omcollections WHERE collid IN('.$cArr[0].') ORDER BY institutioncode,collectioncode';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retStr .= '; '.$r->instcode;
				}
				$rs->free();
			}
			/*
			if(isset($cArr[1]) && $cArr[1]){
				$sql = 'SELECT ccpk, category FROM omcollcategories WHERE ccpk IN('.$cArr[1].') ORDER BY category';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retStr .= '; '.$r->category;
				}
				$rs->free();
			}
			*/
			$retStr = substr($retStr,2);
		}
		return $retStr;
	}

	public function getTaxaSearchStr(){
		$returnArr = Array();
		foreach($this->taxaArr as $taxonName => $taxonArr){
			$str = $taxonName;
			if(array_key_exists("sciname",$taxonArr)){
				$str .= " => ".implode(",",$taxonArr["sciname"]);
			}
			if(array_key_exists("synonyms",$taxonArr)){
				$str .= " (".implode(",",$taxonArr["synonyms"]).")";
			}
			$returnArr[] = $str;
		}
		return implode("; ", $returnArr);
	}

	public function getLocalSearchStr(){
		return implode("; ", $this->localSearchArr);
	}

    public function getSearchResultUrl(){
        $url = '?';
        $stPieces = Array();
        foreach($this->searchTermsArr as $i => $v){
            if($v){
                $stPieces[] = $i.'='.$v;
            }
        }
        $url .= implode("&",$stPieces);
        $url = str_replace('&taxontype=','&type=',$url);
        $url = str_replace('&usethes=','&thes=',$url);
        $url = str_replace(' ','%20',$url);
        return $url;
    }

	public function getTaxonAuthorityList(){
		$taxonAuthorityList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$taxonAuthorityList[$row->taxauthid] = $row->name;
		}
		return $taxonAuthorityList;
	}

	private function readRequestVariables(){
		global $clientRoot;
		//Search will be confinded to a clid vouchers, collid, catid, or will remain open to all collection
		if(array_key_exists('clid',$_REQUEST)){
			//Limit by checklist voucher links
			$clidIn = $_REQUEST['clid'];
			$clidStr = '';
			if(is_string($clidIn)){
				if(is_numeric($clidIn)){
					$clidStr = $clidIn;
				}
			}
			else{
				$clidStr = $this->conn->real_escape_string(implode(',',array_unique($clidIn)));
			}
			$this->searchTermsArr["clid"] = $clidStr;
			//Since checklist vouchers are being searched, clear colldbs
			$domainName = $_SERVER['HTTP_HOST'];
			if(!$domainName) $domainName = $_SERVER['SERVER_NAME'];
		}
		elseif(array_key_exists("db",$_REQUEST)){
			//Limit collids and/or catids
			$dbStr = '';
			$dbs = $_REQUEST["db"];
			if(is_string($dbs)){
				if(is_numeric($dbs) || $dbs == 'allspec' || $dbs == 'allobs' || $dbs == 'all'){
					$dbStr = $dbs.';';
				}
			}
			else{
				$dbStr = $this->conn->real_escape_string(implode(',',array_unique($dbs))).';';
			}
			if(strpos($dbStr,'allspec') !== false){
				$dbStr = 'allspec';
			}
			elseif(strpos($dbStr,'allobs') !== false){
				$dbStr = 'allobs';
			}
			elseif(strpos($dbStr,'all') !== false){
				$dbStr = 'all';
			}
			if(substr($dbStr,0,3) != 'all' && array_key_exists('cat',$_REQUEST)){
				$catArr = array();
				$catid = $_REQUEST['cat'];
				if(is_string($catid)){
					$catArr = Array($catid);
				}
				else{
					$catArr = $catid;
				}
				if(!$dbStr) $dbStr = ';';
				$dbStr .= $this->conn->real_escape_string(implode(",",$catArr));
			}

			if($dbStr){
				$this->searchTermsArr["db"] = $dbStr;
			}
		}
		if(array_key_exists("taxa",$_REQUEST)){
			$taxa = $this->conn->real_escape_string($_REQUEST["taxa"]);
			$searchType = ((array_key_exists("type",$_REQUEST) && is_numeric($_REQUEST["type"]))?$this->conn->real_escape_string($_REQUEST["type"]):1);
			if($taxa){
				$taxaStr = "";
				if(is_numeric($taxa)){
					$sql = "SELECT t.sciname ".
						"FROM taxa t ".
						"WHERE (t.tid = ".$taxa.')';
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$taxaStr = $row->sciname;
					}
					$rs->free();
				}
				else{
					$taxaStr = str_replace(",",";",$taxa);
					$taxaArr = explode(";",$taxaStr);
					foreach($taxaArr as $key => $sciName){
						$snStr = trim($sciName);
						if($searchType != 5) $snStr = ucfirst($snStr);
						$taxaArr[$key] = $snStr;
					}
					$taxaStr = implode(";",$taxaArr);
				}
				$collTaxa = "taxa:".$taxaStr;
				$this->searchTermsArr["taxa"] = $taxaStr;
				$useThes = array_key_exists("thes",$_REQUEST)?$this->conn->real_escape_string($_REQUEST["thes"]):0;
				if($useThes){
					$collTaxa .= "&usethes:true";
					$this->searchTermsArr["usethes"] = true;
				}
				else{
					$this->searchTermsArr["usethes"] = false;
				}
				if($searchType){
					$collTaxa .= "&taxontype:".$searchType;
					$this->searchTermsArr["taxontype"] = $searchType;
				}
			}
			else{
				unset($this->searchTermsArr["taxa"]);
			}
		}
		$searchArr = Array();
		$searchFieldsActivated = false;
		if(array_key_exists("country",$_REQUEST)){
			$country = $this->conn->real_escape_string($this->cleanSearchQuotes($_REQUEST["country"]));
			if($country){
				$str = str_replace(",",";",$country);
				if(stripos($str, "USA") !== false || stripos($str, "United States") !== false || stripos($str, "U.S.A.") !== false || stripos($str, "United States of America") !== false){
					if(stripos($str, "USA") === false){
						$str .= ";USA";
					}
					if(stripos($str, "United States") === false){
						$str .= ";United States";
					}
					if(stripos($str, "U.S.A.") === false){
						$str .= ";U.S.A.";
					}
					if(stripos($str, "United States of America") === false){
						$str .= ";United States of America";
					}
				}
				$searchArr[] = "country:".$str;
				$this->searchTermsArr["country"] = $str;
			}
			else{
				unset($this->searchTermsArr["country"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("state",$_REQUEST)){
			$state = $this->conn->real_escape_string($this->cleanSearchQuotes($_REQUEST["state"]));
			if($state){
				if(strlen($state) == 2 && (!isset($this->searchTermsArr["country"]) || stripos($this->searchTermsArr["country"],'USA') !== false)){
					$sql = 'SELECT s.statename, c.countryname '.
						'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
						'WHERE c.countryname IN("USA","United States") AND (s.abbrev = "'.$state.'")';
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$state = $r->statename;
					}
					$rs->free();
				}
				$str = str_replace(",",";",$state);
				$searchArr[] = "state:".$str;
				$this->searchTermsArr["state"] = $str;
			}
			else{
				unset($this->searchTermsArr["state"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("county",$_REQUEST)){
			$county = $this->conn->real_escape_string($this->cleanSearchQuotes($_REQUEST["county"]));
			$county = str_ireplace(" Co.","",$county);
			$county = str_ireplace(" County","",$county);
			if($county){
				$str = str_replace(",",";",$county);
				$searchArr[] = "county:".$str;
				$this->searchTermsArr["county"] = $str;
			}
			else{
				unset($this->searchTermsArr["county"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("local",$_REQUEST)){
			$local = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["local"]));
			if($local){
				$str = str_replace(",",";",$local);
				$searchArr[] = "local:".$str;
				$this->searchTermsArr["local"] = $str;
			}
			else{
				unset($this->searchTermsArr["local"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("elevlow",$_REQUEST)){
			if(is_numeric($_REQUEST["elevlow"])){
				$elevlow = $this->cleanInStr($_REQUEST["elevlow"]);
				if($elevlow){
					$str = str_replace(",",";",$elevlow);
					$searchArr[] = "elevlow:".$str;
					$this->searchTermsArr["elevlow"] = $str;
				}
				else{
					unset($this->searchTermsArr["elevlow"]);
				}
				$searchFieldsActivated = true;
			}
		}
		if(array_key_exists("elevhigh",$_REQUEST)){
			if(is_numeric($_REQUEST["elevhigh"])){
				$elevhigh = $this->cleanInStr($_REQUEST["elevhigh"]);
				if($elevhigh){
					$str = str_replace(",",";",$elevhigh);
					$searchArr[] = "elevhigh:".$str;
					$this->searchTermsArr["elevhigh"] = $str;
				}
				else{
					unset($this->searchTermsArr["elevhigh"]);
				}
				$searchFieldsActivated = true;
			}
		}
		if(array_key_exists("collector",$_REQUEST)){
			$collector = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["collector"]));
			if($collector){
				$str = str_replace(",",";",$collector);
				$searchArr[] = "collector:".$str;
				$this->searchTermsArr["collector"] = $str;
			}
			else{
				unset($this->searchTermsArr["collector"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("collnum",$_REQUEST)){
			$collNum = $this->cleanInStr($_REQUEST["collnum"]);
			if($collNum){
				$str = str_replace(",",";",$collNum);
				$searchArr[] = "collnum:".$str;
				$this->searchTermsArr["collnum"] = $str;
			}
			else{
				unset($this->searchTermsArr["collnum"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("eventdate1",$_REQUEST)){
			if($eventDate = $this->cleanInStr($_REQUEST["eventdate1"])){
				$searchArr[] = "eventdate1:".$eventDate;
				$this->searchTermsArr["eventdate1"] = $eventDate;
				if(array_key_exists("eventdate2",$_REQUEST)){
					if($eventDate2 = $this->cleanInStr($_REQUEST["eventdate2"])){
						if($eventDate2 != $eventDate){
							$searchArr[] = "eventdate2:".$eventDate2;
							$this->searchTermsArr["eventdate2"] = $eventDate2;
						}
					}
					else{
						unset($this->searchTermsArr["eventdate2"]);
					}
				}
			}
			else{
				unset($this->searchTermsArr["eventdate1"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("catnum",$_REQUEST)){
			$catNum = $this->cleanInStr($_REQUEST["catnum"]);
			if($catNum){
				$str = str_replace(",",";",$catNum);
				$searchArr[] = "catnum:".$str;
				$this->searchTermsArr["catnum"] = $str;
				if(array_key_exists("includeothercatnum",$_REQUEST)){
					$searchArr[] = "othercatnum:1";
					$this->searchTermsArr["othercatnum"] = '1';
				}
			}
			else{
				unset($this->searchTermsArr["catnum"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("typestatus",$_REQUEST)){
			$typestatus = $_REQUEST["typestatus"];
			if($typestatus){
				$searchArr[] = "typestatus:".$typestatus;
				$this->searchTermsArr["typestatus"] = true;
			}
			else{
				unset($this->searchTermsArr["typestatus"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("hasimages",$_REQUEST)){
			$hasimages = $_REQUEST["hasimages"];
			if($hasimages){
				$searchArr[] = "hasimages:".$hasimages;
				$this->searchTermsArr["hasimages"] = true;
			}
			else{
				unset($this->searchTermsArr["hasimages"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("targetclid",$_REQUEST) && is_numeric($_REQUEST['targetclid'])){
			$searchArr[] = "targetclid:".$_REQUEST["targetclid"];
			$this->searchTermsArr["targetclid"] = $_REQUEST["targetclid"];
			$searchFieldsActivated = true;
		}
		$latLongArr = Array();
		if(array_key_exists("upperlat",$_REQUEST)){
			if(is_numeric($_REQUEST["upperlat"]) && is_numeric($_REQUEST["bottomlat"]) && is_numeric($_REQUEST["leftlong"]) && is_numeric($_REQUEST["rightlong"])){
				$upperLat = $this->conn->real_escape_string($_REQUEST["upperlat"]);
				if($upperLat || $upperLat === "0") $latLongArr[] = $upperLat;

				$bottomlat = $this->conn->real_escape_string($_REQUEST["bottomlat"]);
				if($bottomlat || $bottomlat === "0") $latLongArr[] = $bottomlat;

				$leftLong = $this->conn->real_escape_string($_REQUEST["leftlong"]);
				if($leftLong || $leftLong === "0") $latLongArr[] = $leftLong;

				$rightlong = $this->conn->real_escape_string($_REQUEST["rightlong"]);
				if($rightlong || $rightlong === "0") $latLongArr[] = $rightlong;

				if(count($latLongArr) == 4){
					$searchArr[] = "llbound:".implode(";",$latLongArr);
					$this->searchTermsArr["llbound"] = implode(";",$latLongArr);
				}
				else{
					unset($this->searchTermsArr["llbound"]);
				}
				$searchFieldsActivated = true;
			}
		}
		if(array_key_exists("pointlat",$_REQUEST)){
			if(is_numeric($_REQUEST["pointlat"]) && is_numeric($_REQUEST["pointlong"]) && is_numeric($_REQUEST["radius"])){
				$pointLat = $this->conn->real_escape_string($_REQUEST["pointlat"]);
				if($pointLat || $pointLat === "0") $latLongArr[] = $pointLat;

				$pointLong = $this->conn->real_escape_string($_REQUEST["pointlong"]);
				if($pointLong || $pointLong === "0") $latLongArr[] = $pointLong;

				$radius = $this->conn->real_escape_string($_REQUEST["radius"]);
				if($radius) $latLongArr[] = $radius;
				if(count($latLongArr) == 3){
					$searchArr[] = "llpoint:".implode(";",$latLongArr);
					$this->searchTermsArr["llpoint"] = implode(";",$latLongArr);
				}
				else{
					unset($this->searchTermsArr["llpoint"]);
				}
				$searchFieldsActivated = true;
			}
		}
	}

	//Misc return functions
	private function getSynonyms($searchTarget,$taxAuthId = 1){
		$synArr = array();
		$targetTidArr = array();
		$searchStr = '';
		if(is_array($searchTarget)){
			if(is_numeric(current($searchTarget))){
				$targetTidArr = $searchTarget;
			}
			else{
				$searchStr = implode('","',$searchTarget);
			}
		}
		else{
			if(is_numeric($searchTarget)){
				$targetTidArr[] = $searchTarget;
			}
			else{
				$searchStr = $searchTarget;
			}
		}
		if($searchStr){
			//Input is a string, thus get tids
			$sql1 = 'SELECT tid FROM taxa WHERE sciname IN("'.$searchStr.'")';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$targetTidArr[] = $r1->tid;
			}
			$rs1->free();
		}

		if($targetTidArr){
			//Get acceptd names
			$accArr = array();
			$rankId = 0;
			$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted '.
				'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tid IN('.implode(',',$targetTidArr).')) ';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$accArr[] = $r2->tid;
				$rankId = $r2->rankid;
				//Put in synonym array if not target
				if(!in_array($r2->tid,$targetTidArr)) $synArr[$r2->tid] = $r2->sciname;
			}
			$rs2->free();

			if($accArr){
                //Get synonym that are different than target
                $sql3 = 'SELECT DISTINCT t.tid, t.sciname ' .
                    'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
                    'WHERE (ts.taxauthid = ' . $taxAuthId . ') AND (ts.tidaccepted IN(' . implode('', $accArr) . ')) ';
                $rs3 = $this->conn->query($sql3);
                while ($r3 = $rs3->fetch_object()) {
                    if (!in_array($r3->tid, $targetTidArr)) $synArr[$r3->tid] = $r3->sciname;
                }
                $rs3->free();

                //If rank is 220, get synonyms of accepted children
                if ($rankId == 220) {
                    $sql4 = 'SELECT DISTINCT t.tid, t.sciname ' .
                        'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
                        'WHERE (ts.parenttid IN(' . implode('', $accArr) . ')) AND (ts.taxauthid = ' . $taxAuthId . ') ' .
                        'AND (ts.TidAccepted = ts.tid)';
                    $rs4 = $this->conn->query($sql4);
                    while ($r4 = $rs4->fetch_object()) {
                        $synArr[$r4->tid] = $r4->sciname;
                    }
                    $rs4->free();
                }
            }
		}
		return $synArr;
	}

	//Setters and getters
	public function getClName(){
		return $this->clName;
	}
	
	public function setSearchTermsArr($stArr){
		if($stArr) $this->searchTermsArr = $stArr;
	}

	public function getSearchTermsArr(){
		return $this->searchTermsArr;
	}
	
	public function getTaxaArr(){
		return $this->taxaArr;
	}

	//misc functions
	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	protected function cleanSearchQuotes($str){
		$newStr = str_replace('"',"",$str);
		$newStr = str_replace("'","%apos;",$newStr);
		return $newStr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>