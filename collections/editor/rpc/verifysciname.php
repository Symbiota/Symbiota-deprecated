<?php
include_once('../../../config/dbconnection.php');

$con = MySQLiConnectionFactory::getCon("readonly");
$retStr = "";
$sciName = $con->real_escape_string($_REQUEST['sciname']);
// Is the string length greater than 0?
if($sciName){
	$sql = "SELECT DISTINCT t.sciname, t.author, ts.family ".
		"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
		"WHERE t.sciname = \"".$sciName."\" AND ts.taxauthid = 1 ";
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retStr = "{'sciname':'".$row->sciname."',";
		$retStr .= "'author':'".$row->author."',";
		$retStr .= "'family':'".$row->family."'}";
	}
}
$con->close();
echo $retStr;
?>