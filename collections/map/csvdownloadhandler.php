<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownload.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
include_once($serverRoot.'/classes/DwcArchiverCore.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 

$schema = array_key_exists("schema",$_POST)?$_POST["schema"]:"symbiota"; 
$cSet = array_key_exists("cset",$_POST)?$_POST["cset"]:'';
$zip = (array_key_exists('zip',$_POST)?$_POST['zip']:0);
$format = $_POST['format'];
$type = array_key_exists("typecsv",$_POST)?$_POST["typecsv"]:'';
$selections = array_key_exists('selectionscsv',$_POST)?$_POST['selectionscsv']:0;
$stArrJson = array_key_exists("starrcsv",$_POST)?$_POST["starrcsv"]:'';
$stArr = Array();
if($stArrJson){
	$stArr = json_decode($stArrJson, true);
}

$extended = (array_key_exists('extended',$_POST)?$_POST['extended']:0);

$redactLocalities = 1;
$rareReaderArr = array();
if($IS_ADMIN || array_key_exists("CollAdmin", $userRights)){
	$redactLocalities = 0;
}
elseif(array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
	$redactLocalities = 0;
}
else{
	if(array_key_exists('CollEditor', $userRights)){
		$rareReaderArr = $userRights['CollEditor'];
	}
	if(array_key_exists('RareSppReader', $userRights)){
		$rareReaderArr = array_unique(array_merge($rareReaderArr,$userRights['RareSppReader']));
	}
}
	
//Is an occurrence download 
$dwcaHandler = new DwcArchiverCore();
$dwcaHandler->setCharSetOut($cSet);
$dwcaHandler->setSchemaType($schema);
$dwcaHandler->setExtended($extended);
$dwcaHandler->setDelimiter($format);
$dwcaHandler->setVerboseMode(0);
$dwcaHandler->setRedactLocalities($redactLocalities);
if($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);

$mapManager = new MapInterfaceManager();
if($type=='selection' || $type=='dsselectionquery'){
	$selections = preg_match('#\[(.*?)\]#', $selections, $match);
	$selections = $match[1];
	$mapWhere = 'WHERE o.occid IN('.$selections.') ';
}
if($type=='fullquery'){
	$mapManager->setSearchTermsArr($stArr);
	$mapWhere = $mapManager->getSqlWhere();
}
$dwcaHandler->setCustomWhereSql($mapWhere);
$outputFile = null;
if($zip){
	//Ouput file is a zip file
	$includeIdent = (array_key_exists('identifications',$_POST)?1:0);
	$dwcaHandler->setIncludeDets($includeIdent);
	$includeImages = (array_key_exists('images',$_POST)?1:0);
	$dwcaHandler->setIncludeImgs($includeImages);
	$includeAttributes = (array_key_exists('attr',$_POST)?1:0);
	$dwcaHandler->setIncludeAttributes($includeAttributes);
	
	$outputFile = $dwcaHandler->createDwcArchive('webreq');
	
}
else{
	//Output file is a flat occurrence file (not a zip file)
	$outputFile = $dwcaHandler->getOccurrenceFile();
}
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
	header('Content-Type: text/csv; charset='.$charset);
}
else{
	header('Content-Type: text/html; charset='.$charset);
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
?>