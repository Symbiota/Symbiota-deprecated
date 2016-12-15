<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	
	$sql = "SELECT t.tid, t.sciname ". 
		"FROM taxa t ".
		"WHERE t.SecurityStatus <> 2 AND t.sciname LIKE '".$queryString."%' ".
		"ORDER BY t.sciname LIMIT 10";
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
       	$returnArr[] = $row->sciname;
	}
	$con->close();
	echo json_encode($returnArr);
?>