<?php
/*
 * * ****  Accepts  ********************************************
 *
 * POST or GET requests
 *
 * ****  Input Variables  ********************************************
 *
 * term: User inputted string for which to auto-complete.
 * limit (optional): Sets number of vernacular names returned.
 *
 * * ****  Output  ********************************************
 *
 * JSON array of vernacular names.
 *
 */

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyAPIManager.php');

$queryString = $_REQUEST['term'];
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:0;

$qHandler = new TaxonomyAPIManager();
$listArr = Array();

if($queryString){
    $qHandler->setLimit($limit);

    $listArr = $qHandler->generateVernacularList($queryString);
    echo json_encode($listArr);
}
?>