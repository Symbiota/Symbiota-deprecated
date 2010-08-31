<?php
 include_once('../../config/dbconnection.php');
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['q']);
	// Is the string length greater than 0?
	if(strlen($queryString) >0) {
		$sql = "SELECT DISTINCT o.sciname FROM omoccurrences o ".
			"WHERE o.TidInterpreted IS NOT NULL AND o.sciname LIKE '".$queryString."%' ".
			"ORDER BY o.sciname LIMIT 8";
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$returnArr[] = $row->sciname;
         }
	}
	$con->close();
	echo "['".implode("','",$returnArr)."']";
?>