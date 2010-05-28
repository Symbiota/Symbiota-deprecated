<?php
 header("Content-Type: text/html; charset=ISO-8859-1");
 include_once("../../util/symbini.php");
 include_once("../util/MapManager.php");

 $mapManager = new MapManager(); 

  $kmlFilePath = $mapManager->writeKMLFile();
?>


   