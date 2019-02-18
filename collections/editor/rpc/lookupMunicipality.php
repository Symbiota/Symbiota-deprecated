<?php
include_once('../../../config/symbini.php');
include_once('../../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$queryString = $con->real_escape_string($_REQUEST['term']);
$stateStr = array_key_exists('state',$_REQUEST)?$con->real_escape_string($_REQUEST['state']):'';

$sql = 'SELECT DISTINCT m.municipalityname FROM lkupmunicipality m ';
$sqlWhere = 'WHERE m.municipalityname LIKE "'.$queryString.'%" ';
if($stateStr){
	$sql .= 'INNER JOIN lkupstateprovince s ON m.stateid = s.stateid ';
	$sqlWhere .= 'AND s.statename = "'.$stateStr.'" ';
}
$sql .= $sqlWhere;	//.'ORDER BY m.municipalityname';
//echo $sql;
$rs = $con->query($sql);
if($rs){
	while($r = $rs->fetch_object()) {
		$munStr = $r->municipalityname;
		if($CHARSET == 'ISO-8859-1'){
			if(mb_detect_encoding($munStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
				$munStr = utf8_encode($munStr);
			}
		}
		$retArr[] = $munStr;
	}
	$rs->free();
}
$con->close();
echo json_encode($retArr);
?>