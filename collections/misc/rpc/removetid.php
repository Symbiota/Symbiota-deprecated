<?php
/*
 * Created on 3 June 2010
 * By E.E. Gilbert
 */
 include_once("../../../util/dbconnection.php");
 include_once("../../../util/symbini.php");
 header("Content-Type: text/html; charset=".$charset);
 
 $tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 
 
if($tid && ($isAdmin)){
	$conn = MySQLiConnectionFactory::getCon("write");

	$delStatus = "false";
	$sql = "UPDATE taxa t SET t.SecurityStatus = 1 WHERE t.tid = ".$tid;
	//echo $sql;
	if($conn->query($sql)){
		$sql2 = "UPDATE omoccurrences o ".
			"SET o.LocalitySecurity = 1 ".
			"WHERE o.tidinterpreted = ".$tid;
		$conn->query($sql2);
		echo $tid;
	}
	else{
		echo "0";
	}
	
 	$conn->close();
 }

?>
