<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorOcr.php');
	
	$imgUrl = $con->real_escape_string($_REQUEST['url']);
	$ocrManager = SpecProcessorOcr();
	$rawStr = $ocrManager->ocrImage($imgUrl);

	echo $rawStr;

?>