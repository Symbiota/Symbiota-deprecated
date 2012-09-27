<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$stateStr = $_REQUEST['state'];
$countryStr = $_REQUEST['country'];
$collId = $_REQUEST['collid'];

if($countryStr && $collId 
	&& ($isAdmin 
	|| ((array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) 
	|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))))){

	$conn = MySQLiConnectionFactory::getCon("write");
	$stateStr = $conn->real_escape_string(htmlspecialchars($stateStr));
	$countryStr = $conn->real_escape_string(htmlspecialchars($countryStr));
	$sql = 'INSERT INTO lkupstateprovince(statename,countryid) SELECT "'.$stateStr.'", countryid '.
		'FROM lkupcountry WHERE countryname = "'.$countryStr.'"';
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
