<?php 
include_once($serverRoot.'/classes/Manager.php');

/**
 * Controler class for /misc/generaltemplate.php
 * 
 */

class GeneralClassTemplate extends Manager { 

	private $variable1;
	private $variable2;
	
	public function __construct(){
		parent::__construct(null,'readonly');
		//parent::__construct(null,'write');
		/*
		 * Only use right if primary function is to manage data within the database.
		 * If most functions are read only, you can still establish a write conneciton that is used just within a function  
		 * e.g. $con = MySQLiConnectionFactory::getCon('write');
		 */
	}

	public function __destruct(){
 		parent::__destruct();
	}
	
	//Main fucntions added here
	
	
	
	
	//Setters and getters
	public function setGeneralVariable($var){
		$this->variable1 = $this->cleanInStr($var);
	}
	
	public function setNumericVariable($var){
		if(is_numeric($var)){
			$this->variable2 = $var;
		}
	}
} 
?>