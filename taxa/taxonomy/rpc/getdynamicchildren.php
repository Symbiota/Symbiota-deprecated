<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyDisplayManager.php');

$objId = array_key_exists('id',$_REQUEST)?$_REQUEST['id']:0;
$targetId = array_key_exists('targetid',$_REQUEST)?$_REQUEST['targetid']:0;
$taxAuthId = array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:1;
$displayAuthor = array_key_exists('authors',$_REQUEST)?$_REQUEST['authors']:0;

$taxonManager = new TaxonomyDisplayManager();

$taxonManager->setTaxAuthId($taxAuthId);
$taxonManager->setDisplayAuthor($displayAuthor);

$retArr = $taxonManager->getDynamicChildren($objId,$targetId);
echo json_encode($retArr);
?>