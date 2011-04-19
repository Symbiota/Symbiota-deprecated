<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	$collId = array_key_exists('collid',$_REQUEST)?$con->real_escape_string($_REQUEST['collid']):0;
	$countryStr = array_key_exists('country',$_REQUEST)?$con->real_escape_string($_REQUEST['country']):0;

	$sql = 'SELECT DISTINCT stateprovince FROM omoccurrences '.
		'WHERE stateprovince LIKE "'.$queryString.'%" ';
	if($collId){
		$sql .= 'AND collid = '.$collId.' ';
	}
	if($countryStr){
		$sql .= 'AND country = "'.$countryStr.'" ';
	}
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArr[] = $row->stateprovince;
	}
	$result->close();
	$con->close();
	echo json_encode($retArr);
?>