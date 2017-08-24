<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$clid = $_REQUEST['cl'];

$returnArr = Array();
if(is_numeric($clid)){
	$conn = MySQLiConnectionFactory::getCon("readonly");
	$clid = $conn->real_escape_string($clid);
	$queryString = $conn->real_escape_string($_REQUEST['term']);
		
	$sql = 'SELECT t.sciname '. 
		'FROM taxa t INNER JOIN fmchklsttaxalink cl ON t.tid = cl.tid '.
		'WHERE sciname LIKE "'.$queryString.'%" AND cl.clid = '.$clid.' ORDER BY sciname';
	//echo $sql;
	$result = $conn->query($sql);
	while ($r = $result->fetch_object()) {
		$returnArr[] = $r->sciname;
	}
	$conn->close();
}
echo json_encode($returnArr);
?>