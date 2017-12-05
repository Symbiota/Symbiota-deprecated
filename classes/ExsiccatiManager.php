<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

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
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getTitleArr($searchTerm = '', $specimenOnly = 0, $imagesOnly = 0, $collId = 0, $sortBy = 0){
		$retArr = array();
		$sql = 'SELECT DISTINCT et.ometid, et.title, et.editor, et.exsrange, et.abbreviation ';
		$sqlWhere = '';
		if($specimenOnly){
			if($imagesOnly){
				$sql .= 'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
						'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
						'INNER JOIN images i ON ol.occid = i.occid ';
			}
			else{
				//Display only exsiccati that have linked specimens
				$sql .= 'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
						'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid ';
			}
			if($collId){
				$sql .= 'INNER JOIN omoccurrences o ON ol.occid = o.occid ';
				$sqlWhere = 'WHERE o.collid = '.$collId.' ';
			}
		}
		else{
			//Display full list
			$sql .= 'FROM omexsiccatititles et ';
		}
		if($searchTerm){
			$sqlWhere .= ($sqlWhere?'AND ':'WHERE ').'et.title LIKE "%'.$searchTerm.'%" OR et.abbreviation LIKE "%'.$searchTerm.'%" OR et.editor LIKE "%'.$searchTerm.'%" ';
		}
		$sql .= $sqlWhere.'ORDER BY '.($sortBy?"IFNULL(et.abbreviation,et.title)":"et.title").', et.startdate';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if($sortBy == 1) {
					if($r->abbreviation) $titleStr = (strlen($r->abbreviation)>100?substr($r->abbreviation,0,100).'...':$r->abbreviation);
					else {
						$titleStr = (strlen($r->title)>100?substr($r->title,0,100).'...':$r->title);
						$titleStr .= ', '.(strlen($r->editor)>50?substr($r->editor,0,50).'...':$r->editor);
					}
				} else {
					$titleStr = (strlen($r->title)>100?substr($r->title,0,100).'...':$r->title);
					if($r->editor) $titleStr .= ', '.(strlen($r->editor)>50?substr($r->editor,0,50).'...':$r->editor);
				}
				if($r->exsrange) $titleStr .= ' ['.$r->exsrange.']';
				$retArr[$r->ometid] = $this->cleanOutStr($titleStr);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getExsNumberArr($ometid,$specimenOnly,$imagesOnly,$collId){
		$retArr = array();
		if($ometid){
			//Grab all numbers for that exsiccati title; only show number that have occid links
			$sql = 'SELECT DISTINCT en.omenid, en.exsnumber, en.notes, o.sciname, '.
				'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),") ",IFNULL(o.eventDate,"date unknown")) as collector '.
				'FROM omexsiccatinumbers en '.($specimenOnly || $imagesOnly?'INNER':'LEFT').' JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
				($specimenOnly || $imagesOnly?'INNER':'LEFT').' JOIN omoccurrences o ON ol.occid = o.occid ';
			if($imagesOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			$sql .= 'WHERE en.ometid = '.$ometid.' ';
			if($collId) $sql .= 'AND o.collid = '.$collId.' ';
			$sql .= 'ORDER BY en.exsnumber+1,en.exsnumber,ol.ranking';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					if(!array_key_exists($r->omenid,$retArr)){
						$retArr[$r->omenid]['number'] = $this->cleanOutStr($r->exsnumber);
						$retArr[$r->omenid]['collector'] = $r->collector;
						$retArr[$r->omenid]['sciname'] = $r->sciname;
						$retArr[$r->omenid]['notes'] = $this->cleanOutStr($r->notes);
					}
				}
				$rs->free();
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
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getExsOccArr($id, $target = 'omenid'){
		$retArr = array();
		$sql = 'SELECT en.omenid, en.exsnumber, ol.ranking, ol.notes, o.occid, o.occurrenceid, o.catalognumber, '.
			'c.collid, c.collectionname, CONCAT_WS("-",c.institutioncode,c.collectioncode) AS collcode, '.
			'o.sciname, o.scientificnameauthorship, o.recordedby, o.recordnumber, DATE_FORMAT(o.eventdate,"%d %M %Y") AS eventdate, '.
			'trim(o.country) AS country, trim(o.stateprovince) AS stateprovince, trim(o.county) AS county, '.
			'trim(o.municipality) AS municipality, o.locality, o.decimallatitude, o.decimallongitude, '.
			'i.imgid, i.thumbnailurl, i.url '.
			'FROM omexsiccatiocclink ol INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'INNER JOIN omexsiccatinumbers en ON ol.omenid = en.omenid '.
			'LEFT JOIN images i ON o.occid = i.occid ';
		if($target == 'omenid'){
			$sql .= 'WHERE ol.omenid = '.$id;
		}
		else{
			$sql .= 'WHERE en.ometid = '.$id;
		}
		$sql .= ' ORDER BY en.exsnumber+1, ol.ranking, o.recordedby, o.recordnumber';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(!isset($retArr[$r->omenid][$r->occid])){
					$retArr[$r->omenid][$r->occid]['exsnum'] = $this->cleanOutStr($r->exsnumber);
					$retArr[$r->omenid][$r->occid]['ranking'] = $this->cleanOutStr($r->ranking);
					$retArr[$r->omenid][$r->occid]['notes'] = $this->cleanOutStr($r->notes);
					$retArr[$r->omenid][$r->occid]['collid'] = $r->collid;
					$retArr[$r->omenid][$r->occid]['collname'] = $this->cleanOutStr($r->collectionname);
					$retArr[$r->omenid][$r->occid]['collcode'] = $this->cleanOutStr($r->collcode);
					$retArr[$r->omenid][$r->occid]['occurrenceid'] = $r->occurrenceid;
					$retArr[$r->omenid][$r->occid]['catalognumber'] = $r->catalognumber;
					$retArr[$r->omenid][$r->occid]['sciname'] = $this->cleanOutStr($r->sciname);
					$retArr[$r->omenid][$r->occid]['author'] = $this->cleanOutStr($r->scientificnameauthorship);
					$retArr[$r->omenid][$r->occid]['recby'] = $this->cleanOutStr($r->recordedby);
					$retArr[$r->omenid][$r->occid]['recnum'] = $this->cleanOutStr($r->recordnumber);
					$retArr[$r->omenid][$r->occid]['eventdate'] = $r->eventdate;
					$retArr[$r->omenid][$r->occid]['country'] = $r->country;
					$retArr[$r->omenid][$r->occid]['state'] = $r->stateprovince;
					$retArr[$r->omenid][$r->occid]['county'] = $r->county;
					$retArr[$r->omenid][$r->occid]['locality'] = $this->cleanOutStr(($r->municipality?$r->municipality.'; ':'').$r->locality);
					$retArr[$r->omenid][$r->occid]['lat'] = $r->decimallatitude;
					$retArr[$r->omenid][$r->occid]['lng'] = $r->decimallongitude;
				}
				if($r->url){
					$retArr[$r->omenid][$r->occid]['img'][$r->imgid]['url'] = $r->url;
					$retArr[$r->omenid][$r->occid]['img'][$r->imgid]['tnurl'] = ($r->thumbnailurl?$r->thumbnailurl:$r->url);
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function exportExsiccatiAsCsv($searchTerm, $specimenOnly, $imagesOnly, $collId){
		$fieldArr = array('et.ometid' => 'titleID', 'et.title' => 'exsiccatiTitle', 'et.abbreviation' => 'abbreviation', 'et.editor' => 'editors', 'et.exsrange' => 'range',
			'et.startdate' => 'startDate', 'et.enddate' => 'endDate', 'et.source' => 'source', 'et.notes' => 'titleNotes', 'en.exsnumber' => 'exsiccatiNumber');
		$fileName = 'exsiccatiOutput_'.time().'.csv';
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$sqlInsert = '';
		if($collId || $specimenOnly){
			$sqlInsert .= 'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
				'INNER JOIN omoccurrences o ON ol.occid = o.occid ';
			if($imagesOnly) $sqlInsert .= 'INNER JOIN images i ON o.occid = i.occid ';
			if($collId) $sqlInsert .= 'WHERE o.collid = '.$collId.' ';
			$fieldArr['o.occid'] = 'occid';
			$fieldArr['o.catalognumber'] = 'catalogNumber';
			$fieldArr['o.othercatalognumbers'] = 'otherCatalogNumbers';
			$fieldArr['o.dbpk'] = 'sourceIdentifier_dbpk';
			$fieldArr['o.recordedby'] = 'collector';
			$fieldArr['o.recordnumber'] = 'collectorNumber';
			$fieldArr['ol.notes'] = 'occurrenceNotes';
		}
		if($searchTerm){
			$sqlInsert .= ($sqlInsert?'AND ':'WHERE ').'et.title LIKE "%'.$searchTerm.'%" OR et.abbreviation LIKE "%'.$searchTerm.'%" OR et.editor LIKE "%'.$searchTerm.'%" ';
		}
		$sql = 'SELECT '.implode(',',array_keys($fieldArr)).' '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			$sqlInsert.'ORDER BY et.title, et.startdate';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$out = fopen('php://output', 'w');
			fputcsv($out, $fieldArr);
			while($r = $rs->fetch_assoc()){
				fputcsv($out, $r);
			}
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
		$rs->free();
	}

	//Exsiccati edit functions
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
			', startdate = '.($pArr['startdate']?'"'.$this->cleanInStr($pArr['startdate']).'"':'NULL').
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
			if(!$con->query($sql)){
				$retStr = 'DELETE Failed: possibly due to existing exsiccati numbers, which first have to be deleted.';
			}
			$con->close();
		}
		return $retStr;
	}

	public function mergeTitles($ometid,$targetOmetid){
		$retStr = '';
		if($ometid && is_numeric($ometid) && $targetOmetid && is_numeric($targetOmetid)){
			$con = MySQLiConnectionFactory::getCon("write");
			//Transfer omexsiccatinumbers that can be transferred (e.g. exsnumbers not yet existing for target exsiccati titles)
			$sql = 'UPDATE IGNORE omexsiccatinumbers '.
				'SET ometid = '.$targetOmetid.' '.
				'WHERE ometid = '.$ometid;
			//echo $sql;
			$con->query($sql);

			//Remap omexsiccatiocclink that are still linked to old exsiccati title
			$sql = 'UPDATE IGNORE omexsiccatiocclink o INNER JOIN omexsiccatinumbers n1 ON o.omenid = n1.omenid '.
				'INNER JOIN omexsiccatinumbers n2 ON n1.exsnumber = n2.exsnumber '.
				'SET o.omenid = n2.omenid '.
				'WHERE n1.ometid = '.$ometid.' AND n2.ometid = '.$targetOmetid;
			//echo $sql;
			$con->query($sql);

			//DELETE omexsiccatinumbers for old ometids with no linked occurrences
			$sql = 'DELETE n.* FROM omexsiccatinumbers n LEFT JOIN omexsiccatiocclink o ON n.omenid = o.omenid '.
				'WHERE o.omenid IS NULL AND n.ometid = '.$ometid;
			//echo $sql;
			if(!$con->query($sql)){
				$retStr = 'ERROR deleting omexsiccatinumbers: '.$con->error;
			}

			//DELETE omexsiccatinumbers for old ometids with no linked occurrences
			$sql = 'DELETE FROM omexsiccatititles '.
				'WHERE ometid = '.$ometid;
			//echo $sql;
			if(!$con->query($sql)){
				$retStr = 'ERROR deleting omexsiccatititles: '.$con->error;
			}

			$con->close();
		}
		return $retStr;
	}

	public function addNumber($pArr){
		$retStr = '';
		$con = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber,notes) '.
			'VALUES('.$pArr['ometid'].',"'.$this->cleanInStr($pArr['exsnumber']).'",'.
			($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
		//echo $sql;
		if(!$con->query($sql)){
			$retStr = 'ERROR adding exsiccati number: '.$con->error;
		}
		$con->close();
		return $retStr;
	}

	public function editNumber($pArr){
		$retStr = '';
		if($pArr['omenid'] && is_numeric($pArr['omenid'])){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omexsiccatinumbers '.
				'SET exsnumber = "'.$this->cleanInStr($pArr['exsnumber']).'",'.
				'notes = '.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').' '.
				'WHERE (omenid = '.$pArr['omenid'].')';
			if(!$con->query($sql)){
				$retStr = 'ERROR editing exsiccati number: '.$con->error;
			}
			$con->close();
		}
		return $retStr;
	}

	public function deleteNumber($omenid){
		$retStr = '';
		if($omenid && is_numeric($omenid)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'DELETE FROM omexsiccatinumbers WHERE (omenid = '.$omenid.')';
			if(!$con->query($sql)){
				$retStr = 'ERROR deleting exsiccati number: possibly due to linked occurrences reocrds. Delete all occurrence records and then you should be able to delete this number.';
			}
			$con->close();
		}
		return $retStr;
	}

	public function transferNumber($omenid,$targetOmetid){
		$retStr = '';
		if($omenid && is_numeric($omenid) && $targetOmetid && is_numeric($targetOmetid)){
			$con = MySQLiConnectionFactory::getCon("write");

			//Check to see if a matching omexsiccatinumbers exists
			$sql = 'SELECT n1.omenid '.
				'FROM omexsiccatinumbers n1 INNER JOIN omexsiccatinumbers n2 ON n1.exsnumber = n2.exsnumber '.
				'WHERE n1.ometid = '.$targetOmetid.' AND n2.omenid = '.$omenid;
			//echo $sql;
			$rs = $con->query($sql);
			if($r = $rs->fetch_object()){
				//Remap omexsiccatiocclink to existing omexsiccatinumbers
				$sql1 = 'UPDATE IGNORE omexsiccatiocclink '.
					'SET omenid = '.$r->omenid.' '.
					'WHERE omenid = '.$omenid;
				//echo $sql;
				$con->query($sql1);

				//DELETE omexsiccatinumbers for old omenid, given that there are no linked occurrences
				$sql2 = 'DELETE n.* FROM omexsiccatinumbers n LEFT JOIN omexsiccatiocclink o ON n.omenid = o.omenid '.
					'WHERE o.omenid IS NULL AND n.omenid = '.$omenid;
				//echo $sql;
				if(!$con->query($sql2)){
					$retStr = 'ERROR deleting omexsiccatinumber: '.$con->error;
				}
			}
			else{
				//Transfer omexsiccatinumber
				$sql1 = 'UPDATE omexsiccatinumbers '.
					'SET ometid = '.$targetOmetid.' '.
					'WHERE omenid = '.$omenid;
				//echo $sql;
				if(!$con->query($sql1)){
					$retStr = 'ERROR transferring omexsiccatinumber: '.$con->error;
				}
			}

			$con->close();
		}
		return $retStr;
	}

	public function addOccLink($pArr){
		$retStr = '';
		$collId = $pArr['occaddcollid'];
		if($collId && $pArr['omenid'] && is_numeric($pArr['omenid'])){
			$con = MySQLiConnectionFactory::getCon("write");
			$ranking = 10;
			if($pArr['ranking'] && is_numeric($pArr['ranking'])) $ranking = $pArr['ranking'];
			$identifier = $pArr['identifier'];
			if($collId == 'occid' && $identifier && is_numeric($identifier)){
				//occid being supplied within identifier field (catalog number field)
				$sql = 'INSERT INTO omexsiccatiocclink(omenid,occid,ranking,notes) '.
					'VALUES ('.$pArr['omenid'].','.$identifier.','.$ranking.','.
					($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
				if(!$con->query($sql)){
					$retStr = 'ERROR linking occurrence to exsiccati number, SQL: '.$sql;
				}
			}
			elseif($collId && is_numeric($collId) && ($identifier || ($pArr['recordedby'] && $pArr['recordnumber']))){
				//Grab matching occid(s)
				$sql1 = 'SELECT o.occid '.
					'FROM omoccurrences o LEFT JOIN omexsiccatiocclink l ON o.occid = l.occid '.
					'LEFT JOIN omoccurrencesfulltext f ON o.occid = f.occid '.
					'WHERE o.collid = '.$collId.' AND l.occid IS NULL ';
				if($identifier){
					$sql1 .= 'AND (o.catalogNumber = '.(is_numeric($identifier)?$identifier:'"'.$identifier.'"').') ';
				}
				else{
					if(strlen($pArr['recordedby']) < 4 || strtolower($pArr['recordedby']) == 'best'){
						//Need to avoid FULLTEXT stopwords interfering with return
						$sql1 .= 'AND (o.recordedby LIKE "%'.$pArr['recordedby'].'%")';
					}
					else{
						$sql1 .= 'AND (MATCH(f.recordedby) AGAINST("'.$pArr['recordedby'].'")) ';
					}
					$sql1 .= 'AND (o.recordnumber = '.(is_numeric($pArr['recordnumber'])?$pArr['recordnumber']:'"'.$pArr['recordnumber'].'"').') ';
				}
				$sql1 .= 'LIMIT 5';
				//echo $sql1;
				$rs = $con->query($sql1);
				$cnt = 0;
				while($r = $rs->fetch_object()){
					$sql = 'INSERT INTO omexsiccatiocclink(omenid,occid,ranking,notes) '.
						'VALUES('.$pArr['omenid'].', '.$r->occid.', '.$ranking.','.
						($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
					//echo $sql;
					if(!$con->query($sql)){
						$retStr = 'ERROR linking occurrence to exsiccati number, SQL: '.$sql;
					}
					$cnt++;
				}
				$rs->free();
				if($cnt){
					$retStr = 'SUCCESS: '.$cnt.' recorded loaded successfully';
				}
				else{
					$retStr = 'FAILED: no records located matching criteria';
				}
			}
			$con->close();
		}
		else{
			$retStr = 'FAILED: criteria may have not been complete';
		}
		return $retStr;
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

	public function transferOccurrence($omenid, $occid, $targetOmetid, $targetExsNumber){
		$statusStr = '';
		if($omenid && is_numeric($omenid) && $targetOmetid && is_numeric($targetOmetid) && $targetExsNumber){
			$con = MySQLiConnectionFactory::getCon("write");
			//Lookup omenid
			$targetOmenid = 0;
			$sql = 'SELECT omenid FROM omexsiccatinumbers '.
				'WHERE ometid = '.$targetOmetid.' AND exsnumber = "'.$this->cleanInStr($targetExsNumber).'"';
			$rs = $con->query($sql);
			if($r = $rs->fetch_object()){
				$targetOmenid = $r->omenid;
			}
			else{
				//Create new omexsiccatinumber record and the transfer
				$sql1 = 'INSERT INTO omexsiccatinumbers(ometid, exsnumber) '.
					'VALUES('.$targetOmetid.',"'.$this->cleanInStr($targetExsNumber).'") ';
				if($con->query($sql1)){
					$targetOmenid = $con->insert_id;
				}
				else{
					$statusStr = 'ERROR inserting new omexsiccatinumbers record, SQL: '.$sql1;
				}
			}
			$rs->free();
			if($targetOmenid){
				//Transfer record
				$sql2 = 'UPDATE omexsiccatiocclink SET omenid = '.$targetOmenid.' WHERE occid = '.$occid.' AND omenid = '.$omenid;
				if(!$con->query($sql2)){
					$statusStr = 'ERROR tranferring occurrence: '.$con->error;
				}
			}
			else{
				$statusStr = 'ERROR looking up omenid while trying to transfer occurrence';
			}

			$con->close();
		}
		return $statusStr;
	}

	//Batch transfer functions
	public function batchImport($targetCollid,$postArr){
		$statusStr = '';
		$transferCnt = 0;
		if(array_key_exists('occid[]',$postArr)){
			$con = MySQLiConnectionFactory::getCon("write");
			$datasetId = '';
			if(array_key_exists('dataset',$postArr) && $postArr['dataset']){
				//Create new dataset to link all new records
				$sqlDs = 'INSERT INTO omoccurdatasets(name, uid) VALUES("'.$this->cleanInStr($postArr['dataset']).'",'.$GLOBALS['SYMB_UID'].') ';
				if($con->query($sqlDs)){
					$datasetId = $con->insert_id;
				}
				else{
					$statusStr = 'ERROR creating dataset, '.$con->error;
					//$statusStr .= '<br/>SQL: '.$sqlDs;
				}
			}
			//Transfer records
			$occidArr = $postArr['occid[]'];
			$targetFieldArr = $this->getTargetFields();
			$sqlBase = 'INSERT INTO omoccurrences('.implode(',',$targetFieldArr).',collid, catalognumber, dateEntered) '.
				'SELECT '.implode(',',$targetFieldArr).',';
			foreach($occidArr as $occid){
				if(is_numeric($occid)){
					$catNum = $this->cleanInStr($postArr['cat-'.$occid]);
					$sql1 = $targetCollid.', "'.$catNum.'", "'.date('Y-m-d H:i:s').'" AS dateEntered '.
						'FROM omoccurrences WHERE occid = '.$occid;
					if($con->query($sql1)){
						$transferCnt++;
						//Add new record to exsiccati index
						$newOccid = $con->insert_id;
						if($newOccid){
							$sql2 = 'INSERT INTO omexsiccatiocclink(omenid,occid) '.
								'SELECT omenid, occid FROM omexsiccatiocclink WHERE occid = '.$newOccid;
							if(!$con->query($sql2)){
								$statusStr = 'ERROR linking new record to exsiccati: '.$con->error;
								//$statusStr .= '<br/>SQL: '.$sql2;
							}
						}
						if($datasetId){
							//Add to dataset
							$sql3 = 'INSERT INTO omoccurdatasetlink(occid,datasetid) VALUES('.$newOccid.','.$datasetId.') ';
							if(!$con->query($sql3)){
								$statusStr = 'ERROR add new record to dataset: '.$con->error;
								//$statusStr .= '<br/>SQL: '.$sql3;
							}
						}
					}
					else{
						$statusStr .= '<b/>ERROR transferring record #'.$occid.': '.$con->error;
						//$statusStr .= '<br/>SQL: '.$sql1;
					}
				}
			}
			$con->close();
		}
		if($transferCnt){
			$statusStr = 'SUCCESS transferring '.$transferCnt.' records ';
			//if($datasetId) $statusStr = '<br/>Records linked to dataset: <a href="">'.$datasetTitle.'</a>';
		}
		return $statusStr;
	}

	public function exportAsCsv($postArr){
		if(array_key_exists('occid[]',$postArr)){
			$fieldArr = $this->getTargetFields();
			$occidArr = array_flip($postArr['occid[]']);
			$fileName = 'exsiccatiOutput_'.time().'.csv';
			header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header ('Content-Type: text/csv');
			header ('Content-Disposition: attachment; filename="'.$fileName.'"');
			$sql = 'SELECT '.implode(',',$fieldArr).', occid FROM omoccurrences WHERE occid IN('.implode(',',$occidArr).') ';
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				$out = fopen('php://output', 'w');
				array_unshift($fieldArr,'catalogNumber');
				array_push($fieldArr,'occid');
				echo implode(',',$fieldArr)."\n";
				while($r = $rs->fetch_assoc()){
					array_unshift($r,$occidArr[$r->occid]);
					fputcsv($out, $r);
				}
				fclose($out);
			}
			else{
				echo "Recordset is empty.\n";
			}
			$rs->free();
		}
	}

	private function getTargetFields(){
		$fieldArr = array();
		$skipFields = array('occid','collid','dbpk','ownerinstitutioncode','institutionid','collectionid','datasetid','institutioncode','collectioncode',
			'occurrenceid', 'catalognumber', 'othercatalognumbers','previousidentifications', 'taxonremarks', 'identifiedby', 'dateidentified',
			'identificationreferences', 'identificationremarks', 'recordedbyid', 'informationwithheld', 'associatedoccurrences', 'datageneralizations',
			'dynamicproperties', 'verbatimcoordinatesystem', 'storagelocation', 'disposition', 'genericcolumn1', 'genericcolumn2', 'modified',
			'observeruid', 'processingstatus', 'recordenteredby', 'duplicatequantity', 'labelproject', 'dateentered', 'datelastmodified',
			'initialtimestamp');
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$field = strtolower($r->Field);
			if(!in_array($field, $skipFields)){
				$fieldArr[] = $field;
			}
		}
		$rs->free();
		return $fieldArr;
	}

	//Misc
	public function getCollArr($ometid = 0){
		$retArr = array();
		$sql ='SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode '.
			'FROM omcollections c ';
		if($ometid){
			if($ometid == 'all'){
				$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'INNER JOIN omexsiccatiocclink ol ON o.occid = ol.occid ';
			}
			elseif(is_numeric($ometid)){
				$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'INNER JOIN omexsiccatiocclink ol ON o.occid = ol.occid '.
					'INNER JOIN omexsiccatinumbers en ON ol.omenid = en.omenid ';
			}
		}
		$sql .= 'WHERE (colltype != "General Observations") ';
		if($ometid && is_numeric($ometid)){
			$sql .= 'AND (en.ometid = '.$ometid.') ';
		}
		$sql .= 'ORDER BY c.collectionname, c.institutioncode';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->institutioncode.($r->collectioncode?' - '.$r->collectioncode:'').')';
		}
		$rs->free();
		return $retArr;
	}

	public function getTargetCollArr(){
		$retArr = array();
		$collArr = array();
		if(isset($GLOBALS['CollAdmin'])){
			$collArr = $GLOBALS['CollAdmin'];
		}
		if(isset($GLOBALS['CollEditor'])){
			$collArr = array_merge($collArr,$GLOBALS['CollEditor']);
		}
		if($collArr){
			$sql ='SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode '.
				'FROM omcollections c '.
				'WHERE (colltype != "Preserved Specimens") '.
				'ORDER BY c.collectionname, c.institutioncode';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->collid] = $r->collectionname.' ('.$r->institutioncode.($r->collectioncode?' - '.$r->collectioncode:'').')';
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getExsTableRow($occid,$oArr,$omenid,$targetCollid){
		$isTarget = false;
		if($targetCollid == $oArr['collid']) $isTarget = true;
		$retStr = '<tr>';
		$retStr .= '<td align="center">';
		$retStr .= '<input id="'.$occid.'" name="occid[]" type="checkbox" value="'.$occid.'" '.($isTarget?'disabled':'').' />';
		$retStr .= '</td>';
		$retStr .= '<td align="center">';
		if($isTarget){
			$retStr .= '<span style="color:red;"><b>Cannot Import</b><br/>Is Target Collection</span>';
		}
		else{
			$retStr .= '<input name="cat-'.$occid.'" type="text" onchange="checkRecord(this,'.$occid.')" />';
		}
		$retStr .= '</td>';
		$retStr .= '<td align="center"><a href="#" onclick="openExsPU('.$omenid.')">#'.$oArr['exsnum'].'</a></td>';
		$retStr .= '<td>';
		$retStr .= '<span '.($isTarget?'style="color:red;"':'').' title="'.$oArr['collname'].'">'.$oArr['collcode'].'</span>, ';
		$retStr .= '<a href="#" onclick="openIndPU('.$occid.')">'.$oArr['recby'].' '.($oArr['recnum']?$oArr['recnum']:'s.n.').'</a>';
		$retStr .= ($oArr['eventdate']?', '.$oArr['eventdate']:'');
		$retStr .= ', <i>'.$oArr['sciname'].'</i> '.$oArr['author'];
		$retStr .= $oArr['country'].', '.$oArr['state'].', '.$oArr['county'].', '.(strlen($oArr['locality'])>75?substr($oArr['locality'],0,75).'...':$oArr['locality']);
		if($oArr['lat']) $retStr .= ', '.$oArr['lat'].' '.$oArr['lng'];
		if($oArr['notes']) $retStr .= ', <b>'.$oArr['notes'].'</b>';
		$retStr .= '</td>';
		$retStr .= '</tr>';
		return $retStr;
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