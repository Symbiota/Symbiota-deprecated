<?php
	//include_once('../../../config/symbini.php');
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$retArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);

	$sql = 'SELECT DISTINCT title, abbreviation '. 
		'FROM omexsiccatititles '.
		'WHERE title LIKE "%'.$queryString.'%" OR abbreviation LIKE "%'.$queryString.'%" '.
		'ORDER BY title';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$title = $row->title;
		$abbr = $row->abbreviation;
		if(stripos($title,$queryString) !== false){
			$retArr[] = utf8_encode($title);
		}
		if($title != $abbr && stripos($abbr,$queryString) !== false){
			$retArr[] = utf8_encode($abbr);
		}
	}
	$con->close();
	$retArr = array_unique($retArr);
	sort($retArr);
	echo json_encode($retArr);
?>