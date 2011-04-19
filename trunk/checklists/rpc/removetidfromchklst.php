<?php
/*
 * E.E. Gilbert 
 * Oct. 16, 2008
 */
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 
$conn = MySQLiConnectionFactory::getCon("write");
$clid = $conn->real_escape_string($_REQUEST["clid"]); 
$tid = $conn->real_escape_string($_REQUEST["tid"]); 

if($clid && $tid && ($isAdmin || (array_key_exists('ClAdmin',$userRights) && in_array($clid,$userRights['ClAdmin'])))){
	$delStatus = 'false';
	$sql = 'DELETE FROM fmchklsttaxalink WHERE chklsttaxalink.CLID = '.$clid.' AND chklsttaxalink.TID = '.$tid;
	//echo $sql;
	if($conn->query($sql)){
		echo $tid;
	}
	else{
		echo '0';
	}
}
$conn->close();
?>
