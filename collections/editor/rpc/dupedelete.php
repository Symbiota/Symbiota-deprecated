<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');

$dupid = array_key_exists('dupid',$_REQUEST)?$_REQUEST['dupid']:'';
$occid = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:'';

$collArr = array(); 
if(array_key_exists("CollAdmin",$USER_RIGHTS)) $collArr = $USER_RIGHTS['CollAdmin'];
if(array_key_exists("CollAdmin",$USER_RIGHTS)) $collArr = array_merge($collArr, $USER_RIGHTS['CollEditor']);
if($IS_ADMIN || $collArr){
	if(is_numeric($occid) && is_numeric($dupid)){
		$dupeManager = new OccurrenceDuplicate();
		if($dupeManager->deleteOccurFromCluster($dupid, $occid, $collArr)){
			echo '1';
		}
		else{
			echo $dupeManager->getErrorStr();
		}
	}
	else{
		echo 'ERROR unknown [1]';
	}
}
else{
	echo 'ERROR unknown [2]';
}
?>