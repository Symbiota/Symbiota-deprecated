<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
include_once($serverRoot.'/classes/MappingShared.php');
header("Content-Type: text/html; charset=".$charset);

$kmlFields = array_key_exists('kmlFields',$_POST)?$_POST['kmlFields']:'';

$occurMapManager = new OccurrenceMapManager();
$sharedMapManager = new MappingShared();
 
$occurMapManager = new OccurrenceMapManager();
$mapWhere = $occurMapManager->getOccurSqlWhere();
$tArr = $occurMapManager->getTaxaArr();
$stArr = $occurMapManager->getSearchTermsArr();
$sharedMapManager->setSearchTermsArr($stArr);
$sharedMapManager->setTaxaArr($tArr);
if($kmlFields){
	$sharedMapManager->setFieldArr($kmlFields);
}
$coordArr = $sharedMapManager->getGeoCoords(0,false,$mapWhere);

$kmlFilePath = $sharedMapManager->writeKMLFile($coordArr);
?>


   