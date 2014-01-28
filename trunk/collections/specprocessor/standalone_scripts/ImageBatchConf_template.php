<?php 
//Variables needing security

//Server root is generally needed since scripts may be run from outside of folder (including crontab runs)
//This is the path base folder of portal (e.g. trunk)
$serverRoot = '';

//Base folder containing herbarium folder ; read access needed
$sourcePathBase = '';
//Folder where images are to be placed; write access needed
$targetPathBase = '';

//Url base needed to build image URL that will be saved in DB
//Only needed if scripts are run on an exteral server
$imgUrlBase = '';

//Title (e.g. CNALH) and Path to where log files will be placed
$logTitle = '';
$logProcessorPath = $sourcePathBase.'/logs';
//0 = silent, 1 = html, 2 = log file
$logMode = 2;

//If record matching PK is not found, should a new blank record be created?
$createNewRec = true;

//Weather to copyover images with matching names (includes path) or rename new image and keep both
$copyOverImg = true;

$webPixWidth = 1400;
$tnPixWidth = 200;
$lgPixWidth = 3600;
$webFileSizeLimit = 300000;
$lgFileSizeLimit = 3000000;

//Whether to use ImageMagick for creating thumbnails and web images. ImageMagick must be installed on server.
// 0 = use GD library (default), 1 = use ImageMagick
$useImageMagickBatch = 0;

//Value between 0 and 100
$jpgQuality = 80;

$createWebImg = 1;
$createTnImg = 1;
$createLgImg = 1;

$keepOrig = 1;

//0 = write image metadata to file; 
//1 = write metadata to a Symbiota database (connection variables must be set)
$dbMetadata = 1;

/**
 * Array of parameters for collections to process.
 * collid => array( 
 *     'pmterm' => '/A(\d{8})\D+/', 		// regular expression to match collectionCode and catalogNumber in filename, first backreference is used as the catalogNumber. 
 *     'prpatt' => '/^/',           		// optional regular expression for match on catalogNumber to be replaced with prrepl. 
 *     'prrepl' => 'barcode-',       		// optional replacement to apply for prpatt matches on catalogNumber.
 *     										// given above description, 'A01234567.jpg' will yield catalogNumber = 'barcode-01234567'
 *     'sourcePathFrag' => 'asu/lichens/'	// optional path fragment appended to $sourcePathBase that is specific to particular collection. Not typcially needed.  
 * )
 * 
 */

$collArr = array(
	1 => array('sourcePathFrag' => 'abc/lichens', 'pmterm' => '/^ABC(\d{8})\D*/'),
	2 => array('sourcePathFrag' => 'def/vasc/', 'pmterm' => '/^DEF(\d{8})\D*/')
);