<?php
include_once("../../util/symbini.php");
include_once("../util/MapManager.php");
header("Content-Type: text/html; charset=".$charset);

 $mapManager = new MapManager(); 

  $kmlFilePath = $mapManager->writeKMLFile();
?>


   