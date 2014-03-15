<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlpParserLBCCCommon.php');
header("Content-Type: text/html; charset=UTF-8");

$rawOcr = $_REQUEST['rawocr'];

$nlpManager = new SpecProcNlpParserLBCCCommon();
$dcObj = $nlpManager->parseTextBlock($rawOcr);

echo $dcObj;
?>