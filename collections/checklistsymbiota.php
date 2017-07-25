<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceChecklistManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$checklistManager = new OccurrenceChecklistManager();

$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)&&$_REQUEST["taxonfilter"]?$_REQUEST["taxonfilter"]:1;
$interface = array_key_exists("interface",$_REQUEST)&&$_REQUEST["interface"]?$_REQUEST["interface"]:"checklist";
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

//Sanitation
if(!is_numeric($taxonFilter)) $taxonFilter = 1;

if($stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$collStArr = json_decode($stArrCollJson, true);
	$stArr= json_decode($stArrSearchJson, true);
	if($collStArr) $stArr = array_merge($stArr,$collStArr);
	$checklistManager->setSearchTermsArr($stArr);
}

//$taxonFilter = 1;
$dynClid = $checklistManager->buildSymbiotaChecklist($taxonFilter);
if($interface == "key"){
	header("Location: ../ident/key.php?dynclid=".$dynClid."&taxon=All Species");
}
else{
	header("Location: ../checklists/checklist.php?dynclid=".$dynClid);
}
?>