<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: application/json; charset=".$charset);

$con = MySQLiConnectionFactory::getCon("readonly");

$retStr = 0;
$tid = trim($con->real_escape_string($_REQUEST['tid']));
$state = trim($con->real_escape_string($_REQUEST['state']));

if(is_numeric($tid) && $state){
	$sql = 'SELECT c.clid '.
		'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
		'INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
		'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
		'WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
		'AND (ts2.tid = '.$tid.') AND (c.locality = "'.$state.'")';
	//echo $sql;
	$rs = $con->query($sql);
	if($rs->num_rows){
		$retStr = 1;
	}
	$rs->free();
}
$con->close();

if($retStr){
	echo json_encode($retStr);
}
else{
	echo 0;
}
?>