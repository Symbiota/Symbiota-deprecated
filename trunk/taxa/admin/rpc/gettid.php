<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//get the q parameter from URL
$sciName = $_REQUEST["sciname"]; 

$responseStr = "";
$con = MySQLiConnectionFactory::getCon("readonly");
$sql = "SELECT t.tid FROM taxa t ".
	"WHERE (t.sciname = '".$sciName."')";
$result = $con->query($sql);
if($row = $result->fetch_object()){
	$responseStr = $row->tid;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo $responseStr;
?>