<?php
// Used by /trunk/collections/specprocessor/standalone_scripts/ImageBatchHandler.php

if(isset($SERVER_ROOT)){
	//Use Symbiota connection factory
	if(file_exists($SERVER_ROOT.'/config/dbconnection.php')){ 
		include_once($SERVER_ROOT.'/config/dbconnection.php');
	}
	else{
		include_once('ImageBatchConnectionFactory.php');
	}
	if(file_exists($SERVER_ROOT.'/classes/OccurrenceMaintenance.php')){ 
		include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
	}
	if(file_exists($SERVER_ROOT.'/classes/UuidFactory.php')){ 
		include_once($SERVER_ROOT.'/classes/UuidFactory.php');
	}
}
// Check for the symbiota class files used herein for parsing 
// batch files of xml formatted strucutured data.
// Fail gracefully if they aren't available.
// Note also that class_exists() is checked for before
// invocation of these parsers in processFolder().
if(isset($SERVER_ROOT)){
	//Files reside within Symbiota file structure
	if (file_exists($SERVER_ROOT.'/classes/SpecProcessorGPI.php')) { 
		@require_once($SERVER_ROOT.'/classes/SpecProcessorGPI.php');
	}
	if (file_exists($SERVER_ROOT.'/classes/SpecProcessorNEVP.php')) {  
		@require_once($SERVER_ROOT.'/classes/SpecProcessorNEVP.php');
	}
}
else{
	//Files reside in same folder and script is run from within the folder
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
			//Set collection
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