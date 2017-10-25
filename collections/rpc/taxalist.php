<?php
include_once('../../config/symbini.php');
include_once('../../classes/OccurrenceSearchSupport.php');

$supportManager = new OccurrenceSearchSupport();
$nameArr = $supportManager->getTaxaSuggest($_REQUEST['term'], (array_key_exists('t',$_REQUEST)?$_REQUEST['t']:1));

echo json_encode($nameArr);
?>