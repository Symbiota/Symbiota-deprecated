<?php

class SpecProcessorOcrManager{

	protected $conn;
	
	function __construct($logPath) {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function parseRawTextSymbiota($prlid){
		$textBlock = '';
		if(is_numeric($prlid)){
			$conn = MySQLiConnectionFactory::getCon("readonly");
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			$rs = $conn->query($sql);
			if($r = $rs->fetch_object()){
				$textBlock = $r->rawstr;
			} 
			$rs->close();
			$conn->close();
		}
		return $this->parseTextBlockSymbiota($textBlock);
	}
	
	public function parseTextBlockSymbiota($textBlock){
		$dataMap = array();
		
		return $dataMap;
	}
	
	public function parseTextBlockSalix($textBlock){
		$dataMap = array();
		
		return $dataMap;
	}
	
}
?>
 