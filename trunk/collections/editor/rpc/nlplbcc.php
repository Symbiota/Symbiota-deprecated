<?php
include_once('../../../config/symbini.php');
//include_once($serverRoot.'/classes/SpecProcNlpParserLBCCCommon.php');
include_once($serverRoot.'/classes/SpecProcNlpParserLBCC.php');
header("Content-Type: text/html; charset=UTF-8");

$rawOcr = (array_key_exists('rawocr',$_REQUEST)?$_REQUEST['rawocr']:'');
$prlid = (array_key_exists('prlid',$_REQUEST)?$_REQUEST['prlid']:'');

//$nlpManager = new SpecProcNlpParserLBCCCommon();
$nlpManager = new SpecProcNlpParserLBCC();
$dcObj = '';
if($prlid){
	$dcObj = $nlpManager->parseRawOcrRecord($prlid);
}
elseif($rawOcr){
	$dcObj = $nlpManager->parseTextBlock($rawOcr);
}

echo $dcObj;
?>