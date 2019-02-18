<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceCollectionProfile.php');

$collManager = new OccurrenceCollectionProfile();

$GBIFInstKey = $collManager->getGbifInstKey();

echo $GBIFInstKey;
?>