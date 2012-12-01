<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ExsiccatiManager {

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTitleObj($ometid){
		$retArr = array();
		if($ometid){
			//Display full list
			$sql = 'SELECT et.ometid, et.title, et.abbreviation, et.editor, et.exsrange, et.startdate, et.enddate, '.
				'et.source, et.notes, et.lasteditedby '.
				'FROM omexsiccatititles et '.
				'WHERE ometid = '.$ometid;
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['title'] = $this->cleanOutStr($r->title);
					$retArr['abbreviation'] = $this->cleanOutStr($r->abbreviation);
					$retArr['editor'] = $this->cleanOutStr($r->editor);
					$retArr['exsrange'] = $this->cleanOutStr($r->exsrange);
					$retArr['startdate'] = $this->cleanOutStr($r->startdate);
					$retArr['enddate'] = $this->cleanOutStr($r->enddate);
					$retArr['source'] = $this->cleanOutStr($r->source);
					$retArr['notes'] = $this->cleanOutStr($r->notes);
					$retArr['lasteditedby'] = $r->lasteditedby;
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getTitleArr($searchTerm, $specimenOnly, $imagesOnly, $collId){
		$retArr = array();
		$sql = '';
		$sqlWhere = '';
		if($specimenOnly){
			if($imagesOnly){
				$sql = 'SELECT DISTINCT et.ometid, et.title, et.abbreviation, et.editor, et.exsrange, et.startdate, et.enddate, '.
					'et.source, et.notes, et.lasteditedby '.
					'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
					'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
					'INNER JOIN images i ON ol.occid = i.occid ';
				if($collId){
					$sql .= 'INNER JOIN omoccurrences o ON ol.occid = o.occid ';
					$sqlWhere = 'WHERE o.collid = '.$collId.' ';
				}
			}
			else{
				//Display only exsiccati that have linked specimens
				$sql = 'SELECT DISTINCT et.ometid, et.title, et.abbreviation, et.editor, et.exsrange, et.startdate, et.enddate, '.
					'et.source, et.notes, et.lasteditedby '.
					'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
					'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid ';
				if($collId){
					$sql .= 'INNER JOIN omoccurrences o ON ol.occid = o.occid ';
					$sqlWhere = 'WHERE o.collid = '.$collId.' ';
				}
			}
		}
		else{
			//Display full list
			$sql = 'SELECT et.ometid, et.title, et.abbreviation, et.editor, et.exsrange, et.startdate, et.enddate, '.
				'et.source, et.notes, et.lasteditedby '.
				'FROM omexsiccatititles et ';
		}
		if($searchTerm){
			if($sqlWhere){
				$sqlWhere .= 'AND ';
			}
			else{
				$sqlWhere = 'WHERE ';
			}
			$sqlWhere .= 'et.title LIKE "%'.$searchTerm.'%" OR et.abbreviation LIKE "%'.$searchTerm.'%" OR et.editor LIKE "%'.$searchTerm.'%"';
		}
		$sql = $sql.$sqlWhere.'ORDER BY et.title';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->ometid]['title'] = $this->cleanOutStr($r->title);
				$retArr[$r->ometid]['abbreviation'] = $this->cleanOutStr($r->abbreviation);
				$retArr[$r->ometid]['editor'] = $this->cleanOutStr($r->editor);
				$retArr[$r->ometid]['exsrange'] = $this->cleanOutStr($r->exsrange);
				$retArr[$r->ometid]['startdate'] = $this->cleanOutStr($r->startdate);
				$retArr[$r->ometid]['enddate'] = $this->cleanOutStr($r->enddate);
				$retArr[$r->ometid]['source'] = $this->cleanOutStr($r->source);
				$retArr[$r->ometid]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->ometid]['lasteditedby'] = $r->lasteditedby;
			}
			$rs->close();
		}
		return $retArr;
	}

	public function getExsNumberArr($ometid,$specimenOnly,$imagesOnly,$collId){
		$retArr = array();
		if($ometid){
			//Grab all numbers for that exsiccati title; only show number that have occid links
			$sql = 'SELECT DISTINCT en.omenid, en.exsnumber, en.notes, CONCAT_WS("-",c.institutioncode,c.collectioncode) AS collcode, '.
				'IFNULL(o.occurrenceid,o.catalognumber) AS catnum, '.
				'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),") ",IFNULL(o.eventDate,"date unknown")) as collector '.
				'FROM omexsiccatinumbers en '.($specimenOnly&&$imagesOnly?'INNER':'LEFT').' JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
				($specimenOnly&&$imagesOnly?'INNER':'LEFT').' JOIN omoccurrences o ON ol.occid = o.occid '.
				($specimenOnly&&$imagesOnly?'INNER':'LEFT').' JOIN omcollections c ON o.collid = c.collid ';
			if($imagesOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid '; 
			$sql .= 'WHERE en.ometid = '.$ometid.' ';
			if($collId) $sql .= 'AND o.collid = '.$collId.' ';
			$sql .= 'ORDER BY en.exsnumber+1,en.exsnumber,ol.ranking';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					if(!array_key_exists($r->omenid,$retArr)){
						$retArr[$r->omenid]['number'] = $this->cleanOutStr($r->exsnumber);
						$retArr[$r->omenid]['notes'] = $this->cleanOutStr($r->notes);
						$retArr[$r->omenid]['catnum'] = $r->catnum;
						if(stripos($r->catnum,$r->collcode) === false) $retArr[$r->omenid]['collcode'] = $r->collcode;
						$retArr[$r->omenid]['collector'] = $r->collector;
					}
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getExsNumberObj($omenid){
		$retArr = array();
		if($omenid){
			//Grab info for just that exsiccati number with the title info
			$sql = 'SELECT et.ometid, et.title, et.abbreviation, et.editor, et.exsrange, en.exsnumber, en.notes '.
				'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '. 
				'WHERE en.omenid = '.$omenid;
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$retArr['ometid'] = $r->ometid;
					$retArr['title'] = $this->cleanOutStr($r->title);
					$retArr['abbreviation'] = $this->cleanOutStr($r->abbreviation);
					$retArr['editor'] = $this->cleanOutStr($r->editor);
					$retArr['exsrange'] = $this->cleanOutStr($r->exsrange);
					$retArr['exsnumber'] = $this->cleanOutStr($r->exsnumber);
					$retArr['notes'] = $this->cleanOutStr($r->notes);
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getExsOccArr($omenid){
		$retArr = array();
		$sql = 'SELECT ol.ranking, ol.notes, o.occid, o.occurrenceid, o.catalognumber, '.
			'CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")") AS collname, '.
			'o.sciname, o.scientificnameauthorship, o.recordedby, o.recordnumber, DATE_FORMAT(o.eventdate,"%d %M %Y") AS eventdate, '.
			'trim(o.country) AS country, trim(o.stateprovince) AS stateprovince, trim(o.county) AS county, '.
			'trim(o.municipality) AS municipality, o.locality, i.thumbnailurl, i.url '.
			'FROM omexsiccatiocclink ol INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'LEFT JOIN images i ON o.occid = i.occid '.
			'WHERE ol.omenid = '.$omenid.' ORDER BY ol.ranking, o.recordedby, o.recordnumber';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['ranking'] = $this->cleanOutStr($r->ranking);
				$retArr[$r->occid]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->occid]['collname'] = $this->cleanOutStr($r->collname);
				$retArr[$r->occid]['occurrenceid'] = $r->occurrenceid;
				$retArr[$r->occid]['catalognumber'] = $r->catalognumber;
				$retArr[$r->occid]['sciname'] = $this->cleanOutStr($r->sciname);
				$retArr[$r->occid]['author'] = $this->cleanOutStr($r->scientificnameauthorship);
				$retArr[$r->occid]['recordedby'] = $this->cleanOutStr($r->recordedby);
				$retArr[$r->occid]['recordnumber'] = $this->cleanOutStr($r->recordnumber);
				$retArr[$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->occid]['country'] = $r->country;
				$retArr[$r->occid]['stateprovince'] = $r->stateprovince;
				$retArr[$r->occid]['county'] = $r->county;
				$retArr[$r->occid]['municipality'] = $r->municipality;
				$retArr[$r->occid]['locality'] = $this->cleanOutStr($r->locality);
				if($r->url){ 
					$retArr[$r->occid]['url'] = $r->url;
					$retArr[$r->occid]['tnurl'] = ($r->thumbnailurl?$r->thumbnailurl:$r->url);
				}
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function addTitle($pArr,$editedBy){
		$con = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omexsiccatititles(title, abbreviation, editor, exsrange, startdate, enddate, source, notes,lasteditedby) '.
			'VALUES("'.$this->cleanInStr($pArr['title']).'","'.$this->cleanInStr($pArr['abbreviation']).'","'.
			$this->cleanInStr($pArr['editor']).'",'.
			($pArr['exsrange']?'"'.$this->cleanInStr($pArr['exsrange']).'"':'NULL').','.
			($pArr['startdate']?'"'.$this->cleanInStr($pArr['startdate']).'"':'NULL').','.
			($pArr['enddate']?'"'.$this->cleanInStr($pArr['enddate']).'"':'NULL').','.
			($pArr['source']?'"'.$this->cleanInStr($pArr['source']).'"':'NULL').','.
			($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').',"'.
			$editedBy.'")';
		//echo $sql;
		$con->query($sql);
		$con->close();
	}
	
	public function editTitle($pArr,$editedBy){
		$con = MySQLiConnectionFactory::getCon("write");
		$sql = 'UPDATE omexsiccatititles '.
			'SET title = "'.$this->cleanInStr($pArr['title']).'", abbreviation = "'.$this->cleanInStr($pArr['abbreviation']).
			'", editor = "'.$this->cleanInStr($pArr['editor']).'"'.
			', exsrange = '.($pArr['exsrange']?'"'.$this->cleanInStr($pArr['exsrange']).'"':'NULL').
			', startdate = '.($pArr['startdate']?'"'.$this->cleanInStr($pArr['exsrange']).'"':'NULL').
			', enddate = '.($pArr['enddate']?'"'.$this->cleanInStr($pArr['enddate']).'"':'NULL').
			', source = '.($pArr['source']?'"'.$this->cleanInStr($pArr['source']).'"':'NULL').
			', notes = '.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').' '.
			', lasteditedby = "'.$editedBy.'" '.
			'WHERE (ometid = '.$pArr['ometid'].')';
		//echo $sql;
		$con->query($sql);
		$con->close();
	}

	public function deleteTitle($ometid){
		$retStr = '';
		if($ometid && is_numeric($ometid)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'DELETE FROM omexsiccatititles WHERE (ometid = '.$ometid.')';
			//echo $sql;
			if(!$con->query($sql)) $retStr = 'DELETE Failed: possibly due to existing exsiccati numbers, which first have to be deleted.';
			$con->close();
		}
		return $retStr;
	}

	public function addNumber($pArr){
		$con = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber,notes) '.
			'VALUES('.$pArr['ometid'].',"'.$this->cleanInStr($pArr['exsnumber']).'",'.
			($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
		//echo $sql;
		$con->query($sql);
		$con->close();
	}

	public function editNumber($pArr){
		if($pArr['omenid'] && is_numeric($pArr['omenid'])){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omexsiccatinumbers '.
				'SET exsnumber = "'.$this->cleanInStr($pArr['exsnumber']).'",'.
				'notes = '.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').' '.
				'WHERE (omenid = '.$pArr['omenid'].')';
			$con->query($sql);
			$con->close();
		}
	}

	public function deleteNumber($omenid){
		if($omenid && is_numeric($omenid)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'DELETE FROM omexsiccatinumbers WHERE (omenid = '.$omenid.')';
			$con->query($sql);
			$con->close();
		}
	}

	public function addOccLink($pArr){
		$collId = $pArr['occaddcollid'];
		if($collId && $pArr['omenid'] && is_numeric($pArr['omenid'])){
			$con = MySQLiConnectionFactory::getCon("write");
			$ranking = 10;
			if($pArr['ranking'] && is_numeric($pArr['ranking'])) $ranking = $pArr['ranking'];
			$identifier = $pArr['identifier'];
			$sql = '';
			if($collId == 'occid' && $identifier && is_numeric($identifier)){
				$sql = 'INSERT INTO omexsiccatiocclink(omenid,occid,ranking,notes) '.
					'VALUES ('.$pArr['omenid'].','.$identifier.','.$ranking.','.
					($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
			}
			else{
				$sql = 'INSERT INTO omexsiccatiocclink(omenid,occid,ranking,notes) '.
					'SELECT '.$pArr['omenid'].' AS omenid,o.occid,'.$ranking.' AS ranking,'.
					($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').' AS notes '.
					'FROM omoccurrences o '.
					'WHERE o.collid = '.$collId.' AND o.catalogNumber = '.(is_numeric($identifier)?$identifier:'"'.$identifier.'"');
			}
			//echo $sql;
			$con->query($sql);
			$con->close();
		}
	}

	public function editOccLink($pArr){
		if($pArr['omenid'] && $pArr['occid'] && is_numeric($pArr['omenid']) && is_numeric($pArr['occid']) && is_numeric($pArr['ranking'])){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omexsiccatiocclink '.
				'SET ranking = '.$pArr['ranking'].', notes = "'.$this->cleanInStr($pArr['notes']).'" '.
				'WHERE (omenid = '.$pArr['omenid'].') AND (occid = '.$pArr['occid'].')';
			$con->query($sql);
			$con->close();
		}
	}

	public function deleteOccLink($omenid, $occid){
		if($omenid && $occid && is_numeric($omenid) && is_numeric($occid)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'DELETE FROM omexsiccatiocclink WHERE (omenid = '.$omenid.') AND (occid = '.$occid.')';
			$con->query($sql);
			$con->close();
		}
	}

	public function getCollArr($exsOnly){
		$retArr = array();
		$sql ='SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode '.
			'FROM omcollections c ';
		if($exsOnly){
			$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
				'INNER JOIN omexsiccatiocclink ol ON o.occid = ol.occid ';
		}
		$sql .= 'ORDER BY c.collectionname, c.institutioncode';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $this->cleanOutStr($r->collectionname).' ('.$r->institutioncode.($r->collectioncode?' - '.$r->collectioncode:'').')';
		}
		$rs->close();
		return $retArr;
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