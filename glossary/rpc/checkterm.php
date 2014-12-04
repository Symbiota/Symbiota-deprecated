<?php
include_once('../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();
$queryString = $con->real_escape_string($_REQUEST['term']);
// Is the string length greater than 0?
if($queryString) {
	$sql = "";
	$sql = "SELECT glossid ".
		"FROM glossary ".
		"WHERE term = '".$queryString."' ";
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$returnArr[] = $row->glossid;
	}
}
$con->close();
if(!$returnArr){
	$returnArr = 'null';
}
echo json_encode($returnArr);
?>