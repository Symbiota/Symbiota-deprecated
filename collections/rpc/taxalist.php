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
      $limit = array_key_exists('l',$_REQUEST) ? $con->real_escape_string($_REQUEST['l']) : 50;
			$sql = "SELECT DISTINCT v.vernacularname AS sciname ".
				"FROM taxavernaculars v ".
				"WHERE v.vernacularname LIKE '%".$queryString."%' ".
				"limit $limit ";
		}
		elseif($taxonType == 4){
      $limit = array_key_exists('l',$_REQUEST) ? $con->real_escape_string($_REQUEST['l']) : 20;
			$sql = "SELECT sciname ".
				"FROM taxa ".
				"WHERE rankid > 20 AND rankid < 140 AND sciname LIKE '".$queryString."%' ".
				"LIMIT $limit";
		}
		elseif($taxonType == 2){
      $limit = array_key_exists('l',$_REQUEST) ? $con->real_escape_string($_REQUEST['l']) : 20;
			$sql = "SELECT DISTINCT family AS sciname ".
				"FROM taxstatus ".
				"WHERE family LIKE '".$queryString."%' ".
				"LIMIT $limit";
		}
		else{
      $limit = array_key_exists('l',$_REQUEST) ? $con->real_escape_string($_REQUEST['l']) : 20;
			$sql = "SELECT DISTINCT sciname ".
				"FROM taxa ".
				"WHERE sciname LIKE '".$queryString."%' ";
			if($taxonType == 3){
				$sql .= "AND rankid > 140 ";
			}
			else{
				$sql .= "AND rankid >= 140 ";
			}
			$sql .= "LIMIT $limit";
		}
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$returnArr[] = htmlentities($row->sciname);
         }
	}
	$con->close();
	echo json_encode($returnArr);
?>
