<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverPublisher.php');
	
$collStr = $argv[1];
$serverDomain = $argv[2];
$includeDets = 1;
$includeImgs = 1;
$includeAttributes = 1;
$redactLocalities = 1;
if($argc > 3 && is_numeric($argv[3])){
	$includeDets = $argv[3];
	if($argc > 4 && is_numeric($argv[4])){
		$includeImgs = $argv[4];
		if($argc > 5 && is_numeric($argv[5])){
			$redactLocalities = $argv[5];
			if($argc > 6 && is_numeric($argv[6])){
				$includeAttributes = $argv[6];
			}
		}
	}	
}


if($collStr){
	$dwcaManager = new DwcArchiverPublisher();
	$dwcaManager->setIncludeDets($includeDets);
	$dwcaManager->setIncludeImgs($includeImgs);
	$dwcaManager->setIncludeAttributes($includeAttributes);
	$dwcaManager->setRedactLocalities($redactLocalities);
	$dwcaManager->setServerDomain($serverDomain);
	$dwcaManager->setTargetPath($serverRoot.(substr($SERVER_ROOT,-1)=='/'?'':'/').'content/dwca/');
	$dwcaManager->setVerboseMode(0);
	$collArr = explode(',',$collStr);
	$dwcaManager->batchCreateDwca($collArr);
}
?>