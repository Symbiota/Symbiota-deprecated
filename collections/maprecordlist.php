<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);

$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$stArrJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';
$stArr = Array();
if($stArrJson){
	$stArr = json_decode($stArrJson, true);
}

$mapManager = new MapInterfaceManager();
$mapManager->setSearchTermsArr($stArr);
$mapWhere = $mapManager->getSqlWhere();
$occArr = $mapManager->getMapSpecimenArr($pageNumber,$cntPerPage,$mapWhere);
$recordCnt = $mapManager->getRecordCnt(); 
?>

	<div id="queryrecords" style="">
		<script type="text/javascript">
			var starr = '<?php echo json_encode($stArr); ?>';
		</script>
		<div>
			<?php
			$occArr = $mapManager->getMapSpecimenArr($pageNumber,$cntPerPage,$mapWhere);
			$recordCnt = $mapManager->getRecordCnt();
			
			$paginationStr = "<div><div style='clear:both;'><hr/></div><div style='float:left;margin:5px;'>\n";
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
			        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;'>".$hrefPrefix.$x."); return false;'>".$x."</a></span>";
			    }
			    else{
			        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
			    }
			}
			if(($lastPage - $startPage) >= 10){
			    $pageBar .= "<span class='pagination' style='margin-left:5px;'>".$hrefPrefix.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10))."); return false;'>&gt;&gt;</a></span>";
			    $pageBar .= "<span class='pagination' style='margin-left:5px;'>".$hrefPrefix.$lastPage."); return false;'>Last</a></span>";
			}
			$pageBar .= "</div><div style='float:right;margin:5px;'>";
			$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
			$endNum = $beginNum + $cntPerPage - 1;
			if($endNum == 0){
				$beginNum = 0;
			}
			if($endNum > $recordCnt) $endNum = $recordCnt;
			$pageBar .= "Page ".$pageNumber.", records ".$beginNum."-".$endNum." of ".$recordCnt;
			$paginationStr .= $pageBar;
			$paginationStr .= "</div><div style='clear:both;'><hr/></div></div>";
			
			echo $paginationStr; 
			?>
		</div>
			<?php 
			if($occArr){
				?>
				<form name="selectform" action="defaultlabels.php" method="post" onsubmit="return validateSelectForm(this)" target="_blank">
					<!-- <div style="margin-top: 15px; margin-left: 15px;">
						<input data-role="none" name="" value="" type="checkbox" onclick="selectAll(this);" />
						Select/Deselect all Specimens
					</div>-->
					<table class="styledtable">
						<tr>
							<!-- <th></th> -->
							<th>Catalog #</th>
							<th>Collector</th>
							<th>Date</th>
							<th>Family</th>
							<th>Scientific Name</th>
						</tr>
						<?php 
						$trCnt = 0;
						foreach($occArr as $occId => $recArr){
							$trCnt++;
							?>
							<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
								<!-- <td>
									<input data-role="none" type="checkbox" name="occid[]" value="<?php //echo $occId; ?>" />
								</td> -->
								<td>
									<?php echo $recArr["cat"]; ?>
								</td>
								<td>
									<a href="#" onmouseover="openOccidInfoBox('<?php echo $recArr["c"]; ?>',<?php echo $recArr["lat"]; ?>,<?php echo $recArr["lon"]; ?>);" onmouseout="closeOccidInfoBox();" onclick="openIndPopup(<?php echo $occId; ?>); return false;">
										<?php echo $recArr["c"]; ?>
									</a>
								</td>
								<td>
									<?php echo $recArr["e"]; ?>
								</td>
								<td>
									<?php echo $recArr["f"]; ?>
								</td>
								<td>
									<?php echo $recArr["s"]; ?>
								</td>
							</tr>
							<?php 
						}
						?>
					</table>
				</form>
				<div style="">
					<?php echo $paginationStr; ?>
				</div>
				<?php 
			}
			else{
				?>
				<div style="font-weight:bold;font-size:120%;">
					No records found matching the query
				</div>
				<?php 
			}
			?>
	</div>