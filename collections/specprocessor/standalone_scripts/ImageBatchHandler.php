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
	if(file_exists($serverRoot.'/classes/ImageBatchProcessor.php')) { 
		require_once($serverRoot.'/classes/ImageBatchProcessor.php');
	}
}
else{
	include_once('../../../config/symbini.php');
	if(file_exists('ImageBatchConnectionFactory.php')) { 
		include_once('ImageBatchConnectionFactory.php');
	}
	if(file_exists('ImageBatchProcessor.php')){
		require_once('ImageBatchProcessor.php');
	}
}

//-------------------------------------------------------------------------------------------//
$imageProcessor = new ImageBatchProcessor();

//Initiate log file
if(isset($silent) && $silent) $logMode = 2;
$imageProcessor->setLogMode($logMode);
if(!$logProcessorPath && $logPath) $logProcessorPath = $logPath;
$imageProcessor->setLogPath($logProcessorPath);
$imageProcessor->initProcessor($logTitle);

//Set remaining variables
$imageProcessor->setCollArr($collArr);
$imageProcessor->setDbMetadata($dbMetadata);
$imageProcessor->setSourcePathBase($sourcePathBase);
$imageProcessor->setTargetPathBase($targetPathBase);
$imageProcessor->setImgUrlBase($imgUrlBase);
$imageProcessor->setServerRoot($serverRoot);
if($webPixWidth) $imageProcessor->setWebPixWidth($webPixWidth);
if($tnPixWidth) $imageProcessor->setTnPixWidth($tnPixWidth);
if($lgPixWidth) $imageProcessor->setLgPixWidth($lgPixWidth);
if($webFileSizeLimit) $imageProcessor->setWebFileSizeLimit($webFileSizeLimit);
if($lgFileSizeLimit) $imageProcessor->setLgFileSizeLimit($lgFileSizeLimit);
$imageProcessor->setJpgQuality($jpgQuality);
$imageProcessor->setUseImageMagick($useImageMagickBatch);

if(isset($createWebImg) && $createWebImg) $imageProcessor->setCreateWebImg($createWebImg);
$imageProcessor->setCreateTnImg($createTnImg);
$imageProcessor->setCreateLgImg($createLgImg);
$imageProcessor->setKeepOrig($keepOrig);
$imageProcessor->setCreateNewRec($createNewRec);
$imageProcessor->setCopyOverImg($copyOverImg);


//Run process
$imageProcessor->batchLoadImages();
?>