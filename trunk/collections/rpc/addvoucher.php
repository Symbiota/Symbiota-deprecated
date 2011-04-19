<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 
$con = MySQLiConnectionFactory::getCon("write");
$clid = array_key_exists("clid",$_REQUEST)?$con->real_escape_string($_REQUEST["clid"]):0; 
$occid = array_key_exists("occid",$_REQUEST)?$con->real_escape_string($_REQUEST["occid"]):0; 
$tid = array_key_exists("tid",$_REQUEST)?$con->real_escape_string($_REQUEST["tid"]):0; 

if(!$clid){
	echo "ERROR: Checklist ID is null";
}
elseif(!$occid){
	echo "ERROR: Occurrence ID is null";
}
elseif(!$tid){
	echo "ERROR: Problem with taxon name (null tid), contact administrator"; 
}
elseif(!($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"])))){
	echo "ERROR: Permissions Error";
}
else{
	$collStr = "";
	$sql = "SELECT IFNULL(CONCAT(recordedby,' (',IFNULL(recordnumber,'s.n.'),')'),occurrenceid) AS collstr ".
		"FROM omoccurrences ".
		"WHERE TidInterpreted IS NOT NULL AND occid = ".$occid;
	$rs = $con->query($sql);
	if($row = $rs->fetch_object()){
		$collStr = $row->collstr;
	}
	if(!$collStr){
		echo "ERROR: Collector must not be NULL for occurrence record";
	}
	elseif($con->query("INSERT INTO fmvouchers(tid,clid,occid,collector) VALUES($tid,$clid,$occid,\"".$collStr."\")")){
		echo "1";
	}
	else{
		echo "Unknown Error";
	}
}
$con->close();
?>