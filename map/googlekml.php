<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
include_once($serverRoot.'/classes/MappingShared.php');
header("Content-Type: text/html; charset=".$charset);

$kmlFields = array_key_exists('kmlFields',$_POST)?$_POST['kmlFields']:'';
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

$occurMapManager = new OccurrenceMapManager();
$sharedMapManager = new MappingShared();
 
$occurMapManager = new OccurrenceMapManager();
if($stArrCollJson && $stArrSearchJson){
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
	$occurMapManager->setSearchTermsArr($stArr);
}
$mapWhere = $occurMapManager->getOccurSqlWhere();
$tArr = $occurMapManager->getTaxaArr();
$stArr = $occurMapManager->getSearchTermsArr();
$sharedMapManager->setSearchTermsArr($stArr);
$sharedMapManager->setTaxaArr($tArr);
if($kmlFields){
	$sharedMapManager->setFieldArr($kmlFields);
}
$coordArr = $sharedMapManager->getGeoCoords($mapWhere);

$kmlFilePath = $sharedMapManager->writeKMLFile($coordArr);
?>


   