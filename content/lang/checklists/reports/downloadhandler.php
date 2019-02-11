<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
ini_set('max_execution_time', 300);

$schema = array_key_exists("schema",$_POST)?$_POST["schema"]:"symbiota";
$cSet = array_key_exists("cset",$_POST)?$_POST["cset"]:'';
$zip = (array_key_exists('zip',$_POST)?$_POST['zip']:0);
$format = $_POST['format'];
$clid = $_POST['clid'];

$dwcaHandler = new DwcArchiverCore();

//Set locality redaction variables
$redactLocalities = 1;
$rareReaderArr = array();
if($IS_ADMIN || array_key_exists("CollAdmin", $USER_RIGHTS)){
	$redactLocalities = 0;
}
elseif(array_key_exists("RareSppAdmin", $USER_RIGHTS) || array_key_exists("RareSppReadAll", $USER_RIGHTS)){
	$redactLocalities = 0;
}
else{
	if(array_key_exists('CollEditor', $USER_RIGHTS)){
		$rareReaderArr = $USER_RIGHTS['CollEditor'];
	}
	if(array_key_exists('RareSppReader', $USER_RIGHTS)){
		$rareReaderArr = array_unique(array_merge($rareReaderArr,$USER_RIGHTS['RareSppReader']));
	}
}

$dwcaHandler->setVerboseMode(0);
$dwcaHandler->setSchemaType($schema);
$dwcaHandler->setCharSetOut($cSet);
$dwcaHandler->setDelimiter($format);
$dwcaHandler->setRedactLocalities($redactLocalities);
if($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);

$dwcaHandler->setCustomWhereSql('v.clid='.$clid);

$outputFile = null;
if($zip){
	//Ouput file is a zip file
	$includeIdent = (array_key_exists('identifications',$_POST)?1:0);
	$dwcaHandler->setIncludeDets($includeIdent);
	$includeImages = (array_key_exists('images',$_POST)?1:0);
	$dwcaHandler->setIncludeImgs($includeImages);
	$includeAttributes = (array_key_exists('attributes',$_POST)?1:0);
	$dwcaHandler->setIncludeAttributes($includeAttributes);

	$outputFile = $dwcaHandler->createDwcArchive('specVouchers');

}
else{
	//Output file is a flat occurrence file (not a zip file)
	$outputFile = $dwcaHandler->getOccurrenceFile();
}
if($outputFile){
	//ob_start();
	$contentDesc = '';
	if($schema == 'dwc'){
		$contentDesc = 'Darwin Core ';
	}
	else{
		$contentDesc = 'Symbiota ';
	}
	$contentDesc .= 'Occurrence ';
	if($zip){
		$contentDesc .= 'Archive ';
	}
	$contentDesc .= 'File';
	header('Content-Description: '.$contentDesc);

	if($zip){
		header('Content-Type: application/zip');
	}
	elseif($format == 'csv'){
		header('Content-Type: text/csv; charset='.$CHARSET);
	}
	else{
		header('Content-Type: text/html; charset='.$CHARSET);
	}

	header('Content-Disposition: attachment; filename='.basename($outputFile));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($outputFile));
	ob_clean();
	flush();
	//od_end_clean();
	readfile($outputFile);
	unlink($outputFile);
}
else{
	header("Content-type: text/plain");
	header("Content-Disposition: attachment; filename=NoData.txt");
	echo 'The query failed to return records. Please modify query criteria and try again.';
}
?>