<?php
	include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$catNum = $_REQUEST['cn'];
	$collId = $_REQUEST['collid'];
	
	if($catNum && $collId){
		$con = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT occid FROM omoccurrences WHERE catalognumber = "'.$catNum.'" AND collid = '.$collId.' ';
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