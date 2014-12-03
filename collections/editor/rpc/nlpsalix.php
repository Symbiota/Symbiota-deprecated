<?php
error_reporting(0);
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlpUtilities.php');
include_once($serverRoot.'/classes/SpecProcNlpSalix.php');
header("Content-Type: text/html; charset=UTF-8");

$rawOcr = $_REQUEST['rawocr'];
$debug = 0;

$nlpManager = new SpecProcNlpSalix();
$dwcArr = array();
if($rawOcr){
	//Get rid of curly (smart) quotes
	$search = array("’", "‘", "`", "”", "“"); 
	$replace = array("'", "'", "'", '"', '"'); 
	$rawOcr= str_replace($search, $replace, $rawOcr);
	//Get rid of UTF-8 curly smart quotes and dashes 
	$badwordchars=array("\xe2\x80\x98", // left single quote
						"\xe2\x80\x99", // right single quote
						"\xe2\x80\x9c", // left double quote
						"\xe2\x80\x9d", // right double quote
						"\xe2\x80\x94", // em dash
						"\xe2\x80\xa6" // elipses
	);
	$fixedwordchars=array("'", "'", '"', '"', '-', '...');
	$rawOcr = str_replace($badwordchars, $fixedwordchars, $rawOcr);

	$dwcArr = $nlpManager->parse($rawOcr);
	if($debug){
		$fh = fopen($serverRoot.'/temp/ocrdebug.txt','w');
		fwrite($fh,$rawOcr."\n\n\n");
		foreach($dwcArr as $k => $v){
			fwrite($fh,$k.': '.$v."\n");
		}
		fclose($fh);
	}
	$dwcArr = SpecProcNlpUtilities::cleanDwcArr($dwcArr);
}
echo json_encode($dwcArr);
?>