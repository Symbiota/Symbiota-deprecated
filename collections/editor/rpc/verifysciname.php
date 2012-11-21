<?php
include_once('../../../config/dbconnection.php');

$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$sciName = $con->real_escape_string($_REQUEST['sciname']);
// Is the string length greater than 0?
if($sciName){
	$sql = 'SELECT DISTINCT t.tid, t.sciname, t.author, ts.family '.
		'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'WHERE t.sciname = "'.$sciName.'" AND ts.taxauthid = 1 ';
	echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArr['tid'] = $row->tid;
		$retArr['author'] = htmlentities($row->author);
		$retArr['family'] = $row->family;
	}
}
$con->close();
if($retArr){
	echo json_encode($retArr);
}
else{
	echo '';
}
?>