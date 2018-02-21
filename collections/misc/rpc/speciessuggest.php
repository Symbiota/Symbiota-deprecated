<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	
	$sql = 'SELECT tid, sciname '. 
		'FROM taxa '.
		'WHERE sciname LIKE "'.$queryString.'%" ';
	//echo $sql;
	$rs = $con->query($sql);
	while ($row = $rs->fetch_object()) {
       	$returnArr[] = $row->sciname;
	}
	$rs->free();
	$con->close();
	echo json_encode($returnArr);
?>