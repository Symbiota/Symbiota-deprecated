<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownload.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$downloadType = array_key_exists("dltype",$_REQUEST)?$_REQUEST["dltype"]:"specimen"; 
$taxonFilterCode = array_key_exists("taxonFilterCode",$_REQUEST)?$_REQUEST["taxonFilterCode"]:0; 
$targetCollid = array_key_exists("targetcollid",$_REQUEST)?$_REQUEST["targetcollid"]:0; 

$dlManager = new OccurrenceDownload();

if($action == 'Download Records'){
	if($targetCollid) $dlManager->addCondition('collid','EQUALS',$targetCollid);
	if($_POST['processingstatus']){
		$dlManager->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
	}
	$dlManager->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
	$dlManager->addCondition($_POST['customfield2'],$_POST['customtype2'],$_POST['customvalue2']);
	if(isset($_POST['schema'])) $dlManager->setSchemaType($_POST['schema']);
	$dlManager->setDelimiter($_POST['format']);
	$dlManager->setCharSetOut($_POST['cset']);
	/*
	if(array_key_exists('identifications',$_POST) && $_POST['identifications'] == 1){
		$dlManager->setIncludeIdentHistory(true);
	}
	if(array_key_exists('images',$_POST) && $_POST['images'] == 1){
		$dlManager->setIncludeImages(true);
	}
	//Export as an archive file
	*/
	if(array_key_exists('newrecs',$_POST) && $_POST['newrecs'] == 1){
		$dlManager->addCondition('dbpk','NULL');
		$dlManager->addCondition('catalognumber','NOTNULL');
	} 
}
else{	
	$occurManager = new OccurrenceManager();
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
    }
}
//$dlManager->getSql();
$dlManager->downloadSpecimens();
?>