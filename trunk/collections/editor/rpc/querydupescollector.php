<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorDupes.php');

$collName = trim($_REQUEST['cname']);
$collNum = array_key_exists('cnum',$_REQUEST)?trim($_REQUEST['cnum']):'';
$collDate = array_key_exists('cdate',$_REQUEST)?trim($_REQUEST['cdate']):'';
$currentOccid = array_key_exists('curoccid',$_REQUEST)?trim($_REQUEST['curoccid']):0;

$dupeManager = new OccurrenceEditorDupes();
$occArr = $dupeManager->getDupesCollector($collName, $collNum, $collDate, $currentOccid);
if($occArr){
	echo implode(',',$occArr);
}
else{
	echo '';
}
?>