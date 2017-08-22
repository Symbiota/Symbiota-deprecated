<?php
/*
 * * ****  Accepts  ********************************************
 *
 * POST or GET requests
 *
 * ****  Input Variables  ********************************************
 *
 * term: User inputted string for which to auto-complete.
 * hideauth (optional): Hide authority from label of scientific name reutrn.
 * hideprotected (optional): Hide taxa flagged as sensitive from scientific name return.
 * taid (optional): Taxonomic thesaurus ID from which to narrow scientific name return.
 * limit (optional): Sets number of scientific names returned.
 *
 * * ****  Output  ********************************************
 *
 * JSON array of scientific names.
 *
 * Each scientific name contains a subarray with:
 *  id: Symbiota portal TID for that taxon.
 *  value: Suggested label for taxon (currently set to be same as index).
 *  author: Authority for taxon.
 *
 */

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyAPIManager.php');

$queryString = $_REQUEST['term'];
$hideAuth = array_key_exists('hideauth',$_REQUEST)?$_REQUEST['hideauth']:false;
$hideProtected = array_key_exists('hideprotected',$_REQUEST)?$_REQUEST['hideprotected']:false;
$taxAuthId = array_key_exists('taid',$_REQUEST)?$_REQUEST['taid']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:0;

$qHandler = new TaxonomyAPIManager();
$listArr = Array();

if($queryString){
    $qHandler->setHideAuth($hideAuth);
    $qHandler->setHideProtected($hideProtected);
    $qHandler->setTaxAuthId($taxAuthId);
    $qHandler->setLimit($limit);

    $listArr = $qHandler->generateAnyNameList($queryString);
    echo json_encode($listArr);
}
?>