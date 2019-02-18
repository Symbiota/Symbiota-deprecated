<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');

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
elseif(!($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"])))){
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