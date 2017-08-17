<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorOcr.php');
	
	$imgid = $_REQUEST['imgid'];
	$x = array_key_exists('x',$_REQUEST)?$_REQUEST['x']:0;
	$y = array_key_exists('y',$_REQUEST)?$_REQUEST['y']:0;
	$w = array_key_exists('w',$_REQUEST)?$_REQUEST['w']:1;
	$h = array_key_exists('h',$_REQUEST)?$_REQUEST['h']:1;
	$ocrBest = array_key_exists('ocrbest',$_REQUEST)?$_REQUEST['ocrbest']:0;
	
	$rawStr = '';
	$ocrManager = new SpecProcessorOcr();
	$ocrManager->setCropX($x);
	$ocrManager->setCropY($y);
	$ocrManager->setCropW($w);
	$ocrManager->setCropH($h);
	$rawStr = $ocrManager->ocrImageById($imgid,$ocrBest);

	echo $rawStr;
?>