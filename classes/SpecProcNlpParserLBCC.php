<?php
class SpecProcNlpParserLBCC extends SpecProcNlp{

	private $dcArr = array();

	function __construct() {
 		parent::__construct();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	//Parsing functions
	private function parse($textBlock = ''){
		if(!$textBlock && $this->rawStr) $textBlock = $this->rawStr;
		if(!$textBlock){
			$errArr[] = 'ERROR: nothing to parse';
			return false;
		}
		//Parsing code goes here
		
		
		
	}


}
?>
 