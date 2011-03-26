<?php
	include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$collName = $_REQUEST['cname'];
	$collNum = $_REQUEST['cnum'];
	$collDate = array_key_exists('cdate',$_REQUEST)?$_REQUEST['cdate']:'';
	
	if($collName && $collNum){
		$con = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE recordedby LIKE "%'.$collName.'%" AND recordnumber = '.$collNum.' ';
		if($collDate) $sql .= ' AND eventdate = "'.$collDate.'"';
		//echo $sql;
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArr[] = $row->occid;
		}
		$result->close();
		$con->close();
	}
	echo json_encode($retArr);
?>