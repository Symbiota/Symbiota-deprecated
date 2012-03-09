<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorOcr.php');
	
	$imgUrl = $_REQUEST['url'];
	$imgX1 = array_key_exists('imgx1',$_REQUEST)?$_REQUEST['imgx1']:0;
	$imgX2 = array_key_exists('imgx2',$_REQUEST)?$_REQUEST['imgx2']:0;
	$imgY1 = array_key_exists('imgy1',$_REQUEST)?$_REQUEST['imgy1']:0;
	$imgY2 = array_key_exists('imgy2',$_REQUEST)?$_REQUEST['imgy2']:0;
	
	$rawStr = '';
	$ocrManager = new SpecProcessorOcr();
	if($imgX1 || $imgX2 < 1 || $imgY1 || $imgY2 < 1){
		$rawStr = $ocrManager->ocrImage($imgUrl);
	}
	else{
		$rawStr = $ocrManager->ocrImage($imgUrl);
	}

	echo $rawStr;
?>