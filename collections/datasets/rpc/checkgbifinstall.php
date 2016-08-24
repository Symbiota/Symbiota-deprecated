<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');

$collManager = new CollectionProfileManager();

$GBIFInstKey = $collManager->getGbifInstKey();

echo $GBIFInstKey;
?>