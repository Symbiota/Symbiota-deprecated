<?php
include_once('../../../config/dbconnection.php');

$cid = $_POST['cidinput'];
$cs = (array_key_exists('csinput',$_POST)?$_POST['csinput']:0);

$retCnt = 0;
if(is_numeric($cid) && is_numeric($cs)){
	$con = MySQLiConnectionFactory::getCon("readonly");
	$sql = 'SELECT count(*) AS cnt FROM kmdescr WHERE cid = '.$cid;
	if($cs) $sql .= ' AND cs = '.$cs;
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