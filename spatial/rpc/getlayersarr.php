<?php
include_once('../../config/symbini.php');
include_once('../../config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/SpatialModuleManager.php');
$con = MySQLiConnectionFactory::getCon("readonly");

$spatialManager = new SpatialModuleManager();
$layersArr = Array();
$layersArr = $spatialManager->getLayersArr();
echo json_encode($layersArr);
?>