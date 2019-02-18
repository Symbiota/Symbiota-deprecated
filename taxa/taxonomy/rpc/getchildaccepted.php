<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$tid = $_REQUEST['tid'];
$taxAuthId = array_key_exists('taxauthid',$_REQUEST)?$con->real_escape_string($_REQUEST['taxauthid']):'1';

$taxManager = new TaxonomyEditorManager('readonly');
$taxManager->setTaxAuthId($taxAuthId);
$retArr = $taxManager->getChildAccepted($tid);

echo json_encode($retArr);
?>