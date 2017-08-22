<?php
include_once('../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/SpecUpload.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$searchVar = array_key_exists('searchvar',$_REQUEST)?$_REQUEST['searchvar']:'';

$uploadManager = new SpecUpload();
$uploadManager->setCollId($collid);

if($SYMB_UID){
	//Set variables
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
		$recArr = $uploadManager->exportPendingImport($searchVar);
	}
}