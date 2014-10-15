<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/DwcArchiverOccurrence.php');
	
$collStr = $argv[1];
$serverDomain = $argv[2];
$includeDets = 1;
$includeImgs = 1;
$redactLocalities = 1;
if($argc > 3 && is_numeric($argv[3])){
	$includeDets = $argv[3];
	if($argc > 4 && is_numeric($argv[4])){
		$includeImgs = $argv[4];
		if($argc > 5 && is_numeric($argv[5])){
			$redactLocalities = $argv[5];
		}
	}	
}


if($collStr){
	$dwcaManager = new DwcArchiverOccurrence();
	$dwcaManager->setIncludeDets($includeDets);
	$dwcaManager->setIncludeImgs($includeImgs);
	$dwcaManager->setRedactLocalities($redactLocalities);
	$dwcaManager->setServerDomain($serverDomain);
	$dwcaManager->setTargetPath($serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'collections/datasets/dwc/');
	$dwcaManager->setVerbose(0);
	$collArr = explode(',',$collStr);
	$dwcaManager->batchCreateDwca($collArr);
}
?>