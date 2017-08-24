<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');

$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$catNum = array_key_exists("catalognumber",$_REQUEST)?$_REQUEST["catalognumber"]:'';
$sciName = array_key_exists("sciname",$_REQUEST)?$_REQUEST["sciname"]:'';

$occManager = new OccurrenceEditorDeterminations();

$recordListHtml = '';
if($collid){
	$recordListHtml = $occManager->getBulkDetRows($collid,$catNum,$sciName,'');
}

echo $recordListHtml;
?>