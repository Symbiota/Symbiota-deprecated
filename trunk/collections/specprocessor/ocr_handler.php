<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorOcr.php');

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;


$ocrManager = new SpecProcessorOcr();
 
$ocrManager->batchOcrUnprocessed($collId);

?>