<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

$clid = $_REQUEST["clid"];
$occid = $_REQUEST["occid"];
$tid = $_REQUEST["tid"];

if(!$clid || !is_numeric($clid)){
	echo "ERROR: Checklist ID is null";
}
elseif(!$occid || !is_numeric($occid)){
	echo "ERROR: Occurrence ID is null";
}
elseif(!$tid || !is_numeric($tid)){
	echo "ERROR: Problem with taxon name (null tid), contact administrator"; 
}
elseif(!($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"])))){
	echo "ERROR: Permissions Error";
}
else{
	$clManager = new ChecklistVoucherAdmin();
	$clManager->setClid($clid);
	//Method returns 1 on success and a string message upon failure
	echo $clManager->linkVoucher($tid,$occid,1);
}
?>