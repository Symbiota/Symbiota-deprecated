<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpatialModuleManager.php');

$spatialManager = new SpatialModuleManager();
$layersArr = Array();
if(isset($GEOSERVER_URL) && isset($GEOSERVER_LAYER_WORKSPACE)){
    $layersArr = $spatialManager->getLayersArr();
}
echo json_encode($layersArr);
?>