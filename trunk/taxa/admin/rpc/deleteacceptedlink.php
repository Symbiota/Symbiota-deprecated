<?php
header("Content-Type: text/html; charset=ISO-8859-1");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
include_once("../../../util/dbconnection.php");
 include_once("../../../util/symbini.php");

$tid = $_REQUEST["tid"];
$tidAccepted = $_REQUEST["tidaccepted"]; 

$responseStr = "";
if($tid && $tidAccepted && ($isAdmin || in_array("Taxonomy",$userRights))){
	$con = MySQLiConnectionFactory::getCon("write");
	$sql = "DELETE FROM taxstatus WHERE (tid = $tid. AND tidaccepted = $tidAccepted)";
	$status = $con->query($sql);
	if(!($con === false)) $con->close();
}

//output the response
echo $status;

?>