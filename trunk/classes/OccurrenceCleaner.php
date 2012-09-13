<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceCleaner {

	private $conn;
	private $collId;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
		}
	}

	public function getCollMap(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT c.institutioncode, c.collectioncode, c.collectionname, '.
				'c.icon, c.colltype, c.managementtype '.
				'FROM omcollections c '.
				'WHERE (c.collid = '.$this->collId.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['collectioncode'] = $row->collectioncode;
				$returnArr['collectionname'] = $row->collectionname;
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function getDuplicateRecords(){
		$retArr = array();
		$sql = 'SELECT o.occid, o.catalognumber, o.dbpk, o.basisOfRecord, o.otherCatalogNumbers, o.ownerInstitutionCode, o.family, '.
			'o.sciname, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, o.scientificNameAuthorship, '.
			'o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, o.identificationRemarks, '.
			'o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, o.associatedCollectors, '.
			'o.eventDate, o.Year, o.Month, o.Day, o.startDayOfYear, o.endDayOfYear, o.verbatimEventDate, o.habitat, o.substrate, '.
			'o.fieldNotes, o.occurrenceRemarks, o.informationWithheld, o.associatedOccurrences, o.associatedTaxa, o.dynamicProperties, '.
			'o.verbatimAttributes, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, o.stateProvince, '.
			'o.county, o.municipality, o.locality, o.localitySecurity, o.localitySecurityReason, o.decimalLatitude, o.decimalLongitude, '.
			'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
			'o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, '.
			'o.georeferenceVerificationStatus, o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, '.
			'o.verbatimElevation, o.disposition, o.modified, o.recordEnteredBy '. 
			'FROM omoccurrences o INNER JOIN (SELECT catalognumber FROM omoccurrences GROUP BY catalognumber, collid '. 
			'HAVING Count(*)>1 AND collid = '.$this->collId.' AND catalognumber IS NOT NULL) rt ON o.catalognumber = rt.catalognumber '.
			'WHERE o.collid = '.$this->collId.' ORDER BY o.catalognumber LIMIT 230';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$recCnt = 0;
		$fieldArr = array();
		while($r = $rs->fetch_assoc()){
			$catalognumber = $r['catalognumber'];
			if($recCnt > 200 && !array_key_exists($catalognumber,$retArr)) break; 
			$occid = $r['occid'];
			foreach($r as $k =>$v){
				if($recCnt == 1) $fieldArr[$k] = '';
				if($v && $k != 'occid' && $k != 'catalognumber'){
					$retArr[$catalognumber][$occid][$k] = $v;
					$fieldArr[$k] = $k;
				}
			} 
			$recCnt++;
		}
		$rs->close();
		$retArr['fields'] = $fieldArr;
		return $retArr;
	}
	
	public function mergeDupeArr($occidArr){
		$dupArr = array();
		foreach($occidArr as $v){
			$vArr = explode(':',$v);
			$dupArr[$vArr[0]][] = $vArr[1];
		}
		foreach($dupArr as $catNum => $occArr){
			if(count($occArr) > 1){
				$targetOccid = array_shift($occArr);
				foreach($occArr as $sourceOccid){
					$this->mergeRecords($targetOccid,$sourceOccid);
				}
			}
		}
	}
	
	public function mergeRecords($targetOccid,$sourceOccid){
		if(!$targetOccid || !$sourceOccid) return 'ERROR: target or source is null';
		if($targetOccid == $sourceOccid) return 'ERROR: target and source are equal';
		$status = true;

		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$tArr = $oArr[$targetOccid];
		$sArr = $oArr[$sourceOccid];
		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.$v.'"';
			} 
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			$this->conn->query($sqlIns);
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete occurrence edits
		$sql = 'DELETE FROM omoccuredits WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap survey lists
		$sql = 'UPDATE omsurveyoccurlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete source
		$sql = 'DELETE FROM omoccurrences WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$status .= 'ERROR: unable to delete source occurrence (yet may have merged records): '.$this->conn->error;
		}
		return $status;
	}
		
}

?>