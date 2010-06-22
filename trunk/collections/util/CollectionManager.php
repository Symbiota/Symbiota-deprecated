<?php
/*
 * Created on 18 March 2009
 * @author  E. Gilbert: egbot@asu.edu
 */

include_once($serverRoot."/util/dbconnection.php");

class CollectionManager{
	
	protected $taxaArr = Array();
	private $taxaSearchType;
	protected $searchTermsArr = Array();
	protected $localSearchArr = Array();
	protected $collectionArr = Array();
	protected $useCookies = true;
	protected $dynamicClid;
	
 	public function __construct(){
 		$this->useCookies = array_key_exists("usecookies",$_REQUEST)&&$_REQUEST["usecookies"]=="false"?false:true; 
 		if(array_key_exists("reset",$_REQUEST) && $_REQUEST["reset"]){
 			$this->reset();
 		}
 		elseif($this->useCookies){
 			$this->readCollCookies();
 		}
		$this->readRequestVariables();
 	}

 	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
 	}
 	
	public function reset(){
		global $clientRoot;
		setCookie("colltaxa","",time()-3600,$clientRoot);
		setCookie("collsearch","",time()-3600,$clientRoot);
		setCookie("collvars","",time()-3600,$clientRoot);
		if(array_key_exists("db",$this->searchTermsArr) || array_key_exists("oic",$this->searchTermsArr)){
			//reset all search terms except db terms 
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
		if(array_key_exists("db",$this->searchTermsArr) && strpos($this->searchTermsArr["db"],"all") === false){
			$sqlWhere .= "AND (o.CollID IN(".str_replace(";",",",$this->searchTermsArr["db"]).")) ";
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
			$conn = $this->getConnection();
			if($this->taxaSearchType == 4){
				//Common name search
				$this->setSciNamesByVerns($conn);
			}
			else{
				if($useThes){ 
					$this->setSynonyms($conn);
				}
			}
			$conn->close();
			
			//Build sql
			foreach($this->taxaArr as $key => $valueArray){
				if($this->taxaSearchType == 4){
					if(array_key_exists("families",$valueArray)){
						foreach($valueArray["families"] as $f){
							$sqlWhereTaxa .= "OR (o.family = '".$f."') ";
						}
					}
					if(array_key_exists("scinames",$valueArray)){
						foreach($valueArray["scinames"] as $sciName){
							$sqlWhereTaxa .= "OR (o.SciName Like '".$sciName."%') ";
						}
					}
				}
				else{
					if($this->taxaSearchType == 2 || ($this->taxaSearchType == 1 && (substr($key,-5) == "aceae" || substr($key,-4) == "idae"))){
						$sqlWhereTaxa .= "OR (o.family = '".$key."') OR (o.SciName = '".$key."') ";
					}
					if($this->taxaSearchType == 3 || ($this->taxaSearchType == 1 && substr($key,-5) != "aceae" && substr($key,-4) != "idae")){
						$sqlWhereTaxa .= "OR (o.SciName LIKE '".$key."%') ";
					}
				}
				if(array_key_exists("synonyms",$valueArray)){
					$synArr = $valueArray["synonyms"];
					foreach($synArr as $sciName){ 
						if($this->taxaSearchType == 1 || $this->taxaSearchType == 2 || $this->taxaSearchType == 4){
							$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
						}
						if($this->taxaSearchType == 2){
							$sqlWhereTaxa .= "OR (o.SciName = '".$sciName."') ";
						}
						else{
		                    $sqlWhereTaxa .= "OR (o.SciName Like '".$sciName."%') ";
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
				$tempArr[] = "(o.Country LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countryArr);
		}
		if(array_key_exists("state",$this->searchTermsArr)){
			$stateAr = explode(";",$this->searchTermsArr["state"]);
			$tempArr = Array();
			foreach($stateAr as $value){
				$tempArr[] = "(o.StateProvince LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$stateAr);
		}
		if(array_key_exists("county",$this->searchTermsArr)){
			$countyArr = explode(";",$this->searchTermsArr["county"]);
			$tempArr = Array();
			foreach($countyArr as $value){
				$tempArr[] = "(o.County LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countyArr);
		}
		if(array_key_exists("local",$this->searchTermsArr)){
			$localArr = explode(";",$this->searchTermsArr["local"]);
			$tempArr = Array();
			foreach($localArr as $value){
				$tempArr[] = "(o.Locality LIKE '%".trim($value)."%')";
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
				$tempArr[] = "(o.recordNumber LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(", ",$collNumArr);
		}
		if(array_key_exists("clid",$this->searchTermsArr)){
			$clid = $this->searchTermsArr["clid"];
			$clSql = ""; 
			if($clid){
				$sql = "SELECT dynamicsql FROM fmchecklists WHERE clid = ".$clid;
				$con = $this->getConnection();
				$result = $con->query($sql);
				if($row = $result->fetch_object()) $clSql = $row->dynamicsql;
				$con->close();
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
	
    protected function setSciNamesByVerns($conn){
        $sql = "SELECT DISTINCT v.VernacularName, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID) ".
            "INNER JOIN taxa t ON t.TID = ts.tidaccepted ";
    	$whereStr = "";
		foreach($this->taxaArr as $key => $value){
			$whereStr .= "OR v.VernacularName LIKE '%".$key."%' ";
		}
		$sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
		//echo "<div>sql: ".$sql."</div>";
		$result = $conn->query($sql);
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
    
    protected function setSynonyms($conn){
    	foreach($this->taxaArr as $key => $value){
    		if(array_key_exists("scinames",$value) && !in_array("no records",$value["scinames"])){
    			$this->taxaArr = $value["scinames"];
    			foreach($this->taxaArr as $sciname){
	        		$sql = "call ReturnSynonyms('".$sciname."',1)";
	        		$result = $conn->query($sql);
	        		while($row = $result->fetch_object()){
	        			$this->taxaArr[$key]["synonyms"][] = $row->sciname;
	        		}
        			$result->free();
    			}
    		}
    		else{
    			$sql = "call ReturnSynonyms('".$key."',1)";
    			$result = $conn->query($sql);
        		while($row = $result->fetch_object()){
        			$this->taxaArr[$key]["synonyms"][] = $row->sciname;
        		}
        		$result->close();
        		$conn->next_result();
    		}
    	}
    }
	
	public function getCollectionArr(){
		if(!$this->collectionArr) {
			$conn = $this->getConnection();
			$tempCollArr = Array();
			if(array_key_exists("db",$this->searchTermsArr)) $tempCollArr = explode(";",$this->searchTermsArr["db"]);
			$sql = "SELECT CollId, CollectionCode, CollectionName, Homepage, IndividualUrl, icon, colltype, Contact, email, SortSeq ".
				"FROM omcollections ".
				"ORDER BY SortSeq ";
			//echo "<div>SQL: ".$sql."</div>";
			$result = $conn->query($sql);
			while($row = $result->fetch_object()){
				$collId = $row->CollId;
				$this->collectionArr[$collId]["collectioncode"] = $row->CollectionCode;
				$this->collectionArr[$collId]["collectionname"] = $row->CollectionName;
				$this->collectionArr[$collId]["homepage"] = $row->Homepage;
				$this->collectionArr[$collId]["icon"] = $row->icon;
				$this->collectionArr[$collId]["displayorder"] = $row->SortSeq;
				$this->collectionArr[$collId]["colltype"] = $row->colltype;
				if(in_array($collId,$tempCollArr) || in_array("all",$tempCollArr)) $this->collectionArr[$collId]["isselected"] = 1;
			}
			$result->close();
			$conn->close();
		}
		return $this->collectionArr;
	}
	
	public function getSurveys(){
		$returnArr = Array();
		$sql = "SELECT p.projname, s.surveyid, s.projectname ".
			"FROM (fmprojects p INNER JOIN omsurveyprojlink spl ON p.pid = spl.pid) ".
			"INNER JOIN omsurveys s ON spl.surveyid = s.surveyid ".
			"WHERE p.occurrencesearch = 1 ".
			"ORDER BY p.projname, s.projectname"; 
		//echo "<div>$sql</div>";
		$conn = $this->getConnection();
		$rs = $conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->projname][$row->surveyid] = $row->projectname;
		}
		$rs->close();
		$conn->close();
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
			foreach($this->getCollectionArr() as $collId => $fieldArr){
				if(array_key_exists("isselected",$fieldArr)) $tempArr[] = $fieldArr["collectioncode"];
			}
			sort($tempArr);
			if(count($this->getCollectionArr()) == count($tempArr)){
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
		$conn = $this->getConnection();
		$rs = $conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnStr .= " ;".$row->projectname; 
		}
		$conn->close();
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
		$conn = $this->getConnection();
		$taxonAuthorityList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
		$result = $conn->query($sql);
		while($row = $result->fetch_object()){
			$taxonAuthorityList[$row->taxauthid] = $row->name;
		}
		$conn->close();
		return $taxonAuthorityList;
	}
	
	private function readRequestVariables(){
		global $clientRoot;
		if(array_key_exists("db",$_REQUEST)){
			$dbs = $_GET["db"];
			if(is_string($dbs)) $dbs = Array($dbs); 
		 	$dbStr = "";
		 	if(in_array("all",$dbs)){
		 		$dbStr = "all";
		 	}
		 	else{
		 		$dbStr = implode(";",$dbs);
		 	}
		 	if($this->useCookies) setCookie("colldbs",$dbStr,0,$clientRoot);
			setCookie("collsurveyid","",time()-3600,$clientRoot);
			$this->searchTermsArr["db"] = $dbStr;
		}
		elseif(array_key_exists("surveyid",$_REQUEST)){
			$surveyidArr = $_GET["surveyid"];
			if(is_string($surveyidArr)) $surveyidArr = Array($surveyidArr); 
		 	$surveyidStr = implode(";",$surveyidArr);
		 	if($this->useCookies) setCookie("collsurveyid",$surveyidStr,0,$clientRoot);
			setCookie("colldbs","",time()-3600,$clientRoot);
			$this->searchTermsArr["surveyid"] = $surveyidStr;
		}
		if(array_key_exists("taxa",$_REQUEST)){
			$taxa = $_REQUEST["taxa"];
			if($taxa){
				$taxaStr = "";
				if(is_numeric($taxa)){
					$sql = "SELECT t.sciname ". 
						"FROM taxa t ".
						"WHERE t.tid = ".$taxa;
					$conn = $this->getConnection();
					$rs = $conn->query($sql);
					while($row = $rs->fetch_object()){
						$taxaStr = $row->sciname;
					}
					$rs->close();
					$conn->close();
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
				$useThes = array_key_exists("thes",$_REQUEST)?$_REQUEST["thes"]:0; 
				if($useThes){
					$collTaxa .= "&usethes:true";
					$this->searchTermsArr["usethes"] = true;
				}
				else{
					$this->searchTermsArr["usethes"] = false;
				}
				$searchType = array_key_exists("type",$_REQUEST)?$_REQUEST["type"]:1;
				if($searchType){
					$collTaxa .= "&taxontype:".$searchType;
					$this->searchTermsArr["taxontype"] = $searchType;
				}
				if($this->useCookies) setCookie("colltaxa",$collTaxa,0,$clientRoot);
			}
			else{
				if($this->useCookies) setCookie("colltaxa","",time()-3600,$clientRoot);
				unset($this->searchTermsArr["taxa"]);
			}
		}
		$searchArr = Array();
		$searchFieldsActivated = false;
		if(array_key_exists("country",$_REQUEST)){
			$country = $_REQUEST["country"];
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
			$state = $_REQUEST["state"];
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
			$county = $_REQUEST["county"];
			$county = str_replace(" Co.","",$county);
			$county = str_replace(" County","",$county);
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
			$local = $_REQUEST["local"];
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
			$collector = $_REQUEST["collector"];
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
			$collNum = $_REQUEST["collnum"];
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
			$clid = $_REQUEST["clid"];
			$searchArr[] = "clid:".$clid;
			$this->searchTermsArr["clid"] = $clid;
			$searchFieldsActivated = true;
		}
		$latLongArr = Array();
		if(array_key_exists("upperlat",$_REQUEST)){
			$upperLat = $_REQUEST["upperlat"];
			if($upperLat || $upperLat === "0") $latLongArr[] = $upperLat;
		
			$bottomlat = $_REQUEST["bottomlat"];
			if($bottomlat || $bottomlat === "0") $latLongArr[] = $bottomlat;
		
			$leftLong = $_REQUEST["leftlong"];
			if($leftLong || $leftLong === "0") $latLongArr[] = $leftLong;
		
			$rightlong = $_REQUEST["rightlong"];
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
			$pointLat = $_REQUEST["pointlat"];
			if($pointLat || $pointLat === "0") $latLongArr[] = $pointLat;
			
			$pointLong = $_REQUEST["pointlong"];
			if($pointLong || $pointLong === "0") $latLongArr[] = $pointLong;
		
			$radius = $_REQUEST["radius"];
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
			if($this->useCookies) setCookie("collsearch",$searchStr,0,$clientRoot);
		}
		elseif($searchFieldsActivated){
			if($this->useCookies) setCookie("collsearch","",time()-3600,$clientRoot);
		}
	}
	
	public function getUseCookies(){
		return $this->useCookies;
	}
}
?>