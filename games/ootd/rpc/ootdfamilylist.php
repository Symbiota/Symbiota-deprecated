<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$con = MySQLiConnectionFactory::getCon("readonly");

$retArrRow = Array();
$retArr = Array();
$queryString = $con->real_escape_string($_REQUEST['term']);
if($queryString) {
	$sql = "";
	$sql = "SELECT DISTINCT SciName ".
		"FROM taxa ".
		"WHERE RankId = 140 AND SciName LIKE '".$queryString."%'";
	$sql .= 'LIMIT 10';
	//echo $sql;
	if($result = $con->query($sql)){
		while ($row = $result->fetch_object()) {
			$retArrRow['id'] = $row->SciName;
			$retArrRow['label'] = htmlentities($row->SciName);
			$retArrRow['value'] = $row->SciName;
			array_push($retArr, $retArrRow);
		}
	}
}

$con->close();
echo json_encode($retArr);
?>