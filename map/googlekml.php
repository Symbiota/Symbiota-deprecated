<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
include_once($SERVER_ROOT.'/classes/MappingShared.php');
header("Content-Type: text/html; charset=".$CHARSET);

$kmlFields = array_key_exists('kmlFields',$_POST)?$_POST['kmlFields']:'';

$occurManager = new OccurrenceManager();
$sharedMapManager = new MappingShared();

$mapWhere = $occurManager->getSqlWhere();
$tArr = $occurManager->getTaxaArr();
$sharedMapManager->setTaxaArr($tArr);
if($kmlFields){
	$sharedMapManager->setFieldArr($kmlFields);
}
$coordArr = $sharedMapManager->getGeoCoords($mapWhere);

$kmlFilePath = $sharedMapManager->writeKMLFile($coordArr);
?>