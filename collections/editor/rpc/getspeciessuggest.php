<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorServices.php');
header("Content-Type: application/json; charset=".$CHARSET);

$term = $_REQUEST['term'];

$searchManager = new OccurrenceEditorServices();
$retArr = $searchManager->getSpeciesSuggest($term);

echo json_encode($retArr);
?>