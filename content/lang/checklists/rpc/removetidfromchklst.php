<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$clid = $_REQUEST["clid"]; 
$tid = $_REQUEST["tid"]; 

if(is_numeric($clid) && is_numeric($tid)){
	if($isAdmin || (array_key_exists('ClAdmin',$userRights) && in_array($clid,$userRights['ClAdmin']))){
		$conn = MySQLiConnectionFactory::getCon("write");
		$tid = $conn->real_escape_string($tid);
		$clid = $conn->real_escape_string($clid);
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
}
$conn->close();
?>
