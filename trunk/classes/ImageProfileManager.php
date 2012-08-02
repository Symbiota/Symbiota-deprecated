<?php 
include_once($serverRoot.'/config/dbconnection.php');

class ImageLibraryManager{

	function getConnection() {
 		return MySQLiConnectionFactory::getCon("readonly");
	}

 	public function getFamilyList(){
		$con = $this->getConnection();
 		$returnArray = Array();
		$sql = "SELECT DISTINCT ts.Family 
			FROM (images ti INNER JOIN taxstatus ts ON ti.TID = ts.TID) 
			INNER JOIN taxa t ON ts.tidaccepted = t.tid 
			WHERE (ti.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) AND (ts.Family Is Not Null) 
			ORDER BY ts.Family";
		$result = $con->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->Family;
    	}
    	$result->free();
    	$con->close();
		return $returnArray;
	}
	
	public function getGenusList(){
		$con = $this->getConnection();
 		$returnArray = Array();
		$sql = "SELECT DISTINCT t.UnitName1 
			FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID 
			WHERE (ti.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) ORDER BY t.UnitName1";
		$result = $con->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->UnitName1;
    	}
    	$result->free();
    	$con->close();
		return $returnArray;
	}
	
	public function getSpeciesList($taxon){
		$con = $this->getConnection();
		$returnArray = Array();
		$sql = "SELECT DISTINCT t.tid, t.SciName 
			FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID 
			WHERE (ti.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) ";
		if($taxon) $sql .= "AND ((t.SciName LIKE '".$taxon."%') OR (ts.Family = '".$taxon."')) ";
		$sql .= "ORDER BY t.SciName ";
		//echo $sql;
		$result = $con->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[$row->tid] = $row->SciName;
	    }
	    $result->free();
    	$con->close();
	    return $returnArray;
	}
}
?>