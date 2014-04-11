<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownload.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');
include_once($serverRoot.'/classes/DwcArchiverOccurrence.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 

$schema = array_key_exists("schema",$_POST)?$_POST["schema"]:"symbiota"; 
$cSet = array_key_exists("cset",$_POST)?$_POST["cset"]:'';

if($schema == "backup"){

	$collid = $_POST["collid"];
	if($collid && is_numeric($collid)){
		//check permissions due to sensitive localities not being redacted
		if($isAdmin || array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])){
			$dwcaHandler = new DwcArchiverOccurrence();
			$dwcaHandler->setSchemaType('backup');
			$dwcaHandler->setCharSetOut($cSet);
			$dwcaHandler->setVerbose(0);
			$dwcaHandler->setIncludeDets(1);
			$dwcaHandler->setIncludeImgs(1);
			$dwcaHandler->setRedactLocalities(0);
			$dwcaHandler->setCollArr($collid);
			
			$archiveFile = $dwcaHandler->createDwcArchive();
			
			if($archiveFile){
				//ob_start();
				header('Content-Description: Symbiota Occurrence Backup File (DwC-Archive data package)');
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename='.basename($archiveFile));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($archiveFile));
				//od_end_clean();
				readfile($archiveFile);
				unlink($archiveFile);
			}
			else{
				echo 'ERROR creating output file. Query probably did not include any records.';
			}
		}
	}
}
else{
	$zip = (array_key_exists('zip',$_POST)?$_POST['zip']:0);
	$format = $_POST['format'];
	$extended = (array_key_exists('extended',$_POST)?$_POST['extended']:0);

	$redactLocalities = 1;
	$rareReaderArr = array();
	if($IS_ADMIN || array_key_exists("CollAdmin", $userRights)){
		$redactLocalities = 0;
	}
	elseif(array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
		$redactLocalities = 0;
	}
	else{
		if(array_key_exists('CollEditor', $userRights)){
			$rareReaderArr = $userRights['CollEditor'];
		}
		if(array_key_exists('RareSppReader', $userRights)){
			$rareReaderArr = array_unique(array_merge($rareReaderArr,$userRights['RareSppReader']));
		}
	}
		
	if($schema == "georef"){
		$dlManager = new OccurrenceDownload();
		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$occurManager = new OccurrenceManager();
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
		$dlManager->setSchemaType($schema);
		$dlManager->setExtended($extended);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
		$dlManager->addCondition('decimalLatitude','NOTNULL','');
		$dlManager->addCondition('decimalLongitude','NOTNULL','');
		if(array_key_exists('targetcollid',$_POST) && $_POST['targetcollid']){
			$dlManager->addCondition('collid','EQUALS',$_POST['targetcollid']);
		}
		if(array_key_exists('processingstatus',$_POST) && $_POST['processingstatus']){
			$dlManager->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
		}
		if(array_key_exists('customfield1',$_POST) && $_POST['customfield1']){
			$dlManager->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
		}
		$dlManager->downloadData();
	}
	elseif($schema == 'checklist'){
		$dlManager = new OccurrenceDownload();
		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$occurManager = new OccurrenceManager();
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
		$dlManager->setSchemaType($schema);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
		$taxonFilterCode = array_key_exists("taxonFilterCode",$_POST)?$_POST["taxonFilterCode"]:0;
		$dlManager->setTaxonFilter($taxonFilterCode); 
		$dlManager->downloadData();
	}
	else{
		//Is an occurrence download 
		$dwcaHandler = new DwcArchiverOccurrence();
		$dwcaHandler->setCharSetOut($cSet);
		$dwcaHandler->setSchemaType($schema);
		$dwcaHandler->setExtended($extended);
		$dwcaHandler->setDelimiter($format);
		$dwcaHandler->setVerbose(0);
		$dwcaHandler->setRedactLocalities($redactLocalities);
		if($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);

		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$occurManager = new OccurrenceManager();
			$dwcaHandler->setCustomWhereSql($occurManager->getSqlWhere());
		}
		else{
			//Request is coming from exporter.php for collection manager tools
			$dwcaHandler->setCollArr($_POST['targetcollid']);
			if(array_key_exists('processingstatus',$_POST) && $_POST['processingstatus']){
				$dwcaHandler->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
			}
			if(array_key_exists('customfield1',$_POST) && $_POST['customfield1']){
				$dwcaHandler->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
			}
			if(array_key_exists('customfield2',$_POST) && $_POST['customfield2']){
				$dwcaHandler->addCondition($_POST['customfield2'],$_POST['customtype2'],$_POST['customvalue2']);
			}
			if(array_key_exists('customfield3',$_POST) && $_POST['customfield3']){
				$dwcaHandler->addCondition($_POST['customfield3'],$_POST['customtype3'],$_POST['customvalue3']);
			}
			if(array_key_exists('newrecs',$_POST) && $_POST['newrecs'] == 1){
				$dwcaHandler->addCondition('dbpk','NULL');
				$dwcaHandler->addCondition('catalognumber','NOTNULL');
			}
		}
		$outputFile = null;
		if($zip){
			//Ouput file is a zip file
			$includeIdent = (array_key_exists('identifications',$_POST)?1:0);
			$dwcaHandler->setIncludeDets($includeIdent);
			$images = (array_key_exists('images',$_POST)?1:0);
			$dwcaHandler->setIncludeImgs($images);
			
			$outputFile = $dwcaHandler->createDwcArchive('webreq');
			
		}
		else{
			//Output file is a flat occurrence file (not a zip file)
			$outputFile = $dwcaHandler->getOccurrenceFile();
		}
		//ob_start();
		$contentDesc = '';
		if($schema == 'dwc'){
			$contentDesc = 'Darwin Core ';
		}
		else{
			$contentDesc = 'Symbiota ';
		}
		$contentDesc .= 'Occurrence ';
		if($zip){
			$contentDesc .= 'Archive ';
		}
		$contentDesc .= 'File';
		header('Content-Description: '.$contentDesc);
		
		if($zip){
			header('Content-Type: application/zip');
		}
		elseif($format == 'csv'){
			header('Content-Type: text/csv; charset='.$charset);
		}
		else{
			header('Content-Type: text/html; charset='.$charset);
		}
		
		header('Content-Disposition: attachment; filename='.basename($outputFile));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($outputFile));
		ob_clean();
		flush();
		//od_end_clean();
		readfile($outputFile);
		unlink($outputFile);
	}
}
?>