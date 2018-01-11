<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonSearchSupport.php');

$term = $_REQUEST['term'];
$taxonType = (array_key_exists('t',$_REQUEST)?$_REQUEST['t']:0);

if(!is_numeric($taxonType)) $taxonType = 0;
if(isset($DEFAULT_TAXON_SEARCH) && !$taxonType) $taxonType = $DEFAULT_TAXON_SEARCH;

$searchManager = new TaxonSearchSupport();
$nameArr = $searchManager->getTaxaSuggest($_REQUEST['term'], $taxonType);

echo json_encode($nameArr);
?>