<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/SpecUpload.php');
header("Content-Type: text/html; charset=".$charset);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$recLimit = array_key_exists('reclimit',$_REQUEST)?$_REQUEST['reclimit']:1000;
$pageIndex = array_key_exists('pageindex',$_REQUEST)?$_REQUEST['pageindex']:0;
$searchVar = array_key_exists('searchvar',$_REQUEST)?$_REQUEST['searchvar']:'';

$uploadManager = new SpecUpload();
$uploadManager->setCollId($collid);
$collMap = $uploadManager->getCollInfo();

$isEditor = 0;
if($SYMB_UID){
	//Set variables
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
		$isEditor = 1;
	}
}

$recArr = $uploadManager->getUploadMap(($recLimit*$pageIndex),$recLimit,$searchVar);

if(!$searchVar){
	$searchVar = 'TOTAL_TRANSFER';
}

$fileName = $searchVar.'_'.$collid.'_'.'upload.csv';

header ('Content-Type: text/csv');
header ("Content-Disposition: attachment; filename=\"$fileName\""); 

//Write column names out to file
if($recArr){
	$headerMap = array_keys($recArr[0]);
	$outstream = fopen("php://output", "w");
	fputcsv($outstream,$headerMap);
	
	foreach($recArr as $row){
		fputcsv($outstream,$row);
	}
	fclose($outstream);
}
else{
	echo "Recordset is empty.\n";
}