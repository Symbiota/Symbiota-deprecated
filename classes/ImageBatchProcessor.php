<?php
// Used by /trunk/collections/specprocessor/standalone_scripts/ImageBatchHandler.php
// If running as standalone scripts outside of the Symbiota file structure, you must include ImageLocalProcessor class (ImageLocalProcessor.php)
if(isset($SERVER_ROOT) && $SERVER_ROOT){
	include_once($SERVER_ROOT.'/classes/ImageLocalProcessor.php');
}
elseif(isset($serverRoot) && $serverRoot){
	if(file_exists($serverRoot.'/config/dbconnection.php')){ 
		include_once($serverRoot.'/config/dbconnection.php');
	}
	else{
		include_once('ImageBatchConnectionFactory.php');
	}
	if (file_exists($serverRoot.'/classes/ImageLocalProcessor.php')) { 
		@require_once($serverRoot.'/classes/ImageLocalProcessor.php');
	}
	// Check for the symbiota class files used herein for parsing 
	// batch files of xml formatted strucutured data.
	// Fail gracefully if they aren't available.
	// Note also that class_exists() is checked for before
	// invocation of these parsers in processFolder().
	if (file_exists($serverRoot.'/classes/SpecProcessorGPI.php')) { 
		@require_once($serverRoot.'/classes/SpecProcessorGPI.php');
	}
	if (file_exists($serverRoot.'/classes/SpecProcessorNEVP.php')) {  
		@require_once($serverRoot.'/classes/SpecProcessorNEVP.php');
	}
}
else{
	//Files reside in same folder and script is run from within the folder
	if (file_exists('/classes/ImageLocalProcessor.php')) { 
		@require_once('/classes/ImageLocalProcessor.php');
	}
	if(file_exists('SpecProcessorGPI.php')) { 
		@require_once('SpecProcessorGPI.php');
	}
	if (file_exists('SpecProcessorNEVP.php')) {  
		@require_once('SpecProcessorNEVP.php');
	}
}

class ImageBatchProcessor extends ImageLocalProcessor {

	function __construct(){
		parent::__construct();
		if($this->dbMetadata && !$this->conn){
			//Collection wasn't set within parent class, thus let's try setting via script connection class
			if(class_exists('ImageBatchConnectionFactory')){
				$this->conn = ImageBatchConnectionFactory::getCon('write');
			}
			if(!$this->conn){
				$this->logOrEcho("Image upload aborted: Unable to establish connection to ".$collName." database");
				exit("ABORT: Image upload aborted: Unable to establish connection to ".$collName." database");
			}
		}
	}

	function __destruct(){
		parent::__destruct();
	}
}
?>