<?php
require_once('../../../config/symbini.php');
require_once($serverRoot.'/classes/ImageIPlantProcessor.php');

$imageProcessor = new ImageIPlantProcessor();

//Run process
$imageProcessor->initProcessor();
$imageProcessor->batchProcessImages();
?>