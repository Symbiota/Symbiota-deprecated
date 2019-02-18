<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ChecklistFGExportManager.php');
header("Content-Type: text/html; charset=".$charset);

$imgID = array_key_exists("imgid",$_REQUEST)?$_REQUEST["imgid"]:0;

$dataArr = array();

$fgManager = new ChecklistFGExportManager();
$returnStr = '';

if($imgID){
    $imgIDArr = json_decode($imgID,true);
    foreach($imgIDArr as $imId){
        $tempStr = '';
        $url = $fgManager->getImageUrl($imId);
        $dataUrl = $fgManager->getImageDataUrl($url);
        if($dataUrl){
            $tempStr = $imId.'-||-'.$dataUrl;
            $returnStr .= $tempStr.'-****-';
        }
    }
}
echo $returnStr;
?>