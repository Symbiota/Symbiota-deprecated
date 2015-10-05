<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownload.php');

$format = isset($_REQUEST['format'])&&$_REQUEST['format']?$_REQUEST['format']:'rss';
$days = isset($_REQUEST['days'])?$_REQUEST['days']:0;
$limit = isset($_REQUEST['limit'])?$_REQUEST['limit']:0;

$activityManager = new OccurrenceDownload();

header('Content-Description: '.$GLOBALS['DEFAULT_TITLE'].' Data Entry Activity');
header('Content-Type: '.($format=='json'?'application/json':'text/xml'));
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache");
header("Pragma: no-cache");

echo $activityManager->getDataEntryActivity($format,$days,$limit);
?>