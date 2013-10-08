<?php 
//Variables needing security
//Base folder containing herbarium folder ; read access needed
$sourcePathBase = '';
//Folder where images are to be placed; write access needed
$targetPathBase = '';
//Url base needed to build image URL that will be saved in DB
//Only needed if scripts are run on an exteral server
$imgUrlBase = '';
//Title (e.g. CNALH) and Path to where log files will be placed
$logTitle = '';
$logPath = $sourcePathBase.'/logs';
//Full path to Symbiota project root folder
$serverRoot = '';
// Path to Symbiota Class Files, set to null if class files are 
// not available.  Correct path is required for the processing of 
// xml batch files.
$symbiotaClassPath = $serverRoot."/classes/";

//If silent is set to true, script will not produce a log file.
$silent = false;
//If record matching PK is not found, should a new blank record be created?
$createNewRec = true;
//Weather to copyover images with matching names (includes path) or rename new image and keep both
$copyOverImg = true;

$webPixWidth = 800;
$tnPixWidth = 130;
$lgPixWidth = 2000;
$webFileSizeLimit = 300000;

//Whether to use ImageMagick for creating thumbnails and web images. ImageMagick must be installed on server.
// 0 = use GD library (default), 1 = use ImageMagick
$useImageMagick = 0;
//Value between 0 and 100
$jpgCompression = 80;

//Create thumbnail versions of image
$createTnImg = 1;
//Create large version of image, given source image is large enough
$createLgImg = 1;
$keepOrig = 1;

//0 = write image metadata to file; 1 = write metadata to a Symbiota database (connection variables must be set)
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
	7 => array('sourcePathFrag' => 'brit/bryophytes/', 'pmterm' => '/^(BRIT\d{1,7})\D*/', 'prpatt' => '/^/', 'prrepl' => 'barcode-'),
	8 => array('sourcePathFrag' => 'khd/vasc/', 'pmterm' => '/^KHD(\d{8})\D*/')
);