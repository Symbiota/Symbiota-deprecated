<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpatialModuleManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$selArrJson = array_key_exists("selected",$_REQUEST)?$_REQUEST["selected"]:'';
$q = array_key_exists("q",$_REQUEST)?$_REQUEST["q"]:'';
$fq = array_key_exists("fq",$_REQUEST)?$_REQUEST["fq"]:'';
$recordCnt = array_key_exists("rows",$_REQUEST)?$_REQUEST["rows"]:0;

$selections = Array();
$allSelected = false;

$solrManager = new SOLRManager();

$q = $solrManager->checkQuerySecurity($q);

$qStr = 'q='.$q.'&fq='.$fq;

if($selArrJson){
    $selections = json_decode($selArrJson, true);
}

$solrManager->setQStr($qStr);
$solrArr = $solrManager->getGeoArr($pageNumber,$cntPerPage);
$occArr = $solrManager->translateSOLRMapRecList($solrArr);

$pageOccids = array_keys($occArr);
if($selections){
	if(!array_diff($pageOccids,$selections)){
		$allSelected = true;
	}
}

$recordListHtml = '';
$lastPage = (int) ($recordCnt / $cntPerPage) + 1;
$startPage = ($pageNumber > 4?$pageNumber - 4:1);
if($lastPage > $startPage){
	$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
	$paginationStr = "<div><div style='clear:both;margin:5 0 5 0;'><hr /></div><div style='float:left;'>\n";
	$hrefPrefix = "<a href='#' onclick='changeRecordPage(";
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
	$paginationStr .= "</div><div style='clear:both;margin:5 0 5 0;'><hr /></div></div>";

	$recordListHtml = '<div>';
	$recordListHtml .= $paginationStr;
	$recordListHtml .= '</div>';
}
if($occArr){
	$recordListHtml .= '<form name="selectform" id="selectform" action="" method="post" onsubmit="" target="_blank">';
	$recordListHtml .= '<div style="margin-bottom:5px;clear:both;">';
	$recordListHtml .= '<input name="" id="selectallcheck" value="" type="checkbox" onclick="selectAll(this);" '.($allSelected==true?"checked":"").' />';
	$recordListHtml .= 'Select/Deselect all Records';
	$recordListHtml .= '</div>';
	$recordListHtml .= '<table class="styledtable" style="font-family:Arial;font-size:12px;margin-left:-15px;">';
	$recordListHtml .= '<tr>';
	$recordListHtml .= '<th style="width:10px;"></th>';
	$recordListHtml .= '<th>Catalog #</th>';
	$recordListHtml .= '<th>Collector</th>';
	$recordListHtml .= '<th>Date</th>';
	$recordListHtml .= '<th>Scientific Name</th>';
	$recordListHtml .= '</tr>';
	$trCnt = 0;
	foreach($occArr as $occId => $recArr){
		$trCnt++;
		$infoBoxLabel = "'".$recArr["c"]."'";
		$recordListHtml .= '<tr '.($trCnt%2?'class="alt"':'').' id="tr'.$occId.'" >';
		$recordListHtml .= '<td style="width:10px;">';
		$recordListHtml .= '<input type="checkbox" class="occcheck" id="ch'.$occId.'" name="occid[]" value="'.$occId.'" onchange="processCheckSelection(this);" '.(in_array($occId,$selections)?"checked":"").' />';
		$recordListHtml .= '</td>';
		$recordListHtml .= '<td id="cat'.$occId.'" >'.wordwrap($recArr["cat"], 7, "<br />\n", true).'</td>';
		$recordListHtml .= '<td id="label'.$occId.'" >';
		$recordListHtml .= '<a href="#" onmouseover="openOccidInfoBox('.$occId.','.$infoBoxLabel.');" onmouseout="closeOccidInfoBox();" onclick="openIndPopup('.$occId.'); return false;">'.($recArr["c"]?wordwrap($recArr["c"], 12, "<br />\n", true):"Not available").'</a>';
		$recordListHtml .= '</td>';
		$recordListHtml .= '<td id="e'.$occId.'" >'.wordwrap($recArr["e"], 10, "<br />\n", true).'</td>';
		$recordListHtml .= '<td id="s'.$occId.'" >'.wordwrap($recArr["s"], 12, "<br />\n", true).'</td>';
		$recordListHtml .= '</tr>';
	}
	$recordListHtml .= '</table>';
	$recordListHtml .= '</form>';
	if($lastPage > $startPage){
		$recordListHtml .= '<div style="">'.$paginationStr.'</div>';
	}
}
else{
	$recordListHtml .= '<div style="font-weight:bold;font-size:120%;">';
	$recordListHtml .= 'No records found matching the query';
	$recordListHtml .= '</div>';
}
$recordListHtml = utf8_encode($recordListHtml);

//output the response
echo json_encode($recordListHtml);
?>
