<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Content-Type: text/html; charset=".$charset);

$collid = $_REQUEST["collid"];
$cntStr = '';
if($collid && is_numeric($collid)){
	$isEditor = false;
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	 	$isEditor = true;
	}
	
	if($isEditor){

		$processingStatus = array_key_exists('ps',$_REQUEST)?$_REQUEST['ps']:'';
		$customField1 = array_key_exists('cf1',$_POST)?$_POST['cf1']:'';
		$customField2 = array_key_exists('cf2',$_POST)?$_POST['cf2']:'';
		
		$dwcaHandler = new DwcArchiverCore();
		$dwcaHandler->setCollArr($collid);
		$dwcaHandler->setVerboseMode(0);
		$dwcaHandler->addCondition('decimallatitude','NULL');
		$dwcaHandler->addCondition('decimallongitude','NULL');
		$dwcaHandler->addCondition('catalognumber','NOTNULL');
		$dwcaHandler->addCondition('locality','NOTNULL');
		if($processingStatus) $dwcaHandler->addCondition('processingstatus','EQUALS',$processingStatus);
		if($customField1) $dwcaHandler->addCondition($customField1,$_POST['ct1'],$_POST['cv1']);
		if($customField2) $dwcaHandler->addCondition($customField2,$_POST['ct2'],$_POST['cv2']);
		$cntStr = $dwcaHandler->getOccurrenceCnt();

	}
}
echo json_encode($cntStr)
?>