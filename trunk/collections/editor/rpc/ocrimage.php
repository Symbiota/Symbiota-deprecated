<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorOcr.php');
	
	$imgUrl = $_REQUEST['url'];
	$ocrManager = new SpecProcessorOcr();
	$rawStr = $ocrManager->ocrImage($imgUrl);

	echo $rawStr;
?>