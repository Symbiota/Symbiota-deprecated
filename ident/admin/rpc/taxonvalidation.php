<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	header("Content-Type: text/html; charset=".$charset);
	$con = MySQLiConnectionFactory::getCon("readonly");
	$queryString = $con->real_escape_string($_REQUEST['term']);
	$retStr = '';
	if($queryString){
		$sql = 'SELECT tid '. 
			'FROM taxa '.
			'WHERE sciname = "'.$queryString.'" ';
		//echo $sql;
		$result = $con->query($sql);
		if($row = $result->fetch_object()) {
	       	$retStr = $row->tid;
		}
		$result->free();
	}
	$con->close();
	echo $retStr;
?>