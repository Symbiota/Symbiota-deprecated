<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GardenSearchManager.php');

$searchJson = isset($_REQUEST["searchJson"])?$_REQUEST["searchJson"]:'';
$display = isset($_REQUEST["display"])?$_REQUEST["display"]:'';

$gsManager = new GardenSearchManager();

$dataArr = Array();

if($searchJson != '[]' && $display){
    $gsManager->setDisplay($display);
    $gsManager->setSearchParams($searchJson);
    $gsManager->setSQLWhereArr();
    $gsManager->setSQL();
    $dataArr = $gsManager->getDataArr();
    echo json_encode($dataArr);
}
else{
    echo 'empty';
}
?>