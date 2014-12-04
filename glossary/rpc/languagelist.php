<?php
include_once('../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();
$queryString = $con->real_escape_string($_REQUEST['term']);
// Is the string length greater than 0?
if($queryString) {
	$sql = "";
	$sql = "SELECT DISTINCT g.`language` ".
		"FROM glossary AS g ".
		"WHERE g.`language` LIKE '".$queryString."%' ";
	$sql .= 'LIMIT 10';
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArrRow['label'] = htmlentities($row->language);
		array_push($returnArr, $retArrRow);
	}
}
$con->close();
echo json_encode($returnArr);
?>