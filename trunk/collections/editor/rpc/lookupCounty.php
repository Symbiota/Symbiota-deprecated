<?php

 	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$queryString = $con->real_escape_string(htmlspecialchars($_REQUEST['term']));
	$stateStr = array_key_exists('state',$_REQUEST)?$con->real_escape_string(htmlspecialchars($_REQUEST['state'])):'';

	$sql = 'SELECT DISTINCT c.countyname FROM lkupcounty c ';
	$sqlWhere = 'WHERE c.countyname LIKE "'.$queryString.'%" ';
	if($stateStr){
		$sql .= 'INNER JOIN lkupstateprovince s ON c.stateid = s.stateid ';
		$sqlWhere .= 'AND s.statename = "'.$stateStr.'" ';
	}
	$sql .= $sqlWhere.'ORDER BY c.countyname';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArr[] = $row->countyname;
	}
	$result->close();
	$con->close();
	if($retArr){
		echo json_encode($retArr);
	}
	else{
		echo '';
	}
?>