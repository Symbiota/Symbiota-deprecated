<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceSearchSupport{

	protected $conn;

 	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTaxaSuggest($queryString, $taxonType){
		$retArr = Array();
		$queryString = $this->cleanInStr($queryString);
		if(!is_numeric($taxonType)) $taxonType = 0;
		if($queryString) {
			$sql = "";
			if($taxonType == 1){
				// Family or species name
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" AND rankid > 139 LIMIT 30';
			}
			elseif($taxonType == 2){
				// Family only
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == 3){
				// Species name only
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" AND rankid > 179 LIMIT 30';
			}
			elseif($taxonType == 4){
				// Higher taxon
				$sql = 'SELECT sciname FROM taxa WHERE rankid > 20 AND rankid < 140 AND sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == 5){
				// Common name
				$sql = 'SELECT DISTINCT v.vernacularname AS sciname FROM taxavernaculars v WHERE v.vernacularname LIKE "%'.$queryString.'%" limit 50 ';
			}
			else{
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 20';
			}
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = htmlentities($r->sciname);
			}
			$rs->free();
		}
		return $retArr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>