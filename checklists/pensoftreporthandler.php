<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
require_once($SERVER_ROOT.'/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$clid = $_REQUEST['clid'];
//$rType = $_REQUEST['rtype'];

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);
$vManager->setCollectionVariables();


$spreadsheet = new Spreadsheet();
$taxaSheet = $spreadsheet->getActiveSheet()->setTitle('Taxa');
$penArr = $vManager->getPensoftArr();
$headerArr = $penArr['header'];
$taxaArr = $penArr['taxa'];
//print_r($taxaArr); exit;

$letters = range('A', 'Z');
//Output header
$columnCnt = 0;
foreach($headerArr as $headerValue){
	$colLet = $letters[$columnCnt%26].'1';
	if($columnCnt > 26) $colLet = $colLet.$letters[floor($columnCnt/26)];
	$taxaSheet->setCellValue($colLet, $headerValue);
	$columnCnt++;
}

//Output data
$rowCnt = 2;
foreach($taxaArr as $tid => $recArr){
	$columnCnt = 0;
	foreach($headerArr as $headerKey => $v){
		$colLet = $letters[$columnCnt%26].$rowCnt;
		if($columnCnt > 26) $colLet = $colLet.$letters[floor($columnCnt/26)];
		$cellValue = (isset($recArr[$headerKey])?$recArr[$headerKey]:'');
		$taxaSheet->setCellValue($colLet, $cellValue);
		$columnCnt++;
	}
	$rowCnt++;
}

//Create Materials worksheet
$materialsSheet = $spreadsheet->createSheet(1)->setTitle('Materials');




// Create a new worksheet called "My Data"
//$myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'My Data');
// Attach the "My Data" worksheet as the first worksheet in the Spreadsheet object
//$spreadsheet->addSheet($myWorkSheet, 0);


//Create ExternalLinks worksheet
$spreadsheet->createSheet(2)->setTitle('ExternalLinks');


$writer = new Xlsx($spreadsheet);
$writer->save($TEMP_DIR_ROOT.'/downloads/'.$vManager->getExportFileName().'.xlsx');

?>