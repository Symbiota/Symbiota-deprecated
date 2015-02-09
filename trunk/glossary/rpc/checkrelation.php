<?php
include_once('../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();
$term = $con->real_escape_string($_REQUEST['term']);
$language = $con->real_escape_string($_REQUEST['language']);
$tid = $con->real_escape_string($_REQUEST['tid']);
// Is the string length greater than 0?
if($term && $language && $tid) {
	$sql = "";
	$sql = "SELECT g.glossid, g.definition, g.source, g.notes, gl.glossgrpid ".
		"FROM (glossary AS g LEFT JOIN glossarytermlink AS gl ON g.glossid = gl.glossid) ".
		"LEFT JOIN glossarytaxalink AS t ON gl.glossgrpid = t.glossgrpid ".
		"WHERE g.term = '".$term."' AND g.`language` = '".$language."' AND t.tid = ".$tid." ";
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
		$returnArr['glossid'] = $row->glossid;
		$returnArr['glossgrpid'] = $row->glossgrpid;
		$returnArr['definition'] = $row->definition;
		$returnArr['source'] = $row->source;
		$returnArr['notes'] = $row->notes;
	}
}
$con->close();

echo json_encode($returnArr);
?>