<?php
include_once('../../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$queryTerm = $con->real_escape_string($_REQUEST['term']);
$queryTerm = str_replace('"',"''",$queryTerm);

$retStr = '';
$sql = 'SELECT ometid FROM omexsiccatititles '.
	'WHERE CONCAT_WS("",title,CONCAT(" [",abbreviation,"]")) = "'.$queryTerm.'"';
//echo $sql;
$rs = $con->query($sql);
if($r = $rs->fetch_object()) {
	$retStr = $r->ometid;
}
$rs->free();
$con->close();

echo $retStr;
?>