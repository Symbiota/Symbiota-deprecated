<?php
include_once('../../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$occid = $_POST['occid'];
if(is_numeric($occid)){
	$sql = "SELECT recordedby, recordnumber, eventdate ". 
		"FROM omoccurrences ".
		"WHERE occid = ".$occid;
	//echo $sql;
	$rs = $con->query($sql);
	if($row = $rs->fetch_object()){
		$retArr['recordedby'] = $row->recordedby;
		$retArr['recordnumber'] = $row->recordnumber;
		$retArr['eventdate'] = $row->eventdate;
	}
	$rs->free();
	$con->close();
}
echo json_encode($retArr);
?>