<?php
/*
 * E.E. Gilbert 
 * Oct. 16, 2008
 */
include_once("../../util/dbconnection.php");
include_once("../../util/symbini.php");
header("Content-Type: text/html; charset=".$charSet);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 
 $clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:""; 
 $tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 
 
if($clid && $tid && ($isAdmin || in_array("cl-".$clManager->getClid(),$userRights) )){
	$conn = MySQLiConnectionFactory::getCon("write");

	$delStatus = "false";
	$sql = "DELETE FROM fmchklsttaxalink WHERE chklsttaxalink.CLID = $clid AND chklsttaxalink.TID = $tid";
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
