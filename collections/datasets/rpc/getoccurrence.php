<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$occid = $con->real_escape_string($_REQUEST['occid']);

	$sql = "SELECT recordedby, recordnumber, eventdate ". 
		"FROM omoccurrences ".
		"WHERE occid = ".$occid;
	//echo $sql;
	$result = $con->query($sql);
	if($row = $result->fetch_object()) {
		$retArr['recordedby'] = $row->recordedby;
		$retArr['recordnumber'] = $row->recordnumber;
		$retArr['eventdate'] = $row->eventdate;
	}
	$con->close();
	if($retArr){
		echo json_encode($retArr);
	}
	else{
		return '';
	}
?>