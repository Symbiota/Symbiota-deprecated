<?php
include_once('../../config/symbini.php');
include_once('../../classes/TaxonSearchSupport.php');

$searchManager = new TaxonSearchSupport();
$nameArr = $searchManager->getTaxaSuggest($_REQUEST['term'], (array_key_exists('t',$_REQUEST)?$_REQUEST['t']:1));

echo json_encode($nameArr);
?>