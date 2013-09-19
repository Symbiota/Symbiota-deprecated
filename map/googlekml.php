<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
include_once($serverRoot.'/classes/MappingShared.php');
header("Content-Type: text/html; charset=".$charset);

$occurMapManager = new OccurrenceMapManager();
$sharedMapManager = new MappingShared();
 
$occurMapManager = new OccurrenceMapManager();
$mapWhere = $occurMapManager->getOccurSqlWhere();
$tArr = $occurMapManager->getTaxaArr();
$stArr = $occurMapManager->getSearchTermsArr();
$sharedMapManager->setSearchTermsArr($stArr);
$sharedMapManager->setTaxaArr($tArr);
$coordArr = $sharedMapManager->getGeoCoords(0,false,$mapWhere);

$kmlFilePath = $sharedMapManager->writeKMLFile($coordArr);
?>


   