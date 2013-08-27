<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlpParserLBCC.php');
header("Content-Type: text/html; charset=UTF-8");
	
$rawOcr = $_REQUEST['rawocr'];
	
$nlpManager = new SpecProcNlpParserLBCC();
$dcObj = $nlpManager->parseTextBlock($rawOcr);

echo $dcObj;
?>