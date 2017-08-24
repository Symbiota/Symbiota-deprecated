<?php
include_once('../../../config/dbconnection.php');

$cid = $_POST['cidinput'];
$cs = $_POST['csinput'];

$retCnt = 0;
if(is_numeric($cid) && is_numeric($cs)){
	$con = MySQLiConnectionFactory::getCon("readonly");
	$sql = 'SELECT count(*) AS cnt FROM kmcslang WHERE cid = '.$cid.' AND cs = '.$cs;
	//echo $sql;
	$rs = $con->query($sql);
	while($r = $rs->fetch_object()) {
		$retCnt = $r->cnt;
	}
	$rs->free();
	$con->close();
}
echo $retCnt;
?>