<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ChecklistFGExportManager.php');
header("Content-Type: text/html; charset=".$charset);

$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0;
$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:1;
$index = array_key_exists("start",$_REQUEST)?$_REQUEST["start"]:0;
$recLimit = array_key_exists("rows",$_REQUEST)?$_REQUEST["rows"]:0;
$photogJson = array_key_exists("photogArr",$_REQUEST)?$_REQUEST["photogArr"]:'';
$photoNum = array_key_exists("photoNum",$_REQUEST)?$_REQUEST["photoNum"]:0;

$dataArr = array();

$fgManager = new ChecklistFGExportManager();
if($clValue){
    $fgManager->setClValue($clValue);
}
elseif($dynClid){
    $fgManager->setDynClid($dynClid);
}
$fgManager->setSqlVars();
$fgManager->setThesFilter($thesFilter);
$fgManager->setLanguage($LANG_TAG);
$fgManager->setRecIndex($index);
$fgManager->setRecLimit($recLimit);
$fgManager->setMaxPhoto($photoNum);
$fgManager->setPhotogJson($photogJson);

if($clValue || $dynClid){
    $fgManager->primeDataArr();
    $fgManager->primeOrderData();
    $fgManager->primeDescData();
    $fgManager->primeVernaculars();
    $fgManager->primeImages();
    $dataArr = $fgManager->getDataArr();
}
echo json_encode($dataArr);
?>