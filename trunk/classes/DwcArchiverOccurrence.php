<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/UuidFactory.php');

class DwcArchiverOccurrence{

	private $conn;
	private $ts;
	
	private $collArr;
	private $customWhereSql;
	private $conditionSql;
 	private $conditionArr = array();
	private $condAllowArr;

	private $targetPath;
	
	private $logFH;
	private $verbose = 0;

	private $schemaType = 'dwc';			//dwc, symbiota, backup
	private $delimiter = ',';
	private $fileExt = '.csv';
	private $occurrenceFieldArr = array();
	private $determinationFieldArr = array();
	private $imageFieldArr = array();
	private $securityArr = array();
	private $includeDets = 1;
	private $includeImgs = 1;
	private $redactLocalities = 1;
	private $rareReaderArr = array();
	private $charSetSource = '';
	private $charSetOut = '';
	
	public function __construct(){
		global $serverRoot, $charset;
		
		//Ensure that PHP DOMDocument class is installed
		if(!class_exists('DOMDocument')){
			exit('FATAL ERROR: PHP DOMDocument class is not installed, please contact your server admin');
		}
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		$this->ts = time();
		if(!$this->logFH && $this->verbose){
			$logFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/')."temp/logs/DWCA_".date('Y-m-d').".log";
			$this->logFH = fopen($logFile, 'a');
		}
		
		//Character set
		$this->charSetSource = strtoupper($charset);
		$this->charSetOut = $this->charSetSource;
		
		$this->condAllowArr = array('catalognumber','othercatalognumbers','occurrenceid','family','sciname','scientificname',
			'country','stateprovince','county','recordedby','recordnumber','eventdate','municipality',
			'decimallatitude','decimallongitude','minimumelevationinmeters','maximumelevationinmeters','datelastmodified','modified');
		
		$this->securityArr = array('locality','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
			'decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','footprintWKT',
			'verbatimCoordinates','georeferenceRemarks','georeferencedBy','georeferenceProtocol','georeferenceSources',
			'georeferenceVerificationStatus','habitat','informationWithheld');

		//ini_set('memory_limit','512M');
		set_time_limit(500);
	}

	public function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
		if($this->logFH){
			fclose($this->logFH);
		}
	}

	private function initOccurrenceArr(){
		$occurFieldArr['id'] = 'o.occid';
		$occurTermArr['institutionCode'] = 'http://rs.tdwg.org/dwc/terms/institutionCode';
		$occurFieldArr['institutionCode'] = 'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode';
		$occurTermArr['collectionCode'] = 'http://rs.tdwg.org/dwc/terms/collectionCode';
		$occurFieldArr['collectionCode'] = 'IFNULL(o.collectionCode,c.collectionCode) AS collectionCode';
		if($this->schemaType != 'backup'){
			$occurTermArr['collectionID'] = 'http://rs.tdwg.org/dwc/terms/collectionID';
			$occurFieldArr['collectionID'] = 'IFNULL(o.collectionID,c.collectionGUID) AS collectionID';
		}
		$occurTermArr['basisOfRecord'] = 'http://rs.tdwg.org/dwc/terms/basisOfRecord';
		$occurFieldArr['basisOfRecord'] = 'o.basisOfRecord';
		$occurTermArr['occurrenceID'] = 'http://rs.tdwg.org/dwc/terms/occurrenceID';
		$occurFieldArr['occurrenceID'] = 'o.occurrenceID';
		$occurTermArr['catalogNumber'] = 'http://rs.tdwg.org/dwc/terms/catalogNumber';
		$occurFieldArr['catalogNumber'] = 'o.catalogNumber';
		$occurTermArr['otherCatalogNumbers'] = 'http://rs.tdwg.org/dwc/terms/otherCatalogNumbers';
		$occurFieldArr['otherCatalogNumbers'] = 'o.otherCatalogNumbers';
		$occurTermArr['family'] = 'http://rs.tdwg.org/dwc/terms/family';
		$occurFieldArr['family'] = 'o.family';
		$occurTermArr['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$occurFieldArr['scientificName'] = 't.sciname AS scientificName';
		$occurTermArr['verbatimScientificName'] = 'http://symbiota.org/terms/verbatimScientificName';
		$occurFieldArr['verbatimScientificName'] = 'o.sciname AS verbatimScientificName';
		if($this->schemaType == 'backup'){
			$occurTermArr['tidInterpreted'] = 'http://symbiota.org/terms/tidInterpreted';
			$occurFieldArr['tidInterpreted'] = 'o.tidinterpreted';
		}
		$occurTermArr['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
		$occurFieldArr['scientificNameAuthorship'] = 'IFNULL(t.author,o.scientificNameAuthorship) AS scientificNameAuthorship';
		$occurTermArr['genus'] = 'http://rs.tdwg.org/dwc/terms/genus';
		$occurFieldArr['genus'] = 'CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus';
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
 		$occurTermArr['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
 		$occurFieldArr['identificationQualifier'] = 'o.identificationQualifier';
		$occurTermArr['typeStatus'] = 'http://rs.tdwg.org/dwc/terms/typeStatus';
		$occurFieldArr['typeStatus'] = 'o.typeStatus';
		$occurTermArr['recordedBy'] = 'http://rs.tdwg.org/dwc/terms/recordedBy';
		if($this->schemaType == 'dwc'){
			$occurFieldArr['recordedBy'] = 'CONCAT_WS("; ",o.recordedBy,o.associatedCollectors) AS recordedBy';
		}
		else{
			$occurFieldArr['recordedBy'] = 'o.recordedBy';
			$occurTermArr['recordedByID'] = 'http://symbiota.org/terms/recordedByID';
			$occurFieldArr['recordedByID'] = 'o.recordedById';
			$occurTermArr['associatedCollectors'] = 'http://symbiota.org/terms/associatedCollectors'; 
			$occurFieldArr['associatedCollectors'] = 'o.associatedCollectors'; 
		}
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
		if($this->schemaType == 'dwc'){
			$occurFieldArr['occurrenceRemarks'] = 'CONCAT_WS("; ",o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks';
			$occurFieldArr['habitat'] = 'CONCAT_WS("; ",o.habitat, o.substrate) AS habitat';
		}
		else{
			$occurFieldArr['occurrenceRemarks'] = 'o.occurrenceRemarks';
			$occurFieldArr['habitat'] = 'o.habitat';
			$occurTermArr['substrate'] = 'http://symbiota.org/terms/substrate';
			$occurFieldArr['substrate'] = 'o.substrate';
			$occurTermArr['verbatimAttributes'] = 'http://symbiota.org/terms/verbatimAttributes';
			$occurFieldArr['verbatimAttributes'] = 'o.verbatimAttributes';
		}
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
		if($this->schemaType != 'dwc'){
			$occurTermArr['cultivationStatus'] = 'http://symbiota.org/terms/cultivationStatus';
			$occurFieldArr['cultivationStatus'] = 'cultivationStatus';
		}
		$occurTermArr['lifeStage'] = 'http://rs.tdwg.org/dwc/terms/lifeStage';
		$occurFieldArr['lifeStage'] = 'o.lifeStage';
		$occurTermArr['sex'] = 'http://rs.tdwg.org/dwc/terms/sex';
		$occurFieldArr['sex'] = 'o.sex';
		$occurTermArr['individualCount'] = 'http://rs.tdwg.org/dwc/terms/individualCount';
		$occurFieldArr['individualCount'] = 'o.individualCount';
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
		$occurTermArr['localitySecurity'] = 'http://symbiota.org/terms/localitySecurity';
		$occurFieldArr['localitySecurity'] = 'o.localitySecurity';
		if($this->schemaType != 'dwc'){
			$occurTermArr['localitySecurityReason'] = 'http://symbiota.org/terms/localitySecurityReason';
			$occurFieldArr['localitySecurityReason'] = 'o.localitySecurityReason';
		}
		$occurTermArr['decimalLatitude'] = 'http://rs.tdwg.org/dwc/terms/decimalLatitude';
		$occurFieldArr['decimalLatitude'] = 'o.decimalLatitude';
		$occurTermArr['decimalLongitude'] = 'http://rs.tdwg.org/dwc/terms/decimalLongitude';
		$occurFieldArr['decimalLongitude'] = 'o.decimalLongitude';
		$occurTermArr['geodeticDatum'] = 'http://rs.tdwg.org/dwc/terms/geodeticDatum';
		$occurFieldArr['geodeticDatum'] = 'o.geodeticDatum';
		$occurTermArr['coordinateUncertaintyInMeters'] = 'http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters';
		$occurFieldArr['coordinateUncertaintyInMeters'] = 'o.coordinateUncertaintyInMeters';
		$occurTermArr['footprintWKT'] = 'http://rs.tdwg.org/dwc/terms/footprintWKT';
		$occurFieldArr['footprintWKT'] = 'o.footprintWKT';
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
		$occurTermArr['verbatimElevation'] = 'http://rs.tdwg.org/dwc/terms/verbatimElevation';
		$occurFieldArr['verbatimElevation'] = 'o.verbatimElevation';
		$occurTermArr['disposition'] = 'http://rs.tdwg.org/dwc/terms/disposition';
		$occurFieldArr['disposition'] = 'o.disposition';
		$occurTermArr['language'] = 'http://purl.org/dc/terms/language';
		$occurFieldArr['language'] = 'o.language';
		if($this->schemaType == 'backup'){
			$occurTermArr['observeruID'] = 'http://symbiota.org/terms/observeruID';
			$occurFieldArr['observeruID'] = 'o.observeruid';
			$occurTermArr['processingStatus'] = 'http://symbiota.org/terms/processingStatus';
			$occurFieldArr['processingStatus'] = 'o.processingstatus';
			$occurTermArr['recordEnteredBy'] = 'http://symbiota.org/terms/recordEnteredBy';
			$occurFieldArr['recordEnteredBy'] = 'o.recordEnteredBy';
			$occurTermArr['duplicateQuantity'] = 'http://symbiota.org/terms/duplicateQuantity';
			$occurFieldArr['duplicateQuantity'] = 'o.duplicateQuantity';
			$occurTermArr['labelProject'] = 'http://symbiota.org/terms/labelProject';
			$occurFieldArr['labelProject'] = 'o.labelProject';
			$occurTermArr['dateEntered'] = 'http://symbiota.org/terms/dateEntered';
			$occurFieldArr['dateEntered'] = 'o.dateEntered';
		}
		$occurTermArr['modified'] = 'http://purl.org/dc/terms/modified';
		$occurFieldArr['modified'] = 'IFNULL(o.modified,o.datelastmodified) AS modified';
		if($this->schemaType == 'dwc'){
			//If not DWC, don't output right becasue that data is already in the eml file
	 		$occurTermArr['rights'] = 'http://rs.tdwg.org/dwc/terms/rights';
	 		$occurFieldArr['rights'] = 'c.rights';
			$occurTermArr['rightsHolder'] = 'http://rs.tdwg.org/dwc/terms/rightsHolder';
			$occurFieldArr['rightsHolder'] = 'c.rightsHolder';
			$occurTermArr['accessRights'] = 'http://rs.tdwg.org/dwc/terms/accessRights';
			$occurFieldArr['accessRights'] = 'c.accessRights';
		}
		else{
			$occurTermArr['sourcePrimaryKey'] = 'http://symbiota.org/terms/sourcePrimaryKey'; 
			$occurFieldArr['sourcePrimaryKey'] = 'o.dbpk'; 
		}
		$occurTermArr['collectionId'] = 'http://symbiota.org/terms/collectionId'; 
		$occurFieldArr['collectionId'] = 'c.collid'; 
		$occurTermArr['recordId'] = 'http://portal.idigbio.org/terms/recordId';
		$occurFieldArr['recordId'] = 'g.guid AS recordId';
		$occurTermArr['references'] = 'http://purl.org/dc/terms/references';
		$occurFieldArr['references'] = '';
		$this->occurrenceFieldArr['terms'] = $occurTermArr;
		$this->occurrenceFieldArr['fields'] = $occurFieldArr;
	}

	private function getSqlOccurrences(){
		$sql = '';
		$fieldArr = $this->occurrenceFieldArr['fields'];
		if($fieldArr){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				$sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM (omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid) '.
				'INNER JOIN guidoccurrences g ON o.occid = g.occid '.
				'LEFT JOIN taxa t ON o.tidinterpreted = t.TID ';
			if($this->conditionSql) {
				$sql .= $this->conditionSql;
			}
			$sql .= 'ORDER BY o.collid,o.occid'; 
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}

	private function initDeterminationArr(){
		$detFieldArr['coreid'] = 'o.occid';
		$detTermArr['identifiedBy'] = 'http://rs.tdwg.org/dwc/terms/identifiedBy';
		$detFieldArr['identifiedBy'] = 'd.identifiedBy';
		if($this->schemaType == 'backup'){
			$detTermArr['identifiedByID'] = 'http://symbiota.org/terms/identifiedByID';
			$detFieldArr['identifiedByID'] = 'd.idbyid';
		}
		$detTermArr['dateIdentified'] = 'http://rs.tdwg.org/dwc/terms/dateIdentified';
		$detFieldArr['dateIdentified'] = 'd.dateIdentified';
		$detTermArr['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
		$detFieldArr['identificationQualifier'] = 'd.identificationQualifier';
		$detTermArr['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$detFieldArr['scientificName'] = 'd.sciName AS scientificName';
		if($this->schemaType == 'backup'){
			$detTermArr['tidInterpreted'] = 'http://symbiota.org/terms/tidInterpreted';
			$detFieldArr['tidInterpreted'] = 'd.tidinterpreted';
		}
		if($this->schemaType != 'dwc'){
			$detTermArr['identificationIsCurrent'] = 'http://symbiota.org/terms/identificationIsCurrent';
			$detFieldArr['identificationIsCurrent'] = 'd.iscurrent';
		}
		$detTermArr['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
		$detFieldArr['scientificNameAuthorship'] = 'd.scientificNameAuthorship';
		$detTermArr['genus'] = 'http://rs.tdwg.org/dwc/terms/genus';
		$detFieldArr['genus'] = 'CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus';
		$detTermArr['specificEpithet'] = 'http://rs.tdwg.org/dwc/terms/specificEpithet';
		$detFieldArr['specificEpithet'] = 'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet';
		$detTermArr['taxonRank'] = 'http://rs.tdwg.org/dwc/terms/taxonRank';
		$detFieldArr['taxonRank'] = 't.unitind3 AS taxonRank';
		$detTermArr['infraspecificEpithet'] = 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet';
		$detFieldArr['infraspecificEpithet'] = 't.unitname3 AS infraspecificEpithet';
		$detTermArr['identificationReferences'] = 'http://rs.tdwg.org/dwc/terms/identificationReferences';
		$detFieldArr['identificationReferences'] = 'd.identificationReferences';
		$detTermArr['identificationRemarks'] = 'http://rs.tdwg.org/dwc/terms/identificationRemarks';
		$detFieldArr['identificationRemarks'] = 'd.identificationRemarks';
		$detTermArr['recordId'] = 'http://portal.idigbio.org/terms/recordId';
		$detFieldArr['recordId'] = 'g.guid AS recordId';
		$this->determinationFieldArr['terms'] = $detTermArr;
		$this->determinationFieldArr['fields'] = $detFieldArr;
	}
	
	public function getSqlDeterminations(){
		$sql = ''; 
		$fieldArr = $this->determinationFieldArr['fields'];
		if($fieldArr){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM (omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid) '.
				'INNER JOIN guidoccurdeterminations g ON d.detid = g.detid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid '.
				'LEFT JOIN taxa t ON d.tidinterpreted = t.tid ';
			if($this->conditionSql) {
				$sql .= $this->conditionSql.' AND d.appliedstatus = 1 ';
			}
			else{
				$sql .= 'WHERE d.appliedstatus = 1 ';
			}
			$sql .= 'ORDER BY o.collid,o.occid';
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}

	private function initImageArr(){
		$imgFieldArr['coreid'] = 'o.occid';
		$imgTermArr['accessURI'] = 'http://rs.tdwg.org/ac/terms/accessURI';
		$imgFieldArr['accessURI'] = 'IFNULL(i.originalurl,i.url) as accessURI';
		if($this->schemaType == 'backup'){
			$imgTermArr['thumbnailURI'] = 'http://symbiota.org/terms/thumbnailURI';	
			$imgFieldArr['thumbnailURI'] = 'i.thumbnailurl';
			$imgTermArr['webURI'] = 'http://symbiota.org/terms/webURI';
			$imgFieldArr['webURI'] = 'i.url';
			$imgTermArr['rights'] = 'http://purl.org/dc/terms/rights';	
			$imgFieldArr['rights'] = 'i.copyright';
		}
		else{
			$imgTermArr['Owner'] = 'http://ns.adobe.com/xap/1.0/rights/Owner';	//Institution name
			$imgFieldArr['Owner'] = 'IFNULL(c.rightsholder,CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")")) AS owner';
			$imgTermArr['rights'] = 'http://purl.org/dc/terms/rights';		//Copyright unknown
			$imgFieldArr['rights'] = 'c.rights';
			$imgTermArr['UsageTerms'] = 'http://ns.adobe.com/xap/1.0/rights/UsageTerms';	//Creative Commons BY-SA 3.0 license
			$imgFieldArr['UsageTerms'] = 'i.copyright AS usageterms';
			$imgTermArr['WebStatement'] = 'http://ns.adobe.com/xap/1.0/rights/WebStatement';	//http://creativecommons.org/licenses/by-nc-sa/3.0/us/
			$imgFieldArr['WebStatement'] = 'c.accessrights AS webstatement';
		}
		$imgTermArr['caption'] = 'http://rs.tdwg.org/ac/terms/caption';	
		$imgFieldArr['caption'] = 'i.caption';
		$imgTermArr['comments'] = 'http://rs.tdwg.org/ac/terms/comments';	
		$imgFieldArr['comments'] = 'i.notes';
		$imgTermArr['providerManagedID'] = 'http://rs.tdwg.org/ac/terms/providerManagedID';	//GUID
		$imgFieldArr['providerManagedID'] = 'g.guid AS providermanagedid';
		$imgTermArr['MetadataDate'] = 'http://ns.adobe.com/xap/1.0/MetadataDate';	//timestamp
		$imgFieldArr['MetadataDate'] = 'i.initialtimestamp AS metadatadate';
		$imgTermArr['associatedSpecimenReference'] = 'http://rs.tdwg.org/ac/terms/associatedSpecimenReference';	//reference url in portal
		$imgFieldArr['associatedSpecimenReference'] = '';
		$imgTermArr['type'] = 'http://purl.org/dc/terms/type';		//StillImage
		$imgFieldArr['type'] = '';
		$imgTermArr['subtype'] = 'http://rs.tdwg.org/ac/terms/subtype';		//Photograph
		$imgFieldArr['subtype'] = '';
		$imgTermArr['format'] = 'http://purl.org/dc/terms/format';		//jpg
		$imgFieldArr['format'] = '';
		$imgTermArr['metadataLanguage'] = 'http://rs.tdwg.org/ac/terms/metadataLanguage';	//en
		$imgFieldArr['metadataLanguage'] = '';

		$this->imageFieldArr['terms'] = $imgTermArr;
		$this->imageFieldArr['fields'] = $imgFieldArr;
	}

	public function getSqlImages(){
		$sql = ''; 
		$fieldArr = $this->imageFieldArr['fields'];
		if($fieldArr){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
				'INNER JOIN guidimages g ON i.imgid = g.imgid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid ';
			if($this->redactLocalities){
				if($this->rareReaderArr){
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$this->rareReaderArr).')) ';
				}
				else{
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
				}
			}
			if($this->conditionSql) {
				$sql .= $this->conditionSql;
			}
		}
		//echo $sql;
		return $sql;
	}

	public function setTargetPath($tp = ''){
		if($tp){
			$this->targetPath = $tp;
		}
		else{
			//Set to temp download path
			$tPath = $GLOBALS["tempDirRoot"];
			if(!$tPath){
				$tPath = ini_get('upload_tmp_dir');
			}
			if(!$tPath){
				$tPath = $GLOBALS["serverRoot"];
				if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
					$tPath .= '/';
				}
				$tPath .= "temp/";
			}
			if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
				$tPath .= '/';
			}
			if(file_exists($tPath."downloads")){
				$tPath .= "downloads/";
			}
			$this->targetPath = $tPath;
		}
	}

	private function resetCollArr($collTarget){
		unset($this->collArr);
		$this->collArr = array();
		$this->setCollArr($collTarget);
	}
	
	public function setCollArr($collTarget, $collType = ''){
		$collTarget = $this->cleanInStr($collTarget);
		$collType = $this->cleanInStr($collType);
		$sqlWhere = '';
		if($collType == 'specimens'){
			$sqlWhere = '(c.colltype = "Preserved Specimens") ';
		}
		elseif($collType == 'observations'){
			$sqlWhere = '(c.colltype = "Observations" OR c.colltype = "General Observations") ';
		}
		if($collTarget){
			$sqlWhere .= ($sqlWhere?'AND ':'').'(c.collid IN('.$collTarget.')) ';
		}
		else{
			//Don't limit by collection id 
		}
		if($sqlWhere){
			$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.fulldescription, c.collectionguid, '.
				'IFNULL(c.homepage,i.url) AS url, IFNULL(c.contact,i.contact) AS contact, IFNULL(c.email,i.email) AS email, '.
				'c.guidtarget, c.latitudedecimal, c.longitudedecimal, c.icon, c.colltype, c.rights, c.rightsholder, c.usageterm, '.
				'i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country, i.phone '.
				'FROM omcollections c LEFT JOIN institutions i ON c.iid = i.iid WHERE '.$sqlWhere;
			//echo 'SQL: '.$sql.'<br/>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
				$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
				$this->collArr[$r->collid]['collname'] = htmlspecialchars($r->collectionname);
				$this->collArr[$r->collid]['description'] = htmlspecialchars($r->fulldescription);
				$this->collArr[$r->collid]['collectionguid'] = $r->collectionguid;
				$this->collArr[$r->collid]['url'] = $r->url;
				$this->collArr[$r->collid]['contact'] = htmlspecialchars($r->contact);
				$this->collArr[$r->collid]['email'] = $r->email;
				$this->collArr[$r->collid]['guidtarget'] = $r->guidtarget;
				$this->collArr[$r->collid]['lat'] = $r->latitudedecimal;
				$this->collArr[$r->collid]['lng'] = $r->longitudedecimal;
				$this->collArr[$r->collid]['icon'] = $r->icon;
				$this->collArr[$r->collid]['colltype'] = $r->colltype;
				$this->collArr[$r->collid]['rights'] = $r->rights;
				$this->collArr[$r->collid]['rightsholder'] = $r->rightsholder;
				$this->collArr[$r->collid]['usageterm'] = $r->usageterm;
				$this->collArr[$r->collid]['address1'] = htmlspecialchars($r->address1);
				$this->collArr[$r->collid]['address2'] = htmlspecialchars($r->address2);
				$this->collArr[$r->collid]['city'] = $r->city;
				$this->collArr[$r->collid]['state'] = $r->stateprovince;
				$this->collArr[$r->collid]['postalcode'] = $r->postalcode;
				$this->collArr[$r->collid]['country'] = $r->country;
				$this->collArr[$r->collid]['phone'] = $r->phone;
			}
			$rs->free();
		}
	}

	public function getCollArr(){
		return $this->collArr;
	}

	public function setCustomWhereSql($sql){
		$this->customWhereSql = $sql;
	}
	
	public function addCondition($field, $cond, $value = ''){
		if($field && in_array(strtolower($field),$this->condAllowArr)){
			if(!trim($cond)) $cond = 'EQUALS';
			if($value || ($cond == 'NULL' || $cond == 'NOTNULL')){
				$this->conditionArr[$field][$cond][] = $this->cleanInStr($value);
			}
		}
	}

	private function applyConditions(){
		$sqlFrag = '';
		if($this->conditionArr){
			foreach($this->conditionArr as $field => $condArr){
				$sqlFrag2 = '';
				foreach($condArr as $cond => $valueArr){
					if($cond == 'NULL'){
						$sqlFrag2 .= 'OR '.$field.' IS NULL ';
					}
					elseif($cond == 'NOTNULL'){
						$sqlFrag2 .= 'OR '.$field.' IS NOT NULL ';
					}
					elseif($cond == 'EQUALS'){
						$sqlFrag2 .= 'OR '.$field.' IN("'.implode('","',$valueArr).'") ';
					}
					else{
						foreach($valueArr as $value){
							if($cond == 'STARTS'){
								$sqlFrag2 .= 'OR '.$field.' LIKE "'.$value.'%" ';
							}
							elseif($cond == 'LIKE'){ 
								$sqlFrag2 .= 'OR '.$field.' LIKE "%'.$value.'%" ';
							}
						}
					}
				}
				$sqlFrag .= 'AND ('.substr($sqlFrag2,3).') ';
			}
		}
		//Build where
		$this->conditionSql = '';
		if($this->customWhereSql){
			$this->conditionSql = $this->customWhereSql.' ';
		}
		if($this->collArr && (!$this->conditionSql || !stripos($this->conditionSql,'collid in('))){
			$this->conditionSql .= 'AND (o.collid IN('.implode(',',array_keys($this->collArr)).')) ';
		}
		if($sqlFrag){
			$this->conditionSql .= $sqlFrag;
		}
		if($this->conditionSql){
			//Make sure it starts with WHERE 
			if(substr($this->conditionSql,0,4) == 'AND '){
				$this->conditionSql = 'WHERE'.substr($this->conditionSql,3);
			}
			elseif(substr($this->conditionSql,0,6) != 'WHERE '){
				$this->conditionSql = 'WHERE '.$this->conditionSql;
			}
		}
	}

	public function createDwcArchive($fileNameSeed = ''){
		$status = false;
		if(!$fileNameSeed){
			if(count($this->collArr) == 1){
				$firstColl = current($this->collArr);
				if($firstColl){
					$fileNameSeed = $firstColl['instcode'];
					if($firstColl['collcode']) $fileNameSeed .= '-'.$firstColl['collcode'];
				}
				if($this->schemaType == 'backup'){
					$fileNameSeed .= '_backup_'.$this->ts;
				}
			}
			else{
				$fileNameSeed = 'SymbiotaOutput_'.$this->ts;
			}
		}
		$fileName = str_replace(array(' ','"',"'"),'',$fileNameSeed).'_DwC-A.zip';
		
		$this->applyConditions();
		if(!$this->targetPath) $this->setTargetPath();
		$archiveFile = '';
		$this->logOrEcho('Creating DwC-A file: '.$fileName."\n");
		
		if(!class_exists('ZipArchive')){
			$this->logOrEcho("FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin\n");
			exit('FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin');
		}

		$status = $this->writeOccurrenceFile();
		if($status){
			$archiveFile = $this->targetPath.$fileName;
			if(file_exists($archiveFile)) unlink($archiveFile);
			$zipArchive = new ZipArchive;
			$status = $zipArchive->open($archiveFile, ZipArchive::CREATE);
			if($status !== true){
				exit('FATAL ERROR: unable to create archive file: '.$status);
			}
			//$this->logOrEcho("DWCA created: ".$archiveFile."\n");
			
			//Occurrences
			$zipArchive->addFile($this->targetPath.$this->ts.'-occur'.$this->fileExt);
			$zipArchive->renameName($this->targetPath.$this->ts.'-occur'.$this->fileExt,'occurrences'.$this->fileExt);
			//Determination history
			if($this->includeDets) {
				$this->writeDeterminationFile();
				$zipArchive->addFile($this->targetPath.$this->ts.'-det'.$this->fileExt);
				$zipArchive->renameName($this->targetPath.$this->ts.'-det'.$this->fileExt,'identifications'.$this->fileExt);
			}
			//Images
			if($this->includeImgs){
				$this->writeImageFile();
				$zipArchive->addFile($this->targetPath.$this->ts.'-images'.$this->fileExt);
				$zipArchive->renameName($this->targetPath.$this->ts.'-images'.$this->fileExt,'images'.$this->fileExt);
			}
			//Meta file
			$this->writeMetaFile();
			$zipArchive->addFile($this->targetPath.$this->ts.'-meta.xml');
    		$zipArchive->renameName($this->targetPath.$this->ts.'-meta.xml','meta.xml');
			//EML file
			$this->writeEmlFile();
			$zipArchive->addFile($this->targetPath.$this->ts.'-eml.xml');
    		$zipArchive->renameName($this->targetPath.$this->ts.'-eml.xml','eml.xml');

			$zipArchive->close();
			unlink($this->targetPath.$this->ts.'-occur'.$this->fileExt);
			if($this->includeDets) unlink($this->targetPath.$this->ts.'-det'.$this->fileExt);
			if($this->includeImgs) unlink($this->targetPath.$this->ts.'-images'.$this->fileExt);
			unlink($this->targetPath.$this->ts.'-meta.xml');
			if($this->schemaType == 'dwc'){
				rename($this->targetPath.$this->ts.'-eml.xml',$this->targetPath.str_replace('.zip','.eml',$fileName));
			}
			else{
				unlink($this->targetPath.$this->ts.'-eml.xml');
			}
		}
		else{
			$errStr = "FAILED to create archive file. No records were located in this collection. If records exist, it may be that they don't have Symbiota GUID assignments. Have the portal manager run the GUID mapper (available in sitemap)";
			$this->logOrEcho($errStr);
		}
		
		$this->logOrEcho("\n-----------------------------------------------------\n");
		return $archiveFile;
	}
	
	private function writeMetaFile(){
		$this->logOrEcho("Creating meta.xml (".date('h:i:s A').")... ");
		
		//Create new DOM document 
		$newDoc = new DOMDocument('1.0',$this->charSetOut);

		//Add root element 
		$rootElem = $newDoc->createElement('archive');
		$rootElem->setAttribute('metadata','eml.xml');
		$rootElem->setAttribute('xmlns','http://rs.tdwg.org/dwc/text/');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','http://rs.tdwg.org/dwc/text/   http://rs.tdwg.org/dwc/text/tdwg_dwc_text.xsd');
		$newDoc->appendChild($rootElem);

		//Core file definition
		$coreElem = $newDoc->createElement('core');
		$coreElem->setAttribute('encoding',$this->charSetOut);
		$coreElem->setAttribute('fieldsTerminatedBy',$this->delimiter);
		$coreElem->setAttribute('linesTerminatedBy','\n');
		$coreElem->setAttribute('fieldsEnclosedBy','"');
		$coreElem->setAttribute('ignoreHeaderLines','1');
		$coreElem->setAttribute('rowType','http://rs.tdwg.org/dwc/terms/Occurrence');
		
		$filesElem = $newDoc->createElement('files');
		$filesElem->appendChild($newDoc->createElement('location','occurrences'.$this->fileExt));
		$coreElem->appendChild($filesElem);

		$idElem = $newDoc->createElement('id');
		$idElem->setAttribute('index','0');
		$coreElem->appendChild($idElem);

		$occCnt = 1;
		$termArr = $this->occurrenceFieldArr['terms'];
		if($this->schemaType == 'dwc'){
			unset($termArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($termArr['collectionId']);
		}
		foreach($termArr as $k => $v){
			$fieldElem = $newDoc->createElement('field');
			$fieldElem->setAttribute('index',$occCnt);
			$fieldElem->setAttribute('term',$v);
			$coreElem->appendChild($fieldElem);
			$occCnt++;
		}
		$rootElem->appendChild($coreElem);

		//Identification extension
		$extElem1 = $newDoc->createElement('extension');
		$extElem1->setAttribute('encoding',$this->charSetOut);
		$extElem1->setAttribute('fieldsTerminatedBy',$this->delimiter);
		$extElem1->setAttribute('linesTerminatedBy','\n');
		$extElem1->setAttribute('fieldsEnclosedBy','"');
		$extElem1->setAttribute('ignoreHeaderLines','1');
		$extElem1->setAttribute('rowType','http://rs.tdwg.org/dwc/terms/Identification');

		$filesElem1 = $newDoc->createElement('files');
		$filesElem1->appendChild($newDoc->createElement('location','identifications'.$this->fileExt));
		$extElem1->appendChild($filesElem1);
		
		$coreIdElem1 = $newDoc->createElement('coreid');
		$coreIdElem1->setAttribute('index','0');
		$extElem1->appendChild($coreIdElem1);
		
		//List identification fields
		if($this->includeDets){
			$detCnt = 1;
			$termArr = $this->determinationFieldArr['terms'];
			foreach($termArr as $k => $v){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$detCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem1->appendChild($fieldElem);
				$detCnt++;
			}
			$rootElem->appendChild($extElem1);
		}

		//Image extension
		if($this->includeImgs){
			$extElem2 = $newDoc->createElement('extension');
			$extElem2->setAttribute('encoding',$this->charSetOut);
			$extElem2->setAttribute('fieldsTerminatedBy',$this->delimiter);
			$extElem2->setAttribute('linesTerminatedBy','\n');
			$extElem2->setAttribute('fieldsEnclosedBy','"');
			$extElem2->setAttribute('ignoreHeaderLines','1');
			$extElem2->setAttribute('rowType','http://rs.gbif.org/terms/1.0/Image');
	
			$filesElem2 = $newDoc->createElement('files');
			$filesElem2->appendChild($newDoc->createElement('location','images'.$this->fileExt));
			$extElem2->appendChild($filesElem2);
			
			$coreIdElem2 = $newDoc->createElement('coreid');
			$coreIdElem2->setAttribute('index','0');
			$extElem2->appendChild($coreIdElem2);
			
			//List image fields
			$imgCnt = 1;
			$termArr = $this->imageFieldArr['terms'];
			foreach($termArr as $k => $v){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$imgCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem2->appendChild($fieldElem);
				$imgCnt++;
			}
			$rootElem->appendChild($extElem2);
		}
		
		$newDoc->save($this->targetPath.$this->ts.'-meta.xml');
		
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function getEmlArr(){
		global $clientRoot, $defaultTitle, $adminEmail;
		
		$urlPathPrefix = "http://";
		if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPathPrefix = "https://";
		$urlPathPrefix .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPathPrefix .= ':'.$_SERVER["SERVER_PORT"];
		$localDomain = $urlPathPrefix;
		$urlPathPrefix .= $clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
		
		$emlArr = array();
		if(count($this->collArr) == 1){
			$collId = key($this->collArr);
			$cArr = $this->collArr[$collId];

			$emlArr['alternateIdentifier'][] = $urlPathPrefix.'collections/misc/collprofiles.php?collid='.$collId;
			$emlArr['title'] = $cArr['collname'];
			$emlArr['description'] = $cArr['description'];
	
			$emlArr['contact']['individualName'] = $cArr['contact'];
			$emlArr['contact']['organizationName'] = $cArr['collname'];
			$emlArr['contact']['phone'] = $cArr['phone'];
			$emlArr['contact']['electronicMailAddress'] = $cArr['email'];
			$emlArr['contact']['onlineUrl'] = $cArr['url'];
			
			$emlArr['contact']['addr']['deliveryPoint'] = $cArr['address1'].($cArr['address2']?', '.$cArr['address2']:'');
			$emlArr['contact']['addr']['city'] = $cArr['city'];
			$emlArr['contact']['addr']['administrativeArea'] = $cArr['state'];
			$emlArr['contact']['addr']['postalCode'] = $cArr['postalcode'];
			$emlArr['contact']['addr']['country'] = $cArr['country'];
			
			
			$emlArr['intellectualRights'] = $cArr['rights'];
		}
		else{
			$emlArr['title'] = $defaultTitle.' general data extract';
		}
		if(isset($GLOBALS['USER_DISPLAY_NAME']) && $GLOBALS['USER_DISPLAY_NAME']){
			$emlArr['creator'][0]['individualName'] = $GLOBALS['USER_DISPLAY_NAME'];
			$emlArr['associatedParty'][0]['individualName'] = $GLOBALS['USER_DISPLAY_NAME'];
			$emlArr['associatedParty'][0]['role'] = 'CONTENT_PROVIDER';
		}

		if(array_key_exists('PORTAL_GUID',$GLOBALS) && $GLOBALS['PORTAL_GUID']){
			$emlArr['creator'][0]['attr']['id'] = $GLOBALS['PORTAL_GUID'];
		}
		$emlArr['creator'][0]['organizationName'] = $defaultTitle;
		$emlArr['creator'][0]['electronicMailAddress'] = $adminEmail;
		$emlArr['creator'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['metadataProvider'][0]['organizationName'] = $defaultTitle;
		$emlArr['metadataProvider'][0]['electronicMailAddress'] = $adminEmail;
		$emlArr['metadataProvider'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['pubDate'] = date("Y-m-d");
		
		//Append collection metadata
		$cnt = 1;
		foreach($this->collArr as $id => $collArr){
			//associatedParty elements
			$emlArr['associatedParty'][$cnt]['organizationName'] = $collArr['collname'];
			$emlArr['associatedParty'][$cnt]['individualName'] = $collArr['contact'];
			$emlArr['associatedParty'][$cnt]['positionName'] = 'Collection Manager';
			$emlArr['associatedParty'][$cnt]['role'] = 'CONTENT_PROVIDER';
			$emlArr['associatedParty'][$cnt]['electronicMailAddress'] = $collArr['email'];
			$emlArr['associatedParty'][$cnt]['phone'] = $collArr['phone'];
			
			if($collArr['state']){
				$emlArr['associatedParty'][$cnt]['address']['deliveryPoint'] = $collArr['address1'];
				if($collArr['address2']) $emlArr['associatedParty'][$cnt]['address']['deliveryPoint'] = $collArr['address2'];
				$emlArr['associatedParty'][$cnt]['address']['city'] = $collArr['city'];
				$emlArr['associatedParty'][$cnt]['address']['administrativeArea'] = $collArr['state'];
				$emlArr['associatedParty'][$cnt]['address']['postalCode'] = $collArr['postalcode'];
				$emlArr['associatedParty'][$cnt]['address']['country'] = $collArr['country'];
			}

			//Collection metadata section (additionalMetadata)
			$emlArr['collMetadata'][$cnt]['attr']['identifier'] = $collArr['collectionguid'];
			$emlArr['collMetadata'][$cnt]['attr']['id'] = $id;
			$emlArr['collMetadata'][$cnt]['alternateIdentifier'] = $urlPathPrefix.'collections/misc/collprofiles.php?collid='.$id;
			$emlArr['collMetadata'][$cnt]['parentCollectionIdentifier'] = $collArr['instcode']; 
			$emlArr['collMetadata'][$cnt]['collectionIdentifier'] = $collArr['collcode']; 
			$emlArr['collMetadata'][$cnt]['collectionName'] = $collArr['collname'];
			if($collArr['icon']){
				$imgLink = '';
				if(substr($collArr['icon'],0,17) == 'images/collicons/'){
					$imgLink = $urlPathPrefix.$collArr['icon'];
				}
				elseif(substr($collArr['icon'],0,1) == '/'){
					$imgLink = $localDomain.$collArr['icon'];
				}
				else{
					$imgLink = $collArr['icon'];
				}
				$emlArr['collMetadata'][$cnt]['resourceLogoUrl'] = $imgLink;
			}
			$emlArr['collMetadata'][$cnt]['onlineUrl'] = $collArr['url'];
			$emlArr['collMetadata'][$cnt]['intellectualRights'] = $collArr['rights'];
			if($collArr['rightsholder']) $emlArr['collMetadata'][$cnt]['additionalInfo'] = $collArr['rightsholder'];
			if($collArr['usageterm']) $emlArr['collMetadata'][$cnt]['additionalInfo'] = $collArr['usageterm'];
			$emlArr['collMetadata'][$cnt]['abstract'] = $collArr['description'];
			
			$cnt++; 
		}
		
		return $emlArr;
	}
	
	private function writeEmlFile(){
		$this->logOrEcho("Creating eml.xml (".date('h:i:s A').")... ");
		
		$emlDoc = $this->getEmlDom();

		$emlDoc->save($this->targetPath.$this->ts.'-eml.xml');

    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	/* 
	 * Input: Array containing the eml data
	 * OUTPUT: XML String representing the EML
	 * USED BY: this class, DwcArchiverExpedition, and emlhandler.php 
	 */
	public function getEmlDom($emlArr = null){
		
		if(!$emlArr) $emlArr = $this->getEmlArr();
		//Create new DOM document 
		$newDoc = new DOMDocument('1.0',$this->charSetOut);

		//Add root element 
		$rootElem = $newDoc->createElement('eml:eml');
		$rootElem->setAttribute('xmlns:eml','eml://ecoinformatics.org/eml-2.1.1');
		$rootElem->setAttribute('xmlns:dc','http://purl.org/dc/terms/');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','eml://ecoinformatics.org/eml-2.1.1 http://rs.gbif.org/schema/eml-gbif-profile/1.0.1/eml.xsd');
		$rootElem->setAttribute('packageId',UuidFactory::getUuidV4());
		$rootElem->setAttribute('system','http://symbiota.org');
		$rootElem->setAttribute('scope','system');
		$rootElem->setAttribute('xml:lang','eng');
		
		$newDoc->appendChild($rootElem);

		$cArr = array();
		$datasetElem = $newDoc->createElement('dataset');
		$rootElem->appendChild($datasetElem);

		if(array_key_exists('alternateIdentifier',$emlArr)){
			foreach($emlArr['alternateIdentifier'] as $v){
				$altIdElem = $newDoc->createElement('alternateIdentifier');
				$altIdElem->appendChild($newDoc->createTextNode($v));
				$datasetElem->appendChild($altIdElem);
			}
		}
		
		if(array_key_exists('title',$emlArr)){
			$titleElem = $newDoc->createElement('title');
			$titleElem->setAttribute('xml:lang','eng');
			$titleElem->appendChild($newDoc->createTextNode($emlArr['title']));
			$datasetElem->appendChild($titleElem);
		}

		if(array_key_exists('creator',$emlArr)){
			$createArr = $emlArr['creator'];
			foreach($createArr as $childArr){
				$creatorElem = $newDoc->createElement('creator');
				if(isset($childArr['attr'])){
					$attrArr = $childArr['attr'];
					unset($childArr['attr']);
					foreach($attrArr as $atKey => $atValue){
						$creatorElem->setAttribute($atKey,$atValue);
					}
				}
				foreach($childArr as $k => $v){
					$newChildElem = $newDoc->createElement($k);
					$newChildElem->appendChild($newDoc->createTextNode($v));
					$creatorElem->appendChild($newChildElem);
				}
				$datasetElem->appendChild($creatorElem);
			}
		}

		if(array_key_exists('metadataProvider',$emlArr)){
			$mdArr = $emlArr['metadataProvider'];
			foreach($mdArr as $childArr){
				$mdElem = $newDoc->createElement('metadataProvider');
				foreach($childArr as $k => $v){
					$newChildElem = $newDoc->createElement($k);
					$newChildElem->appendChild($newDoc->createTextNode($v));
					$mdElem->appendChild($newChildElem);
				}
				$datasetElem->appendChild($mdElem);
			}
		}
		
		if(array_key_exists('pubDate',$emlArr) && $emlArr['pubDate']){
			$pubElem = $newDoc->createElement('pubDate');
			$pubElem->appendChild($newDoc->createTextNode($emlArr['pubDate']));
			$datasetElem->appendChild($pubElem);
		}
		$langStr = 'eng';
		if(array_key_exists('language',$emlArr) && $emlArr) $langStr = $emlArr['language'];
		$langElem = $newDoc->createElement('language');
		$langElem->appendChild($newDoc->createTextNode($langStr));
		$datasetElem->appendChild($langElem);

		if(array_key_exists('description',$emlArr) && $emlArr['description']){
			$abstractElem = $newDoc->createElement('abstract');
			$paraElem = $newDoc->createElement('para');
			$paraElem->appendChild($newDoc->createTextNode($emlArr['description']));
			$abstractElem->appendChild($paraElem);
			$datasetElem->appendChild($abstractElem);
		}
		
		if(array_key_exists('contact',$emlArr)){
			$contactArr = $emlArr['contact'];
			$contactElem = $newDoc->createElement('contact');
			$addrArr = array();
			if(isset($contactArr['addr'])){
				$addrArr = $contactArr['addr'];
				unset($contactArr['addr']);
			}
			foreach($contactArr as $contactKey => $contactValue){
				$conElem = $newDoc->createElement($contactKey);
				$conElem->appendChild($newDoc->createTextNode($contactValue));
				$contactElem->appendChild($conElem);
			}
			if(isset($contactArr['addr'])){
				$addressElem = $newDoc->createElement('address');
				foreach($addrArr as $aKey => $aVal){
					$childAddrElem = $newDoc->createElement($aKey);
					$childAddrElem->appendChild($newDoc->createTextNode($aVal));
					$addressElem->appendChild($childAddrElem);
				}
				$contactElem->appendChild($addressElem);
			}
			$datasetElem->appendChild($contactElem);
		}

		if(array_key_exists('associatedParty',$emlArr)){
			$associatedPartyArr = $emlArr['associatedParty'];
			foreach($associatedPartyArr as $assocKey => $assocArr){
				$assocElem = $newDoc->createElement('associatedParty');
				$addrArr = array();
				if(isset($assocArr['address'])){
					$addrArr = $assocArr['address'];
					unset($assocArr['address']);
				}
				foreach($assocArr as $aKey => $aArr){
					$childAssocElem = $newDoc->createElement($aKey);
					$childAssocElem->appendChild($newDoc->createTextNode($aArr));
					$assocElem->appendChild($childAssocElem);
				}
				if($addrArr){
					$addrElem = $newDoc->createElement('address');
					foreach($addrArr as $addrKey => $addrValue){
						$childAddrElem = $newDoc->createElement($addrKey);
						$childAddrElem->appendChild($newDoc->createTextNode($addrValue));
						$addrElem->appendChild($childAddrElem);
					}
					$assocElem->appendChild($addrElem);
				}
				$datasetElem->appendChild($assocElem);
			}
		}
		
		if(array_key_exists('intellectualRights',$emlArr)){
			$rightsElem = $newDoc->createElement('intellectualRights');
			$paraElem = $newDoc->createElement('para');
			$paraElem->appendChild($newDoc->createTextNode($emlArr['intellectualRights']));
			$rightsElem->appendChild($paraElem);
			$datasetElem->appendChild($rightsElem);
		}

		$symbElem = $newDoc->createElement('symbiota');
		$dateElem = $newDoc->createElement('dateStamp');
		$dateElem->appendChild($newDoc->createTextNode(date("c")));
		$symbElem->appendChild($dateElem);
		//Citation
		$id = UuidFactory::getUuidV4();
		$citeElem = $newDoc->createElement('citation');
		$citeElem->appendChild($newDoc->createTextNode($GLOBALS['defaultTitle'].' - '.$id));
		$citeElem->setAttribute('identifier',$id);
		$symbElem->appendChild($citeElem);
		//Physical
		$physicalElem = $newDoc->createElement('physical');
		$physicalElem->appendChild($newDoc->createElement('characterEncoding',$this->charSetOut));
		//format
		$dfElem = $newDoc->createElement('dataFormat');
		$edfElem = $newDoc->createElement('externallyDefinedFormat');
		$dfElem->appendChild($edfElem);
		$edfElem->appendChild($newDoc->createElement('formatName','Darwin Core Archive'));
		$physicalElem->appendChild($dfElem);
		$symbElem->appendChild($physicalElem);
		//Collection data
		if(array_key_exists('collMetadata',$emlArr)){
			
			foreach($emlArr['collMetadata'] as $k => $collArr){
				$collElem = $newDoc->createElement('collection');
				if(isset($collArr['attr']) && $collArr['attr']){
					$attrArr = $collArr['attr'];
					unset($collArr['attr']);
					foreach($attrArr as $attrKey => $attrValue){
						$collElem->setAttribute($attrKey,$attrValue);
					}
				}
				$abstractStr = '';
				if(isset($collArr['abstract']) && $collArr['abstract']){
					$abstractStr = $collArr['abstract'];
					unset($collArr['abstract']);
				}
				foreach($collArr as $collKey => $collValue){
					$collElem2 = $newDoc->createElement($collKey);
					$collElem2->appendChild($newDoc->createTextNode($collValue));
					$collElem->appendChild($collElem2);
				}
				if($abstractStr){
					$abstractElem = $newDoc->createElement('abstract');
					$abstractElem2 = $newDoc->createElement('para');
					$abstractElem2->appendChild($newDoc->createTextNode($abstractStr));
					$abstractElem->appendChild($abstractElem2);
					$collElem->appendChild($abstractElem);
				}
				$symbElem->appendChild($collElem);
			}
		}
		
		$metaElem = $newDoc->createElement('metadata');
		$metaElem->appendChild($symbElem);
		$addMetaElem = $newDoc->createElement('additionalMetadata');
		$addMetaElem->appendChild($metaElem);
		$rootElem->appendChild($addMetaElem);

		return $newDoc;
	}

	private function writeOccurrenceFile(){
		global $clientRoot;
		$this->logOrEcho("Creating occurrence file (".date('h:i:s A').")... ");
		$filePath = $this->targetPath.$this->ts.'-occur'.$this->fileExt;
		$fh = fopen($filePath, 'w');
		
		if(!$this->occurrenceFieldArr){
			$this->initOccurrenceArr();
		}
		
		//Output records
		$sql = $this->getSqlOccurrences();
		//Output header
		$fieldArr = $this->occurrenceFieldArr['fields'];
		if($this->schemaType == 'dwc'){
			unset($fieldArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($fieldArr['collectionId']);
		}
		$this->writeOutRecord($fh,array_keys($fieldArr));
		if(!$this->collArr){
			//Collection array not previously primed by source  
			$sql1 = 'SELECT DISTINCT o.collid FROM omoccurrences o ';
			if($this->conditionSql) $sql1 .= $this->conditionSql;
			$rs1 = $this->conn->query($sql1);
			$collidStr = '';
			while($r1 = $rs1->fetch_object()){
				$collidStr .= ','.$r1->collid;
			}
			$rs1->free();
			if($collidStr) $this->setCollArr(trim($collidStr,','));
		}
		
		//echo $sql; exit;
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			$urlPathPrefix = "http://";
			if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPathPrefix = "https://";
			$urlPathPrefix .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPathPrefix .= ':'.$_SERVER["SERVER_PORT"];
			$urlPathPrefix .= $clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
			
			$hasRecords = false;
			while($r = $rs->fetch_assoc()){
				$hasRecords = true;
				//Protect sensitive records
				if($this->redactLocalities && $r["localitySecurity"] == 1 && !in_array($r['collid'],$this->rareReaderArr)){
					foreach($this->securityArr as $v){
						if(array_key_exists($v,$r)) $r[$v] = '[Redacted]';
					}
				}
				
				$r['references'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				$guidTarget = $this->collArr[$r['collid']]['guidtarget'];
				if($guidTarget == 'catalogNumber'){
					$r['occurrenceID'] = $r['catalogNumber'];
				}
				elseif($guidTarget == 'symbiotaUUID'){
					$r['occurrenceID'] = $r['recordId'];
				}
				$r['recordId'] = 'urn:uuid:'.$_SERVER["SERVER_NAME"].':'.$r['recordId'];
				if($this->schemaType == 'dwc'){
					unset($r['localitySecurity']);
				}
				if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
					unset($r['collid']);
				}
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
			if(!$hasRecords){
				$filePath = false;
				$this->logOrEcho("No records returned. Modify query variables to be more inclusive. \n");
			}
		}
		else{
			$this->logOrEcho("ERROR creating occurrence file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}

		fclose($fh);
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
		return $filePath;
	}
	
	public function getOccurrenceFile(){
		$this->applyConditions();
		if(!$this->targetPath) $this->setTargetPath();
		$filePath = $this->writeOccurrenceFile();
		return $filePath;
	}
	
	private function writeDeterminationFile(){
		$this->logOrEcho("Creating identification file (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-det'.$this->fileExt, 'w');
		
		if(!$this->determinationFieldArr){
			$this->initDeterminationArr();
		}
		//Output header
		$this->writeOutRecord($fh,array_keys($this->determinationFieldArr['fields']));
		
		//Output records
		$sql = $this->getSqlDeterminations();
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				$r['recordId'] = 'urn:uuid:'.$_SERVER["SERVER_NAME"].':'.$r['recordId'];
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating identification file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
			
		fclose($fh);
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function writeImageFile(){
		global $clientRoot,$imageDomain;

		$this->logOrEcho("Creating image file (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-images'.$this->fileExt, 'w');
		
		if(!$this->imageFieldArr){
			$this->initImageArr();
		}
		
		//Output header
		$this->writeOutRecord($fh,array_keys($this->imageFieldArr['fields']));
		
		//Output records
		$sql = $this->getSqlImages();
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			$urlPathPrefix = "http://";
			if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPathPrefix = "https://";
			$urlPathPrefix .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPathPrefix .= ':'.$_SERVER["SERVER_PORT"];
			$localDomain = '';
			if(isset($imageDomain) && $imageDomain){
				$localDomain = $imageDomain;
			}
			else{
				$localDomain = $urlPathPrefix;
			}
			$urlPathPrefix .= $clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
			while($r = $rs->fetch_assoc()){
				if(substr($r['accessURI'],0,1) == '/') $r['accessURI'] = $localDomain.$r['accessURI'];
				if($this->schemaType != 'backup'){
					if(stripos($r['rights'],'http://creativecommons.org') === 0){
						$r['webstatement'] = $r['rights'];
						$r['rights'] = '';
						if(!$r['usageterms']){
							if($r['webstatement'] == 'http://creativecommons.org/publicdomain/zero/1.0/'){
								$r['usageterms'] = 'CC0 1.0 (Public-domain)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by/3.0/'){
								$r['usageterms'] = 'CC BY (Attribution)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by-sa/3.0/'){
								$r['usageterms'] = 'CC BY-SA (Attribution-ShareAlike)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by-nc/3.0/'){
								$r['usageterms'] = 'CC BY-NC (Attribution-Non-Commercial)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by-nc-sa/3.0/'){
								$r['usageterms'] = 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)';
							}
						}
					}
					if(!$r['usageterms']) $r['usageterms'] = 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)';
				}
				$r['providermanagedid'] = 'urn:uuid:'.$_SERVER["SERVER_NAME"].':'.$r['providermanagedid'];
				$r['associatedSpecimenReference'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				$r['type'] = 'StillImage';
				$r['subtype'] = 'Photograph';
				$extStr = strtolower(substr($r['accessURI'],strrpos($r['accessURI'],'.')+1));
				if($extStr == 'jpg' || $extStr == 'jpeg'){
					$r['format'] = 'image/jpeg';
				}
				elseif($extStr == 'gif'){
					$r['format'] = 'image/gif';
				}
				elseif($extStr == 'png'){
					$r['format'] = 'image/png';
				}
				elseif($extStr == 'tiff' || $extStr == 'tif'){
					$r['format'] = 'image/tiff';
				}
				else{
					$r['format'] = '';
				}
				$r['metadataLanguage'] = 'en';
				//Load record array into output file
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating image file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
		
		fclose($fh);
		
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}
	
	private function writeOutRecord($fh,$outputArr){
		if($this->delimiter == ","){
			fputcsv($fh, $outputArr);
		}
		else{
			foreach($outputArr as $k => $v){
				$outputArr[$k] = str_replace($this->delimiter,'',$v);
			}
			fwrite($fh, implode($this->delimiter,$outputArr)."\n");
		}
	}

	//DWCA publishing and RSS related functions 
	public function batchCreateDwca($collIdArr){
		global $serverRoot;

		$this->logOrEcho("Starting batch process (".date('Y-m-d h:i:s A').")\n");
		$this->logOrEcho("\n-----------------------------------------------------\n\n");
		
		$successArr = array();
		foreach($collIdArr as $id){
			//Create a separate DWCA object for each collection
			$this->resetCollArr($id);
			if($this->createDwcArchive()){
				$successArr[] = $id;
			}
		}
		//Reset $this->collArr with all the collections ran successfully and then rebuild the RSS feed 
		$this->resetCollArr(implode(',',$successArr));
		$this->writeRssFile();
		$this->logOrEcho("Batch process finished! (".date('Y-m-d h:i:s A').") \n");
	}
	
	public function writeRssFile(){
		global $defaultTitle, $serverRoot, $clientRoot;

		$this->logOrEcho("Mapping data to RSS feed... \n");
		
		//Create new document and write out to target
		$newDoc = new DOMDocument('1.0',$this->charSetOut);

		//Add root element 
		$rootElem = $newDoc->createElement('rss');
		$rootAttr = $newDoc->createAttribute('version');
		$rootAttr->value = '2.0';
		$rootElem->appendChild($rootAttr);
		$newDoc->appendChild($rootElem);

		//Add Channel
		$channelElem = $newDoc->createElement('channel');
		$rootElem->appendChild($channelElem);
		
		//Add title, link, description, language
		$titleElem = $newDoc->createElement('title');
		$titleElem->appendChild($newDoc->createTextNode($defaultTitle.' Darwin Core Archive rss feed'));
		$channelElem->appendChild($titleElem);
		$urlPathPrefix = "http://";
		if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPathPrefix = "https://";
		$urlPathPrefix .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPathPrefix .= ':'.$_SERVER["SERVER_PORT"];
		$localDomain = $urlPathPrefix;
		$urlPathPrefix .= $clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
		$linkElem = $newDoc->createElement('link');
		$linkElem->appendChild($newDoc->createTextNode($urlPathPrefix));
		$channelElem->appendChild($linkElem);
		$descriptionElem = $newDoc->createElement('description');
		$descriptionElem->appendChild($newDoc->createTextNode($defaultTitle.' Darwin Core Archive rss feed'));
		$channelElem->appendChild($descriptionElem);
		$languageElem = $newDoc->createElement('language','en-us');
		$channelElem->appendChild($languageElem);

		//Create new item for target archives and load into array
		$itemArr = array();
		foreach($this->collArr as $collId => $cArr){
			$itemElem = $newDoc->createElement('item');
			$itemAttr = $newDoc->createAttribute('collid');
			$itemAttr->value = $collId;
			$itemElem->appendChild($itemAttr);
			//Add title
			$instCode = $cArr['instcode'];
			if($cArr['collcode']) $instCode .= '-'.$cArr['collcode'];
			$title = $instCode.' DwC-Archive';
			$itemTitleElem = $newDoc->createElement('title');
			$itemTitleElem->appendChild($newDoc->createTextNode($title));
			$itemElem->appendChild($itemTitleElem);
			//Icon
			$imgLink = '';
			if(substr($cArr['icon'],0,17) == 'images/collicons/'){
				//Link is a 
				$imgLink = $urlPathPrefix.$cArr['icon'];
			}
			elseif(substr($cArr['icon'],0,1) == '/'){
				$imgLink = $localDomain.$cArr['icon'];
			}
			else{
				$imgLink = $cArr['icon'];
			}
			$iconElem = $newDoc->createElement('image');
			$iconElem->appendChild($newDoc->createTextNode($imgLink));
			$itemElem->appendChild($iconElem);
			
			//description
			$descTitleElem = $newDoc->createElement('description');
			$descTitleElem->appendChild($newDoc->createTextNode('Darwin Core Archive for '.$cArr['collname']));
			$itemElem->appendChild($descTitleElem);
			//GUIDs
			$guidElem = $newDoc->createElement('guid');
			$guidElem->appendChild($newDoc->createTextNode($urlPathPrefix.'collections/misc/collprofiles.php?collid='.$collId));
			$itemElem->appendChild($guidElem);
			$guidElem2 = $newDoc->createElement('guid');
			$guidElem2->appendChild($newDoc->createTextNode($cArr['collectionguid']));
			$itemElem->appendChild($guidElem2);
			//EML file link
			$emlElem = $newDoc->createElement('emllink');
			$emlElem->appendChild($newDoc->createTextNode($urlPathPrefix.'collections/datasets/dwc/'.str_replace(' ','_',$instCode).'_DwC-A.eml'));
			$itemElem->appendChild($emlElem);
			//type
			$typeTitleElem = $newDoc->createElement('type','DWCA');
			$itemElem->appendChild($typeTitleElem);
			//recordType
			$recTypeTitleElem = $newDoc->createElement('recordType','DWCA');
			$itemElem->appendChild($recTypeTitleElem);
			//link
			$linkTitleElem = $newDoc->createElement('link');
			$linkTitleElem->appendChild($newDoc->createTextNode($urlPathPrefix.'collections/datasets/dwc/'.str_replace(' ','_',$instCode).'_DwC-A.zip'));
			$itemElem->appendChild($linkTitleElem);
			//pubDate
			//$dsStat = stat($this->targetPath.$instCode.'_DwC-A.zip');
			$pubDateTitleElem = $newDoc->createElement('pubDate');
			$pubDateTitleElem->appendChild($newDoc->createTextNode(date("D, d M Y H:i:s")));
			$itemElem->appendChild($pubDateTitleElem);
			$itemArr[$title] = $itemElem;
		}

		//Add existing items
		$rssFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		if(file_exists($rssFile)){
			//Get other existing DWCAs by reading and parsing current rss.xml
			$oldDoc = new DOMDocument();
			$oldDoc->load($rssFile);
			$items = $oldDoc->getElementsByTagName("item");
			foreach($items as $i){
				//Filter out item for active collection
				$t = $i->getElementsByTagName("title")->item(0)->nodeValue;
				if(!array_key_exists($i->getAttribute('collid'),$this->collArr)) $itemArr[$t] = $newDoc->importNode($i,true);
			}
		}

		//Sort and add items to channel
		ksort($itemArr);
		foreach($itemArr as $i){
			$channelElem->appendChild($i);
		}
		
		$newDoc->save($rssFile);

		$this->logOrEcho("Done!!\n");
	}
	
	public function deleteArchive($collId){
		global $serverRoot;
		$rssFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		if(!file_exists($rssFile)) return false;
		$doc = new DOMDocument();
		$doc->load($rssFile);
		$cElem = $doc->getElementsByTagName("channel")->item(0);
		$items = $cElem->getElementsByTagName("item");
		foreach($items as $i){
			if($i->getAttribute('collid') == $collId){
				$link = $i->getElementsByTagName("link");
				$nodeValue = $link->item(0)->nodeValue;
				$filePath = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/');
				$filePath .= 'collections/datasets/dwc'.substr($nodeValue,strrpos($nodeValue,'/'));
				unlink($filePath);
				$emlPath = str_replace('.zip','.eml',$filePath);
				if(file_exists($emlPath)) unlink($emlPath);
				$cElem->removeChild($i);
			}
		}
		$doc->save($rssFile);
		return true;
	}

	//getters, setters, and misc functions
	public function getDwcaItems($collid = 0){
		global $serverRoot;
		$retArr = Array();
		$rssFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		if(file_exists($rssFile)){
			$xmlDoc = new DOMDocument();
			$xmlDoc->load($rssFile);
			$items = $xmlDoc->getElementsByTagName("item");
			$cnt = 0;
			foreach($items as $i ){
				$id = $i->getAttribute("collid");
				if(!$collid || $collid == $id){
					$titles = $i->getElementsByTagName("title");
					$retArr[$cnt]['title'] = $titles->item(0)->nodeValue;
					$descriptions = $i->getElementsByTagName("description");
					$retArr[$cnt]['description'] = $descriptions->item(0)->nodeValue;
					$types = $i->getElementsByTagName("type");
					$retArr[$cnt]['type'] = $types->item(0)->nodeValue;
					$recordTypes = $i->getElementsByTagName("recordType");
					$retArr[$cnt]['recordType'] = $recordTypes->item(0)->nodeValue;
					$links = $i->getElementsByTagName("link");
					$retArr[$cnt]['link'] = $links->item(0)->nodeValue;
					$pubDates = $i->getElementsByTagName("pubDate");
					$retArr[$cnt]['pubDate'] = $pubDates->item(0)->nodeValue;
					$retArr[$cnt]['collid'] = $id;
					$cnt++;
				}
			}
		}
		$this->aasort($retArr, 'description');
		return $retArr;
	}

	private function aasort(&$array, $key){
		$sorter = array();
		$ret = array();
		reset($array);
		foreach ($array as $ii => $va) {
			$sorter[$ii] = $va[$key];
		}
		asort($sorter);
		foreach ($sorter as $ii => $va) {
			$ret[$ii] = $array[$ii];
		}
		$array = $ret;
	}

	public function getCollectionList(){
		$retArr = array();
		$sql = 'SELECT collid, collectionname, CONCAT_WS("-",institutioncode,collectioncode) as instcode '.
			'FROM omcollections '.
			'WHERE colltype = "Preserved Specimens" '.
			'ORDER BY collectionname ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->instcode.')';
		}
		return $retArr;
	}

	public function setVerbose($c){
		$this->verbose = $c;
	}
	
	public function setSchemaType($type){
		$this->schemaType = $type;
	}
	
	public function setDelimiter($d){
		if($d == 'tab' || $d == "\t"){
			$this->delimiter = "\t";
			$this->fileExt = '.tab';
		}
		elseif($d == 'csv' || $d == 'comma' || $d == ','){
			$this->delimiter = ",";
			$this->fileExt = '.csv';
		}
		else{
			$this->delimiter = $d;
			$this->fileExt = '.txt';
		}
	}

	public function setIncludeDets($includeDets){
		$this->includeDets = $includeDets;
	}
	
	public function setIncludeImgs($includeImgs){
		$this->includeImgs = $includeImgs;
	}
	
	public function setRedactLocalities($redact){
		$this->redactLocalities = $redact;
	}

	public function setRareReaderArr($approvedCollid){
		if(is_array($approvedCollid)){ 
			$this->rareReaderArr = $approvedCollid;
		}
		elseif(is_string($approvedCollid)){
			$this->rareReaderArr = explode(',',$approvedCollid);
		}
	}

	public function setCharSetOut($cs){
		$cs = strtoupper($cs);
		if($cs == 'ISO-8859-1' || $cs == 'UTF-8'){
			$this->charSetOut = $cs;
		}
	}

	private function logOrEcho($str){
		if($this->verbose){
			if($this->logFH){
				fwrite($this->logFH,$str);
			} 
			echo '<li>'.$str.'</li>';
			ob_flush();
			flush();
		}
	}
	
	private function encodeArr(&$inArr){
		if($this->charSetSource && $this->charSetOut != $this->charSetSource){
			foreach($inArr as $k => $v){
				$inArr[$k] = $this->encodeStr($v);
			}
		}
	}

	private function encodeStr($inStr){
		$retStr = $inStr;
		if($this->charSetSource){
			if($this->charSetOut == 'utf-8' && $this->charSetSource == 'iso-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($this->charSetOut == "iso-8859-1" && $this->charSetSource == 'utf-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		return $retStr;
	}
	
	private function addcslashesArr(&$arr){
		foreach($arr as $k => $v){
			$arr[$k] = addcslashes($v,"\n\r\"\\");
		}
	}

	public function humanFilesize($filePath) {
		if(!file_exists($filePath)) return '';
		$decimals = 0;
		$bytes = filesize($filePath);
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = preg_replace('/\s\s+/', ' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>