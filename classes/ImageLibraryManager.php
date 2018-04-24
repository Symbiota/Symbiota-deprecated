<?php
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSearchSupport.php');

class ImageLibraryManager extends OccurrenceTaxaManager{

	private $searchTermArr = Array();
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
		$sql .= $this->getListSql();
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
		$sql .= $this->getListSql();
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
		$sql .= $this->getListSql();
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

	private function getListSql(){
		$sql = 'FROM images i INNER JOIN taxa t ON i.tid = t.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid ';
		if(array_key_exists("tags",$this->searchTermArr) && $this->searchTermArr["tags"]){
			$sql .= 'INNER JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTersArr) && $this->searchTermArr["keywords"]){
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
		return $this->searchSupportManager->getFullCollectionList($catId, true);
	}

	public function outputFullCollArr($occArr, $targetCatID = 0){
		if(!$this->searchSupportManager) $this->searchSupportManager = new occurrenceSearchSupport($this->conn);
		$this->searchSupportManager->outputFullCollArr($occArr, $targetCatID, false, false);
	}

	private function readRequestVariables(){
		if(array_key_exists("db",$_REQUEST) && $_REQUEST['db']){
			$dbStr = OccurrenceSearchSupport::getDbRequestVariable($_REQUEST);
			if($dbStr) $this->searchTermArr["db"] = $dbStr;
		}
		if(array_key_exists("taxa",$_REQUEST) && $_REQUEST["taxa"]){
			$this->setTaxonRequestVariable();
		}
		if(array_key_exists("phuid",$_REQUEST)){
			$phuid = $this->cleanInStr($_REQUEST["phuid"]);
			if(is_numeric($phuid)){
				$this->searchTermArr["phuid"] = $phuid;
			}
		}
		if(array_key_exists("tags",$_REQUEST)){
			$tags = $this->cleanInStr($_REQUEST["tags"]);
			if($tags){
				$this->searchTermArr["tags"] = $tags;
			}
		}
		if(array_key_exists("keywordstr",$_REQUEST)){
			$keywords = $this->cleanInStr($_REQUEST["keywordstr"]);
			if($keywords){
				$str = str_replace(",",";",$keywords);
				$this->searchTermArr["keywords"] = $str;
			}
		}
		if(array_key_exists("imagecount",$_REQUEST)){
			$imagecount = $this->cleanInStr($_REQUEST["imagecount"]);
			if($imagecount){
				$this->searchTermArr["imagecount"] = $imagecount;
			}
		}
		if(array_key_exists("imagetype",$_REQUEST)){
			$imagetype = $this->cleanInStr($_REQUEST["imagetype"]);
			if(is_numeric($imagetype)){
				$this->searchTermArr["imagetype"] = $imagetype;
			}
		}
	}

	public function getImageArr($pageRequest,$cntPerPage){
		$retArr = Array();
		$this->setRecordCnt();
		$sql = 'SELECT DISTINCT i.imgid, i.tid, t.sciname, i.url, i.thumbnailurl, i.originalurl, i.photographeruid, i.caption, i.occid ';
		/*
		$sql = 'SELECT DISTINCT i.imgid, o.tidinterpreted, t.tid, t.sciname, i.url, i.thumbnailurl, i.originalurl, i.photographeruid, i.caption, '.
			'o.occid, o.stateprovince, o.catalognumber, CONCAT_WS("-",c.institutioncode, c.collectioncode) as instcode ';
		*/
		$sql .= $this->getSqlBase().$this->sqlWhere;
		if(array_key_exists("imagecount",$this->searchTermArr) && $this->searchTermArr["imagecount"]){
			if($this->searchTermArr["imagecount"] == 'taxon'){
				$sql .= 'GROUP BY i.tid ';
			}
			elseif($this->searchTermArr["imagecount"] == 'specimen'){
				$sql .= 'GROUP BY i.occid ';
			}
		}
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		if($this->sqlWhere) $sql .= "ORDER BY t.sciname ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
		$occArr = array();
		$result = $this->conn->query($sql);
		while($r = $result->fetch_object()){
			$imgId = $r->imgid;
			$retArr[$imgId]['imgid'] = $r->imgid;
			//$retArr[$imgId]['tidaccepted'] = $r->tidinterpreted;
			$retArr[$imgId]['tid'] = $r->tid;
			$retArr[$imgId]['sciname'] = $r->sciname;
			$retArr[$imgId]['url'] = $r->url;
			$retArr[$imgId]['thumbnailurl'] = $r->thumbnailurl;
			$retArr[$imgId]['originalurl'] = $r->originalurl;
			$retArr[$imgId]['uid'] = $r->photographeruid;
			$retArr[$imgId]['caption'] = $r->caption;
			$retArr[$imgId]['occid'] = $r->occid;
			//$retArr[$imgId]['stateprovince'] = $r->stateprovince;
			//$retArr[$imgId]['catalognumber'] = $r->catalognumber;
			//$retArr[$imgId]['instcode'] = $r->instcode;
			if($r->occid) $occArr[$r->occid] = $r->occid;
		}
		$result->free();
		if($occArr){
			//Get occurrence data
			$collArr = array();
			$sql2 = 'SELECT occid, catalognumber, stateprovince, collid FROM omoccurrences WHERE occid IN('.implode(',',$occArr).')';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$retArr['occ'][$r2->occid]['catnum'] = $r2->catalognumber;
				$retArr['occ'][$r2->occid]['stateprovince'] = $r2->stateprovince;
				$retArr['occ'][$r2->occid]['collid'] = $r2->collid;
				$collArr[$r2->collid] = $r2->collid;
			}
			$rs2->free();
			//Get collection data
			$sql3 = 'SELECT collid, CONCAT_WS("-",institutioncode, collectioncode) as instcode FROM omcollections WHERE collid IN('.implode(',',$collArr).')';
			$rs3 = $this->conn->query($sql3);
			while($r3 = $rs3->fetch_object()){
				$retArr['coll'][$r3->collid] = $r3->instcode;
			}
			$rs3->free();
		}
		return $retArr;
	}

	private function getSqlBase(){
		$sql = 'FROM images i ';
		if($this->taxaArr){
			$sql .= 'INNER JOIN taxa t ON i.tid = t.tid ';
		}
		else{
			$sql .= 'LEFT JOIN taxa t ON i.tid = t.tid ';
		}
		if(strpos($this->sqlWhere,'ts.taxauthid')){
			$sql .= 'INNER JOIN taxstatus ts ON i.tid = ts.tid ';
		}
		if(strpos($this->sqlWhere,'e.taxauthid') || $this->tidFocus){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		}
		if(isset($this->searchTermArr["imagetype"]) && ($this->searchTermArr["imagetype"] == 1 || $this->searchTermArr["imagetype"] == 2)){
			$sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid INNER JOIN omcollections c ON o.collid = c.collid ';
		}
		elseif(isset($this->searchTermArr['db'])){
			$sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		}
		/*
		else{
			$sql .= 'LEFT JOIN omoccurrences o ON i.occid = o.occid LEFT JOIN omcollections c ON o.collid = c.collid ';
		}
		*/
		if(array_key_exists("tags",$this->searchTermArr) && $this->searchTermArr["tags"]){
			$sql .= 'INNER JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermArr) && $this->searchTermArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords ik ON i.imgid = ik.imgid ';
		}
		return $sql;
	}

	private function setSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("db",$this->searchTermArr) && $this->searchTermArr['db']){
			$sqlWhere .= OccurrenceSearchSupport::getDbWhereFrag($this->cleanInStr($this->searchTermArr['db']));
		}
		if($this->taxaArr){
			$sqlWhereTaxa = '';
			foreach($this->taxaArr['taxa'] as $searchTaxon => $searchArr){
				$taxonType = $this->taxaArr['taxontype'];
				if(isset($searchArr['taxontype'])) $taxonType = $searchArr['taxontype'];
				if($taxonType == TaxaSearchType::TAXONOMIC_GROUP){
					//Class, order, or other higher rank
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						//$sqlWhereTaxa .= 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE (taxauthid = '.$this->taxAuthId.') AND (parenttid IN('.trim($tidStr,',').') OR (tid = '.trim($tidStr,',').')))) ';
						$sqlWhereTaxa .= 'OR ((e.taxauthid = '.$this->taxAuthId.') AND ((i.tid IN('.implode(',', $tidArr).')) OR e.parenttid IN('.implode(',', $tidArr).'))) ';
					}
				}
				elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
					$sqlWhereTaxa .= 'OR ((ts.family = "'.$searchTaxon.'") AND (ts.taxauthid = '.$this->taxAuthId.')) ';
				}
				else{
					if($taxonType == TaxaSearchType::COMMON_NAME){
						//Common name search
						$famArr = array();
						if(array_key_exists("families",$searchArr)){
							$famArr = $searchArr["families"];
						}
						if(array_key_exists("tid",$searchArr)){
							$tidArr = array_keys($searchArr['tid']);
							$sql = 'SELECT DISTINCT t.sciname '.
								'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
								'WHERE (t.rankid = 140) AND (e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid IN('.implode(',',$tidArr).'))';
							$rs = $this->conn->query($sql);
							while($r = $rs->fetch_object()){
								$famArr[] = $r->sciname;
							}
						}
						if($famArr){
							$famArr = array_unique($famArr);
							$sqlWhereTaxa .= 'OR (ts.family IN("'.implode('","',$famArr).'")) ';
						}
						/*
						if(array_key_exists("scinames",$searchArr)){
							foreach($searchArr["scinames"] as $sciName){
								$sqlWhereTaxa .= "OR (o.sciname Like '".$sciName."%') ";
							}
						}
						*/
					}
					else{
						if(array_key_exists("tid",$searchArr)){
							$rankid = current($searchArr['tid']);
							$tidArr = array_keys($searchArr['tid']);
							$sqlWhereTaxa .= "OR (i.tid IN(".implode(',',$tidArr).")) ";
							if($rankid < 230) $sqlWhereTaxa .= 'OR ((e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid IN('.implode(',', $tidArr).')) AND (ts.taxauthid = '.$this->taxAuthId.' AND ts.tid = ts.tidaccepted)) ';
						}
						else{
							//Return matches for "Pinus a"
							$sqlWhereTaxa .= "OR (t.sciname LIKE '".$this->cleanInStr($searchTaxon)."%') ";
						}
					}
					if(array_key_exists("synonyms",$searchArr)){
						$synArr = $searchArr["synonyms"];
						if($synArr){
							$sqlWhereTaxa .= 'OR (i.tid IN('.implode(',',array_keys($synArr)).')) ';
						}
					}
				}
			}
			if($sqlWhereTaxa) $sqlWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
		}
		elseif($this->tidFocus){
			$sqlWhere .= 'AND (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		}
		if(array_key_exists("phuid",$this->searchTermArr)){
			$sqlWhere .= "AND (i.photographeruid IN(".$this->searchTermArr["phuid"].")) ";
		}
		if(array_key_exists("tags",$this->searchTermArr)&&$this->searchTermArr["tags"]){
			$sqlWhere .= 'AND (it.keyvalue = "'.$this->cleanInStr($this->searchTermArr["tags"]).'") ';
		}
		if(array_key_exists("keywords",$this->searchTermArr)&&$this->searchTermArr["keywords"]){
			$keywordArr = explode(";",$this->searchTermArr["keywords"]);
			$tempArr = Array();
			foreach($keywordArr as $value){
				$tempArr[] = "(ik.keyword LIKE '%".$this->cleanInStr($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if(array_key_exists("imagetype",$this->searchTermArr) && $this->searchTermArr["imagetype"]){
			if($this->searchTermArr["imagetype"] == 1){
				//Specimen Images
				$sqlWhere .= 'AND (i.occid IS NOT NULL) AND (c.colltype = "Preserved Specimens") ';
			}
			elseif($this->searchTermArr["imagetype"] == 2){
				//Image Vouchered Observations
				$sqlWhere .= 'AND (i.occid IS NOT NULL) AND (c.colltype != "Preserved Specimens") ';
			}
			elseif($this->searchTermArr["imagetype"] == 3){
				//Field Images (lacking specific locality details)
				$sqlWhere .= 'AND (i.occid IS NULL) ';
			}
		}
		if($sqlWhere){
			$this->sqlWhere = 'WHERE '.substr($sqlWhere,4);
		}
		//echo $sqlWhere;
	}

	private function setRecordCnt(){
		$sql = '';
		if(array_key_exists("imagecount",$this->searchTermArr) && $this->searchTermArr["imagecount"]){
			if($this->searchTermArr["imagecount"] == 'taxon'){
				$sql = "SELECT COUNT(DISTINCT i.tid) AS cnt ";
			}
			elseif($this->searchTermArr["imagecount"] == 'specimen'){
				$sql = "SELECT COUNT(DISTINCT i.occid) AS cnt ";
			}
			else{
				$sql = "SELECT COUNT(DISTINCT i.imgid) AS cnt ";
			}
		}
		else{
			$sql = "SELECT COUNT(DISTINCT i.imgid) AS cnt ";
		}
		$sql .= 'FROM images i ';
		if($this->taxaArr){
			$sql .= 'INNER JOIN taxa t ON i.tid = t.tid ';
		}
		if(strpos($this->sqlWhere,'ts.taxauthid')){
			$sql .= 'INNER JOIN taxstatus ts ON i.tid = ts.tid ';
		}
		if(strpos($this->sqlWhere,'e.taxauthid') || $this->tidFocus){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		}
		if(array_key_exists("tags",$this->searchTermArr) && $this->searchTermArr["tags"]){
			$sql .= 'INNER JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermArr) && $this->searchTermArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords ik ON i.imgid = ik.imgid ';
		}
		if(isset($this->searchTermArr["imagetype"]) && ($this->searchTermArr["imagetype"] == 1 || $this->searchTermArr["imagetype"] == 2)){
			$sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid INNER JOIN omcollections c ON o.collid = c.collid ';
		}
		elseif(isset($this->searchTermArr['db'])){
			$sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		}
		$sql .= $this->sqlWhere;
		//echo "<div>Count sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$this->recordCount = $row->cnt;
		}
		$result->free();
	}

	public function getQueryTermStr(){
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

	public function getPhotographerUidArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT u.uid, CONCAT_WS(", ",u.lastname, u.firstname) AS fullname '.
			'FROM images i INNER JOIN users u ON i.photographeruid = u.uid '.
			'ORDER BY u.lastname, u.firstname ';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->uid] = $r->fullname;
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
	public function getRecordCnt(){
		return $this->recordCount;
	}
}
?>