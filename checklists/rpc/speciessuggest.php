<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$CHARSET);

$retArr = Array();
$con = MySQLiConnectionFactory::getCon("readonly");

$queryString = $con->real_escape_string($_REQUEST['term']);
$taxLevel = (isset($_REQUEST['level'])?$con->real_escape_string($_REQUEST['level']):'low');

$sql = 'SELECT tid, sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" ';
if($taxLevel == 'low'){
	$sql .= 'AND rankid > 179';
}
else{
	$sql .= 'AND rankid < 180';
}
//echo $sql;
$result = $con->query($sql);
while ($r = $result->fetch_object()) {
	$retArr[$r->tid]['id'] = $r->tid;
	$retArr[$r->tid]['value'] = $r->sciname;
}
$result->free();
$con->close();

echo json_encode($retArr);
?>