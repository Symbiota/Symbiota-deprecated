<?php
class SpecProcNlpParserLBCCCommon{

	protected $conn;
	protected $anotherVariableUsedByBothLichenAndBryophyte;
	private $usedOnlyWithinThisClass;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}
	
	protected function functionsSharedByBothLichenAndBryophyteClasses() {


	}
}

?>