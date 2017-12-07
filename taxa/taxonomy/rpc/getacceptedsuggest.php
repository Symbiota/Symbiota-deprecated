<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$con = MySQLiConnectionFactory::getCon("readonly");
$q = $con->real_escape_string($_REQUEST['term']);
$taxAuthId = array_key_exists('taid',$_REQUEST)?$con->real_escape_string($_REQUEST['taid']):'1'; 

$retArr = Array();
$sql = 'SELECT t.tid, t.sciname, t.author FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
	'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tid = ts.tidaccepted) AND (t.sciname LIKE "'.$q.'%") ORDER BY t.sciname LIMIT 10';
$result = $con->query($sql);
while($row = $result->fetch_object()){
	if($CHARSET == 'UTF-8') $retArr[] = $row->sciname.' '.$row->author;
	else $retArr[] = utf8_encode($row->sciname.' '.$row->author);
}
$result->free();
if(!($con === false)) $con->close();

//output the response
echo json_encode($retArr);
?>