<?php
include_once("../../../util/dbconnection.php");
include_once("../../../util/symbini.php");
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//get the q parameter from URL
$family = $_REQUEST["family"]; 

$returnList = Array();
$con = MySQLiConnectionFactory::getCon("readonly");
$sql = "SELECT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
	"WHERE (ts.taxauthid = 1) AND (t.rankid > 180) AND (ts.tid = ts.tidaccepted) AND (ts.family = '".$family."') ORDER BY t.sciname";
$result = $con->query($sql);
while($row = $result->fetch_object()){
	$returnList[$row->tid] = $row->sciname;
}
$result->close();
if(!($con === false)) $con->close();

$responseStr = "";
if ($returnList){
	$responseStr = "<option SELECTED>Select Accepted Name</option>";
	foreach($returnList as $k=>$v){
		$responseStr .= "<option value='".$k."'>".$v."</option>";
	}
}
else{
	$responseStr = "<option SELECTED>No Taxa Returned</option>";
}

//output the response
echo $responseStr;
?>