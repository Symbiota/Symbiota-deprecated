<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');

$otherCatNum = array_key_exists('othercatnum',$_POST)?trim($_POST['othercatnum']):'';
$collid = array_key_exists('collid',$_POST)?trim($_POST['collid']):0;
$currentOccid = array_key_exists('occid',$_POST)?trim($_POST['occid']):0;

sdga

$dupeManager = new OccurrenceDuplicate();
$retStr = $dupeManager->getDupes($collName, $collNum, $collDate, $ometid, $exsNumber, $currentOccid);
echo $retStr;


include_once('../../../config/dbconnection.php');
	$retArr = Array();
	$con = MySQLiConnectionFactory::getCon("readonly");
	$inValue = $con->real_escape_string($_REQUEST['invalue']);
	$collId = $con->real_escape_string($_REQUEST['collid']);
	$occid = $con->real_escape_string($_REQUEST['occid']);
	
	if($inValue && $collId){
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE othercatalognumbers = "'.$inValue.'" AND collid = '.$collId.' AND occid <> '.$occid;
		//echo $sql;
		$result = $con->query($sql);
		while ($row = $result->fetch_object()) {
			$retArr[] = $row->occid;
		}
		$result->close();
	}
	$con->close();
	echo 'ocnum:'.implode(',',$retArr);
?>