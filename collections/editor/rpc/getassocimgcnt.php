<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retCnt = 0;
	$occId = $con->real_escape_string($_REQUEST['occid']);

	$sql = 'SELECT count(*) AS imgcnt FROM images WHERE occid = '.$occId;
	//echo $sql;
	$result = $con->query($sql);
	while($row = $result->fetch_object()) {
		$retCnt = $row->imgcnt;
	}
	$result->close();
	$con->close();
	echo $retCnt;
?>