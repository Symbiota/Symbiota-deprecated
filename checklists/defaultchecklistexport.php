<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
require_once $SERVER_ROOT.'/classes/PhpWord/Autoloader.php';
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 240); //240 seconds = 4 minutes

$ses_id = session_id();

$clManager = new ChecklistManager();
use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\Settings;
Autoloader::register();
Settings::loadConfig();

$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0; 
$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:1;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0;
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
$showAlphaTaxa = array_key_exists("showalphataxa",$_REQUEST)?$_REQUEST["showalphataxa"]:0; 
$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;

$exportEngine = '';
$exportExtension = '';
$exportEngine = 'Word2007';
$exportExtension = 'docx';

if($clValue){
	$statusStr = $clManager->setClValue($clValue);
}
elseif($dynClid){
	$clManager->setDynClid($dynClid);
}
$clArray = Array();
if($clValue || $dynClid){
	$clArray = $clManager->getClMetaData();
}
$showDetails = 0;
/*if($clValue && $clArray["defaultSettings"]){
	$defaultArr = json_decode($clArray["defaultSettings"], true);
	$showDetails = $defaultArr["ddetails"];
	if($action != "Rebuild List"){
		$showCommon = $defaultArr["dcommon"];
		$showImages = $defaultArr["dimages"]; 
		$showVouchers = $defaultArr["dvouchers"];
		$showAuthors = $defaultArr["dauthors"];
		$showAlphaTaxa = $defaultArr["dalpha"];
	}
}*/
if($pid) $clManager->setProj($pid);
elseif(array_key_exists("proj",$_REQUEST)) $pid = $clManager->setProj($_REQUEST['proj']);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
if($searchCommon){
	$showCommon = 1;
	$clManager->setSearchCommon();
}
if($searchSynonyms) $clManager->setSearchSynonyms();
if($showAuthors) $clManager->setShowAuthors();
if($showCommon) $clManager->setShowCommon();
if($showImages) $clManager->setShowImages();
if($showVouchers) $clManager->setShowVouchers();
if($showAlphaTaxa) $clManager->setShowAlphaTaxa();
$clid = $clManager->getClid();
$pid = $clManager->getPid();

$isEditor = false;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = true;
}
$taxaArray = Array();
if($clValue || $dynClid){
	$taxaArray = $clManager->getTaxaList($pageNumber,0);
}

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->addParagraphStyle('defaultPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('titleFont', array('bold'=>true,'size'=>20,'name'=>'Arial'));
$phpWord->addFontStyle('topicFont', array('bold'=>true,'size'=>12,'name'=>'Arial'));
$phpWord->addFontStyle('textFont', array('size'=>12,'name'=>'Arial'));
$phpWord->addParagraphStyle('linePara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addParagraphStyle('familyPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>225,'spaceAfter'=>75,'keepNext'=>true));
$phpWord->addFontStyle('familyFont', array('bold'=>true,'size'=>16,'name'=>'Arial'));
$phpWord->addParagraphStyle('scinamePara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.3125,'spaceBefore'=>0,'spaceAfter'=>45,'keepNext'=>true));
$phpWord->addFontStyle('scientificnameFont', array('bold'=>true,'italic'=>true,'size'=>12,'name'=>'Arial'));
$phpWord->addParagraphStyle('notesvouchersPara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>45));
$phpWord->addParagraphStyle('imagePara', array('align'=>'center','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
$tableStyle = array('width'=>100);
$colRowStyle = array('cantSplit'=>true,'exactHeight'=>3750);
$phpWord->addTableStyle('imageTable',$tableStyle,$colRowStyle);
$imageCellStyle = array('valign'=>'center','width'=>2475,'borderSize'=>15,'borderColor'=>'808080');
$blankCellStyle = array('valign'=>'center','width'=>2475,'borderSize'=>15,'borderColor'=>'000000');

$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>1080,'headerHeight'=>0,'footerHeight'=>0));
$title = str_replace('&quot;','"',$clManager->getClName());
$title = str_replace('&apos;',"'",$title);
$textrun = $section->addTextRun('defaultPara');
$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$clientRoot.'/checklists/checklist.php?cl='.$clValue."&proj=".$pid."&dynclid=".$dynClid,htmlspecialchars($title),'titleFont');
$textrun->addTextBreak(1);
if($clValue){
	if($clArray['type'] == 'rarespp'){
		$locality = str_replace('&quot;','"',$clArray["locality"]);
		$locality = str_replace('&apos;',"'",$locality);
		$textrun->addText(htmlspecialchars('Sensitive species checklist for: '),'topicFont');
		$textrun->addText(htmlspecialchars($locality),'textFont');
		$textrun->addTextBreak(1);
	}
	$authors = str_replace('&quot;','"',$clArray["authors"]);
	$authors = str_replace('&apos;',"'",$authors);
	$textrun->addText(htmlspecialchars('Authors: '),'topicFont');
	$textrun->addText(htmlspecialchars($authors),'textFont');
	$textrun->addTextBreak(1);
	if($clArray["publication"]){
		$publication = str_replace('&quot;','"',preg_replace('/\s+/',' ',$clArray["publication"]));
		$publication = str_replace('&apos;',"'",$publication);
		$textrun->addText(htmlspecialchars('Publication: '),'topicFont');
		$textrun->addText(htmlspecialchars($publication),'textFont');
		$textrun->addTextBreak(1);
	}
}
if(($clArray["locality"] || ($clValue && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"])){
	$locStr = str_replace('&quot;','"',$clArray["locality"]);
	$locStr = str_replace('&apos;',"'",$locStr);
	if($clValue && $clArray["latcentroid"]) $locStr .= " (".$clArray["latcentroid"].", ".$clArray["longcentroid"].")";
	if($locStr){
		$textrun->addText(htmlspecialchars('Locality: '),'topicFont');
		$textrun->addText(htmlspecialchars($locStr),'textFont');
		$textrun->addTextBreak(1);
	}
	if($clValue && $clArray["abstract"]){
		$abstract = str_replace('&quot;','"',preg_replace('/\s+/',' ',$clArray["abstract"]));
		$abstract = str_replace('&apos;',"'",$abstract);
		$textrun->addText(htmlspecialchars('Abstract: '),'topicFont');
		$textrun->addText(htmlspecialchars($abstract),'textFont');
		$textrun->addTextBreak(1);
	}
	if($clValue && $clArray["notes"]){
		$notes = str_replace('&quot;','"',preg_replace('/\s+/',' ',$clArray["notes"]));
		$notes = str_replace('&apos;',"'",$notes);
		$textrun->addText(htmlspecialchars('Notes: '),'topicFont');
		$textrun->addText(htmlspecialchars($notes),'textFont');
		$textrun->addTextBreak(1);
	}
}
$textrun = $section->addTextRun('linePara');
$textrun->addLine(array('weight'=>1,'width'=>670,'height'=>0));
$textrun = $section->addTextRun('defaultPara');
$textrun->addText(htmlspecialchars('Families: '),'topicFont');
$textrun->addText(htmlspecialchars($clManager->getFamilyCount()),'textFont');
$textrun->addTextBreak(1);
$textrun->addText(htmlspecialchars('Genera: '),'topicFont');
$textrun->addText(htmlspecialchars($clManager->getGenusCount()),'textFont');
$textrun->addTextBreak(1);
$textrun->addText(htmlspecialchars('Species: '),'topicFont');
$textrun->addText(htmlspecialchars($clManager->getSpeciesCount().' (species rank)'),'textFont');
$textrun->addTextBreak(1);
$textrun->addText(htmlspecialchars('Total Taxa: '),'topicFont');
$textrun->addText(htmlspecialchars($clManager->getTaxaCount().' (including subsp. and var.)'),'textFont');
$textrun->addTextBreak(1);
$prevfam = '';
if($showImages){
	$imageCnt = 0;
	$table = $section->addTable('imageTable');
	foreach($taxaArray as $tid => $sppArr){
		$imageCnt++;
		$family = $sppArr['family'];
		$tu = (array_key_exists('tnurl',$sppArr)?$sppArr['tnurl']:'');
		$u = (array_key_exists('url',$sppArr)?$sppArr['url']:'');
		$imgSrc = ($tu?$tu:$u);
		if($imageCnt%4 == 1) $table->addRow();
		if($imgSrc){
			$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgSrc,0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgSrc;
			$cell = $table->addCell(null,$imageCellStyle);
			$textrun = $cell->addTextRun('imagePara');
			$textrun->addImage($imgSrc,array('width'=>160,'height'=>160));
			$textrun->addTextBreak(1);
			$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$clientRoot.'/taxa/index.php?taxauthid=1&taxon='.$tid.'&cl='.$clid,htmlspecialchars($sppArr['sciname']),'topicFont');
			$textrun->addTextBreak(1);
			if(array_key_exists('vern',$sppArr)){
				$vern = str_replace('&quot;','"',$sppArr["vern"]);
				$vern = str_replace('&apos;',"'",$vern);
				$textrun->addText(htmlspecialchars($vern),'topicFont');
				$textrun->addTextBreak(1);
			}
			if(!$showAlphaTaxa){
				if($family != $prevfam){
					$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$clientRoot.'/taxa/index.php?taxauthid=1&taxon='.$family.'&cl='.$clid,htmlspecialchars('['.$family.']'),'textFont');
					$prevfam = $family;
				}
			}
		}
		else{
			$cell = $table->addCell(null,$blankCellStyle);
			$textrun = $cell->addTextRun('imagePara');
			$textrun->addText(htmlspecialchars('Image'),'topicFont');
			$textrun->addTextBreak(1);
			$textrun->addText(htmlspecialchars('not yet'),'topicFont');
			$textrun->addTextBreak(1);
			$textrun->addText(htmlspecialchars('available'),'topicFont');
		}
	}
}
else{
	$voucherArr = $clManager->getVoucherArr();
	foreach($taxaArray as $tid => $sppArr){
		if(!$showAlphaTaxa){
			$family = $sppArr['family'];
			if($family != $prevfam){
				$textrun = $section->addTextRun('familyPara');
				$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$clientRoot.'/taxa/index.php?taxauthid=1&taxon='.$family.'&cl='.$clid,htmlspecialchars($family),'familyFont');
				$prevfam = $family;
			}
		}
		$textrun = $section->addTextRun('scinamePara');
		$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$clientRoot.'/taxa/index.php?taxauthid=1&taxon='.$tid.'&cl='.$clid,htmlspecialchars($sppArr['sciname']),'scientificnameFont');
		if(array_key_exists("author",$sppArr)){ 
			$sciAuthor = str_replace('&quot;','"',$sppArr["author"]);
			$sciAuthor = str_replace('&apos;',"'",$sciAuthor);
			$textrun->addText(htmlspecialchars(' '.$sciAuthor),'textFont');
		}
		if(array_key_exists('vern',$sppArr)){
			$vern = str_replace('&quot;','"',$sppArr["vern"]);
			$vern = str_replace('&apos;',"'",$vern);
			$textrun->addText(htmlspecialchars(' - '.$vern),'topicFont');
		}
		if($showVouchers){
			if(array_key_exists('notes',$sppArr) || array_key_exists($tid,$voucherArr)){
				$textrun = $section->addTextRun('notesvouchersPara');
			}
			if(array_key_exists('notes',$sppArr)){
				$noteStr = str_replace('&quot;','"',trim($sppArr['notes']));
				$noteStr = str_replace('&apos;',"'",$noteStr);
				$textrun->addText(htmlspecialchars($noteStr.($noteStr && array_key_exists($tid,$voucherArr)?'; ':'')),'textFont');
			}
			if(array_key_exists($tid,$voucherArr)){
				$i = 0;
				foreach($voucherArr[$tid] as $occid => $collName){
					if($i > 0) $textrun->addText(htmlspecialchars(', '),'textFont');
					$voucStr = str_replace('&quot;','"',$collName);
					$voucStr = str_replace('&apos;',"'",$voucStr);
					$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$clientRoot.'/collections/individual/index.php?occid='.$occid,htmlspecialchars($voucStr),'textFont');
					$i++;
				}
			}
		}
	}
}

$fileName = str_replace(' ','_',$clManager->getClName());
$fileName = str_replace('/','_',$fileName);
$targetFile = $SERVER_ROOT.'/temp/report/'.$fileName.'.'.$exportExtension;
$phpWord->save($targetFile, $exportEngine);

header('Content-Description: File Transfer');
header('Content-type: application/force-download');
header('Content-Disposition: attachment; filename='.basename($targetFile));
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($targetFile));
readfile($targetFile);
unlink($targetFile);
?>