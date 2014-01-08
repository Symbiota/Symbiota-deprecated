<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$retArr = Array();
$con = MySQLiConnectionFactory::getCon("readonly");
$queryString = $con->real_escape_string($_REQUEST['term']);
$sql = 'SELECT tid, sciname '. 
	'FROM taxa '.
	'WHERE rankid > 140 AND sciname LIKE "'.$queryString.'%" ';
//echo $sql;
$result = $con->query($sql);
while ($r = $result->fetch_object()) {
	$retArr[] = '"id": '.$r->tid.', "value":"'.str_replace('"',"''",$r->sciname).'"';
}
$result->free();
$con->close();

if($retArr) echo '[{'.implode('},{',$retArr).'}]';
?>