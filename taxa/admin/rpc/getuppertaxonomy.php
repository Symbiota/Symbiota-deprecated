<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$con = MySQLiConnectionFactory::getCon("readonly");
$sciname = $con->real_escape_string(htmlspecialchars($_REQUEST["sciname"]));

$responseStr = "";
$sql = "SELECT ts.uppertaxonomy FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
	"WHERE (ts.taxauthid = 1) AND (t.sciname = '".$sciname."')";
$result = $con->query($sql);
if($row = $result->fetch_object()){
	$responseStr = $row->uppertaxonomy;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo $responseStr;
?>