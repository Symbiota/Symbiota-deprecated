<?php
include_once('../../../config/symbini.php');
include_once('../../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$queryString = $con->real_escape_string($_REQUEST['term']);

$sql = 'SELECT DISTINCT countryname FROM lkupcountry '.
	'WHERE countryname LIKE "'.$queryString.'%" ';
//echo $sql;
$result = $con->query($sql);
while ($row = $result->fetch_object()) {
	$countryStr = $row->countryname;
	if($charset == 'ISO-8859-1'){
		if(mb_detect_encoding($countryStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
			$countryStr = utf8_encode($countryStr);
		}
	}
	$retArr[] = $countryStr;
}
$result->free();
$con->close();
sort($retArr);
if($retArr){
	echo '["'.implode('","',($retArr)).'"]';
}
else{
	echo '';
}
?>