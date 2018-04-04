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
$sheet = $spreadsheet->getActiveSheet();
$penArr = $vManager->getPensoftArr();
$headerArr = $penArr['header'];
$taxaArr = $penArr['taxa'];

$letters = range('A', 'Z');
foreach($clArr as $tid => $recArr){
	foreach($recArr as $cellCnt => $cellValue){
		echo $letters[$cellCnt].$rowCnt.' - '.$cellValue.'<br/>';
		$sheet->setCellValue($letters[$cellCnt].$rowCnt, $cellValue);
	}
}
$writer = new Xlsx($spreadsheet);
$writer->save($TEMP_DIR_ROOT.'/downloads/'.$vManager->getExportFileName());

?>