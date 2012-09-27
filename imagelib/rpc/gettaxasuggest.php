<?php
	include_once('../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$q = $con->real_escape_string(htmlspecialchars($_REQUEST['term']));

	$sql = 'SELECT t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'WHERE ts.taxauthid = 1 AND t.rankid > 140 AND t.sciname LIKE "'.$q.'%" ';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
       	$returnArr[] = $row->sciname;
	}
	$con->close();
	echo json_encode($returnArr);
?>