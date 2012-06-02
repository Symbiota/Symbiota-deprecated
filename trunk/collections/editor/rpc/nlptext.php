<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecProcessorNlp.php');
	
	$rawStr = $_REQUEST['rawstr'];
	
	$nlpManager = new SpecProcessorNlp();
	$dcArr = $nlpManager->parseTextBlock($rawStr);

	echo json_encode($dcArr);
?>