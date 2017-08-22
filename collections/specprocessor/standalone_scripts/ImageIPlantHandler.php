<?php
require_once('../../../config/symbini.php');
require_once($SERVER_ROOT.'/classes/ImageProcessor.php');

$imageProcessor = new ImageProcessor();

//Run process
$imageProcessor->initProcessor();
$imageProcessor->processIPlantImages();
?>