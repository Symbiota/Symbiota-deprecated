<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/UuidFactory.php');

class DwcArchiverOccurrence{

	private $conn;

	private $ts;
	
	private $collArr;
	private $conditionSql;
	private $condAllowArr;

	private $targetPath;
	private $fileName;
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
		//ini_set('memory_limit','512M');
		set_time_limit(500);

		//Ensure that PHP DOMDocument class is installed
		if(!class_exists('DOMDocument')){
			exit('FATAL ERROR: PHP DOMDocument class is not installed, please contact your server admin');
		}

		$this->conn = MySQLiConnectionFactory::getCon('readonly');

		$this->ts = time();
		
		$this->condAllowArr = array('country','stateprovince','county','recordedby');

		$this->occurrenceFieldArr = array(
			'id' => '',
			'institutionCode' => 'http://rs.tdwg.org/dwc/terms/institutionCode',
			'collectionCode' => 'http://rs.tdwg.org/dwc/terms/collectionCode',
			'basisOfRecord' => 'http://rs.tdwg.org/dwc/terms/basisOfRecord',
			'occurrenceID' => 'http://rs.tdwg.org/dwc/terms/occurrenceID',
			'catalogNumber' => 'http://rs.tdwg.org/dwc/terms/catalogNumber',
			'otherCatalogNumbers' => 'http://rs.tdwg.org/dwc/terms/otherCatalogNumbers',
			'ownerInstitutionCode' => 'http://rs.tdwg.org/dwc/terms/ownerInstitutionCode',
			'family' => 'http://rs.tdwg.org/dwc/terms/family',
			'scientificName' => 'http://rs.tdwg.org/dwc/terms/scientificName',
			'scientificNameAuthorship' => 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship',
			'genus' => 'http://rs.tdwg.org/dwc/terms/genus',
			'specificEpithet' => 'http://rs.tdwg.org/dwc/terms/specificEpithet',
			'taxonRank' => 'http://rs.tdwg.org/dwc/terms/taxonRank',
			'infraspecificEpithet' => 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet',
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
 			'fieldNumber' => 'http://rs.tdwg.org/dwc/terms/fieldNumber',
 			'occurrenceRemarks' => 'http://rs.tdwg.org/dwc/terms/occurrenceRemarks',
			'informationWithheld' => 'http://rs.tdwg.org/dwc/terms/informationWithheld',
 			'dynamicProperties' => 'http://rs.tdwg.org/dwc/terms/dynamicProperties',
 			'associatedTaxa' => 'http://rs.tdwg.org/dwc/terms/associatedTaxa',
 			'reproductiveCondition' => 'http://rs.tdwg.org/dwc/terms/reproductiveCondition',
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
	 		'language' => 'http://purl.org/dc/terms/language',
	 		'rights' => 'http://rs.tdwg.org/dwc/terms/rights',
	 		'rightsHolder' => 'http://rs.tdwg.org/dwc/terms/rightsHolder',
	 		'accessRights' => 'http://rs.tdwg.org/dwc/terms/accessRights',
	 		'modified' => 'http://purl.org/dc/terms/modified',
	 		'recordId' => 'http://portal.idigbio.org/terms/recordId',
			'references' => 'http://purl.org/dc/terms/references'
 		);
		$this->determinationFieldArr = array(
	 		'coreid' => '',
			'identifiedBy' => 'http://rs.tdwg.org/dwc/terms/identifiedBy',
			'dateIdentified' => 'http://rs.tdwg.org/dwc/terms/dateIdentified',
			'identificationQualifier' => 'http://rs.tdwg.org/dwc/terms/identificationQualifier',
			'scientificName' => 'http://rs.tdwg.org/dwc/terms/scientificName',
			'scientificNameAuthorship' => 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship',
			'genus' => 'http://rs.tdwg.org/dwc/terms/genus',
			'specificEpithet' => 'http://rs.tdwg.org/dwc/terms/specificEpithet',
			'taxonRank' => 'http://rs.tdwg.org/dwc/terms/taxonRank',
			'infraspecificEpithet' => 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet',
			'identificationReferences' => 'http://rs.tdwg.org/dwc/terms/identificationReferences',
			'identificationRemarks' => 'http://rs.tdwg.org/dwc/terms/identificationRemarks',
	 		'recordId' => 'http://portal.idigbio.org/terms/recordId'
		);
		$this->imageFieldArr = array(
			'coreid' => '',
			'accessURI' => 'http://rs.tdwg.org/ac/terms/accessURI',		//url 
			'providerManagedID' => 'http://rs.tdwg.org/ac/terms/providerManagedID',	//GUID
	 		'title' => 'http://purl.org/dc/terms/title',	//scientific name
	 		'comments' => 'http://rs.tdwg.org/ac/terms/comments',	//General notes	
			'Owner' => 'http://ns.adobe.com/xap/1.0/rights/Owner',	//Institution name
			'rights' => 'http://purl.org/dc/terms/rights',		//Copyright unknown
			'UsageTerms' => 'http://ns.adobe.com/xap/1.0/rights/UsageTerms',	//Creative Commons BY-SA 3.0 license
			'WebStatement' => 'http://ns.adobe.com/xap/1.0/rights/WebStatement',	//http://creativecommons.org/licenses/by-nc-sa/3.0/us/
			'MetadataDate' => 'http://ns.adobe.com/xap/1.0/MetadataDate',	//timestamp
			'associatedSpecimenReference' => 'http://rs.tdwg.org/ac/terms/associatedSpecimenReference',	//reference url in portal
			'type' => 'http://purl.org/dc/terms/type',		//StillImage
			'subtype' => 'http://rs.tdwg.org/ac/terms/subtype',		//Photograph
			'format' => 'http://purl.org/dc/terms/format',		//jpg
			'metadataLanguage' => 'http://rs.tdwg.org/ac/terms/metadataLanguage'	//en
		);

 		$this->securityArr = array('locality','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
			'decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','footprintWKT',
			'verbatimCoordinates','georeferenceRemarks','georeferencedBy','georeferenceProtocol','georeferenceSources',
			'georeferenceVerificationStatus','habitat','informationWithheld');
	}

	public function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
		if($this->logFH){
			fclose($this->logFH);
		}
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
				$tPath = $GLOBALS["serverRoot"]."/temp";
			}
			if(file_exists($tPath."/downloads")){
				$tPath .= "/downloads";
			}
			if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
				$tPath .= '/';
			}
			$this->targetPath = $tPath;
		}
	}

	public function setFileName($seed){
		$this->fileName = $this->conn->real_escape_string($seed).'_DwC-A.zip';
	}

	public function setCollArr($collTarget, $collType = ''){
		$collTarget = $this->cleanInStr($collTarget);
		$collType = $this->cleanInStr($collType);
		unset($this->collArr);
		$this->collArr = array();
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
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.fulldescription, c.collectionguid, '.
			'IFNULL(c.homepage,i.url) AS url, IFNULL(c.contact,i.contact) AS contact, IFNULL(c.email,i.email) AS email, '.
			'c.guidtarget, c.latitudedecimal, c.longitudedecimal, c.icon, c.colltype, c.rights, c.rightsholder, c.usageterm, '.
			'i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country, i.phone '.
			'FROM omcollections c LEFT JOIN institutions i ON c.iid = i.iid WHERE '.$sqlWhere;
		//echo $sql.'<br/>';
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

	public function getCollArr(){
		return $this->collArr;
	}

	public function setConditionStr($condObj, $filter = 1){
		$condArr = array();
		if(is_array($condObj)){
			//Array of key/value pairs (e.g. array(country => USA,United States, stateprovince => Arizona,New Mexico)
			$condArr = $condObj;
		}
		elseif(is_string($condObj)){
			//String of key/value pairs (e.g. country:USA,United States;stateprovince:Arizona,New Mexico;county-start:Pima,Eddy
			$cArr = explode(';',$condObj);
			foreach($cArr as $rawV){
				$tok = explode(':',$rawV);
				if(count($tok) == 2){
					$condArr[$tok[0]] = $tok[1];
				}
			}
		}
		foreach($condArr as $k => $v){
			if(!$filter || in_array(strtolower($k),$this->condAllowArr)){
				$type = '';
				if($p = strpos($k,'-')){
					$type = strtolower(substr($k,0,$p));
					$k = substr($k,$p);
				}
				if($type == 'like'){
					$sqlFrag = '';
					$terms = explode(',',$v);
					foreach($terms as $t){
						$sqlFrag .= 'OR (o.'.$k.' LIKE "%'.$this->cleanInStr($t).'%") ';
					}
					$this->conditionSql .= 'AND ('.substr($sqlFrag,3).') ';
				}
				elseif($type == 'start'){
					$sqlFrag = '';
					$terms = explode(',',$v);
					foreach($terms as $t){
						$sqlFrag .= 'OR (o.'.$k.' LIKE "'.$this->cleanInStr($t).'%") ';
					}
					$this->conditionSql .= 'AND ('.substr($sqlFrag,3).') ';
				}
				elseif($type == 'null'){
					$this->conditionSql .= 'AND (o.'.$k.' IS NULL) ';
				}
				elseif($type == 'notnull'){
					$this->conditionSql .= 'AND (o.'.$k.' IS NOT NULL) ';
				}
				else{
					$this->conditionSql .= 'AND (o.'.$k.' IN("'.str_replace(',','","',$v).'")) ';
				}
			}
		}
	}

	public function createDwcArchive($includeDets, $includeImgs, $redactLocalities, $type = 'coll'){
		global $serverRoot;
		if(!$this->targetPath) $this->setTargetPath();
		$archiveFile = '';
		if($this->collArr){
			if(!$this->logFH && !$this->silent){
				$logFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/')."temp/logs/DWCA_".date('Y-m-d').".log";
				$this->logFH = fopen($logFile, 'a');
			}
			$this->logOrEcho('Creating DwC-A file...'."\n");
			
			if(!class_exists('ZipArchive')){
				$this->logOrEcho("FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin\n");
				exit('FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin');
			}
	
			$archiveFile = $this->targetPath.$this->fileName;
			if(file_exists($archiveFile)) unlink($archiveFile);
			$this->zipArchive = new ZipArchive;
			$status = $this->zipArchive->open($archiveFile, ZipArchive::CREATE);
			if($status !== true){
				exit('FATAL ERROR: unable to create archive file: '.$status);
			}
			//$this->logOrEcho("DWCA created: ".$archiveFile."\n");
			
			$this->writeMetaFile();
			if($type == 'coll'){
				$this->writeEmlFile($this->getCollectionEmlArr());
			}
			else{
				$this->writeEmlFile();
			}
			$this->writeOccurrenceFile($redactLocalities);
			if($includeDets) $this->writeDeterminationFile();
			if($includeImgs) $this->writeImageFile($redactLocalities);
			$this->zipArchive->close();
			
			//Clean up
			unlink($this->targetPath.$this->ts.'-meta.xml');
			unlink($this->targetPath.$this->ts.'-eml.xml');
			unlink($this->targetPath.$this->ts.'-occur.csv');
			unlink($this->targetPath.$this->ts.'-images.csv');
			unlink($this->targetPath.$this->ts.'-det.csv');
	
			$this->logOrEcho("\n-----------------------------------------------------\n");
		}
		else{
			echo 'ERROR: unable to create DwC-A for collection #'.implode(',',array_keys($this->collArr));
		}
		return $archiveFile;
	}
	
	private function writeMetaFile(){
		$this->logOrEcho("Creating meta.xml (".date('h:i:s A').")... ");

		//Create new DOM document 
		$newDoc = new DOMDocument('1.0','UTF-8');

		//Add root element 
		$rootElem = $newDoc->createElement('archive');
		$rootElem->setAttribute('metadata','eml.xml');
		$rootElem->setAttribute('xmlns','http://rs.tdwg.org/dwc/text/');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','http://rs.tdwg.org/dwc/text/   http://rs.tdwg.org/dwc/text/tdwg_dwc_text.xsd');
		$newDoc->appendChild($rootElem);

		//Core file definition
		$coreElem = $newDoc->createElement('core');
		$coreElem->setAttribute('encoding',$GLOBALS['charset']);
		$coreElem->setAttribute('fieldsTerminatedBy',',');
		$coreElem->setAttribute('linesTerminatedBy','\n');
		$coreElem->setAttribute('fieldsEnclosedBy','"');
		$coreElem->setAttribute('ignoreHeaderLines','1');
		$coreElem->setAttribute('rowType','http://rs.tdwg.org/dwc/terms/Occurrence');
		
		$filesElem = $newDoc->createElement('files');
		$filesElem->appendChild($newDoc->createElement('location','occurrences.csv'));
		$coreElem->appendChild($filesElem);

		$idElem = $newDoc->createElement('id');
		$idElem->setAttribute('index','0');
		$coreElem->appendChild($idElem);

		$occCnt = 0;
		foreach($this->occurrenceFieldArr as $k => $v){
			if($occCnt){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$occCnt);
				$fieldElem->setAttribute('term',$v);
				$coreElem->appendChild($fieldElem);
			}
			$occCnt++;
		}
		$rootElem->appendChild($coreElem);

		//Identification extension
		$extElem1 = $newDoc->createElement('extension');
		$extElem1->setAttribute('encoding',$GLOBALS['charset']);
		$extElem1->setAttribute('fieldsTerminatedBy',',');
		$extElem1->setAttribute('linesTerminatedBy','\n');
		$extElem1->setAttribute('fieldsEnclosedBy','"');
		$extElem1->setAttribute('ignoreHeaderLines','1');
		$extElem1->setAttribute('rowType','http://rs.tdwg.org/dwc/terms/Identification');

		$filesElem1 = $newDoc->createElement('files');
		$filesElem1->appendChild($newDoc->createElement('location','identifications.csv'));
		$extElem1->appendChild($filesElem1);
		
		$coreIdElem1 = $newDoc->createElement('coreid');
		$coreIdElem1->setAttribute('index','0');
		$extElem1->appendChild($coreIdElem1);
		
		//List identification fields
		$detCnt = 0;
		foreach($this->determinationFieldArr as $k => $v){
			if($detCnt){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$detCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem1->appendChild($fieldElem);
			}
			$detCnt++;
		}
		$rootElem->appendChild($extElem1);

		//Image extension
		$extElem2 = $newDoc->createElement('extension');
		$extElem2->setAttribute('encoding',$GLOBALS['charset']);
		$extElem2->setAttribute('fieldsTerminatedBy',',');
		$extElem2->setAttribute('linesTerminatedBy','\n');
		$extElem2->setAttribute('fieldsEnclosedBy','"');
		$extElem2->setAttribute('ignoreHeaderLines','1');
		$extElem2->setAttribute('rowType','http://rs.gbif.org/terms/1.0/Image');

		$filesElem2 = $newDoc->createElement('files');
		$filesElem2->appendChild($newDoc->createElement('location','images.csv'));
		$extElem2->appendChild($filesElem2);
		
		$coreIdElem2 = $newDoc->createElement('coreid');
		$coreIdElem2->setAttribute('index','0');
		$extElem2->appendChild($coreIdElem2);
		
		//List image fields
		$imgCnt = 0;
		foreach($this->imageFieldArr as $k => $v){
			if($imgCnt){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$imgCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem2->appendChild($fieldElem);
			}
			$imgCnt++;
		}
		$rootElem->appendChild($extElem2);
		
		$tempFileName = $this->targetPath.$this->ts.'-meta.xml';
		$newDoc->save($tempFileName);
		$this->zipArchive->addFile($tempFileName);
    	$this->zipArchive->renameName($tempFileName,'meta.xml');
		
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function getDatasetEmlArr(){
		global $clientRoot, $defaultTitle, $adminEmail;
		
		$urlPathPrefix = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
		
		$emlArr = array();
		$emlArr['title'] = $defaultTitle.' general data extract';
		
		$emlArr['creator'][0]['organizationName'] = $USER_DISPLAY_NAME;
		
		$emlArr['metadataProvider'][0]['organizationName'] = $defaultTitle;
		$emlArr['metadataProvider'][0]['electronicMailAddress'] = $adminEmail;
		$emlArr['metadataProvider'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['pubDate'] = date("Y-m-d");
		
		//Display search criteria
				
		return $emlArr;
	}

	private function getCollectionEmlArr(){
		global $clientRoot, $defaultTitle, $adminEmail;
		
		$urlPathPrefix = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
		
		//Create arr using the first occurrence of $this->collArr
		$collId = key($this->collArr);
		$cArr = $this->collArr[$collId];
		
		$emlArr = array();
		$emlArr['alternateIdentifier'][] = $cArr['collectionguid'];
		$emlArr['alternateIdentifier'][] = $urlPathPrefix.'collections/misc/misc/collprofiles.php?collid='.$collId;
		$emlArr['title'] = $cArr['collname'];
		
		$emlArr['creator'][0]['organizationName'] = $defaultTitle;
		$emlArr['creator'][0]['electronicMailAddress'] = $adminEmail;
		$emlArr['creator'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['metadataProvider'][0]['organizationName'] = $defaultTitle;
		$emlArr['metadataProvider'][0]['electronicMailAddress'] = $adminEmail;
		$emlArr['metadataProvider'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['pubDate'] = date("Y-m-d");
		$emlArr['description'] = $cArr['description'];

		$emlArr['contact'][0]['individualName'] = $cArr['contact'];
		$emlArr['contact'][0]['organizationName'] = $cArr['collname'];

		$emlArr['contact'][0]['deliveryPoint'] = $cArr['address1'].($cArr['address2']?', '.$cArr['address2']:'');
		$emlArr['contact'][0]['city'] = $cArr['city'];
		$emlArr['contact'][0]['administrativeArea'] = $cArr['state'];
		$emlArr['contact'][0]['postalCode'] = $cArr['postalcode'];
		$emlArr['contact'][0]['country'] = $cArr['country'];
		
		$emlArr['contact'][0]['phone'] = $cArr['phone'];
		$emlArr['contact'][0]['electronicMailAddress'] = $cArr['email'];
		$emlArr['contact'][0]['onlineUrl'] = $cArr['url'];
		
		$emlArr['intellectualRights'] = $cArr['rights'];
				
		return $emlArr;
	}

	private function writeEmlFile($emlArr){
		$this->logOrEcho("Creating eml.xml (".date('h:i:s A').")... ");

		if(!$emlArr) $emlArr = $this->getDatasetEmlArr();
		
		//Create new DOM document 
		$newDoc = new DOMDocument('1.0','UTF-8');

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
				$datasetElem->appendChild($newDoc->createElement('alternateIdentifier',$v));
			}
		}
		
		if(array_key_exists('title',$emlArr)){
			$titleElem = $newDoc->createElement('title',$emlArr['title']);
			$titleElem->setAttribute('xml:lang','eng');
			$datasetElem->appendChild($titleElem);
		}

		if(array_key_exists('creator',$emlArr)){
			$createArr = $emlArr['creator'];
			foreach($createArr as $childArr){
				$creatorElem = $newDoc->createElement('creator');
				foreach($childArr as $k => $v){
					$creatorElem->appendChild($newDoc->createElement($k,$v));
				}
				$datasetElem->appendChild($creatorElem);
			}
		}

		if(array_key_exists('metadataProvider',$emlArr)){
			$mdArr = $emlArr['metadataProvider'];
			foreach($mdArr as $childArr){
				$mdElem = $newDoc->createElement('metadataProvider');
				foreach($childArr as $k => $v){
					$mdElem->appendChild($newDoc->createElement($k,$v));
				}
				$datasetElem->appendChild($mdElem);
			}
		}
		
		if(array_key_exists('pubDate',$emlArr)){
			$datasetElem->appendChild($newDoc->createElement('pubDate',$emlArr['pubDate']));
		}
		$datasetElem->appendChild($newDoc->createElement('language','eng'));

		if(array_key_exists('description',$emlArr)){
			$abstractElem = $newDoc->createElement('abstract');
			$abstractElem->appendChild($newDoc->createElement('para',$emlArr['description']));
			$datasetElem->appendChild($abstractElem);
		}
		
		if(array_key_exists('contact',$emlArr)){
			$contactArr = $emlArr['contact'];
			foreach($contactArr as $childArr){
				$contactElem = $newDoc->createElement('contact');
				$contactElem->appendChild($newDoc->createElement('individualName',$childArr['individualName']));
				$contactElem->appendChild($newDoc->createElement('organizationName',$childArr['organizationName']));
				
				$addressElem = $newDoc->createElement('address');
				$addressElem->appendChild($newDoc->createElement('deliveryPoint',$childArr['deliveryPoint']));
				$addressElem->appendChild($newDoc->createElement('city',$childArr['city']));
				$addressElem->appendChild($newDoc->createElement('administrativeArea',$childArr['administrativeArea']));
				$addressElem->appendChild($newDoc->createElement('postalCode',$childArr['postalCode']));
				$addressElem->appendChild($newDoc->createElement('country',$childArr['country']));
				$contactElem->appendChild($addressElem);
				
				$contactElem->appendChild($newDoc->createElement('phone',$childArr['phone']));
				$contactElem->appendChild($newDoc->createElement('electronicMailAddress',$childArr['electronicMailAddress']));
				$contactElem->appendChild($newDoc->createElement('onlineUrl',$childArr['onlineUrl']));
				
				$datasetElem->appendChild($contactElem);
			}
		}
		
		if(array_key_exists('intellectualRights',$emlArr)){
			$rightsElem = $newDoc->createElement('intellectualRights');
			$rightsElem = $newDoc->createElement('para',$emlArr['intellectualRights']);
			$datasetElem->appendChild($rightsElem);
		}
		
		$tempFileName = $this->targetPath.$this->ts.'-eml.xml';
		$newDoc->save($tempFileName);

		$this->zipArchive->addFile($tempFileName);
    	$this->zipArchive->renameName($tempFileName,'eml.xml');

    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function writeOccurrenceFile($redactLocalities){
		global $clientRoot;
		$this->logOrEcho("Creating occurrences.csv (".date('h:i:s A').")... ");
		if($this->collArr){
			$fh = fopen($this->targetPath.$this->ts.'-occur.csv', 'w');
			
			//Output header
			fputcsv($fh, array_keys($this->occurrenceFieldArr));
			
			//Output records
			$sql = 'SELECT o.occid, IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, '.
				'o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, o.ownerInstitutionCode, '.
				'o.family, o.sciname AS scientificName, IFNULL(t.author,o.scientificNameAuthorship) AS scientificNameAuthorship, '.
				'IFNULL(CONCAT_WS(" ",t.unitind1,t.unitname1),o.genus) AS genus, IFNULL(CONCAT_WS(" ",t.unitind2,t.unitname2),o.specificEpithet) AS specificEpithet, '.
				'IFNULL(t.unitind3,o.taxonRank) AS taxonRank, IFNULL(t.unitname3,o.infraspecificEpithet) AS infraspecificEpithet, '.
				'o.identifiedBy, o.dateIdentified, o.identificationReferences, o.identificationRemarks, o.identificationQualifier, o.typeStatus, '.
				'CONCAT_WS("; ",o.recordedBy,o.associatedCollectors) AS recordedBy, o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, '.
				'o.verbatimEventDate, CONCAT_WS("; ",o.habitat, o.substrate) AS habitat, o.fieldNumber, '.
				'CONCAT_WS("; ",o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks, o.informationWithheld, '.
				'o.dynamicProperties, o.associatedTaxa, o.reproductiveCondition, o.establishmentMeans, '.
				'o.lifeStage, o.sex, o.individualCount, o.samplingProtocol, o.preparations, '.
				'o.country, o.stateProvince, o.county, o.municipality, o.locality, o.decimalLatitude, o.decimalLongitude, '.
				'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.footprintWKT, o.verbatimCoordinates, '.
				'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, '.
				'o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.disposition, '.
				'o.language, c.rights, c.rightsHolder, c.accessRights, IFNULL(o.modified,o.datelastmodified) AS modified, '.
				'g.guid AS recordId, o.localitySecurity, c.collid '.
				'FROM (omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid) '.
				'INNER JOIN guidoccurrences g ON o.occid = g.occid '.
				'LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE c.collid IN('.implode(',',array_keys($this->collArr)).') ';
			if($this->conditionSql) {
				$sql .= $this->conditionSql;
			}
			$sql .= 'ORDER BY o.collid,o.occid'; 
			//echo $sql;
			if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
				while($r = $rs->fetch_assoc()){
					if($redactLocalities && $r["localitySecurity"] > 0 && !$this->canReadRareSpp){
						foreach($this->securityArr as $v){
							if(array_key_exists($v,$r)) $r[$v] = '[Redacted]';
						}
					}
					unset($r['localitySecurity']);
					$r['references'] = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.'/collections/individual/index.php?occid='.$r['occid'];
					$guidTarget = $this->collArr[$r['collid']]['guidtarget'];
					if($guidTarget == 'catalogNumber'){
						$r['occurrenceID'] = $r['catalogNumber'];
					}
					elseif($guidTarget == 'symbiotaUUID'){
						$r['occurrenceID'] = $r['recordId'];
					}
					$r['recordId'] = 'urn:uuid:'.$_SERVER["SERVER_NAME"].':'.$r['recordId'];
					unset($r['collid']);
					fputcsv($fh, $this->addcslashesArr($r));
				}
				$rs->free();
			}
			else{
				$this->logOrEcho("ERROR creating occurrence.csv file: ".$this->conn->error."\n");
				$this->logOrEcho("\tSQL: ".$sql."\n");
			}
	
			fclose($fh);
			$this->zipArchive->addFile($this->targetPath.$this->ts.'-occur.csv');
			$this->zipArchive->renameName($this->targetPath.$this->ts.'-occur.csv','occurrences.csv');
		}
		else{
			$this->logOrEcho("ERROR: collections not defined; occurrences.csv not created\n");
		}
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}
	
	private function writeDeterminationFile(){
		$this->logOrEcho("Creating identifications.csv (".date('h:i:s A').")... ");
		if($this->collArr){
			$fh = fopen($this->targetPath.$this->ts.'-det.csv', 'w');
			
			//Output header
			fputcsv($fh, array_keys($this->determinationFieldArr));
			
			//Output records
			$sql = 'SELECT d.occid, d.identifiedBy, d.dateIdentified, d.identificationQualifier, d.sciName AS scientificName, '.
				'd.scientificNameAuthorship, CONCAT_WS(" ",t.unitname1,t.unitname1) AS genus, '. 
				'CONCAT_WS(" ",t.unitname2,t.unitname2) AS specificEpithet, t.unitind3 AS taxonRank, '. 
				't.unitname3 AS infraspecificEpithet, d.identificationReferences, d.identificationRemarks, g.guid AS recordId '.
				'FROM (omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid) '.
				'INNER JOIN guidoccurdeterminations g ON d.detid = g.detid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid '.
				'LEFT JOIN taxa t ON d.tidinterpreted = t.tid '. 
				'WHERE o.collid IN('.implode(',',array_keys($this->collArr)).') ';
			if($this->conditionSql) {
				$sql .= $this->conditionSql;
			}
			$sql .= 'ORDER BY o.collid,o.occid';
			//echo $sql;
			if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
				while($r = $rs->fetch_assoc()){
					$r['recordId'] = 'urn:uuid:'.$_SERVER["SERVER_NAME"].':'.$r['recordId'];
					fputcsv($fh, $this->addcslashesArr($r));
				}
				$rs->free();
			}
			else{
				$this->logOrEcho("ERROR creating identifications.csv file: ".$this->conn->error."\n");
				$this->logOrEcho("\tSQL: ".$sql."\n");
			}
				
			fclose($fh);
			$this->zipArchive->addFile($this->targetPath.$this->ts.'-det.csv');
			$this->zipArchive->renameName($this->targetPath.$this->ts.'-det.csv','identifications.csv');
		}
		else{
			$this->logOrEcho("ERROR: collections not defined; identifications.csv not created\n");
		}
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function writeImageFile($redactLocalities){
		global $clientRoot,$imageDomain;

		$this->logOrEcho("Creating images.csv (".date('h:i:s A').")... ");
		if($this->collArr){
			$fh = fopen($this->targetPath.$this->ts.'-images.csv', 'w');
			
			//Output header
			fputcsv($fh, array_keys($this->imageFieldArr));
	
			//Output records
			$sql = 'SELECT o.occid, IFNULL(i.originalurl,i.url) as accessURI, g.guid AS providermanagedid, '. 
				'o.sciname AS title, IFNULL(i.caption,i.notes) as comments, '.
				'IFNULL(c.rightsholder,CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")")) AS owner, '.
				'c.rights, "" AS usageterms, c.accessrights AS webstatement, c.initialtimestamp AS metadatadate '.
				'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
				'INNER JOIN guidimages g ON i.imgid = g.imgid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid '.
				'WHERE c.collid IN('.implode(',',array_keys($this->collArr)).') ';
			if($redactLocalities && !$this->canReadRareSpp){
				$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
			}
			if($this->conditionSql) {
				$sql .= $this->conditionSql;
			}
			//$sql .= 'ORDER BY o.occid';
			//echo $sql;
			if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
				$referencePrefix = 'http://'.$_SERVER["SERVER_NAME"];
				if(isset($imageDomain) && $imageDomain) $referencePrefix = $imageDomain;
				while($r = $rs->fetch_assoc()){
					if(substr($r['accessURI'],0,1) == '/') $r['accessURI'] = $referencePrefix.$r['accessURI'];
					if(stripos($r['rights'],'http://creativecommons.org') === 0){
						$r['providermanagedid'] = 'urn:uuid:'.$_SERVER["SERVER_NAME"].':'.$r['providermanagedid'];
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
					$r['associatedSpecimenReference'] = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.'/collections/individual/index.php?occid='.$r['occid'];
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
					fputcsv($fh, $this->addcslashesArr($r));
				}
				$rs->free();
			}
			else{
				$this->logOrEcho("ERROR creating images.csv file: ".$this->conn->error."\n");
				$this->logOrEcho("\tSQL: ".$sql."\n");
			}
			
			fclose($fh);
			$this->zipArchive->addFile($this->targetPath.$this->ts.'-images.csv');
			$this->zipArchive->renameName($this->targetPath.$this->ts.'-images.csv','images.csv');
		}
		else{
			$this->logOrEcho("ERROR: collections not defined; images.csv not created\n");
		}
		
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	//DWCA publishing and RSS related functions 
	public function batchCreateDwca($collIdArr, $includeDets, $includeImgs, $redactLocalities){
		global $serverRoot;
		//Create log File
		$logFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/')."temp/logs/DWCA_".date('Y-m-d').".log";
		$this->logFH = fopen($logFile, 'a');
		$this->logOrEcho("Starting batch process (".date('Y-m-d h:i:s A').")\n");
		$this->logOrEcho("\n-----------------------------------------------------\n\n");
		
		foreach($collIdArr as $id){
			//Create a separate DWCA object for each collection
			$this->setCollArr($id);
			$this->logOrEcho('Starting DwC-A process for '.$this->collArr[$id]['collcode']."\n");
			$this->setFileName($this->collArr[$id]['collcode']);
			$this->createDwcArchive($includeDets, $includeImgs, $redactLocalities);
		}
		//Reset $this->collArr with all the collections and then rebuild the RSS feed 
		$this->setCollArr(implode(',',$collIdArr));
		$this->writeRssFile();
		$this->logOrEcho("Batch process finished! (".date('Y-m-d h:i:s A').") \n");
	}
	
	public function writeRssFile(){
		global $defaultTitle, $serverRoot, $clientRoot;

		$this->logOrEcho("Mapping data to RSS feed... \n");
		
		//Create new document and write out to target
		$newDoc = new DOMDocument('1.0','UTF-8');

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
		$titleElem = $newDoc->createElement('title',$defaultTitle.' Darwin Core Archive rss feed');
		$channelElem->appendChild($titleElem);
		$linkElem = $newDoc->createElement('link','http://'.$_SERVER["SERVER_NAME"]);
		$channelElem->appendChild($linkElem);
		$descriptionElem = $newDoc->createElement('description',$defaultTitle.' Darwin Core Archive rss feed');
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
			$title = $cArr['collcode'].' DwC-Archive';
			$itemTitleElem = $newDoc->createElement('title',$title);
			$itemElem->appendChild($itemTitleElem);
			//description
			$descTitleElem = $newDoc->createElement('description','Darwin Core Archive for '.$cArr['collname']);
			$itemElem->appendChild($descTitleElem);
			//GUID
			$guidElem = $newDoc->createElement('guid','http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'collections/misc/collprofiles.php?collid='.$collId);
			$itemElem->appendChild($guidElem);
			//type
			$typeTitleElem = $newDoc->createElement('type','DWCA');
			$itemElem->appendChild($typeTitleElem);
			//recordType
			$recTypeTitleElem = $newDoc->createElement('recordType','DWCA');
			$itemElem->appendChild($recTypeTitleElem);
			//link
			$linkTitleElem = $newDoc->createElement('link','http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'collections/datasets/dwc/'.$cArr['collcode'].'_DwC-A.zip');
			$itemElem->appendChild($linkTitleElem);
			//pubDate
			$dsStat = stat($this->targetPath.$cArr['collcode'].'_DwC-A.zip');
			$pubDateTitleElem = $newDoc->createElement('pubDate',date("D, d M Y H:i:s O", $dsStat["mtime"]));
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
		$sr = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/');
		$rssFile = $sr.'webservices/dwc/rss.xml';
		if(!file_exists($rssFile)) return false;
		$doc = new DOMDocument();
		$doc->load($rssFile);
		$cElem = $doc->getElementsByTagName("channel")->item(0);
		$items = $cElem->getElementsByTagName("item");
		foreach($items as $i){
			if($i->getAttribute('collid') == $collId){
				$link = $i->getElementsByTagName("link");
				$nodeValue = $link->item(0)->nodeValue;
				$fileUrl = $sr.'collections/datasets/dwc'.substr($nodeValue,strrpos($nodeValue,'/'));
				unlink($fileUrl);
				$cElem->removeChild($i);
			}
		}
		$doc->save($rssFile);
		return true;
	}

	//Misc functions
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

	public function setSilent($c){
		$this->silent = $c;
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
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
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
	
	private function addcslashesArr($arr){
		$retArr = array();
		foreach($arr as $k => $v){
			$retArr[$k] = addcslashes($v,"\n\r\"\\");
		}
		return $retArr;
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