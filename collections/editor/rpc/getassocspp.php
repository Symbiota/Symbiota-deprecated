<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$queryStr = $con->real_escape_string($_REQUEST['term']);

	$str1 = '';$str2 = '';$str3 = '';
	$strArr = explode(' ',$queryStr);
	$strCnt = count($strArr);
	$str1 = $strArr[0];
	if($strCnt > 1){
		$str2 = $strArr[1];
	}
	if($strCnt > 2){
		$str3 = $strArr[2];
	}
	
	if($str1){
		$retArr = Array();
		$sql = 'SELECT sciname '. 
			'FROM taxa '.
			'WHERE unitname1 LIKE "'.$str1.'%" ';
		if($str2){
			$sql .= 'AND unitname2 LIKE "'.$str2.'%" ';
		}
		if($str3){
			$sql .= 'AND unitname3 LIKE "'.$str3.'%" ';
		}
		$sql .= 'AND rankid > 140 ORDER BY sciname LIMIT 10';
		//echo $sql;
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArr[] = $row->sciname;
		}
		$con->close();
		echo '["'.implode('","',($retArr)).'"]';
	}
	else{
		echo '';
	}
?>