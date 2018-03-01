<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ChecklistFGExportManager.php');
header("Content-Type: text/html; charset=".$charset);

$imgID = array_key_exists("imgid",$_REQUEST)?$_REQUEST["imgid"]:0;

$dataArr = array();

$fgManager = new ChecklistFGExportManager();

if($imgID){
    $url = $fgManager->getImageUrl($imgID);
    echo $fgManager->getImageDataUrl($url);
}
?>