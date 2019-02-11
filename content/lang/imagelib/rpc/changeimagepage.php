<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$taxon = array_key_exists("taxon",$_REQUEST)?trim($_REQUEST["taxon"]):"";
$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$stArrJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';
$view = array_key_exists("view",$_REQUEST)?$_REQUEST["view"]:'';
$stArr = Array();
$imageArr = Array();
$taxaList = Array();
if($stArrJson){
	$stArr = json_decode($stArrJson, true);
}

$imgLibManager = new ImageLibraryManager();
$imgLibManager->setSearchTermsArr($stArr);

$recordListHtml = '';
if($view == 'thumb'){
	
	$imgLibManager->setTaxon($taxon);
	$imgLibManager->setSqlWhere();
	$imageArr = $imgLibManager->getImageArr($pageNumber,$cntPerPage);
	$recordCnt = $imgLibManager->getRecordCnt();
	
	$lastPage = (int) ($recordCnt / $cntPerPage) + 1;
	$startPage = ($pageNumber > 4?$pageNumber - 4:1);
	if($lastPage > $startPage){
		$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
		$onclick = 'changeImagePage("","thumb",starr,';
		$hrefPrefix = "<a href='#' onclick='".$onclick;
		$pageBar = '<div style="float:left" >';
		if($startPage > 1){
			$pageBar .= "<span class='pagination' style='margin-right:5px;'>".$hrefPrefix."1); return false;'>First</a></span>";
			$pageBar .= "<span class='pagination' style='margin-right:5px;'>".$hrefPrefix.(($pageNumber - 10) < 1 ?1:$pageNumber - 10)."); return false;'>&lt;&lt;</a></span>";
		}
		for($x = $startPage; $x <= $endPage; $x++){
			if($pageNumber != $x){
				$pageBar .= "<span class='pagination' style='margin-right:3px;'>".$hrefPrefix.$x."); return false;'>".$x."</a></span>";
			}
			else{
				$pageBar .= "<span class='pagination' style='margin-right:3px;font-weight:bold;'>".$x."</span>";
			}
		}
		if(($lastPage - $startPage) >= 10){
			$pageBar .= "<span class='pagination' style='margin-left:5px;'>".$hrefPrefix.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10))."); return false;'>&gt;&gt;</a></span>";
			$pageBar .= "<span class='pagination' style='margin-left:5px;'>".$hrefPrefix.$lastPage."); return false;'>Last</a></span>";
		}
		$pageBar .= "</div><div style='float:right;margin-top:4px;margin-bottom:8px;'>";
		$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
		$endNum = $beginNum + $cntPerPage - 1;
		if($endNum > $recordCnt) $endNum = $recordCnt;
		$pageBar .= "Page ".$pageNumber.", records ".$beginNum."-".$endNum." of ".$recordCnt."</div>";
		$paginationStr = $pageBar;
		
		$recordListHtml .= '<div style="width:100%;">';
		$recordListHtml .= $paginationStr;
		$recordListHtml .= '</div>';
		$recordListHtml .= '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
	}
	$recordListHtml .= '<div style="width:98%;margin-left:auto;margin-right:auto;">';
	if($imageArr){
		foreach($imageArr as $imgArr){
			$imgId = $imgArr['imgid'];
			$imgUrl = $imgArr['url'];
			$imgTn = $imgArr['thumbnailurl'];
			if($imgTn){
				$imgUrl = $imgTn;
				if($imageDomain && substr($imgTn,0,1)=='/'){
					$imgUrl = $imageDomain.$imgTn;
				}
			}
			elseif($imageDomain && substr($imgUrl,0,1)=='/'){
				$imgUrl = $imageDomain.$imgUrl;
			}
			$recordListHtml .= '<div class="tndiv" style="margin-bottom:15px;margin-top:15px;">';
			$recordListHtml .= '<div class="tnimg">';
			if($imgArr['occid']){
				$recordListHtml .= '<a href="#" onclick="openIndPU('.$imgArr['occid'].');return false;">';
			}
			else{
				$recordListHtml .= '<a href="#" onclick="openImagePopup('.$imgId.');return false;">';
			}
			$recordListHtml .= '<img src="'.$imgUrl.'" />';
			$recordListHtml .= '</a>';
			$recordListHtml .= '</div>';
			$recordListHtml .= '<div>';
			$sciname = $imgArr['sciname'];
			if($sciname){
				if(strpos($imgArr['sciname'],' ')) $sciname = '<i>'.$sciname.'</i>';
				if($imgArr['tid']) $recordListHtml .= '<a href="#" onclick="openTaxonPopup('.$imgArr['tid'].');return false;" >';
				$recordListHtml .= $sciname;
				if($imgArr['tid']) $recordListHtml .= '</a>';
				$recordListHtml .= '<br />';
			}
			if($imgArr['catalognumber']){
				$recordListHtml .= '<a href="#" onclick="openIndPU('.$imgArr['occid'].');return false;">';
				$recordListHtml .= $imgArr['instcode'] . ": " . $imgArr['catalognumber'];
				$recordListHtml .= '</a>';
			}
			elseif($imgArr['lastname']){
				$pName = $imgArr['firstname'].' '.$imgArr['lastname'];
				if(strlen($pName) < 20) $recordListHtml .= $pName.'<br />';
				else $recordListHtml .= $imgArr['lastname'].'<br />';
			}
			//if($imgArr['stateprovince']) $recordListHtml .= $imgArr['stateprovince'] . "<br />";
			$recordListHtml .= '</div>';
			$recordListHtml .= '</div>';
		}
		$recordListHtml .= '</div>';
		if($lastPage > $startPage){
			$recordListHtml .= '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
			$recordListHtml .= '<div style="width:100%;">'.$paginationStr.'</div>';
		}
		$recordListHtml .= '<div style="clear:both;"></div>';
	}
	else{
		$recordListHtml .= '<div style="font-weight:bold;font-size:120%;">';
		$recordListHtml .= 'There were no images matching your search critera';
		$recordListHtml .= '</div>';
	}
}
elseif($view == 'famlist'){
	$imgLibManager->setSqlWhere();
	$taxaList = $imgLibManager->getFamilyList();
	
	$recordListHtml .= "<div style='margin-left:20px;margin-bottom:20px;font-weight:bold;'>Select a family to see genera list.</div>";
	foreach($taxaList as $value){
		$onChange = '"'.$value.'","genlist",starr,1';
		$famChange = '"'.$value.'"';
		$recordListHtml .= "<div style='margin-left:30px;'><a href='#' onclick='changeFamily(".$famChange.");changeImagePage(".$onChange."); return false;'>".strtoupper($value)."</a></div>";
	}
}
elseif($view == 'genlist'){
	$imgLibManager->setSqlWhere();
	$taxaList = $imgLibManager->getGenusList($taxon);
	
	$topOnChange = '"","famlist",starr,1';
	$recordListHtml .= "<div style='margin-left:20px;margin-bottom:10px;font-weight:bold;'><a href='#' onclick='changeImagePage(".$topOnChange."); return false;'>Return to family list</a></div>";
	$recordListHtml .= "<div style='margin-left:20px;margin-bottom:20px;font-weight:bold;'>Select a genus to see species list.</div>";
	foreach($taxaList as $value){
		$onChange = '"'.$value.'","splist",starr,1';
		$recordListHtml .= "<div style='margin-left:30px;'><a href='#' onclick='changeImagePage(".$onChange."); return false;'>".$value."</a></div>";
	}
}
elseif($view == 'splist'){
	$imgLibManager->setSqlWhere();
	$taxaList = $imgLibManager->getSpeciesList($taxon);
	
	$topOnChange = 'selectedFamily,"genlist",starr,1';
	$recordListHtml .= "<div style='margin-left:20px;margin-bottom:10px;font-weight:bold;'><a href='#' onclick='changeImagePage(".$topOnChange."); return false;'>Return to genera list</a></div>";
	$recordListHtml .= "<div style='margin-left:20px;margin-bottom:20px;font-weight:bold;'>Select a species to see images.</div>";
	foreach($taxaList as $key => $value){
		$onChange = '"'.$value.'","thumb",starr,1';
		$recordListHtml .= "<div style='margin-left:30px;'><a href='#' onclick='changeImagePage(".$onChange."); return false;'>".$value."</a></div>";
	}
}

//output the response
echo json_encode($recordListHtml);
?>