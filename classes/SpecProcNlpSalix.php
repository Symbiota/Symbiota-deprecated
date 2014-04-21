<?php
include_once($serverRoot.'/classes/SpecProcNlp.php');
include_once($serverRoot.'/classes/SalixUtilities.php');

/* This class extends the SpecProcNlp class and thus inherits all public and protected variables and functions 
 * The general idea is to that the SpecProcNlp class will cover all shared Symbiota functions (e.g. batch processor, resolve state, resolve collector, test output, etc)
 */ 


class SpecProcNlpSalix extends SpecProcNlp{

	private $wordFreqArr = array();
	
	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	//Parsing functions
	public function parse($rawStr) {
		$dwcArr = array();
		//Add the SALIX parsing code here and/or in following functions of a private scope
		
		
		return $dwcArr;
	}

	private function getWordFreq(){
		$sql = 'SELECT firstword, secondword, locality, localityfreq, habitat, habitatFreq, substrate, substrateFreq, '.
			'verbatimAttributes, verbatimAttributesFreq, occurrenceRemarks, occurrenceRemarksFreq, totalcount, datelastmodified '.
			'FROM salixwordstats '.
			'WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			//Not sure if this is the best way organize the word stats, but it's an idea
			$this->wordFreqArr[$r->firstword][$r->secondword]['loc'] = $r->localityfreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['hab'] = $r->habitatFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['sub'] = $r->substrateFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['att'] = $r->verbatimAttributesFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['rem'] = $r->occurrenceRemarksFreq;
		}
		$rs->free();
	}
	
	private function rebuildWordStats(){
		$salixHandler = new SalixUtilities();
		$salixHandler->setVerbose(0);
		$salixHandler->buildWordStats($this->collId, 1);
	}

}
?>
