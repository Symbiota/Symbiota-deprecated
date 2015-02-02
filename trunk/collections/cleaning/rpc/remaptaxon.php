<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=UTF-8");

$collid = $_REQUEST['collid'];
$oldSciname = $_REQUEST['oldsciname'];
$tid = $_REQUEST['tid'];
$newSciname = $_REQUEST['newsciname'];

$status = "0";
if($collid && $oldSciname && $tid && $newSciname){
	$cleanerManager = new OccurrenceCleaner();
	if($cleanerManager->remapOccurrenceTaxon($collid, $oldSciname, $tid, $newSciname)){
		$status = 1;
	}
}
echo $status;
?>