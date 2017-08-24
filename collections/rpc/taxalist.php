<?php
include_once('../../config/symbini.php');
include_once('../../classes/OccurrenceSearchSupport.php');

$supportManager = new OccurrenceSearchSupport();
$nameArr = $supportManager->getTaxaSuggest($_REQUEST['term'], $_REQUEST['t']);

echo json_encode($nameArr);
?>