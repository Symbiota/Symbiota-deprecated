<?php
include_once('../../../config/symbini.php');
include_once('../../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$queryString = $con->real_escape_string($_REQUEST['term']);
$countryStr = array_key_exists('country',$_REQUEST)?$con->real_escape_string($_REQUEST['country']):'';

$sql = 'SELECT DISTINCT s.statename FROM lkupstateprovince s ';
$sqlWhere = 'WHERE s.statename LIKE "'.$queryString.'%" ';
if($countryStr){
	$sql .= 'INNER JOIN lkupcountry c ON s.countryid = c.countryid ';
	$sqlWhere .= 'AND c.countryname = "'.$countryStr.'" ';
}
$sql .= $sqlWhere.'ORDER BY s.statename';
//echo $sql;
$result = $con->query($sql);
while ($row = $result->fetch_object()) {
	$stateStr = $row->statename;
	if($charset == 'ISO-8859-1'){
		if(mb_detect_encoding($stateStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
			$stateStr = utf8_encode($stateStr);
		}
	}
	$retArr[] = $stateStr;
}
$result->close();
$con->close();
if($retArr){
	echo '["'.implode('","',($retArr)).'"]';
}
else{
	echo '';
}
?>