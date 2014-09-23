<?php
include_once($serverRoot.'/config/dbconnection.php');

class ReferenceManager{

	private $conn;
	private $refId = 0;
	private $refAuthId = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}

	public function getRefList($keyword,$author){
		$retArr = array();
		if($keyword || $author){
			$sql = 'SELECT r.refid, r.title, r.secondarytitle, r.pubdate, r.edition, r.volume, '.
				'GROUP_CONCAT(CONCAT(a.lastname,", ",CONCAT_WS(".",LEFT(a.firstname,1),LEFT(a.middlename,1))) SEPARATOR ", ") AS authline '.
				'FROM referenceobject AS r LEFT JOIN referenceauthorlink AS l ON r.refid = l.refid '.
				'LEFT JOIN referenceauthors AS a ON l.refauthid = a.refauthorid ';
			if($keyword && !$author){
				$sql .= 'WHERE r.title LIKE "%'.$keyword.'%" ';
			}
			if(!$keyword && $author){
				$sql .= 'WHERE a.lastname LIKE "%'.$author.'%" ';
			}
			if($keyword && $author){
				$sql .= 'WHERE r.title LIKE "%'.$keyword.'%" AND a.lastname LIKE "%'.$author.'%" ';
			}
			$sql .= 'GROUP BY r.refid ';
			$sql .= 'ORDER BY r.title';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->refid]['refid'] = $r->refid;
					$retArr[$r->refid]['title'] = $r->title;
					$retArr[$r->refid]['secondarytitle'] = $r->secondarytitle;
					$retArr[$r->refid]['pubdate'] = $r->pubdate;
					$retArr[$r->refid]['edition'] = $r->edition;
					$retArr[$r->refid]['volume'] = $r->volume;
					$retArr[$r->refid]['authline'] = $r->authline;
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getRefTypeArr(){
		$retArr = array();
		$sql = 'SELECT ReferenceTypeId, ReferenceType '. 
			'FROM referencetype '.
			'ORDER BY ReferenceType';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->ReferenceTypeId] = $r->ReferenceType;
			}
		}
		return $retArr;
	}
	
	public function createReference($pArr){
		global $SYMB_UID;
		$statusStr = '';
		$sql = 'INSERT INTO referenceobject(title,ReferenceTypeId,modifieduid,modifiedtimestamp) '.
			'VALUES("'.$this->cleanInStr($pArr['newreftitle']).'","'.$this->cleanInStr($pArr['newreftype']).'",'.$SYMB_UID.',now()) ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->refId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new reference failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function getRefArr($refId){
		$retArr = array();
		$sql = 'SELECT o. refid, o.title, o.secondarytitle, o.shorttitle, o.pubdate, o.edition, o.volume, o.numbervolumnes, '.
			'o.number, o.pages, o.section, o.placeofpublication, o.publisher, o.isbn_issn, o.url, '.
			'o.libraryNumber, o.guid, o.ispublished, o.notes, t.ReferenceType, t.ReferenceTypeId '.
			'FROM referenceobject AS o LEFT JOIN referencetype AS t ON o.ReferenceTypeId = t.ReferenceTypeId '.
			'WHERE o. refid = '.$refId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['refid'] = $r->refid;
				$retArr['title'] = $r->title;
				$retArr['secondarytitle'] = $r->secondarytitle;
				$retArr['shorttitle'] = $r->shorttitle;
				$retArr['pubdate'] = $r->pubdate;
				$retArr['edition'] = $r->edition;
				$retArr['volume'] = $r->volume;
				$retArr['numbervolumnes'] = $r->numbervolumnes;
				$retArr['number'] = $r->number;
				$retArr['pages'] = $r->pages;
				$retArr['section'] = $r->section;
				$retArr['placeofpublication'] = $r->placeofpublication;
				$retArr['publisher'] = $r->publisher;
				$retArr['isbn_issn'] = $r->isbn_issn;
				$retArr['url'] = $r->url;
				$retArr['libraryNumber'] = $r->libraryNumber;
				$retArr['guid'] = $r->guid;
				$retArr['ispublished'] = $r->ispublished;
				$retArr['notes'] = $r->notes;
				$retArr['ReferenceType'] = $r->ReferenceType;
				$retArr['ReferenceTypeId'] = $r->ReferenceTypeId;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getRefAuthArr($refId){
		$retArr = array();
		$sql = 'SELECT a.refauthorid, CONCAT_WS(" ",a.firstname,a.middlename,a.lastname) AS authorName '.
			'FROM referenceauthorlink AS l LEFT JOIN referenceauthors AS a ON l.refauthid = a.refauthorid '.
			'WHERE l.refid = '.$refId.' '.
			'ORDER BY authorName';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->refauthorid] = $r->authorName;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getRefChecklistArr($refId){
		$retArr = array();
		$sql = 'SELECT l.clid, a.Name '.
			'FROM referencechecklistlink AS l LEFT JOIN fmchecklists AS a ON l.clid = a.CLID '.
			'WHERE l.refid = '.$refId.' '.
			'ORDER BY a.Name';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->Name;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getRefCollArr($refId){
		$retArr = array();
		$sql = 'SELECT l.collid, a.CollectionName '.
			'FROM referencecollectionlink AS l LEFT JOIN omcollections AS a ON l.collid = a.CollID '.
			'WHERE l.refid = '.$refId.' '.
			'ORDER BY a.CollectionName';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->collid] = $r->CollectionName;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getRefOccArr($refId){
		$retArr = array();
		$sql = 'SELECT l.occid, CONCAT_WS("; ",a.sciname, a.catalognumber, CONCAT(a.recordedby," (",IFNULL(a.recordnumber,"s.n."),")")) AS identifier '.
			'FROM referenceoccurlink AS l LEFT JOIN omoccurrences AS a ON l.occid = a.occid '.
			'WHERE l.refid = '.$refId.' '.
			'ORDER BY a.sciname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->occid] = $r->identifier;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getRefTaxaArr($refId){
		$retArr = array();
		$sql = 'SELECT l.tid, a.SciName '.
			'FROM referencetaxalink AS l LEFT JOIN taxa AS a ON l.tid = a.TID '.
			'WHERE l.refid = '.$refId.' '.
			'ORDER BY a.SciName';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->tid] = $r->SciName;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function addAuthor($refId,$refAuthId){
		$statusStr = '';
		$sql = 'INSERT INTO referenceauthorlink(refid,refauthid) '.
			'VALUES('.$refId.','.$refAuthId.') ';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'Success!';
		}
		else{
			$statusStr = 'ERROR: Creation of new reference author failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function deleteRefAuthor($refId,$refAuthId){
		$statusStr = '';
		$sql = 'DELETE FROM referenceauthorlink '.
				'WHERE (refid = '.$refId.') AND (refauthid = '.$refAuthId.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'Reference author deleted.';
		}
		else{
			$statusStr = 'ERROR: Deletion of reference author failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function deleteReference($refId){
		$statusStr = '';
		$sql = 'DELETE FROM referenceauthorlink '.
				'WHERE (refid = '.$refId.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$sql = 'DELETE FROM referenceobject '.
					'WHERE (refid = '.$refId.')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = 'Reference deleted.';
			}
		}
		else{
			$statusStr = 'ERROR: Deletion of reference failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function deleteRefLink($refId,$table,$field,$id){
		$statusStr = '';
		$sql = 'DELETE FROM '.$table.' '.
				'WHERE (refid = '.$refId.') AND ('.$field.' = '.$id.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'Success!';
		}
		else{
			$statusStr = 'ERROR: Deletion of reference link failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function createAuthor($firstName,$middleName,$lastName){
		global $SYMB_UID;
		$statusStr = '';
		$sql = 'INSERT INTO referenceauthors(firstname,middlename,lastname,modifieduid,modifiedtimestamp) '.
			'VALUES("'.$this->cleanInStr($firstName).'","'.$this->cleanInStr($middleName).'","'.$this->cleanInStr($lastName).'",'.$SYMB_UID.',now()) ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->refAuthId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new author failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function getRefTypeFieldArr($refTypeId){
		$retArr = array();
		$sql = 'SELECT ReferenceTypeId, ReferenceType, IsPublished, IsParent, `Year`, Title, SecondaryTitle, PlacePublished, '.
			'Publisher, Volume, NumberVolumes, Number, Pages, Section, TertiaryTitle, Edition, `Date`, TypeWork, ShortTitle, '.
			'AlternativeTitle, ISBN_ISSN, OriginalPublication, ReprintEdition, ReviewedItem, Figures '.
			'FROM referencetype '.
			'WHERE ReferenceTypeId = '.$refTypeId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['ReferenceTypeId'] = $r->ReferenceTypeId;
				$retArr['ReferenceType'] = $r->ReferenceType;
				$retArr['IsPublished'] = $r->IsPublished;
				$retArr['IsParent'] = $r->IsParent;
				$retArr['Year'] = $r->Year;
				$retArr['Title'] = $r->Title;
				$retArr['SecondaryTitle'] = $r->SecondaryTitle;
				$retArr['PlacePublished'] = $r->PlacePublished;
				$retArr['Publisher'] = $r->Publisher;
				$retArr['Volume'] = $r->Volume;
				$retArr['NumberVolumes'] = $r->NumberVolumes;
				$retArr['Number'] = $r->Number;
				$retArr['Pages'] = $r->Pages;
				$retArr['Section'] = $r->Section;
				$retArr['TertiaryTitle'] = $r->TertiaryTitle;
				$retArr['Edition'] = $r->Edition;
				$retArr['Date'] = $r->Date;
				$retArr['TypeWork'] = $r->TypeWork;
				$retArr['ShortTitle'] = $r->ShortTitle;
				$retArr['AlternativeTitle'] = $r->AlternativeTitle;
				$retArr['ISBN_ISSN'] = $r->ISBN_ISSN;
				$retArr['OriginalPublication'] = $r->OriginalPublication;
				$retArr['ReprintEdition'] = $r->ReprintEdition;
				$retArr['ReviewedItem'] = $r->ReviewedItem;
				$retArr['Figures'] = $r->Figures;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function editReference($pArr){
		global $SYMB_UID;
		$statusStr = '';
		$refId = $pArr['refid'];
		if(is_numeric($refId)){
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'refid'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE referenceobject SET '.substr($sql,1).',modifieduid='.$SYMB_UID.',modifiedtimestamp=now() WHERE (refid = '.$refId.')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of reference failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	//Get and set functions 
	public function getRefId(){
		return $this->refId;
	}
	
	public function getRefAuthId(){
		return $this->refAuthId;
	}
	
	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>