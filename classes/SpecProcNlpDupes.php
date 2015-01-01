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

	public function batchBuildFragments(){
		$this->echoStr('Starting batch process');
		$sql = 'SELECT r.prlid, r.rawstr '.
			'FROM specprocessorrawlabels r LEFT JOIN specprococrfrag f ON r.prlid = f.prlid '.
			'WHERE f.prlid IS NULL';
		$rs = $this->conn->query($sql);
		$cnt = 1;
		if($r = $rs->fetch_object()){
			if($this->processFragment($r->rawstr,$r->prlid)){
				if($cnt%1000 == 0) $this->echoStr($cnt.' OCR records',1);
			}
			$cnt++;
		}
		$rs->free();
		$this->echoStr('Batch process finished');
	}
	
	private function processFragment($rawOcr,$prlid){
		$status = false;
		//Clean string
		$rawOcr = str_replace('.', ' ',$rawOcr);
		$rawOcr = preg_replace('/\s\s+/',' ',$rawOcr);
		$rawOcr = trim(preg_replace('/[^a-z,0-9]/','',$rawOcr));
		if(strlen($ocrStr) > 10){
			//Load into database
			$framArr = preg_split("/\s/", $rawOcr);
			$previousWord = '';
			$cnt = 0;
			foreach($framArr as $w){
				if($previousWord){
					$keyTerm = $previousWord.$w;
					$sql = 'INSERT INTO(prlid,firstword,secondword,keyterm,wordorder) '.
						'VALUES('.$prlid.',"'.$previousWord.'","'.$w.'","'.$keyTerm.'",'.$cnt.')';
					if($this->conn->query($sql)){
						$status = true;
						$cnt++;
					}
					else{
						$this->echoStr('ERROR loading terms: '.$this->conn->error, $indent = 1);
					}
				}
				$previousWord = $w;
			}
		}
		return $status;
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