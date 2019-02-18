<?php
	include_once('../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	$taxonType = array_key_exists('t',$_REQUEST)?$con->real_escape_string($_REQUEST['t']):1;
	// Is the string length greater than 0?
	if($queryString) {
//		$sql = "SELECT DISTINCT o.sciname FROM omoccurrences o WHERE o.TidInterpreted IS NOT NULL AND o.sciname LIKE '".$queryString."%' ORDER BY o.sciname LIMIT 8";
		$sql = "";
		if($taxonType == 5){
			$sql = "SELECT DISTINCT v.vernacularname AS sciname ".
				"FROM taxavernaculars v ".
				"WHERE v.vernacularname LIKE '%".$queryString."%' ".
				"limit 50 ";
		}
		elseif($taxonType == 4){
			$sql = "SELECT sciname ".
				"FROM taxa ".
				"WHERE rankid > 20 AND rankid < 140 AND sciname LIKE '".$queryString."%' ".
				'LIMIT 20';
		}
		elseif($taxonType == 2){
			$sql = "SELECT DISTINCT family AS sciname ".
				"FROM taxstatus ".
				"WHERE family LIKE '".$queryString."%' ".
				"LIMIT 20";
		}
		else{
			$sql = "SELECT DISTINCT sciname ".
				"FROM taxa ".
				"WHERE sciname LIKE '".$queryString."%' ";
			if($taxonType == 3){
				$sql .= "AND rankid > 140 ";
			}
			else{
				$sql .= "AND rankid >= 140 ";
			}
			$sql .= 'LIMIT 20';
		}
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$returnArr[] = htmlentities($row->sciname);
         }
	}
	$con->close();
	echo json_encode($returnArr);
?>