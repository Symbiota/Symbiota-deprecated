<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$queryString = $_REQUEST['term'];
	$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
	$stateStr = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:0;

	$sql = 'SELECT DISTINCT county FROM omoccurrences '.
		'WHERE county LIKE "'.$queryString.'%" ';
	if($collId){
		$sql .= 'AND collid = '.$collId.' ';
	}
	if($stateStr){
		$sql .= 'AND stateprovince = "'.$stateStr.'" ';
	}
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArr[] = $row->county;
	}
	$result->close();
	$con->close();
	echo json_encode($retArr);
?>