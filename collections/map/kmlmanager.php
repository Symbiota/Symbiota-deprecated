<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$type = array_key_exists("kmltype",$_POST)?$_POST["kmltype"]:'';
$selections = array_key_exists('selectionskml',$_POST)?$_POST['selectionskml']:0;
$limit = array_key_exists("kmlreclimit",$_POST)?$_POST["kmlreclimit"]:'';

$mapManager = new OccurrenceMapManager();
if($type=='selection' || $type=='dsselectionquery'){
	$coordArr = $mapManager->getSelectionGeoCoords($selections);
}
if($type=='fullquery'){
	$coordArr = $mapManager->getCoordinateMap(0,0);
}

$kmlFilePath = $mapManager->writeKMLFile($coordArr);
?>