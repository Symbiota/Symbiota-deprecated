<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
header("Content-Type: text/html; charset=".$CHARSET);


$queryTerm = $_REQUEST['term'];
$taxAuthId = array_key_exists('taid',$_REQUEST)?$con->real_escape_string($_REQUEST['taid']):'1';

$taxManager = new TaxonomyEditorManager('readonly');
$taxManager->setTaxAuthId($taxAuthId);
$retArr = $taxManager->getAcceptedTaxa($queryTerm);

echo json_encode($retArr);
?>