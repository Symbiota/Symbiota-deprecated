<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

$type = array_key_exists("kmltype",$_POST)?$_POST["kmltype"]:'';
$selections = array_key_exists('selectionskml',$_POST)?$_POST['selectionskml']:0;
$stArrJson = array_key_exists("starrkml",$_POST)?$_POST["starrkml"]:'';
$limit = array_key_exists("kmlreclimit",$_POST)?$_POST["kmlreclimit"]:'';
$stArr = Array();
if($stArrJson){
	$stArr = json_decode($stArrJson, true);
}

$mapManager = new MapInterfaceManager();
$fullCollList = $mapManager->getFullCollArr($stArr);
if($type=='selection' || $type=='dsselectionquery'){
	$coordArr = $mapManager->getSelectionGeoCoords($selections);
}
if($type=='fullquery'){
	$mapManager->setSearchTermsArr($stArr);
	$mapWhere = $mapManager->getSqlWhere();
	$coordArr = $mapManager->getCollGeoCoords($mapWhere,0,0);
}

$kmlFilePath = $mapManager->writeKMLFile($coordArr);
?>


   