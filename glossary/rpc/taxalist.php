<?php
include_once('../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();
$queryString = array_key_exists("term",$_REQUEST)?$con->real_escape_string($_REQUEST['term']):$con->real_escape_string($_REQUEST['q']);
$type = $con->real_escape_string($_REQUEST['t']);
// Is the string length greater than 0?
if($queryString) {
	$sql = "";
	$sql = "SELECT DISTINCT ts.tidaccepted, t.SciName ".
		"FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid ".
		"WHERE t.SciName LIKE '".$queryString."%' AND t.RankId < 115 AND ts.taxauthid = 1 ";
	$sql .= 'LIMIT 10';
	$result = $con->query($sql);
	if($type == 'single'){
		while ($row = $result->fetch_object()) {
			$retArrRow['id'] = $row->tidaccepted;
			$retArrRow['label'] = htmlentities($row->SciName);
			$retArrRow['value'] = $row->tidaccepted;
			array_push($returnArr, $retArrRow);
		}
	}
	if($type == 'batch'){
		$i = 0;
		while ($row = $result->fetch_object()) {
			$returnArr[$i]['name'] = htmlentities($row->SciName);
			$returnArr[$i]['id'] = htmlentities($row->tidaccepted);
			$i++;
		}
	}
}
$con->close();
echo json_encode($returnArr);
?>