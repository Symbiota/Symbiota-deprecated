<?php
	include_once("../../../util/dbconnection.php");
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['q']);
	
	$sql = "SELECT t.tid, t.sciname ". 
		"FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid ".
		"WHERE ts.taxauthid = 1 AND t.rankid > 140 AND t.sciname LIKE '".$queryString."%' ".
		"ORDER BY t.sciname LIMIT 10";
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
       	$returnArr[] = $row->sciname;
	}
	$con->close();
	echo "['".implode("','",$returnArr)."']";
?>