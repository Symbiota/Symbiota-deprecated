<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SalixUtilities.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID){
	header('Location: ../../../profile/index.php?refurl=../collections/specprocessor/salix/salixhandler.php?'.$_SERVER['QUERY_STRING']);
}

$action = (isset($_REQUEST['action'])?$_REQUEST['action']:'');
$verbose = (isset($_REQUEST['verbose'])?$_REQUEST['verbose']:1);

$isEditor = 0;
if($SYMB_UID){
	if($isAdmin){
		$isEditor = 1;
	}
	else{
		if(array_key_exists("CollAdmin",$userRights)){
			$isEditor = 1;
		}
	}
}
if($isEditor){
	if($action == ''){
		$salixHanlder = new SalixUtilities();
		$salixHanlder->setVerbose($verbose);
		$salixHanlder->buildWordStats();
	}
}
?>