<?php
include_once($serverRoot.'/config/dbconnection.php');

class MapInterfaceManager{
	
	protected $conn;
	protected $searchTermsArr = Array();
	protected $localSearchArr = Array();
	protected $useCookies = 1;
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
		$version = '';
		if(mysql_get_server_info()){
			$output = mysql_get_server_info();
			preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $ver);
			$version = $ver[0];
		}
		else{
			$output = shell_exec('mysql -V'); 
			preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $ver); 
			$version = $ver[0];
		}
		return $version;
	}
	
	public function getSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("surveyid",$this->searchTermsArr)){
			//$sqlWhere .= "AND (sol.surveyid IN('".str_replace(";","','",$this->searchTermsArr["surveyid"])."')) ";
			$sqlWhere .= "AND (sol.clid IN('".$this->searchTermsArr["surveyid"]."')) ";
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
						$dbStr .= ($dbStr?'OR ':'').'(o.CollID IN(SELECT collid FROM omcollcatlink WHERE (ccpk IN('.$dbArr[1].')))) ';
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
					$rs1 = $this->conn->query("SELECT tid FROM taxa WHERE (sciname = '".$key."')");
					if($r1 = $rs1->fetch_object()){
						//$sqlWhereTaxa .= "OR (o.tidinterpreted IN(SELECT tid FROM taxstatus WHERE taxauthid = 1 AND hierarchystr LIKE '%,".$r1->tid.",%')) ";
						
						//$sql2 = "SELECT DISTINCT ts.family FROM taxstatus ts ".
						//	"WHERE ts.taxauthid = 1 AND (ts.hierarchystr LIKE '%,".$r1->tid.",%') AND ts.family IS NOT NULL AND ts.family <> '' ";
						$sql2 = 'SELECT DISTINCT t.sciname FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
							'WHERE ts.taxauthid = 1 AND (ts.hierarchystr LIKE "%,'.$r1->tid.',%") AND t.rankid = 140';
						$sqlWhereTaxa .= "OR (o.family IN(".$sql2.")) ";
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
							$hSqlStr = '';
							foreach($tidArr as $tid){
								$hSqlStr .= 'OR (ts.hierarchystr LIKE "%,'.$tid.',%") ';
							}
							$sql = 'SELECT DISTINCT ts.family FROM taxstatus ts '.
								'WHERE ts.taxauthid = 1 AND ('.substr($hSqlStr,3).')';
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
					}
				}
			}
			$sqlWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
		}

		if(array_key_exists("country",$this->searchTermsArr)){
			$countryArr = explode(";",$this->searchTermsArr["country"]);
			$tempArr = Array();
			foreach($countryArr as $value){
				$tempArr[] = "(o.Country = '".trim($value)."')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countryArr);
		}
		if(array_key_exists("state",$this->searchTermsArr)){
			$stateAr = explode(";",$this->searchTermsArr["state"]);
			$tempArr = Array();
			foreach($stateAr as $value){
				$tempArr[] = "(o.StateProvince LIKE '".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$stateAr);
		}
		if(array_key_exists("county",$this->searchTermsArr)){
			$countyArr = explode(";",$this->searchTermsArr["county"]);
			$tempArr = Array();
			foreach($countyArr as $value){
				$tempArr[] = "(o.county LIKE '".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countyArr);
		}
		if(array_key_exists("local",$this->searchTermsArr)){
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
				foreach($coordArr as $k => $v){
					$coordStr .= $v['k']." ".$v['A'].",";
				}
				$coordStr .= $coordArr[0]['k']." ";
				$coordStr .= $coordArr[0]['A']."))";
				$sqlWhere .= "AND (ST_Within(p.point,GeomFromText('".$coordStr." '))) ";
			}
		}
		if(array_key_exists("collector",$this->searchTermsArr)){
			$collectorArr = explode(";",$this->searchTermsArr["collector"]);
			$tempArr = Array();
			foreach($collectorArr as $value){
				$tempArr[] = "(o.recordedBy LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(", ",$collectorArr);
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
		if(array_key_exists('catnum',$this->searchTermsArr)){
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
		if(array_key_exists("typestatus",$this->searchTermsArr)){
			$typestatusArr = explode(";",$this->searchTermsArr["typestatus"]);
			$tempArr = Array();
			foreach($typestatusArr as $value){
				$tempArr[] = "(o.typestatus LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(", ",$typestatusArr);
		}
		if(array_key_exists("clid",$this->searchTermsArr)){
			$clid = $this->searchTermsArr["clid"];
			$clSql = ""; 
			if($clid){
				$sql = 'SELECT dynamicsql, name FROM fmchecklists WHERE (clid = '.$clid.')';
				$result = $this->conn->query($sql);
				if($row = $result->fetch_object()){
					$clSql = $row->dynamicsql;
					$this->clName = $row->name;
				}
				if($clSql){
					$sqlWhere .= "AND (".$clSql.") ";
					$this->localSearchArr[] = "SQL: ".$clSql;
				}
			}
		}
		if(array_key_exists("sql",$this->searchTermsArr)){
			$sqlTerm = $this->searchTermsArr["sql"];
			$sqlWhere .= "AND (".$clSql.") ";
			$this->localSearchArr[] = "SQL: ".$clSql;
		}
		$retStr = '';
		if($sqlWhere){
			$retStr = 'WHERE '.substr($sqlWhere,4);
		}
		else{
			//Make the sql valid, but return nothing
			$retStr = 'WHERE o.collid = -1 ';
		}
		//echo $retStr;
		return $retStr; 
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
		$result->close();
    }
    
    protected function setSynonyms(){
    	foreach($this->taxaArr as $key => $value){
    		if(array_key_exists("scinames",$value) && !in_array("no records",$value["scinames"])){
    			$this->taxaArr = $value["scinames"];
    			foreach($this->taxaArr as $sciname){
	        		$sql = "call ReturnSynonyms('".$sciname."',1)";
	        		$result = $this->conn->query($sql);
	        		while($row = $result->fetch_object()){
	        			$this->taxaArr[$key]["synonyms"][] = $row->sciname;
	        		}
        			$result->close();
    			}
    		}
    		else{
    			$sql = "call ReturnSynonyms('".$key."',1)";
    			$result = $this->conn->query($sql);
        		while($row = $result->fetch_object()){
        			$this->taxaArr[$key]["synonyms"][] = $row->sciname;
        		}
        		$result->close();
        		$this->conn->next_result();
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
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, cat.catagory '.
			'FROM omcollections c LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
			'LEFT JOIN omcollcatagories cat ON ccl.ccpk = cat.ccpk '.
			'ORDER BY ccl.sortsequence, cat.catagory, c.sortseq, c.CollectionName ';
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($r = $result->fetch_object()){
			$collType = (stripos($r->colltype, "observation") !== false?'obs':'spec');
			if($r->ccpk){
				if(!isset($retArr[$collType]['cat'][$r->ccpk]['name'])){
					$retArr[$collType]['cat'][$r->ccpk]['name'] = $r->catagory;
					//if(in_array($r->ccpk,$catIdArr)) $retArr[$collType]['cat'][$catId]['isselected'] = 1;
					//if(in_array($r->ccpk,$catIdArr)) $retArr[$collType]['cat'][$catId]['icon'] = $r->icon;
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
		//Search will be confinded to a surveyid, collid, catid, or will remain open to all collection
		if(array_key_exists("surveyid",$_REQUEST)){
			//Limit by servey id 
			$surveyidArr = $_REQUEST["surveyid"];
			if(is_string($surveyidArr)) $surveyidArr = Array($surveyidArr); 
		 	$surveyidStr = implode(",",$surveyidArr);
		 	$this->searchTermsArr["surveyid"] = $surveyidStr;
		}
		else{
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
		}
		if(array_key_exists("taxa",$_REQUEST)){
			$taxa = $this->conn->real_escape_string($_REQUEST["taxa"]);
			$searchType = array_key_exists("type",$_REQUEST)?$this->conn->real_escape_string($_REQUEST["type"]):1;
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
		//$searchArr = Array();
		//$searchFieldsActivated = false;
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
				//$searchArr[] = "country:".$str;
				$this->searchTermsArr["country"] = $str;
			}
			else{
				unset($this->searchTermsArr["country"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("state",$_REQUEST)){
			$state = $this->conn->real_escape_string($_REQUEST["state"]);
			if($state){
				$str = str_replace(",",";",$state);
				//$searchArr[] = "state:".$str;
				$this->searchTermsArr["state"] = $str;
			}
			else{
				unset($this->searchTermsArr["state"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("county",$_REQUEST)){
			$county = $this->conn->real_escape_string($_REQUEST["county"]);
			$county = str_ireplace(" Co.","",$county);
			$county = str_ireplace(" County","",$county);
			if($county){
				$str = str_replace(",",";",$county);
				//$searchArr[] = "county:".$str;
				$this->searchTermsArr["county"] = $str;
			}
			else{
				unset($this->searchTermsArr["county"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("local",$_REQUEST)){
			$local = $this->conn->real_escape_string(trim($_REQUEST["local"]));
			if($local){
				$str = str_replace(",",";",$local);
				//$searchArr[] = "local:".$str;
				$this->searchTermsArr["local"] = $str;
			}
			else{
				unset($this->searchTermsArr["local"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("collector",$_REQUEST)){
			$collector = $this->conn->real_escape_string(trim($_REQUEST["collector"]));
			if($collector){
				$str = str_replace(",",";",$collector);
				//$searchArr[] = "collector:".$str;
				$this->searchTermsArr["collector"] = $str;
			}
			else{
				unset($this->searchTermsArr["collector"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("collnum",$_REQUEST)){
			$collNum = $this->conn->real_escape_string(trim($_REQUEST["collnum"]));
			if($collNum){
				$str = str_replace(",",";",$collNum);
				//$searchArr[] = "collnum:".$str;
				$this->searchTermsArr["collnum"] = $str;
			}
			else{
				unset($this->searchTermsArr["collnum"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("eventdate1",$_REQUEST)){
			if($eventDate = $this->conn->real_escape_string(trim($_REQUEST["eventdate1"]))){
				//$searchArr[] = "eventdate1:".$eventDate;
				$this->searchTermsArr["eventdate1"] = $eventDate;
				if(array_key_exists("eventdate2",$_REQUEST)){
					if($eventDate2 = $this->conn->real_escape_string(trim($_REQUEST["eventdate2"]))){
						if($eventDate2 != $eventDate){
							//$searchArr[] = "eventdate2:".$eventDate2;
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
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("catnum",$_REQUEST)){
			$catNum = $this->conn->real_escape_string(trim($_REQUEST["catnum"]));
			if($catNum){
				$str = str_replace(",",";",$catNum);
				//$searchArr[] = "catnum:".$str;
				$this->searchTermsArr["catnum"] = $str;
			}
			else{
				unset($this->searchTermsArr["catnum"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("typestatus",$_REQUEST)){
			$typestatus = $this->conn->real_escape_string(trim($_REQUEST["typestatus"]));
			if($typestatus){
				$str = str_replace(",",";",$typestatus);
				//$searchArr[] = "typestatus:".$str;
				$this->searchTermsArr["typestatus"] = $str;
			}
			else{
				unset($this->searchTermsArr["typestatus"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("clid",$_REQUEST)){
			$clid = $this->conn->real_escape_string($_REQUEST["clid"]);
			//$searchArr[] = "clid:".$clid;
			$this->searchTermsArr["clid"] = $clid;
			//$searchFieldsActivated = true;
		}
		$latLongArr = Array();
		if(array_key_exists("upperlat",$_REQUEST)){
			$upperLat = $this->conn->real_escape_string($_REQUEST["upperlat"]);
			if($upperLat || $upperLat === "0") $latLongArr[] = $upperLat;
		
			$bottomlat = $this->conn->real_escape_string($_REQUEST["bottomlat"]);
			if($bottomlat || $bottomlat === "0") $latLongArr[] = $bottomlat;
		
			$leftLong = $this->conn->real_escape_string($_REQUEST["leftlong"]);
			if($leftLong || $leftLong === "0") $latLongArr[] = $leftLong;
		
			$rightlong = $this->conn->real_escape_string($_REQUEST["rightlong"]);
			if($rightlong || $rightlong === "0") $latLongArr[] = $rightlong;

			if(count($latLongArr) == 4){
				//$searchArr[] = "llbound:".implode(";",$latLongArr);
				$this->searchTermsArr["llbound"] = implode(";",$latLongArr);
			}
			else{
				unset($this->searchTermsArr["llbound"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("pointlat",$_REQUEST)){
			$pointLat = $this->conn->real_escape_string($_REQUEST["pointlat"]);
			if($pointLat || $pointLat === "0") $latLongArr[] = $pointLat;
			
			$pointLong = $this->conn->real_escape_string($_REQUEST["pointlong"]);
			if($pointLong || $pointLong === "0") $latLongArr[] = $pointLong;
		
			$radius = $this->conn->real_escape_string($_REQUEST["radius"]);
			if($radius) $latLongArr[] = $radius;
			if(count($latLongArr) == 3){
				//$searchArr[] = "llpoint:".implode(";",$latLongArr);
				$this->searchTermsArr["llpoint"] = implode(";",$latLongArr);
			}
			else{
				unset($this->searchTermsArr["llpoint"]);
			}
			//$searchFieldsActivated = true;
		}
		if(array_key_exists("poly_array",$_REQUEST)){
			$jsonPolyArr = $_REQUEST["poly_array"];
			if($jsonPolyArr){
				//$searchArr[] = "polycoords:".$jsonPolyArr;
				$this->searchTermsArr["polycoords"] = $jsonPolyArr;
				
				//$searchFieldsActivated = true;
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
		$retVar = '';
		$sql = 'SELECT collid '.
			'FROM omcollections '.
			'WHERE collectionname = "General Observations"';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retVar = $r->collid;
			}
			$rs->close();
		}
		return $retVar;
	}
	
	public function getFullTaxaArr($mapWhere,$taxonAuthorityId,$stArr){
		global $userRights, $mappingBoundaries;
		$sql = '';
		$this->taxaArr = Array();
		if(!$taxonAuthorityId==1){
			$sql = 'SELECT DISTINCT ts.family, t.sciname '.
                'FROM ((omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.Tid) '.
                'INNER JOIN taxa t ON ts1.TidAccepted = t.Tid) '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid ';
		}
        else{
			$sql = 'SELECT DISTINCT IFNULL(ts.family,o.family) AS family, o.sciname '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
				'LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
		}
		if((array_key_exists("surveyid",$stArr))) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		if((array_key_exists("polycoords",$stArr))) $sql .= "INNER JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere;
		$sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL)";
		if(!$taxonAuthorityId==1){
			$sql .= " AND ts1.taxauthid = ".$taxonAuthorityId." AND ts.taxauthid = ".$taxonAuthorityId." AND t.RankId > 140 ";
        }
        else{
			$sql .= " AND (t.rankid > 140) AND (ts.taxauthid = 1) ";
		}
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		//echo json_encode($this->taxaArr);
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$sciName = $row->sciname;
			$family = $row->family;
			$this->taxaArr[$sciName] = Array();
		}
		$result->close();
		
		return $sql;
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
	
	public function getTaxaGeoCoords($limit=1000,$includeDescr=false,$mapWhere,$recLimit){
		global $userRights, $mappingBoundaries;
		$coordArr = Array();
		$sql = '';
		//$sql = 'SELECT o.occid, IFNULL(IFNULL(IFNULL(o.occurrenceid,o.catalognumber),CONCAT(o.recordedby," ",o.recordnumber)),o.occid) AS identifier, '.
		$sql = 'SELECT o.occid, CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),")") AS identifier, '.
			'o.sciname, o.family, o.DecimalLatitude, o.DecimalLongitude, o.collid, o.catalognumber, o.othercatalognumbers, c.institutioncode, c.collectioncode ';
		if($includeDescr){
			$sql .= ", CONCAT_WS('; ',CONCAT_WS(' ', o.recordedBy, o.recordNumber), o.eventDate, o.SciName) AS descr ";
		}
		if($this->fieldArr){
			foreach($this->fieldArr as $k => $v){
				$sql .= ", o.".$v." ";
			}
		}
		$sql .= "FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ";
		//if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		if((array_key_exists("surveyid",$this->searchTermsArr))) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		if((array_key_exists("polycoords",$this->searchTermsArr))) $sql .= "INNER JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere;
		$sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL)";
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		//$sql .= " LIMIT 5000";
		$taxaMapper = Array();
		$taxaMapper["undefined"] = "undefined";
		$usedColors = Array();
		//echo json_encode($this->taxaArr);
		foreach($this->taxaArr as $key => $valueArr){
			$color = '';
			do{$color = $this->getRandomColor();} while(in_array($color, $usedColors));
			$usedColors[] = $color;
			$coordArr[$key] = Array("color" => $color);
			$taxaMapper[$key] = $key;
			if(array_key_exists("scinames",$valueArr)){
				$scinames = $valueArr["scinames"];
				foreach($scinames as $sciname){
					$taxaMapper[$sciname] = $key;
				}
			}
			if(array_key_exists("synonyms",$valueArr)){
				$synonyms = $valueArr["synonyms"];
				foreach($synonyms as $syn){
					$taxaMapper[$syn] = $key;
				}
			}
		}
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$recCnt = 0;
		while($row = $result->fetch_object()){
			if($result->num_rows <= $recLimit){
				$occId = $row->occid;
				$sciName = $row->sciname;
				$family = ucfirst(strtolower($row->family));
				$latLngStr = $row->DecimalLatitude.",".$row->DecimalLongitude;
				if(!array_key_exists($sciName,$taxaMapper)){
					foreach($taxaMapper as $keySciname => $v){
						if(strpos($sciName,$keySciname) === 0){
							$sciName = $keySciname;
							break;
						}
					}
					if(!array_key_exists($sciName,$taxaMapper) && array_key_exists($family,$taxaMapper)){
						$sciName = $family;
					}
				}
				if(!array_key_exists($sciName,$taxaMapper)) $sciName = "undefined"; 
				$coordArr[$taxaMapper[$sciName]][$occId]["latLngStr"] = $latLngStr;
				$coordArr[$taxaMapper[$sciName]][$occId]["collid"] = htmlentities($row->collid);
				$coordArr[$taxaMapper[$sciName]][$occId]["identifier"] = htmlentities($row->identifier);
				$coordArr[$taxaMapper[$sciName]][$occId]["institutioncode"] = htmlentities($row->institutioncode);
				$coordArr[$taxaMapper[$sciName]][$occId]["collectioncode"] = htmlentities($row->collectioncode);
				$coordArr[$taxaMapper[$sciName]][$occId]["catalognumber"] = htmlentities($row->catalognumber);
				$coordArr[$taxaMapper[$sciName]][$occId]["othercatalognumbers"] = htmlentities($row->othercatalognumbers);
				if($includeDescr){
					$coordArr[$taxaMapper[$sciName]][$occId]["descr"] = htmlentities($row->descr);
				}
				if($this->fieldArr){
					foreach($this->fieldArr as $k => $v){
						$coordArr[$taxaMapper[$sciName]][$occId][$v] = htmlentities($row->$v);
					}
				}
			}
			else{
				$recCnt = $result->num_rows;
			}
		}
		if(array_key_exists("undefined",$coordArr)){
			do{$color = $this->getRandomColor();} while(in_array($color, $usedColors));
			$coordArr["undefined"]["color"] = $color;
		}
		$result->close();
		
		if($recCnt > $recLimit){
			$coordArr = $recCnt;
		}
		
		return $coordArr;
		//return $sql;
	}
	
	public function getCollGeoCoords($limit=1000,$includeDescr=false,$mapWhere,$recLimit){
		global $userRights, $mappingBoundaries;
		$coordArr = Array();
		$sql = '';
		//$sql = 'SELECT o.occid, IFNULL(IFNULL(IFNULL(o.occurrenceid,o.catalognumber),CONCAT(o.recordedby," ",o.recordnumber)),o.occid) AS identifier, '.
		$sql = 'SELECT o.occid, CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),")") AS identifier, '.
			'o.sciname, o.family, o.tidinterpreted, o.DecimalLatitude, o.DecimalLongitude, o.collid, o.catalognumber, o.othercatalognumbers, c.institutioncode, c.collectioncode, '.
			'c.CollectionName ';
		if($includeDescr){
			$sql .= ", CONCAT_WS('; ',CONCAT_WS(' ', o.recordedBy, o.recordNumber), o.eventDate, o.SciName) AS descr ";
		}
		if($this->fieldArr){
			foreach($this->fieldArr as $k => $v){
				$sql .= ", o.".$v." ";
			}
		}
		$sql .= "FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ";
		//if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		if((array_key_exists("surveyid",$this->searchTermsArr))) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		if((array_key_exists("polycoords",$this->searchTermsArr))) $sql .= "INNER JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere;
		$sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL)";
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		//$sql .= " LIMIT 5000";
		$collMapper = Array();
		$collMapper["undefined"] = "undefined";
		$usedColors = Array();
		//echo json_encode($this->taxaArr);
		foreach($this->collArr as $key => $valueArr){
			$color = 'e69e67';
			//do{$color = $this->getRandomColor();} while(in_array($color, $usedColors));
			//$usedColors[] = $color;
			$coordArr[$key] = Array("color" => $color);
			$collMapper[$key] = $key;
		}
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$recCnt = 0;
		while($row = $result->fetch_object()){
			if($result->num_rows <= $recLimit){
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
				if($includeDescr){
					$coordArr[$collMapper[$collName]][$occId]["descr"] = $this->xmlentities($row->descr);
				}
				if($this->fieldArr){
					foreach($this->fieldArr as $k => $v){
						$coordArr[$collMapper[$collName]][$occId][$v] = $this->xmlentities($row->$v);
					}
				}
			}
			else{
				$recCnt = $result->num_rows;
			}
		}
		if(array_key_exists("undefined",$coordArr)){
			//do{$color = $this->getRandomColor();} while(in_array($color, $usedColors));
			$coordArr["undefined"]["color"] = $color;
		}
		$result->close();
		
		if($recCnt > $recLimit){
			$coordArr = $recCnt;
		}
		
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
			echo "<StyleMap id='".str_replace(" ","_",$sciName)."'>\n";
            echo "<Pair><key>normal</key><styleUrl>#sn_".$iconStr."</styleUrl></Pair>";
			echo "<Pair><key>highlight</key><styleUrl>#sh_".$iconStr."</styleUrl></Pair>";
			echo "</StyleMap>\n";
			echo "<Folder><name>".$sciName."</name>\n";
			foreach($contentArr as $latLong => $llArr){
				foreach($llArr as $occId => $pointArr){
					echo "<Placemark>\n";
					echo "<name>".htmlspecialchars($pointArr["identifier"], ENT_QUOTES)."</name>\n";
					echo "<ExtendedData>\n";
					echo "<Data name='institutioncode'>".$pointArr["institutioncode"]."</Data>\n";
					echo "<Data name='collectioncode'>".$pointArr["collectioncode"]."</Data>\n";
					echo "<Data name='catalognumber'>".$pointArr["catalognumber"]."</Data>\n";
					echo "<Data name='othercatalognumbers'>".$pointArr["othercatalognumbers"]."</Data>\n";
					if($this->fieldArr){
						foreach($this->fieldArr as $k => $v){
							echo "<Data name='".$v."'>".$pointArr[$v]."</Data>\n";
						}
					}
					echo "<Data name='DataSource'>Data retrieved from ".$defaultTitle." Data Portal</Data>\n";
					$url = "http://".$_SERVER["SERVER_NAME"].$clientRoot."/collections/individual/index.php?occid=".$occId;
					echo "<Data name='RecordURL'>".$url."</Data>\n";
					echo "</ExtendedData>\n";
					echo "<styleUrl>#".str_replace(" ","_",$sciName)."</styleUrl>\n";
	                echo "<Point><coordinates>".implode(",",array_reverse(explode(",",$latLong))).",0</coordinates></Point>\n";
					echo "</Placemark>\n";
				}
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
							<img id="plus-<?php echo $idStr; ?>" src="../images/plus.gif" style="<?php echo ($defaultCatid==$catid?'display:none;':'') ?>" /><img id="minus-<?php echo $idStr; ?>" src="../images/minus.gif" style="<?php echo ($defaultCatid==$catid?'':'display:none;') ?>" />
						</a>
					</td>
					<td>
						<input id="cat<?php echo $idStr; ?>Input" data-role="none" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ((in_array($catid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> /> 
					</td>
					<td>
			    		<span style='text-decoration:none;color:black;font-size:130%;font-weight:bold;'>
				    		<a href = 'misc/collprofiles.php?catid=<?php echo $catid; ?>' target="_blank" ><?php echo $name; ?></a>
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
												$cIcon = (substr($collName2["icon"],0,6)=='images'?'../':'').$collName2["icon"]; 
												?>
												<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
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
								    		<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:120%;' target="_blank" >
								    			<?php echo $collName2["collname"]." (".$collName2["instcode"].")"; ?>
								    		</a>
								    		<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
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
							$cIcon = (substr($cArr["icon"],0,6)=='images'?'../':'').$cArr["icon"]; 
							?>
							<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
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
			    		<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:120%;' target="_blank" >
			    			<?php echo $cArr["collname"]." (".$cArr["instcode"].")"; ?>
			    		</a>
			    		<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
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
	
	private function setRecordCnt($sqlWhere){
		global $clientRoot;
		if($sqlWhere){
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ";
			//if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
			if(array_key_exists("polycoords",$this->searchTermsArr)) $sql .= "INNER JOIN omoccurpoints p ON o.occid = p.occid ";
			$sql .= $sqlWhere;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->close();
		}
		//setCookie("collvars","reccnt:".$this->recordCount,time()+64800,($clientRoot?$clientRoot:'/'));
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
		$sql = 'SELECT o.occid, c.institutioncode, o.catalognumber, CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),")") AS collector, '.
			'o.eventdate, o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude, '.
			'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason '.
			'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		//if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		if(array_key_exists("polycoords",$this->searchTermsArr)) $sql .= "INNER JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere;
		$sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL) ";
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= "ORDER BY c.sortseq, c.collectionname ";
		if(strpos($mapWhere,"(o.sciname") || strpos($mapWhere,"o.family")){
			$sql .= ",o.sciname ";
		}
		$sql .= ",o.recordedBy,o.recordNumber+1 ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
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
	
	public function createShape(){
		$queryShape = '';
		$shapeBounds = '';
		$properties = '';
		$properties = 'strokeWeight: 0,';
		$properties .= 'fillOpacity: 0.45,';
		$properties .= 'editable: true,';
		//$properties .= 'draggable: true,';
		$properties .= 'map: map});';
		
		if(($_REQUEST["upperlat"]) || ($_REQUEST["pointlat"]) || ($_REQUEST["poly_array"])){
			if($_REQUEST["upperlat"]){
				$queryShape = 'var queryRectangle = new google.maps.Rectangle({';
				$queryShape .= 'bounds: new google.maps.LatLngBounds(';
				$queryShape .= 'new google.maps.LatLng('.$_REQUEST["bottomlat"].', '.$_REQUEST["leftlong"].'),';
				$queryShape .= 'new google.maps.LatLng('.$_REQUEST["upperlat"].', '.$_REQUEST["rightlong"].')),';
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
				$queryShape .= 'queryShapeBounds.extend(new google.maps.LatLng('.$_REQUEST["bottomlat"].', '.$_REQUEST["leftlong"].'));';
				$queryShape .= 'queryShapeBounds.extend(new google.maps.LatLng('.$_REQUEST["upperlat"].', '.$_REQUEST["rightlong"].'));';
				$queryShape .= 'map.fitBounds(queryShapeBounds);';
				$queryShape .= 'map.panToBounds(queryShapeBounds);';
			}
			if($_REQUEST["pointlat"]){
				$radius = (($_REQUEST["radius"]/0.6214)*1000);
				$queryShape = 'var queryCircle = new google.maps.Circle({';
				$queryShape .= 'center: new google.maps.LatLng('.$_REQUEST["pointlat"].', '.$_REQUEST["pointlong"].'),';
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
			if($_REQUEST["poly_array"]){
				$coordArr = json_decode($_REQUEST["poly_array"], true);
				if($coordArr){
					$shapeBounds = 'var queryShapeBounds = new google.maps.LatLngBounds();';
					$queryShape = 'var queryPolygon = new google.maps.Polygon({';
					$queryShape .= 'paths: [';
					foreach($coordArr as $k => $v){
						$queryShape .= 'new google.maps.LatLng('.$v['k'].', '.$v['A'].'),';
						$shapeBounds .= 'queryShapeBounds.extend(new google.maps.LatLng('.$v['k'].', '.$v['A'].'));';
					}
					$queryShape .= 'new google.maps.LatLng('.$coordArr[0]['k'].', '.$coordArr[0]['A'].')],';
					$shapeBounds .= 'queryShapeBounds.extend(new google.maps.LatLng('.$coordArr[0]['k'].', '.$coordArr[0]['A'].'));';
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
        $sql = 'SELECT DISTINCT t.tid, IFNULL(ts.family,o.family) AS family, t.sciname '.
			'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
			'LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
		if(array_key_exists("surveyid",$stArr)) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		if(array_key_exists("polycoords",$stArr)) $sql .= "INNER JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere." AND (t.rankid >= 140) AND (ts.taxauthid = 1) ";
		$sql .= " ORDER BY family, o.sciname ";
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if(!$family) $family = 'undefined';
			$tid = $row->tid;
			$sciName = $row->sciname;
			if($sciName){
				$returnVec[$family][$sciName]["tid"] = $tid;
				$returnVec[$family][$sciName]["sciname"] = $sciName;
				$this->checklistTaxaCnt++;
			}
        }
        return $returnVec;
		//return $sql;
	}
	
	public function getChecklistTaxaCnt(){
		return $this->checklistTaxaCnt;
	}
	
}
?>