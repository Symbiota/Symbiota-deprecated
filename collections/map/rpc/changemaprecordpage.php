<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);

$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$stArrJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';
$selArrJson = array_key_exists("selected",$_REQUEST)?$_REQUEST["selected"]:'';
$stArr = Array();
$selections = Array();
$allSelected = false;
if($stArrJson){
	$stArr = json_decode($stArrJson, true);
}
if($selArrJson){
	$selections = json_decode($selArrJson, true);
}

$mapManager = new MapInterfaceManager();
$mapManager->setSearchTermsArr($stArr);
$mapWhere = $mapManager->getSqlWhere();
$occArr = $mapManager->getMapSpecimenArr($pageNumber,$cntPerPage,$mapWhere);
$recordCnt = $mapManager->getRecordCnt();

$pageOccids = array_keys($occArr);
if($selections){
	if(!array_diff($pageOccids,$selections)){
		$allSelected = true;
	}
}

$recordListHtml = '';
$recordListHtml = '<div>';
$paginationStr = "<div><div style='clear:both;'><hr/></div><div style='float:left;'>\n";
$lastPage = (int) ($recordCnt / $cntPerPage) + 1;
$startPage = ($pageNumber > 4?$pageNumber - 4:1);
$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
$hrefPrefix = "<a href='#' onclick='changeRecordPage(starr,";
$pageBar = '';
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
$pageBar .= "</div><div style='clear:both;float:left;margin-top:4px;margin-bottom:8px;'>";
$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
$endNum = $beginNum + $cntPerPage - 1;
if($endNum > $recordCnt) $endNum = $recordCnt;
$pageBar .= "Page ".$pageNumber.", records ".$beginNum."-".$endNum." of ".$recordCnt;
$paginationStr .= $pageBar;
$paginationStr .= "</div><div style='clear:both;'><hr/></div></div>";

$recordListHtml .= $paginationStr;
$recordListHtml .= '</div>';

if($occArr){
	$recordListHtml .= '<form name="selectform" id="selectform" action="" method="post" onsubmit="" target="_blank">';
	$recordListHtml .= '<div style="margin-bottom:5px;">';
	$recordListHtml .= '<input name="" id="selectallcheck" value="" type="checkbox" onclick="selectAll(this);" '.($allSelected==true?"checked":"").' />';
	$recordListHtml .= 'Select/Deselect all Specimens';
	$recordListHtml .= '</div>';
	$recordListHtml .= '<table class="styledtable" style="margin-left:-15px;">';
	$recordListHtml .= '<tr>';
	$recordListHtml .= '<th style="width:15px;"></th>';
	$recordListHtml .= '<th>Catalog #</th>';
	$recordListHtml .= '<th>Collector</th>';
	$recordListHtml .= '<th style="width:40px;">Date</th>';
	$recordListHtml .= '<th>Scientific Name</th>';
	$recordListHtml .= '</tr>';
	$trCnt = 0;
	foreach($occArr as $occId => $recArr){
		$trCnt++;
		$infoBoxLabel = "'".$recArr["c"]."'";
		$recordListHtml .= '<tr '.($trCnt%2?'class="alt"':'').' id="tr'.$occId.'" >';
		$recordListHtml .= '<td>';
		$recordListHtml .= '<input type="checkbox" class="occcheck" id="ch'.$occId.'" name="occid[]" value="'.$occId.'" onchange="findSelections(this);" '.(in_array($occId,$selections)?"checked":"").' />';
		$recordListHtml .= '</td>';
		$recordListHtml .= '<td id="cat'.$occId.'" >'.$recArr["cat"].'</td>';
		$recordListHtml .= '<td id="label'.$occId.'" >';
		$recordListHtml .= '<a href="#" onmouseover="openOccidInfoBox('.$infoBoxLabel.','.$recArr["lat"].','.$recArr["lon"].');" onmouseout="closeOccidInfoBox();" onclick="openIndPopup('.$occId.'); return false;">'.$recArr["c"].'</a>';
		$recordListHtml .= '</td>';
		$recordListHtml .= '<td id="e'.$occId.'" >'.$recArr["e"].'</td>';
		$recordListHtml .= '<td id="s'.$occId.'" >'.$recArr["s"].'</td>';
		$recordListHtml .= '</tr>';
	}
	$recordListHtml .= '</table>';
	$recordListHtml .= '</form>';
	$recordListHtml .= '<div style="">'.$paginationStr.'</div>';
}
else{
	$recordListHtml .= '<div style="font-weight:bold;font-size:120%;">';
	$recordListHtml .= 'No records found matching the query';
	$recordListHtml .= '</div>';
}

//output the response
echo json_encode($recordListHtml);
?>
