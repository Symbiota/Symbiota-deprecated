<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorOcrManager.php');
	
	$imgUrl = $con->real_escape_string($_REQUEST['url']);
	$ocrManager = SpecProcessorOcrManager();
	$rawStr = $ocrManager->ocrImage($imgUrl);

	echo $rawStr;

?>