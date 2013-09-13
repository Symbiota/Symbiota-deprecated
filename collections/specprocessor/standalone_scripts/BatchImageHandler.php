<?php
date_default_timezone_set('America/Phoenix');
include_once('../../../config/dbconnection.php');
require_once('BatchImageConf.php');
require_once('BatchImageProcessor.php');
include_once('BatchImageConnectionFactory.php');

// Check for the symbiota class files used herein for parsing 
// batch files of xml formatted strucutured data.
// Fail gracefully if they aren't available.
// Note also that class_exists() is checked for before
// invocation of these parsers in processFolder().
if (file_exists("$symbiotaClassPath/SpecProcessorGPI.php")) { 
	@require_once("$symbiotaClassPath/SpecProcessorGPI.php");
}
if (file_exists("$symbiotaClassPath/SpecProcessorNEVP.php")) {  
	@require_once("$symbiotaClassPath/SpecProcessorNEVP.php");
}

//-------------------------------------------------------------------------------------------//
$imageProcessor = new BatchImageProcessor($logPath,$logTitle);

//Set variables
$imageProcessor->setCollArr($collArr);
$imageProcessor->setDbMetadata($dbMetadata);
$imageProcessor->setSourcePathBase($sourcePathBase);
$imageProcessor->setTargetPathBase($targetPathBase);
$imageProcessor->setImgUrlBase($imgUrlBase);
$imageProcessor->setServerRoot($serverRoot);
$imageProcessor->setSymbiotaClassPath($symbiotaClassPath);
$imageProcessor->setWebPixWidth($webPixWidth);
$imageProcessor->setTnPixWidth($tnPixWidth);
$imageProcessor->setLgPixWidth($lgPixWidth);
$imageProcessor->setJpgCompression($jpgCompression);
$imageProcessor->setUseImageMagick($useImageMagick);

$imageProcessor->setCreateTnImg($createTnImg);
$imageProcessor->setCreateLgImg($createLgImg);
$imageProcessor->setKeepOrig($keepOrig);
$imageProcessor->setCreateNewRec($createNewRec);
$imageProcessor->setCopyOverImg($copyOverImg);

$imageProcessor->setSilent($silent);

//Run process
$imageProcessor->batchLoadImages();

?>