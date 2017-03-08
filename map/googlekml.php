<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');
include_once($SERVER_ROOT.'/classes/MappingShared.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$kmlFields = array_key_exists('kmlFields',$_POST)?$_POST['kmlFields']:'';
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

$occurMapManager = new OccurrenceMapManager();
$sharedMapManager = new MappingShared();
$solrManager = new SOLRManager();

$occWhereStr = '';
 
$occurMapManager = new OccurrenceMapManager();
if($stArrCollJson && $stArrSearchJson){
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
	$occurMapManager->setSearchTermsArr($stArr);

    if($SOLR_MODE){
        $solrManager->setSearchTermsArr($stArr);
    	$occArr = $solrManager->getOccArr(true);
        if($occArr){
            $occWhereStr = 'WHERE o.occid IN('.implode(',',$occArr).') ';
        }
    }
}
if($SOLR_MODE && $occWhereStr){
    $mapWhere = $occWhereStr;
}
else{
    $mapWhere = $occurMapManager->getOccurSqlWhere();
}
$tArr = $occurMapManager->getTaxaArr();
$stArr = $occurMapManager->getSearchTermsArr();
$sharedMapManager->setSearchTermsArr($stArr);
$sharedMapManager->setTaxaArr($tArr);
if($kmlFields){
	$sharedMapManager->setFieldArr($kmlFields);
}
$coordArr = $sharedMapManager->getGeoCoords($mapWhere);

$kmlFilePath = $sharedMapManager->writeKMLFile($coordArr);
?>


   