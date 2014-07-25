<?php 
include_once($serverRoot.'/config/dbconnection.php');

class ImageLibraryManager{

	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

 	public function getFamilyList(){
 		$returnArray = Array();
		$sql = "SELECT DISTINCT ts.Family 
			FROM (images ti INNER JOIN taxstatus ts ON ti.TID = ts.TID) 
			INNER JOIN taxa t ON ts.tidaccepted = t.tid 
			WHERE (ti.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) AND (ts.Family Is Not Null) 
			ORDER BY ts.Family";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->Family;
    	}
    	$result->free();
		return $returnArray;
	}
	
	public function getGenusList(){
 		$returnArray = Array();
		$sql = "SELECT DISTINCT t.UnitName1 
			FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID 
			WHERE (ti.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) ORDER BY t.UnitName1";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->UnitName1;
    	}
    	$result->free();
		return $returnArray;
	}
	
	public function getSpeciesList($taxon){
		$returnArray = Array();
		$sql = "SELECT DISTINCT t.tid, t.SciName 
			FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID 
			WHERE (ti.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) ";
		if($taxon){
			$taxon = $this->cleanInStr($taxon);
			$sql .= "AND ((t.SciName LIKE '".$taxon."%') OR (ts.Family = '".$taxon."')) ";
		}
		$sql .= "ORDER BY t.SciName ";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[$row->tid] = $row->SciName;
	    }
	    $result->free();
	    return $returnArray;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>