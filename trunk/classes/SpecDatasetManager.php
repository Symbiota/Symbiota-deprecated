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
			'o.family, o.sciname, o.genus, o.specificepithet, o.taxonrank, o.infraspecificepithet, '.
			'o.scientificnameauthorship, o.taxonremarks, o.identifiedby, o.dateidentified, o.identificationreferences, '.
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

	public function queryOccurrences(){
		$retArr = array();
		if($this->collId){
			$sqlWhere = '';
			$sqlOrderBy = '';
			if($_POST['labelproject']){
				$sqlWhere .= 'AND (labelproject = "'.trim($_POST['labelproject']).'") ';
			}
			if($_POST['recordenteredby']){
				$sqlWhere .= 'AND (recordenteredby LIKE "'.trim($_POST['recordenteredby']).'%") ';
			}
			if($_POST['datelastmodified']){
				if($p = strpos($_POST['datelastmodified'],' - ')){
					$sqlWhere .= 'AND (DATE(datelastmodified) BETWEEN "'.trim(substr($_POST['datelastmodified'],0,$p)).'" AND "'.trim(substr($_POST['datelastmodified'],$p+3)).'") ';
				}
				else{
					$sqlWhere .= 'AND (DATE(datelastmodified) = "'.trim($_POST['datelastmodified']).'") ';
				}
				
				$sqlOrderBy .= ',datelastmodified';
			}
			$rnIsNum = false;
			if($_POST['recordnumber']){
				$rnArr = explode(',',$_POST['recordnumber']);
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
			if($_POST['recordedby']){
				$sqlWhere .= 'AND (recordedby LIKE "%'.trim($_POST['recordedby']).'%") ';
				$sqlOrderBy .= ',(recordnumber'.($rnIsNum?'+1':'').')';
			}
			if($_POST['identifier']){
				$iArr = explode(',',$_POST['identifier']);
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
					'FROM omoccurrences '.($_POST['recordedby']?'use index(Index_collector) ':'').
					'WHERE collid = '.$this->collId.' '.$sqlWhere;
				if($sqlOrderBy) $sql .= 'ORDER BY '.substr($sqlOrderBy,1);
				$sql .= ' LIMIT 100';
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

	public function getLabelRecordSet(){
		$rs;
		if($occidArr = $_POST['occid']){
			$sql = $this->getLabelSql();
			//echo 'SQL: '.$sql;
			$rs = $this->conn->query($sql);
		}
		return $rs;
	}
	
	public function exportCsvFile(){
		$sql = $this->getLabelSql();
		//echo 'SQL: '.$sql;
		if($sql){
	    	$fileName = 'labeloutput_'.time().".csv";
			header ('Content-Type: text/csv');
			header ("Content-Disposition: attachment; filename=\"$fileName\""); 
			
			$rs = $this->conn->query($sql);
			if($rs){
				echo "\"occid\",\"catalogNumber\",\"family\",\"scientificName\",\"genus\",\"specificEpithet\",".
				"\"taxonRank\",\"infraspecificEpithet\",\"scientificNameAuthorship\",\"taxonRemarks\",\"identifiedBy\",".
				"\"dateIdentified\",\"identificationReferences\",\"identificationRemarks\",\"identificationQualifier\",".
	 			"\"recordedBy\",\"recordNumber\",\"associatedCollectors\",\"eventDate\",\"year\",\"month\",\"monthName\",\"day\",".
		 		"\"verbatimEventDate\",\"habitat\",\"substrate\",\"verbatimAttributes\",\"occurrenceRemarks\",".
	 			"\"associatedTaxa\",\"reproductiveCondition\",\"establishmentMeans\",\"country\",".
	 			"\"stateProvince\",\"county\",\"municipality\",\"locality\",\"decimalLatitude\",\"decimalLongitude\",".
		 		"\"geodeticDatum\",\"coordinateUncertaintyInMeters\",\"verbatimCoordinates\",".
	 			"\"minimumElevationInMeters\",\"maximumElevationInMeters\",\"verbatimElevation\",\"disposition\"\n";
				
				while($row = $rs->fetch_assoc()){
					$dupCnt = $_POST['q-'.$row['occid']];
					for($i = 0;$i < $dupCnt;$i++){
						echo $row['occid'].",\"".$row["catalognumber"]."\",\"".
							$row["family"]."\","."\"".$row["sciname"]."\",\"".$row["genus"]."\",\"".$row["specificepithet"]."\",\"".
							$row["taxonrank"]."\",\"".$row["infraspecificepithet"]."\",\"".$row["scientificnameauthorship"]."\",\"".
							$row["taxonremarks"]."\",\"".$row["identifiedby"]."\",\"".$row["dateidentified"]."\",\"".$row["identificationreferences"]."\",\"".
							$row["identificationremarks"]."\",\"".$row["identificationqualifier"]."\",\"".$row["recordedby"]."\",\"".$row["recordnumber"]."\",\"".
							$row["associatedcollectors"]."\",\"".$row["eventdate"]."\",".$row["year"].",".$row["month"].",".$row["monthname"].",".$row["day"].",\"".
							$row["verbatimeventdate"]."\",\"".$row["habitat"]."\",\"".$row["substrate"]."\",\"".
							$row["verbatimattributes"]."\",\"".
							$row["occurrenceremarks"]."\",\"".$row["associatedtaxa"]."\",\"".$row["reproductivecondition"]."\",\"".
							$row["establishmentmeans"]."\",\"".$row["country"]."\",\"".$row["stateprovince"]."\",\"".
							$row["county"]."\",\"".$row["municipality"]."\",\"".$row["locality"]."\",".$row["decimallatitude"].",".
							$row["decimallongitude"].",\"".$row["geodeticdatum"]."\",".$row["coordinateuncertaintyinmeters"].",\"".
							$row["verbatimcoordinates"]."\",".$row["minimumelevationinmeters"].",".$row["maximumelevationinmeters"].",\"".
							$row["verbatimelevation"]."\",\"".$row["disposition"]."\"\n";
					}
				}
			}
			else{
				echo "Recordset is empty.\n";
			}
	        if($rs) $rs->close();
		}
	}
	
	private function getLabelSql(){
		$sql = '';
		if($occidArr = $_POST['occid']){
			$sql = $this->occSql.' WHERE occid IN('.implode(',',$occidArr).')';
			//echo 'SQL: '.$sql;
		}
		return $sql;
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