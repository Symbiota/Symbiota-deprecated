<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1;

//Sanitation
if(!is_numeric($cntPerPage)) $cntPerPage = 100;
if(!is_numeric($pageNumber)) $pageNumber = 1;

$mapManager = new OccurrenceMapManager();
$searchVar = $mapManager->getSearchTermStr();
$occArr = $mapManager->getOccurrenceArr($pageNumber,$cntPerPage);
?>
<div id="queryrecordsdiv" style="">
	<div style="height:25px;margin-top:-5px;">
		<div>
			<div style="float:left;">
				<form action="csvoptions.php" method="post">
					<button name="dlsubmit" type="submit">Download CSV file</button>
					<input name="dltype" type="hidden" value="specimen" />
					<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
				</form>
			</div>
			<div style="float:right;">
				<form name="fullquerykmlform" id="fullquerykmlform" action="kmlmanager.php" method="post" style="margin-bottom:0px;" onsubmit="">
					<input data-role="none" name="selectionskml" id="selectionskml" type="hidden" value="" />
					<input data-role="none" name="kmltype" id="kmltype" type="hidden" value="fullquery" />
					<input data-role="none" name="kmlreclimit" id="kmlreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
					<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
					<button data-role="none" name="submitaction" type="button" onclick='prepSelectionKml(this.form);' >Download KML file</button>
				</form>
			</div>
		</div>
	</div>
	<div>
		<?php
		$paginationStr = '<div><div style="clear:both;"><hr/></div><div style="margin:5px;">';
		$lastPage = (int)($mapManager->getRecordCnt() / $cntPerPage) + 1;
		$startPage = ($pageNumber > 5?$pageNumber - 5:1);
		$endPage = ($lastPage > $startPage + 10?$startPage + 10:$lastPage);
		$pageBar = '';
		if($startPage > 1){
			$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="occurrencelist.php?'.$searchVar.'" >'.$LANG['PAGINATION_FIRST'].'</a></span>';
			$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="occurrencelist.php?'.$searchVar.'&page='.(($pageNumber - 10) < 1?1:$pageNumber - 10).'">&lt;&lt;</a></span>';
		}
		for($x = $startPage; $x <= $endPage; $x++){
			if($pageNumber != $x){
				$pageBar .= '<span class="pagination" style="margin-right:3px;margin-right:3px;"><a href="occurrencelist.php?'.$searchVar.'&page='.$x.'">'.$x.'</a></span>';
			}
			else{
				$pageBar .= '<span class="pagination" style="margin-right:3px;margin-right:3px;font-weight:bold;">'.$x.'</span>';
			}
		}
		if(($lastPage - $startPage) >= 10){
			$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="occurrencelist.php?'.$searchVar.'&page='.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10)).'">&gt;&gt;</a></span>';
			$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="occurrencelist.php?'.$searchVar.'&page='.$lastPage.'">Last</a></span>';
		}
		$pageBar .= '</div><div style="margin:5px;">';
		$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
		$endNum = $beginNum + $cntPerPage - 1;
		if($endNum > $mapManager->getRecordCnt()) $endNum = $mapManager->getRecordCnt();
		$pageBar .= $LANG['PAGINATION_PAGE'].' '.$pageNumber.', '.$LANG['PAGINATION_RECORDS'].' '.$beginNum.'-'.$endNum.' '.$LANG['PAGINATION_OF'].' '.$mapManager->getRecordCnt();
		$paginationStr .= $pageBar;
		$paginationStr .= '</div><div style="clear:both;"><hr/></div></div>';
		echo $paginationStr;

		if($occArr){
			?>
			<form name="selectform" id="selectform" action="" method="post" onsubmit="" target="_blank">
				<table class="styledtable" style="font-family:Arial;font-size:12px;margin-left:-15px;">
					<tr>
						<!--
						<th style="width:10px;" title="Select/Deselect all Records">
							<input id="selectallcheck" type="checkbox" onclick="selectAll(this);" '.($allSelected==true?"checked":"").' />
						</th>
						 -->
						<th>Catalog #</th>
						<th>Collector</th>
						<th>Date</th>
						<th>Scientific Name</th>
					</tr>
					<?php
					$trCnt = 0;
					foreach($occArr as $occId => $recArr){
						$trCnt++;
						echo '<tr '.($trCnt%2?'class="alt"':'').' id="tr'.$occId.'" >';
						//echo '<td style="width:10px;">';
						//echo '<input type="checkbox" class="occcheck" id="ch'.$occId.'" name="occid[]" value="'.$occId.'" onchange="findSelections(this);" '.(in_array($occId,$selections)?"checked":"").' />';
						//echo '</td>';
						echo '<td id="cat'.$occId.'" >'.wordwrap($recArr["cat"], 7, "<br />\n", true).'</td>';
						echo '<td id="label'.$occId.'" >';
						echo '<a href="#" onmouseover="openOccidInfoBox(\''.$recArr["c"].'\','.$recArr["lat"].','.$recArr["lon"].');" onmouseout="closeOccidInfoBox();" onclick="openIndPopup('.$occId.'); return false;">'.($recArr["c"]?wordwrap($recArr["c"], 12, "<br />\n", true):"Not available").'</a>';
						echo '</td>';
						echo '<td id="e'.$occId.'" >'.wordwrap($recArr["e"], 10, "<br />\n", true).'</td>';
						echo '<td id="s'.$occId.'" >'.wordwrap($recArr["s"], 12, "<br />\n", true).'</td>';
						echo '</tr>';
					}
					?>
				</table>
			</form>
			<?php
			if($lastPage > $startPage) echo '<div style="">'.$paginationStr.'</div>';
		}
		else{
			?>
			<div style="font-weight:bold;font-size:120%;">No records found matching the query</div>
			<?php
		}
		?>
	</div>
</div>