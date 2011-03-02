<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$q = $_REQUEST['q'];

$upperTax = Array();
$con = MySQLiConnectionFactory::getCon("readonly");
$sql = 'SELECT DISTINCT uppertaxonomy FROM taxstatus '.
	'WHERE uppertaxonomy LIKE "'.$q.'%" ORDER BY uppertaxonomy LIMIT 10';
$result = $con->query($sql);
if($row = $result->fetch_object()){
	$upperTax[] = $row->uppertaxonomy;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo "['".implode("','",$upperTax)."']";
?>