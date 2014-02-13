<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$occId = $con->real_escape_string($_REQUEST['occid']);

	$sql = 'SELECT s.surveyid, s.projectname '.
		'FROM omsurveys s INNER JOIN omsurveyoccurlink l ON s.surveyid = l.surveyid '.
		'WHERE l.occid = '.$occId;
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$retArr[$row->surveyid] = $row->projectname;
	}
	$result->close();
	$con->close();
	echo '["'.implode('","',($retArr)).'"]';
?>