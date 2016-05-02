<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$q = $con->real_escape_string($_REQUEST['term']);
	$taxAuthId = array_key_exists('taid',$_REQUEST)?$con->real_escape_string($_REQUEST['taid']):0;
	$rankLimit = array_key_exists('rlimit',$_REQUEST)?$con->real_escape_string($_REQUEST['rlimit']):0;
	$rankLow = array_key_exists('rlow',$_REQUEST)?$con->real_escape_string($_REQUEST['rlow']):0;
	$rankHigh = array_key_exists('rhigh',$_REQUEST)?$con->real_escape_string($_REQUEST['rhigh']):0;

	$sqlWhere = '';
	$sql = 'SELECT t.tid, t.sciname FROM taxa t ';
	if($taxAuthId){
		$sql .= 'INNER JOIN taxstatus ts ON t.tid = ts.tid ';
		$sqlWhere .= 'AND ts.taxauthid = '.$taxAuthId.' ';
	}
	if($q){
		$sqlWhere .= 'AND t.sciname LIKE "'.$q.'%" ';
	}
	if($rankLimit){
		$sqlWhere .= 'AND (t.rankid = '.$rankLimit.') ';
	}
	else{
		if($rankLow){
			$sqlWhere .= 'AND (t.rankid > '.$rankLow.' OR t.rankid IS NULL) ';
		}
		if($rankHigh){
			$sqlWhere .= 'AND (t.rankid < '.$rankHigh.' OR t.rankid IS NULL) ';
		}
	}
	if($sqlWhere){
		$sql .= 'WHERE '.substr($sqlWhere,4);
	}
	$sql .= 'ORDER BY t.sciname LIMIT 10';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		//$returnArr[] = '{ "value": "'.$row->sciname.'", "tid": "'.$row->tid.'" }';
		$returnArr[] = utf8_encode($row->sciname);
	}
	$con->close();
	//echo '[ '.implode(',',$returnArr).' ]';
	echo json_encode($returnArr);
?>