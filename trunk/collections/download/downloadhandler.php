<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownloadManager.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	
	$downloadType = array_key_exists("dltype",$_REQUEST)?$_REQUEST["dltype"]:"specimen"; 
	$taxonFilterCode = array_key_exists("taxonFilterCode",$_REQUEST)?$_REQUEST["taxonFilterCode"]:0; 
	
	$occurManager = new OccurrenceManager();
	$dlManager = new OccurrenceDownloadManager();
	$dlManager->setSqlWhere($occurManager->getSqlWhere());
 
    if($downloadType == "checklist"){
		$dlManager->downloadChecklistCsv($taxonFilterCode);
    }
    elseif($downloadType == "georef"){
		$dlManager->downloadGeorefCsv();
    }
    elseif($downloadType == "darwincore_text"){
		$dlManager->setSchemaType('dwc');
    	$dlManager->downloadSpecimens();
    }
    elseif($downloadType == "darwincore_xml"){
		//$dlManager->downloadSpecimenDarwinCoreXml();  
    }
    else{
		//$dlManager->setCharSetOut($cSet);
		$dlManager->setSchemaType('symbiota');
    	$dlManager->downloadSpecimens();
    }
?>