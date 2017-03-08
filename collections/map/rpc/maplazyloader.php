<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/MapInterfaceManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

$stArrJson = $_REQUEST["starr"];
$occIndex = $_REQUEST['index'];
$recordCnt = $_REQUEST['reccnt'];
$mapType = $_REQUEST['maptype'];

$mapManager = new MapInterfaceManager();
$solrManager = new SOLRManager();

$retArr = Array();

$stArr = json_decode($stArrJson, true);

if($stArr || ($mapType && $mapType == 'occquery')){
    if($stArr){
        $mapManager->setSearchTermsArr($stArr);
    }
    $mapWhere = $mapManager->getSqlWhere();
    $fullCollList = $mapManager->getFullCollArr($stArr);
    if($SOLR_MODE){
        $solrManager->setSearchTermsArr($stArr);
        $collArr = $mapManager->getCollArr();
        $solrManager->setCollArr($collArr);
        $solrArr = $solrManager->getGeoArr($occIndex,1000);
        $retArr['recarr'] = $solrManager->translateSOLRGeoCollList($solrArr);
        $retArr['rectot'] = $solrManager->getRecordCnt();
    }
    else{
        $retArr['recarr'] = $mapManager->getCollGeoCoords($mapWhere,$occIndex,1000);
        $retArr['rectot'] = $recordCnt;
    }
}

echo json_encode($retArr);
?>