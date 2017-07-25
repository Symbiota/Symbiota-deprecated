<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
include_once($SERVER_ROOT.'/classes/MappingShared.php');
header("Content-Type: text/html; charset=".$CHARSET);

$kmlFields = array_key_exists('kmlFields',$_POST)?$_POST['kmlFields']:'';
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

$occurManager = new OccurrenceManager();
$sharedMapManager = new MappingShared();

if($stArrCollJson && $stArrSearchJson){
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
	$occurManager->setSearchTermsArr($stArr);

}
$mapWhere = $occurManager->getSqlWhere();
$tArr = $occurManager->getTaxaArr();
$stArr = $occurManager->getSearchTermsArr();
$sharedMapManager->setSearchTermsArr($stArr);
$sharedMapManager->setTaxaArr($tArr);
if($kmlFields){
	$sharedMapManager->setFieldArr($kmlFields);
}
$coordArr = $sharedMapManager->getGeoCoords($mapWhere);

$kmlFilePath = $sharedMapManager->writeKMLFile($coordArr);
?>