<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorOcr.php');
	
	$imgUrl = $_REQUEST['url'];
	$x = array_key_exists('x',$_REQUEST)?$_REQUEST['x']:0;
	$y = array_key_exists('y',$_REQUEST)?$_REQUEST['y']:0;
	$w = array_key_exists('w',$_REQUEST)?$_REQUEST['w']:1;
	$h = array_key_exists('h',$_REQUEST)?$_REQUEST['h']:1;
	
	$rawStr = '';
	$ocrManager = new SpecProcessorOcr();
	if($x || $y || $w < 1 || $h < 1){
		$rawStr = $ocrManager->ocrImageByUrl($imgUrl,0,0,0,$x,$y,$w,$h);
	}
	else{
		$rawStr = $ocrManager->ocrImageByUrl($imgUrl);
	}

	echo $rawStr;
?>