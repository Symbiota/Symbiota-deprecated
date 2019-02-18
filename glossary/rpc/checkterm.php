<?php
include_once('../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$returnArr = Array();

$term = $con->real_escape_string($_REQUEST['term']);
$language = $con->real_escape_string($_REQUEST['language']);
$relGlossId = $_REQUEST['relglossid'];
if(!is_numeric($relGlossId)) $relGlossId = 0;
$tid = $_REQUEST['tid'];
if(!is_numeric($tid)) $tid = 0;

// Is the string length greater than 0?
if($term && $language && ($tid || $relGlossId)) {
	$sql = '';
	if($tid){
		$sql = 'SELECT g.glossid '.
			'FROM glossary g LEFT JOIN glossarytermlink gl ON g.glossid = gl.glossid '.
			'LEFT JOIN glossarytaxalink t ON gl.glossgrpid = t.glossid '.
			'LEFT JOIN glossarytaxalink t2 ON g.glossid = t2.glossid '.
			'WHERE (g.term = "'.$term.'") AND (g.`language` = "'.$language.'") AND (t.tid = '.$tid.' OR t2.tid = '.$tid.')';
	}
	else{
		$sql = 'SELECT g.glossid '.
			'FROM glossary g INNER JOIN glossarytermlink gl ON g.glossid = gl.glossid '.
			'INNER JOIN glossarytaxalink t ON gl.glossgrpid = t.glossid '.
			'INNER JOIN glossarytermlink gl2 ON gl.glossgrpid = gl2.glossgrpid '.
			'WHERE (g.term = "'.$term.'") AND (g.`language` = "'.$language.'") AND (gl2.glossid = '.$relGlossId.')';
	}
	$result = $con->query($sql);
	if($row = $result->fetch_object()) {
		$returnArr[] = $row->glossid;
	}
	$result->free();
}
$con->close();

if(!$returnArr) echo '';
else echo '1';
?>