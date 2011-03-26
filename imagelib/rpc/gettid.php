<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//get the q parameter from URL
$sciName = $_REQUEST['sciname']; 
$taxAuthId = array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:0;

$responseStr = "";
$con = MySQLiConnectionFactory::getCon("readonly");
$sql = 'SELECT t.tid FROM taxa t ';
if($taxAuthId){
	$sql .= 'INNER JOIN taxstatus ts ON t.tid = ts.tid ';
}
$sql .= 'WHERE (t.sciname = "'.$sciName.'") ';
if($taxAuthId){
	$sql .= 'AND ts.taxauthid = '.$taxAuthId;
}
$result = $con->query($sql);
if($row = $result->fetch_object()){
	$responseStr = $row->tid;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo $responseStr;
?>