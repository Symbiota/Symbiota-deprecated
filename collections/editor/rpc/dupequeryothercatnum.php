<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');

$otherCatNum = array_key_exists('othercatnum',$_POST)?trim($_POST['othercatnum']):'';
$collid = array_key_exists('collid',$_POST)?trim($_POST['collid']):0;
$currentOccid = array_key_exists('occid',$_POST)?trim($_POST['occid']):0;

$dupeManager = new OccurrenceDuplicate();
$retStr = $dupeManager->getDupesOtherCatalogNumbers($otherCatNum, $collid, $currentOccid);
echo 'ocnum:'.implode(',',$retStr);
?>