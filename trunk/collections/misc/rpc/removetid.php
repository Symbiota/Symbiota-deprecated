<?php
/*
 * Created on 3 June 2010
 * By E.E. Gilbert
 */
 include_once('../../../config/symbini.php');
 include_once($serverRoot.'/config/dbconnection.php');
 header("Content-Type: text/html; charset=".$charset);
 
$conn = MySQLiConnectionFactory::getCon("write");
$tid = array_key_exists("tid",$_REQUEST)?$conn->real_escape_string($_REQUEST["tid"]):""; 
 
if($tid && ($isAdmin)){

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
}
$conn->close();
?>
