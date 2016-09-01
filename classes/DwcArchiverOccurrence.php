<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

class DwcArchiverOccurrence{

	private $conn;
	private $ts;

	private $collArr;
	private $customWhereSql;
	private $conditionSql;
 	private $conditionArr = array();
	private $condAllowArr;
	private $upperTaxonomy = array();

	private $targetPath;
	private $serverDomain;

	private $logFH;
	private $verbose = false;

	private $schemaType = 'dwc';			//dwc, symbiota, backup
	private $limitToGuids = false;			//Limit output to only records with GUIDs
	private $extended = 0;
	private $delimiter = ',';
	private $fileExt = '.csv';
	private $occurrenceFieldArr = array();
	private $determinationFieldArr = array();
	private $imageFieldArr = array();
	private $occurrenceTargetFieldArr = array();
	private $determinationTargetFieldArr = array();
	private $imageTargetFieldArr = array();
	private $securityArr = array();
	private $includeDets = 1;
	private $includeImgs = 1;
	private $redactLocalities = 1;
	private $rareReaderArr = array();
	private $charSetSource = '';
	private $charSetOut = '';

	private $geolocateVariables = array();
	
	public function __construct(){

		//Ensure that PHP DOMDocument class is installed
		if(!class_exists('DOMDocument')){
			exit('FATAL ERROR: PHP DOMDocument class is not installed, please contact your server admin');
		}
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		$this->ts = time();
		if(!$this->logFH && $this->verbose){
			$logFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/')."temp/logs/DWCA_".date('Y-m-d').".log";
			$this->logFH = fopen($logFile, 'a');
		}

		//Character set
		$this->charSetSource = strtoupper($GLOBALS['CHARSET']);
		$this->charSetOut = $this->charSetSource;
		
		$this->condAllowArr = array('catalognumber','othercatalognumbers','occurrenceid','family','sciname',
			'country','stateprovince','county','municipality','recordedby','recordnumber','eventdate',
			'decimallatitude','decimallongitude','minimumelevationinmeters','maximumelevationinmeters','datelastmodified','dateentered');
		
		$this->securityArr = array('eventDate','month','day','startDayOfYear','endDayOfYear','verbatimEventDate',
			'recordNumber','locality','locationRemarks','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
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
		$occurTermArr['sourcePrimaryKey'] = 'http://symbiota.org/terms/sourcePrimaryKey'; 
		$occurFieldArr['sourcePrimaryKey'] = 'o.dbpk'; 
		$occurTermArr['collId'] = 'http://symbiota.org/terms/collId'; 
		$occurFieldArr['collId'] = 'c.collid'; 
		$occurTermArr['recordId'] = 'http://portal.idigbio.org/terms/recordId';
		$occurFieldArr['recordId'] = 'g.guid AS recordId';
		$occurTermArr['references'] = 'http://purl.org/dc/terms/references';
		$occurFieldArr['references'] = '';
		//CA: Bookmark
		/*idCollaboratorIndigenous*/
		$occurTermArr['idCollaboratorIndigenous'] = 'http://symbiota.org/terms/idCollaboratorIndigenous';
		$occurFieldArr['idCollaboratorIndigenous'] = 'o.idcollaboratorindigenous';
		/*sexCollaboratorIndigenous*/
		$occurTermArr['sexCollaboratorIndigenous'] = 'http://symbiota.org/terms/sexCollaboratorIndigenous';
		$occurFieldArr['sexCollaboratorIndigenous'] = 'o.sexcollaboratorindigenous';
		/*dobCollaboratorIndigenous*/
		$occurTermArr['dobCollaboratorIndigenous'] = 'http://symbiota.org/terms/dobCollaboratorIndigenous';
		$occurFieldArr['dobCollaboratorIndigenous'] = 'o.dobcollaboratorindigenous';
		/*verbatimIndigenous*/
		$occurTermArr['verbatimIndigenous'] = 'http://symbiota.org/terms/verbatimIndigenous';
		$occurFieldArr['verbatimIndigenous'] = 'o.verbatimindigenous';
		/*validIndigenous*/
		$occurTermArr['validIndigenous'] = 'http://symbiota.org/terms/validIndigenous';
		$occurFieldArr['validIndigenous'] = 'o.validindigenous';
		/*linkLanguageCollaboratorIndigenous*/
		$occurTermArr['linkLanguageCollaboratorIndigenous'] = 'http://symbiota.org/terms/linkLanguageCollaboratorIndigenous';
		$occurFieldArr['linkLanguageCollaboratorIndigenous'] = 'o.linklanguagecollaboratorindigenous';
		/*familyLanguageCollaboratorIndigenous*/
		$occurTermArr['familyLanguageCollaboratorIndigenous'] = 'http://symbiota.org/terms/familyLanguageIndigenous';
		$occurFieldArr['familyLanguageCollaboratorIndigenous'] = 'o.familylanguagecollaboratorindigenous';
		/*groupLanguageCollaboratorIndigenous*/
		$occurTermArr['groupLanguageCollaboratorIndigenous'] = 'http://symbiota.org/terms/groupLanguageIndigenous';
		$occurFieldArr['groupLanguageCollaboratorIndigenous'] = 'o.grouplanguagecollaboratorindigenous';
		/*subgroupLanguageCollaboratorIndigenous*/
		$occurTermArr['subgroupLanguageCollaboratorIndigenous'] = 'http://symbiota.org/terms/subgroupLanguageIndigenous';
		$occurFieldArr['subgroupLanguageCollaboratorIndigenous'] = 'o.subgrouplanguagecollaboratorindigenous';
		/*villageCollaboratorIndigenous*/
		$occurTermArr['villageCollaboratorIndigenous'] = 'http://symbiota.org/terms/villageCollaboratorIndigenous';
		$occurFieldArr['villageCollaboratorIndigenous'] = 'o.villagecollaboratorindigenous';
		/*municipalityCollaboratorIndigenous*/
		$occurTermArr['municipalityCollaboratorIndigenous'] = 'http://symbiota.org/terms/municipalityCollaboratorIndigenous';
		$occurFieldArr['municipalityCollaboratorIndigenous'] = 'o.municipalitycollaboratorindigenous';
		/*stateCollaboratorIndigenous*/
		$occurTermArr['stateCollaboratorIndigenous'] = 'http://symbiota.org/terms/stateCollaboratorIndigenous';
		$occurFieldArr['stateCollaboratorIndigenous'] = 'o.statecollaboratorindigenous';
		/*countryCollaboratorIndigenous*/
		$occurTermArr['countryCollaboratorIndigenous'] = 'http://symbiota.org/terms/countryCollaboratorIndigenous';
		$occurFieldArr['countryCollaboratorIndigenous'] = 'o.countrycollaboratorindigenous';
		/*isoLanguageCollaboratorIndigenous*/
		$occurTermArr['isoLanguageCollaboratorIndigenous'] = 'http://symbiota.org/terms/isoLanguageCollaboratorIndigenous';
		$occurFieldArr['isoLanguageCollaboratorIndigenous'] = 'o.isolanguagecollaboratorindigenous';
		/*vernacularLexiconIndigenous*/
		$occurTermArr['vernacularLexiconIndigenous'] = 'http://symbiota.org/terms/vernacularLexiconIndigenous';
		$occurFieldArr['vernacularLexiconIndigenous'] = 'o.vernacularlexiconindigenous';
		/*glossLexiconIndigenous*/
		$occurTermArr['glossLexiconIndigenous'] = 'http://symbiota.org/terms/glossLexiconIndigenous';
		$occurFieldArr['glossLexiconIndigenous'] = 'o.glosslexiconindigenous';
		/*parseLexiconIndigenous*/
		$occurTermArr['parseLexiconIndigenous'] = 'http://symbiota.org/terms/parseLexiconIndigenous';
		$occurFieldArr['parseLexiconIndigenous'] = 'o.parselexiconindigenous';
		/*parentTaxaLexiconIndigenous*/
		$occurTermArr['parentTaxaLexiconIndigenous'] = 'http://symbiota.org/terms/parentTaxaLexiconIndigenous';
		$occurFieldArr['parentTaxaLexiconIndigenous'] = 'o.parentytaxalexiconindigenous';
		/*siblingTaxaLexiconIndigenous*/
		$occurTermArr['siblingTaxaLexiconIndigenous'] = 'http://symbiota.org/terms/siblingTaxaLexiconIndigenous';
		$occurFieldArr['siblingTaxaLexiconIndigenous'] = 'o.siblingtaxalexiconindigenous';
		/*childTaxaLexiconIndigenous*/
		$occurTermArr['childTaxaLexiconIndigenous'] = 'http://symbiota.org/terms/childTaxaLexiconIndigenous';
		$occurFieldArr['childTaxaLexiconIndigenous'] = 'o.childtaxalexiconindigenous';
		/*otherTaxaUseIndigenous*/
		$occurTermArr['otherTaxaUseIndigenous'] = 'http://symbiota.org/terms/otherTaxaUseIndigenous';
		$occurFieldArr['otherTaxaUseIndigenous'] = 'o.othertaxauseindigenous';
		/*typologyLexiconIndigenous*/
		$occurTermArr['typologyLexiconIndigenous'] = 'http://symbiota.org/terms/typologyLexiconIndigenous';
		$occurFieldArr['typologyLexiconIndigenous'] = 'o.typologylexiconindigenous';
		/*semanticsLexiconIndigenous*/
		$occurTermArr['semanticsLexiconIndigenous'] = 'http://symbiota.org/terms/semanticsLexiconIndigenous';
		$occurFieldArr['semanticsLexiconIndigenous'] = 'o.semanticslexiconindigenous';
		/*notesLexiconIndigenous*/
		$occurTermArr['notesLexiconIndigenous'] = 'http://symbiota.org/terms/notesLexiconIndigenous';
		$occurFieldArr['notesLexiconIndigenous'] = 'o.noteslexiconindigenous';
		/*categoryUseIndigenous*/
		$occurTermArr['categoryUseIndigenous'] = 'http://symbiota.org/terms/categoryUseIndigenous';
		$occurFieldArr['categoryUseIndigenous'] = 'o.categoryuseindigenous';
		/*specificUseIndigenous*/
		$occurTermArr['specificUseIndigenous'] = 'http://symbiota.org/terms/specificUseIndigenous';
		$occurFieldArr['specificUseIndigenous'] = 'o.specificuseindigenous';
		/*partUseIndigenous*/
		$occurTermArr['partUseIndigenous'] = 'http://symbiota.org/terms/partUseIndigenous';
		$occurFieldArr['partUseIndigenous'] = 'o.partuseindigenous';
		/*notesUseIndigenous*/
		$occurTermArr['notesUseIndigenous'] = 'http://symbiota.org/terms/notesUseIndigenous';
		$occurFieldArr['notesUseIndigenous'] = 'o.notesuseindigenous';

		$this->occurrenceFieldArr['terms'] = $this->trimOccurrenceBySchemaType($occurTermArr);
		$occurFieldArr = $this->trimOccurrenceBySchemaType($occurFieldArr);
		if($this->schemaType == 'dwc'){
			$occurFieldArr['recordedBy'] = 'CONCAT_WS("; ",o.recordedBy,o.associatedCollectors) AS recordedBy';
			$occurFieldArr['occurrenceRemarks'] = 'CONCAT_WS("; ",o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks';
			$occurFieldArr['habitat'] = 'CONCAT_WS("; ",o.habitat, o.substrate) AS habitat';
		}
		$this->occurrenceFieldArr['fields'] = $occurFieldArr;
	}

	private function trimOccurrenceBySchemaType($occurArr){
		$retArr = array();
		if($this->schemaType == 'dwc'){
			$trimArr = array('tidInterpreted','recordedByID','associatedCollectors','substrate','verbatimAttributes','cultivationStatus',
				'localitySecurityReason','genericcolumn1','genericcolumn2','storageLocation','observerUid','processingStatus',
				'duplicateQuantity','dateEntered','dateLastModified','sourcePrimaryKey');
			$retArr = array_diff_key($occurArr,array_flip($trimArr));
		}
		elseif($this->schemaType == 'symbiota'){
			$trimArr = array();
			if(!$this->extended){
				$trimArr = array('collectionID','rights','rightsHolder','accessRights','tidInterpreted','genericcolumn1','genericcolumn2',
					'storageLocation','observerUid','processingStatus','duplicateQuantity','dateEntered','dateLastModified'); 
			}
			$retArr = array_diff_key($occurArr,array_flip($trimArr));
		}
		elseif($this->schemaType == 'backup'){
			$trimArr = array('collectionID','rights','rightsHolder','accessRights'); 
			$retArr = array_diff_key($occurArr,array_flip($trimArr));
		}
		elseif($this->schemaType == 'coge'){
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
	
	private function getSqlOccurrences($fullSql = true){
		$sql = '';
		$this->applyConditions();
		if($this->conditionSql){
			if($fullSql){
				$fieldArr = $this->occurrenceFieldArr['fields'];
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
			if(strpos($this->conditionSql,'v.clid')){
				//Search criteria came from custom search page
				$sql .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
			}
			if(strpos($this->conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			$sql .= $this->conditionSql;
			if($fullSql) $sql .= ' ORDER BY o.collid'; 
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}
	
	public function getOccurrenceCnt(){
		$retStr = 0;
		$sql = $this->getSqlOccurrences(false);
		if($sql){
			$sql = 'SELECT COUNT(o.occid) as cnt '.$sql;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retStr = $r->cnt;
			}
			$rs->free();
		}
		return $retStr;
	}

	private function initDeterminationArr(){
		$detFieldArr['coreid'] = 'o.occid';
		$detTermArr['identifiedBy'] = 'http://rs.tdwg.org/dwc/terms/identifiedBy';
		$detFieldArr['identifiedBy'] = 'd.identifiedBy';
		$detTermArr['identifiedByID'] = 'http://symbiota.org/terms/identifiedByID';
		$detFieldArr['identifiedByID'] = 'd.idbyid';
		$detTermArr['dateIdentified'] = 'http://rs.tdwg.org/dwc/terms/dateIdentified';
		$detFieldArr['dateIdentified'] = 'd.dateIdentified';
		$detTermArr['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
		$detFieldArr['identificationQualifier'] = 'd.identificationQualifier';
		$detTermArr['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$detFieldArr['scientificName'] = 'd.sciName AS scientificName';
		$detTermArr['tidInterpreted'] = 'http://symbiota.org/terms/tidInterpreted';
		$detFieldArr['tidInterpreted'] = 'd.tidinterpreted';
		$detTermArr['identificationIsCurrent'] = 'http://symbiota.org/terms/identificationIsCurrent';
		$detFieldArr['identificationIsCurrent'] = 'd.iscurrent';
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

		$this->determinationFieldArr['terms'] = $this->trimDeterminationBySchemaType($detTermArr);
		$this->determinationFieldArr['fields'] = $this->trimDeterminationBySchemaType($detFieldArr);
	}
	
	private function trimDeterminationBySchemaType($detArr){
		$trimArr = array();
		if($this->schemaType == 'dwc'){
			$trimArr = array('identifiedByID');
			$trimArr = array('tidInterpreted');
			$trimArr = array('identificationIsCurrent');
		}
		elseif($this->schemaType == 'symbiota'){
			if(!$this->extended){
				$trimArr = array('identifiedByID');
				$trimArr = array('tidInterpreted');
			}
		}
		elseif($this->schemaType == 'backup'){
			$trimArr = array(); 
		}
		elseif($this->schemaType == 'coge'){
			$trimArr = array(); 
		}
		return array_diff_key($detArr,array_flip($trimArr));
	}

	public function getSqlDeterminations(){
		$sql = ''; 
		$fieldArr = $this->determinationFieldArr['fields'];
		if($fieldArr && $this->conditionSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM (omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid) '.
				'INNER JOIN guidoccurdeterminations g ON d.detid = g.detid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid '.
				'LEFT JOIN taxa t ON d.tidinterpreted = t.tid ';
			if(strpos($this->conditionSql,'v.clid')){
				//Search criteria came from custom search page
				$sql .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
			}
			if(strpos($this->conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			$sql .= $this->conditionSql.'AND d.appliedstatus = 1 '.
				'ORDER BY o.collid';
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}

	private function initImageArr(){
		$imgFieldArr['coreid'] = 'o.occid';
		$imgTermArr['identifier'] = 'http://purl.org/dc/terms/identifier';
		$imgFieldArr['identifier'] = 'IFNULL(i.originalurl,i.url) as identifier';
		$imgTermArr['accessURI'] = 'http://rs.tdwg.org/ac/terms/accessURI';
		$imgFieldArr['accessURI'] = 'IFNULL(i.originalurl,i.url) as accessURI';
		$imgTermArr['thumbnailAccessURI'] = 'http://rs.tdwg.org/ac/terms/thumbnailAccessURI';	
		$imgFieldArr['thumbnailAccessURI'] = 'i.thumbnailurl as thumbnailAccessURI';
		$imgTermArr['goodQualityAccessURI'] = 'http://rs.tdwg.org/ac/terms/goodQualityAccessURI';
		$imgFieldArr['goodQualityAccessURI'] = 'i.url as goodQualityAccessURI';
		$imgTermArr['rights'] = 'http://purl.org/dc/terms/rights';	
		$imgFieldArr['rights'] = 'c.rights';
		$imgTermArr['Owner'] = 'http://ns.adobe.com/xap/1.0/rights/Owner';	//Institution name
		$imgFieldArr['Owner'] = 'IFNULL(c.rightsholder,CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")")) AS owner';
		$imgTermArr['UsageTerms'] = 'http://ns.adobe.com/xap/1.0/rights/UsageTerms';	//Creative Commons BY-SA 3.0 license
		$imgFieldArr['UsageTerms'] = 'i.copyright AS usageterms';
		$imgTermArr['WebStatement'] = 'http://ns.adobe.com/xap/1.0/rights/WebStatement';	//http://creativecommons.org/licenses/by-nc-sa/3.0/us/
		$imgFieldArr['WebStatement'] = 'c.accessrights AS webstatement';
		$imgTermArr['caption'] = 'http://rs.tdwg.org/ac/terms/caption';	
		$imgFieldArr['caption'] = 'i.caption';
		$imgTermArr['comments'] = 'http://rs.tdwg.org/ac/terms/comments';	
		$imgFieldArr['comments'] = 'i.notes';
		$imgTermArr['providerManagedID'] = 'http://rs.tdwg.org/ac/terms/providerManagedID';	//GUID
		$imgFieldArr['providerManagedID'] = 'g.guid AS providermanagedid';
		$imgTermArr['MetadataDate'] = 'http://ns.adobe.com/xap/1.0/MetadataDate';	//timestamp
		$imgFieldArr['MetadataDate'] = 'i.initialtimestamp AS metadatadate';
		$imgTermArr['format'] = 'http://purl.org/dc/terms/format';		//jpg
		$imgFieldArr['format'] = 'i.format';
		$imgTermArr['associatedSpecimenReference'] = 'http://rs.tdwg.org/ac/terms/associatedSpecimenReference';	//reference url in portal
		$imgFieldArr['associatedSpecimenReference'] = '';
		$imgTermArr['type'] = 'http://purl.org/dc/terms/type';		//StillImage
		$imgFieldArr['type'] = '';
		$imgTermArr['subtype'] = 'http://rs.tdwg.org/ac/terms/subtype';		//Photograph
		$imgFieldArr['subtype'] = '';
		$imgTermArr['metadataLanguage'] = 'http://rs.tdwg.org/ac/terms/metadataLanguage';	//en
		$imgFieldArr['metadataLanguage'] = '';

		if($this->schemaType == 'backup'){
			$imgFieldArr['rights'] = 'i.copyright';
		}

		$this->imageFieldArr['terms'] = $this->trimImageBySchemaType($imgTermArr);
		$this->imageFieldArr['fields'] = $this->trimImageBySchemaType($imgFieldArr);
	}

	private function trimImageBySchemaType($imageArr){
		$trimArr = array();
		if($this->schemaType == 'backup'){
			$trimArr = array('Owner', 'UsageTerms', 'WebStatement'); 
		}
		return array_diff_key($imageArr,array_flip($trimArr));
	}

	public function getSqlImages(){
		$sql = ''; 
		$fieldArr = $this->imageFieldArr['fields'];
		if($fieldArr && $this->conditionSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
				'INNER JOIN guidimages g ON i.imgid = g.imgid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid ';

			if(strpos($this->conditionSql,'v.clid')){
				//Search criteria came from custom search page
				$sql .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
			}
			if(strpos($this->conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			$sql .= $this->conditionSql;
			if($this->redactLocalities){
				if($this->rareReaderArr){
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$this->rareReaderArr).')) ';
				}
				else{
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
				}
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
	
	public function setServerDomain($domain){
		$this->serverDomain = $domain;
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
				'IFNULL(c.homepage,i.url) AS url, IFNULL(c.contact,i.contact) AS contact, IFNULL(c.email,i.email) AS email, c.guidtarget, '.
				'c.latitudedecimal, c.longitudedecimal, c.icon, c.managementtype, c.colltype, c.rights, c.rightsholder, c.usageterm, '.
				'i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country, i.phone '.
				'FROM omcollections c LEFT JOIN institutions i ON c.iid = i.iid WHERE '.$sqlWhere;
			//echo 'SQL: '.$sql.'<br/>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
				$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
				$this->collArr[$r->collid]['collname'] = $r->collectionname;
				$this->collArr[$r->collid]['description'] = $r->fulldescription;
				$this->collArr[$r->collid]['collectionguid'] = $r->collectionguid;
				$this->collArr[$r->collid]['url'] = $r->url;
				$this->collArr[$r->collid]['contact'] = $r->contact;
				$this->collArr[$r->collid]['email'] = $r->email;
				$this->collArr[$r->collid]['guidtarget'] = $r->guidtarget;
				$this->collArr[$r->collid]['lat'] = $r->latitudedecimal;
				$this->collArr[$r->collid]['lng'] = $r->longitudedecimal;
				$this->collArr[$r->collid]['icon'] = $r->icon;
				$this->collArr[$r->collid]['colltype'] = $r->colltype;
				$this->collArr[$r->collid]['managementtype'] = $r->managementtype;
				$this->collArr[$r->collid]['rights'] = $r->rights;
				$this->collArr[$r->collid]['rightsholder'] = $r->rightsholder;
				$this->collArr[$r->collid]['usageterm'] = $r->usageterm;
				$this->collArr[$r->collid]['address1'] = $r->address1;
				$this->collArr[$r->collid]['address2'] = $r->address2;
				$this->collArr[$r->collid]['city'] = $r->city;
				$this->collArr[$r->collid]['state'] = $r->stateprovince;
				$this->collArr[$r->collid]['postalcode'] = $r->postalcode;
				$this->collArr[$r->collid]['country'] = $r->country;
				$this->collArr[$r->collid]['phone'] = $r->phone;
			}
			$rs->free();
		}
	}

    public function verifyCollRecords($collId){
		$sql = '';
		$recArr = array();
		$sql = 'SELECT COUNT(CASE WHEN ISNULL(o.occurrenceID) THEN o.occid ELSE NULL END) AS nullOccurID, '.
			'COUNT(CASE WHEN ISNULL(o.basisOfRecord) THEN o.occid ELSE NULL END) AS nullBasisRec, '.
			'COUNT(CASE WHEN ISNULL(o.catalogNumber) THEN o.occid ELSE NULL END) AS nullCatNum, '.
			'COUNT(CASE WHEN ISNULL(g.guid) THEN o.occid ELSE NULL END) AS nullSymUUID '.
			'FROM omoccurrences AS o LEFT JOIN guidoccurrences AS g ON o.occid = g.occid '.
			'WHERE o.collid = '.$collId;
		//echo 'SQL: '.$sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$recArr['nullOccurID'] = $r->nullOccurID;
			$recArr['nullBasisRec'] = $r->nullBasisRec;
			$recArr['nullCatNum'] = $r->nullCatNum;
			$recArr['nullSymUUID'] = $r->nullSymUUID;
		}
		$rs->free();
		
		return $recArr;
	}

	public function getCollArr(){
		return $this->collArr;
	}

	public function setCustomWhereSql($sql){
		$this->customWhereSql = $sql;
	}
	
	public function addCondition($field, $cond, $value = ''){
		//Sanitation
		$cond = strtoupper(trim($cond));
		if(!preg_match('/^[A-Za-z]+$/',$field)) return false;
		if(!preg_match('/^[A-Z]+$/',$cond)) return false;
		//Set condition
		if($field){
			if(!$cond) $cond = 'EQUALS';
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
						$sqlFrag2 .= 'OR o.'.$field.' IS NULL ';
					}
					elseif($cond == 'NOTNULL'){
						$sqlFrag2 .= 'OR o.'.$field.' IS NOT NULL ';
					}
					elseif($cond == 'EQUALS'){
						$sqlFrag2 .= 'OR o.'.$field.' IN("'.implode('","',$valueArr).'") ';
					}
					else{
						foreach($valueArr as $value){
							if($cond == 'STARTS'){
								$sqlFrag2 .= 'OR o.'.$field.' LIKE "'.$value.'%" ';
							}
							elseif($cond == 'LIKE'){ 
								$sqlFrag2 .= 'OR o.'.$field.' LIKE "%'.$value.'%" ';
							}
							elseif($cond == 'LESSTHAN'){ 
								$sqlFrag2 .= 'OR o.'.$field.' < "'.$value.'" ';
							}
							elseif($cond == 'GREATERTHAN'){ 
								$sqlFrag2 .= 'OR o.'.$field.' > "'.$value.'" ';
							}
						}
					}
				}
				if($sqlFrag2) $sqlFrag .= 'AND ('.substr($sqlFrag2,3).') ';
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

    public function getAsJson() {
        $this->schemaType='dwc';
        $arr = $this->getDwcArray();
        return json_encode($arr[0]);
    }

    /** 
     * Render the records as RDF in a turtle serialization following the TDWG
     *  DarwinCore RDF Guide.
     *
     * @return strin containing turtle serialization of selected dwc records.
     */
    public function getAsTurtle() { 
       $debug = false;
       $returnvalue  = "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
       $returnvalue .= "@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\n";
       $returnvalue .= "@prefix owl: <http://www.w3.org/2002/07/owl#> .\n";
       $returnvalue .= "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n";
       $returnvalue .= "@prefix dwc: <http://rs.tdwg.org/dwc/terms/> .\n";
       $returnvalue .= "@prefix dwciri: <http://rs.tdwg.org/dwc/iri/> .\n";
       $returnvalue .= "@prefix dc: <http://purl.org/dc/elements/1.1/> . \n";
       $returnvalue .= "@prefix dcterms: <http://purl.org/dc/terms/> . \n";
       $returnvalue .= "@prefix dcmitype: <http://purl.org/dc/dcmitype/> . \n";
       $this->schemaType='dwc';
       $arr = $this->getDwcArray();
	   $occurTermArr = $this->occurrenceFieldArr['terms'];
       $dwcguide223 = "";
       foreach ($arr as $rownum => $dwcArray)  {
          if ($debug) { print_r($dwcArray);  } 
          if (isset($dwcArray['occurrenceID'])||(isset($dwcArray['catalogNumber']) && isset($dwcArray['collectionCode']))) { 
             $occurrenceid = $dwcArray['occurrenceID'];
             if (UuidFactory::is_valid($occurrenceid)) { 
                $occurrenceid = "urn:uuid:$occurrenceid";
             } else {
                $catalogNumber = $dwcArray['catalogNumber'];
                if (strlen($occurrenceid)==0 || $occurrenceid==$catalogNumber) {
                    // If no occurrenceID is present, construct one with a urn:catalog: scheme.
                    // Pathology may also exist of an occurrenceID equal to the catalog number, fix this.
                    $institutionCode = $dwcArray['institutionCode'];
                    $collectionCode = $dwcArray['collectionCode'];
                    $occurrenceid = "urn:catalog:$institutionCode:$collectionCode:$catalogNumber";
                }
             }
             $returnvalue .= "<$occurrenceid>\n";
             $returnvalue .= "    a dwc:Occurrence ";
             $separator = " ; \n ";
             foreach($dwcArray as $key => $value) { 
                if (strlen($value)>0) { 
                  switch ($key) {
                    case "recordId": 
                    case "occurrenceID": 
                    case "verbatimScientificName":
                         // skip
                      break;
                    case "collectionID":
                         // RDF Guide Section 2.3.3 owl:sameAs for urn:lsid and resolvable IRI.
                         if (stripos("urn:uuid:",$value)===false && UuidFactory::is_valid($value)) { 
                           $lsid = "urn:uuid:$value";
                         } elseif (stripos("urn:lsid:biocol.org",$value)===0) { 
                           $lsid = "http://biocol.org/$value";
                           $dwcguide223 .= "<http://biocol.org/$value>\n";
                           $dwcguide223 .= "    owl:sameAs <$value> .\n";
                         } else { 
                           $lsid = $value;
                         }
                         $returnvalue .= "$separator   dwciri:inCollection <$lsid>";
                      break;
                    case "basisOfRecord": 
                          if (preg_match("/(PreservedSpecimen|FossilSpecimen)/",$value)==1) { 
                             $returnvalue .= "$separator   a dcmitype:PhysicalObject";
                          }
                          $returnvalue .= "$separator   dwc:$key  \"$value\"";
                      break;
                    case "modified":
                         $returnvalue .= "$separator   dcterms:$key \"$value\"";
                      break;
                    case "rights":
                          // RDF Guide Section 3.3 dcterms:licence for IRI, xmpRights:UsageTerms for literal
                          if (stripos("http://creativecommons.org/licenses/",$value)==0) { 
                             $returnvalue .= "$separator   dcterms:license <$value>";
                          } else { 
                             $returnvalue .= "$separator   dc:$key \"$value\"";
                          }
                      break;
                    case "rightsHolder":
                          // RDF Guide Section 3.3  dcterms:rightsHolder for IRI, xmpRights:Owner for literal
                          if (stripos("http://",$value)==0 || stripos("urn:",$value)==0) { 
                             $returnvalue .= "$separator   dcterms:rightsHolder <$value>";
                          } else { 
                             $returnvalue .= "$separator   xmpRights:Owner \"$value\"";
                          }
                      break;
                    case "day":
                    case "month":
                    case "year":
                         if ($value!="0") { 
                           $returnvalue .= "$separator   dwc:$key  \"$value\"";
                         }
                      break;
                    case "eventDate":
                         if ($value!="0000-00-00" && strlen($value)>0) { 
                           $value = str_replace("-00","",$value);
                           $returnvalue .= "$separator   dwc:$key  \"$value\"";
                         }
                      break;
                    default: 
                        if (isset($occurTermArr[$key])) { 
                           $ns = RdfUtility::namespaceAbbrev($occurTermArr[$key]);
                           $returnvalue .= $separator . "   " . $ns . " \"$value\"";
                        }
                  }
                }
             }
         
             $returnvalue .= ".\n";
          }
       }
       if ($dwcguide223!="") { 
          $returnvalue .= $dwcguide223;
       }
       return $returnvalue;
    }

    /** 
     * Render the records as RDF in a rdf/xml serialization following the TDWG
     *  DarwinCore RDF Guide.
     *
     * @return string containing rdf/xml serialization of selected dwc records.
     */
    public function getAsRdfXml() { 
       $debug = false;
	   $newDoc = new DOMDocument('1.0',$this->charSetOut);
       $newDoc->formatOutput = true;

       $rootElem = $newDoc->createElement('rdf:RDF');
       $rootElem->setAttribute('xmlns:rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');
       $rootElem->setAttribute('xmlns:rdfs','http://www.w3.org/2000/01/rdf-schema#');
       $rootElem->setAttribute('xmlns:owl','http://www.w3.org/2002/07/owl#');
       $rootElem->setAttribute('xmlns:foaf','http://xmlns.com/foaf/0.1/');
       $rootElem->setAttribute('xmlns:dwc','http://rs.tdwg.org/dwc/terms/');
       $rootElem->setAttribute('xmlns:dwciri','http://rs.tdwg.org/dwc/iri/');
       $rootElem->setAttribute('xmlns:dc','http://purl.org/dc/elements/1.1/');
       $rootElem->setAttribute('xmlns:dcterms','http://purl.org/dc/terms/');
       $rootElem->setAttribute('xmlns:dcmitype','http://purl.org/dc/dcmitype/');
       $newDoc->appendChild($rootElem);

       $this->schemaType='dwc';
       $arr = $this->getDwcArray();
	   $occurTermArr = $this->occurrenceFieldArr['terms'];
       foreach ($arr as $rownum => $dwcArray)  {
          if ($debug) { print_r($dwcArray);  } 
          if (isset($dwcArray['occurrenceID'])||(isset($dwcArray['catalogNumber']) && isset($dwcArray['collectionCode']))) { 
             $occurrenceid = $dwcArray['occurrenceID'];
             if (UuidFactory::is_valid($occurrenceid)) { 
                $occurrenceid = "urn:uuid:$occurrenceid";
             } else {
                $catalogNumber = $dwcArray['catalogNumber'];
                if (strlen($occurrenceid)==0 || $occurrenceid==$catalogNumber) {
                    // If no occurrenceID is present, construct one with a urn:catalog: scheme.
                    // Pathology may also exist of an occurrenceID equal to the catalog number, fix this.
                    $institutionCode = $dwcArray['institutionCode'];
                    $collectionCode = $dwcArray['collectionCode'];
                    $occurrenceid = "urn:catalog:$institutionCode:$collectionCode:$catalogNumber";
                }
             }
             $occElem = $newDoc->createElement('dwc:Occurrence');
             $occElem->setAttribute("rdf:about","$occurrenceid");
             $sameAsElem = null;
             foreach($dwcArray as $key => $value) { 
                $flags = ENT_NOQUOTES;
                if(defined('ENT_XML1')) $flags = ENT_NOQUOTES | ENT_XML1 | ENT_DISALLOWED;
                $value = htmlentities($value,$flags,$this->charSetOut);
                // TODO: Figure out how to use mb_encode_numericentity() here.
                $value = str_replace("&copy;","&#169;",$value);  // workaround, need to fix &copy; rendering
                if (strlen($value)>0) { 
                  $elem = null;
                  switch ($key) {
                    case "recordId": 
                    case "occurrenceID": 
                    case "verbatimScientificName":
                         // skip
                      break;
                    case "collectionID":
                         // RDF Guide Section 2.3.3 owl:sameAs for urn:lsid and resolvable IRI.
                         if (stripos("urn:uuid:",$value)===false && UuidFactory::is_valid($value)) { 
                           $lsid = "urn:uuid:$value";
                         }elseif (stripos("urn:lsid:biocol.org",$value)===0) { 
                           $lsid = "http://biocol.org/$value";
                           $sameAsElem = $newDoc->createElement("rdf:Description");
                           $sameAsElem->setAttribute("rdf:about","http://biocol.org/$value");
                           $sameAsElemC = $newDoc->createElement("owl:sameAs");
                           $sameAsElemC->setAttribute("rdf:resource","$value");
                           $sameAsElem->appendChild($sameAsElemC);
                         } else { 
                           $lsid = $value;
                         }
                         $elem = $newDoc->createElement("dwciri:inCollection");
                         $elem->setAttribute("rdf:resource","$lsid");
                      break;
                    case "basisOfRecord": 
                          if (preg_match("/(PreservedSpecimen|FossilSpecimen)/",$value)==1) { 
                             $elem = $newDoc->createElement("rdf:type");
                             $elem->setAttribute("rdf:resource","http://purl.org/dc/dcmitype/PhysicalObject");
                          }
                          $elem = $newDoc->createElement("dwc:$key",$value);
                      break;
                    case "rights":
                          // RDF Guide Section 3.3 dcterms:licence for IRI, xmpRights:UsageTerms for literal
                          if (stripos("http://creativecommons.org/licenses/",$value)==0) { 
                             $elem = $newDoc->createElement("dcterms:license");
                             $elem->setAttribute("rdf:resource","$value");
                          } else { 
                             $elem = $newDoc->createElement("xmpRights:UsageTerms",$value);
                          }
                      break;
                    case "rightsHolder":
                          // RDF Guide Section 3.3  dcterms:rightsHolder for IRI, xmpRights:Owner for literal
                          if (stripos("http://",$value)==0 || stripos("urn:",$value)==0) { 
                             $elem = $newDoc->createElement("dcterms:rightsHolder");
                             $elem->setAttribute("rdf:resource","$value");
                          } else { 
                             $elem = $newDoc->createElement("xmpRights:Owner",$value);
                          }
                      break;
                    case "modified":
                          $elem = $newDoc->createElement("dcterms:$key",$value);
                      break;
                    case "day":
                    case "month":
                    case "year":
                         if ($value!="0") { 
                            $elem = $newDoc->createElement("dwc:$key",$value);
                         }
                      break;
                    case "eventDate":
                         if ($value!="0000-00-00" || strlen($value)>0) { 
                           $value = str_replace("-00","",$value);
                           $elem = $newDoc->createElement("dwc:$key",$value);
                         }
                      break;
                    default: 
                         if (isset($occurTermArr[$key])) { 
                            $ns = RdfUtility::namespaceAbbrev($occurTermArr[$key]);
                            $elem = $newDoc->createElement($ns);
                            $elem->appendChild($newDoc->createTextNode($value));
                         }
                  }
                  if ($elem!=null) { 
                     $occElem->appendChild($elem);
                  }
                }
             }
             $node = $newDoc->importNode($occElem);
             $newDoc->documentElement->appendChild($node);
             if ($sameAsElem!=null) { 
                $node = $newDoc->importNode($sameAsElem);
                $newDoc->documentElement->appendChild($node);
             }
             // For many matching rows this is a point where partial serialization could occur
             // to prevent creation of a large DOM model in memmory.
          }
       }
       $returnvalue = $newDoc->saveXML();
       return $returnvalue;
    }

    private function getDwcArray() { 
		$result = Array();
		if(!$this->occurrenceFieldArr){
			$this->initOccurrenceArr();
		}
		
		$sql = $this->getSqlOccurrences();
		if(!$sql) return false;
		$fieldArr = $this->occurrenceFieldArr['fields'];
		if($this->schemaType == 'dwc'){
			unset($fieldArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($fieldArr['collId']);
		}
		if(!$this->collArr){
			//Collection array not previously primed by source  
			$sql1 = 'SELECT DISTINCT o.collid FROM omoccurrences o ';
			if($this->conditionSql){
				if(stripos($this->conditionSql,'v.clid')){
					$sql1 .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
				}
				if(stripos($this->conditionSql,'p.point')){
					$sql1 .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
				}
				$sql1 .= $this->conditionSql;
			}
			$rs1 = $this->conn->query($sql1);
			$collidStr = '';
			while($r1 = $rs1->fetch_object()){
				$collidStr .= ','.$r1->collid;
			}
			$rs1->free();
			if($collidStr) $this->setCollArr(trim($collidStr,','));
		}

		//Populate Upper Taxonomic data
		$this->setUpperTaxonomy();
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			if(!$this->serverDomain){
				$this->serverDomain = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
				$this->serverDomain .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
			}
			$urlPathPrefix = '';
			if($this->serverDomain){
				$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			}
			$hasRecords = false;
			$cnt = 0;
			while($r = $rs->fetch_assoc()){
				$hasRecords = true;
				//Protect sensitive records
				if($this->redactLocalities 
                   && $r["localitySecurity"] == 1 
                   && !in_array($r['collid'],$this->rareReaderArr)
                ){
					$protectedFields = array();
					foreach($this->securityArr as $v){
						if(array_key_exists($v,$r) && $r[$v]){
							$r[$v] = '';
							$protectedFields[] = $v;
						}
					}
					if($protectedFields){
						$r['informationWithheld'] = trim($r['informationWithheld'].'; field values redacted: '.implode(', ',$protectedFields),' ;');
					}
				}
				if(!$r['occurrenceID']){
					//Set occurrence GUID based on GUID target, but only if occurrenceID field isn't already populated
					$guidTarget = $this->collArr[$r['collid']]['guidtarget'];
					if($guidTarget == 'catalogNumber'){
						$r['occurrenceID'] = $r['catalogNumber'];
					}
					elseif($guidTarget == 'symbiotaUUID'){
						$r['occurrenceID'] = $r['recordId'];
					}
				}
				
				$r['recordId'] = 'urn:uuid:'.$r['recordId'];
				//Add collection GUID based on management type
				$managementType = $this->collArr[$r['collid']]['managementtype'];
				if($managementType && $managementType == 'Live Data'){
					if(array_key_exists('collectionID',$r) && !$r['collectionID']){
						$guid = $this->collArr[$r['collid']]['collectionguid'];
						if(strlen($guid) == 36) $guid = 'urn:uuid:'.$guid;
						$r['collectionID'] = $guid;
					}
				}
				if($this->schemaType == 'dwc'){
					unset($r['localitySecurity']);
				}
				if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
					unset($r['collid']);
				}
				//Add upper taxonomic data
				if($r['family'] && $this->upperTaxonomy){
					$famStr = strtolower($r['family']);
					if(isset($this->upperTaxonomy[$famStr]['o'])){
						$r['t_order'] = $this->upperTaxonomy[$famStr]['o'];
					}
					if(isset($this->upperTaxonomy[$famStr]['c'])){
						$r['t_class'] = $this->upperTaxonomy[$famStr]['c'];
					}
					if(isset($this->upperTaxonomy[$famStr]['p'])){
						$r['t_phylum'] = $this->upperTaxonomy[$famStr]['p'];
					}
					if(isset($this->upperTaxonomy[$famStr]['k'])){
						$r['t_kingdom'] = $this->upperTaxonomy[$famStr]['k'];
					}
				}
				if($urlPathPrefix) $r['t_references'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				
				foreach($r as $rKey => $rValue){
					if(substr($rKey, 0, 2) == 't_') $rKey = substr($rKey,2);
	                $result[$cnt][$rKey] = $rValue;
				}
				$cnt++;
			}
			$rs->free();
			$result[0]['associatedMedia'] = $this->getAssociatedMedia();
		}
		else{
			$this->logOrEcho("ERROR creating occurrence file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
		return $result;
    }
    
    private function getAssociatedMedia(){
    	$retStr = '';
    	$sql = 'SELECT originalurl FROM images '.str_replace('o.','',$this->conditionSql);
    	$rs = $this->conn->query($sql);
    	while($r = $rs->fetch_object()){
    		$retStr .= ';'.$r->originalurl;
    	}
    	$rs->free();
    	return trim($retStr,';');
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
			$errStr = "<span style='color:red;'>FAILED to create archive file due to failure to return occurrence records. ".
				"Note that OccurrenceID GUID assignments are required for Darwin Core Archive publishing. ".
				"Symbiota GUID (recordID) assignments are also required, which can be verified by the portal manager through running the GUID mapping utilitiy available in sitemap</span>";
			$this->logOrEcho($errStr);
			$collid = key($this->collArr);
			if($collid) $this->deleteArchive($collid);
			unset($this->collArr[$collid]);
		}
		$this->logOrEcho("\n-----------------------------------------------------\n");
		return $archiveFile;
	}

	//Generate DwC support files
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
			unset($termArr['collId']);
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
		
		if(!$this->serverDomain){
			$this->serverDomain = "http://";
			if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
			$this->serverDomain .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
		}
		$urlPathPrefix = '';
		if($this->serverDomain){
			$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
		}
		$localDomain = $this->serverDomain;
		
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
			$emlArr['title'] = $GLOBALS['DEFAULT_TITLE'].' general data extract';
		}
		if(isset($GLOBALS['USER_DISPLAY_NAME']) && $GLOBALS['USER_DISPLAY_NAME']){
			//$emlArr['creator'][0]['individualName'] = $GLOBALS['USER_DISPLAY_NAME'];
			$emlArr['associatedParty'][0]['individualName'] = $GLOBALS['USER_DISPLAY_NAME'];
			$emlArr['associatedParty'][0]['role'] = 'CONTENT_PROVIDER';
		}

		if(array_key_exists('PORTAL_GUID',$GLOBALS) && $GLOBALS['PORTAL_GUID']){
			$emlArr['creator'][0]['attr']['id'] = $GLOBALS['PORTAL_GUID'];
		}
		$emlArr['creator'][0]['organizationName'] = $GLOBALS['DEFAULT_TITLE'];
		$emlArr['creator'][0]['electronicMailAddress'] = $GLOBALS['ADMIN_EMAIL'];
		$emlArr['creator'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['metadataProvider'][0]['organizationName'] = $GLOBALS['DEFAULT_TITLE'];
		$emlArr['metadataProvider'][0]['electronicMailAddress'] = $GLOBALS['ADMIN_EMAIL'];
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
		$emlArr = $this->utf8EncodeArr($emlArr);
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
        global $RIGHTS_TERMS_DEFS;
        $usageTermArr = Array();

        if(!$emlArr) $emlArr = $this->getEmlArr();
        foreach($RIGHTS_TERMS_DEFS as $k => $v){
            if($k == $emlArr['intellectualRights']){
                $usageTermArr = $v;
            }
        }

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
            $paraElem->appendChild($newDoc->createTextNode('To the extent possible under law, the publisher has waived all rights to these data and has dedicated them to the'));
            $ulinkElem = $newDoc->createElement('ulink');
            $citetitleElem = $newDoc->createElement('citetitle');
            $citetitleElem->appendChild($newDoc->createTextNode((array_key_exists('title',$usageTermArr)?$usageTermArr['title']:'')));
            $ulinkElem->appendChild($citetitleElem);
            $ulinkElem->setAttribute('url',(array_key_exists('url',$usageTermArr)?$usageTermArr['url']:$emlArr['intellectualRights']));
            $paraElem->appendChild($ulinkElem);
            $paraElem->appendChild($newDoc->createTextNode((array_key_exists('def',$usageTermArr)?$usageTermArr['def']:'')));
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
				$collArr = $this->utf8EncodeArr($collArr);
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
		if($this->schemaType == 'coge' && $this->geolocateVariables){
			if(!$this->serverDomain){
				$this->serverDomain = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
				$this->serverDomain .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
			}
			$urlPathPrefix = '';
			if($this->serverDomain){
				$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
				$urlPathPrefix .= 'collections/individual/index.php';
				//Add Geolocate metadata
				$glElem = $newDoc->createElement('geoLocate');
				$glElem->appendChild($newDoc->createElement('dataSourcePrimaryName',$this->geolocateVariables['cogename']));
				$glElem->appendChild($newDoc->createElement('dataSourceSecondaryName',$this->geolocateVariables['cogedescr']));
				$glElem->appendChild($newDoc->createElement('targetCommunityName',$this->geolocateVariables['cogecomm']));
				#if(isset($this->geolocateVariables['targetcommunityidentifier'])) $glElem->appendChild($newDoc->createElement('targetCommunityIdentifier',''));
				$glElem->appendChild($newDoc->createElement('specimenHyperlinkBase',$urlPathPrefix));
				$glElem->appendChild($newDoc->createElement('specimenHyperlinkParameter','occid'));
				$glElem->appendChild($newDoc->createElement('specimenHyperlinkValueField','Id'));
				$metaElem->appendChild($glElem);
			}
		}
		$addMetaElem = $newDoc->createElement('additionalMetadata');
		$addMetaElem->appendChild($metaElem);
		$rootElem->appendChild($addMetaElem);

		return $newDoc;
	}

	//Generate Data files
	private function writeOccurrenceFile(){
		$this->logOrEcho("Creating occurrence file (".date('h:i:s A').")... ");
		$filePath = $this->targetPath.$this->ts.'-occur'.$this->fileExt;
		$fh = fopen($filePath, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}
		$hasRecords = false;
		
		if(!$this->occurrenceFieldArr){
			$this->initOccurrenceArr();
		}
		
		//Output records
		$sql = $this->getSqlOccurrences();
		if(!$sql) return false;
		//Output header
		$fieldArr = $this->occurrenceFieldArr['fields'];
		if($this->schemaType == 'dwc'){
			unset($fieldArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($fieldArr['collId']);
		}
		$fieldOutArr = array();
		if($this->schemaType == 'coge'){
			//Convert to GeoLocate flavor
			$glFields = array('specificEpithet'=>'Species','scientificNameAuthorship'=>'ScientificNameAuthor','recordedBy'=>'Collector','recordNumber'=>'CollectorNumber',
				'year'=>'YearCollected','month'=>'MonthCollected','day'=>'DayCollected','decimalLatitude'=>'Latitude','decimalLongitude'=>'Longitude',
				'minimumElevationInMeters'=>'MinimumElevation','maximumElevationInMeters'=>'MaximumElevation','maximumDepthInMeters'=>'MaximumDepth','minimumDepthInMeters'=>'MinimumDepth',
				'occurrenceRemarks'=>'Notes','dateEntered','dateLastModified','collId','recordId','references');
			foreach($fieldArr as $k => $v){
				if(array_key_exists($k,$glFields)){
					$fieldOutArr[] = $glFields[$k];
				} 
				else{
					$fieldOutArr[] = strtoupper(substr($k,0,1)).substr($k,1);
				}
			}
		}
		else{
			$fieldOutArr = array_keys($fieldArr);
		}
		$this->writeOutRecord($fh,$fieldOutArr);
		if(!$this->collArr){
			//Collection array not previously primed by source  
			$sql1 = 'SELECT DISTINCT o.collid FROM omoccurrences o ';
			if($this->conditionSql){
				if(stripos($this->conditionSql,'v.clid')){
					$sql1 .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
				}
				if(stripos($this->conditionSql,'p.point')){
					$sql1 .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
				}
				$sql1 .= $this->conditionSql;
			}
			$rs1 = $this->conn->query($sql1);
			$collidStr = '';
			while($r1 = $rs1->fetch_object()){
				$collidStr .= ','.$r1->collid;
			}
			$rs1->free();
			if($collidStr) $this->setCollArr(trim($collidStr,','));
		}

		//Populate Upper Taxonomic data
		$this->setUpperTaxonomy();
		
		//echo $sql; exit;
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			if(!$this->serverDomain){
				$this->serverDomain = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
				$this->serverDomain .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
			}
			$urlPathPrefix = '';
			if($this->serverDomain){
				$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			}
			
			while($r = $rs->fetch_assoc()){
				//Set occurrence GUID based on GUID target
				$guidTarget = $this->collArr[$r['collid']]['guidtarget'];
				if($guidTarget == 'catalogNumber'){
					$r['occurrenceID'] = $r['catalogNumber'];
				}
				elseif($guidTarget == 'symbiotaUUID'){
					$r['occurrenceID'] = $r['recordId'];
				}
				if($this->limitToGuids && (!$r['occurrenceID'] || !$r['basisOfRecord'])){
					// Skip record because there is no occurrenceID guid
					continue;
				}
				$hasRecords = true;
				//Protect sensitive records
				if($this->redactLocalities && $r["localitySecurity"] == 1 && !in_array($r['collid'],$this->rareReaderArr)){
					$protectedFields = array();
					foreach($this->securityArr as $v){
						if(array_key_exists($v,$r) && $r[$v]){
							$r[$v] = '';
							$protectedFields[] = $v;
						}
					}
					if($protectedFields){
						$r['informationWithheld'] = trim($r['informationWithheld'].'; field values redacted: '.implode(', ',$protectedFields),' ;');
					}
				}
				
				if($urlPathPrefix) $r['t_references'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				$r['recordId'] = 'urn:uuid:'.$r['recordId'];
				//Add collection GUID based on management type
				$managementType = $this->collArr[$r['collid']]['managementtype'];
				if($managementType && $managementType == 'Live Data'){
					if(array_key_exists('collectionID',$r) && !$r['collectionID']){
						$guid = $this->collArr[$r['collid']]['collectionguid'];
						if(strlen($guid) == 36) $guid = 'urn:uuid:'.$guid;
						$r['collectionID'] = $guid;
					}
				}
				if($this->schemaType == 'dwc'){
					unset($r['localitySecurity']);
				}
				if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
					unset($r['collid']);
				}
				//Add upper taxonomic data
				if($r['family'] && $this->upperTaxonomy){
					$famStr = strtolower($r['family']);
					if(isset($this->upperTaxonomy[$famStr]['o'])){
						$r['t_order'] = $this->upperTaxonomy[$famStr]['o'];
					}
					if(isset($this->upperTaxonomy[$famStr]['c'])){
						$r['t_class'] = $this->upperTaxonomy[$famStr]['c'];
					}
					if(isset($this->upperTaxonomy[$famStr]['p'])){
						$r['t_phylum'] = $this->upperTaxonomy[$famStr]['p'];
					}
					if(isset($this->upperTaxonomy[$famStr]['k'])){
						$r['t_kingdom'] = $this->upperTaxonomy[$famStr]['k'];
					}
				} 
				//print_r($r); exit;
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating occurrence file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}

		fclose($fh);
		if(!$hasRecords){
			$filePath = false;
			//$this->writeOutRecord($fh,array('No records returned. Modify query variables to be more inclusive.'));
			$this->logOrEcho("No records returned. Modify query variables to be more inclusive. \n");
		}
		$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
		return $filePath;
	}
	
	public function getOccurrenceFile(){
		if(!$this->targetPath) $this->setTargetPath();
		$filePath = $this->writeOccurrenceFile();
		return $filePath;
	}
	
	private function writeDeterminationFile(){
		$this->logOrEcho("Creating identification file (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-det'.$this->fileExt, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}
		
		if(!$this->determinationFieldArr){
			$this->initDeterminationArr();
		}
		//Output header
		$this->writeOutRecord($fh,array_keys($this->determinationFieldArr['fields']));
		
		//Output records
		$sql = $this->getSqlDeterminations();
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				$r['recordId'] = 'urn:uuid:'.$r['recordId'];
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

		$this->logOrEcho("Creating image file (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-images'.$this->fileExt, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}

		if(!$this->imageFieldArr){
			$this->initImageArr();
		}
		
		//Output header
		$this->writeOutRecord($fh,array_keys($this->imageFieldArr['fields']));
		
		//Output records
		$sql = $this->getSqlImages();
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			
			if(!$this->serverDomain){
				$this->serverDomain = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
				$this->serverDomain .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
			}
			$urlPathPrefix = '';
			if($this->serverDomain){
				$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			}
			
			$localDomain = '';
			if(isset($GLOBALS['IMAGE_DOMAIN']) && $GLOBALS['IMAGE_DOMAIN']){
				$localDomain = $GLOBALS['IMAGE_DOMAIN'];
			}
			else{
				$localDomain = $this->serverDomain;
			}

			while($r = $rs->fetch_assoc()){
				if(substr($r['identifier'],0,1) == '/') $r['identifier'] = $localDomain.$r['identifier'];
				if(substr($r['accessURI'],0,1) == '/') $r['accessURI'] = $localDomain.$r['accessURI'];
				if(substr($r['thumbnailAccessURI'],0,1) == '/') $r['thumbnailAccessURI'] = $localDomain.$r['thumbnailAccessURI'];
				if(substr($r['goodQualityAccessURI'],0,1) == '/') $r['goodQualityAccessURI'] = $localDomain.$r['goodQualityAccessURI'];

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
				$r['providermanagedid'] = 'urn:uuid:'.$r['providermanagedid'];
				$r['associatedSpecimenReference'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				$r['type'] = 'StillImage';
				$r['subtype'] = 'Photograph';
				$extStr = strtolower(substr($r['accessURI'],strrpos($r['accessURI'],'.')+1));
				if($r['format'] == ''){
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
				}
				$r['metadataLanguage'] = 'en';
				//Load record array into output file
				//$this->encodeArr($r);
				//$this->addcslashesArr($r);
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
		$status = false;
		$this->logOrEcho("Starting batch process (".date('Y-m-d h:i:s A').")\n");
		$this->logOrEcho("\n-----------------------------------------------------\n\n");
		
		$successArr = array();
		foreach($collIdArr as $id){
			//Create a separate DWCA object for each collection
			$this->resetCollArr($id);
			if($this->createDwcArchive()){
				$successArr[] = $id;
				$status = true;
			}
		}
		//Reset $this->collArr with all the collections ran successfully and then rebuild the RSS feed 
		$this->resetCollArr(implode(',',$successArr));
		$this->writeRssFile();
		$this->logOrEcho("Batch process finished! (".date('Y-m-d h:i:s A').") \n");
		return $status;
	}
	
	public function writeRssFile(){

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
		$titleElem->appendChild($newDoc->createTextNode($GLOBALS['DEFAULT_TITLE'].' Darwin Core Archive rss feed'));
		$channelElem->appendChild($titleElem);
		
		if(!$this->serverDomain){
			$this->serverDomain = "http://";
			if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
			$this->serverDomain .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
		}
		$urlPathPrefix = '';
		if($this->serverDomain){
			$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
		}
		$localDomain = $this->serverDomain;
		
		$linkElem = $newDoc->createElement('link');
		$linkElem->appendChild($newDoc->createTextNode($urlPathPrefix));
		$channelElem->appendChild($linkElem);
		$descriptionElem = $newDoc->createElement('description');
		$descriptionElem->appendChild($newDoc->createTextNode($GLOBALS['DEFAULT_TITLE'].' Darwin Core Archive rss feed'));
		$channelElem->appendChild($descriptionElem);
		$languageElem = $newDoc->createElement('language','en-us');
		$channelElem->appendChild($languageElem);

		//Create new item for target archives and load into array
		$itemArr = array();
		foreach($this->collArr as $collId => $cArr){
			$cArr = $this->utf8EncodeArr($cArr);
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
			$fileNameSeed = str_replace(array(' ','"',"'"),'',$instCode).'_DwC-A';
			
			$emlElem = $newDoc->createElement('emllink');
			$emlElem->appendChild($newDoc->createTextNode($urlPathPrefix.'collections/datasets/dwc/'.$fileNameSeed.'.eml'));
			$itemElem->appendChild($emlElem);
			//type
			$typeTitleElem = $newDoc->createElement('type','DWCA');
			$itemElem->appendChild($typeTitleElem);
			//recordType
			$recTypeTitleElem = $newDoc->createElement('recordType','DWCA');
			$itemElem->appendChild($recTypeTitleElem);
			//link
			$linkTitleElem = $newDoc->createElement('link');
			$linkTitleElem->appendChild($newDoc->createTextNode($urlPathPrefix.'collections/datasets/dwc/'.$fileNameSeed.'.zip'));
			$itemElem->appendChild($linkTitleElem);
			//pubDate
			//$dsStat = stat($this->targetPath.$instCode.'_DwC-A.zip');
			$pubDateTitleElem = $newDoc->createElement('pubDate');
			$pubDateTitleElem->appendChild($newDoc->createTextNode(date("D, d M Y H:i:s")));
			$itemElem->appendChild($pubDateTitleElem);
			$itemArr[$title] = $itemElem;
		}

		//Add existing items
		$rssFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'webservices/dwc/rss.xml';
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
		$rssFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		if(!file_exists($rssFile)) return false;
		$doc = new DOMDocument();
		$doc->load($rssFile);
		$cElem = $doc->getElementsByTagName("channel")->item(0);
		$items = $cElem->getElementsByTagName("item");
		foreach($items as $i){
			if($i->getAttribute('collid') == $collId){
				$link = $i->getElementsByTagName("link");
				$nodeValue = $link->item(0)->nodeValue;
				$filePath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/');
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
		$retArr = Array();
		$rssFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'webservices/dwc/rss.xml';
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

	private function setUpperTaxonomy(){
		if(!$this->upperTaxonomy){
			$sqlOrder = 'SELECT t.sciname AS family, t2.sciname AS taxonorder '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '. 
				'WHERE t.rankid = 140 AND t2.rankid = 100';
			$rsOrder = $this->conn->query($sqlOrder);
			while($rowOrder = $rsOrder->fetch_object()){
				$this->upperTaxonomy[strtolower($rowOrder->family)]['o'] = $rowOrder->taxonorder;
			}
			$rsOrder->free();
			
			$sqlClass = 'SELECT t.sciname AS family, t2.sciname AS taxonclass '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 60';
			$rsClass = $this->conn->query($sqlClass);
			while($rowClass = $rsClass->fetch_object()){
				$this->upperTaxonomy[strtolower($rowClass->family)]['c'] = $rowClass->taxonclass;
			}
			$rsClass->free();
			
			$sqlPhylum = 'SELECT t.sciname AS family, t2.sciname AS taxonphylum '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 30';
			$rsPhylum = $this->conn->query($sqlPhylum);
			while($rowPhylum = $rsPhylum->fetch_object()){
				$this->upperTaxonomy[strtolower($rowPhylum->family)]['p'] = $rowPhylum->taxonphylum;
			}
			$rsPhylum->free();
			
			$sqlKing = 'SELECT t.sciname AS family, t2.sciname AS kingdom '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 10';
			$rsKing = $this->conn->query($sqlKing);
			while($rowKing = $rsKing->fetch_object()){
				$this->upperTaxonomy[strtolower($rowKing->family)]['k'] = $rowKing->kingdom;
			}
			$rsKing->free();
		}
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
		$sql = 'SELECT c.collid, c.collectionname, CONCAT_WS("-",c.institutioncode,c.collectioncode) as instcode '.
			'FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid '.
			'WHERE c.colltype = "Preserved Specimens" AND s.recordcnt > 0 '.
			'ORDER BY c.collectionname ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->instcode.')';
		}
		return $retArr;
	}

	public function setVerbose($c){
		if($c){
			$this->verbose = true;
		}
	}

	public function setSchemaType($type){
		//dwc, symbiota, backup, coge
		if(in_array($type, array('dwc','backup','coge'))){
			$this->schemaType = $type;
		}
		else{
			$this->schemaType = 'symbiota';
		}
	}
	
	public function setLimitToGuids($testValue){
		if($testValue) $this->limitToGuids = true;
	}

	public function setExtended($e){
		$this->extended = $e;
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
	
	public function setGeolocateVariables($geolocateArr){
		$this->geolocateVariables = $geolocateArr;
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
	
	private function utf8EncodeArr($inArr){
		$retArr = $inArr;
		if($this->charSetSource == 'ISO-8859-1'){
			foreach($retArr as $k => $v){
				if(is_array($v)){
					$retArr[$k] = $this->utf8EncodeArr($v);
				}
				elseif(is_string($v)){
					if(mb_detect_encoding($v,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
						$retArr[$k] = utf8_encode($v);
					}
				}
				else{
					$retArr[$k] = $v;
				}
			}
		}
		return $retArr;
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
		if($inStr && $this->charSetSource){
			if($this->charSetOut == 'UTF-8' && $this->charSetSource == 'ISO-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($this->charSetOut == "ISO-8859-1" && $this->charSetSource == 'UTF-8'){
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
			if($v) $arr[$k] = addcslashes($v,"\n\r\\");
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