<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//get the q parameter from URL
$sciname = $_REQUEST["sciname"]; 

$responseStr = "";
$con = MySQLiConnectionFactory::getCon("readonly");
$sql = "SELECT ts.uppertaxonomy, ts.family FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
	"WHERE (ts.taxauthid = 1) AND (t.sciname = '".$sciname."')";
$result = $con->query($sql);
if($row = $result->fetch_object()){
	$upperTax = $row->uppertaxonomy;
	$family = $row->family;
	$responseStr = $upperTax."|".$family;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo $responseStr;
?>