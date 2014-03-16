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
				header('Content-Description: Data File Transfer Error');
				header('Content-Type: text/plain');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				//echo 'Error: unable to create archive';
			}
		}
	}
}
else{
	$zip = (array_key_exists('zip',$_POST)?$_POST['zip']:0);
	$format = $_POST['format'];

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
		
	if($zip && ($schema == 'symbiota' || $schema == 'dwc')){

		$dwcaHandler = new DwcArchiverOccurrence();
		$dwcaHandler->setCharSetOut($cSet);
		$dwcaHandler->setSchemaType($schema);
		$dwcaHandler->setVerbose(0);
		
		$includeIdent = (array_key_exists('identifications',$_POST)?1:0);
		$dwcaHandler->setIncludeDets($includeIdent);
		$images = (array_key_exists('images',$_POST)?1:0);
		$dwcaHandler->setIncludeImgs($images);

		$dwcaHandler->setRedactLocalities($redactLocalities);
		if($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);

		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$occurManager = new OccurrenceManager();
			$dwcaHandler->setCustomWhereSql($occurManager->getSqlWhere());
		}

		$archiveFile = $dwcaHandler->createDwcArchive('webreq');
		
		if($archiveFile){
			//ob_start();
			if($schema == 'dwc'){
				header('Content-Description: Darwin Core Archive File');
			}
			else{
				header('Content-Description: Symbiota Archive File');
			}
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename='.basename($archiveFile));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($archiveFile));
			ob_clean();
			flush();
			//od_end_clean();
			readfile($archiveFile);
			unlink($archiveFile);
			exit;
		}
		else{
			header('Content-Description: DwC-A File Transfer Error');
			header('Content-Type: text/plain');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			echo 'Error: unable to create archive';
		}
	}
	else{
		$dlManager = new OccurrenceDownload();
		$dlManager->setSchemaType($schema);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
	
		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$occurManager = new OccurrenceManager();
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
	
		if($schema == "georef"){
			$dlManager->addCondition('decimalLatitude','NOTNULL','');
			$dlManager->addCondition('decimalLongitude','NOTNULL','');
			if(isset($_POST['customfield1'])){
				$dlManager->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
			}
		}
		elseif($schema == 'checklist'){
			$taxonFilterCode = array_key_exists("taxonFilterCode",$_POST)?$_POST["taxonFilterCode"]:0;
			$dlManager->setTaxonFilter($taxonFilterCode); 
		}
		else{
			
			if(array_key_exists('targetcollid',$_POST) && $_POST['targetcollid']){
				$dlManager->addCondition('collid','EQUALS',$_POST['targetcollid']);
			}
			if(array_key_exists('processingstatus',$_POST) && $_POST['processingstatus']){
				$dlManager->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
			}
			if(array_key_exists('customfield1',$_POST) && $_POST['customfield1']){
				$dlManager->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
			}
			if(array_key_exists('customfield2',$_POST) && $_POST['customfield2']){
				$dlManager->addCondition($_POST['customfield2'],$_POST['customtype2'],$_POST['customvalue2']);
			}
			if(array_key_exists('customfield3',$_POST) && $_POST['customfield3']){
				$dlManager->addCondition($_POST['customfield3'],$_POST['customtype3'],$_POST['customvalue3']);
			}
			if(array_key_exists('identifications',$_POST) && $_POST['identifications'] == 1){
				$dlManager->setIncludeIdentHistory(true);
			}
			if(array_key_exists('images',$_POST) && $_POST['images'] == 1){
				$dlManager->setIncludeImages(true);
			}
			if(array_key_exists('zip',$_POST) && $_POST['zip'] == 1){
				$dlManager->setZipFile(true);
			}
			if(array_key_exists('newrecs',$_POST) && $_POST['newrecs'] == 1){
				$dlManager->addCondition('dbpk','NULL');
				$dlManager->addCondition('catalognumber','NOTNULL');
			}
		}
		$dlManager->downloadData();
	}
}
?>