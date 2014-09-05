<?php
include_once($serverRoot.'/classes/SalixUtilities.php');

/* This class extends the SpecProcNlp class and thus inherits all public and protected variables and functions 
 * The general idea is to that the SpecProcNlp class will cover all shared Symbiota functions (e.g. batch processor, resolve state, resolve collector, test output, etc)
 */ 


class SpecProcNlpSalix{

	private $conn;
	private $wordFreqArr = array();
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		set_time_limit(7200);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	//Parsing functions
	public function parse($rawOcr) {
		$dwcArr = array();
		//Add the SALIX parsing code here and/or in following functions of a private scope

		
		
		$dwcArr['country'] = 'call worked';
		return $dwcArr;
	}

	private function getRawOcr($prlid){
		$retStr = '';
		if(is_numeric($prlid)){
			//Get raw OCR string
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retStr = $r->rawstr;
			}
			$rs->free();
		}
		return $retStr;
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