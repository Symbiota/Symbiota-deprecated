<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$q = $con->real_escape_string($_REQUEST['term']);

$sql = 'SELECT tid, sciname FROM taxa '.
	'WHERE sciname LIKE "'.$q.'%" ';
//echo $sql;
$result = $con->query($sql);
while ($r = $result->fetch_object()) {
    $retArr[] = (object)array(
        'value' => $r->tid,
        'label' => $r->sciname);
}
$con->close();
echo json_encode($retArr);

?>