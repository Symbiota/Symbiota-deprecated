<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyCleaner.php');
header("Content-Type: text/html; charset=UTF-8");

$collid = $_REQUEST['collid'];
$oldSciname = $_REQUEST['oldsciname'];
$tid = $_REQUEST['tid'];
$newSciname = $_REQUEST['newsciname'];
$author = $_REQUEST['author'];
$idQualifier = $_REQUEST['idq'];

$status = '0';
if($collid && $oldSciname && $tid && $newSciname){
	$cleanerManager = new TaxonomyCleaner();
	if($cleanerManager->remapOccurrenceTaxon($collid, $oldSciname, $tid, $newSciname, $author, $idQualifier)){
		$status = '1';
	}
}
echo $status;
?>