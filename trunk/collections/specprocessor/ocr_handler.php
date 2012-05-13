<?php
date_default_timezone_set('America/Phoenix');
$charset = 'ISO-8859-1';//"UTF-8";
$tempDirRoot = $SERVER['PHP_SELF'];
$tesseractPath = 'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe';
include_once('dbconnection.php');
include_once('SpecProcessorOcr.php');

$ocrManager = new SpecProcessorOcr();

$collArr = array(28);
//$collArr = array(2,22,28,31,32);
$ocrManager->batchOcrUnprocessed($collArr,1);

?>