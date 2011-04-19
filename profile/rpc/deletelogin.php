<?php
/* E.E. Gilbert 16 Oct 2008 */
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$conn = MySQLiConnectionFactory::getCon("write");
$userid = array_key_exists("userid",$_REQUEST)?$conn->real_escape_string($_REQUEST["userid"]):""; 
$login = array_key_exists("login",$_REQUEST)?$conn->real_escape_string($_REQUEST["login"]):""; 

if($userid && $login && ($isAdmin || $userid == $uid )){
	$delStatus = "false";
	$sql = "DELETE FROM userlogin WHERE uid = $userid AND username = '$login'";
	//echo $sql;
	if($conn->query($sql)){
		echo 1;
	}
	else{
		echo 0;
	}
}
$conn->close();
?>
