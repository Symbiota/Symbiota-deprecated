<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/TaxonSearchManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSearchSupport.php');

class ImageLibraryManager extends TaxonSearchManager {

	private $searchTermsArr = Array();
	private $recordCount = 0;
	private $tidFocus;
	private $collArrIndex = 0;
	private $searchSupportManager = null;
	private $sqlWhere = '';

	function __construct() {
		parent::__construct();
		if(array_key_exists('TID_FOCUS', $GLOBALS) && preg_match('/^[\d,]+$/', $GLOBALS['TID_FOCUS'])){
			$this->tidFocus = $GLOBALS['TID_FOCUS'];
		}
		$this->readRequestVariables();
		$this->setSqlWhere();
	}

	function __destruct(){
		parent::__destruct();
	}

	//Image browser functions
	public function getFamilyList(){
		$returnArray = Array();
		$sql = 'SELECT DISTINCT ts.Family ';
		$sql .= $this->getImageSql();
		$sql .= 'AND (ts.Family Is Not Null) ';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->Family;
		}
		$result->free();
		sort($returnArray);
		return $returnArray;
	}

	public function getGenusList($taxon = ''){
		$sql = 'SELECT DISTINCT t.UnitName1 ';
		$sql .= $this->getImageSql();
		if($taxon){
			$taxon = $this->cleanInStr($taxon);
			$sql .= "AND (ts.Family = '".$taxon."') ";
		}
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->UnitName1;
		}
		$result->free();
		sort($returnArray);
		return $returnArray;
	}

	public function getSpeciesList($taxon = ''){
		$retArr = Array();
		$tidArr = Array();
		if($taxon){
			$taxon = $this->cleanInStr($taxon);
			if(strpos($taxon, ' ')) $tidArr = array_keys($this->getSynonyms($taxon));
		}
		$sql = 'SELECT DISTINCT t.tid, t.SciName ';
		$sql .= $this->getImageSql();
		if($tidArr){
			$sql .= 'AND ((t.SciName LIKE "'.$taxon.'%") OR (t.tid IN('.implode(',', $tidArr).'))) ';
		}
		elseif($taxon){
			$sql .= "AND ((t.SciName LIKE '".$taxon."%') OR (ts.family = '".$taxon."')) ";
		}
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->tid] = $row->SciName;
		}
		$result->free();
		asort($retArr);
		return $retArr;
	}

	private function getImageSql(){
		$sql = 'FROM images i INNER JOIN taxa t ON i.tid = t.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid ';
		if(array_key_exists("tags",$this->searchTermsArr) && $this->searchTermsArr["tags"]){
			$sql .= 'INNER JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr) && $this->searchTermsArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords ik ON i.imgid = ik.imgid ';
		}
		if($this->tidFocus) $sql .= 'INNER JOIN taxaenumtree e ON ts.tid = e.tid ';
		if($this->sqlWhere){
			$sql .= $this->sqlWhere.' AND ';
		}
		else{
			$sql .= 'WHERE ';
		}
		$sql .= '(i.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 219) ';
		if($this->tidFocus) $sql .= 'AND (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		return $sql;
	}

	//Image contributor listings
	public function getCollectionImageList(){
		//Get collection names
		$stagingArr = array();
		$sql = 'SELECT collid, CONCAT(collectionname, " (", CONCAT_WS("-",institutioncode,collectioncode),")") as collname, colltype FROM omcollections ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$stagingArr[$r->collid]['name'] = $r->collname;
			$stagingArr[$r->collid]['type'] = (strpos($r->colltype,'Observations') !== false?'obs':'coll');
		}
		$rs->free();
		//Get image counts
		$sql = 'SELECT o.collid, COUNT(i.imgid) AS imgcnt '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid ';
		if($this->tidFocus){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid '.
				'WHERE (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		}
		$sql .= 'GROUP BY o.collid ';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$stagingArr[$row->collid]['imgcnt'] = $row->imgcnt;
		}
		$result->free();
		//Only return collections with images
		$retArr = array();
		foreach($stagingArr as $id => $collArr){
			if(array_key_exists('imgcnt', $collArr)){
				$retArr[$collArr['type']][$id]['imgcnt'] = $collArr['imgcnt'];
				$retArr[$collArr['type']][$id]['name'] = $collArr['name'];
			}
		}
		return $retArr;
	}

	public function getPhotographerList(){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as pname, CONCAT_WS(", ", u.firstname, u.lastname) as fullname, u.email, Count(ti.imgid) AS imgcnt '.
			'FROM users u INNER JOIN images ti ON u.uid = ti.photographeruid ';
		if($this->tidFocus){
			$sql .= 'INNER JOIN taxaenumtree e ON ti.tid = e.tid '.
				'WHERE (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		}
		$sql .= 'GROUP BY u.uid '.
			'ORDER BY u.lastname, u.firstname';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid]['name'] = $row->pname;
			$retArr[$row->uid]['fullname'] = $row->fullname;
			$retArr[$row->uid]['imgcnt'] = $row->imgcnt;
		}
		$result->free();
		return $retArr;
	}

	//Search functions
	public function getFullCollectionList($catId = ''){
		if(!$this->searchSupportManager) $this->searchSupportManager = new occurrenceSearchSupport($this->conn);
		if(isset($this->searchTermArr['db'])) $this->searchSupportManager->setCollidStr($this->searchTermArr['db']);
		return $this->searchSupportManager->getFullCollectionList($catId);
	}

	public function outputFullCollArr($occArr, $targetCatID = 0){
		if(!$this->searchSupportManager) $this->searchSupportManager = new occurrenceSearchSupport($this->conn);
		$this->searchSupportManager->outputFullCollArr($occArr, $targetCatID, false, false);
	}

	private function readRequestVariables(){
		//Search will be confinded to a collid, catid, or will remain open to all collection
		//Limit collids and/or catids
		$dbStr = '';
		$this->searchTermsArr["db"] = '';
		if(array_key_exists("db",$_REQUEST)){
			$dbs = $_REQUEST["db"];
			if(is_string($dbs)){
				$dbStr = $dbs.';';
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
			$this->searchTermsArr["db"] = $dbStr;
		}
		$this->searchTermsArr["taxa"] = '';
		$this->searchTermsArr["taxontype"] = '';
		$this->searchTermsArr["usethes"] = '';
		if(array_key_exists("taxastr",$_REQUEST)){
			$taxa = $this->cleanInStr($_REQUEST["taxastr"]);
			$searchType = array_key_exists("nametype",$_REQUEST)?$this->cleanInStr($_REQUEST["nametype"]):1;
			$this->searchTermsArr["taxontype"] = $searchType;
			$useThes = array_key_exists("thes",$_REQUEST)?$this->cleanInStr($_REQUEST["thes"]):0;
			$this->searchTermsArr["usethes"] = $useThes;
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
						$snStr = ucfirst($snStr);
						$taxaArr[$key] = $snStr;
					}
					$taxaStr = implode(";",$taxaArr);
				}
				$this->searchTermsArr["taxa"] = $taxaStr;
			}
		}
		$this->searchTermsArr["phuid"] = '';
		if(array_key_exists("phuidstr",$_REQUEST)){
			$phuid = $this->cleanInStr($_REQUEST["phuidstr"]);
			if($phuid){
				$this->searchTermsArr["phuid"] = $phuid;
			}
		}
		$this->searchTermsArr["tags"] = '';
		if(array_key_exists("tags",$_REQUEST)){
			$tags = $this->cleanInStr($_REQUEST["tags"]);
			if($tags){
				$this->searchTermsArr["tags"] = $tags;
			}
		}
		$this->searchTermsArr["keywords"] = '';
		if(array_key_exists("keywordstr",$_REQUEST)){
			$keywords = $this->cleanInStr($_REQUEST["keywordstr"]);
			if($keywords){
				$str = str_replace(",",";",$keywords);
				$this->searchTermsArr["keywords"] = $str;
			}
		}
		$this->searchTermsArr["imagecount"] = '';
		if(array_key_exists("imagecount",$_REQUEST)){
			$imagecount = $this->cleanInStr($_REQUEST["imagecount"]);
			if($imagecount){
				$this->searchTermsArr["imagecount"] = $imagecount;
			}
		}
		$this->searchTermsArr["imagetype"] = '';
		if(array_key_exists("imagetype",$_REQUEST)){
			$imagetype = $this->cleanInStr($_REQUEST["imagetype"]);
			if($imagetype){
				$this->searchTermsArr["imagetype"] = $imagetype;
			}
		}
	}

	public function setTaxon($taxon){
		if($taxon){
			$this->searchTermsArr["taxontype"] = 2;
			$this->searchTermsArr["usethes"] = 1;
			$this->searchTermsArr["taxa"] = $taxon;
		}
	}

	private function setSqlWhere(){
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
					$sqlWhere .= 'AND ('.$dbStr.') ';
				}
			}
		}

		if(array_key_exists("taxa",$this->searchTermsArr)&&$this->searchTermsArr["taxa"]){
			$useThes		   = (array_key_exists("usethes",$this->searchTermsArr)?$this->searchTermsArr["usethes"]:0);
			$baseSearchType	= $this->searchTermsArr["taxontype"];
			$taxaSearchTerms   = explode(";",trim($this->searchTermsArr["taxa"]));
			$this->setTaxaArr($useThes,$baseSearchType,$taxaSearchTerms);

			$sqlWhereTaxa = "";
			foreach($this->taxaArr as $key => $valueArray){
				$taxaSearchType = $valueArray['taxontype'];
				if($taxaSearchType == TaxaSearchType::FAMILY_ONLY){
					$rs1 = $this->conn->query("SELECT tid, rankid FROM taxa WHERE (sciname = '".$key."')");
					if($r1 = $rs1->fetch_object()){
						if($r1->rankid < 180){
							$sqlWhereTaxa = 'OR (i.tid IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid IN('.$r1->tid.'))) ';
						}
					}
					if(!$sqlWhereTaxa){
						$sqlWhereTaxa = "OR (t.sciname LIKE '".$key."%') ";
						//Look for synonyms
						if(array_key_exists("synonyms",$valueArray)){
							$synArr = $valueArray["synonyms"];
							if($synArr){
								foreach($synArr as $synTid => $sciName){
									if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
										$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
									}
								}
								$sqlWhereTaxa .= 'OR (i.tid IN('.implode(',',array_keys($synArr)).')) ';
							}
						}
					}
				}
				else{
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
							$sqlWhereTaxa .= "OR (t.sciname LIKE '".$sciName."%') ";
						}
					}
				}
			}
			$sqlWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
		}
		elseif($this->tidFocus){
			$sqlWhere .= 'AND (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		}
		if(array_key_exists("phuid",$this->searchTermsArr)&&$this->searchTermsArr["phuid"]){
			$sqlWhere .= "AND (i.photographeruid IN(".$this->searchTermsArr["phuid"].")) ";
		}
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sqlWhere .= 'AND (it.keyvalue = "'.$this->searchTermsArr["tags"].'") ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$keywordArr = explode(";",$this->searchTermsArr["keywords"]);
			$tempArr = Array();
			foreach($keywordArr as $value){
				$tempArr[] = "(ik.keyword LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if(array_key_exists("imagetype",$this->searchTermsArr) && $this->searchTermsArr["imagetype"]){
			if($this->searchTermsArr["imagetype"] == 'specimenonly'){
				$sqlWhere .= 'AND (i.occid IS NOT NULL) AND (c.colltype = "Preserved Specimens") ';
			}
			elseif($this->searchTermsArr["imagetype"] == 'observationonly'){
				$sqlWhere .= 'AND (i.occid IS NOT NULL) AND (c.colltype != "Preserved Specimens") ';
			}
			elseif($this->searchTermsArr["imagetype"] == 'fieldonly'){
				$sqlWhere .= 'AND (i.occid IS NULL) ';
			}
		}
		if($sqlWhere){
			$this->sqlWhere = 'WHERE '.substr($sqlWhere,4);
		}
		else{
			//Make the sql valid, but return nothing
			//$this->sqlWhere = 'WHERE o.collid = -1 ';
		}
	}

	public function getImageArr($pageRequest,$cntPerPage){
		$retArr = Array();
		$sqlFrag = $this->getSqlBase().$this->sqlWhere;
		$this->setRecordCnt($sqlFrag);
		$sql = 'SELECT DISTINCT i.imgid, o.tidinterpreted, t.tid, t.sciname, i.url, i.thumbnailurl, i.originalurl, '.
			'u.uid, u.lastname, u.firstname, i.caption, '.
			'o.occid, o.stateprovince, o.catalognumber, CONCAT_WS("-",c.institutioncode, c.collectioncode) as instcode ';
		$sql .= $sqlFrag;
		if(array_key_exists("imagecount",$this->searchTermsArr)&&$this->searchTermsArr["imagecount"]){
			if($this->searchTermsArr["imagecount"] == 'taxon'){
				$sql .= 'GROUP BY ts.tidaccepted ';
			}
			elseif($this->searchTermsArr["imagecount"] == 'specimen'){
				$sql .= 'GROUP BY o.occid ';
			}
		}
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= "ORDER BY t.sciname ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($r = $result->fetch_object()){
			$imgId = $r->imgid;
			$retArr[$imgId]['imgid'] = $r->imgid;
			$retArr[$imgId]['tidaccepted'] = $r->tidinterpreted;
			$retArr[$imgId]['tid'] = $r->tid;
			$retArr[$imgId]['sciname'] = $r->sciname;
			$retArr[$imgId]['url'] = $r->url;
			$retArr[$imgId]['thumbnailurl'] = $r->thumbnailurl;
			$retArr[$imgId]['originalurl'] = $r->originalurl;
			$retArr[$imgId]['uid'] = $r->uid;
			$retArr[$imgId]['lastname'] = $r->lastname;
			$retArr[$imgId]['firstname'] = $r->firstname;
			$retArr[$imgId]['caption'] = $r->caption;
			$retArr[$imgId]['occid'] = $r->occid;
			$retArr[$imgId]['stateprovince'] = $r->stateprovince;
			$retArr[$imgId]['catalognumber'] = $r->catalognumber;
			$retArr[$imgId]['instcode'] = $r->instcode;
		}
		$result->free();
		return $retArr;
		//return $sql;
	}

	private function setRecordCnt($sqlFrag){
		if($sqlFrag){
			$sql = '';
			if(array_key_exists("imagecount",$this->searchTermsArr) && $this->searchTermsArr["imagecount"]){
				if($this->searchTermsArr["imagecount"] == 'taxon'){
					$sql = "SELECT COUNT(DISTINCT o.tidinterpreted) AS cnt ";
				}
				elseif($this->searchTermsArr["imagecount"] == 'specimen'){
					$sql = "SELECT COUNT(DISTINCT o.occid) AS cnt ";
				}
				else{
					$sql = "SELECT COUNT(i.imgid) AS cnt ";
				}
			}
			else{
				$sql = "SELECT COUNT(i.imgid) AS cnt ";
			}
			$sql .= $sqlFrag;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->free();
		}
	}

	private function getSqlBase($full = true){
		$sql = 'FROM images i ';
		if(isset($this->searchTermsArr["taxa"]) && $this->searchTermsArr["taxa"]){
			//Query variables include a taxon search, thus use an INNER JOIN since its faster
			$sql .= 'INNER JOIN taxa t ON i.tid = t.tid ';
		}
		else{
			if($this->tidFocus) $sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
			$sql .= 'LEFT JOIN taxa t ON i.tid = t.tid ';
		}
		if($full){
			if(isset($this->searchTermsArr["phuid"]) && $this->searchTermsArr["phuid"]){
				$sql .= 'INNER JOIN users u ON i.photographeruid = u.uid ';
			}
			else{
				$sql .= 'LEFT JOIN users u ON i.photographeruid = u.uid ';
			}
		}
		if($this->searchTermsArr["imagetype"] == 'specimenonly' || $this->searchTermsArr["imagetype"] == 'observationonly'){
			$sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid ';
		}
		else{
			$sql .= 'LEFT JOIN omoccurrences o ON i.occid = o.occid ';
			if($full) $sql .= 'LEFT JOIN omcollections c ON o.collid = c.collid ';
		}
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sql .= 'INNER JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords ik ON i.imgid = ik.imgid ';
		}
		return $sql;
	}

	//Listing functions
	public function getTaxaSuggest($queryString, $type = 'sciname'){
		$retArr = array();
		$sql = '';
		if($type == 'sciname'){
			$sql = 'SELECT tid, sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 10';
		}
		else{
			$sql = 'SELECT tid, vernacularname FROM taxavernaculars WHERE VernacularName LIKE "'.$queryString.'%" LIMIT 10 ';
		}
		$rs = $con->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->tid] = htmlentities($r->sciname);
		}
		$rs->free();
		return $retArr;
	}

	public function getPhotographerSuggest($queryString){
		$retArr = array();
		$sql = 'SELECT DISTINCT u.uid, CONCAT_WS(" ",u.firstname,u.lastname) AS fullname '.
			'FROM images i INNER JOIN users u ON i.photographeruid = u.uid '.
			'WHERE u.firstname LIKE "'.$queryString.'%" OR u.lastname LIKE "'.$queryString.'%" '.
			'ORDER BY fullname LIMIT 10 ';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->uid] = htmlentities($r->fullname);
		}
		$rs->free();
		return $retArr;
	}

	public function getTagArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT keyvalue FROM imagetag ORDER BY keyvalue ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->keyvalue;
			}
		}
		$rs->free();
		return $retArr;
	}

	public function getKeywordSuggest($queryString){
		$retArr = array();
		$sql = 'SELECT DISTINCT keyword FROM imagekeywords WHERE keyword LIKE "'.$queryString.'%" LIMIT 10 ';
		$rs = $this->conn->query($sql);
		$i = 0;
		while ($r = $rs->fetch_object()) {
			$retArr[$i]['name'] = htmlentities($r->keyword);
			$i++;
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setSearchTermsArr($stArr){
		$this->searchTermsArr = $stArr;
	}

	public function getSearchTermsArr(){
		return $this->searchTermsArr;
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}
}
?>