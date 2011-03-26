<?php
	include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$occurrenceId = $_REQUEST['oi'];
	$collId = $_REQUEST['collid'];
	
	if($occurrenceId && $collId){
		$con = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT occid FROM omoccurrences WHERE occurrenceid = "'.$occurrenceId.'" AND collid = '.$collId.' ';
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