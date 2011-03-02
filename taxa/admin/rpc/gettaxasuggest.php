<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$q = $_REQUEST['q'];
	$taxAuthId = array_key_exists('taid',$_REQUEST)?$_REQUEST['taid']:0;
	$rankLimit = array_key_exists('rlimit',$_REQUEST)?$_REQUEST['rlimit']:0;
	$rankLow = array_key_exists('rlow',$_REQUEST)?$_REQUEST['rlow']:0;
	$rankHigh = array_key_exists('rhigh',$_REQUEST)?$_REQUEST['rhigh']:0;

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
		$sqlWhere .= 'AND t.rankid = '.$rankLimit.' ';
	}
	else{
		if($rankLow){
			$sqlWhere .= 'AND t.rankid > '.$rankLow.' ';
		}
		if($rankHigh){
			$sqlWhere .= 'AND t.rankid < '.$rankHigh.' ';
		}
	}
	if($sqlWhere){
		$sql .= 'WHERE '.substr($sqlWhere,4);
	}
	$sql .= 'ORDER BY t.sciname LIMIT 10';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
       	$returnArr[] = $row->sciname;
	}
	$con->close();
	echo "['".implode("','",$returnArr)."']";
?>