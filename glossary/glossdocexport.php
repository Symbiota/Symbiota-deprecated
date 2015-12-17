<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
require_once $serverRoot.'/classes/PhpWord/Autoloader.php';
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

if(session_id() == ''){
    session_start();
}
$ses_id = session_id();

$glosManager = new GlossaryManager();
use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\Settings;
Autoloader::register();
Settings::loadConfig();

$language = array_key_exists('searchlanguage',$_POST)?$_POST['searchlanguage']:'';
$taxon = array_key_exists('searchtaxa',$_POST)?$_POST['searchtaxa']:'';
$exportType = array_key_exists('exporttype',$_POST)?$_POST['exporttype']:'';
$translations = array_key_exists('language',$_POST)?$_POST['language']:array();
$definitions = array_key_exists('definitions',$_POST)?$_POST['definitions']:'';
$images = array_key_exists('images',$_POST)?$_POST['images']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$exportEngine = '';
$exportExtension = '';
$exportEngine = 'Word2007';
$exportExtension = 'docx';
$fileName = '';
$transExportArr = array();
$sciName = '';
$translationArr = array();

if($exportType == 'translation'){
	$transExportArr = $glosManager->getTransExportArr($language,$taxon,$translations,$definitions);
	if($transExportArr){
		$sciName = $transExportArr['glossSciName'];
		$translationArr = $transExportArr['glossTranslations'];
		unset($transExportArr['glossSciName']);
		unset($transExportArr['glossTranslations']);
		ksort($transExportArr);
		$fileName = $sciName.'_TranslationTable';
	}
}
if($exportType == 'singlelanguage'){
	$singleExportArr = $glosManager->getSingleExportArr($language,$taxon,$images);
	if($singleExportArr){
		$sciName = $singleExportArr['glossSciName'];
		unset($singleExportArr['glossSciName']);
		ksort($singleExportArr);
		$fileName = $sciName.'_SingleLanguage';
	}
}

$phpWord = new \PhpOffice\PhpWord\PhpWord();

$phpWord->addParagraphStyle('titlePara', array('align'=>'center','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('titleFont', array('bold'=>true,'size'=>16,'name'=>'Arial'));
$phpWord->addParagraphStyle('transTermPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('transTermTopicNodefFont', array('bold'=>true,'size'=>15,'name'=>'Arial'));
$phpWord->addFontStyle('transTermTopicDefFont', array('bold'=>true,'size'=>14,'name'=>'Arial'));
$phpWord->addParagraphStyle('transDefPara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addParagraphStyle('transDefList', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('transMainTermNodefFont', array('bold'=>false,'size'=>12,'name'=>'Arial','color'=>'21304B'));
$phpWord->addFontStyle('transTransTermNodefFont', array('bold'=>false,'size'=>12,'name'=>'Arial','color'=>'000000'));
$phpWord->addFontStyle('transMainTermDefFont', array('bold'=>true,'size'=>12,'name'=>'Arial','color'=>'21304B'));
$phpWord->addFontStyle('transTransTermDefFont', array('bold'=>true,'size'=>12,'name'=>'Arial','color'=>'000000'));
$phpWord->addFontStyle('transDefTextFont', array('bold'=>false,'size'=>12,'name'=>'Arial','color'=>'000000'));
$tableStyle = array('width'=>100,'cellMargin'=>40);
$colRowStyle = array('cantSplit'=>true,'exactHeight'=>180);
$phpWord->addTableStyle('exportTable',$tableStyle,$colRowStyle);
$nodefCellStyle = array('valign'=>'center','width'=>2520,'borderSize'=>0,'borderColor'=>'ffffff');
$imageCellStyle = array('valign'=>'top','width'=>2520,'borderSize'=>0,'borderColor'=>'ffffff');

$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>1080,'headerHeight'=>0,'footerHeight'=>0));
if($exportType == 'translation' && $transExportArr){
	$textrun = $section->addTextRun('titlePara');
	$textrun->addText(htmlspecialchars('Translation Table for '.$sciName),'titleFont');
	$textrun->addTextBreak(1);
	if($definitions == 'nodef'){
		$textrun = $section->addTextRun('transTermPara');
		$textrun->addText(htmlspecialchars($language),'transTermTopicNodefFont');
		foreach($translations as $trans){
			$textrun->addText(htmlspecialchars('-'.$trans),'transTermTopicNodefFont');
		}
		$textrun->addText(htmlspecialchars(' Terms'),'transTermTopicNodefFont');
		$textrun->addTextBreak(1);
		$table = $section->addTable('exportTable');
		foreach($transExportArr as $transEx => $transExArr){
			$termGrpId = $transExArr['glossgrpid'];
			$table->addRow();
			$cell = $table->addCell(2520,$nodefCellStyle);
			$textrun = $cell->addTextRun('transTermPara');
			$textrun->addText(htmlspecialchars($transExArr['term']),'transMainTermNodefFont');
			foreach($translations as $trans){
				$cell = $table->addCell(2520,$nodefCellStyle);
				$textrun = $cell->addTextRun('transTermPara');
				if(array_key_exists($termGrpId,$translationArr) && array_key_exists($trans,$translationArr[$termGrpId])){
					$textrun->addText(htmlspecialchars($translationArr[$termGrpId][$trans]['term']),'transTransTermNodefFont');
				}
				else{
					$textrun->addText(htmlspecialchars(' '),'transTransTermNodefFont');
				}
			}
		}
	}
	else{
		$textrun = $section->addTextRun('transTermPara');
		$textrun->addText(htmlspecialchars($language),'transTermTopicDefFont');
		foreach($translations as $trans){
			$textrun->addText(htmlspecialchars('-'.$trans),'transTermTopicDefFont');
		}
		$textrun->addText(htmlspecialchars(' Terms'),'transTermTopicDefFont');
		$textrun = $section->addTextRun('transDefPara');
		$textrun->addText(htmlspecialchars($language),'transTermTopicDefFont');
		if($definitions == 'alldef'){
			foreach($translations as $trans){
				$textrun->addText(htmlspecialchars(' - '.$trans),'transTermTopicDefFont');
			}
		}
		$textrun->addText(htmlspecialchars(' Definition'),'transTermTopicDefFont');
		$textrun->addTextBreak(1);
		foreach($transExportArr as $transEx => $transExArr){
			$termGrpId = $transExArr['glossgrpid'];
			$textrun = $section->addTextRun('transTermPara');
			$textrun->addText(htmlspecialchars($transExArr['term']),'transMainTermDefFont');
			foreach($translations as $trans){
				if(array_key_exists($termGrpId,$translationArr) && array_key_exists($trans,$translationArr[$termGrpId])){
					$textrun->addText(htmlspecialchars(' - '.$translationArr[$termGrpId][$trans]['term']),'transTransTermDefFont');
				}
				else{
					$textrun->addText(htmlspecialchars(' -  '),'transTransTermDefFont');
				}
			}
			if($definitions == 'onedef'){
				if($transExArr['definition']){
					$textrun = $section->addTextRun('transDefPara');
					$textrun->addText(htmlspecialchars($transExArr['definition']),'transDefTextFont');
					$textrun->addTextBreak(1);
				}
			}
			if($definitions == 'alldef'){
				if($transExArr['definition']){
					$listItemRun = $section->addListItemRun(0,null,'transDefList');
					$listItemRun->addText(htmlspecialchars($transExArr['definition']),'transDefTextFont');
				}
				foreach($translations as $trans){
					$listItemRun = $section->addListItemRun(0,null,'transDefList');
					if(array_key_exists($termGrpId,$translationArr) && array_key_exists($trans,$translationArr[$termGrpId])){
						$listItemRun->addText(htmlspecialchars($translationArr[$termGrpId][$trans]['definition']),'transDefTextFont');
					}
					else{
						$listItemRun->addText(htmlspecialchars(' '),'transDefTextFont');
					}
				}
				$section->addTextBreak(1);
			}
		}
	}
}
if($exportType == 'singlelanguage' && $singleExportArr){
	$textrun = $section->addTextRun('titlePara');
	$textrun->addText(htmlspecialchars('Single Language Glossary for '.$sciName),'titleFont');
	$textrun->addTextBreak(1);
	foreach($singleExportArr as $singleEx => $singleExArr){
		$textrun = $section->addTextRun('transTermPara');
		$textrun->addText(htmlspecialchars($singleExArr['term']),'transMainTermDefFont');
		if($singleExArr['definition']){
			$textrun = $section->addTextRun('transDefPara');
			$textrun->addText(htmlspecialchars($singleExArr['definition']),'transDefTextFont');
		}
		if($images && array_key_exists('images',$singleExArr)){
			$imageArr = $singleExArr['images'];
			$table = $section->addTable('exportTable');
			foreach($imageArr as $img => $imgArr){
				$imgSrc = $imgArr["url"];
				if(substr($imgSrc,0,1)=="/"){
					if(array_key_exists("imageDomain",$GLOBALS) && $GLOBALS["imageDomain"]){
						$imgSrc = $GLOBALS["imageDomain"].$imgSrc;
					}
					else{
						$imgSrc = 'http://'.$_SERVER['HTTP_HOST'].$imgSrc;
					}
				}
				$table->addRow();
				$cell = $table->addCell(4125,$imageCellStyle);
				$textrun = $cell->addTextRun('transDefPara');
				if(getimagesize($imgSrc)){
					$textrun->addImage($imgSrc,array('width'=>250));
				}
				$cell = $table->addCell(5625,$imageCellStyle);
				$textrun = $cell->addTextRun('transTermPara');
				if($imgArr["structures"]){
					$textrun->addText(htmlspecialchars('Structures: '),'transTransTermDefFont');
					$textrun->addText(htmlspecialchars($imgArr["structures"]),'transDefTextFont');
					$textrun->addTextBreak(2);
				}
				if($imgArr["notes"]){
					$textrun->addText(htmlspecialchars('Notes: '),'transTransTermDefFont');
					$textrun->addText(htmlspecialchars($imgArr["notes"]),'transDefTextFont');
				}
			}
		}
		$section->addTextBreak(1);
	}
}

$targetFile = $serverRoot.'/temp/report/'.$fileName.'.'.$exportExtension;
$phpWord->save($targetFile, $exportEngine);

header('Content-Description: File Transfer');
header('Content-type: application/force-download');
header('Content-Disposition: attachment; filename='.basename($targetFile));
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($targetFile));
readfile($targetFile);
unlink($targetFile);
?>