<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlpUtilities.php');

$targetParser = 'common';
if(strpos($_SERVER['SERVER_NAME'],'bryophyte') !== false){
	include_once($serverRoot.'/classes/SpecProcNlpLbccBryophyte.php');
	$targetParser = 'bryophyte';
}
elseif(strpos($_SERVER['SERVER_NAME'],'lichen') !== false){
	include_once($serverRoot.'/classes/SpecProcNlpLbccLichen.php');
	$targetParser = 'lichen';
}
else{
	include_once($serverRoot.'/classes/SpecProcNlpLbcc.php');
}

header("Content-Type: text/html; charset=UTF-8");

$rawStr = $_REQUEST['rawocr'];
$collid = $_REQUEST['collid'];
$catNum = $_REQUEST['catnum'];

$dwcArr = array();
if($rawStr) {
	$handler;
	if($targetParser == 'bryophyte'){
		$handler = new SpecProcNlpLbccBryophyte();
		
	}
	elseif($targetParser == 'lichen'){
		$handler = new SpecProcNlpLbccLichen();
	}
	else{
		$handler = new SpecProcNlpLbcc();
	}
	if($handler) {
		$handler->setCollId($collid);
		$handler->setCatalogNumber($catNum);
		$dwcArr = $handler->parse($rawStr);
		$dwcArr = SpecProcNlpUtilities::cleanDwcArr($dwcArr);
	}
}

echo json_encode($dwcArr);
?>