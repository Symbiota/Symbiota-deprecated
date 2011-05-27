<?php
	include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$con = MySQLiConnectionFactory::getCon("readonly");
	$collName = $con->real_escape_string($_REQUEST['cname']);
	$collNum = $con->real_escape_string($_REQUEST['cnum']);
	$collDate = array_key_exists('cdate',$_REQUEST)?$con->real_escape_string($_REQUEST['cdate']):'';
	
	if($collName && $collNum){
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE recordedby LIKE "%'.$collName.'%" ';
		if(preg_match('/(\d+)\D*([a-zA-Z]+)$/',$collNum,$m)){
			$sql .= 'AND recordnumber LIKE "'.$m[1].'%'.$m[2].'" ';
		}
		else{
			$sql .= 'AND recordnumber = "'.$collNum.'" ';
		}
		if($collDate) $sql .= ' AND eventdate = "'.$collDate.'"';
		//echo $sql;
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArr[] = $row->occid;
		}
		$result->close();
	}
	$con->close();
	echo json_encode($retArr);
?>