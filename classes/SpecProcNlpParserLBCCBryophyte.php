<?php
class SpecProcNlpParserLBCCBryophyte extends SpecProcNlpParserLBCCCommon{

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	protected function getLabelInfo($str) {
		if($str) {
			return $this->doGenericLabel($str);
		}
		return array();
	}
}
?>