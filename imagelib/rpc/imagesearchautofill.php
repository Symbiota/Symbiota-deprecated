<?php
	include_once('../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['q']);
	$type = $con->real_escape_string($_REQUEST['t']);
	if($queryString && $type){
		$sql = "";
		if($type == "taxa"){
			$sql = "SELECT DISTINCT sciname ".
				"FROM taxa ".
				"WHERE sciname LIKE '".$queryString."%' ".
				"LIMIT 10 ";
			$result = $con->query($sql);
			$i = 0;
			while ($row = $result->fetch_object()) {
				$returnArr[$i]['name'] = htmlentities($row->sciname);
				$i++;
			}
		}
		if($type == "common"){
			$sql = "SELECT DISTINCT VernacularName ".
				"FROM taxavernaculars ".
				"WHERE VernacularName LIKE '".$queryString."%' ".
				"LIMIT 10 ";
			$result = $con->query($sql);
			$i = 0;
			while ($row = $result->fetch_object()) {
				$returnArr[$i]['name'] = htmlentities($row->VernacularName);
				$i++;
			}
		}
		if($type == "photographer"){
			$retArrRow = Array();
			$sql = "SELECT DISTINCT u.uid, CONCAT_WS(' ',u.firstname,u.lastname) AS fullname ".
				"FROM images AS i LEFT JOIN users AS u ON i.photographeruid = u.uid ".
				"WHERE u.firstname LIKE '".$queryString."%' OR u.lastname LIKE '".$queryString."%' ".
				"ORDER BY fullname ".
				"LIMIT 10 ";
			$result = $con->query($sql);
			$i = 0;
			while ($row = $result->fetch_object()) {
				$returnArr[$i]['name'] = htmlentities($row->fullname);
				$returnArr[$i]['id'] = htmlentities($row->uid);
				$i++;
			}
		}
		if($type == "keywords"){
			$sql = "SELECT DISTINCT keyword ".
				"FROM imagekeywords ".
				"WHERE keyword LIKE '".$queryString."%' ".
				"LIMIT 10 ";
			$result = $con->query($sql);
			$i = 0;
			while ($row = $result->fetch_object()) {
				$returnArr[$i]['name'] = htmlentities($row->keyword);
				$i++;
			}
		}
	}
	$con->close();
	echo json_encode($returnArr);
?>