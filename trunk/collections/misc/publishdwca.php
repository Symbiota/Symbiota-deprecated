<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDwcArchiver.php');

$dwcaManager = new OccurrenceDwcArchiver();
$dwcaManager->setCollId(3);
$dwcaManager->createDwcArchive();


//$dwcaManager->getDwcaItem();

?>