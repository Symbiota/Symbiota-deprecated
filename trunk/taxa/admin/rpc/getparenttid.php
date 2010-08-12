<?php
include_once("../../../util/dbconnection.php");
include_once("../../../util/symbini.php");
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//get the q parameter from URL
$parentName = $_REQUEST["parent"]; 

$responseStr = "empty";
$con = MySQLiConnectionFactory::getCon("readonly");
$sql = "SELECT t.tid FROM taxa t ".
	"WHERE (t.sciname = '".$parentName."')";
$result = $con->query($sql);
if($row = $result->fetch_object()){
	$responseStr = $row->tid;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo $responseStr;
?>