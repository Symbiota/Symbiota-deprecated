<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$retArrRow = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	// Is the string length greater than 0?
	if($queryString) {
		$sql = "";
		$sql = "SELECT DISTINCT CLID, Name ".
			"FROM fmchecklists ".
			"WHERE Name LIKE '%".$queryString."%' ";
		$sql .= 'LIMIT 10';
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArrRow['label'] = htmlentities($row->Name);
			$retArrRow['value'] = $row->CLID;
			array_push($returnArr, $retArrRow);
         }
	}
	$con->close();
	echo json_encode($returnArr);
?>