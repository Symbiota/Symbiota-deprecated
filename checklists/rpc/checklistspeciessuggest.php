<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$conn = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();
$queryString = $conn->real_escape_string($_REQUEST['term']);
$clid = $conn->real_escape_string($_REQUEST['cl']);
	
$sql = 'SELECT t.sciname '. 
	'FROM taxa t INNER JOIN fmchklsttaxalink cl ON t.tid = cl.tid '.
	'WHERE sciname LIKE "'.$queryString.'%" AND cl.clid = '.$clid.' ORDER BY sciname';
//echo $sql;
$result = $conn->query($sql);
while ($r = $result->fetch_object()) {
	$returnArr[] = $r->sciname;
}
$conn->close();
echo json_encode($returnArr);
?>