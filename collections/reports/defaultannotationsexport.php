<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceLabel.php');
require_once $serverRoot.'/classes/PhpWord/Autoloader.php';
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$ses_id = session_id();

$labelManager = new OccurrenceLabel();
use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\Settings;
Autoloader::register();
Settings::loadConfig();

$collid = $_POST["collid"];
$lHeader = $_POST['lheading'];
$lFooter = $_POST['lfooter'];
$detIdArr = $_POST['detid'];
$speciesAuthors = ((array_key_exists('speciesauthors',$_POST) && $_POST['speciesauthors'])?1:0);
$clearQueue = ((array_key_exists('clearqueue',$_POST) && $_POST['clearqueue'])?1:0);
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';
$rowsPerPage = 3;

$exportEngine = '';
$exportExtension = '';
if($action == 'Export to DOCX'){
	$exportEngine = 'Word2007';
	$exportExtension = 'docx';
}

$sectionStyle = array();
if($rowsPerPage==1){
	$lineWidth = 740;
	$sectionStyle = array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0);
}
if($rowsPerPage==2){
	$lineWidth = 350;
	$sectionStyle = array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0,'colsNum'=>2,'colsSpace'=>180,'breakType'=>'continuous');
}
if($rowsPerPage==3){
	$lineWidth = 220;
	$sectionStyle = array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0,'colsNum'=>3,'colsSpace'=>180,'breakType'=>'continuous');
}

$labelManager->setCollid($collid);

$isEditor = 0;
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collid,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}

if($isEditor && $action){
	$labelArr = $labelManager->getAnnoArray($_POST['detid'], $speciesAuthors);
	if($clearQueue){
		$labelManager->clearAnnoQueue($_POST['detid']);
	}
}

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->addParagraphStyle('firstLine', array('lineHeight'=>.1,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('lastLine', array('spaceAfter'=>50,'lineHeight'=>.1));
$phpWord->addFontStyle('dividerFont', array('size'=>1));
$phpWord->addParagraphStyle('header', array('align'=>'center','lineHeight'=>1.0,'spaceAfter'=>40,'keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('footer', array('align'=>'center','lineHeight'=>1.0,'spaceBefore'=>40,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('headerfooterFont', array('bold'=>true,'size'=>9,'name'=>'Arial'));
$phpWord->addParagraphStyle('other', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>30,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('scientificname', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('scientificnameFont', array('bold'=>true,'italic'=>true,'size'=>10,'name'=>'Arial'));
$phpWord->addFontStyle('scientificnameinterFont', array('bold'=>true,'size'=>10,'name'=>'Arial'));
$phpWord->addFontStyle('scientificnameauthFont', array('size'=>10,'name'=>'Arial'));
$phpWord->addFontStyle('identifiedFont', array('size'=>8,'name'=>'Arial'));
$tableStyle = array('width'=>100,'borderColor'=>'000000','borderSize'=>2,'cellMargin'=>75);
$colRowStyle = array('cantSplit'=>true);
$phpWord->addTableStyle('defaultTable',$tableStyle,$colRowStyle);
$cellStyle = array('valign'=>'top');

$section = $phpWord->addSection($sectionStyle);

foreach($labelArr as $occid => $occArr){
	$headerStr = trim($lHeader);
	$footerStr = trim($lFooter);
	
	$dupCnt = $_POST['q-'.$occid];
	for($i = 0;$i < $dupCnt;$i++){
		$section->addText(htmlspecialchars(' '),'dividerFont','firstLine');
		$table = $section->addTable('defaultTable');
		$table->addRow();
		$cell = $table->addCell(5000,$cellStyle);
		if($headerStr){
			$textrun = $cell->addTextRun('header');
			$textrun->addText(htmlspecialchars($headerStr),'headerfooterFont');
		}
		$textrun = $cell->addTextRun('scientificname');
		if($occArr['identificationqualifier']) $textrun->addText(htmlspecialchars($occArr['identificationqualifier']).' ','scientificnameauthFont');
		$scinameStr = $occArr['sciname'];
		$parentAuthor = (array_key_exists('parentauthor',$occArr)?' '.$occArr['parentauthor']:'');
		if(strpos($scinameStr,' sp.') !== false){
			$scinameArr = explode(" sp. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('sp.','scientificnameinterFont');
		}
		elseif(strpos($scinameStr,'subsp.') !== false){
			$scinameArr = explode(" subsp. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('subsp. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'ssp.') !== false){
			$scinameArr = explode(" ssp. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('ssp. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'var.') !== false){
			$scinameArr = explode(" var. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('var. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'variety') !== false){
			$scinameArr = explode(" variety ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('var. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'Variety') !== false){
			$scinameArr = explode(" Variety ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('var. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'v.') !== false){
			$scinameArr = explode(" v. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('var. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,' f.') !== false){
			$scinameArr = explode(" f. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('f. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'cf.') !== false){
			$scinameArr = explode(" cf. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('cf. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		elseif(strpos($scinameStr,'aff.') !== false){
			$scinameArr = explode(" aff. ",$scinameStr);
			$textrun->addText(htmlspecialchars($scinameArr[0]).' ','scientificnameFont');
			if($parentAuthor) $textrun->addText(htmlspecialchars($parentAuthor).' ','scientificnameauthFont');
			$textrun->addText('aff. ','scientificnameinterFont');
			$textrun->addText(htmlspecialchars($scinameArr[1]).' ','scientificnameFont');
		}
		else{
			$textrun->addText(htmlspecialchars($scinameStr).' ','scientificnameFont');
		}
		$textrun->addText(htmlspecialchars($occArr['scientificnameauthorship']),'scientificnameauthFont');
		if($occArr['identificationremarks']){
			$textrun = $cell->addTextRun('other');
			$textrun->addText(htmlspecialchars($occArr['identificationremarks']).' ','identifiedFont');
		}
		if($occArr['identificationreferences']){
			$textrun = $cell->addTextRun('other');
			$textrun->addText(htmlspecialchars($occArr['identificationreferences']).' ','identifiedFont');
		}
		if($occArr['identifiedby'] || $occArr['dateidentified']){
			$textrun = $cell->addTextRun('other');
			if($occArr['identifiedby']){
				$textrun->addText('Determiner: '.htmlspecialchars($occArr['identifiedby']),'identifiedFont');
				if($occArr['dateidentified']){
					$textrun->addTextBreak(1);
				}
			}
			if($occArr['dateidentified']){
				$textrun->addText('Date: '.htmlspecialchars($occArr['dateidentified']).' ','identifiedFont');
			}
		}
		if($footerStr){
			$textrun = $cell->addTextRun('footer');
			$textrun->addText(htmlspecialchars($footerStr),'headerfooterFont');
		}
		$section->addText(htmlspecialchars(' '),'dividerFont','lastLine');
	}
}

$targetFile = $serverRoot.'/temp/report/'.$paramsArr['un'].'_'.date('Ymd').'_annotations_'.$ses_id.'.'.$exportExtension;
$phpWord->save($targetFile, $exportEngine);

header('Content-Description: File Transfer');
header('Content-type: application/force-download');
header('Content-Disposition: attachment; filename='.basename($targetFile));
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($targetFile));
readfile($targetFile);
unlink($targetFile);
?>