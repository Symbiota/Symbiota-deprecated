<?php
date_default_timezone_set('America/Phoenix');

require_once('ImageBatchConf.php');
if(isset($serverRoot)) { 
	if(file_exists($serverRoot.'/config/symbini.php')){
		include_once($serverRoot.'/config/symbini.php');
	}
	if(file_exists($serverRoot.'/collections/specprocessor/standalone_scripts/ImageBatchConnectionFactory.php')) { 
		include_once($serverRoot.'/collections/specprocessor/standalone_scripts/ImageBatchConnectionFactory.php');
	}
	if(file_exists($serverRoot.'/classes/ImageIPlantProcessor.php')) { 
		require_once($serverRoot.'/classes/ImageIPlantProcessor.php');
	}
}
else{
	include_once('../../../config/symbini.php');
	if(file_exists('ImageBatchConnectionFactory.php')) { 
		include_once('ImageBatchConnectionFactory.php');
	}
	if(file_exists('ImageIPlantProcessor.php')){
		require_once('ImageIPlantProcessor.php');
	}
}

//-------------------------------------------------------------------------------------------//
$imageProcessor = new ImageIPlantProcessor();

//Initiate log file
$imageProcessor->setLogMode($logMode);
$imageProcessor->setLogPath($logProcessorPath);

//Set remaining variables
$imageProcessor->setCollArr($collArr);

$imageProcessor->setCreateNewRec($createNewRec);

//Run process
$imageProcessor->initProcessor($logTitle);
$imageProcessor->batchProcessImages();
?>