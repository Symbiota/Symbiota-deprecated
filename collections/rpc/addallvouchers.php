<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

$clid = $_REQUEST["clid"];
$occArr = $_REQUEST["jsonOccArr"];
$tid = $_REQUEST["tid"];

if(!$clid || !is_numeric($clid)){
	echo "ERROR: Checklist ID is null";
}
elseif(!$occArr){
	echo "ERROR: Specimen identifiers are missing";
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
	$result = 0;
	foreach($occArr as $occId){
		if($clManager->linkVoucher($tid,$occId,1) != 1){
			$result = 0;
			break;
		}
		else{
			$result = 1;
		}
	}
	if($result){
		echo 1;
	}
	else{
		echo "ERROR: Problem adding vouchers to checklist";
	}
}
?>