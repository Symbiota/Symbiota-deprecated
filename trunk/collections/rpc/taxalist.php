<?php
	include_once('../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $_REQUEST['term'];
	$taxonType = array_key_exists('t',$_REQUEST)?$_REQUEST['t']:1;
	// Is the string length greater than 0?
	if($queryString) {
//		$sql = "SELECT DISTINCT o.sciname FROM omoccurrences o WHERE o.TidInterpreted IS NOT NULL AND o.sciname LIKE '".$queryString."%' ORDER BY o.sciname LIMIT 8";
		$sql = "";
		if($taxonType == 5){
			$sql = "SELECT DISTINCT v.vernacularname AS sciname ".
				"FROM taxavernaculars v INNER JOIN omoccurrences o ON v.tid = o.tidinterpreted ".
				"WHERE v.vernacularname LIKE '".$queryString."%' ".
				"ORDER BY v.vernacularname";
		}
		elseif($taxonType == 4){
			$sql = "SELECT DISTINCT t.sciname ".
				"FROM taxa t  ".
				"WHERE t.rankid > 20 AND t.rankid < 140 AND t.sciname LIKE '".$queryString."%' ";
		}
		elseif($taxonType == 2){
			$sql = "SELECT DISTINCT family AS sciname ".
				"FROM taxstatus ".
				"WHERE family LIKE '".$queryString."%' ";
		}
		else{
			$sql = "SELECT DISTINCT t.sciname ".
				"FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted ".
				"WHERE o.sciname LIKE '".$queryString."%' ";
			if($taxonType == 3){
				$sql .= "AND t.rankid > 140 ";
			}
			else{
				$sql .= "AND t.rankid >= 140 ";
			}
			//$sql .= "ORDER BY o.sciname";
		}
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$returnArr[] = $row->sciname;
         }
	}
	$con->close();
	echo json_encode($returnArr);
?>