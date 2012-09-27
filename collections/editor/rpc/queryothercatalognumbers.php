<?php
	include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$con = MySQLiConnectionFactory::getCon("readonly");
	$inValue = $con->real_escape_string(htmlspecialchars($_REQUEST['invalue']));
	$collId = $con->real_escape_string($_REQUEST['collid']);
	$occid = $con->real_escape_string($_REQUEST['occid']);
	
	if($inValue && $collId){
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE othercatalognumbers = "'.$inValue.'" AND collid = '.$collId.' AND occid <> '.$occid;
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