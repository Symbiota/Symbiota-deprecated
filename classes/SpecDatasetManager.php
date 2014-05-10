<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecDatasetManager {

	private $conn;
	private $collId;
	private $collName;
	private $collType;
	private $symbUid;
	private $occSql;
	private $isAdmin = 0;

	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->occSql = 'SELECT o.occid, o.collid, o.catalognumber, o.othercatalognumbers, '.
			'o.family, o.sciname AS scientificname, o.genus, o.specificepithet, o.taxonrank, o.infraspecificepithet, '.
			'o.scientificnameauthorship, "" AS parentauthor, o.taxonremarks, o.identifiedby, o.dateidentified, o.identificationreferences, '.
			'o.identificationremarks, o.identificationqualifier, o.typestatus, o.recordedby, o.recordnumber, o.associatedcollectors, '.
			'DATE_FORMAT(o.eventdate,"%e %M %Y") AS eventdate, o.year, o.month, o.day, DATE_FORMAT(o.eventdate,"%M") AS monthname, '.
			'o.verbatimeventdate, o.habitat, o.substrate, o.occurrenceremarks, o.associatedtaxa, o.verbatimattributes, '.
			'o.reproductivecondition, o.cultivationstatus, o.establishmentmeans, o.country, '.
			'o.stateprovince, o.county, o.municipality, o.locality, o.decimallatitude, o.decimallongitude, '.
			'o.geodeticdatum, o.coordinateuncertaintyinmeters, o.verbatimcoordinates, '.
			'o.minimumelevationinmeters, o.maximumelevationinmeters, '.
			'o.verbatimelevation, o.disposition, o.duplicatequantity, o.datelastmodified '.
			'FROM omoccurrences o ';
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function queryOccurrences($postArr){
		$retArr = array();
		if($this->collId){
			$sqlWhere = '';
			$sqlOrderBy = '';
			if($postArr['labelproject']){
				$sqlWhere .= 'AND (labelproject = "'.trim($postArr['labelproject']).'") ';
			}
			if($postArr['recordenteredby']){
				$sqlWhere .= 'AND (recordenteredby LIKE "'.trim($postArr['recordenteredby']).'%") ';
			}
			if($postArr['datelastmodified']){
				if($p = strpos($postArr['datelastmodified'],' - ')){
					$sqlWhere .= 'AND (DATE(datelastmodified) BETWEEN "'.trim(substr($postArr['datelastmodified'],0,$p)).'" AND "'.trim(substr($postArr['datelastmodified'],$p+3)).'") ';
				}
				else{
					$sqlWhere .= 'AND (DATE(datelastmodified) = "'.trim($postArr['datelastmodified']).'") ';
				}
				
				$sqlOrderBy .= ',datelastmodified';
			}
			$rnIsNum = false;
			if($postArr['recordnumber']){
				$rnArr = explode(',',$postArr['recordnumber']);
				$rnBetweenFrag = array();
				$rnInFrag = array();
				foreach($rnArr as $v){
					$v = trim($v);
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$rnIsNum = true;
							$rnBetweenFrag[] = '(recordnumber BETWEEN '.$term1.' AND '.$term2.')';
						}
						else{
							$catTerm = 'recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(recordnumber) = '.strlen($term2); 
							$rnBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$rnInFrag[] = $v;
					}
				}
				$rnWhere = '';
				if($rnBetweenFrag){
					$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
				}
				if($rnInFrag){
					$rnWhere .= 'OR (recordnumber IN("'.implode('","',$rnInFrag).'")) ';
				}
				$sqlWhere .= 'AND ('.substr($rnWhere,3).') ';
			}
			if($postArr['recordedby']){
				$sqlWhere .= 'AND (recordedby LIKE "%'.trim($postArr['recordedby']).'%") ';
				$sqlOrderBy .= ',(recordnumber'.($rnIsNum?'+1':'').')';
			}
			if($postArr['identifier']){
				$iArr = explode(',',$postArr['identifier']);
				$iBetweenFrag = array();
				$iInFrag = array();
				foreach($iArr as $v){
					$v = trim($v);
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$searchIsNum = true; 
							$iBetweenFrag[] = '(catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
						}
						else{
							$catTerm = 'catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(catalogNumber) = '.strlen($term2); 
							$iBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$iInFrag[] = $v;
					}
				}
				$iWhere = '';
				if($iBetweenFrag){
					$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				}
				if($iInFrag){
					$iWhere .= 'OR (catalogNumber IN("'.implode('","',$iInFrag).'")) ';
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
				$sqlOrderBy .= ',catalogNumber';
			}
			if($sqlWhere){
				$sql = 'SELECT occid, IFNULL(duplicatequantity,1) AS q, CONCAT(recordedby," (",IFNULL(recordnumber,"s.n."),")") AS collector, '.
					'family, sciname, CONCAT_WS("; ",country, stateProvince, county, locality) AS locality '.
					'FROM omoccurrences '.($postArr['recordedby']?'use index(Index_collector) ':'').
					'WHERE collid = '.$this->collId.' '.$sqlWhere;
				if($sqlOrderBy) $sql .= 'ORDER BY '.substr($sqlOrderBy,1);
				$sql .= ' LIMIT 500';
				//echo '<div>'.$sql.'</div>';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$occId = $r->occid;
					$retArr[$occId]['q'] = $r->q;
					$retArr[$occId]['c'] = $this->cleanOutStr($r->collector);
					//$retArr[$occId]['f'] = $this->cleanOutStr($r->family);
					$retArr[$occId]['s'] = $this->cleanOutStr($r->sciname);
					$retArr[$occId]['l'] = $this->cleanOutStr($r->locality);
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getLabelArray($occidArr, $speciesAuthors){
		$retArr = array();
		if($occidArr){
			$authorArr = array();
			$sqlWhere = 'WHERE (occid IN('.implode(',',$occidArr).'))';
			//Get species authors for infraspecific taxa
			$sql1 = 'SELECT o.occid, t2.author '.
				'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN taxa t2 ON ts.parenttid = t2.tid '.
				$sqlWhere.' AND t.rankid > 220 AND ts.taxauthid = 1 ';
			if(!$speciesAuthors){
				$sql1 .= 'AND t.unitname2 = t.unitname3 ';
			}
			//if()
			if($rs1 = $this->conn->query($sql1)){
				while($row1 = $rs1->fetch_object()){
					$authorArr[$row1->occid] = $row1->author;
				}
				$rs1->free();
			}
				
			//Get occurrence records
			$sql2 = $this->occSql.' '.$sqlWhere;
			//echo 'SQL: '.$sql;
			if($rs2 = $this->conn->query($sql2)){
				while($row2 = $rs2->fetch_assoc()){
					$row2 = array_change_key_case($row2);
					if(array_key_exists($row2['occid'],$authorArr)){
						$row2['parentauthor'] = $authorArr[$row2['occid']];
					}
					$retArr[$row2['occid']] = $row2;
				}
				$rs2->free();
			}
		}
		return $retArr;
	}
	
	public function exportCsvFile($postArr, $speciesAuthors){
		$occidArr = $postArr['occid'];
		if($occidArr){
	    	$fileName = 'labeloutput_'.time().".csv";
			header ('Content-Type: text/csv');
			header ('Content-Disposition: attachment; filename="'.$fileName.'"'); 
			
			$labelArr = $this->getLabelArray($occidArr, $speciesAuthors);
			if($labelArr){
				$headerArr = array("occid","catalogNumber","family","scientificName","genus","specificEpithet",
					"taxonRank","infraSpecificEpithet","scientificNameAuthorship","parentAuthor","taxonRemarks","identifiedBy",
					"dateIdentified","identificationReferences","identificationRemarks","identificationQualifier",
		 			"recordedBy","recordNumber","associatedCollectors","eventDate","year","month","monthName","day",
			 		"verbatimEventDate","habitat","substrate","verbatimAttributes","occurrenceRemarks",
		 			"associatedTaxa","reproductiveCondition","establishmentMeans","country",
		 			"stateProvince","county","municipality","locality","decimalLatitude","decimalLongitude",
			 		"geodeticDatum","coordinateUncertaintyInMeters","verbatimCoordinates",
		 			"minimumElevationInMeters","maximumElevationInMeters","verbatimElevation","disposition");

				$headerLcArr = array();
				foreach($headerArr as $k => $v){
					$headerLcArr[$k] = strtolower($v);
				}
				foreach($labelArr as $occid => $occArr){
					$dupCnt = $postArr['q-'.$occid];
					for($i = 0;$i < $dupCnt;$i++){
						foreach($headerLcArr as $k => $colName){
							if($k) echo ',';
							echo '"'.str_replace('"','""',$occArr[$colName]).'"';
						}
						echo "\n";
					}
				}
			}
			else{
				echo "Recordset is empty.\n";
			}
		}
	}

	public function getLabelProjects(){
		$retArr = array();
		$sql = 'SELECT DISTINCT labelproject, observeruid FROM omoccurrences '.
			'WHERE labelproject IS NOT NULL AND collid = '.$this->collId.' ';
		if(!$this->isAdmin){
			$sql .= 'AND observeruid = '.$this->symbUid.' ';
		}
		$sql .= 'ORDER BY labelproject';
		$rs = $this->conn->query($sql);
		$altArr = array();
		while($r = $rs->fetch_object()){
			if($this->symbUid == $r->observeruid){
				$retArr[] = $this->cleanOutStr($r->labelproject);
			}
			else{
				$altArr[] = $this->cleanOutStr($r->labelproject);
			}
		}
		$rs->close();
		if($altArr){
			if($retArr) $retArr[] = '------------------';
			$retArr = array_merge($retArr,$altArr);
		}
		return $retArr;
	}

	public function setCollId($collId){
		$this->collId = $collId;
		$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype '.
			'FROM omcollections WHERE collid = '.$collId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->collName = $r->collectionname.' ('.$r->institutioncode.($r->collectioncode?':'.$r->collectioncode:'').')';
				$this->collType = $r->colltype;
			}
			$rs->close();
		}
	}

	public function getCollName(){
		return $this->collName;
	}

	public function getCollType(){
		return $this->collType;
	}

	public function setSymbUid($uid){
		$this->symbUid = $uid;
	}
	
	public function setIsAdmin($isAdmin){
		$this->isAdmin = $isAdmin;
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