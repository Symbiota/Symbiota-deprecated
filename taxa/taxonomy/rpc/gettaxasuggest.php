<?php
	include_once('../../../config/symbini.php');
	include_once($SERVER_ROOT.'/config/dbconnection.php');
	
	$con = MySQLiConnectionFactory::getCon("readonly");
	
	$q = $con->real_escape_string($_REQUEST['term']);
    $hideAuth = array_key_exists('hideauth',$_REQUEST)?$con->real_escape_string($_REQUEST['hideauth']):false;
	$taxAuthId = array_key_exists('taid',$_REQUEST)?$con->real_escape_string($_REQUEST['taid']):0;
	$rankLimit = array_key_exists('rlimit',$_REQUEST)?$con->real_escape_string($_REQUEST['rlimit']):0;
	$rankLow = array_key_exists('rlow',$_REQUEST)?$con->real_escape_string($_REQUEST['rlow']):0;
	$rankHigh = array_key_exists('rhigh',$_REQUEST)?$con->real_escape_string($_REQUEST['rhigh']):0;

	$returnArr = Array();
	
	$sqlWhere = '';
	$sql = 'SELECT DISTINCT t.tid, t.sciname'.(!$hideAuth?',t.author':'').' FROM taxa t ';
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
	//$sql .= 'ORDER BY t.sciname';
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
        $returnArr[] = $row->sciname.(!$hideAuth?' '.$row->author:'');
	    //if($CHARSET == 'UTF-8') $returnArr[] = $row->sciname.' '.$row->author;
		//else $returnArr[] = utf8_encode($row->sciname.' '.$row->author);
	}
	$result->free();
	$con->close();
	echo json_encode($returnArr);
?>