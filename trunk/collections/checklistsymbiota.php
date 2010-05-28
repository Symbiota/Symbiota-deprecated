<?php
 header("Content-Type: text/html; charset=ISO-8859-1");
 include_once("../util/symbini.php");
 include_once("util/ChecklistManager.php");

 $checklistManager = new ChecklistManager();

 //$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:1;
 //if(!$taxonFilter) $taxonFilter = 1;
 $taxonFilter = 1;
 $symClid = $checklistManager->buildSymbiotaChecklist($taxonFilter);
 header("Location: ../ident/key.php?crumburl=../collections/checklist.php&crumbtitle=Dynamic%20Checklist&symclid=".$symClid."&taxon=All Species");

?>
