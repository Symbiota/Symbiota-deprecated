<?php
class DwcArchiverOccurrence{

	public static function getOccurrenceArr($schemaType, $extended){
		$occurFieldArr['id'] = 'o.occid';
		$occurTermArr['institutionCode'] = 'http://rs.tdwg.org/dwc/terms/institutionCode';
		$occurFieldArr['institutionCode'] = 'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode';
		$occurTermArr['collectionCode'] = 'http://rs.tdwg.org/dwc/terms/collectionCode';
		$occurFieldArr['collectionCode'] = 'IFNULL(o.collectionCode,c.collectionCode) AS collectionCode';
		$occurTermArr['ownerInstitutionCode'] = 'http://rs.tdwg.org/dwc/terms/ownerInstitutionCode';
		$occurFieldArr['ownerInstitutionCode'] = 'o.ownerInstitutionCode';
		$occurTermArr['collectionID'] = 'http://rs.tdwg.org/dwc/terms/collectionID';
		$occurFieldArr['collectionID'] = 'IFNULL(o.collectionID, c.collectionguid) AS collectionID';
		$occurTermArr['basisOfRecord'] = 'http://rs.tdwg.org/dwc/terms/basisOfRecord';
		$occurFieldArr['basisOfRecord'] = 'o.basisOfRecord';
		$occurTermArr['occurrenceID'] = 'http://rs.tdwg.org/dwc/terms/occurrenceID';
		$occurFieldArr['occurrenceID'] = 'o.occurrenceID';
		$occurTermArr['catalogNumber'] = 'http://rs.tdwg.org/dwc/terms/catalogNumber';
		$occurFieldArr['catalogNumber'] = 'o.catalogNumber';
		$occurTermArr['otherCatalogNumbers'] = 'http://rs.tdwg.org/dwc/terms/otherCatalogNumbers';
		$occurFieldArr['otherCatalogNumbers'] = 'o.otherCatalogNumbers';
		$occurTermArr['kingdom'] = 'http://rs.tdwg.org/dwc/terms/kingdom';
		$occurFieldArr['kingdom'] = '';
		$occurTermArr['phylum'] = 'http://rs.tdwg.org/dwc/terms/phylum';
		$occurFieldArr['phylum'] = '';
		$occurTermArr['class'] = 'http://rs.tdwg.org/dwc/terms/class';
		$occurFieldArr['class'] = '';
		$occurTermArr['order'] = 'http://rs.tdwg.org/dwc/terms/order';
		$occurFieldArr['order'] = '';
		$occurTermArr['family'] = 'http://rs.tdwg.org/dwc/terms/family';
		$occurFieldArr['family'] = 'o.family';
		$occurTermArr['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$occurFieldArr['scientificName'] = 'o.sciname AS scientificName';
		//$occurTermArr['verbatimScientificName'] = 'http://symbiota.org/terms/verbatimScientificName';
		//$occurFieldArr['verbatimScientificName'] = 'o.scientificname AS verbatimScientificName';
		$occurTermArr['tidInterpreted'] = 'http://symbiota.org/terms/tidInterpreted';
		$occurFieldArr['tidInterpreted'] = 'o.tidinterpreted';
		$occurTermArr['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
		$occurFieldArr['scientificNameAuthorship'] = 'IFNULL(t.author,o.scientificNameAuthorship) AS scientificNameAuthorship';
		$occurTermArr['genus'] = 'http://rs.tdwg.org/dwc/terms/genus';
		$occurFieldArr['genus'] = 'IF(t.rankid >= 180,CONCAT_WS(" ",t.unitind1,t.unitname1),NULL) AS genus';
		$occurTermArr['specificEpithet'] = 'http://rs.tdwg.org/dwc/terms/specificEpithet';
		$occurFieldArr['specificEpithet'] = 'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet';
		$occurTermArr['taxonRank'] = 'http://rs.tdwg.org/dwc/terms/taxonRank';
		$occurFieldArr['taxonRank'] = 't.unitind3 AS taxonRank';
		$occurTermArr['infraspecificEpithet'] = 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet';
		$occurFieldArr['infraspecificEpithet'] = 't.unitname3 AS infraspecificEpithet';
 		$occurTermArr['identifiedBy'] = 'http://rs.tdwg.org/dwc/terms/identifiedBy';
 		$occurFieldArr['identifiedBy'] = 'o.identifiedBy';
 		$occurTermArr['dateIdentified'] = 'http://rs.tdwg.org/dwc/terms/dateIdentified';
 		$occurFieldArr['dateIdentified'] = 'o.dateIdentified';
 		$occurTermArr['identificationReferences'] = 'http://rs.tdwg.org/dwc/terms/identificationReferences';
 		$occurFieldArr['identificationReferences'] = 'o.identificationReferences';
 		$occurTermArr['identificationRemarks'] = 'http://rs.tdwg.org/dwc/terms/identificationRemarks';
 		$occurFieldArr['identificationRemarks'] = 'o.identificationRemarks';
 		$occurTermArr['taxonRemarks'] = 'http://rs.tdwg.org/dwc/terms/taxonRemarks';
 		$occurFieldArr['taxonRemarks'] = 'o.taxonRemarks';
 		$occurTermArr['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
 		$occurFieldArr['identificationQualifier'] = 'o.identificationQualifier';
		$occurTermArr['typeStatus'] = 'http://rs.tdwg.org/dwc/terms/typeStatus';
		$occurFieldArr['typeStatus'] = 'o.typeStatus';
		$occurTermArr['recordedBy'] = 'http://rs.tdwg.org/dwc/terms/recordedBy';
		$occurFieldArr['recordedBy'] = 'o.recordedBy';
		$occurTermArr['recordedByID'] = 'http://symbiota.org/terms/recordedByID';
		$occurFieldArr['recordedByID'] = 'o.recordedById';
		$occurTermArr['associatedCollectors'] = 'http://symbiota.org/terms/associatedCollectors'; 
		$occurFieldArr['associatedCollectors'] = 'o.associatedCollectors'; 
		$occurTermArr['recordNumber'] = 'http://rs.tdwg.org/dwc/terms/recordNumber';
		$occurFieldArr['recordNumber'] = 'o.recordNumber';
		$occurTermArr['eventDate'] = 'http://rs.tdwg.org/dwc/terms/eventDate';
		$occurFieldArr['eventDate'] = 'o.eventDate';
		$occurTermArr['year'] = 'http://rs.tdwg.org/dwc/terms/year';
		$occurFieldArr['year'] = 'o.year';
		$occurTermArr['month'] = 'http://rs.tdwg.org/dwc/terms/month';
		$occurFieldArr['month'] = 'o.month';
		$occurTermArr['day'] = 'http://rs.tdwg.org/dwc/terms/day';
		$occurFieldArr['day'] = 'o.day';
		$occurTermArr['startDayOfYear'] = 'http://rs.tdwg.org/dwc/terms/startDayOfYear';
		$occurFieldArr['startDayOfYear'] = 'o.startDayOfYear';
		$occurTermArr['endDayOfYear'] = 'http://rs.tdwg.org/dwc/terms/endDayOfYear';
		$occurFieldArr['endDayOfYear'] = 'o.endDayOfYear';
		$occurTermArr['verbatimEventDate'] = 'http://rs.tdwg.org/dwc/terms/verbatimEventDate';
		$occurFieldArr['verbatimEventDate'] = 'o.verbatimEventDate';
		$occurTermArr['occurrenceRemarks'] = 'http://rs.tdwg.org/dwc/terms/occurrenceRemarks';
		$occurTermArr['habitat'] = 'http://rs.tdwg.org/dwc/terms/habitat';
		$occurFieldArr['occurrenceRemarks'] = 'o.occurrenceRemarks';
		$occurFieldArr['habitat'] = 'o.habitat';
		$occurTermArr['substrate'] = 'http://symbiota.org/terms/substrate';
		$occurFieldArr['substrate'] = 'o.substrate';
		$occurTermArr['verbatimAttributes'] = 'http://symbiota.org/terms/verbatimAttributes';
		$occurFieldArr['verbatimAttributes'] = 'o.verbatimAttributes';
		$occurTermArr['fieldNumber'] = 'http://rs.tdwg.org/dwc/terms/fieldNumber';
		$occurFieldArr['fieldNumber'] = 'o.fieldNumber';
		$occurTermArr['informationWithheld'] = 'http://rs.tdwg.org/dwc/terms/informationWithheld';
		$occurFieldArr['informationWithheld'] = 'o.informationWithheld';
		$occurTermArr['dataGeneralizations'] = 'http://rs.tdwg.org/dwc/terms/dataGeneralizations';
		$occurFieldArr['dataGeneralizations'] = 'o.dataGeneralizations';
		$occurTermArr['dynamicProperties'] = 'http://rs.tdwg.org/dwc/terms/dynamicProperties';
		$occurFieldArr['dynamicProperties'] = 'o.dynamicProperties';
		$occurTermArr['associatedTaxa'] = 'http://rs.tdwg.org/dwc/terms/associatedTaxa';
		$occurFieldArr['associatedTaxa'] = 'o.associatedTaxa';
		$occurTermArr['reproductiveCondition'] = 'http://rs.tdwg.org/dwc/terms/reproductiveCondition';
		$occurFieldArr['reproductiveCondition'] = 'o.reproductiveCondition';
		$occurTermArr['establishmentMeans'] = 'http://rs.tdwg.org/dwc/terms/establishmentMeans';
		$occurFieldArr['establishmentMeans'] = 'o.establishmentMeans';
		$occurTermArr['cultivationStatus'] = 'http://symbiota.org/terms/cultivationStatus';
		$occurFieldArr['cultivationStatus'] = 'cultivationStatus';
		$occurTermArr['lifeStage'] = 'http://rs.tdwg.org/dwc/terms/lifeStage';
		$occurFieldArr['lifeStage'] = 'o.lifeStage';
		$occurTermArr['sex'] = 'http://rs.tdwg.org/dwc/terms/sex';
		$occurFieldArr['sex'] = 'o.sex';
		$occurTermArr['individualCount'] = 'http://rs.tdwg.org/dwc/terms/individualCount';
		$occurFieldArr['individualCount'] = 'CASE WHEN o.individualCount REGEXP("(^[0-9]+$)") THEN o.individualCount ELSE NULL END AS individualCount';
		$occurTermArr['samplingProtocol'] = 'http://rs.tdwg.org/dwc/terms/samplingProtocol';
		$occurFieldArr['samplingProtocol'] = 'o.samplingProtocol';
		$occurTermArr['samplingEffort'] = 'http://rs.tdwg.org/dwc/terms/samplingEffort';
		$occurFieldArr['samplingEffort'] = 'o.samplingEffort';
		$occurTermArr['preparations'] = 'http://rs.tdwg.org/dwc/terms/preparations';
		$occurFieldArr['preparations'] = 'o.preparations';
		$occurTermArr['country'] = 'http://rs.tdwg.org/dwc/terms/country';
		$occurFieldArr['country'] = 'o.country';
		$occurTermArr['stateProvince'] = 'http://rs.tdwg.org/dwc/terms/stateProvince';
		$occurFieldArr['stateProvince'] = 'o.stateProvince';
		$occurTermArr['county'] = 'http://rs.tdwg.org/dwc/terms/county';
		$occurFieldArr['county'] = 'o.county';
		$occurTermArr['municipality'] = 'http://rs.tdwg.org/dwc/terms/municipality';
		$occurFieldArr['municipality'] = 'o.municipality';
		$occurTermArr['locality'] = 'http://rs.tdwg.org/dwc/terms/locality';
		$occurFieldArr['locality'] = 'o.locality';
		$occurTermArr['locationRemarks'] = 'http://rs.tdwg.org/dwc/terms/locationRemarks';
		$occurFieldArr['locationRemarks'] = 'o.locationremarks';
		$occurTermArr['localitySecurity'] = 'http://symbiota.org/terms/localitySecurity';
		$occurFieldArr['localitySecurity'] = 'o.localitySecurity';
		$occurTermArr['localitySecurityReason'] = 'http://symbiota.org/terms/localitySecurityReason';
		$occurFieldArr['localitySecurityReason'] = 'o.localitySecurityReason';
		$occurTermArr['decimalLatitude'] = 'http://rs.tdwg.org/dwc/terms/decimalLatitude';
		$occurFieldArr['decimalLatitude'] = 'o.decimalLatitude';
		$occurTermArr['decimalLongitude'] = 'http://rs.tdwg.org/dwc/terms/decimalLongitude';
		$occurFieldArr['decimalLongitude'] = 'o.decimalLongitude';
		$occurTermArr['geodeticDatum'] = 'http://rs.tdwg.org/dwc/terms/geodeticDatum';
		$occurFieldArr['geodeticDatum'] = 'o.geodeticDatum';
		$occurTermArr['coordinateUncertaintyInMeters'] = 'http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters';
		$occurFieldArr['coordinateUncertaintyInMeters'] = 'o.coordinateUncertaintyInMeters';
		//$occurTermArr['footprintWKT'] = 'http://rs.tdwg.org/dwc/terms/footprintWKT';
		//$occurFieldArr['footprintWKT'] = 'o.footprintWKT';
		$occurTermArr['verbatimCoordinates'] = 'http://rs.tdwg.org/dwc/terms/verbatimCoordinates';
		$occurFieldArr['verbatimCoordinates'] = 'o.verbatimCoordinates';
		$occurTermArr['georeferencedBy'] = 'http://rs.tdwg.org/dwc/terms/georeferencedBy';
		$occurFieldArr['georeferencedBy'] = 'o.georeferencedBy';
		$occurTermArr['georeferenceProtocol'] = 'http://rs.tdwg.org/dwc/terms/georeferenceProtocol';
		$occurFieldArr['georeferenceProtocol'] = 'o.georeferenceProtocol';
		$occurTermArr['georeferenceSources'] = 'http://rs.tdwg.org/dwc/terms/georeferenceSources';
		$occurFieldArr['georeferenceSources'] = 'o.georeferenceSources';
		$occurTermArr['georeferenceVerificationStatus'] = 'http://rs.tdwg.org/dwc/terms/georeferenceVerificationStatus';
		$occurFieldArr['georeferenceVerificationStatus'] = 'o.georeferenceVerificationStatus';
		$occurTermArr['georeferenceRemarks'] = 'http://rs.tdwg.org/dwc/terms/georeferenceRemarks';
		$occurFieldArr['georeferenceRemarks'] = 'o.georeferenceRemarks';
		$occurTermArr['minimumElevationInMeters'] = 'http://rs.tdwg.org/dwc/terms/minimumElevationInMeters';
		$occurFieldArr['minimumElevationInMeters'] = 'o.minimumElevationInMeters';
		$occurTermArr['maximumElevationInMeters'] = 'http://rs.tdwg.org/dwc/terms/maximumElevationInMeters';
		$occurFieldArr['maximumElevationInMeters'] = 'o.maximumElevationInMeters';
		$occurTermArr['minimumDepthInMeters'] = 'http://rs.tdwg.org/dwc/terms/minimumDepthInMeters';
		$occurFieldArr['minimumDepthInMeters'] = 'o.minimumDepthInMeters';
		$occurTermArr['maximumDepthInMeters'] = 'http://rs.tdwg.org/dwc/terms/maximumDepthInMeters';
		$occurFieldArr['maximumDepthInMeters'] = 'o.maximumDepthInMeters';
		$occurTermArr['verbatimDepth'] = 'http://rs.tdwg.org/dwc/terms/verbatimDepth';
		$occurFieldArr['verbatimDepth'] = 'o.verbatimDepth';
		$occurTermArr['verbatimElevation'] = 'http://rs.tdwg.org/dwc/terms/verbatimElevation';
		$occurFieldArr['verbatimElevation'] = 'o.verbatimElevation';
		$occurTermArr['disposition'] = 'http://rs.tdwg.org/dwc/terms/disposition';
		$occurFieldArr['disposition'] = 'o.disposition';
		$occurTermArr['language'] = 'http://purl.org/dc/terms/language';
		$occurFieldArr['language'] = 'o.language';
		$occurTermArr['genericcolumn1'] = 'http://symbiota.org/terms/genericcolumn1';
		$occurFieldArr['genericcolumn1'] = 'o.genericcolumn1';
		$occurTermArr['genericcolumn2'] = 'http://symbiota.org/terms/genericcolumn2';
		$occurFieldArr['genericcolumn2'] = 'o.genericcolumn2';
		$occurTermArr['storageLocation'] = 'http://symbiota.org/terms/storageLocation';
		$occurFieldArr['storageLocation'] = 'o.storageLocation';
		$occurTermArr['observerUid'] = 'http://symbiota.org/terms/observerUid';
		$occurFieldArr['observerUid'] = 'o.observeruid';
		$occurTermArr['processingStatus'] = 'http://symbiota.org/terms/processingStatus';
		$occurFieldArr['processingStatus'] = 'o.processingstatus';
		$occurTermArr['duplicateQuantity'] = 'http://symbiota.org/terms/duplicateQuantity';
		$occurFieldArr['duplicateQuantity'] = 'o.duplicateQuantity';
		$occurTermArr['recordEnteredBy'] = 'http://symbiota.org/terms/recordEnteredBy';
		$occurFieldArr['recordEnteredBy'] = 'o.recordEnteredBy';
		$occurTermArr['dateEntered'] = 'http://symbiota.org/terms/dateEntered';
		$occurFieldArr['dateEntered'] = 'o.dateEntered';
		$occurTermArr['dateLastModified'] = 'http://rs.tdwg.org/dwc/terms/dateLastModified';
		$occurFieldArr['dateLastModified'] = 'o.datelastmodified';
		$occurTermArr['modified'] = 'http://purl.org/dc/terms/modified';
		$occurFieldArr['modified'] = 'IFNULL(o.modified,o.datelastmodified) AS modified';
		$occurTermArr['rights'] = 'http://purl.org/dc/elements/1.1/rights';
		$occurFieldArr['rights'] = 'c.rights';
		$occurTermArr['rightsHolder'] = 'http://purl.org/dc/terms/rightsHolder';
		$occurFieldArr['rightsHolder'] = 'c.rightsHolder';
		$occurTermArr['accessRights'] = 'http://purl.org/dc/terms/accessRights';
		$occurFieldArr['accessRights'] = 'c.accessRights';
		$occurTermArr['sourcePrimaryKey-dbpk'] = 'http://symbiota.org/terms/sourcePrimaryKey-dbpk';
		$occurFieldArr['sourcePrimaryKey-dbpk'] = 'o.dbpk'; 
		$occurTermArr['collId'] = 'http://symbiota.org/terms/collId'; 
		$occurFieldArr['collId'] = 'c.collid'; 
		$occurTermArr['recordId'] = 'http://portal.idigbio.org/terms/recordId';
		$occurFieldArr['recordId'] = 'g.guid AS recordId';
		$occurTermArr['references'] = 'http://purl.org/dc/terms/references';
		$occurFieldArr['references'] = '';

		$occurrenceFieldArr['terms'] = self::trimOccurrenceBySchemaType($occurTermArr, $schemaType, $extended);
		$occurFieldArr = self::trimOccurrenceBySchemaType($occurFieldArr, $schemaType, $extended);
		if($schemaType == 'dwc'){
			$occurFieldArr['recordedBy'] = 'CONCAT_WS("; ",o.recordedBy,o.associatedCollectors) AS recordedBy';
			$occurFieldArr['occurrenceRemarks'] = 'CONCAT_WS("; ",o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks';
			$occurFieldArr['habitat'] = 'CONCAT_WS("; ",o.habitat, o.substrate) AS habitat';
		}
		$occurrenceFieldArr['fields'] = $occurFieldArr;
		return $occurrenceFieldArr;
	}

	private static function trimOccurrenceBySchemaType($occurArr, $schemaType, $extended){
		$retArr = array();
		if($schemaType == 'dwc'){
			$trimArr = array('tidInterpreted','recordedByID','associatedCollectors','substrate','verbatimAttributes','cultivationStatus',
				'localitySecurityReason','genericcolumn1','genericcolumn2','storageLocation','observerUid','processingStatus',
				'duplicateQuantity','dateEntered','dateLastModified','sourcePrimaryKey-dbpk');
			$retArr = array_diff_key($occurArr,array_flip($trimArr));
		}
		elseif($schemaType == 'symbiota'){
			$trimArr = array();
			if(!$extended){
				$trimArr = array('collectionID','rights','rightsHolder','accessRights','tidInterpreted','genericcolumn1','genericcolumn2',
					'storageLocation','observerUid','processingStatus','duplicateQuantity','dateEntered','dateLastModified'); 
			}
			$retArr = array_diff_key($occurArr,array_flip($trimArr));
		}
		elseif($schemaType == 'backup'){
			$trimArr = array('collectionID','rights','rightsHolder','accessRights'); 
			$retArr = array_diff_key($occurArr,array_flip($trimArr));
		}
		elseif($schemaType == 'coge'){
			$targetArr = array('id','basisOfRecord','institutionCode','collectionCode','catalogNumber','occurrenceID','family','scientificName','scientificNameAuthorship',
				'kingdom','phylum','class','order','genus','specificEpithet','infraSpecificEpithet',
				'recordedBy','recordNumber','eventDate','year','month','day','fieldNumber','country','stateProvince','county','municipality',
				'locality','localitySecurity','geodeticDatum','decimalLatitude','decimalLongitude','verbatimCoordinates',
				'minimumElevationInMeters','maximumElevationInMeters','verbatimElevation','maximumDepthInMeters','minimumDepthInMeters',
				'sex','occurrenceRemarks','preparationType','individualCount','dateEntered','dateLastModified','recordId','references','collId');
			$retArr = array_intersect_key($occurArr,array_flip($targetArr));
		}
		return $retArr;
	}
	
	public static function getSqlOccurrences($fieldArr, $conditionSql, $tableJoinStr, $fullSql = true){
		$sql = '';
		if($conditionSql){
			if($fullSql){
				$sqlFrag = '';
				foreach($fieldArr as $fieldName => $colName){
					if($colName){
						$sqlFrag .= ', '.$colName;
					}
					else{
						$sqlFrag .= ', "" AS t_'.$fieldName;
					}
				}
				$sql = 'SELECT DISTINCT '.trim($sqlFrag,', ');
			}
			$sql .= ' FROM (omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid) '.
				'INNER JOIN guidoccurrences g ON o.occid = g.occid '.
				'LEFT JOIN taxa t ON o.tidinterpreted = t.TID ';
			$sql .= $tableJoinStr.$conditionSql;
			if($fullSql) $sql .= ' ORDER BY o.collid'; 
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}
}
?>