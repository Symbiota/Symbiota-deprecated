<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

$clid = $_REQUEST['clid'];
$rType = $_REQUEST['rtype'];

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);
$vManager->setCollectionVariables();

if($rType == 'fullcsv'){
	$vManager->downloadChecklistCsv();
}
elseif($rType == 'fullvoucherscsv'){
	$vManager->downloadVoucherCsv();
}
elseif($rType == 'pensoftcsv'){
	$vManager->downloadPensoftCsv();
}
elseif($rType == 'pensoftxlsx'){
	$vManager->downloadPensoftXlsx();
	exit;
}
elseif($rType == 'missingoccurcsv'){
	$vManager->exportMissingOccurCsv();
}
elseif($rType == 'problemtaxacsv'){
	$vManager->exportProblemTaxaCsv();
}
?>