<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$countryStr = $_REQUEST['country'];
$collId = $_REQUEST['collid'];

if($countryStr && $collId  
	&& ($isAdmin 
	|| ((array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) 
	|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))))){
	
	$conn = MySQLiConnectionFactory::getCon("write");
	$countryStr = $conn->real_escape_string($_REQUEST['country']);
	$sql = 'INSERT INTO lkupcountry(countryname) VALUES("'.$countryStr.'")';
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
