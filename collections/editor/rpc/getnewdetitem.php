<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');

$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:'';
$catNum = array_key_exists("catalognumber",$_REQUEST)?$_REQUEST["catalognumber"]:'';
$sciName = array_key_exists("sciname",$_REQUEST)?$_REQUEST["sciname"]:'';

$retArr = array();
if(is_numeric($collid)){
	$occManager = new OccurrenceEditorDeterminations();
	$occManager->setCollId($collid);
	$retArr = $occManager->getNewDetItem($catNum,$sciName);
}

echo json_encode($retArr);
?>