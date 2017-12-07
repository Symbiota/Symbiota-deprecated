<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');

$collManager = new OccurrenceCollectionProfile();

$GBIFInstKey = $collManager->getGbifInstKey();

echo $GBIFInstKey;
?>