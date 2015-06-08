<?php 
//Title (e.g. CNALH) and Path to where log files will be placed
$logTitle = '';
$logProcessorPath = $sourcePathBase.'/logs';
//0 = silent, 1 = html, 2 = log file
$logMode = 2;

//If record matching PK is not found, should a new blank record be created?
$createNewRec = true;


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
	1 => array('pmterm' => '/^ABC(\d{8})\D*/'),
	2 => array('pmterm' => '/^DEF(\d{8})\D*/')
);
?>