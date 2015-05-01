<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverOccurrence.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Content-Type: text/html; charset=".$charset);

$collid = $_REQUEST["collid"];
$archiveFile = '';
if($collid && is_numeric($collid)){
	$isEditor = false;
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	 	$isEditor = true;
	}
	
	if($isEditor){
		$processingStatus = array_key_exists('ps',$_REQUEST)?$_REQUEST['ps']:'';
		$customField1 = array_key_exists('cf1',$_POST)?$_POST['cf1']:'';
		$customField2 = array_key_exists('cf2',$_POST)?$_POST['cf2']:'';
		
		$dwcaHandler = new DwcArchiverOccurrence();
	
		$dwcaHandler->setCharSetOut('UTF-8');
		$dwcaHandler->setSchemaType('coge');
		$dwcaHandler->setExtended(false);
		$dwcaHandler->setDelimiter('csv');
		$dwcaHandler->setVerbose(0);
		$dwcaHandler->setRedactLocalities(0);
		$dwcaHandler->setIncludeDets(0);
		$dwcaHandler->setIncludeImgs(0);
		$dwcaHandler->setCollArr($collid);
		$dwcaHandler->addCondition('decimallatitude','NULL');
		$dwcaHandler->addCondition('decimallongitude','NULL');
		$dwcaHandler->addCondition('locality','NOTNULL');
		if($processingStatus) $dwcaHandler->addCondition('processingstatus','EQUALS',$processingStatus);
		if($customField1) $dwcaHandler->addCondition($customField1,$_POST['ct1'],$_POST['cv1']);
		if($customField2) $dwcaHandler->addCondition($customField2,$_POST['ct2'],$_POST['cv2']);

		$tPath = $SERVER_ROOT;
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
			$tPath .= '/';
		}
		$tPath .= "temp/data/geolocate/";
		$dwcaHandler->setTargetPath($tPath);
		$archiveFile = $dwcaHandler->createDwcArchive('CoGeCommunityFile');
	}
}
echo json_encode($archiveFile)
?>