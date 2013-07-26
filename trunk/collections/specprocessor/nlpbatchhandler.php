<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlp.php');

$collTarget = array_key_exists("colltarget",$_REQUEST)?$_REQUEST["colltarget"]:42;
//$processingStatus = array_key_exists("processingstatus",$_REQUEST)?$_REQUEST["processingstatus"]:'';
$ocrSource = array_key_exists("ocrsource",$_REQUEST)?$_REQUEST["ocrsource"]:'abbyy';
$printMode = array_key_exists("printmode",$_REQUEST)?$_REQUEST["printmode"]:0;
$parserTarget = array_key_exists("parsertarget",$_REQUEST)?$_REQUEST["parsertarget"]:'lbcc';
$parserTarget = strtolower($parserTarget);

$nlpHandler = null;
if($parserTarget == 'lbcc'){
	$nlpHandler = new SpecProcNlpParserLBCC($printMode);
}
elseif($parserTarget == 'salix'){
	$nlpHandler = new SpecProcNlpParserSALIX($printMode);
}
else{
	$nlpHandler = new SpecProcNlpParser($printMode);
}

$nlpHandler->setPrintMode($printMode);
$nlpHandler->setLogErrors(true);
$nlpHandler->batchProcess($collTarget,$ocrSource);



?>