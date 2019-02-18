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
		'WHERE t.sciname LIKE "'.$queryString.'%" AND cl.clid = '.$clid;
	//echo $sql;
	$result = $conn->query($sql);
	while ($r = $result->fetch_object()) {
		$returnArr[] = $r->sciname;
	}
	$result->free();
	
	$sql = 'SELECT DISTINCT ts.family '. 
		'FROM fmchklsttaxalink cl INNER JOIN taxstatus ts ON cl.tid = ts.tid '.
		'WHERE ts.family LIKE "'.$queryString.'%" AND cl.clid = '.$clid.' AND ts.taxauthid = 1 ';
	//echo $sql;
	$result = $conn->query($sql);
	while ($r = $result->fetch_object()) {
		$returnArr[] = $r->family;
	}
	$result->free();
	
	$conn->close();
}
sort($returnArr);
echo json_encode($returnArr);
?>