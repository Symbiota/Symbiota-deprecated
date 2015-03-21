<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	
$clid = $_REQUEST['clid']; 
$rType = $_REQUEST['rtype']; 
	
$vManager = new ChecklistVoucherAdmin();

$vManager->setClid($clid);

if($rType == 'missingoccurcsv'){
	$vManager->exportMissingOccurCsv();
}
elseif($rType == 'problemtaxacsv'){
	$vManager->exportProblemTaxaCsv();
}
elseif($rType == 'fullvoucherscsv'){
	$vManager->downloadDatasetCsv();
}

?>