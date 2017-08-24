<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');

$collId = $_REQUEST['collid'];
$fieldName = $_REQUEST['fieldname'];
$oldValue = $_REQUEST['oldvalue'];
$buMatch = array_key_exists('bumatch',$_REQUEST)?$_REQUEST['bumatch']:0;
$ouid = array_key_exists('ouid',$_REQUEST)?$_REQUEST['ouid']:0;
$retCnt = '';
if($fieldName){
	$occManager = new OccurrenceEditorManager();
	$occManager->setCollId($collId);
	if($ouid){
		$occManager->setQueryVariables(array('ouid' => $ouid));
	}
	else{
		$occManager->setQueryVariables();
	}
	$occManager->setSqlWhere();
	
	$retCnt = $occManager->getBatchUpdateCount($fieldName,$oldValue, $buMatch);
}
echo $retCnt;
?>