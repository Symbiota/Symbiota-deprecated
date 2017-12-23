<?php
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSearchSupport.php');

class ImageLibraryManager extends OccurrenceTaxaManager {

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
		return $this->searchSupportManager->getFullCollectionList($catId);
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
		$this->searchTermArr["phuid"] = '';
		if(array_key_exists("phuidstr",$_REQUEST)){
			$phuid = $this->cleanInStr($_REQUEST["phuidstr"]);
			if($phuid){
				$this->searchTermArr["phuid"] = $phuid;
			}
		}
		$this->searchTermArr["tags"] = '';
		if(array_key_exists("tags",$_REQUEST)){
			$tags = $this->cleanInStr($_REQUEST["tags"]);
			if($tags){
				$this->searchTermArr["tags"] = $tags;
			}
		}
		$this->searchTermArr["keywords"] = '';
		if(array_key_exists("keywordstr",$_REQUEST)){
			$keywords = $this->cleanInStr($_REQUEST["keywordstr"]);
			if($keywords){
				$str = str_replace(",",";",$keywords);
				$this->searchTermArr["keywords"] = $str;
			}
		}
		$this->searchTermArr["imagecount"] = '';
		if(array_key_exists("imagecount",$_REQUEST)){
			$imagecount = $this->cleanInStr($_REQUEST["imagecount"]);
			if($imagecount){
				$this->searchTermArr["imagecount"] = $imagecount;
			}
		}
		$this->searchTermArr["imagetype"] = '';
		if(array_key_exists("imagetype",$_REQUEST)){
			$imagetype = $this->cleanInStr($_REQUEST["imagetype"]);
			if($imagetype){
				$this->searchTermArr["imagetype"] = $imagetype;
			}
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
		if(array_key_exists("imagecount",$this->searchTermArr)&&$this->searchTermArr["imagecount"]){
			if($this->searchTermArr["imagecount"] == 'taxon'){
				$sql .= 'GROUP BY ts.tidaccepted ';
			}
			elseif($this->searchTermArr["imagecount"] == 'specimen'){
				$sql .= 'GROUP BY o.occid ';
			}
		}
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= "ORDER BY t.sciname ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		echo "<div>Spec sql: ".$sql."</div>"; exit;
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
	}

	private function getSqlBase($full = true){
		$sql = 'FROM images i ';
		if(isset($this->searchTermArr["taxa"]) && $this->searchTermArr["taxa"]){
			//Query variables include a taxon search, thus use an INNER JOIN since its faster
			$sql .= 'INNER JOIN taxa t ON i.tid = t.tid ';
		}
		else{
			if($this->tidFocus) $sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
			$sql .= 'LEFT JOIN taxa t ON i.tid = t.tid ';
		}
		if($full){
			if(isset($this->searchTermArr["phuid"]) && $this->searchTermArr["phuid"]){
				$sql .= 'INNER JOIN users u ON i.photographeruid = u.uid ';
			}
			else{
				$sql .= 'LEFT JOIN users u ON i.photographeruid = u.uid ';
			}
		}
		if($this->searchTermArr["imagetype"] == 'specimenonly' || $this->searchTermArr["imagetype"] == 'observationonly'){
			$sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid ';
		}
		else{
			$sql .= 'LEFT JOIN omoccurrences o ON i.occid = o.occid ';
			if($full) $sql .= 'LEFT JOIN omcollections c ON o.collid = c.collid ';
		}
		if(array_key_exists("tags",$this->searchTermArr)&&$this->searchTermArr["tags"]){
			$sql .= 'INNER JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermArr)&&$this->searchTermArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords ik ON i.imgid = ik.imgid ';
		}
		return $sql;
	}

	private function setSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("db",$this->searchTermArr) && $this->searchTermArr['db']){
			$sqlWhere .= OccurrenceSearchSupport::getDbWhereFrag($this->cleanInStr($this->searchTermArr['db']));
		}
		if(array_key_exists("taxa",$this->searchTermArr)&&$this->searchTermArr["taxa"]){
			$sqlWhere .= $this->getTaxonWhereFrag();
		}
		elseif($this->tidFocus){
			$sqlWhere .= 'AND (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		}
		if(array_key_exists("phuid",$this->searchTermArr)&&$this->searchTermArr["phuid"]){
			$sqlWhere .= "AND (i.photographeruid IN(".$this->searchTermArr["phuid"].")) ";
		}
		if(array_key_exists("tags",$this->searchTermArr)&&$this->searchTermArr["tags"]){
			$sqlWhere .= 'AND (it.keyvalue = "'.$this->searchTermArr["tags"].'") ';
		}
		if(array_key_exists("keywords",$this->searchTermArr)&&$this->searchTermArr["keywords"]){
			$keywordArr = explode(";",$this->searchTermArr["keywords"]);
			$tempArr = Array();
			foreach($keywordArr as $value){
				$tempArr[] = "(ik.keyword LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if(array_key_exists("imagetype",$this->searchTermArr) && $this->searchTermArr["imagetype"]){
			if($this->searchTermArr["imagetype"] == 'specimenonly'){
				$sqlWhere .= 'AND (i.occid IS NOT NULL) AND (c.colltype = "Preserved Specimens") ';
			}
			elseif($this->searchTermArr["imagetype"] == 'observationonly'){
				$sqlWhere .= 'AND (i.occid IS NOT NULL) AND (c.colltype != "Preserved Specimens") ';
			}
			elseif($this->searchTermArr["imagetype"] == 'fieldonly'){
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

	private function setRecordCnt($sqlFrag){
		if($sqlFrag){
			$sql = '';
			if(array_key_exists("imagecount",$this->searchTermArr) && $this->searchTermArr["imagecount"]){
				if($this->searchTermArr["imagecount"] == 'taxon'){
					$sql = "SELECT COUNT(DISTINCT o.tidinterpreted) AS cnt ";
				}
				elseif($this->searchTermArr["imagecount"] == 'specimen'){
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
	public function setsearchTermArr($stArr){
		$this->searchTermArr = $stArr;
	}

	public function getsearchTermArr(){
		return $this->searchTermArr;
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}
}
?>