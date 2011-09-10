<?php
/*
 * E.E. Gilbert 
 * Oct. 16, 2008
 */
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$countyStr = $_REQUEST['county'];
$stateStr = $_REQUEST['state'];
$collId = $_REQUEST['collid'];

if($countyStr && $stateStr && $collId 
	&& ($isAdmin 
	|| ((array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) 
	|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))))){

	$conn = MySQLiConnectionFactory::getCon("write");
	$countyStr = $conn->real_escape_string($countyStr);
	$stateStr = $conn->real_escape_string($stateStr);
	$sql = 'INSERT INTO lkupcounty(countyname,stateid) SELECT "'.$countyStr.'", stateid '.
		'FROM lkupstateprovince WHERE statename = "'.$stateStr.'"';
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
