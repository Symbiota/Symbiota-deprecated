<?php
include_once('../../../config/symbini.php');
include_once('../../../config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$term = $con->real_escape_string($_REQUEST['term']);

$sql = 'SELECT CONCAT(CONCAT_WS(", ",u.lastname, u.firstname)," - ",l.username," [#",u.uid,"]") AS username '. 
	'FROM users u INNER JOIN userlogin l ON u.uid = l.uid '.
	'WHERE u.lastname = "'.$term.'" OR l.username = "'.$term.'"';
//echo $sql;
$rs = $con->query($sql);
while($r = $rs->fetch_object()) {
	$username = $r->username;
	if($charset == 'ISO-8859-1'){
		if(mb_detect_encoding($username,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
			$username = utf8_encode($username);
		}
	}
	$retArr[] = $username;
}
$rs->free();
$con->close();
if($retArr){
	echo json_encode($retArr);
}
else{
	echo '[]';
}
?>