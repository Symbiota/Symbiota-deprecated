<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorDupes.php');

$ometid = $_REQUEST['ometid'];
$exsnumber = trim($_REQUEST['exsnumber']);
$occid = trim($_REQUEST['occid']);

if($SYMB_UID && $ometid && $exsnumber){
	$dupeManager = new OccurrenceEditorDupes();
	$occArr = $dupeManager->getDupesExsiccati($ometid, $exsnumber, $occid);
	if($occArr){
		echo implode(',',$occArr);
	}
	else{
		echo '';
	}
}
?>