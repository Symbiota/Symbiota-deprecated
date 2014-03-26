<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlp.php');
header("Content-Type: text/html; charset=UTF-8");

$rawOcr = (array_key_exists('rawocr',$_REQUEST)?$_REQUEST['rawocr']:'');
$prlid = (array_key_exists('prlid',$_REQUEST)?$_REQUEST['prlid']:'');
$rl = $_REQUEST['iurl'];
$collid = $_REQUEST['collid'];
$catNo = $_REQUEST['catalognumber'];

$nlpManager = new SpecProcNlp();
$dcObj = '';
if($prlid){
	$dcObj = $nlpManager->parseRawOcrRecord($prlid);
}
elseif($rawOcr){
	$dcObj = $nlpManager->parseTextBlock($rawOcr, $collid, $rl, $catNo);
}

echo $dcObj;
?>