<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class SpecProcNlpDupes {

	private $conn;
	private $verbose = 1;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function buildFragments(){
		$sql = 'SELECT r.prlid, r.rawstr '.
			'FROM specprocessorrawlabels r LEFT JOIN specprococrfrag f ON r.prlid = f.prlid '.
			'WHERE f.prlid IS NULL';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$prlid = $r->prlid;
			$rawOcr = $r->rawstr;
			
		}
		$rs->free();
	}
	
	private function cleanOcrStr(&$ocrStr){
		$ocrStr = str_replace('.', ' ',$ocrStr);
		$ocrStr = preg_replace('/\s\s+/',' ',$ocrStr);
		$ocrStr = preg_replace('/[^a-z,0-9]/','',$ocrStr);
		if(){
			
		}
	}
	
	private function getKeyTerm($w){
		if(preg_match('/\d+/',$w)) return '';
		$w = trim($w,' ,;().');
		if(strlen($w) < 2) return '';
		return $w;
	}
	
	//Setters and getters
	public function setVerbose($v){
		$this->verbose = $v;
	}
	
	//misc fucntions 
	private function echoStr($str, $indent = 0){
		if($this->verbose){
			echo '<li'.($indent?' style="margin-left:"'.$indent.'px':'').'>'.$str."</li>\n";
			ob_flush();
			flush();
		}
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>