<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$tid = $_REQUEST["tid"];
$tidAccepted = $_REQUEST["tidaccepted"]; 

$responseStr = "";
if($tid && $tidAccepted && ($isAdmin || array_key_exists("Taxonomy",$userRights))){
	$con = MySQLiConnectionFactory::getCon("write");
	$sql = "DELETE FROM taxstatus WHERE (tid = $tid. AND tidaccepted = $tidAccepted)";
	$status = $con->query($sql);
	if(!($con === false)) $con->close();
}

//output the response
echo $status;

?>