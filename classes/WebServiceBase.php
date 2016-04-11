<?php
require_once($SERVER_ROOT.'/classes/Manager.php');

class WebServiceBase extends Manager{

	function __construct($id,$conType) {
		parent::__construct($id,$conType);
		$this->setLogFH('../content/logs/occurrenceWriter_'.date('Ymd').'.log');
	}

	function __destruct(){
		parent::__destruct();
	}

	protected function validateSecurityKey($k){
		return true;
		return false;
	}
}
?>