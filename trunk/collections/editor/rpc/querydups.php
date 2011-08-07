<?php
	include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$con = MySQLiConnectionFactory::getCon("readonly");
	$collName = $con->real_escape_string($_REQUEST['cname']);
	$collNum = $con->real_escape_string($_REQUEST['cnum']);
	$collDate = array_key_exists('cdate',$_REQUEST)?$con->real_escape_string($_REQUEST['cdate']):'';
	
	if($collName && $collNum){
		//Parse last name from collector's name 
		$lastName = "";
		$lastNameArr = explode(',',$collName);
		$lastNameArr = explode(';',$lastNameArr[0]);
		$lastNameArr = explode('&',$lastNameArr[0]);
		$lastNameArr = explode(' and ',$lastNameArr[0]);
		$lastNameArr = preg_match_all('/[A-Za-z]{3,}/',$lastNameArr[0],$match);
		if($match){
			if(count($match[0]) == 1){
				$lastName = $match[0][0];
			}
			elseif(count($match[0]) > 1){
				$lastName = $match[0][1];
			}
		}
		
		$sql = 'SELECT occid FROM omoccurrences WHERE recordedby LIKE "%'.$lastName.'%" ';
		if(preg_match('/(\d+)\D*([a-zA-Z]+)$/',$collNum,$m)){
			$sql .= 'AND recordnumber LIKE "'.$m[1].'%'.$m[2].'" ';
		}
		else{
			$sql .= 'AND recordnumber = "'.$collNum.'" ';
		}
		if($collDate) $sql .= ' AND eventdate = "'.$collDate.'"';
		//echo $sql;
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArr[] = $row->occid;
		}
		$result->close();
	}
	$con->close();
	echo json_encode($retArr);
?>