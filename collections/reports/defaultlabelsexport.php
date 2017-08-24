<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceLabel.php');
@include_once("Image/Barcode.php");
@include_once("Image/Barcode2.php");
require_once $serverRoot.'/classes/PhpWord/Autoloader.php';
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$ses_id = session_id();

if(class_exists('Image_Barcode2')){
	$bcObj = new Image_Barcode2;
}
elseif(class_exists('Image_Barcode')){
	$bcObj = new Image_Barcode;
}

$labelManager = new OccurrenceLabel();
use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\Settings;
Autoloader::register();
Settings::loadConfig();

$collid = $_POST["collid"];
$hPrefix = $_POST['lhprefix'];
$hMid = $_POST['lhmid'];
$hSuffix = $_POST['lhsuffix'];
$lFooter = $_POST['lfooter'];
$occIdArr = $_POST['occid'];
$rowsPerPage = $_POST['rpp'];
$speciesAuthors = ((array_key_exists('speciesauthors',$_POST) && $_POST['speciesauthors'])?1:0);
$showcatalognumbers = ((array_key_exists('catalognumbers',$_POST) && $_POST['catalognumbers'])?1:0);
$useBarcode = array_key_exists('bc',$_POST)?$_POST['bc']:0;
$useSymbBarcode = array_key_exists('symbbc',$_POST)?$_POST['symbbc']:0;
$barcodeOnly = array_key_exists('bconly',$_POST)?$_POST['bconly']:0;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

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
	$sectionStyle = array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0,'colsNum'=>2,'colsSpace'=>690,'breakType'=>'continuous');
}
if($rowsPerPage==3){
	$lineWidth = 220;
	$sectionStyle = array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0,'colsNum'=>3,'colsSpace'=>690,'breakType'=>'continuous');
}

$labelManager->setCollid($collid);

$isEditor = 0;
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collid,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}

if($isEditor && $action){
	$labelArr = $labelManager->getLabelArray($_POST['occid'], $speciesAuthors);
}

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->addParagraphStyle('firstLine', array('lineHeight'=>.1,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('lastLine', array('spaceAfter'=>300,'lineHeight'=>.1));
$phpWord->addFontStyle('dividerFont', array('size'=>1));
$phpWord->addParagraphStyle('barcodeonly', array('align'=>'center','lineHeight'=>1.0,'spaceAfter'=>300,'keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('lheader', array('align'=>'center','lineHeight'=>1.0,'spaceAfter'=>150,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('lheaderFont', array('bold'=>true,'size'=>14,'name'=>'Arial'));
$phpWord->addParagraphStyle('family', array('align'=>'right','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('familyFont', array('size'=>10,'name'=>'Arial'));
$phpWord->addParagraphStyle('scientificname', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('scientificnameFont', array('bold'=>true,'italic'=>true,'size'=>11,'name'=>'Arial'));
$phpWord->addFontStyle('scientificnameinterFont', array('bold'=>true,'size'=>11,'name'=>'Arial'));
$phpWord->addFontStyle('scientificnameauthFont', array('size'=>11,'name'=>'Arial'));
$phpWord->addParagraphStyle('identified', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'indent'=>0.3125,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('identifiedFont', array('size'=>10,'name'=>'Arial'));
$phpWord->addParagraphStyle('loc1', array('spaceBefore'=>150,'lineHeight'=>1.0,'spaceAfter'=>0,'align'=>'left','keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('countrystateFont', array('size'=>11,'bold'=>true,'name'=>'Arial'));
$phpWord->addFontStyle('localityFont', array('size'=>11,'name'=>'Arial'));
$phpWord->addParagraphStyle('other', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('otherFont', array('size'=>10,'name'=>'Arial'));
$phpWord->addFontStyle('associatedtaxaFont', array('size'=>10,'italic'=>true,'name'=>'Arial'));
$phpWord->addParagraphStyle('collector', array('spaceBefore'=>150,'lineHeight'=>1.0,'spaceAfter'=>0,'align'=>'left','keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('cnbarcode', array('align'=>'center','lineHeight'=>1.0,'spaceAfter'=>0,'spaceBefore'=>150,'keepNext'=>true,'keepLines'=>true));
$phpWord->addParagraphStyle('lfooter', array('align'=>'center','lineHeight'=>1.0,'spaceAfter'=>0,'spaceBefore'=>150,'keepNext'=>true,'keepLines'=>true));
$phpWord->addFontStyle('lfooterFont', array('bold'=>true,'size'=>12,'name'=>'Arial'));

$section = $phpWord->addSection($sectionStyle);

foreach($labelArr as $occid => $occArr){
	if($barcodeOnly){
		if($occArr['catalognumber']){
			$textrun = $section->addTextRun('cnbarcode');
			$bc = $bcObj->draw(strtoupper($occArr['catalognumber']),"Code39","png",false,40);
			imagepng($bc,$serverRoot.'/temp/report/'.$ses_id.$occArr['catalognumber'].'.png');
			$textrun->addImage($serverRoot.'/temp/report/'.$ses_id.$occArr['catalognumber'].'.png', array('align'=>'center'));
			imagedestroy($bc);
		}
	}
	else{
		$midStr = '';
		if($hMid == 1){
			$midStr = $occArr['country'];
		}
		elseif($hMid == 2){
			$midStr = $occArr['stateprovince'];
		}
		elseif($hMid == 3){
			$midStr = $occArr['county'];
		}
		elseif($hMid == 4){
			$midStr = $occArr['family'];
		}
		$headerStr = '';
		if($hPrefix || $midStr || $hSuffix){
			$headerStrArr = array();
			$headerStrArr[] = trim($hPrefix);
			$headerStrArr[] = trim($midStr);
			$headerStrArr[] = trim($hSuffix);
			$headerStr = implode(" ",$headerStrArr);
		}
		$dupCnt = $_POST['q-'.$occid];
		for($i = 0;$i < $dupCnt;$i++){
			$section->addText(htmlspecialchars(' '),'dividerFont','firstLine');
			if($headerStr){
				$section->addText(htmlspecialchars($headerStr),'lheaderFont','lheader');
			}
			if($hMid != 4) $section->addText(htmlspecialchars($occArr['family']),'familyFont','family');
			$textrun = $section->addTextRun('scientificname');
			if($occArr['identificationqualifier']) $textrun->addText(htmlspecialchars($occArr['identificationqualifier']).' ','scientificnameauthFont');
			$scinameStr = $occArr['scientificname'];
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
			if($occArr['identifiedby']){
				$textrun = $section->addTextRun('identified');
				$textrun->addText('Det by: '.htmlspecialchars($occArr['identifiedby']).' ','identifiedFont');
				$textrun->addText(htmlspecialchars($occArr['dateidentified']),'identifiedFont');
				if($occArr['identificationreferences'] || $occArr['identificationremarks'] || $occArr['taxonremarks']){
					$section->addText(htmlspecialchars($occArr['identificationreferences']),'identifiedFont','identified');
					$section->addText(htmlspecialchars($occArr['identificationremarks']),'identifiedFont','identified');
					$section->addText(htmlspecialchars($occArr['taxonremarks']),'identifiedFont','identified');
				}
			}
			$textrun = $section->addTextRun('loc1');
			$textrun->addText(htmlspecialchars($occArr['country'].($occArr['country']?', ':'')),'countrystateFont');
			$textrun->addText(htmlspecialchars($occArr['stateprovince'].($occArr['stateprovince']?', ':'')),'countrystateFont');
			$countyStr = trim($occArr['county']);
			if($countyStr){
				if(!stripos($occArr['county'],' County') && !stripos($occArr['county'],' Parish')) $countyStr .= ' County';
				$countyStr .= ', ';
			}
			$textrun->addText(htmlspecialchars($countyStr),'countrystateFont');
			$textrun->addText(htmlspecialchars($occArr['municipality'].($occArr['municipality']?', ':'')),'localityFont');
			$locStr = trim($occArr['locality']);
			if(substr($locStr,-1) != '.'){$locStr .= '.';}
			$textrun->addText(htmlspecialchars($locStr),'localityFont');
			if($occArr['decimallatitude'] || $occArr['verbatimcoordinates']){
				$textrun = $section->addTextRun('other');
				if($occArr['verbatimcoordinates']){
					$textrun->addText(htmlspecialchars($occArr['verbatimcoordinates']),'otherFont');
				}
				else{
					$textrun->addText(htmlspecialchars($occArr['decimallatitude']).($occArr['decimallatitude']>0?'N, ':'S, '),'otherFont');
					$textrun->addText(htmlspecialchars($occArr['decimallongitude']).($occArr['decimallongitude']>0?'E':'W'),'otherFont');
				}
				if($occArr['coordinateuncertaintyinmeters']) $textrun->addText(htmlspecialchars(' +-'.$occArr['coordinateuncertaintyinmeters'].' meters'),'otherFont');
				if($occArr['geodeticdatum']) $textrun->addText(htmlspecialchars(' '.$occArr['geodeticdatum']),'otherFont');
			}
			if($occArr['minimumelevationinmeters']){
				$textrun = $section->addTextRun('other');
				$textrun->addText(htmlspecialchars('Elev: '.$occArr['minimumelevationinmeters'].($occArr['maximumelevationinmeters']?' - '.$occArr['maximumelevationinmeters']:'').'m. '),'otherFont');
				if($occArr['verbatimelevation']) $textrun->addText(htmlspecialchars(' ('.$occArr['verbatimelevation'].')'),'otherFont');
			}
			if($occArr['habitat']){
				$textrun = $section->addTextRun('other');
				$habStr = trim($occArr['habitat']);
				if(substr($habStr,-1) != '.'){$habStr .= '.';}
				$textrun->addText(htmlspecialchars($habStr),'otherFont');
			}
			if($occArr['substrate']){
				$textrun = $section->addTextRun('other');
				$substrateStr = trim($occArr['substrate']);
				if(substr($substrateStr,-1) != '.'){$substrateStr .= '.';}
				$textrun->addText(htmlspecialchars($substrateStr),'otherFont');
			}
			if($occArr['verbatimattributes'] || $occArr['establishmentmeans']){
				$textrun = $section->addTextRun('other');
				$textrun->addText(htmlspecialchars($occArr['verbatimattributes']),'otherFont');
				if($occArr['verbatimattributes'] && $occArr['establishmentmeans']) $textrun->addText(htmlspecialchars('; '),'otherFont');
				$textrun->addText(htmlspecialchars($occArr['establishmentmeans']),'otherFont');
			}
			if($occArr['associatedtaxa']){
				$textrun = $section->addTextRun('other');
				$textrun->addText(htmlspecialchars('Associated species: '),'otherFont');
				$textrun->addText(htmlspecialchars($occArr['associatedtaxa']),'associatedtaxaFont');
			}
			if($occArr['occurrenceremarks']){
				$section->addText(htmlspecialchars($occArr['occurrenceremarks']),'otherFont','other');
			}
			if($occArr['typestatus']){
				$section->addText(htmlspecialchars($occArr['typestatus']),'otherFont','other');
			}
			$textrun = $section->addTextRun('collector');
			$textrun->addText(htmlspecialchars($occArr['recordedby']),'otherFont');
			$textrun->addText(htmlspecialchars(' '.$occArr['recordnumber']),'otherFont');
			$section->addText(htmlspecialchars($occArr['eventdate']),'otherFont','other');
			if($occArr['associatedcollectors']){
				$section->addText(htmlspecialchars('With: '.$occArr['associatedcollectors']),'otherFont','identified');
			}
			if($useBarcode && $occArr['catalognumber']){
				$textrun = $section->addTextRun('cnbarcode');
				$bc = $bcObj->draw(strtoupper($occArr['catalognumber']),"Code39","png",false,40);
				imagepng($bc,$serverRoot.'/temp/report/'.$ses_id.$occArr['catalognumber'].'.png');
				$textrun->addImage($serverRoot.'/temp/report/'.$ses_id.$occArr['catalognumber'].'.png', array('align'=>'center','marginTop'=>0.15625));
				if($occArr['othercatalognumbers']){
					$textrun->addTextBreak(1);
					$textrun->addText(htmlspecialchars($occArr['othercatalognumbers']),'otherFont');
				}
				imagedestroy($bc);
			}
			elseif($showcatalognumbers){
				$textrun = $section->addTextRun('cnbarcode');
				if($occArr['catalognumber']){
					$textrun->addText(htmlspecialchars($occArr['catalognumber']),'otherFont');
				}
				if($occArr['othercatalognumbers']){
					if($occArr['catalognumber']){
						$textrun->addTextBreak(1);
					}
					$textrun->addText(htmlspecialchars($occArr['othercatalognumbers']),'otherFont');
				}
			}
			if($lFooter){
				$section->addText(htmlspecialchars($lFooter),'lfooterFont','lfooter');
			}
			if($useSymbBarcode){
				$textrun = $section->addTextRun('cnbarcode');
				$textrun->addTextBreak(1);
				$textrun->addLine(array('weight'=>2,'width'=>$lineWidth,'height'=>0,'dash'=>'dash'));
				$textrun->addTextBreak(1);
				$bc = $bcObj->draw(strtoupper($occid),"Code39","png",false,40);
				imagepng($bc,$serverRoot.'/temp/report/'.$ses_id.$occid.'.png');
				$textrun->addImage($serverRoot.'/temp/report/'.$ses_id.$occid.'.png', array('align'=>'center','marginTop'=>0.104166667));
				if($occArr['catalognumber']){
					$textrun->addTextBreak(1);
					$textrun->addText(htmlspecialchars($occArr['catalognumber']),'otherFont');
				}
				imagedestroy($bc);
			}
			$section->addText(htmlspecialchars(' '),'dividerFont','lastLine');
		}
	}
}

$targetFile = $serverRoot.'/temp/report/'.$paramsArr['un'].'_'.date('Ymd').'_labels_'.$ses_id.'.'.$exportExtension;
$phpWord->save($targetFile, $exportEngine);

header('Content-Description: File Transfer');
header('Content-type: application/force-download');
header('Content-Disposition: attachment; filename='.basename($targetFile));
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($targetFile));
readfile($targetFile);
$files = glob($serverRoot.'/temp/report/*');
foreach($files as $file){
	if(is_file($file)){
		if(strpos($file,$ses_id) !== false){
			unlink($file);
		}
	}
}
?>