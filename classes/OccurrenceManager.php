<?php
/*
 * Created on 18 March 2009
 * @author  E. Gilbert: egbot@asu.edu
 */

include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceManager{

	protected $conn;
	protected $taxaArr = Array();
	private $taxaSearchType;
	protected $searchTermsArr = Array();
	protected $localSearchArr = Array();
	protected $collectionArr = Array();
	protected $useCookies = true;
	protected $reset = false;
	protected $dynamicClid;
	private $clName;
	
 	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
 		$this->useCookies = array_key_exists("usecookies",$_REQUEST)&&$_REQUEST["usecookies"]=="false"?false:true; 
 		if(array_key_exists("reset",$_REQUEST) && $_REQUEST["reset"]){
 			$this->reset();
 			$this->reset = true;
 		}
 		if($this->useCookies){
 			$this->readCollCookies();
 		}
		$this->readRequestVariables();
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
	}

	public function reset(){
		global $clientRoot;
		setCookie("colltaxa","",time()-3600,($clientRoot?$clientRoot:'/'));
		setCookie("collsearch","",time()-3600,($clientRoot?$clientRoot:'/'));
		setCookie("collvars","",time()-3600,($clientRoot?$clientRoot:'/'));
		if(array_key_exists("db",$this->searchTermsArr) || array_key_exists("oic",$this->searchTermsArr)){
			//reset all other search terms except maintain the db terms 
			$dbsTemp = "";
			if(array_key_exists("db",$this->searchTermsArr)) $dbsTemp = $this->searchTermsArr["db"];
			$surveyIdTemp = "";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $surveyIdTemp = $this->searchTermsArr["surveyid"];
			unset($this->searchTermsArr);
			if($dbsTemp) $this->searchTermsArr["db"] = $dbsTemp;
			if($surveyIdTemp) $this->searchTermsArr["surveyid"] = $surveyIdTemp;
		}
	}

	private function readCollCookies(){
		if(array_key_exists("colldbs",$_COOKIE)){
			$this->searchTermsArr["db"] = $_COOKIE["colldbs"];
		}
		elseif(array_key_exists("collsurveyid",$_COOKIE)){
			$this->searchTermsArr["surveyid"] = $_COOKIE["collsurveyid"];
		}
		if(array_key_exists("colltaxa",$_COOKIE)){
			$collTaxa = $_COOKIE["colltaxa"]; 
			$taxaArr = explode("&",$collTaxa);
			foreach($taxaArr as $value){
				$this->searchTermsArr[substr($value,0,strpos($value,":"))] = substr($value,strpos($value,":")+1);
			}
		}
		if(array_key_exists("collsearch",$_COOKIE)){
			$collSearch = $_COOKIE["collsearch"]; 
			$searArr = explode("&",$collSearch);
			foreach($searArr as $value){
				$this->searchTermsArr[substr($value,0,strpos($value,":"))] = substr($value,strpos($value,":")+1);
			}
		}
		if(array_key_exists("collvars",$_COOKIE)){
			$collVarStr = $_COOKIE["collvars"];
			$varsArr = explode("&",$collVarStr);
			foreach($varsArr as $value){
				if(strpos($value,"dynclid") === 0){
					$this->dynamicClid = substr($value,strpos($value,":")+1);
				}
				elseif(strpos($value,"reccnt") === 0){
					$this->recordCount = substr($value,strpos($value,":")+1);
				}
			}
		}
		return $this->searchTermsArr;
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

	protected function getSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("db",$this->searchTermsArr)){
			if(strpos($this->searchTermsArr["db"],"all") === false){
				$dbStr = preg_replace('/;catid:\d*/','',$this->searchTermsArr["db"]);
				$sqlWhere .= "AND (o.CollID IN(".str_replace(";",",",trim($dbStr,';')).")) ";
			}
			elseif(preg_match('/;catid:(\d+)/',$this->searchTermsArr["db"],$matches)){
				$catId = $matches[1];
				if($catId) $sqlWhere .= "AND (o.CollID IN(SELECT collid FROM omcollcatlink WHERE ccpk = ".$catId.")) ";
			}
		}
		elseif(array_key_exists("surveyid",$this->searchTermsArr)){
			$sqlWhere .= "AND (sol.surveyid IN('".str_replace(";","','",$this->searchTermsArr["surveyid"])."')) ";
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
					$rs1 = $this->conn->query("SELECT tid FROM taxa WHERE sciname = '".$key."'");
					if($r1 = $rs1->fetch_object()){
						//$sqlWhereTaxa .= "OR (o.tidinterpreted IN(SELECT tid FROM taxstatus WHERE taxauthid = 1 AND hierarchystr LIKE '%,".$r1->tid.",%')) ";
						
						$fStr = "";
						$sql2 = "SELECT DISTINCT ts.family FROM taxstatus ts ".
							"WHERE ts.taxauthid = 1 AND ts.hierarchystr LIKE '%,".$r1->tid.",%' AND ts.family IS NOT NULL AND ts.family <> '' ";
						$rs2 = $this->conn->query($sql2);
						while($r2 = $rs2->fetch_object()){
							$fStr .= "','".$r2->family;
						}
						if($fStr){
							$sqlWhereTaxa .= "OR (o.family IN('".substr($fStr,3)."')) ";
						}
					}
				}
				else{
					if($this->taxaSearchType == 5){
						if(array_key_exists("families",$valueArray)){
							foreach($valueArray["families"] as $f){
								$sqlWhereTaxa .= "OR (o.family = '".$f."') ";
							}
						}
						if(array_key_exists("scinames",$valueArray)){
							foreach($valueArray["scinames"] as $sciName){
								$sqlWhereTaxa .= "OR (o.sciname Like '".$sciName."%') ";
							}
						}
					}
					else{
						if($this->taxaSearchType == 2 || ($this->taxaSearchType == 1 && (substr($key,-5) == "aceae" || substr($key,-4) == "idae"))){
							$sqlWhereTaxa .= "OR (o.family = '".$key."') ";
						}
						if($this->taxaSearchType == 3 || ($this->taxaSearchType == 1 && substr($key,-5) != "aceae" && substr($key,-4) != "idae")){
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
				$tempArr[] = "(o.StateProvince = '".trim($value)."')";
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
				//Formula approximates a bounding box; bounding box is for efficiency, will test practicality of doing a radius query in future  
				$latRadius = $pointArr[2] / 69.1;
				$longRadius = cos($pointArr[0]/57.3)*($pointArr[2]/69.1);
				$lat1 = $pointArr[0] - $latRadius;
				$lat2 = $pointArr[0] + $latRadius;
				$long1 = $pointArr[1] - $longRadius;
				$long2 = $pointArr[1] + $longRadius;
				$sqlWhere .= "AND (o.DecimalLatitude BETWEEN ".$lat1." AND ".$lat2." AND ".
					"o.DecimalLongitude BETWEEN ".$long1." AND ".$long2.") ";
			}
			$this->localSearchArr[] = "Point radius: ".$pointArr[0].", ".$pointArr[1].", within ".$pointArr[2]." miles";
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
			$tempArr = Array();
			foreach($collNumArr as $value){
				$tempArr[] = "(o.recordNumber LIKE '".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(", ",$collNumArr);
		}
		if(array_key_exists("clid",$this->searchTermsArr)){
			$clid = $this->searchTermsArr["clid"];
			$clSql = ""; 
			if($clid){
				$sql = "SELECT dynamicsql, name ".
					"FROM fmchecklists WHERE clid = ".$clid;
				$result = $this->conn->query($sql);
				if($row = $result->fetch_object()){
					$clSql = $row->dynamicsql;
					$this->clName = $row->name;
				}
				if($clSql){
					$sqlWhere .= "AND (".$clSql.")";
					$this->localSearchArr[] = "SQL: ".$clSql;
				}
			}
		}
		if(array_key_exists("sql",$this->searchTermsArr)){
			$sqlTerm = $this->searchTermsArr["sql"];
			$sqlWhere .= "AND (".$clSql.")";
			$this->localSearchArr[] = "SQL: ".$clSql;
		}
		//echo "WHERE ".substr($sqlWhere,4);
		return "WHERE ".substr($sqlWhere,4);
	}
	
    protected function setSciNamesByVerns(){
        $sql = "SELECT DISTINCT v.VernacularName, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID) ".
            "INNER JOIN taxa t ON t.TID = ts.tidaccepted ";
    	$whereStr = "";
		foreach($this->taxaArr as $key => $value){
			$whereStr .= "OR v.VernacularName LIKE '%".$key."%' ";
		}
		$sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
		//echo "<div>sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		if($result->num_rows){
			while($row = $result->fetch_object()){
				$vernName = $row->VernacularName;
				if($row->rankid == 140){
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
	
	public function getCollectionArr($catId = ""){
		if(!$this->collectionArr) {
			$tempCollArr = Array();
			if(array_key_exists("db",$this->searchTermsArr)) $tempCollArr = explode(";",$this->searchTermsArr["db"]);
			$sql = "SELECT c.collid, c.institutioncode, c.collectioncode, c.CollectionName, c.Homepage, ".
				"c.IndividualUrl, c.icon, c.colltype, c.Contact, c.email, c.SortSeq ".
				"FROM omcollections c ";
			if($catId){
				$sql .= "INNER JOIN omcollcatlink ccl ON c.collid = ccl.collid WHERE ccl.ccpk = ".$catId." ";
			}
			$sql .= "ORDER BY c.SortSeq, c.CollectionName ";
			//echo "<div>SQL: ".$sql."</div>";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$collId = $row->collid;
				$this->collectionArr[$collId]["institutioncode"] = $row->institutioncode;
				$this->collectionArr[$collId]["collectioncode"] = $row->collectioncode;
				$this->collectionArr[$collId]["collectionname"] = $row->CollectionName;
				$this->collectionArr[$collId]["homepage"] = $row->Homepage;
				$this->collectionArr[$collId]["icon"] = $row->icon;
				$this->collectionArr[$collId]["displayorder"] = $row->SortSeq;
				$this->collectionArr[$collId]["colltype"] = $row->colltype;
				if(in_array($collId,$tempCollArr) || in_array("all",$tempCollArr)) $this->collectionArr[$collId]["isselected"] = 1;
			}
			$result->close();
		}
		return $this->collectionArr;
	}
	
	public function getSurveys(){
		$returnArr = Array();
		$sql = "SELECT p.projname, s.surveyid, s.projectname ".
			"FROM (fmprojects p INNER JOIN omsurveyprojlink spl ON p.pid = spl.pid) ".
			"INNER JOIN omsurveys s ON spl.surveyid = s.surveyid ".
			"WHERE p.occurrencesearch = 1 ".
			"ORDER BY p.sortsequence, p.projname, s.projectname"; 
		//echo "<div>$sql</div>";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->projname][$row->surveyid] = $row->projectname;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function getDatasetSearchStr(){
		$returnStr ="";
		if(array_key_exists("surveyid",$this->searchTermsArr)){
			$returnStr = $this->getSurveyStr();
		}
		else{
			if(!$this->collectionArr) $this->getCollectionArr();
			$tempArr = Array();
			foreach($this->collectionArr as $collId => $fieldArr){
				if(array_key_exists("isselected",$fieldArr)) $tempArr[] = $fieldArr["institutioncode"];
			}
			sort($tempArr);
			if(count($this->collectionArr) == count($tempArr)){
				$returnStr = "All Collections";
			}
			else{
				$returnStr = implode("; ",$tempArr);
			}
		}
		return $returnStr;
	}
	
	private function getSurveyStr(){
		$returnStr = "";
		$sql = "SELECT projectname FROM omsurveys WHERE surveyid IN(".str_replace(";",",",$this->searchTermsArr["surveyid"]).") ";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnStr .= " ;".$row->projectname; 
		}
		return substr($returnStr,2);
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
		if(array_key_exists("db",$_REQUEST)){
			$dbs = $_REQUEST["db"];
			if(is_string($dbs)) $dbs = Array($dbs); 
		 	$dbStr = "";
		 	if(in_array("all",$dbs)){
		 		$dbStr = "all";
		 		if(array_key_exists('catid',$_REQUEST) && $_REQUEST['catid']){
		 			$dbStr .= ";catid:".$this->conn->real_escape_string($_REQUEST['catid']);
		 		}
		 	}
		 	else{
		 		$dbStr = implode(";",$dbs);
		 	}
		 	if($this->useCookies) setCookie("colldbs",$dbStr,0,($clientRoot?$clientRoot:'/'));
			setCookie("collsurveyid","",time()-3600,($clientRoot?$clientRoot:'/'));
			$this->searchTermsArr["db"] = $dbStr;
		}
		elseif(array_key_exists("surveyid",$_REQUEST)){
			$surveyidArr = $_REQUEST["surveyid"];
			if(is_string($surveyidArr)) $surveyidArr = Array($surveyidArr); 
		 	$surveyidStr = implode(";",$surveyidArr);
		 	if($this->useCookies) setCookie("collsurveyid",$surveyidStr,0,($clientRoot?$clientRoot:'/'));
			setCookie("colldbs","",time()-3600,($clientRoot?$clientRoot:'/'));
			$this->searchTermsArr["surveyid"] = $surveyidStr;
		}
		if(array_key_exists("taxa",$_REQUEST)){
			$taxa = $this->conn->real_escape_string($_REQUEST["taxa"]);
			if($taxa){
				$taxaStr = "";
				if(is_numeric($taxa)){
					$sql = "SELECT t.sciname ". 
						"FROM taxa t ".
						"WHERE t.tid = ".$taxa;
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
						$taxaArr[$key] = ucfirst(trim($sciName));
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
				$searchType = array_key_exists("type",$_REQUEST)?$this->conn->real_escape_string($_REQUEST["type"]):1;
				if($searchType){
					$collTaxa .= "&taxontype:".$searchType;
					$this->searchTermsArr["taxontype"] = $searchType;
				}
				if($this->useCookies) setCookie("colltaxa",$collTaxa,0,($clientRoot?$clientRoot:'/'));
			}
			else{
				if($this->useCookies) setCookie("colltaxa","",time()-3600,($clientRoot?$clientRoot:'/'));
				unset($this->searchTermsArr["taxa"]);
			}
		}
		$searchArr = Array();
		$searchFieldsActivated = false;
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
				$searchArr[] = "country:".$str;
				$this->searchTermsArr["country"] = $str;
			}
			else{
				unset($this->searchTermsArr["country"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("state",$_REQUEST)){
			$state = $this->conn->real_escape_string($_REQUEST["state"]);
			if($state){
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
			$county = $this->conn->real_escape_string($_REQUEST["county"]);
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
			$local = $this->conn->real_escape_string($_REQUEST["local"]);
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
		if(array_key_exists("collector",$_REQUEST)){
			$collector = $this->conn->real_escape_string($_REQUEST["collector"]);
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
			$collNum = $this->conn->real_escape_string($_REQUEST["collnum"]);
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
		if(array_key_exists("clid",$_REQUEST)){
			$clid = $this->conn->real_escape_string($_REQUEST["clid"]);
			$searchArr[] = "clid:".$clid;
			$this->searchTermsArr["clid"] = $clid;
			$searchFieldsActivated = true;
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
				$searchArr[] = "llbound:".implode(";",$latLongArr);
				$this->searchTermsArr["llbound"] = implode(";",$latLongArr);
			}
			else{
				unset($this->searchTermsArr["llbound"]);
			}
			$searchFieldsActivated = true;
		}
		if(array_key_exists("pointlat",$_REQUEST)){
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

		$searchStr = implode("&",$searchArr);
		if($searchStr){
			if($this->useCookies) setCookie("collsearch",$searchStr,0,($clientRoot?$clientRoot:'/'));
		}
		elseif($searchFieldsActivated){
			if($this->useCookies) setCookie("collsearch","",time()-3600,($clientRoot?$clientRoot:'/'));
		}
	}
	
	public function getUseCookies(){
		return $this->useCookies;
	}
	
	public function getClName(){
		return $this->clName;
	}
}
?>