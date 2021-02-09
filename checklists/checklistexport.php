<?php

include_once('../config/symbini.php');

function exportChecklistToCSV($checklist) {
	
	$taxa = array();
	$header = array(
		"Family",
		"ScientificName",
		"ScientificNameAuthorship",
		"CommonName",
		#"TaxonId"
	);
	foreach ($checklist['taxa'] as $taxon) {
		$tmp = array(
			$taxon['family'],
			$taxon['sciname'],
			(isset($taxon['author'])? $taxon['author'] :''),
			sizeof($taxon['vernacular']['names']) ? $taxon['vernacular']['names'][0] : $taxon['vernacular']['basename'],
			#$taxa['tid'],
		);
		$taxa[] = $tmp;
	}
	sort($taxa);
	array_unshift($taxa,$header);
	#return $return;
	
	
	$title = str_replace(" ","_",$checklist['title']) . "_" . date("Ymd");
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header("Content-Disposition: attachment; filename={$title}.csv");
	$out = fopen('php://output', 'w');
	foreach ($taxa as $taxon) {
		fputcsv($out, $taxon, ",","\"");
	}
	fclose($out);

	
}



function exportChecklistToWord($checklist) {
	global $SERVER_ROOT;

	/*
	using composer because need newer version (PHP7-compatible?); 
	this required a path fix to /vendor/phpoffice/phpword/bootstrap.php
	*/
	$bootstrap = $SERVER_ROOT.'/vendor/phpoffice/phpword/bootstrap.php';
	#var_dump($bootstrap);exit;
	require_once $bootstrap;

	$exportEngine = '';
	$exportExtension = '';
	$exportEngine = 'Word2007';
	$exportExtension = 'docx';
	
	$title = str_replace('&quot;','"',$checklist['title']);
	$title = str_replace('&apos;',"'",$title);
	
	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0;
	/* unused so far - ap
	$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0;  
	$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
	$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
	$showAlphaTaxa = array_key_exists("showalphataxa",$_REQUEST)?$_REQUEST["showalphataxa"]:0; 
	$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
	$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
	*/


	/*
		$textrun->addTextBreak(1) does not seem to work;
		instead, do addTextRun() - ap
	*/
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	
	$properties = $phpWord->getDocInfo();
	$properties->setTitle($title);
	
	$phpWord->setDefaultFontSize(12);
	$phpWord->setDefaultFontName('Arial');
	$phpWord->addParagraphStyle('defaultPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>50,'keepNext'=>true));
	$phpWord->addFontStyle('titleFont', array('bold'=>true,'size'=>20,'name'=>'Arial'));
	$phpWord->addFontStyle('topicFont', array('bold'=>true,'size'=>12,'name'=>'Arial'));
	$phpWord->addFontStyle('textFont', array('size'=>12,'name'=>'Arial'));
	$phpWord->addParagraphStyle('linePara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>75,'keepNext'=>true));
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

	$textrun = $section->addTextRun('defaultPara');
	$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/checklists/checklist.php?cl='.$checklist['clid']."&proj=".$checklist['pid']."&dynclid=".$checklist['dynclid'],htmlspecialchars($title));#,'titleFont'	
	#$textrun->addTextBreak(1);
	if($checklist['clid']){
		if(isset($checklist['type']) && $checklist['type'] == 'rarespp'){
			$locality = str_replace('&quot;','"',$checklist["locality"]);
			$locality = str_replace('&apos;',"'",$locality);
			$textrun = $section->addTextRun('linePara');
			$textrun->addText(htmlspecialchars('Sensitive species checklist for: '),'topicFont');
			$textrun->addText(htmlspecialchars($locality),'textFont');
			#$textrun->addTextBreak(1);
		}
		if (isset($checklist['authors']) && !empty($checklist['authors'])) {
			$authors = str_replace('&quot;','"',$checklist["authors"]);
			$authors = str_replace('&apos;',"'",$authors);
			$textrun = $section->addTextRun('linePara');
			$textrun->addText(htmlspecialchars('Authors: '),'topicFont');
			$textrun->addText(htmlspecialchars($authors),'textFont');
			#$textrun->addTextBreak(1);
		}
		/*#unused so far - would need to be added to ExploreManager.php and api - ap
		if(isset($checklist["publication"])){
			$publication = str_replace('&quot;','"',preg_replace('/\s+/',' ',$checklist["publication"]));
			$publication = str_replace('&apos;',"'",$publication);
			$textrun->addText(htmlspecialchars('Publication: '),'topicFont');
			$textrun->addText(htmlspecialchars($publication),'textFont');
			$textrun->addTextBreak(1);
		}*/
	}
	if((isset($checklist["locality"]) || ($checklist['clid'] && (isset($checklist["lat"]) || isset($checklist["abstract"]))) || isset($checklist["notes"]))){
		$locStr = str_replace('&quot;','"',$checklist["locality"]);
		$locStr = str_replace('&apos;',"'",$locStr);
		if($checklist['clid']  && $checklist["lat"]) $locStr .= " (".$checklist["lat"].", ".$checklist["lng"].")";
		if($locStr){
			$textrun = $section->addTextRun('linePara');
			$textrun->addText(htmlspecialchars('Locality: '),'topicFont');
			$textrun->addText(htmlspecialchars($locStr),'textFont');
			#$textrun->addTextBreak(1);
		}
		if($checklist['clid'] && isset($checklist["abstract"]) && !empty($checklist['abstract'])){
			$abstract = str_replace('&quot;','"',preg_replace('/\s+/',' ',strip_tags($checklist["abstract"])));
			$abstract = str_replace('&apos;',"'",$abstract);
			$textrun = $section->addTextRun('linePara');
			$textrun->addText(htmlspecialchars('Abstract: '),'topicFont');
			$textrun->addText(htmlspecialchars($abstract),'textFont');
			#$textrun->addTextBreak(1);
		}
		if($checklist['clid'] && isset($checklist["notes"]) && !empty($checklist['notes'])){
			$notes = str_replace('&quot;','"',preg_replace('/\s+/',' ',$checklist["notes"]));
			$notes = str_replace('&apos;',"'",$notes);
			$textrun = $section->addTextRun('linePara');
			$textrun->addText(htmlspecialchars('Notes: '),'topicFont');
			$textrun->addText(htmlspecialchars($notes),'textFont');
			#$textrun->addTextBreak(1);
		}
	}

	$textrun = $section->addTextRun('linePara');
	$textrun->addLine(array('weight'=>1,'width'=>670,'height'=>0));
	$textrun = $section->addTextRun('linePara');
	$textrun->addText(htmlspecialchars('Families: '),'topicFont');
	$textrun->addText(htmlspecialchars($checklist['totals']['families']),'textFont');
	#$textrun->addTextBreak();
	$textrun = $section->addTextRun('linePara');
	$textrun->addText(htmlspecialchars('Genera: '),'topicFont');
	$textrun->addText(htmlspecialchars($checklist['totals']['genera']),'textFont');
	$textrun = $section->addTextRun('linePara');
	$textrun->addText(htmlspecialchars('Species: '),'topicFont');
	$textrun->addText(htmlspecialchars($checklist['totals']['species'].' (species rank)'),'textFont');
	$textrun = $section->addTextRun('linePara');
	$textrun->addText(htmlspecialchars('Total Taxa: '),'topicFont');
	$textrun->addText(htmlspecialchars($checklist['totals']['taxa'].' (including subsp. and var.)'),'textFont');
	#$textrun->addTextBreak();
	$prevfam = '';
	/* unused so far - ap
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
				$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/taxa/index.php?taxauthid=1&taxon='.$tid.'&cl='.$clid,htmlspecialchars($sppArr['sciname']),'topicFont');
				$textrun->addTextBreak(1);
				if(array_key_exists('vern',$sppArr)){
					$vern = str_replace('&quot;','"',$sppArr["vern"]);
					$vern = str_replace('&apos;',"'",$vern);
					$textrun->addText(htmlspecialchars($vern),'topicFont');
					$textrun->addTextBreak(1);
				}
				if(!$showAlphaTaxa){
					if($family != $prevfam){
						$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/taxa/index.php?taxauthid=1&taxon='.$family.'&cl='.$clid,htmlspecialchars('['.$family.']'),'textFont');
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
	else{*/
	
		foreach($checklist['taxa'] as $sppArr){
			#if(!$showAlphaTaxa){
				$family = strtoupper($sppArr['family']);
				if($family != $prevfam){
					$textrun = $section->addTextRun('familyPara');
					$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/taxa/index.php?taxauthid=1&taxon='.$family.'&cl='.$checklist['clid'],htmlspecialchars($family),'familyFont');
					$prevfam = $family;
				}
			#}
			$textrun = $section->addTextRun('scinamePara');
			$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/taxa/index.php?taxauthid=1&taxon='.$sppArr['tid'].'&cl='.$checklist['clid'],htmlspecialchars($sppArr['sciname']),'scientificnameFont');
			/*if(array_key_exists("author",$sppArr)){ 
				$sciAuthor = str_replace('&quot;','"',$sppArr["author"]);
				$sciAuthor = str_replace('&apos;',"'",$sciAuthor);
				$textrun->addText(htmlspecialchars(' '.$sciAuthor),'textFont');
			}*/
			if ($showCommon) {
				if(array_key_exists('vernacular',$sppArr)){
					$vernacular = str_replace('&quot;','"',$sppArr["vernacular"]['names'][0]);
					$vernacular = str_replace('&apos;',"'",$vernacular);
					$textrun->addText(htmlspecialchars(' - '.$vernacular),'topicFont');
				}
			}
			/* unused so far - ap
			if($showVouchers){
				$voucherArr = $clManager->getVoucherArr();
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
						$textrun->addLink('http://'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/collections/individual/index.php?occid='.$occid,htmlspecialchars($voucStr),'textFont');
						$i++;
					}
				}
			}
			*/
		} #end foreach taxa
	#} # endif $showImages

	$fileName = str_replace(' ','_',$checklist['title']);
	$fileName = str_replace('/','_',$fileName);
	$targetFile = $serverRoot.'/temp/report/'.$fileName.'.'.$exportExtension;
	var_dump($phpWord);
/*
	$phpWord->save($targetFile, $exportEngine);

	header('Content-Description: File Transfer');
	header('Content-type: application/force-download');
	header('Content-Disposition: attachment; filename='.basename($targetFile));
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.filesize($targetFile));
	readfile($targetFile);
	unlink($targetFile);
	*/
}
?>