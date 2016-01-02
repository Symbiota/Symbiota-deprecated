<?php
include_once('../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();
$language = $con->real_escape_string($_REQUEST['language']);
$tid = $con->real_escape_string($_REQUEST['tid']);
// Is the string length greater than 0?
if($language && $tid) {
	$sql = "";
	$sql = "SELECT g.glossid ".
		"FROM (glossary AS g LEFT JOIN glossarytermlink AS gl ON g.glossid = gl.glossid) ".
		"LEFT JOIN glossarytaxalink AS t ON gl.glossgrpid = t.glossgrpid ".
		"WHERE g.`language` = '".$language."' AND t.tid IN(".$tid.") ";
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$returnArr[] = $row->glossid;
	}
}
$con->close();
if(!$returnArr){
	$returnArr = 'null';
}
echo json_encode($returnArr);
?>