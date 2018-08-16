<?php
include_once($SERVER_ROOT.'/classes/OccurrenceSearchSupport.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');

class OccurrenceManager extends OccurrenceTaxaManager {

	protected $searchTermArr = Array();
	protected $sqlWhere;
	protected $displaySearchArr = Array();
	protected $reset = 0;
	private $voucherManager;
	private $occurSearchProjectExists = 0;
	protected $searchSupportManager = null;

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
		if(!$this->sqlWhere) $this->setSqlWhere();
		return $this->sqlWhere;
	}

	private function setSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("targetclid",$this->searchTermArr) && is_numeric($this->searchTermArr["targetclid"])){
			//Used to exclude vouchers alredy linked to target checklist
			$sqlWhere .= 'AND ('.$this->voucherManager->getSqlFrag().') ';
			if(array_key_exists('mode', $_REQUEST) && $_REQUEST['mode'] == 'voucher'){
				$clOccidArr = array();
				if(isset($this->taxaArr['search']) && is_numeric($this->taxaArr['search'])){
					$sql = 'SELECT DISTINCT v.occid '.
						'FROM fmvouchers v INNER JOIN taxstatus ts ON v.tid = ts.tidaccepted '.
						'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
						'WHERE (v.clid = '.$this->searchTermArr["targetclid"].') AND (v.tid = '.$this->taxaArr['search'].')';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$clOccidArr[] = $r->occid;
					}
					$rs->free();
				}
				if($clOccidArr) $sqlWhere .= 'AND (o.occid NOT IN('.implode(',',$clOccidArr).')) ';
			}
			$this->displaySearchArr[] = $this->voucherManager->getQueryVariableStr();
		}
		elseif(array_key_exists('clid',$this->searchTermArr) && is_numeric($this->searchTermArr['clid'])){
			if(isset($this->searchTermArr["cltype"]) && $this->searchTermArr["cltype"] == 'all'){
				$sqlWhere .= 'AND (cl.clid IN('.$this->searchTermArr['clid'].')) ';
			}
			else{
				$sqlWhere .= 'AND (v.clid IN('.$this->searchTermArr['clid'].')) ';
			}
			$this->displaySearchArr[] = 'Checklist ID: '.$this->searchTermArr['clid'];
		}
		elseif(array_key_exists("db",$this->searchTermArr) && $this->searchTermArr['db']){
			$sqlWhere .= OccurrenceSearchSupport::getDbWhereFrag($this->cleanInStr($this->searchTermArr['db']));
		}

		$sqlWhere .= $this->getTaxonWhereFrag();

		if(array_key_exists("country",$this->searchTermArr)){
			$countryArr = explode(";",$this->searchTermArr["country"]);
			$tempArr = Array();
			foreach($countryArr as $k => $value){
				if($value == 'NULL'){
					$countryArr[$k] = 'Country IS NULL';
					$tempArr[] = '(o.Country IS NULL)';
				}
				else{
					$tempArr[] = '(o.Country = "'.$this->cleanInStr($value).'")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$countryArr);
		}
		if(array_key_exists("state",$this->searchTermArr)){
			$stateAr = explode(";",$this->searchTermArr["state"]);
			$tempArr = Array();
			foreach($stateAr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.StateProvince IS NULL)';
					$stateAr[$k] = 'State IS NULL';
				}
				else{
					$tempArr[] = '(o.StateProvince = "'.$this->cleanInStr($value).'")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$stateAr);
		}
		if(array_key_exists("county",$this->searchTermArr)){
			$countyArr = explode(";",$this->searchTermArr["county"]);
			$tempArr = Array();
			foreach($countyArr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.county IS NULL)';
					$countyArr[$k] = 'County IS NULL';
				}
				else{
					$term = $this->cleanInStr(trim(str_ireplace(' county',' ',$value),'%'));
					if(strlen($term) < 4) $term .= ' ';
					$tempArr[] = '(o.county LIKE "'.$term.'%")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$countyArr);
		}
		if(array_key_exists("local",$this->searchTermArr)){
			$localArr = explode(", ",$this->searchTermArr["local"]);
			$tempArr = Array();
			foreach($localArr as $k => $value){
				$value = trim($value);
				if($value == 'NULL'){
					$tempArr[] = '(o.locality IS NULL)';
					$localArr[$k] = 'Locality IS NULL';
				}
				else{
					if(strlen($value) < 4){
						$tempArr[] = '(o.municipality LIKE "'.$this->cleanInStr($value).'%" OR o.Locality LIKE "%'.$this->cleanInStr($value).'%")';
					}
					else{
						$tempArr[] = '(MATCH(f.locality) AGAINST(\'"'.$this->cleanInStr(str_replace('"', '', $value)).'"\' IN BOOLEAN MODE)) ';
					}
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$localArr);
		}
		if(array_key_exists("elevlow",$this->searchTermArr) || array_key_exists("elevhigh",$this->searchTermArr)){
			$elevlow = -200;
			$elevhigh = 9000;
			if(array_key_exists("elevlow",$this->searchTermArr)) $elevlow = $this->searchTermArr["elevlow"];
			if(array_key_exists("elevhigh",$this->searchTermArr)) $elevhigh = $this->searchTermArr["elevhigh"];
			$sqlWhere .= 'AND (( minimumElevationInMeters >= '.$elevlow.' AND maximumElevationInMeters <= '.$elevhigh.' ) OR ' .
				'( maximumElevationInMeters is null AND minimumElevationInMeters >= '.$elevlow.' AND minimumElevationInMeters <= '.$elevhigh.' )) ';
			$this->displaySearchArr[] = 'Elev: '.$elevlow.($elevhigh?' - '.$elevhigh:'');
		}
		if(array_key_exists("llbound",$this->searchTermArr)){
			$llboundArr = explode(";",$this->searchTermArr["llbound"]);
			if(count($llboundArr) == 4){
				$uLat = $llboundArr[0];
				$bLat = $llboundArr[1];
				$lLng = $llboundArr[2];
				$rLng = $llboundArr[3];
				//$sqlWhere .= 'AND (o.DecimalLatitude BETWEEN '.$llboundArr[1].' AND '.$llboundArr[0].' AND o.DecimalLongitude BETWEEN '.$llboundArr[2].' AND '.$llboundArr[3].') ';
				$sqlWhere .= 'AND (ST_Within(p.point,GeomFromText("POLYGON(('.$uLat.' '.$rLng.','.$bLat.' '.$rLng.','.$bLat.' '.$lLng.','.$uLat.' '.$lLng.','.$uLat.' '.$rLng.'))"))) ';
				$this->displaySearchArr[] = 'Lat: '.$llboundArr[1].' - '.$llboundArr[0].' Long: '.$llboundArr[2].' - '.$llboundArr[3];
			}
		}
		elseif(array_key_exists("llpoint",$this->searchTermArr)){
			$pointArr = explode(";",$this->searchTermArr["llpoint"]);
			if(count($pointArr) == 4){
				$lat = $pointArr[0];
				$lng = $pointArr[1];
				$radius = $pointArr[2];
				if($pointArr[3] == 'km') $radius *= 0.6214;
				/*
				//Formula approximates a bounding box; bounding box is for efficiency, will test practicality of doing a radius query in future
				$latRadius = $radius / 69.1;
				$longRadius = cos($pointArr[0]/57.3)*($radius/69.1);
				$lat1 = $pointArr[0] - $latRadius;
				$lat2 = $pointArr[0] + $latRadius;
				$long1 = $pointArr[1] - $longRadius;
				$long2 = $pointArr[1] + $longRadius;
				$sqlWhere .= "AND ((o.DecimalLatitude BETWEEN ".$lat1." AND ".$lat2.") AND (o.DecimalLongitude BETWEEN ".$long1." AND ".$long2.")) ";
				*/
				$sqlWhere .= 'AND (( 3959 * acos( cos( radians('.$lat.') ) * cos( radians( o.DecimalLatitude ) ) * cos( radians( o.DecimalLongitude )'.
					' - radians('.$lng.') ) + sin( radians('.$lat.') ) * sin(radians(o.DecimalLatitude)) ) ) < '.$radius.') ';
			}
			$this->displaySearchArr[] = $pointArr[0]." ".$pointArr[1]." +- ".$pointArr[2].$pointArr[3];
		}
		elseif(array_key_exists('footprintwkt',$this->searchTermArr)){
			$sqlWhere .= 'AND (ST_Within(p.point,GeomFromText("'.$this->searchTermArr['footprintwkt'].'"))) ';
			$this->displaySearchArr[] = 'Polygon search (not displayed)';
		}
		if(array_key_exists("collector",$this->searchTermArr)){
			$collectorArr = explode(";",$this->searchTermArr["collector"]);
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
						if(strlen($collV) == 2 || strlen($collV) == 3 || strtolower($collV) == 'best'){
							//Need to avoid FULLTEXT stopwords interfering with return
							$tempInnerArr[] = '(o.recordedBy LIKE "%'.$this->cleanInStr($collV).'%")';
						}
						else{
							$tempInnerArr[] = '(MATCH(f.recordedby) AGAINST("'.$this->cleanInStr(str_replace('"', '', $collV)).'")) ';
						}
					}
					$tempArr[] = implode(' AND ', $tempInnerArr);
				}
			}
			elseif(count($collectorArr) > 1){
				$collStr = current($collectorArr);
				if(strlen($collStr) < 4 || strtolower($collStr) == 'best'){
					//Need to avoid FULLTEXT stopwords interfering with return
					$tempInnerArr[] = '(o.recordedBy LIKE "%'.$this->cleanInStr($collStr).'%")';
				}
				else{
					$tempArr[] = '(MATCH(f.recordedby) AGAINST("'.$this->cleanInStr($collStr).'")) ';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(', ',$collectorArr);
		}
		if(array_key_exists("collnum",$this->searchTermArr)){
			$collNumArr = explode(";",$this->cleanInStr($this->searchTermArr["collnum"]));
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
				$this->displaySearchArr[] = implode(", ",$collNumArr);
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
				$this->displaySearchArr[] = 'Date IS NULL';
			}
			elseif($eDate1 = $this->cleanInStr($this->formatDate($dateArr[0]))){
				$eDate2 = (count($dateArr)>1?$this->cleanInStr($this->formatDate($dateArr[1])):'');
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
				$this->displaySearchArr[] = $this->searchTermArr['eventdate1'].(isset($this->searchTermArr['eventdate2'])?' to '.$this->searchTermArr['eventdate2']:'');
			}
		}
		if(array_key_exists('catnum',$this->searchTermArr)){
			$catStr = $this->cleanInStr($this->searchTermArr['catnum']);
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
			$this->displaySearchArr[] = $this->searchTermArr['catnum'];
		}
		if(array_key_exists("typestatus",$this->searchTermArr)){
			$sqlWhere .= "AND (o.typestatus IS NOT NULL) ";
			$this->displaySearchArr[] = 'is type';
		}
		if(array_key_exists("hasimages",$this->searchTermArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM images)) ";
			$this->displaySearchArr[] = 'has images';
		}
		if(array_key_exists("hasgenetic",$this->searchTermArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM omoccurgenetic)) ";
			$this->displaySearchArr[] = 'has genetic data';
		}
		if($sqlWhere){
			$this->sqlWhere = 'WHERE '.substr($sqlWhere,4);
		}
		else{
			//Make the sql valid, but return nothing
			//$this->sqlWhere = 'WHERE o.occid IS NULL ';
		}
		//echo $this->sqlWhere; exit;
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

	protected function getTableJoins($sqlWhere){
		$sqlJoin = '';
		if(array_key_exists('clid',$this->searchTermArr) && $this->searchTermArr['clid']){
			if(strpos($sqlWhere,'v.clid')){
				$sqlJoin .= 'INNER JOIN fmvouchers v ON o.occid = v.occid ';
			}
			else{
				$sqlJoin .= 'INNER JOIN fmchklsttaxalink cl ON o.tidinterpreted = cl.tid ';
			}
		}
		if(strpos($sqlWhere,'MATCH(f.recordedby)') || strpos($sqlWhere,'MATCH(f.locality)')){
			$sqlJoin .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		if(strpos($sqlWhere,'e.taxauthid')){
			$sqlJoin .= 'INNER JOIN taxaenumtree e ON o.tidinterpreted = e.tid ';
		}
		if(strpos($sqlWhere,'ts.family')){
			$sqlJoin .= 'LEFT JOIN taxstatus ts ON o.tidinterpreted = ts.tid ';
		}
		if(array_key_exists("polycoords",$this->searchTermArr) || strpos($sqlWhere,'p.point')){
			$sqlJoin .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid ';
		}
		return $sqlJoin;
	}

	public function getFullCollectionList($catId = ''){
		if(!$this->searchSupportManager) $this->searchSupportManager = new occurrenceSearchSupport($this->conn);
		if(isset($this->searchTermArr['db'])) $this->searchSupportManager->setCollidStr($this->searchTermArr['db']);
		return $this->searchSupportManager->getFullCollectionList($catId);
	}

	public function outputFullCollArr($occArr, $targetCatID = 0, $displayIcons = true, $displaySearchButtons = true){
		if(!$this->searchSupportManager) $this->searchSupportManager = new occurrenceSearchSupport($this->conn);
		$this->searchSupportManager->outputFullCollArr($occArr, $targetCatID, $displayIcons, $displaySearchButtons);
	}

	public function getOccurVoucherProjects(){
		$retArr = Array();
		$titleArr = Array();
		$sql = 'SELECT p2.pid AS parentpid, p2.projname as catname, p1.pid, p1.projname, c.clid, c.name as clname '.
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
			$cArr = explode(';',$this->cleanInStr($this->searchTermArr['db']));
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

	public function getLocalSearchStr(){
		return implode("; ", $this->displaySearchArr);
	}

	public function getSearchTerm($k){
		if($k && isset($this->searchTermArr[$k])){
			return trim($this->searchTermArr[$k],' ;');
		}
		return '';
	}

	public function getQueryTermStr(){
		//Returns a search variable string
		$retStr = '';
		foreach($this->searchTermArr as $k => $v){
			$retStr .= '&'.$k.'='.urlencode($v);
		}
		if(isset($this->taxaArr['search'])){
			$retStr .= '&taxa='.urlencode($this->taxaArr['search']);
			if($this->taxaArr['usethes']) $retStr .= '&usethes=1';
			$retStr .= '&taxontype='.$this->taxaArr['taxontype'];
		}
		return trim($retStr,' &');
	}

	protected function readRequestVariables(){
		if(array_key_exists('searchvar',$_REQUEST)){
			parse_str($_REQUEST['searchvar'],$retArr);
			if(isset($retArr['taxa'])){
				$taxaArr['taxa'] = $retArr['taxa'];
				unset($retArr['taxa']);
				if(isset($retArr['usethes'])){
					$taxaArr['usethes'] = $retArr['usethes'];
					unset($retArr['usethes']);
				}
				if(isset($retArr['taxontype'])){
					$taxaArr['taxontype'] = $retArr['taxontype'];
					unset($retArr['taxontype']);
				}
				$this->setTaxonRequestVariable($taxaArr);
			}
			if($retArr) $this->searchTermArr = $retArr;
		}
		//Search will be confinded to a clid vouchers, collid, catid, or will remain open to all collection
		if(array_key_exists("targetclid",$_REQUEST) && is_numeric($_REQUEST['targetclid'])){
			$this->searchTermArr["targetclid"] = $_REQUEST["targetclid"];
			$this->setChecklistVariables($_REQUEST["targetclid"]);
		}
		elseif(array_key_exists('clid',$_REQUEST)){
			//Limit by checklist voucher links
			$clidIn = $_REQUEST['clid'];
			$clidStr = '';
			if(is_string($clidIn)){
				$clidStr = $clidIn;
			}
			else{
				$clidStr = implode(',',array_unique($clidIn));
			}
			if(!preg_match('/^[0-9,]+$/', $clidStr)) $clidStr = '';
			$this->setChecklistVariables($clidStr);
			$this->searchTermArr["clid"] = $clidStr;
		}
		elseif(array_key_exists("db",$_REQUEST) && $_REQUEST['db']){
			$dbStr = $this->cleanInputStr(OccurrenceSearchSupport::getDbRequestVariable($_REQUEST));
			if($dbStr) $this->searchTermArr["db"] = $dbStr;
		}
		if(array_key_exists("taxa",$_REQUEST) && $_REQUEST["taxa"]){
			$this->setTaxonRequestVariable();
		}
		if(array_key_exists("country",$_REQUEST)){
			$country = $this->cleanInputStr($_REQUEST["country"]);
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
				$this->searchTermArr["country"] = $str;
			}
			else{
				unset($this->searchTermArr["country"]);
			}
		}
		if(array_key_exists("state",$_REQUEST)){
			$state = $this->cleanInputStr($_REQUEST["state"]);
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
				$this->searchTermArr["state"] = $str;
			}
			else{
				unset($this->searchTermArr["state"]);
			}
		}
		if(array_key_exists("county",$_REQUEST)){
			$county = $this->cleanInputStr($_REQUEST["county"]);
			$county = str_ireplace(" Co.","",$county);
			$county = str_ireplace(" County","",$county);
			if($county){
				$str = str_replace(",",";",$county);
				$this->searchTermArr["county"] = $str;
			}
			else{
				unset($this->searchTermArr["county"]);
			}
		}
		if(array_key_exists("local",$_REQUEST)){
			$local = $this->cleanInputStr($_REQUEST["local"]);
			if($local){
				$str = str_replace(",",";",$local);
				$this->searchTermArr["local"] = $str;
			}
			else{
				unset($this->searchTermArr["local"]);
			}
		}
		if(array_key_exists("elevlow",$_REQUEST)){
			$elevLow = filter_var(trim($_REQUEST["elevlow"]), FILTER_SANITIZE_NUMBER_INT);
			if(is_numeric($elevLow)){
				$this->searchTermArr["elevlow"] = $elevLow;
			}
			else{
				unset($this->searchTermArr["elevlow"]);
			}
		}
		if(array_key_exists("elevhigh",$_REQUEST)){
			$elevHigh = filter_var(trim($_REQUEST["elevhigh"]), FILTER_SANITIZE_NUMBER_INT);
			if(is_numeric($elevHigh)){
				$this->searchTermArr["elevhigh"] = $elevHigh;
			}
			else{
				unset($this->searchTermArr["elevhigh"]);
			}
		}
		if(array_key_exists("collector",$_REQUEST)){
			$collector = $this->cleanInputStr($_REQUEST["collector"]);
			$collector = str_replace('%', '', $collector);
			if($collector){
				$str = str_replace(",",";",$collector);
				$this->searchTermArr["collector"] = $str;
			}
			else{
				unset($this->searchTermArr["collector"]);
			}
		}
		if(array_key_exists("collnum",$_REQUEST)){
			$collNum = $this->cleanInputStr($_REQUEST["collnum"]);
			if($collNum){
				$str = str_replace(",",";",$collNum);
				$this->searchTermArr["collnum"] = $str;
			}
			else{
				unset($this->searchTermArr["collnum"]);
			}
		}
		if(array_key_exists("eventdate1",$_REQUEST)){
			if($eventDate = $this->cleanInputStr($_REQUEST["eventdate1"])){
				$this->searchTermArr["eventdate1"] = $eventDate;
				if(array_key_exists("eventdate2",$_REQUEST)){
					if($eventDate2 = $_REQUEST["eventdate2"]){
						if($eventDate2 != $eventDate){
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
		}
		if(array_key_exists("catnum",$_REQUEST)){
			$catNum = $this->cleanInputStr(str_replace(",",";",$_REQUEST["catnum"]));
			if($catNum){
				$this->searchTermArr["catnum"] = $catNum;
				if(array_key_exists("includeothercatnum",$_REQUEST)){
					$this->searchTermArr["othercatnum"] = '1';
				}
			}
			else{
				unset($this->searchTermArr["catnum"]);
			}
		}
		if(array_key_exists("typestatus",$_REQUEST)){
			if($_REQUEST["typestatus"]){
				$this->searchTermArr["typestatus"] = true;
			}
			else{
				unset($this->searchTermArr["typestatus"]);
			}
		}
		if(array_key_exists("hasimages",$_REQUEST)){
			if($_REQUEST["hasimages"]){
				$this->searchTermArr["hasimages"] = true;
			}
			else{
				unset($this->searchTermArr["hasimages"]);
			}
		}
		if(array_key_exists("hasgenetic",$_REQUEST)){
			if($_REQUEST["hasgenetic"]){
				$this->searchTermArr["hasgenetic"] = true;
			}
			else{
				unset($this->searchTermArr["hasgenetic"]);
			}
		}
		$llPattern = '-?\d+\.{0,1}\d*';
		if(array_key_exists("upperlat",$_REQUEST)){
			$upperLat = ''; $bottomlat = ''; $leftLong = ''; $rightlong = '';
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST["upperlat"]), $m1)){
				$upperLat = round($m1[1],5);
				$uLatDir = (isset($_REQUEST['upperlat_NS'])?strtoupper($_REQUEST['upperlat_NS']):'');
				if(($uLatDir == 'N' && $upperLat < 0) || ($uLatDir == 'S' && $upperLat > 0)) $upperLat *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST["bottomlat"]), $m2)){
				$bottomlat = round($m2[1],5);
				$bLatDir = (isset($_REQUEST['bottomlat_NS'])?strtoupper($_REQUEST['bottomlat_NS']):'');
				if(($bLatDir == 'N' && $bottomlat < 0) || ($bLatDir == 'S' && $bottomlat > 0)) $bottomlat *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['leftlong']), $m3)){
				$leftLong = round($m3[1],5);
				$lLngDir = (isset($_REQUEST['leftlong_EW'])?strtoupper($_REQUEST['leftlong_EW']):'');
				if(($lLngDir == 'E' && $leftLong < 0) || ($lLngDir == 'W' && $leftLong > 0)) $leftLong *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['rightlong']), $m4)){
				$rightlong = round($m4[1],5);
				$rLngDir = (isset($_REQUEST['rightlong_EW'])?strtoupper($_REQUEST['rightlong_EW']):'');
				if(($rLngDir == 'E' && $rightlong < 0) || ($rLngDir == 'W' && $rightlong > 0)) $rightlong *= -1;
			}

			if(is_numeric($upperLat) && is_numeric($bottomlat) && is_numeric($leftLong) && is_numeric($rightlong)){
				$latLongStr = $upperLat.';'.$bottomlat.';'.$leftLong.';'.$rightlong;
				$this->searchTermArr["llbound"] = $latLongStr;
			}
			else{
				unset($this->searchTermArr["llbound"]);
			}
		}
		if(array_key_exists("llbound",$_REQUEST) && $_REQUEST['llbound']){
			$this->searchTermArr["llbound"] = $this->cleanInputStr($_REQUEST['llbound']);
		}
		if(array_key_exists("pointlat",$_REQUEST)){
			$pointLat = ''; $pointLong = ''; $radius = '';
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['pointlat']), $m1)){
				$pointLat = $m1[1];
				if(isset($_REQUEST['pointlat_NS'])){
					if($_REQUEST['pointlat_NS'] == 'S' && $pointLat > 0) $pointLat *= -1;
					elseif($_REQUEST['pointlat_NS'] == 'N' && $pointLat < 0) $pointLat *= -1;
				}
			}
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['pointlong']), $m2)){
				$pointLong = $m2[1];
				if(isset($_REQUEST['pointlong_EW'])){
					if($_REQUEST['pointlong_EW'] == 'W' && $pointLong > 0) $pointLong *= -1;
					elseif($_REQUEST['pointlong_EW'] == 'E' && $pointLong < 0) $pointLong *= -1;
				}
			}
			if(preg_match('/(\d+)/', $_REQUEST['radius'], $m3)){
				$radius = $m3[1];
			}
			if($pointLat && $pointLong && is_numeric($radius)){
				$radiusUnits = (isset($_REQUEST['radiusunits'])?$this->cleanInputStr($_REQUEST['radiusunits']):'mi');
				$pointRadiusStr = $pointLat.';'.$pointLong.';'.$radius.';'.$radiusUnits;
				$this->searchTermArr['llpoint'] = $pointRadiusStr;
			}
			else{
				unset($this->searchTermArr["llpoint"]);
			}
		}
		if(array_key_exists("llpoint",$_REQUEST) && $_REQUEST['llpoint']){
			$this->searchTermArr["llpoint"] = $this->cleanInputStr($_REQUEST['llpoint']);
		}
		if(array_key_exists("footprintwkt",$_REQUEST) && $_REQUEST['footprintwkt']){
			$this->searchTermArr["footprintwkt"] = $this->cleanInputStr($_REQUEST['footprintwkt']);
		}
	}

	private function setChecklistVariables($clid){
		$this->voucherManager = new ChecklistVoucherAdmin($this->conn);
		$this->voucherManager->setClid($clid);
		$this->voucherManager->setCollectionVariables();
	}

	//Misc return functions
	//Setters and getters
	public function getClName(){
		if(!$this->voucherManager) return false;
		return $this->voucherManager->getClName();
	}

	public function getClFootprintWkt(){
		if(!$this->voucherManager) return false;
		return $this->voucherManager->getClFootprintWkt();
	}

	public function getTaxaArr(){
		return $this->taxaArr;
	}
}
?>