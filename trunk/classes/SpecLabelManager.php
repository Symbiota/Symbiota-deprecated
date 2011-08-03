<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecLabelManager {

	private $conn;
	private $collId;
	private $collMap = Array();
	private $occurrenceMap = Array();
	private $occSql;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->occSql = 'SELECT o.occid, o.collid, o.occurrenceID, o.catalogNumber, '.
		'o.family, o.sciname, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, '.
		'o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, '.
		'o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
		'o.associatedCollectors, o.eventdate, o.verbatimEventDate, o.habitat, o.occurrenceRemarks, o.associatedTaxa, '.
		'o.verbatimAttributes, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, '.
		'o.stateProvince, o.county, o.locality, o.decimalLatitude, o.decimalLongitude, '.
		'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.verbatimCoordinates, '.
		'o.minimumElevationInMeters, o.maximumElevationInMeters, '.
		'o.verbatimElevation, o.disposition, o.duplicateQuantity, o.dateLastModified '.
		'FROM omoccurrences o ';
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $this->conn->real_escape_string($id);
		}
	}

	public function queryOccurrences(){
		$retArr = array();
		if($this->collId){
			$sqlWhere = '';
			$sqlOrderBy = '';
			if($_POST['labelproject']){
				$sqlWhere .= 'AND (labelproject = "'.$_POST['labelproject'].'") ';
			}
			if($_POST['recordenteredby']){
				$sqlWhere .= 'AND (recordenteredby LIKE "'.$_POST['recordenteredby'].'%") ';
			}
			if($_POST['datelastmodified']){
				if($p = strpos($_POST['datelastmodified'],' - ')){
					$sqlWhere .= 'AND (DATE(datelastmodified) BETWEEN "'.substr($_POST['datelastmodified'],0,$p).'" AND "'.substr($_POST['datelastmodified'],$p+3).'") ';
				}
				else{
					$sqlWhere .= 'AND (DATE(datelastmodified) = "'.$_POST['datelastmodified'].'") ';
				}
				
				$sqlOrderBy .= ',datelastmodified';
			}
			if($_POST['recordedby']){
				$sqlWhere .= 'AND (recordedby LIKE "%'.$_POST['recordedby'].'%") ';
				$sqlOrderBy .= ',recordnumber';
			}
			if($_POST['recordnumber']){
				$rnArr = explode(',',$_POST['recordnumber']);
				$rnBetweenFrag = array();
				$rnInFrag = array();
				foreach($rnArr as $v){
					if($p = strpos($v,' - ')){
						$rnBetweenFrag[] = '(recordnumber BETWEEN "'.substr($v,0,$p).'" AND "'.substr($v,$p+3).'") ';
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
			if($_POST['identifier']){
				$iArr = explode(',',$_POST['identifier']);
				$iBetweenFrag = array();
				$iInFrag = array();
				foreach($iArr as $v){
					if($p = strpos($v,' - ')){
						$iBetweenFrag[] = '(catalogNumber BETWEEN '.substr($v,0,$p).' AND '.substr($v,$p+3).')';
						$iBetweenFrag[] = '(occurrenceId BETWEEN '.substr($v,0,$p).' AND '.substr($v,$p+3).')';
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
					$iWhere .= 'OR (catalogNumber IN("'.implode('","',$iInFrag).'")) OR (occurrenceId IN("'.implode('","',$iInFrag).'")) ';
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
				$sqlOrderBy .= ',catalogNumber,occurrenceId';
			}
			if($sqlWhere){
				$sql = 'SELECT occid, IFNULL(duplicatequantity,1) AS q, CONCAT(recordedby," (",IFNULL(recordnumber,"s.n."),")") AS collector, '.
					'family, sciname, CONCAT_WS("; ",country, stateProvince, county, locality) AS locality '.
					'FROM omoccurrences WHERE '.substr($sqlWhere,4);
				if($sqlOrderBy) $sql .= 'ORDER BY '.substr($sqlOrderBy,1);
				$sql .= ' LIMIT 100';
				//echo '<div>'.$sql.'</div>';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$occId = $r->occid;
					$retArr[$occId]['q'] = $r->q;
					$retArr[$occId]['c'] = $r->collector;
					//$retArr[$occId]['f'] = $r->family;
					$retArr[$occId]['s'] = $r->sciname;
					$retArr[$occId]['l'] = $r->locality;
				}
				$rs->close();
			}
		}
		return $retArr;
	}
	
	protected function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace("\"","'",$newStr);
		return $newStr;
	}
}
?>