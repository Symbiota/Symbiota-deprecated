<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorDupes.php');

$collName = array_key_exists('cname',$_REQUEST)?trim($_REQUEST['cname']):'';
$collNum = array_key_exists('cnum',$_REQUEST)?trim($_REQUEST['cnum']):'';
$collDate = array_key_exists('cdate',$_REQUEST)?trim($_REQUEST['cdate']):'';
$ometid = array_key_exists('ometid',$_REQUEST)?trim($_REQUEST['ometid']):'';
$exsNumber = array_key_exists('exsnumber',$_REQUEST)?trim($_REQUEST['exsnumber']):'';
$currentOccid = array_key_exists('curoccid',$_REQUEST)?trim($_REQUEST['curoccid']):0;

$dupeManager = new OccurrenceEditorDupes();
$retStr = $dupeManager->getDupes($collName, $collNum, $collDate, $ometid, $exsNumber, $currentOccid);
echo $retStr;
?>