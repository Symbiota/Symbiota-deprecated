<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceDwcArchiver{

	private $conn;
	private $collId;
	private $nameTemplate;
	private $targetPath;
	private $zipArchive;
	private $logFH;
	private $silent = 0;

	private $occurrenceFieldArr;
	private $determinationFieldArr;
	private $imageFieldArr;
	private $securityArr = array();
	private $canReadRareSpp = false;
	
	public function __construct(){
		global $serverRoot, $userRights, $isAdmin;
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		$tsStr = time();

		$this->occurrenceFieldArr = array(
			'id' => '',
			'institutionCode' => 'http://rs.tdwg.org/dwc/terms/institutionCode',
			'collectionCode' => 'http://rs.tdwg.org/dwc/terms/collectionCode',
			'basisOfRecord' => 'http://rs.tdwg.org/dwc/terms/basisOfRecord',
			'catalogNumber' => 'http://rs.tdwg.org/dwc/terms/catalogNumber',
			'otherCatalogNumbers' => 'http://rs.tdwg.org/dwc/terms/otherCatalogNumbers',
			'ownerInstitutionCode' => 'http://rs.tdwg.org/dwc/terms/ownerInstitutionCode',
			'family' => 'http://rs.tdwg.org/dwc/terms/family',
			'scientificName' => 'http://rs.tdwg.org/dwc/terms/scientificName',
			'genus' => 'http://rs.tdwg.org/dwc/terms/genus',
			'specificEpithet' => 'http://rs.tdwg.org/dwc/terms/specificEpithet',
			'taxonRank' => 'http://rs.tdwg.org/dwc/terms/taxonRank',
			'infraspecificEpithet' => 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet',
			'scientificNameAuthorship' => 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship',
 			'taxonRemarks' => 'http://rs.tdwg.org/dwc/terms/taxonRemarks',
 			'identifiedBy' => 'http://rs.tdwg.org/dwc/terms/identifiedBy',
 			'dateIdentified' => 'http://rs.tdwg.org/dwc/terms/dateIdentified',
 			'identificationReferences' => 'http://rs.tdwg.org/dwc/terms/identificationReferences',
 			'identificationRemarks' => 'http://rs.tdwg.org/dwc/terms/identificationRemarks',
 			'identificationQualifier' => 'http://rs.tdwg.org/dwc/terms/identificationQualifier',
			'typeStatus' => 'http://rs.tdwg.org/dwc/terms/typeStatus',
			'recordedBy' => 'http://rs.tdwg.org/dwc/terms/recordedBy',
			'recordNumber' => 'http://rs.tdwg.org/dwc/terms/recordNumber',
			'eventDate' => 'http://rs.tdwg.org/dwc/terms/eventDate',
			'year' => 'http://rs.tdwg.org/dwc/terms/year',
			'month' => 'http://rs.tdwg.org/dwc/terms/month',
			'day' => 'http://rs.tdwg.org/dwc/terms/day',
			'startDayOfYear' => 'http://rs.tdwg.org/dwc/terms/startDayOfYear',
			'endDayOfYear' => 'http://rs.tdwg.org/dwc/terms/endDayOfYear',
 			'verbatimEventDate' => 'http://rs.tdwg.org/dwc/terms/verbatimEventDate',
 			'habitat' => 'http://rs.tdwg.org/dwc/terms/habitat',
 			'fieldNotes' => 'http://rs.tdwg.org/dwc/terms/fieldNotes',
 			'fieldNumber' => 'http://rs.tdwg.org/dwc/terms/fieldNumber',
 			'occurrenceRemarks' => 'http://rs.tdwg.org/dwc/terms/occurrenceRemarks',
			'informationWithheld' => 'http://rs.tdwg.org/dwc/terms/informationWithheld',
 			'dynamicProperties' => 'http://rs.tdwg.org/dwc/terms/dynamicProperties',
 			'associatedTaxa' => 'http://rs.tdwg.org/dwc/terms/associatedTaxa',
 			'reproductiveCondition' => 'http://rs.tdwg.org/dwc/terms/reproductiveCondition',
 			'cultivationStatus' => 'http://rs.tdwg.org/dwc/terms/cultivationStatus',
 			'establishmentMeans' => 'http://rs.tdwg.org/dwc/terms/establishmentMeans',
 			'lifeStage' => 'http://rs.tdwg.org/dwc/terms/lifeStage',
 			'sex' => 'http://rs.tdwg.org/dwc/terms/sex',
 			'individualCount' => 'http://rs.tdwg.org/dwc/terms/individualCount',
 			'samplingProtocol' => 'http://rs.tdwg.org/dwc/terms/samplingProtocol',
 			'preparations' => 'http://rs.tdwg.org/dwc/terms/preparations',
 			'country' => 'http://rs.tdwg.org/dwc/terms/country',
 			'stateProvince' => 'http://rs.tdwg.org/dwc/terms/stateProvince',
 			'county' => 'http://rs.tdwg.org/dwc/terms/county',
 			'municipality' => 'http://rs.tdwg.org/dwc/terms/municipality',
 			'locality' => 'http://rs.tdwg.org/dwc/terms/locality',
 			'decimalLatitude' => 'http://rs.tdwg.org/dwc/terms/decimalLatitude',
 			'decimalLongitude' => 'http://rs.tdwg.org/dwc/terms/decimalLongitude',
	 		'geodeticDatum' => 'http://rs.tdwg.org/dwc/terms/geodeticDatum',
	 		'coordinateUncertaintyInMeters' => 'http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters',
	 		'footprintWKT' => 'http://rs.tdwg.org/dwc/terms/footprintWKT',
	 		'verbatimCoordinates' => 'http://rs.tdwg.org/dwc/terms/verbatimCoordinates',
			'georeferencedBy' => 'http://rs.tdwg.org/dwc/terms/georeferencedBy',
			'georeferenceProtocol' => 'http://rs.tdwg.org/dwc/terms/georeferenceProtocol',
			'georeferenceSources' => 'http://rs.tdwg.org/dwc/terms/georeferenceSources',
			'georeferenceVerificationStatus' => 'http://rs.tdwg.org/dwc/terms/georeferenceVerificationStatus',
			'georeferenceRemarks' => 'http://rs.tdwg.org/dwc/terms/georeferenceRemarks',
			'minimumElevationInMeters' => 'http://rs.tdwg.org/dwc/terms/minimumElevationInMeters',
			'maximumElevationInMeters' => 'http://rs.tdwg.org/dwc/terms/maximumElevationInMeters',
			'verbatimElevation' => 'http://rs.tdwg.org/dwc/terms/verbatimElevation',
	 		'disposition' => 'http://rs.tdwg.org/dwc/terms/disposition',
	 		'modified' => 'http://purl.org/dc/terms/modified',
	 		'language' => 'http://purl.org/dc/terms/language',
	 		'rights' => 'http://rs.tdwg.org/dwc/terms/rights',
	 		'rightsHolder' => 'http://rs.tdwg.org/dwc/terms/rightsHolder',
	 		'accessRights' => 'http://rs.tdwg.org/dwc/terms/accessRights'
		);
		$this->determinationFieldArr = array(
	 		'coreid' => '',
			'identifiedBy' => 'http://rs.tdwg.org/dwc/terms/identifiedBy',
			'dateIdentified' => 'http://rs.tdwg.org/dwc/terms/dateIdentified',
			'identificationQualifier' => 'http://rs.tdwg.org/dwc/terms/identificationQualifier',
			'scientificName' => 'http://rs.tdwg.org/dwc/terms/scientificName',
			'genus' => 'http://rs.tdwg.org/dwc/terms/genus',
			'specificEpithet' => 'http://rs.tdwg.org/dwc/terms/specificEpithet',
			'taxonRank' => 'http://rs.tdwg.org/dwc/terms/taxonRank',
			'infraspecificEpithet' => 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet',
			'scientificNameAuthorship' => 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship',
			'identificationReferences' => 'http://rs.tdwg.org/dwc/terms/identificationReferences',
			'identificationRemarks' => 'http://rs.tdwg.org/dwc/terms/identificationRemarks'
			);
		$this->imageFieldArr = array(
			'coreid' => '',
			'identifier' => 'http://purl.org/dc/terms/identifier',
	 		'title' => 'http://purl.org/dc/terms/title',
	 		'description' => 'http://purl.org/dc/terms/description',
			'publisher' => 'http://purl.org/dc/terms/publisher',
			'license' => 'http://purl.org/dc/terms/license',
			'created' => 'http://purl.org/dc/terms/created',
			'references' => 'http://purl.org/dc/terms/references'
		);

		$this->securityArr = array('locality','locationRemarks','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
			'decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','footprintWKT','coordinatePrecision',
			'verbatimCoordinates','verbatimCoordinateSystem','georeferenceRemarks',
			'verbatimLatitude','verbatimLongitude','habitat');

		$this->targetPath = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'collections/datasets/dwc/';
	}

	public function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $id;
			$sql = 'SELECT institutioncode, collectioncode FROM omcollections WHERE collid = '.$id;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$inst = $r->institutioncode;
				if($r->collectioncode) $inst .= '-'.$r->collectioncode;
				$this->nameTemplate = $inst.'_'.date('Y-m-d');
			}
			$rs->close();
		}
	}

	public function createDwcArchive(){
		global $serverRoot;
		//ini_set('memory_limit','512M');
		set_time_limit(500);
		
		if(!class_exists('ZipArchive')){
			exit('FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin');
		}
		
		//Create log File
		$logFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/')."temp/logs/DWCA_".date('Y-m-d').".log";
		$this->logFH = fopen($logFile, 'a');
		$this->logOrEcho("\n\n-----------------------------------------------------\n");
		$this->logOrEcho("DateTime: ".date('Y-m-d h:i:s A')."\n");
		
		$archiveFile = $this->targetPath.'DwC_Archive_'.$this->nameTemplate.'.zip';
		if(file_exists($archiveFile)) unlink($archiveFile);
		$this->zipArchive = new ZipArchive;
		$this->zipArchive->open($archiveFile, ZipArchive::CREATE);
		
		$this->logOrEcho("DWCA created: ".$archiveFile."\n");
		
		$this->writeMetaFile();
		$this->writeOccurrenceFile();
		$this->writeDeterminationFile();
		$this->writeImageFile();
		$this->zipArchive->close();
		
		$this->writeRssFile();

		//Clean up
    	unlink($this->targetPath.$this->nameTemplate.'-meta.xml');
		unlink($this->targetPath.$this->nameTemplate.'-occur.csv');
		unlink($this->targetPath.$this->nameTemplate.'-images.csv');
		unlink($this->targetPath.$this->nameTemplate.'-det.csv');
		
		//Close log file
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		$this->logOrEcho("----------------------------\n\n");
		if($this->logFH){
			fclose($this->logFH);
		}
	}
	
	private function writeMetaFile(){
		global $charset;

		$this->logOrEcho("Creating meta.xml (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->nameTemplate.'-meta.xml', 'w');

		//Output header 
		$outStr = '<archive xmlns="http://rs.tdwg.org/dwc/text/" 
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
			xsi:schemaLocation="http://rs.tdwg.org/dwc/text/   http://rs.tdwg.org/dwc/text/tdwg_dwc_text.xsd">
			<core encoding="'.($charset=='UTF-8'?'UTF-8':'ISO-8859-1').'" fieldsTerminatedBy="," linesTerminatedBy="\n" fieldsEnclosedBy=\'"\' ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Occurrence">
				<files>
					<location>occurrences.csv</location>
				</files>
				<id index="0" />';
		$fieldCnt = 0;
		foreach($this->occurrenceFieldArr as $k => $v){
			if($v){
				$outStr .= '<field index="'.$fieldCnt.'" term="'.$v.'" /> ';
			}
			$fieldCnt++;
		}
		$outStr .= '</core>
			<extension encoding="'.($charset=='UTF-8'?'UTF-8':'ISO-8859-1').'" fieldsTerminatedBy="," linesTerminatedBy="\n" fieldsEnclosedBy=\'"\' ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Identification">
				<files>
					<location>indentifications.csv</location>
				</files>
				<coreid index="0" />';
		$fieldCnt = 0;
		foreach($this->determinationFieldArr as $k => $v){
			if($v){
				$outStr .= '<field index="'.$fieldCnt.'" term="'.$v.'" /> ';
			}
			$fieldCnt++;
		}
		$outStr .= '</extension>
			<extension encoding="'.($charset=='UTF-8'?'UTF-8':'ISO-8859-1').'" fieldsTerminatedBy="," linesTerminatedBy="\n" fieldsEnclosedBy=\'"\' ignoreHeaderLines="1" rowType="http://rs.gbif.org/terms/1.0/Image">
				<files>
					<location>images.csv</location>
				</files>
				<coreid index="0" />';
		$fieldCnt = 0;
		foreach($this->imageFieldArr as $k => $v){
			if($v){
				$outStr .= '<field index="'.$fieldCnt.'" term="'.$v.'" /> ';
			}
			$fieldCnt++;
		}
		$outStr .= '</extension>
		</archive>';
		
		fwrite($fh,$outStr);
   		fclose($fh);
		$this->zipArchive->addFile($this->targetPath.$this->nameTemplate.'-meta.xml');
    	$this->zipArchive->renameName($this->targetPath.$this->nameTemplate.'-meta.xml','meta.xml');
		
    	$this->logOrEcho("&nbsp;&nbsp;&nbsp;&nbsp;Done!! (".date('h:i:s A').")\n");
	}

	private function writeOccurrenceFile(){
		$this->logOrEcho("Creating occurrences.csv (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->nameTemplate.'-occur.csv', 'w');
		
		//Output header
		fputcsv($fh, array_keys($this->occurrenceFieldArr));
		
		//Output records
		$sql = 'SELECT o.occid, IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, '.
			'o.basisOfRecord, o.catalogNumber, o.otherCatalogNumbers, o.ownerInstitutionCode, '.
			'o.family, o.sciname AS scientificName, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, o.scientificNameAuthorship, '.
			'o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, o.identificationRemarks, o.identificationQualifier, o.typeStatus, '.
			'CONCAT_WS("; ",o.recordedBy,o.associatedCollectors) AS recordedBy, o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, '.
			'o.verbatimEventDate, CONCAT_WS("; ",o.habitat, o.substrate) AS habitat, o.fieldNotes, o.fieldNumber, '.
			'CONCAT_WS("; ",o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks, o.informationWithheld, '.
			'o.dynamicProperties, o.associatedTaxa, o.reproductiveCondition, o.establishmentMeans, '.
			'o.lifeStage, o.sex, o.individualCount, o.samplingProtocol, o.preparations, '.
			'o.country, o.stateProvince, o.county, o.municipality, o.locality, o.decimalLatitude, o.decimalLongitude, '.
			'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.footprintWKT, o.verbatimCoordinates, '.
			'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, '.
			'o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.disposition, '.
			'IFNULL(o.modified,o.datelastmodified) AS modified, o.language, c.rights, c.rightsHolder, c.accessRights, o.localitySecurity '.
			'FROM (omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid) '.
			'WHERE c.collid = '.$this->collId.' ORDER BY o.occid';
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				if($r["localitySecurity"] == 1 && !$this->canReadRareSpp){
					$r["habitat"] = '[Redacted]';
					$r["locality"] = '[Redacted]';
					$r["decimalLatitude"] = '[Redacted]';
					$r["decimalLongitude"] = '[Redacted]';
					$r["geodeticDatum"] = '[Redacted]';
					$r["coordinateUncertaintyInMeters"] = '[Redacted]';
					$r["footprintWKT"] = '[Redacted]';
					$r["verbatimCoordinates"] = '[Redacted]';
					$r["verbatimCoordinateSystem"] = '[Redacted]';
					$r["georeferencedBy"] = '[Redacted]';
					$r["georeferenceProtocol"] = '[Redacted]';
					$r["georeferenceSources"] = '[Redacted]';
					$r["georeferenceVerificationStatus"] = '[Redacted]';
					$r["georeferenceRemarks"] = '[Redacted]';
					$r["minimumElevationInMeters"] = '[Redacted]';
					$r["maximumElevationInMeters"] = '[Redacted]';
					$r["verbatimElevation"] = '[Redacted]';
					$r["informationWithheld"] = 'Locality Redacted';
				}
				unset($r['localitySecurity']);
				fputcsv($fh, $r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating occurrence.csv file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}

		fclose($fh);
		$this->zipArchive->addFile($this->targetPath.$this->nameTemplate.'-occur.csv');
		$this->zipArchive->renameName($this->targetPath.$this->nameTemplate.'-occur.csv','occurrences.csv');

    	$this->logOrEcho("&nbsp;&nbsp;&nbsp;&nbsp;Done!! (".date('h:i:s A').")\n");
	}
	
	private function writeDeterminationFile(){

		$this->logOrEcho("Creating identifications.csv (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->nameTemplate.'-det.csv', 'w');
		
		//Output header
		fputcsv($fh, array_keys($this->determinationFieldArr));
		
		//Output records
		$sql = 'SELECT d.occid, d.identifiedBy, d.dateIdentified, d.identificationQualifier, d.sciName AS scientificName, '.
			'd.scientificNameAuthorship, CONCAT_WS(" ",t.unitname1,t.unitname1) AS genus, '. 
			'CONCAT_WS(" ",t.unitname2,t.unitname2) AS specificEpithet, t.unitind3 AS taxonRank, '. 
			't.unitname3 AS infraspecificEpithet, d.identificationReferences, d.identificationRemarks '.
			'FROM (omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid) '.
			'LEFT JOIN taxa t ON d.tidinterpreted = t.tid '. 
			'WHERE o.collid = '.$this->collId.' ORDER BY o.occid';
		//echo $sql;
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				fputcsv($fh, $r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating identifications.csv file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
			
		fclose($fh);
		$this->zipArchive->addFile($this->targetPath.$this->nameTemplate.'-det.csv');
		$this->zipArchive->renameName($this->targetPath.$this->nameTemplate.'-det.csv','identifications.csv');

    	$this->logOrEcho("&nbsp;&nbsp;&nbsp;&nbsp;Done!! (".date('h:i:s A').")\n");
	}

	private function writeImageFile(){
		global $clientRoot;

		$this->logOrEcho("Creating images.csv (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->nameTemplate.'-images.csv', 'w');
		
		//Output header
		fputcsv($fh, array_keys($this->imageFieldArr));

		//Output records
		$sql = 'SELECT o.occid, IFNULL(i.originalurl,i.url) as identifier, o.sciname, IFNULL(i.caption,i.notes) as description, '.
			'CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")") AS publisher, '.
			'c.rights, c.initialtimestamp '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'WHERE c.collid = '.$this->collId.' ORDER BY o.occid';
		//echo $sql;
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				if(substr($r['identifier'],1) == '/'){
					$r['identifier'] = 'http://'.$_SERVER["SERVER_NAME"].$r['identifier'];
				}
				$r['references'] = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.'/collections/individual/index.php?occid='.$r['occid'];
				fputcsv($fh, $r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating images.csv file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
		
		fclose($fh);
		$this->zipArchive->addFile($this->targetPath.$this->nameTemplate.'-images.csv');
		$this->zipArchive->renameName($this->targetPath.$this->nameTemplate.'-images.csv','images.csv');

    	$this->logOrEcho("&nbsp;&nbsp;&nbsp;&nbsp;Done!! (".date('h:i:s A').")\n");
	}
	
	private function writeRssFile(){
		global $defaultTitle, $serverRoot, $clientRoot;

		$rssFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		$this->logOrEcho('Rebuilding rss.xml file: '.$rssFile."... ");
		
		$targetIndex = false;
		$datasets = Array();
		if(file_exists($rssFile)){
			//Get other existing DWCAs by reading and parsing current rss.xml feed and load into array
			$xmlDoc = new DOMDocument();
			$xmlDoc->load($rssFile);
			$items = $xmlDoc->getElementsByTagName("item");
			$itemCnt = 0;
			foreach($items as $i ){
				$titles = $i->getElementsByTagName("title");
				$title = $titles->item(0)->nodeValue;
				if($title == $this->nameTemplate) $targetIndex = $itemCnt;
				$datasets[$itemCnt]['title'] = $title;
				$ids = $i->getElementsByTagName("id");
				$datasets[$itemCnt]['id'] = $ids->item(0)->nodeValue;
				$descriptions = $i->getElementsByTagName("description");
				$datasets[$itemCnt]['description'] = $descriptions->item(0)->nodeValue;
				$types = $i->getElementsByTagName("type");
				$datasets[$itemCnt]['type'] = $types->item(0)->nodeValue;
				$recordTypes = $i->getElementsByTagName("recordType");
				$datasets[$itemCnt]['recordType'] = $recordTypes->item(0)->nodeValue;
				$links = $i->getElementsByTagName("link");
				$datasets[$itemCnt]['link'] = $links->item(0)->nodeValue;
				$pubDates = $i->getElementsByTagName("pubDate");
				$datasets[$itemCnt]['pubDate'] = $pubDates->item(0)->nodeValue;
				$itemCnt++;
			}
			if($targetIndex === false) $targetIndex = $itemCnt;
		}
		//Add or replace new archive that was just created 
		$datasets[$targetIndex]['title'] = $this->nameTemplate;
		$datasets[$targetIndex]['id'] = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'collections/datasets/dwc/DwC_Archive_'.$this->nameTemplate.'.zip';
		$datasets[$targetIndex]['description'] = 'Darwin Core Archive for '.$this->nameTemplate;
		$datasets[$targetIndex]['type'] = 'DWCA';
		$datasets[$targetIndex]['recordType'] = 'DWCA';
		$datasets[$targetIndex]['link'] = $clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'collections/datasets/dwc/DwC_Archive_'.$this->nameTemplate.'.zip';
		$dsStat = stat($this->targetPath.'DwC_Archive_'.$this->nameTemplate.'.zip');
		$datasets[$targetIndex]['pubDate'] = date("D, d M Y H:i:s O", $dsStat["mtime"]);
		
		//Write out datasets to $rssFile
		$dsHandle = fopen($rssFile,"w");
		
		fwrite($dsHandle,'<?xml version="1.0" encoding="ISO-8859-1"?>');
		fwrite($dsHandle,'<rss version="2.0">');
		fwrite($dsHandle,'<channel>');
		fwrite($dsHandle,'<title>'.$defaultTitle.' Darwin Core Archive rss feed</title>');
		fwrite($dsHandle,'<link>http://'.$_SERVER["SERVER_NAME"].'</link>');
		fwrite($dsHandle,'<description>'.$defaultTitle.' Darwin Core Archive rss feed</description>');
		fwrite($dsHandle,'<language>en-us</language>');

		//Rewrite rss.xml file with updated archive  
		foreach($datasets as $dataset){
			fwrite($dsHandle,'<item>');
			fwrite($dsHandle,'<title>' . $dataset["title"] . '</title>');
			fwrite($dsHandle,'<id>' . $dataset["id"] . '</id>');
			fwrite($dsHandle,'<description>' . $dataset["description"] . '</description>');
			fwrite($dsHandle,'<type>' . $dataset["type"] . '</type>');
			fwrite($dsHandle,'<recordType>' . $dataset["recordType"] . '</recordType>');
			fwrite($dsHandle,'<link>' . $dataset["link"] . '</link>');
			fwrite($dsHandle,'<pubDate>' . $dataset["pubDate"] . '</pubDate>');
			fwrite($dsHandle,'</item>');
		}

		fwrite($dsHandle,'</channel>');
		fwrite($dsHandle,'</rss>');
		fclose($dsHandle);

		$this->logOrEcho("Rebuilt!\n");
	}
	
	public function getDwcaItem(){
		$retArr = Array();
		$rssFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		if(file_exists($rssFile)){
			//Get other existing DWCAs by reading and parsing current rss.xml feed and load into array
			$xmlDoc = new DOMDocument();
			$xmlDoc->load($rssFile);
			$items = $xmlDoc->getElementsByTagName("item");
			foreach($items as $i ){
				$titles = $i->getElementsByTagName("title");
				$title = $titles->item(0)->nodeValue;
				if(strpos($title,substr($this->nameTemplate,0,strpos($this->nameTemplate,'_'))) !== false){
					$retArr['title'] = $title;
					$ids = $i->getElementsByTagName("id");
					$retArr['id'] = $ids->item(0)->nodeValue;
					$descriptions = $i->getElementsByTagName("description");
					$retArr['description'] = $descriptions->item(0)->nodeValue;
					$types = $i->getElementsByTagName("type");
					$retArr['type'] = $types->item(0)->nodeValue;
					$recordTypes = $i->getElementsByTagName("recordType");
					$retArr['recordType'] = $recordTypes->item(0)->nodeValue;
					$links = $i->getElementsByTagName("link");
					$retArr['link'] = $links->item(0)->nodeValue;
					$pubDates = $i->getElementsByTagName("pubDate");
					$retArr['pubDate'] = $pubDates->item(0)->nodeValue;
					break;
				}
			}
		}
		return $retArr;
	}

	public function setSilent($c){
		$this->silent = $c;
	}

	public function getSilent(){
		return $this->silent;
	}

	private function logOrEcho($str){
		if(!$this->silent){
			if($this->logFH){
				fwrite($this->logFH,$str);
			} 
			echo '<li>'.$str.'</li>';
			ob_flush();
			flush();
		}
	}

	private function encodeArr(&$inArr,$targetCharset){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->encodeString($v,$targetCharset);
		}
	}
	
	private function encodeString($inStr,$targetCharset){
		global $charset;
		$retStr = $inStr;
		
		$portalCharset = ''; 
		if(strtolower($charset) == 'utf-8' || strtolower($charset) == 'utf8'){
			$portalCharset = 'utf-8';
		}
		elseif(strtolower($charset) == 'iso-8859-1'){
			$portalCharset = 'iso-8859-1';
		}
		if($portalCharset){
			if($targetCharset == 'utf8' && $portalCharset == 'iso-8859-1'){
				if(mb_detect_encoding($inStr,'ISO-8859-1,UTF-8') == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($targetCharset == "iso88591" && $portalCharset == 'utf-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		
		return $retStr;
	}
}
?>