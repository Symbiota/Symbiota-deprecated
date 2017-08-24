<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$con = MySQLiConnectionFactory::getCon("readonly");
$ident = $con->real_escape_string($_REQUEST["ident"]);
$collId = $con->real_escape_string($_REQUEST["collid"]);

$responseStr = "";
$sql = "SELECT loanid ".
	"FROM omoccurloans ".
	'WHERE loanIdentifierBorr = "'.$ident.'" AND collidBorr = '.$collId;
//echo $sql;
$result = $con->query($sql);
while ($row = $result->fetch_object()) {
	$returnArr[] = $row->loanid;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo json_encode($returnArr);
?>