<?php
include_once($serverRoot.'/config/dbconnection.php');
class MapInterfaceManager{
	
	protected $conn;
	protected $searchTermsArr = Array();
	protected $localSearchArr = Array();
	protected $reset = 0;
	protected $dynamicClid;
	protected $recordCount = 0;
	private $taxaArr = Array();
	private $collArr = Array();
	private $taxaSearchType;
	private $clName;
	private $collArrIndex = 0;
	private $iconColors = Array();
	private $googleIconArr = Array();
	private $fieldArr = Array();
	private $sqlWhere;
	private $searchTerms = 0;
	
    public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
    	$this->googleIconArr = array('pushpin/ylw-pushpin','pushpin/blue-pushpin','pushpin/grn-pushpin','pushpin/ltblu-pushpin',
			'pushpin/pink-pushpin','pushpin/purple-pushpin', 'pushpin/red-pushpin','pushpin/wht-pushpin','paddle/blu-blank',
			'paddle/grn-blank','paddle/ltblu-blank','paddle/pink-blank','paddle/wht-blank','paddle/blu-diamond','paddle/grn-diamond',
			'paddle/ltblu-diamond','paddle/pink-diamond','paddle/ylw-diamond','paddle/wht-diamond','paddle/red-diamond','paddle/purple-diamond',
			'paddle/blu-circle','paddle/grn-circle','paddle/ltblu-circle','paddle/pink-circle','paddle/ylw-circle','paddle/wht-circle',
			'paddle/red-circle','paddle/purple-circle','paddle/blu-square','paddle/grn-square','paddle/ltblu-square','paddle/pink-square',
			'paddle/ylw-square','paddle/wht-square','paddle/red-square','paddle/purple-square','paddle/blu-stars','paddle/grn-stars',
			'paddle/ltblu-stars','paddle/pink-stars','paddle/ylw-stars','paddle/wht-stars','paddle/red-stars','paddle/purple-stars');
		$this->readRequestVariables();
    }
	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
	}
	
	private function getRandomColor(){
    	//echo "here";
		$first = str_pad(dechex(mt_rand(128,255)),2,'0',STR_PAD_LEFT);
		$second = str_pad(dechex(mt_rand(128,255)),2,'0',STR_PAD_LEFT);
		$third = str_pad(dechex(mt_rand(128,255)),2,'0',STR_PAD_LEFT);
		$color_code = $first.$second.$third;
		
		return $color_code;
    }
	
	public function getMysqlVersion(){
		$version = array();
		$output = '';
		if(mysqli_get_server_info($this->conn)){
			$output = mysqli_get_server_info($this->conn);
		}
		else{
			$output = shell_exec('mysql -V'); 
		}
		if($output){
			if(strpos($output,'MariaDB') !== false){
				$version["db"] = 'MariaDB';
			}
			else{
				$version["db"] = 'mysql';
				preg_match('@[0-9]+\.[0-9]+\.[0-9]+@',$output,$ver);
				$version["ver"] = $ver[0];
			}
		}
		return $version;
	}
	
	public function getSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("db",$this->searchTermsArr) && $this->searchTermsArr['db']){
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
						$dbStr .= ($dbStr?'OR ':'').'(o.CollID IN(SELECT collid FROM omcollcatlink WHERE (ccpk IN('.$dbArr[1].')))) ';
					}
					$sqlWhere .= 'AND ('.$dbStr.') ';
				}
			}
		}
		
		if(array_key_exists("taxa",$this->searchTermsArr)&&$this->searchTermsArr["taxa"]){
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
					$rs1 = $this->conn->query("SELECT tid FROM taxa WHERE (sciname = '".$key."')");
					if($r1 = $rs1->fetch_object()){
						$sqlWhereTaxa = 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid IN('.$r1->tid.'))) ';
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
								'FROM taxa t LEFT JOIN taxaenumtree e ON t.tid = e.tid '.
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
						$synArr = $valueArray["synonyms"];
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
		if(array_key_exists("clid",$this->searchTermsArr)&&$this->searchTermsArr["clid"]){
			$clidArr = explode(";",$this->searchTermsArr["clid"]);
			$tempArr = Array();
			foreach($clidArr as $value){
				$tempArr[] = "(v.CLID = ".trim($value).")";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$clidArr);
		}
		if(array_key_exists("country",$this->searchTermsArr)&&$this->searchTermsArr["country"]){
			$countryArr = explode(";",$this->searchTermsArr["country"]);
			$tempArr = Array();
			foreach($countryArr as $value){
				$tempArr[] = "(o.Country = '".trim($value)."')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countryArr);
		}
		if(array_key_exists("state",$this->searchTermsArr)&&$this->searchTermsArr["state"]){
			$stateAr = explode(";",$this->searchTermsArr["state"]);
			$tempArr = Array();
			foreach($stateAr as $value){
				$tempArr[] = "(o.StateProvince LIKE '".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$stateAr);
		}
		if(array_key_exists("county",$this->searchTermsArr)&&$this->searchTermsArr["county"]){
			$countyArr = explode(";",$this->searchTermsArr["county"]);
			$tempArr = Array();
			foreach($countyArr as $value){
				$tempArr[] = "(o.county LIKE '".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countyArr);
		}
		if(array_key_exists("local",$this->searchTermsArr)&&$this->searchTermsArr["local"]){
			$localArr = explode(";",$this->searchTermsArr["local"]);
			$tempArr = Array();
			foreach($localArr as $value){
				$tempArr[] = "(o.municipality LIKE '".trim($value)."%' OR o.Locality LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$localArr);
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
				$sqlWhere .= "AND (( 3959 * acos( cos( radians(".$pointArr[0].") ) * cos( radians( o.DecimalLatitude ) ) * cos( radians( o.DecimalLongitude ) - radians(".$pointArr[1].") ) + sin( radians(".$pointArr[0].") ) * sin(radians(o.DecimalLatitude)) ) ) < ".$pointArr[2].") ";
			}
			$this->localSearchArr[] = "Point radius: ".$pointArr[0].", ".$pointArr[1].", within ".$pointArr[2]." miles";
		}
		if(array_key_exists("polycoords",$this->searchTermsArr)){
			$coordArr = json_decode($this->searchTermsArr["polycoords"], true);
			if($coordArr){
				$coordStr = '';
				$coordStr = 'Polygon((';
				$keys = array();
				foreach($coordArr as $k => $v){
					$keys = array_keys($v);
					$coordStr .= $v[$keys[0]]." ".$v[$keys[1]].",";
				}
				$coordStr .= $coordArr[0][$keys[0]]." ";
				$coordStr .= $coordArr[0][$keys[1]]."))";
				$sqlWhere .= "AND (ST_Within(p.point,GeomFromText('".$coordStr." '))) ";
			}
		}
		if(array_key_exists("collector",$this->searchTermsArr)&&$this->searchTermsArr["collector"]){
			$collectorArr = explode(";",$this->searchTermsArr["collector"]);
			$tempArr = Array();
			foreach($collectorArr as $value){
				$tempArr[] = "(o.recordedBy LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(", ",$collectorArr);
		}
		if(array_key_exists("collnum",$this->searchTermsArr)&&$this->searchTermsArr["collnum"]){
			$collNumArr = explode(";",$this->searchTermsArr["collnum"]);
			$rnWhere = '';
			foreach($collNumArr as $v){
				$v = trim($v);
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$rnIsNum = true;
						$rnWhere = 'OR (o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
					}
					else{
						$catTerm = 'o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.recordnumber) = '.strlen($term2); 
						$rnWhere = 'OR ('.$catTerm.')';
					}
				}
				elseif(is_numeric($v)){
					$rnWhere .= 'OR (o.recordNumber = '.$v.') ';
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
		if(array_key_exists('eventdate1',$this->searchTermsArr)&&$this->searchTermsArr["eventdate1"]){
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
			if($eDate1 = $this->formatDate($dateArr[0])){
				$eDate2 = (count($dateArr)>1?$this->formatDate($dateArr[1]):'');
				if($eDate2){
					$sqlWhere .= 'AND (DATE(o.eventdate) BETWEEN "'.$eDate1.'" AND "'.$eDate2.'") ';
				}
				else{
					if(substr($eDate1,-5) == '00-00'){
						$sqlWhere .= 'AND (o.eventdate LIKE "'.substr($eDate1,0,5).'%") ';
					}
					elseif(substr($eDate1,-2) == '00'){
						$sqlWhere .= 'AND (o.eventdate LIKE "'.substr($eDate1,0,8).'%") ';
					}
					else{
						$sqlWhere .= 'AND (DATE(o.eventdate) = "'.$eDate1.'") ';
					}
				}
			}
			$this->localSearchArr[] = $this->searchTermsArr['eventdate1'].(isset($this->searchTermsArr['eventdate2'])?' to '.$this->searchTermsArr['eventdate2']:'');
		}
		if(array_key_exists('catnum',$this->searchTermsArr)&&$this->searchTermsArr["catnum"]){
			$catStr = $this->searchTermsArr['catnum'];
			$isOccid = false;
			if(substr($catStr,0,5) == 'occid'){
				$catStr = trim(substr($catStr,5));
				$isOccid = true;
			}
			$catArr = explode(',',str_replace(';',',',$catStr));
			$betweenFrag = array();
			$inFrag = array();
			foreach($catArr as $v){
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						if($isOccid){
							$betweenFrag[] = '(o.occid BETWEEN '.$term1.' AND '.$term2.')';
						}
						else{
							$betweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
						} 
					}
					else{
						$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2); 
						$betweenFrag[] = '('.$catTerm.')';
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
				if($isOccid){
					$catWhere .= 'OR (o.occid IN('.implode(',',$inFrag).')) ';
				}
				else{
					$catWhere .= 'OR (o.catalogNumber IN("'.implode('","',$inFrag).'")) ';
				} 
			}
			$sqlWhere .= 'AND ('.substr($catWhere,3).') ';
			$this->localSearchArr[] = $this->searchTermsArr['catnum'];
		}
		if(array_key_exists('othercatnum',$this->searchTermsArr)&&$this->searchTermsArr["othercatnum"]){
			$otherCatStr = $this->searchTermsArr['othercatnum'];
			$sqlWhere .= 'AND (o.otherCatalogNumbers IN("'.$otherCatStr.'")) ';
			$this->localSearchArr[] = $this->searchTermsArr['othercatnum'];
		}
		if(array_key_exists('typestatus',$this->searchTermsArr)&&$this->searchTermsArr["typestatus"]){
			$sqlWhere .= 'AND (o.typestatus IS NOT NULL) ';
			$this->localSearchArr[] = 'is type';
		}
		if(array_key_exists('hasimages',$this->searchTermsArr)&&$this->searchTermsArr["hasimages"]){
			$sqlWhere .= 'AND (o.occid IN(SELECT occid FROM images)) ';
			$this->localSearchArr[] = 'has images';
		}
		$retStr = '';
		if($sqlWhere){
			$retStr = 'WHERE '.substr($sqlWhere,4);
			$retStr .= " AND (o.sciname IS NOT NULL AND o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL) ";
		}
		else{
			//Make the sql valid, but return nothing
			$retStr = 'WHERE o.collid = -1 ';
		}
		return $retStr; 
	}
	
	protected function setSciNamesByVerns(){
        $sql = "SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus ts LEFT JOIN taxavernaculars v ON ts.TID = v.TID) ".
            "LEFT JOIN taxa t ON t.TID = ts.tidaccepted ";
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
		$result->close();
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
	
	public function getFullCollectionList($catId = ""){
		$retArr = array();
		//Set collection array
		$collIdArr = array();
		$catIdArr = array();
		if(isset($this->searchTermsArr['db']) && array_key_exists('db',$this->searchTermsArr)){
			$cArr = explode(';',$this->searchTermsArr['db']);
			$collIdArr = explode(',',$cArr[0]);
			if(isset($cArr[1])) $catIdStr = $cArr[1];
		}
		//Set collections
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, cat.category '.
			'FROM omcollections c LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
			'LEFT JOIN omcollcategories cat ON ccl.ccpk = cat.ccpk '.
			'ORDER BY ccl.sortsequence, cat.category, c.sortseq, c.CollectionName ';
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($r = $result->fetch_object()){
			$collType = (stripos($r->colltype, "observation") !== false?'obs':'spec');
			if($r->ccpk){
				if(!isset($retArr[$collType]['cat'][$r->ccpk]['name'])){
					$retArr[$collType]['cat'][$r->ccpk]['name'] = $r->category;
				}
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["instcode"] = $r->institutioncode;
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["collcode"] = $r->collectioncode;
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["collname"] = $r->collectionname;
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["icon"] = $r->icon;
			}
			else{
				$retArr[$collType]['coll'][$r->collid]["instcode"] = $r->institutioncode;
				$retArr[$collType]['coll'][$r->collid]["collcode"] = $r->collectioncode;
				$retArr[$collType]['coll'][$r->collid]["collname"] = $r->collectionname;
				$retArr[$collType]['coll'][$r->collid]["icon"] = $r->icon;
			}
		}
		$result->close();
		//Modify sort so that default catid is first
		if(isset($retArr['spec']['cat'][$catId])){
			$targetArr = $retArr['spec']['cat'][$catId];
			unset($retArr['spec']['cat'][$catId]);
			array_unshift($retArr['spec']['cat'],$targetArr);
		} 
		elseif(isset($retArr['obs']['cat'][$catId])){
			$targetArr = $retArr['obs']['cat'][$catId];
			unset($retArr['obs']['cat'][$catId]);
			array_unshift($retArr['obs']['cat'],$targetArr);
		}
		return $retArr;
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
		//Search will be confinded to a collid, catid, or will remain open to all collection
		//Limit collids and/or catids
		$dbStr = '';
		if(array_key_exists("db",$_REQUEST)){
			$dbs = $_REQUEST["db"];
			if(is_string($dbs)){
				$dbStr = $dbs.';';
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
		if(array_key_exists("taxa",$_REQUEST)){
			$taxa = $this->conn->real_escape_string($_REQUEST["taxa"]);
			$searchType = array_key_exists("type",$_REQUEST)?$this->conn->real_escape_string($_REQUEST["type"]):1;
			$this->searchTermsArr["type"] = $searchType;
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
					$rs->close();
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
		if(array_key_exists("checklistname",$_REQUEST)){
			$this->searchTermsArr["checklistname"] = $this->conn->real_escape_string($_REQUEST["checklistname"]);
		}
		if(array_key_exists("clid",$_REQUEST)){
            $clid = $this->conn->real_escape_string($_REQUEST["clid"]);
            if($clid){
                $this->searchTermsArr["clid"] = $this->conn->real_escape_string($_REQUEST["clid"]);
            }
            else{
                unset($this->searchTermsArr["clid"]);
            }
		}
		if(array_key_exists("gridSizeSetting",$_REQUEST)){
			$this->searchTermsArr["gridSizeSetting"] = $this->conn->real_escape_string($_REQUEST["gridSizeSetting"]);
		}
		if(array_key_exists("minClusterSetting",$_REQUEST)){
			$this->searchTermsArr["minClusterSetting"] = $this->conn->real_escape_string($_REQUEST["minClusterSetting"]);
		}
		if(array_key_exists("clusterSwitch",$_REQUEST)){
			$this->searchTermsArr["clusterSwitch"] = $this->conn->real_escape_string($_REQUEST["clusterSwitch"]);
		}
		if(array_key_exists("recordlimit",$_REQUEST)){
			$this->searchTermsArr["recordlimit"] = $this->conn->real_escape_string($_REQUEST["recordlimit"]);
		}
		if(array_key_exists("country",$_REQUEST)){
			$country = $this->conn->real_escape_string($_REQUEST["country"]);
			if($country){
				$str = str_replace(",",";",$country);
				if(stripos($str, "USA") !== false && stripos($str, "United States") === false){
					$str .= ";United States";
				}
				elseif(stripos($str, "United States") !== false && stripos($str, "USA") === false){
					$str .= ";USA";
				}
				$this->searchTermsArr["country"] = $str;
			}
        }
		if(array_key_exists("state",$_REQUEST)){
			$state = $this->conn->real_escape_string($_REQUEST["state"]);
			if($state){
				$str = str_replace(",",";",$state);
				$this->searchTermsArr["state"] = $str;
			}
        }
		if(array_key_exists("county",$_REQUEST)){
			$county = $this->conn->real_escape_string($_REQUEST["county"]);
			$county = str_ireplace(" Co.","",$county);
			$county = str_ireplace(" County","",$county);
			if($county){
				$str = str_replace(",",";",$county);
				$this->searchTermsArr["county"] = $str;
			}
		}
		if(array_key_exists("local",$_REQUEST)){
			$local = $this->conn->real_escape_string(trim($_REQUEST["local"]));
			if($local){
				$str = str_replace(",",";",$local);
				$this->searchTermsArr["local"] = $str;
			}
		}
		if(array_key_exists("collector",$_REQUEST)){
			$collector = $this->conn->real_escape_string(trim($_REQUEST["collector"]));
			if($collector){
				$str = str_replace(",",";",$collector);
				$this->searchTermsArr["collector"] = $str;
			}
		}
		if(array_key_exists("collnum",$_REQUEST)){
			$collNum = $this->conn->real_escape_string(trim($_REQUEST["collnum"]));
			if($collNum){
				$str = str_replace(",",";",$collNum);
				$this->searchTermsArr["collnum"] = $str;
			}
		}
		if(array_key_exists("eventdate1",$_REQUEST)){
			if($eventDate = $this->conn->real_escape_string(trim($_REQUEST["eventdate1"]))){
				$this->searchTermsArr["eventdate1"] = $eventDate;
				if(array_key_exists("eventdate2",$_REQUEST)){
					if($eventDate2 = $this->conn->real_escape_string(trim($_REQUEST["eventdate2"]))){
						if($eventDate2 != $eventDate){
							$this->searchTermsArr["eventdate2"] = $eventDate2;
						}
					}
				}
			}
		}
		if(array_key_exists("catnum",$_REQUEST)){
			$catNum = $this->conn->real_escape_string(trim($_REQUEST["catnum"]));
			if($catNum){
				$str = str_replace(",",";",$catNum);
				$this->searchTermsArr["catnum"] = $str;
			}
		}
		if(array_key_exists("othercatnum",$_REQUEST)){
			$otherCatNum = $this->conn->real_escape_string(trim($_REQUEST["othercatnum"]));
			if($otherCatNum){
				$str = str_replace(",",";",$otherCatNum);
				$this->searchTermsArr["othercatnum"] = $str;
			}
		}
		if(array_key_exists("typestatus",$_REQUEST)){
			$typestatus = $_REQUEST["typestatus"];
			if($typestatus){
				$this->searchTermsArr["typestatus"] = true;
			}
		}
		if(array_key_exists("hasimages",$_REQUEST)){
			$hasimages = $_REQUEST["hasimages"];
			if($hasimages){
				$this->searchTermsArr["hasimages"] = true;
			}
		}
		$latLongArr = Array();
		if(array_key_exists("upperlat",$_REQUEST)){
			$upperLat = $this->conn->real_escape_string($_REQUEST["upperlat"]);
			if($upperLat){
                $this->searchTermsArr["upperlat"] = $_REQUEST["upperlat"];
                if($upperLat || $upperLat === "0") $latLongArr[] = $upperLat;
                $bottomlat = $this->conn->real_escape_string($_REQUEST["bottomlat"]);
                $this->searchTermsArr["bottomlat"] = $_REQUEST["bottomlat"];
                if($bottomlat || $bottomlat === "0") $latLongArr[] = $bottomlat;
                $leftLong = $this->conn->real_escape_string($_REQUEST["leftlong"]);
                $this->searchTermsArr["leftlong"] = $_REQUEST["leftlong"];
                if($leftLong || $leftLong === "0") $latLongArr[] = $leftLong;
                $rightlong = $this->conn->real_escape_string($_REQUEST["rightlong"]);
                $this->searchTermsArr["rightlong"] = $_REQUEST["rightlong"];
                if($rightlong || $rightlong === "0") $latLongArr[] = $rightlong;
                if(count($latLongArr) == 4){
                    $this->searchTermsArr["llbound"] = implode(";",$latLongArr);
                }
            }
            else{
                unset($this->searchTermsArr["upperlat"]);
                unset($this->searchTermsArr["bottomlat"]);
                unset($this->searchTermsArr["leftlong"]);
                unset($this->searchTermsArr["rightlong"]);
            }
		}
		if(array_key_exists("pointlat",$_REQUEST)){
			$pointLat = $this->conn->real_escape_string($_REQUEST["pointlat"]);
			if($pointLat){
                $this->searchTermsArr["pointlat"] = $_REQUEST["pointlat"];
                if($pointLat || $pointLat === "0") $latLongArr[] = $pointLat;
                $pointLong = $this->conn->real_escape_string($_REQUEST["pointlong"]);
                $this->searchTermsArr["pointlong"] = $_REQUEST["pointlong"];
                if($pointLong || $pointLong === "0") $latLongArr[] = $pointLong;
                $radius = $this->conn->real_escape_string($_REQUEST["radius"]);
                $this->searchTermsArr["radius"] = $_REQUEST["radius"];
                if($radius) $latLongArr[] = $radius;
                if(count($latLongArr) == 3){
                    $this->searchTermsArr["llpoint"] = implode(";",$latLongArr);
                }
            }
            else{
                unset($this->searchTermsArr["pointlat"]);
                unset($this->searchTermsArr["pointlong"]);
                unset($this->searchTermsArr["radius"]);
            }
		}
		if(array_key_exists("poly_array",$_REQUEST)){
			$jsonPolyArr = $_REQUEST["poly_array"];
			if($jsonPolyArr){
				$this->searchTermsArr["polycoords"] = substr(json_encode($jsonPolyArr),1,-1);
			}
		}
	}
	
	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
    public function getGenObsInfo(){
		$retVar = array();
		$sql = 'SELECT collid, CollType '.
			'FROM omcollections ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(stripos($r->CollType, "observation") !== false){
					$retVar[] = $r->collid;
				}
			}
			$rs->close();
		}
		return $retVar;
	}
	
	public function getFullCollArr($stArr){
		$sql = '';
		$this->collArr = Array();
		$sql = '';
		$sql = 'SELECT c.CollID, c.CollectionName '.
			'FROM omcollections AS c ';
		if($stArr['db'] != 'all'){
			$dbArr = explode(';',$stArr["db"]);
			$dbStr = '';
			$sql .= 'WHERE (c.collid IN('.trim($dbArr[0]).')) ';
        }
		$sql .= 'ORDER BY c.CollectionName ';
        //echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$collName = $row->CollectionName;
			$this->collArr[$collName] = Array();
		}
		$result->close();
		
		//return $sql;
	}
	
	public function getCollGeoCoords($mapWhere,$pageRequest,$cntPerPage){
		global $userRights, $mappingBoundaries;
		$coordArr = Array();
		$sql = '';
		$sql = 'SELECT o.occid, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS identifier, '.
			'o.sciname, o.family, o.tidinterpreted, o.DecimalLatitude, o.DecimalLongitude, o.collid, o.catalognumber, '.
			'o.othercatalognumbers, c.institutioncode, c.collectioncode, c.CollectionName ';
		if($this->fieldArr){
			foreach($this->fieldArr as $k => $v){
				$sql .= ", o.".$v." ";
			}
		}
		$sql .= "FROM omoccurrences AS o LEFT JOIN omcollections AS c ON o.collid = c.collid ";
        if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "LEFT JOIN fmvouchers AS v ON o.occid = v.occid ";
		if(array_key_exists("polycoords",$this->searchTermsArr)) $sql .= "LEFT JOIN omoccurpoints AS p ON o.occid = p.occid ";
		$sql .= $mapWhere;
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		if($pageRequest && $cntPerPage){
            $sql .= "LIMIT ".$pageRequest.",".$cntPerPage;
        }
		$collMapper = Array();
		$collMapper["undefined"] = "undefined";
		$usedColors = Array();
        $color = 'e69e67';
		//echo json_encode($this->taxaArr);
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$recCnt = 0;
		while($row = $result->fetch_object()){
			if(($row->DecimalLongitude <= 180 && $row->DecimalLongitude >= -180) && ($row->DecimalLatitude <= 90 && $row->DecimalLatitude >= -90)){
                $occId = $row->occid;
                $collName = $row->CollectionName;
                $family = $row->family;
                $latLngStr = $row->DecimalLatitude.",".$row->DecimalLongitude;
                $coordArr[$collName][$occId]["latLngStr"] = $latLngStr;
                $coordArr[$collName][$occId]["collid"] = $this->xmlentities($row->collid);
                if($row->tidinterpreted){
                    $coordArr[$collName][$occId]["tidinterpreted"] = $this->xmlentities($row->tidinterpreted);
                }
                else{
                    $tidcode = strtolower(str_replace( " ", "",$row->sciname));
                    $tidcode = preg_replace( "/[^A-Za-z0-9 ]/","",$tidcode);
                    $coordArr[$collName][$occId]["tidinterpreted"] = $this->xmlentities($tidcode);
                }
                if($family){
                    $coordArr[$collName][$occId]["family"] = strtoupper($family);
                }
                else{
                    $coordArr[$collName][$occId]["family"] = 'undefined';
                }
                $coordArr[$collName][$occId]["sciname"] = $row->sciname;
                $coordArr[$collName][$occId]["identifier"] = $this->xmlentities($row->identifier);
                $coordArr[$collName][$occId]["institutioncode"] = $this->xmlentities($row->institutioncode);
                $coordArr[$collName][$occId]["collectioncode"] = $this->xmlentities($row->collectioncode);
                $coordArr[$collName][$occId]["catalognumber"] = $this->xmlentities($row->catalognumber);
                $coordArr[$collName][$occId]["othercatalognumbers"] = $this->xmlentities($row->othercatalognumbers);
                $coordArr[$collName]["color"] = $color;
                if($this->fieldArr){
                    foreach($this->fieldArr as $k => $v){
                        $coordArr[$collName][$occId][$v] = $this->xmlentities($row->$v);
                    }
                }
            }
		}
		if(array_key_exists("undefined",$coordArr)){
			$coordArr["undefined"]["color"] = $color;
		}
		$result->free();
		
		if($recCnt > $recLimit){
			$coordArr = $recCnt;
		}
		
		return $coordArr;
		//return $sql;
	}
	
	public function getSelectionGeoCoords($seloccids){
		global $userRights, $mappingBoundaries;
		$seloccids = preg_match('#\[(.*?)\]#', $seloccids, $match);
		$seloccids = $match[1];
		$coordArr = Array();
		$sql = '';
		$sql = 'SELECT o.occid, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS identifier, '.
			'o.sciname, o.family, o.tidinterpreted, o.DecimalLatitude, o.DecimalLongitude, o.collid, '.
			'o.catalognumber, o.othercatalognumbers, c.institutioncode, c.collectioncode, c.CollectionName '.
			'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid '.
			'WHERE o.occid IN('.$seloccids.') ';
		$collMapper = Array();
		$collMapper["undefined"] = "undefined";
		//echo json_encode($this->taxaArr);
		foreach($this->collArr as $key => $valueArr){
			$color = 'e69e67';
			$coordArr[$key] = Array("color" => $color);
			$collMapper[$key] = $key;
		}
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$recCnt = 0;
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$collName = $row->CollectionName;
			$latLngStr = $row->DecimalLatitude.",".$row->DecimalLongitude;
			if(!array_key_exists($collName,$collMapper)) $collName = "undefined"; 
			$coordArr[$collMapper[$collName]][$occId]["latLngStr"] = $latLngStr;
			$coordArr[$collMapper[$collName]][$occId]["collid"] = $this->xmlentities($row->collid);
			$coordArr[$collMapper[$collName]][$occId]["tidinterpreted"] = $this->xmlentities($row->tidinterpreted);
			$coordArr[$collMapper[$collName]][$occId]["identifier"] = $this->xmlentities($row->identifier);
			$coordArr[$collMapper[$collName]][$occId]["institutioncode"] = $this->xmlentities($row->institutioncode);
			$coordArr[$collMapper[$collName]][$occId]["collectioncode"] = $this->xmlentities($row->collectioncode);
			$coordArr[$collMapper[$collName]][$occId]["catalognumber"] = $this->xmlentities($row->catalognumber);
			$coordArr[$collMapper[$collName]][$occId]["othercatalognumbers"] = $this->xmlentities($row->othercatalognumbers);
		}
		if(array_key_exists("undefined",$coordArr)){
			$coordArr["undefined"]["color"] = $color;
		}
		$result->close();
		
		return $coordArr;
		//return $sql;
	}
	
    public function writeKMLFile($coordArr){
    	global $defaultTitle, $userRights, $clientRoot, $charset;
		$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10) $fileName = substr($fileName,0,10);
			$fileName = str_replace(".","",$fileName);
			$fileName = str_replace(" ","_",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= time().".kml";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-type: application/vnd.google-earth.kml+xml');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 
		echo "<?xml version='1.0' encoding='".$charset."'?>\n";
        echo "<kml xmlns='http://www.opengis.net/kml/2.2'>\n";
        echo "<Document>\n";
		echo "<Folder>\n<name>".$defaultTitle." Specimens - ".date('j F Y g:ia')."</name>\n";
        
		$cnt = 0;
		foreach($coordArr as $sciName => $contentArr){
			$iconStr = $this->googleIconArr[$cnt%44];
			$cnt++;
			unset($contentArr["color"]);
			
			echo "<Style id='sn_".$iconStr."'>\n";
            echo "<IconStyle><scale>1.1</scale><Icon>";
			echo "<href>http://maps.google.com/mapfiles/kml/".$iconStr.".png</href>";
			echo "</Icon><hotSpot x='20' y='2' xunits='pixels' yunits='pixels'/></IconStyle>\n</Style>\n";
			echo "<Style id='sh_".$iconStr."'>\n";
            echo "<IconStyle><scale>1.3</scale><Icon>";
			echo "<href>http://maps.google.com/mapfiles/kml/".$iconStr.".png</href>";
			echo "</Icon><hotSpot x='20' y='2' xunits='pixels' yunits='pixels'/></IconStyle>\n</Style>\n";
			echo "<StyleMap id='".htmlspecialchars(str_replace(" ","_",$sciName), ENT_QUOTES)."'>\n";
            echo "<Pair><key>normal</key><styleUrl>#sn_".$iconStr."</styleUrl></Pair>";
			echo "<Pair><key>highlight</key><styleUrl>#sh_".$iconStr."</styleUrl></Pair>";
			echo "</StyleMap>\n";
			echo "<Folder><name>".htmlspecialchars($sciName, ENT_QUOTES)."</name>\n";
			foreach($contentArr as $occId => $pointArr){
				echo "<Placemark>\n";
				echo "<name>".htmlspecialchars($pointArr["identifier"], ENT_QUOTES)."</name>\n";
				echo "<ExtendedData>\n";
				echo "<Data name='institutioncode'>".htmlspecialchars($pointArr["institutioncode"], ENT_QUOTES)."</Data>\n";
				echo "<Data name='collectioncode'>".htmlspecialchars($pointArr["collectioncode"], ENT_QUOTES)."</Data>\n";
				echo "<Data name='catalognumber'>".htmlspecialchars($pointArr["catalognumber"], ENT_QUOTES)."</Data>\n";
				echo "<Data name='othercatalognumbers'>".htmlspecialchars($pointArr["othercatalognumbers"], ENT_QUOTES)."</Data>\n";
				if($this->fieldArr){
					foreach($this->fieldArr as $k => $v){
						echo "<Data name='".$v."'>".$pointArr[$v]."</Data>\n";
					}
				}
				echo "<Data name='DataSource'>Data retrieved from ".$defaultTitle." Data Portal</Data>\n";
				$url = "http://".$_SERVER["SERVER_NAME"].$clientRoot."/collections/individual/index.php?occid=".$occId;
				echo "<Data name='RecordURL'>".$url."</Data>\n";
				echo "</ExtendedData>\n";
				echo "<styleUrl>#".htmlspecialchars(str_replace(" ","_",$sciName), ENT_QUOTES)."</styleUrl>\n";
				echo "<Point><coordinates>".implode(",",array_reverse(explode(",",$pointArr["latLngStr"]))).",0</coordinates></Point>\n";
				echo "</Placemark>\n";
			}
			echo "</Folder>\n";
		}
		echo "</Folder>\n";
		echo "</Document>\n";
		echo "</kml>\n";
    }
	
	private function xmlentities($string){
		return str_replace(array ('&','"',"'",'<','>','?'),array ('&amp;','&quot;','&apos;','&lt;','&gt;','&apos;'),$string);
	}
	
    //Setters and getters
    public function setFieldArr($fArr){
    	$this->fieldArr = $fArr;
    }
	
	public function setSearchTermsArr($stArr){
    	$this->searchTermsArr = $stArr;
		$this->searchTerms = 1;
    }
	
	public function getSearchTermsArr(){
    	return $this->searchTermsArr;
    }
	
	//New Map Interface functions
	private function formatDate($inDate){
		$inDate = trim($inDate);
		$retDate = '';
		$y=''; $m=''; $d='';
		if(preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/',$inDate)){
			$dateTokens = explode('-',$inDate);
			$y = $dateTokens[0];
			$m = $dateTokens[1];
			$d = $dateTokens[2];
		}
		elseif(preg_match('/^\d{1,2}\/*\d{0,2}\/\d{2,4}$/',$inDate)){
			//dd/mm/yyyy
			$dateTokens = explode('/',$inDate);
			$m = $dateTokens[0];
			if(count($dateTokens) == 3){
				$d = $dateTokens[1];
				$y = $dateTokens[2];
			}
			else{
				$d = '00';
				$y = $dateTokens[1];
			}
		}
		elseif(preg_match('/^\d{0,2}\s*\D+\s*\d{2,4}$/',$inDate)){
			$dateTokens = explode(' ',$inDate);
			if(count($dateTokens) == 3){
				$y = $dateTokens[2];
				$mText = substr($dateTokens[1],0,3);
				$d = $dateTokens[0];
			}
			else{
				$y = $dateTokens[1];
				$mText = substr($dateTokens[0],0,3);
				$d = '00';
			}
			$mText = strtolower($mText);
			$mNames = Array("ene"=>1,"jan"=>1,"feb"=>2,"mar"=>3,"abr"=>4,"apr"=>4,"may"=>5,"jun"=>6,"jul"=>7,"ago"=>8,"aug"=>8,"sep"=>9,"oct"=>10,"nov"=>11,"dic"=>12,"dec"=>12);
			$m = $mNames[$mText];
		}
		elseif(preg_match('/^\s*\d{4}\s*$/',$inDate)){
			$retDate = $inDate.'-00-00';
		}
		elseif($dateObj = strtotime($inDate)){
			$retDate = date('Y-m-d',$dateObj);
		}
		if(!$retDate && $y){
			if(strlen($y) == 2){
				if($y < 20){
					$y = "20".$y;
				}
				else{
					$y = "19".$y;
				}
			}
			if(strlen($m) == 1){
				$m = '0'.$m;
			}
			if(strlen($d) == 1){
				$d = '0'.$d;
			}
			$retDate = $y.'-'.$m.'-'.$d;
		}
		return $retDate;
	}
	
	public function outputFullMapCollArr($dbArr,$occArr,$defaultCatid = 0){
		$collCnt = 0;
		if(isset($occArr['cat'])){
			$catArr = $occArr['cat'];
			?>
			<table>
			<?php 
			foreach($catArr as $catid => $catArr){
				$name = $catArr["name"];
				unset($catArr["name"]);
				$idStr = $this->collArrIndex.'-'.$catid;
				?>
				<tr>
					<td>
						<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
							<img id="plus-<?php echo $idStr; ?>" src="../../images/plus_sm.png" style="<?php echo ($defaultCatid==$catid?'display:none;':'') ?>" /><img id="minus-<?php echo $idStr; ?>" src="../../images/minus_sm.png" style="<?php echo ($defaultCatid==$catid?'':'display:none;') ?>" />
						</a>
					</td>
					<td>
						<input id="cat<?php echo $idStr; ?>Input" data-role="none" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ((in_array($catid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> /> 
					</td>
					<td>
			    		<span style='text-decoration:none;color:black;font-size:14px;font-weight:bold;'>
				    		<a href = '../misc/collprofiles.php?catid=<?php echo $catid; ?>' target="_blank" ><?php echo $name; ?></a>
				    	</span>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<div id="cat-<?php echo $idStr; ?>" style="<?php echo ($defaultCatid==$catid?'':'display:none;') ?>margin:10px 0px;">
							<table style="margin-left:15px;">
						    	<?php 
								foreach($catArr as $collid => $collName2){
						    		?>
						    		<tr>
										<td>
											<?php 
											if($collName2["icon"]){
												$cIcon = (substr($collName2["icon"],0,6)=='images'?'../../':'').$collName2["icon"]; 
												?>
												<a href = '../misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
													<img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
												</a>
										    	<?php
											}
										    ?>
										</td>
										<td style="padding:6px">
								    		<input name="db[]" value="<?php echo $collid; ?>" data-role="none" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat<?php echo $catid; ?>Input')" <?php echo ((in_array($collid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> /> 
										</td>
										<td style="padding:6px">
								    		<a href = '../misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
								    			<?php echo $collName2["collname"]." (".$collName2["instcode"].")"; ?>
								    		</a>
								    		<a href = '../misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
								    			more info
								    		</a>
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
			}
			?>
			</table>
			<?php 
		}
		if(isset($occArr['coll'])){
			$collArr = $occArr['coll'];
			?>
			<table>
			<?php 
			foreach($collArr as $collid => $cArr){
				?>
				<tr>
					<td>
						<?php 
						if($cArr["icon"]){
							$cIcon = (substr($cArr["icon"],0,6)=='images'?'../../':'').$cArr["icon"]; 
							?>
							<a href = '../misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
								<img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
							</a>
					    	<?php
						}
					    ?>
					    &nbsp;
					</td>
					<td style="padding:6px;">
			    		<input name="db[]" value="<?php echo $collid; ?>" data-role="none" type="checkbox" onclick="uncheckAll(this.form)" <?php echo ((in_array($collid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> /> 
					</td>
					<td style="padding:6px">
			    		<a href = '../misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
			    			<?php echo $cArr["collname"]." (".$cArr["instcode"].")"; ?>
			    		</a>
			    		<a href = '../misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
			    			more info
			    		</a>
				    </td>
				</tr>
				<?php
				$collCnt++;
			}
			?>
			</table>
			<?php 
		}
		$this->collArrIndex++;
	}
	
	public function setRecordCnt($sqlWhere){
		global $userRights, $clientRoot;
		if($sqlWhere){
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ";
			if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "LEFT JOIN fmvouchers AS v ON o.occid = v.occid ";
			if(array_key_exists("polycoords",$this->searchTermsArr)) $sql .= "LEFT JOIN omoccurpoints p ON o.occid = p.occid ";
			$sql .= $sqlWhere;
			if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
				//Is global rare species reader, thus do nothing to sql and grab all records
			}
			elseif(array_key_exists("RareSppReader",$userRights)){
				$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
			}
			else{
				$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
			}
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->close();
		}
	}
	public function getRecordCnt(){
		return $this->recordCount;
	}
	
	public function getMapSpecimenArr($pageRequest,$cntPerPage,$mapWhere){
		global $userRights;
		$retArr = Array();
		if(!$this->recordCount){
			$this->setRecordCnt($mapWhere);
		}
		$sql = 'SELECT o.occid, c.institutioncode, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, '.
			'o.eventdate, o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude, '.
			'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason '.
			'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
		if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "LEFT JOIN fmvouchers AS v ON o.occid = v.occid ";
		if(array_key_exists("polycoords",$this->searchTermsArr)) $sql .= "LEFT JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere;
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= "ORDER BY o.sciname, o.eventdate ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		echo "<div>Spec sql: ".$sql."</div>"; exit;
		$result = $this->conn->query($sql);
		$canReadRareSpp = false;
		if(array_key_exists("SuperAdmin", $userRights) || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$canReadRareSpp = true;
		}
		while($r = $result->fetch_object()){
			$occId = $r->occid;
			$retArr[$occId]['i'] = $this->cleanOutStr($r->institutioncode);
			$retArr[$occId]['cat'] = $this->cleanOutStr($r->catalognumber);
			$retArr[$occId]['c'] = $this->cleanOutStr($r->collector);
			$retArr[$occId]['e'] = $this->cleanOutStr($r->eventdate);
			$retArr[$occId]['f'] = $this->cleanOutStr($r->family);
			$retArr[$occId]['s'] = $this->cleanOutStr($r->sciname);
			$retArr[$occId]['l'] = $this->cleanOutStr($r->locality);
			$retArr[$occId]['lat'] = $this->cleanOutStr($r->DecimalLatitude);
			$retArr[$occId]['lon'] = $this->cleanOutStr($r->DecimalLongitude);
			$localitySecurity = $r->LocalitySecurity;
			if(!$localitySecurity || $canReadRareSpp 
				|| (array_key_exists("CollEditor", $userRights) && in_array($collIdStr,$userRights["CollEditor"]))
				|| (array_key_exists("RareSppReader", $userRights) && in_array($collIdStr,$userRights["RareSppReader"]))){
				$retArr[$occId]['l'] = str_replace('.,',',',$r->locality);
			}
			else{
				$securityStr = '<span style="color:red;">Detailed locality information protected. ';
				if($r->localitysecurityreason){
					$securityStr .= $r->localitysecurityreason;
				}
				else{
					$securityStr .= 'This is typically done to protect rare or threatened species localities.';
				}
				$retArr[$occId]['l'] = $securityStr.'</span>';
			}
		}
		$result->close();
		return $retArr;
		//return $sql;
	}
	
	public function getTaxaArr(){
    	return $this->taxaArr;
    }
	
	public function createShape($previousCriteria){
		$queryShape = '';
		$shapeBounds = '';
		$properties = '';
		$properties = 'strokeWeight: 0,';
		$properties .= 'fillOpacity: 0.45,';
		$properties .= 'editable: true,';
		//$properties .= 'draggable: true,';
		$properties .= 'map: map});';
		
		if(($previousCriteria["upperlat"]) || ($previousCriteria["pointlat"]) || ($previousCriteria["poly_array"])){
			if($previousCriteria["upperlat"]){
				$queryShape = 'var queryRectangle = new google.maps.Rectangle({';
				$queryShape .= 'bounds: new google.maps.LatLngBounds(';
				$queryShape .= 'new google.maps.LatLng('.$previousCriteria["bottomlat"].', '.$previousCriteria["leftlong"].'),';
				$queryShape .= 'new google.maps.LatLng('.$previousCriteria["upperlat"].', '.$previousCriteria["rightlong"].')),';
				$queryShape .= $properties;
				$queryShape .= "queryRectangle.type = 'rectangle';";
				$queryShape .= "google.maps.event.addListener(queryRectangle, 'click', function() {";
				$queryShape .= 'setSelection(queryRectangle);});';
				$queryShape .= "google.maps.event.addListener(queryRectangle, 'dragend', function() {";
				$queryShape .= 'setSelection(queryRectangle);});';
				$queryShape .= "google.maps.event.addListener(queryRectangle, 'bounds_changed', function() {";
				$queryShape .= 'setSelection(queryRectangle);});';
				$queryShape .= 'setSelection(queryRectangle);';
				$queryShape .= 'var queryShapeBounds = new google.maps.LatLngBounds();';
				$queryShape .= 'queryShapeBounds.extend(new google.maps.LatLng('.$previousCriteria["bottomlat"].', '.$previousCriteria["leftlong"].'));';
				$queryShape .= 'queryShapeBounds.extend(new google.maps.LatLng('.$previousCriteria["upperlat"].', '.$previousCriteria["rightlong"].'));';
				$queryShape .= 'map.fitBounds(queryShapeBounds);';
				$queryShape .= 'map.panToBounds(queryShapeBounds);';
			}
			if($previousCriteria["pointlat"]){
				$radius = (($previousCriteria["radius"]/0.6214)*1000);
				$queryShape = 'var queryCircle = new google.maps.Circle({';
				$queryShape .= 'center: new google.maps.LatLng('.$previousCriteria["pointlat"].', '.$previousCriteria["pointlong"].'),';
				$queryShape .= 'radius: '.$radius.',';
				$queryShape .= $properties;
				$queryShape .= "queryCircle.type = 'circle';";
				$queryShape .= "google.maps.event.addListener(queryCircle, 'click', function() {";
				$queryShape .= 'setSelection(queryCircle);});';
				$queryShape .= "google.maps.event.addListener(queryCircle, 'dragend', function() {";
				$queryShape .= 'setSelection(queryCircle);});';
				$queryShape .= "google.maps.event.addListener(queryCircle, 'radius_changed', function() {";
				$queryShape .= 'setSelection(queryCircle);});';
				$queryShape .= "google.maps.event.addListener(queryCircle, 'center_changed', function() {";
				$queryShape .= 'setSelection(queryCircle);});';
				$queryShape .= 'setSelection(queryCircle);';
				$queryShape .= 'var queryShapeBounds = queryCircle.getBounds();';
				$queryShape .= 'map.fitBounds(queryShapeBounds);';
				$queryShape .= 'map.panToBounds(queryShapeBounds);';
				
			}
			if($previousCriteria["poly_array"]){
				$coordArr = json_decode($previousCriteria["poly_array"], true);
				if($coordArr){
					$shapeBounds = 'var queryShapeBounds = new google.maps.LatLngBounds();';
					$queryShape = 'var queryPolygon = new google.maps.Polygon({';
					$queryShape .= 'paths: [';
					$keys = array();
					foreach($coordArr as $k => $v){
						$keys = array_keys($v);
						$queryShape .= 'new google.maps.LatLng('.$v[$keys[0]].', '.$v[$keys[1]].'),';
						$shapeBounds .= 'queryShapeBounds.extend(new google.maps.LatLng('.$v[$keys[0]].', '.$v[$keys[1]].'));';
					}
					$queryShape .= 'new google.maps.LatLng('.$coordArr[0][$keys[0]].', '.$coordArr[0][$keys[1]].')],';
					$shapeBounds .= 'queryShapeBounds.extend(new google.maps.LatLng('.$coordArr[0][$keys[0]].', '.$coordArr[0][$keys[1]].'));';
					$queryShape .= $properties;
					$queryShape .= "queryPolygon.type = 'polygon';";
					$queryShape .= "google.maps.event.addListener(queryPolygon, 'click', function() {";
					$queryShape .= 'setSelection(queryPolygon);});';
					$queryShape .= "google.maps.event.addListener(queryPolygon, 'dragend', function() {";
					$queryShape .= 'setSelection(queryPolygon);});';
					$queryShape .= "google.maps.event.addListener(queryPolygon.getPath(), 'insert_at', function() {";
					$queryShape .= 'setSelection(queryPolygon);});';
					$queryShape .= "google.maps.event.addListener(queryPolygon.getPath(), 'remove_at', function() {";
					$queryShape .= 'setSelection(queryPolygon);});';
					$queryShape .= "google.maps.event.addListener(queryPolygon.getPath(), 'set_at', function() {";
					$queryShape .= 'setSelection(queryPolygon);});';
					$queryShape .= 'setSelection(queryPolygon);';
					$queryShape .= $shapeBounds;
					$queryShape .= 'map.fitBounds(queryShapeBounds);';
					$queryShape .= 'map.panToBounds(queryShapeBounds);';
				}
			}
		}
		return $queryShape;
	}
	
	public function getChecklist($stArr,$mapWhere){
		$returnVec = Array();
		$this->checklistTaxaCnt = 0;
		$sql = "";
        $sql = 'SELECT DISTINCT t.tid, IFNULL(ts.family,o.family) AS family, IFNULL(t.sciname,o.sciname) AS sciname '.
			'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
			'LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
		if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "LEFT JOIN fmvouchers AS v ON o.occid = v.occid ";
		if(array_key_exists("polycoords",$stArr)) $sql .= "LEFT JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere." AND (ISNULL(ts.taxauthid) OR ts.taxauthid = 1) ";
		$sql .= " ORDER BY family, o.sciname ";
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if(!$family) $family = 'undefined';
			if($row->tid){
				$tidcode = $row->tid;
			}
			else{
				$tidcode = strtolower(str_replace( " ", "",$row->sciname));
				$tidcode = preg_replace( "/[^A-Za-z0-9 ]/","",$tidcode);
			}
			$sciName = $row->sciname;
			if($sciName){
				$returnVec[$family][$tidcode]["tid"] = $this->xmlentities($tidcode);
				$returnVec[$family][$tidcode]["sciname"] = $sciName;
				$this->checklistTaxaCnt++;
			}
        }
        return $returnVec;
		//return $sql;
	}
	
	public function getChecklistTaxaCnt(){
		return $this->checklistTaxaCnt;
	}
    public function getCollArr(){
        return $this->collArr;
    }
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
				'FROM taxa t LEFT JOIN taxstatus ts ON t.Tid = ts.TidAccepted '.
				'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tid IN('.implode(',',$targetTidArr).')) ';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$accArr[] = $r2->tid;
				$rankId = $r2->rankid;
				//Put in synonym array if not target
				if(!in_array($r2->tid,$targetTidArr)) $synArr[$r2->tid] = $r2->sciname;
			}
			$rs2->free();
	
			//Get synonym that are different than target
			$sql3 = 'SELECT DISTINCT t.tid, t.sciname '.
				'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tidaccepted IN('.implode('',$accArr).')) ';
			$rs3 = $this->conn->query($sql3);
			while($r3 = $rs3->fetch_object()){
				if(!in_array($r3->tid,$targetTidArr)) $synArr[$r3->tid] = $r3->sciname;
			}
			$rs3->free();
	
			//If rank is 220, get synonyms of accepted children
			if($rankId == 220){
				$sql4 = 'SELECT DISTINCT t.tid, t.sciname '.
					'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
					'WHERE (ts.parenttid IN('.implode('',$accArr).')) AND (ts.taxauthid = '.$taxAuthId.') '.
					'AND (ts.TidAccepted = ts.tid)';
				$rs4 = $this->conn->query($sql4);
				while($r4 = $rs4->fetch_object()){
					$synArr[$r4->tid] = $r4->sciname;
				}
				$rs4->free();
			}
		}
		return $synArr;
	}
	
	public function getGpxText($seloccids){
		global $defaultTitle;
		$seloccids = preg_match('#\[(.*?)\]#', $seloccids, $match);
		$seloccids = $match[1];
		$gpxText = '';
		$gpxText = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
		$gpxText .= '<gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1" creator="mymy">';
		$sql = "";
        $sql = 'SELECT o.occid, o.basisOfRecord, c.institutioncode, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, '.
			'o.eventdate, o.family, o.sciname, o.locality, o.DecimalLatitude, o.DecimalLongitude '.
			'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
		$sql .= 'WHERE o.occid IN('.$seloccids.') ';
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$comment = $row->institutioncode.($row->catalognumber?': '.$row->catalognumber.'. ':'. ');
			$comment .= $row->collector.'. '.$row->eventdate.'. Locality: '.$row->locality.' (occid: '.$row->occid.')';
			$gpxText .= '<wpt lat="'.$row->DecimalLatitude.'" lon="'.$row->DecimalLongitude.'">';
			$gpxText .= '<name>'.$row->sciname.'</name>';
			$gpxText .= '<cmt>'.$comment.'</cmt>';
			$gpxText .= '<sym>Waypoint</sym>';
			$gpxText .= '</wpt>';
		}
		$gpxText .= '</gpx>';
		
        return $gpxText;
	}
	
	public function getOccurrences($datasetId){
		$retArr = array();
		if($datasetId){
			$sql = 'SELECT o.occid, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, o.eventdate, '.
				'o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude '.
				'FROM omoccurrences o LEFT JOIN omoccurdatasetlink dl ON o.occid = dl.occid '.
				'WHERE dl.datasetid = '.$datasetId.' '.
				'ORDER BY o.sciname ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['occid'] = $r->occid;
				$retArr[$r->occid]['sciname'] = $r->sciname;
				$retArr[$r->occid]['catnum'] = $r->catalognumber;
				$retArr[$r->occid]['coll'] = $r->collector;
				$retArr[$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->occid]['occid'] = $r->occid;
				$retArr[$r->occid]['lat'] = $r->DecimalLatitude;
				$retArr[$r->occid]['long'] = $r->DecimalLongitude;
			}
			$rs->free();
		}
		if(count($retArr)>1){
			return $retArr;
		}
		else{
			return;
		}
	}
	
	public function getPersonalRecordsets($uid){
		$retArr = Array();
		$sql = "";
        //Get datasets owned by user
		$sql = 'SELECT datasetid, name '.
			'FROM omoccurdatasets '.
			'WHERE (uid = '.$uid.') '.
			'ORDER BY name';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->datasetid]['datasetid'] = $r->datasetid;
			$retArr[$r->datasetid]['name'] = $r->name;
			$retArr[$r->datasetid]['role'] = "DatasetAdmin";
		}
		$sql2 = 'SELECT d.datasetid, d.name, r.role '.
			'FROM omoccurdatasets d LEFT JOIN userroles r ON d.datasetid = r.tablepk '.
			'WHERE (r.uid = '.$uid.') AND (r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")) '.
			'ORDER BY sortsequence,name';
		$rs = $this->conn->query($sql2);
		while($r = $rs->fetch_object()){
			$retArr[$r->datasetid]['datasetid'] = $r->datasetid;
			$retArr[$r->datasetid]['name'] = $r->name;
			$retArr[$r->datasetid]['role'] = $r->role;
		}
		$rs->free();
		return $retArr;
		//return $sql;
	}
}
?>