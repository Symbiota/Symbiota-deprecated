<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);

	$sql = 'SELECT DISTINCT ometid, title, abbreviation FROM omexsiccatititles '.
		'WHERE title LIKE "%'.$queryString.'%" OR abbreviation LIKE "%'.$queryString.'%" ORDER BY title';
	//echo $sql;
	$result = $con->query($sql);
	while ($r = $result->fetch_object()) {
		$retArr[] = '"id": '.$r->ometid.', "value":"'.str_replace('"',"''",$r->title.($r->abbreviation?' ['.$r->abbreviation.']':'')).'"';
	}
	$con->close();
	echo '[{'.implode('},{',$retArr).'}]';
?>