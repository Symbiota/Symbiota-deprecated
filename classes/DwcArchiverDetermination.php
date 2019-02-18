<?php
class DwcArchiverDetermination{

	public static function getDeterminationArr($schemaType,$extended){
		$fieldArr['coreid'] = 'o.occid';
		$termArr['identifiedBy'] = 'http://rs.tdwg.org/dwc/terms/identifiedBy';
		$fieldArr['identifiedBy'] = 'd.identifiedBy';
		$termArr['identifiedByID'] = 'http://symbiota.org/terms/identifiedByID';
		$fieldArr['identifiedByID'] = 'd.idbyid';
		$termArr['dateIdentified'] = 'http://rs.tdwg.org/dwc/terms/dateIdentified';
		$fieldArr['dateIdentified'] = 'd.dateIdentified';
		$termArr['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
		$fieldArr['identificationQualifier'] = 'd.identificationQualifier';
		$termArr['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$fieldArr['scientificName'] = 'd.sciName AS scientificName';
		$termArr['tidInterpreted'] = 'http://symbiota.org/terms/tidInterpreted';
		$fieldArr['tidInterpreted'] = 'd.tidinterpreted';
		$termArr['identificationIsCurrent'] = 'http://symbiota.org/terms/identificationIsCurrent';
		$fieldArr['identificationIsCurrent'] = 'd.iscurrent';
		$termArr['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
		$fieldArr['scientificNameAuthorship'] = 'd.scientificNameAuthorship';
		$termArr['genus'] = 'http://rs.tdwg.org/dwc/terms/genus';
		$fieldArr['genus'] = 'CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus';
		$termArr['specificEpithet'] = 'http://rs.tdwg.org/dwc/terms/specificEpithet';
		$fieldArr['specificEpithet'] = 'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet';
		$termArr['taxonRank'] = 'http://rs.tdwg.org/dwc/terms/taxonRank';
		$fieldArr['taxonRank'] = 't.unitind3 AS taxonRank';
		$termArr['infraspecificEpithet'] = 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet';
		$fieldArr['infraspecificEpithet'] = 't.unitname3 AS infraspecificEpithet';
		$termArr['identificationReferences'] = 'http://rs.tdwg.org/dwc/terms/identificationReferences';
		$fieldArr['identificationReferences'] = 'd.identificationReferences';
		$termArr['identificationRemarks'] = 'http://rs.tdwg.org/dwc/terms/identificationRemarks';
		$fieldArr['identificationRemarks'] = 'd.identificationRemarks';
		$termArr['recordId'] = 'http://portal.idigbio.org/terms/recordId';
		$fieldArr['recordId'] = 'g.guid AS recordId';
		$termArr['modified'] = 'http://purl.org/dc/terms/modified';
		$fieldArr['modified'] = 'd.initialTimeStamp AS modified';

		$retArr['terms'] = self::trimBySchemaType($termArr,$schemaType,$extended);
		$retArr['fields'] = self::trimBySchemaType($fieldArr,$schemaType,$extended);
		return $retArr;
	}
	
	private static function trimBySchemaType($detArr,$schemaType,$extended){
		$trimArr = array();
		if($schemaType == 'dwc'){
			$trimArr = array('identifiedByID');
			$trimArr = array('tidInterpreted');
			$trimArr = array('identificationIsCurrent');
		}
		elseif($schemaType == 'symbiota'){
			if(!$extended){
				$trimArr = array('identifiedByID');
				$trimArr = array('tidInterpreted');
			}
		}
		elseif($schemaType == 'backup'){
			$trimArr = array(); 
		}
		elseif($schemaType == 'coge'){
			$trimArr = array(); 
		}
		return array_diff_key($detArr,array_flip($trimArr));
	}

	public static function getSqlDeterminations($fieldArr,$conditionSql){
		$sql = ''; 
		if($fieldArr && $conditionSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid '.
				'INNER JOIN guidoccurdeterminations g ON d.detid = g.detid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid '.
				'LEFT JOIN taxa t ON d.tidinterpreted = t.tid ';
			if(strpos($conditionSql,'v.clid')){
				//Search criteria came from custom search page
				$sql .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
			}
			if(strpos($conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			if(strpos($conditionSql,'MATCH(f.recordedby)') || strpos($conditionSql,'MATCH(f.locality)')){
				$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			}
			if(stripos($conditionSql,'a.stateid')){
				//Search is limited by occurrence attribute
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid ';
			}
			elseif(stripos($conditionSql,'s.traitid')){
				//Search is limited by occurrence trait
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid '.
					'INNER JOIN tmstates s ON a.stateid = s.stateid ';
			}
			$sql .= $conditionSql.'AND d.appliedstatus = 1 '.
				'ORDER BY o.collid';
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}
}
?>