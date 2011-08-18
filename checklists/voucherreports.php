<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistManager.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	
$clid = $_REQUEST['clid']; 
$rType = $_REQUEST['rtype']; 
	
$clManager = new ChecklistManager();

$clManager->setClValue($clid);

if($rType == 'missingoccurcsv'){
	$clManager->exportMissingOccurCsv();
}
?>