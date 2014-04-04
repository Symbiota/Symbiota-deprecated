<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceChecklistManager.php');
header("Content-Type: text/html; charset=".$charset);

$checklistManager = new OccurrenceChecklistManager();

$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)&&$_REQUEST["taxonfilter"]?$_REQUEST["taxonfilter"]:1;
$interface = array_key_exists("interface",$_REQUEST)&&$_REQUEST["interface"]?$_REQUEST["interface"]:"checklist";
//$taxonFilter = 1;
$dynClid = $checklistManager->buildSymbiotaChecklist($taxonFilter);
if($interface == "key"){
	header("Location: ../ident/key.php?dynclid=".$dynClid."&taxon=All Species");
}
else{
	header("Location: ../checklists/checklist.php?dynclid=".$dynClid);
}

?>
