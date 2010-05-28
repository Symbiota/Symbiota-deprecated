<?php
/* E.E. Gilbert 16 Oct 2008 */
header("Content-Type: text/html; charset=ISO-8859-1");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

include_once("../../util/symbini.php");
include_once("../../util/dbconnection.php");

$userid = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:""; 
$login = array_key_exists("login",$_REQUEST)?$_REQUEST["login"]:""; 
 
if($userid && $login && ($isAdmin || $userid == $uid )){
	$conn = MySQLiConnectionFactory::getCon("write");

	$delStatus = "false";
	$sql = "DELETE FROM userlogin WHERE uid = $userid AND username = '$login'";
	//echo $sql;
	if($conn->query($sql)){
		echo 1;
	}
	else{
		echo 0;
	}
	
 	$conn->close();
}

?>
