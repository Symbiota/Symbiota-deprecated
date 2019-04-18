<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributes.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_REQUEST['occid'];
$action = array_key_exists('submitAction',$_REQUEST)?$_REQUEST['submitAction']:'';

$postArr = array('occid' => $occid, 'traitid' => $_REQUEST['traitID'], 'setstatus' => $_REQUEST['setStatus'], 'source' => $_REQUEST['source'], 'notes' => $_REQUEST['notes']);

$stateArr = json_decode($_REQUEST['stateData'],true);
$postArr = array_merge($postArr,$stateArr);

$status = 0;

$attrManager = new OccurrenceAttributes();
$attrManager->setOccid($occid);

$isEditor = false;
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = true;
	}
	elseif($collid){
		//If a page related to collections, one maight want to...
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = true;
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$isEditor = true;
		}
	}
}

if($isEditor){
	if($action == 'addTraitCoding'){
		if($attrManager->addAttributes($postArr,$SYMB_UID)){
			$status = 1;
		}
	}
	elseif($action == 'editTraitCoding'){
		if($attrManager->editAttributes($postArr)){
			$status = 1;
		}
	}
	if($attrManager->getErrorMessage()) echo $attrManager->getErrorMessage();
}
echo $status;
?>