<?php
header("Content-Type: text/html; charset=ISO-8859-1");
/*
 * Created on 3 June 2010
 * By E.E. Gilbert
 */
 include_once("../../../util/dbconnection.php");
 include_once("../../../util/symbini.php");
 
 $tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 
 
if($tid && ($isAdmin)){
	$conn = MySQLiConnectionFactory::getCon("write");

	$delStatus = "false";
	$sql = "UPDATE taxa t SET t.SecurityStatus = 1 WHERE t.tid = ".$tid;
	//echo $sql;
	if($conn->query($sql)){
		echo $tid;
	}
	else{
		echo "0";
	}
	
 	$conn->close();
 }

?>
