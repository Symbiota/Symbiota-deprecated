<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
include_once($SERVER_ROOT.'/classes/SearchManager.php');

class OccurrenceManager extends SearchManager {

	protected $searchTermArr = Array();
	protected $localSearchArr = Array();
	protected $reset = 0;
	private $clName;
	private $collArrIndex = 0;
	private $occurSearchProjectExists = 0;

 	public function __construct(){
 	    parent::__construct();
 	    if(array_key_exists("reset",$_REQUEST) && $_REQUEST["reset"])  $this->reset();
		$this->readRequestVariables();
 	}

	public function __destruct(){
	    parent::__destruct();
	}

	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
	}

	public function reset(){
		$domainName = $_SERVER['HTTP_HOST'];
		if(!$domainName) $domainName = $_SERVER['SERVER_NAME'];
 		$this->reset = 1;
		if(isset($this->searchTermArr['db']) || isset($this->searchTermArr['oic'])){
			//reset all other search terms except maintain the db terms
			$dbsTemp = "";
			if(isset($this->searchTermArr['db'])) $dbsTemp = $this->searchTermArr["db"];
			$clidTemp = "";
			if(isset($this->searchTermArr['clid'])) $clidTemp = $this->searchTermArr["clid"];
			unset($this->searchTermArr);
			if($dbsTemp) $this->searchTermArr["db"] = $dbsTemp;
			if($clidTemp) $this->searchTermArr["clid"] = $clidTemp;
		}
	}

	public function getSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists('clid',$this->searchTermArr)){
			$sqlWhere .= "AND (v.clid IN(".$this->searchTermArr['clid'].")) ";
		}
		elseif(array_key_exists("db",$this->searchTermArr) && $this->searchTermArr['db']){
			//Do nothing if db = all
			if($this->searchTermArr['db'] != 'all'){
				if($this->searchTermArr['db'] == 'allspec'){
					$sqlWhere .= 'AND (o.collid IN(SELECT collid FROM omcollections WHERE colltype = "Preserved Specimens")) ';
				}
				elseif($this->searchTermArr['db'] == 'allobs'){
					$sqlWhere .= 'AND (o.collid IN(SELECT collid FROM omcollections WHERE colltype IN("General Observations","Observations"))) ';
				}
				else{
					$dbArr = explode(';',$this->searchTermArr["db"]);
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

		if(array_key_exists("taxa",$this->searchTermArr)){
		    $useThes           = (array_key_exists("usethes",$this->searchTermArr)?$this->searchTermArr["usethes"]:0);
		    $baseSearchType    = $this->searchTermArr["taxontype"];
		    $taxaSearchTerms   = explode(";",trim($this->searchTermArr["taxa"]));
		    $this->setTaxaArr($useThes,$baseSearchType,$taxaSearchTerms);

			//Build sql
			$sqlWhereTaxa = "";
			foreach($this->taxaArr as $key => $valueArray){
			    $tempTaxonType = $valueArray['taxontype'];
				if($tempTaxonType== TaxaSearchType::HIGHER_TAXONOMY){
					//Class, order, or other higher rank
					if(isset($valueArray['tid'])){
						$rs1 = $this->conn->query('SELECT tidaccepted FROM taxstatus WHERE (tid = '.$valueArray['tid'].')');
						if($r1 = $rs1->fetch_object()){
							$sqlWhereTaxa = 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = 1 AND (parenttid IN('.$r1->tidaccepted.') OR (tid = '.$r1->tidaccepted.')))) ';
						}
					}
				}
				else{
					if($tempTaxonType== TaxaSearchType::COMMON_NAME){
						//Common name search
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
						if($tempTaxonType== TaxaSearchType::FAMILY_ONLY || ($tempTaxonType== TaxaSearchType::FAMILY_GENUS_OR_SPECIES && (strtolower(substr($key,-5)) == "aceae" || strtolower(substr($key,-4)) == "idae"))){
							$sqlWhereTaxa .= "OR (o.family = '".$key."') ";
						}
						if($tempTaxonType== TaxaSearchType::SPECIES_NAME_ONLY || ($tempTaxonType== TaxaSearchType::FAMILY_GENUS_OR_SPECIES && strtolower(substr($key,-5)) != "aceae" && strtolower(substr($key,-4)) != "idae")){
							$sqlWhereTaxa .= "OR (o.sciname LIKE '".$key."%') ";
						}
					}
					if(array_key_exists("synonyms",$valueArray)){
						$synArr = $valueArray["synonyms"];
						if($synArr){
							if($tempTaxonType== TaxaSearchType::FAMILY_GENUS_OR_SPECIES || $tempTaxonType== TaxaSearchType::FAMILY_ONLY || $tempTaxonType== TaxaSearchType::COMMON_NAME){
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
							if($taxaSearchType == TaxaSearchType::FAMILY_GENUS_OR_SPECIES || $taxaSearchType == TaxaSearchType::FAMILY_ONLY || $taxaSearchType == TaxaSearchType::COMMON_NAME){
								$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
							}
							if($taxaSearchType == TaxaSearchType::FAMILY_ONLY){
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

		if(array_key_exists("country",$this->searchTermArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermArr["country"]);
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
		if(array_key_exists("state",$this->searchTermArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermArr["state"]);
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
		if(array_key_exists("county",$this->searchTermArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermArr["county"]);
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
		if(array_key_exists("local",$this->searchTermArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermArr["local"]);
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
		if(array_key_exists("elevlow",$this->searchTermArr) || array_key_exists("elevhigh",$this->searchTermArr)){
			$elevlow = 0;
			$elevhigh = 30000;
			if (array_key_exists("elevlow",$this->searchTermArr))  { $elevlow = $this->searchTermArr["elevlow"]; }
			if (array_key_exists("elevhigh",$this->searchTermArr))  { $elevhigh = $this->searchTermArr["elevhigh"]; }
			$tempArr = Array();
			$sqlWhere .= "AND ( " .
						 "	  ( minimumElevationInMeters >= $elevlow AND maximumElevationInMeters <= $elevhigh ) OR " .
						 "	  ( maximumElevationInMeters is null AND minimumElevationInMeters >= $elevlow AND minimumElevationInMeters <= $elevhigh ) ".
						 "	) ";
		}
		if(array_key_exists("llbound",$this->searchTermArr)){
			$llboundArr = explode(";",$this->searchTermArr["llbound"]);
			if(count($llboundArr) == 4){
				$sqlWhere .= "AND (o.DecimalLatitude BETWEEN ".$llboundArr[1]." AND ".$llboundArr[0]." AND ".
					"o.DecimalLongitude BETWEEN ".$llboundArr[2]." AND ".$llboundArr[3].") ";
				$this->localSearchArr[] = "Lat: >".$llboundArr[1].", <".$llboundArr[0]."; Long: >".$llboundArr[2].", <".$llboundArr[3];
			}
		}
		if(array_key_exists("llpoint",$this->searchTermArr)){
			$pointArr = explode(";",$this->searchTermArr["llpoint"]);
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
		if(array_key_exists("collector",$this->searchTermArr)){
			$searchStr = str_replace("%apos;","'",$this->searchTermArr["collector"]);
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
		if(array_key_exists("collnum",$this->searchTermArr)){
			$collNumArr = explode(";",$this->searchTermArr["collnum"]);
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
		if(array_key_exists('eventdate1',$this->searchTermArr)){
			$dateArr = array();
			if(strpos($this->searchTermArr['eventdate1'],' to ')){
				$dateArr = explode(' to ',$this->searchTermArr['eventdate1']);
			}
			elseif(strpos($this->searchTermArr['eventdate1'],' - ')){
				$dateArr = explode(' - ',$this->searchTermArr['eventdate1']);
			}
			else{
				$dateArr[] = $this->searchTermArr['eventdate1'];
				if(isset($this->searchTermArr['eventdate2'])){
					$dateArr[] = $this->searchTermArr['eventdate2'];
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
				$this->localSearchArr[] = $this->searchTermArr['eventdate1'].(isset($this->searchTermArr['eventdate2'])?' to '.$this->searchTermArr['eventdate2']:'');
			}
		}
		if(array_key_exists('catnum',$this->searchTermArr)){
			$catStr = $this->searchTermArr['catnum'];
			$includeOtherCatNum = array_key_exists('othercatnum',$this->searchTermArr)?true:false;

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
			$this->localSearchArr[] = $this->searchTermArr['catnum'];
		}
		if(array_key_exists("typestatus",$this->searchTermArr)){
			$sqlWhere .= "AND (o.typestatus IS NOT NULL) ";
			$this->localSearchArr[] = 'is type';
		}
		if(array_key_exists("hasimages",$this->searchTermArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM images)) ";
			$this->localSearchArr[] = 'has images';
		}
		if(array_key_exists("hasgenetic",$this->searchTermArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM omoccurgenetic)) ";
			$this->localSearchArr[] = 'has genetic data';
		}
		if(array_key_exists("targetclid",$this->searchTermArr)){
			$clid = $this->searchTermArr["targetclid"];
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

	protected function setTableJoins($sqlWhere){
		$sqlJoin = '';
		if(array_key_exists("clid",$this->searchTermArr)) $sqlJoin .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
		if(strpos($sqlWhere,'MATCH(f.recordedby)')) $sqlJoin .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
		return $sqlJoin;
	}

	public function getFullCollectionList($catId = ''){
		if($catId && !is_numeric($catId)) $catId = ''; 
		//Set collection array
		$collIdArr = array();
		$catIdArr = array();
		if(isset($this->searchTermArr['db']) && array_key_exists('db',$this->searchTermArr)){
			$cArr = explode(';',$this->searchTermArr['db']);
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
		$buttonStr = '<button type="submit" class="ui-button ui-widget ui-corner-all">'.(isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next &gt;').'</button>';
		//$buttonStr = '<input type="submit" class="nextbtn searchcollnextbtn" value="'.(isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >').'" />';
		$collCnt = 0;
		echo '<div style="position:relative">';
		if(isset($occArr['cat'])){
			$categoryArr = $occArr['cat'];
			?>
			<div style="float:right;margin-top:20px;">
				<?php echo $buttonStr; ?>
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
					<?php echo $buttonStr; ?>
				</div>
				<?php
			}
			if(count($collArr) > 40){
				?>
				<div style="float:right;position:absolute;top:<?php echo count($collArr)*15; ?>px;right:0px;">
					<?php echo $buttonStr; ?>
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
		if(!array_key_exists('db',$this->searchTermArr) || $this->searchTermArr['db'] == 'all'){
			$retStr = "All Collections";
		}
		elseif($this->searchTermArr['db'] == 'allspec'){
			$retStr = "All Specimen Collections";
		}
		elseif($this->searchTermArr['db'] == 'allobs'){
			$retStr = "All Observation Projects";
		}
		else{
			$cArr = explode(';',$this->searchTermArr['db']);
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
		    $str = TaxaSearchType::anyNameSearchTag($taxonArr["taxontype"]).": ";
			$str .= $taxonName;
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
		if(array_key_exists('searchvar',$_REQUEST)){
			parse_str($_REQUEST['searchvar'],$retArr);
			if($retArr) $this->searchTermArr = $retArr;
		}
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
				$clidStr = $this->cleanInStr(implode(',',array_unique($clidIn)));
			}
			$this->searchTermArr["clid"] = $clidStr;
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
				$dbStr = $this->cleanInStr(implode(',',array_unique($dbs))).';';
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
				$dbStr .= $this->cleanInStr(implode(",",$catArr));
			}

			if($dbStr){
				$this->searchTermArr["db"] = $dbStr;
			}
		}
		if(array_key_exists("taxa",$_REQUEST)){
			$taxa = $this->cleanInStr($_REQUEST["taxa"]);
			$searchType = ((array_key_exists("taxontype",$_REQUEST) && is_numeric($_REQUEST["taxontype"]))?$_REQUEST["taxontype"]:0);
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
						if($searchType < 5) $snStr = ucfirst($snStr);
						$taxaArr[$key] = $snStr;
					}
					$taxaStr = implode(";",$taxaArr);
				}
				$this->searchTermArr["taxa"] = $taxaStr;
				$useThes = array_key_exists("usethes",$_REQUEST)&&$_REQUEST["usethes"]==1?1:0;
				if($useThes){
					$this->searchTermArr["usethes"] = "1";
				}
				else{
					$this->searchTermArr["usethes"] = "0";
				}
				$this->searchTermArr["taxontype"] = $searchType;
			}
			else{
				unset($this->searchTermArr["taxa"]);
			}
		}
		$searchArr = Array();
		$searchFieldsActivated = false;
		if(array_key_exists("country",$_REQUEST)){
			$country = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["country"]));
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
				$this->searchTermArr["country"] = $str;
			}
			else{
				unset($this->searchTermArr["country"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("state",$_REQUEST)){
			$state = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["state"]));
			if($state){
				if(strlen($state) == 2 && (!isset($this->searchTermArr["country"]) || stripos($this->searchTermArr["country"],'USA') !== false)){
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
				$this->searchTermArr["state"] = $str;
			}
			else{
				unset($this->searchTermArr["state"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("county",$_REQUEST)){
			$county = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["county"]));
			$county = str_ireplace(" Co.","",$county);
			$county = str_ireplace(" County","",$county);
			if($county){
				$str = str_replace(",",";",$county);
				$searchArr[] = "county:".$str;
				$this->searchTermArr["county"] = $str;
			}
			else{
				unset($this->searchTermArr["county"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("local",$_REQUEST)){
			$local = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["local"]));
			if($local){
				$str = str_replace(",",";",$local);
				$searchArr[] = "local:".$str;
				$this->searchTermArr["local"] = $str;
			}
			else{
				unset($this->searchTermArr["local"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("elevlow",$_REQUEST)){
			if(is_numeric($_REQUEST["elevlow"])){
				$elevlow = $this->cleanInStr($_REQUEST["elevlow"]);
				if($elevlow){
					$str = str_replace(",",";",$elevlow);
					$searchArr[] = "elevlow:".$str;
					$this->searchTermArr["elevlow"] = $str;
				}
				else{
					unset($this->searchTermArr["elevlow"]);
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
					$this->searchTermArr["elevhigh"] = $str;
				}
				else{
					unset($this->searchTermArr["elevhigh"]);
				}
				$searchFieldsActivated = true;
			}
		}
		if(array_key_exists("collector",$_REQUEST)){
			$collector = $this->cleanInStr($this->cleanSearchQuotes($_REQUEST["collector"]));
			if($collector){
				$str = str_replace(",",";",$collector);
				$searchArr[] = "collector:".$str;
				$this->searchTermArr["collector"] = $str;
			}
			else{
				unset($this->searchTermArr["collector"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("collnum",$_REQUEST)){
			$collNum = $this->cleanInStr($_REQUEST["collnum"]);
			if($collNum){
				$str = str_replace(",",";",$collNum);
				$searchArr[] = "collnum:".$str;
				$this->searchTermArr["collnum"] = $str;
			}
			else{
				unset($this->searchTermArr["collnum"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("eventdate1",$_REQUEST)){
			if($eventDate = $this->cleanInStr($_REQUEST["eventdate1"])){
				$searchArr[] = "eventdate1:".$eventDate;
				$this->searchTermArr["eventdate1"] = $eventDate;
				if(array_key_exists("eventdate2",$_REQUEST)){
					if($eventDate2 = $this->cleanInStr($_REQUEST["eventdate2"])){
						if($eventDate2 != $eventDate){
							$searchArr[] = "eventdate2:".$eventDate2;
							$this->searchTermArr["eventdate2"] = $eventDate2;
						}
					}
					else{
						unset($this->searchTermArr["eventdate2"]);
					}
				}
			}
			else{
				unset($this->searchTermArr["eventdate1"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("catnum",$_REQUEST)){
			$catNum = $this->cleanInStr($_REQUEST["catnum"]);
			if($catNum){
				$str = str_replace(",",";",$catNum);
				$searchArr[] = "catnum:".$str;
				$this->searchTermArr["catnum"] = $str;
				if(array_key_exists("includeothercatnum",$_REQUEST)){
					$searchArr[] = "othercatnum:1";
					$this->searchTermArr["othercatnum"] = '1';
				}
			}
			else{
				unset($this->searchTermArr["catnum"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("typestatus",$_REQUEST)){
			$typestatus = $_REQUEST["typestatus"];
			if($typestatus){
				$searchArr[] = "typestatus:".$typestatus;
				$this->searchTermArr["typestatus"] = true;
			}
			else{
				unset($this->searchTermArr["typestatus"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("hasimages",$_REQUEST)){
			$hasimages = $_REQUEST["hasimages"];
			if($hasimages){
				$searchArr[] = "hasimages:".$hasimages;
				$this->searchTermArr["hasimages"] = true;
			}
			else{
				unset($this->searchTermArr["hasimages"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("hasgenetic",$_REQUEST)){
			$hasgenetic = $_REQUEST["hasgenetic"];
			if($hasgenetic){
				$searchArr[] = "hasgenetic:".$hasgenetic;
				$this->searchTermArr["hasgenetic"] = true;
			}
			else{
				unset($this->searchTermArr["hasgenetic"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("targetclid",$_REQUEST) && is_numeric($_REQUEST['targetclid'])){
			$searchArr[] = "targetclid:".$_REQUEST["targetclid"];
			$this->searchTermArr["targetclid"] = $_REQUEST["targetclid"];
			$searchFieldsActivated = true;
		}
		$latLongArr = Array();
		if(array_key_exists("upperlat",$_REQUEST)){
			if(is_numeric($_REQUEST["upperlat"]) && is_numeric($_REQUEST["bottomlat"]) && is_numeric($_REQUEST["leftlong"]) && is_numeric($_REQUEST["rightlong"])){
				$upperLat = $this->cleanInStr($_REQUEST["upperlat"]);
				if($upperLat || $upperLat === "0") $latLongArr[] = $upperLat;

				$bottomlat = $this->cleanInStr($_REQUEST["bottomlat"]);
				if($bottomlat || $bottomlat === "0") $latLongArr[] = $bottomlat;

				$leftLong = $this->cleanInStr($_REQUEST["leftlong"]);
				if($leftLong || $leftLong === "0") $latLongArr[] = $leftLong;

				$rightlong = $this->cleanInStr($_REQUEST["rightlong"]);
				if($rightlong || $rightlong === "0") $latLongArr[] = $rightlong;

				if(count($latLongArr) == 4){
					$searchArr[] = "llbound:".implode(";",$latLongArr);
					$this->searchTermArr["llbound"] = implode(";",$latLongArr);
				}
				else{
					unset($this->searchTermArr["llbound"]);
				}
				$searchFieldsActivated = true;
			}
		}
		if(array_key_exists("pointlat",$_REQUEST)){
			if(is_numeric($_REQUEST["pointlat"]) && is_numeric($_REQUEST["pointlong"]) && is_numeric($_REQUEST["radius"])){
				$pointLat = $this->cleanInStr($_REQUEST["pointlat"]);
				if($pointLat || $pointLat === "0") $latLongArr[] = $pointLat;

				$pointLong = $this->cleanInStr($_REQUEST["pointlong"]);
				if($pointLong || $pointLong === "0") $latLongArr[] = $pointLong;

				$radius = $this->cleanInStr($_REQUEST["radius"]);
				if($radius) $latLongArr[] = $radius;
				if(count($latLongArr) == 3){
					$searchArr[] = "llpoint:".implode(";",$latLongArr);
					$this->searchTermArr["llpoint"] = implode(";",$latLongArr);
				}
				else{
					unset($this->searchTermArr["llpoint"]);
				}
				$searchFieldsActivated = true;
			}
		}
	}

	//Misc return functions
	//Setters and getters
	public function getClName(){
		return $this->clName;
	}

	public function setsearchTermArr($stArr){
		if($stArr && !$this->searchTermArr) $this->searchTermArr = $stArr;
	}

	public function getSearchTerm($k){
		if($k && isset($this->searchTermArr[$k])){
			return $this->searchTermArr[$k];
		}
		return '';
	}

	public function getSearchTermArr(){
		return $this->searchTermArr;
	}

	public function getSearchTermStr(){
		$retStr = '';
		foreach($this->searchTermArr as $k => $v){
			$retStr .= '&'.$k.'='.htmlentities($v);
		}
		return trim($retStr,' &');
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

}
?>