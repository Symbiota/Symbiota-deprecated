<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/MapInterfaceManager.php');

$stArrJson = $_REQUEST["starr"];
$occIndex = $_REQUEST['index'];
$recordCnt = $_REQUEST['reccnt'];
$mapType = $_REQUEST['maptype'];

$mapManager = new MapInterfaceManager();

$retArr = Array();

$stArr = json_decode($stArrJson, true);

if($stArr || ($mapType && $mapType == 'occquery')){
	if($stArr){
		$mapManager->setSearchTermsArr($stArr);
	}
	$mapWhere = $mapManager->getSqlWhere();
	$fullCollList = $mapManager->getFullCollArr($stArr);
	$retArr['recarr'] = $mapManager->getCollGeoCoords($mapWhere,$occIndex,1000);
	$retArr['rectot'] = $recordCnt;
}

echo json_encode($retArr);
?>