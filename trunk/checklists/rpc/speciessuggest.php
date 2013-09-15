<?php
	include_once('../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	$clid = $con->real_escape_string($_REQUEST['cl']);
	
	$sql = 'SELECT DISTINCT tid, sciname '. 
		'FROM taxa '.
		'WHERE tid NOT IN (SELECT tid FROM fmchklsttaxalink WHERE clid = '.$clid.') '.
		'AND rankid > 140 AND sciname LIKE "'.$queryString.'%" ';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$sn = $row->sciname;
		if(strtolower($charset) == "iso-8859-1"){
			if(mb_detect_encoding($sn,'UTF-8,ISO-8859-1',true) == "ISO-8859-1") $sn = utf8_encode($sn);
		}
       	$returnArr[] = $sn;
	}
	$con->close();
	echo json_encode($returnArr);
?>