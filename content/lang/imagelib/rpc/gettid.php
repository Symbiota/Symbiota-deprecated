<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$con = MySQLiConnectionFactory::getCon("readonly");

$sciName = $con->real_escape_string($_REQUEST['term']); 
$taxAuthId = array_key_exists('taxauthid',$_REQUEST)?$con->real_escape_string($_REQUEST['taxauthid']):0;

$responseStr = "";
$sql = 'SELECT t.tid FROM taxa t WHERE (t.sciname = "'.$sciName.'") ';

$result = $con->query($sql);
if($row = $result->fetch_object()){
	$responseStr = $row->tid;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo $responseStr;
?>