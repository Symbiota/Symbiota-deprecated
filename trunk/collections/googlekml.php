<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$charset);

$mapManager = new OccurrenceMapManager(); 
$mapManager->setMapType('occquery');

$kmlFilePath = $mapManager->writeKMLFile();
?>


   