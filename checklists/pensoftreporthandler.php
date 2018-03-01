<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
require $SERVER_ROOT.'/classes/PhpSpreadsheet/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$clid = $_REQUEST['clid'];
$rType = $_REQUEST['rtype'];

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);
$vManager->setCollectionVariables();


$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$clArr = $vManager->getPensoftChecklistArr();

$letters = range('A', 'Z');
foreach($clArr as $rowCnt => $recArr){
	foreach($recArr as $cellCnt => $cellValue){
		$sheet->setCellValue($letters[$cellCnt].$rowCnt, $cellValue);
	}
}
$writer = new Xlsx($spreadsheet);
$writer->save($TEMP_DIR_ROOT.'/downloads/'.$vManager->getExportFileName());

?>