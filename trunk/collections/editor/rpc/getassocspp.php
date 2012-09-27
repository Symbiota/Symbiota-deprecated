<?php
	include_once('../../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$queryStr = $con->real_escape_string(htmlspecialchars($_REQUEST['term']));

	$str1 = '';$str2 = '';$str3 = '';
	$strArr = explode(' ',$queryStr);
	$strCnt = count($strArr);
	if($strCnt == 2){
		$str1 = $strArr[0];
		$str2 = $strArr[1];
	}
	else if($strCnt > 2){
		$str1 = $strArr[0];
		$str2 = $strArr[1];
		$str3 = $strArr[2];
	}
	
	if($str1){
		$retArr = Array();
		$sql = 'SELECT sciname '. 
			'FROM taxa '.
			'WHERE unitname1 LIKE "'.$str1.'%" AND unitname2 LIKE "'.$str2.'%" ';
		if($str3){
			$sql .= 'AND unitname3 LIKE "'.$str3.'%" ';
		}
		else{
			//$sql .= 'AND rankid = 220 ';
		}
		$sql .= 'ORDER BY sciname LIMIT 10';
		//echo $sql;
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArr[] = $row->sciname;
		}
		$con->close();
		echo json_encode($retArr);
	}
	else{
		echo '';
	}
?>