<?php
require_once('../../../config/symbini.php');
require_once($serverRoot.'/classes/ImageProcessor.php');

$imageProcessor = new ImageProcessor();

//Run process
$imageProcessor->initProcessor();
$imageProcessor->processIPlantImages();
?>