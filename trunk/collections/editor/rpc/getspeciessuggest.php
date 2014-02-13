<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);

	$sql = "SELECT DISTINCT t.tid, t.sciname ". 
		"FROM taxa t ".
		"WHERE t.sciname LIKE '".$queryString."%' ".
		"ORDER BY t.sciname LIMIT 10";
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArr[] = $row->sciname;
	}
	$con->close();
	echo '["'.implode('","',($retArr)).'"]';
?>