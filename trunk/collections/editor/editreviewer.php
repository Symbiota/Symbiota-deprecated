<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecEditReviewManager.php');

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$submitStr = array_key_exists('submitstr',$_REQUEST)?$_REQUEST['submitstr']:'';
$mode = array_key_exists('mode',$_REQUEST)?$_REQUEST['mode']:'';
$download = array_key_exists('download',$_REQUEST)?$_REQUEST['download']:'';
$faStatus = array_key_exists('fastatus',$_REQUEST)?$_REQUEST['fastatus']:'';
$frStatus = array_key_exists('frstatus',$_REQUEST)?$_REQUEST['frstatus']:'1';
$editorUid = array_key_exists('editor',$_REQUEST)?$_REQUEST['editor']:'';
$pageNum = array_key_exists('pagenum',$_REQUEST)?$_REQUEST['pagenum']:'0';
$limitCnt = array_key_exists('limitcnt',$_REQUEST)?$_REQUEST['limitcnt']:'1000';

if(!$symbUid){
	header('Location: ../../profile/index.php?refurl=../collections/editor/editreviewer.php?'.$_SERVER['QUERY_STRING']);
}

$reviewManager = new SpecEditReviewManager();
$collName = $reviewManager->setCollId($collId);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$status = "";
if($editable){
	if($download){
		$reviewManager->downloadRecords($_REQUEST);
		exit();
	}
	elseif($submitStr == 'Perform Update'){
		$reviewManager->applyAction($_POST);
	}
	elseif($submitStr == 'Delete Edits'){
		$reviewManager->deleteEdits($_POST);
	}
}

if($mode == 'export'){
	$reviewManager->exportCsvFile();
}

$editArr = $reviewManager->getEditArr($faStatus, $frStatus, $editorUid, $pageNum, $limitCnt);
$recCnt = $reviewManager->getRecCnt();
$subCnt = $limitCnt*($pageNum + 1);
if($recCnt < ($pageNum+1)*$limitCnt) $subCnt = $recCnt - ($pageNum)*$limitCnt;  
$navPageBase = 'editreviewer.php?collid='.$collId.'&mode='.$mode.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&editor='.$editorUid;

$navStr = '<div style="float:right;">';
if($pageNum){
	$navStr .= '<a href="'.$navPageBase.'&pagenum='.($pageNum-1).'&limitcnt='.$limitCnt.'" title="Previous '.$limitCnt.' records">&lt;&lt;</a>';
}
else{
	$navStr .= '&lt;&lt;';
}
$navStr .= ' | ';
$navStr .= ($pageNum*$limitCnt).'-'.$subCnt.' of '.$recCnt.' records';
$navStr .= ' | ';
if($subCnt < $recCnt){
	$navStr .= '<a href="'.$navPageBase.'&pagenum='.($pageNum+1).'&limitcnt='.$limitCnt.'" title="Next '.$limitCnt.' records">&gt;&gt;</a>';
}
else{
	$navStr .= '&gt;&gt;';
}
$navStr .= '</div>';

header("Content-Type: text/html; charset=".$charset);
?>
<html>
	<head>
		<title>Specimen Edit Reviewer</title>
		<link href="<?php echo $clientRoot; ?>/css/base.css" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" rel="stylesheet" />
		<script language="javascript">
			function selectAllOcedid(cbObj){
				var eElements = document.getElementsByName("ocedid[]");
				for(i = 0; i < eElements.length; i++){
					var elem = eElements[i];
					if(cbObj.checked){
						elem.checked = true;
					}
					else{
						elem.checked = false;
					}
				}
			}

			function validateEditForm(f){
				var elements = document.getElementsByName("ocedid[]");
				for(i = 0; i < elements.length; i++){
					var elem = elements[i];
					if(elem.checked) return true;
				}
			   	alert("Please check at least one edit from list!");
		      	return false;
			}
		</script>
	</head>
	<body>
		<?php
		if($mode != 'printmode'){
			$displayLeftMenu = (isset($collections_editor_editreviewerMenu)?$collections_individual_editreviewerMenu:false);
			include($serverRoot.'/header.php');
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
			if(isset($collections_editor_editreviewerCrumbs)){
				echo $collections_editor_editreviewerCrumbs;
			}
			else{
				echo '<a href="../misc/collprofiles.php?collid='.$collId.'&emode=1">Collection Management Panel</a> &gt;&gt; ';
			}
			echo " <b>Specimen Edits Reviewer</b>";
			echo "</div>";
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($collId){
				?>
				<table style="width:100%;">
					<tr>
					<td style="vertical-align:top;margin-top:20px;" <?php if($mode == 'printmode') echo 'colspan="2"'; ?>>
						<div style="font-weight:bold;font-size:130%;"><?php echo $collName; ?></div>
						<?php 
						if($status){ 
							?>
							<div style='margin:20px;font-weight:bold;color:red;'>
								<?php echo $status; ?>
							</div>
							<?php 
						}
						?>
					</td>
					<?php 
					if($mode != 'printmode'){
						?>
						<td width="30%">
							<form name="filter" action="editreviewer.php" method="post">
								<fieldset style="width:230px;text-align:left;">
									<legend><b>Filter</b></legend>
									<div style="margin:3px;">
										Applied Status: 
										<select name="fastatus">
											<option value="">All Records</option>
											<option value="0" <?php echo ($faStatus=='0'?'SELECTED':''); ?>>Not Applied</option>
											<option value="1" <?php echo ($faStatus=='1'?'SELECTED':''); ?>>Applied</option>
										</select>
									</div>
									<div style="margin:3px;">
										Review Status: 
										<select name="frstatus">
											<option value="0">All Records</option>
											<option value="0-2" <?php echo (!$frStatus||$frStatus=='0-2'?'SELECTED':''); ?>>Open/Pending</option>
											<option value="0-1" <?php echo ($frStatus=='0-1'?'SELECTED':''); ?>>Open Only</option>
											<option value="2" <?php echo ($frStatus=='2'?'SELECTED':''); ?>>Pending Only</option>
											<option value="3" <?php echo ($frStatus=='3'?'SELECTED':''); ?>>Closed</option>
										</select>
									</div>
									<div style="margin:3px;">
										Editor: 
										<select name="editor">
											<option value="">All Editors</option>
											<option value="">----------------------</option>
											<?php 
											$editorArr = $reviewManager->getEditorList();
											foreach($editorArr as $uid => $e){
												echo '<option value="'.$uid.'" '.($editorUid==$uid?'SELECTED':'').'>'.$e.'</option>'."\n";
											}
											?>
										</select>
									</div>
									<div style="margin:3px;text-align:right;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="action" type="submit" value="Filter Records" />
									</div>
								</fieldset>
							</form>
						</td>
						<?php
					}
					?>
					</tr>
					<tr>
						<td colspan="2">
							<?php 
							if($mode == 'printmode'){
								echo '<b><a href="editreviewer.php?collid='.$collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&pagenum='.$pageNum.'&limitcnt='.$limitCnt.'">Return to Main Page</a></b>';
							}
							else{ 
								echo $navStr; 
								?>
								<div>
									<input name='selectall' type="checkbox" onclick="selectAllOcedid(this)" /> Select/Deselect All
								</div>
								<?php 
							}
							?>
							<form name="editform" action="editreviewer.php" method="post" onsubmit="return validateEditForm(this);" >
								<table class="styledtable" style="<?php if($mode == 'printmode') echo "width:675px;"; ?>">
									<tr>
										<?php if($mode != 'printmode'){ ?>
											<th></th>
										<?php } ?>
										<th>Record #</th>
										<th>Catalog Number</th>
										<th>Field Name</th>
										<th>Old Value</th>
										<th>New Value</th>
										<th>Review Status</th>
										<th>Applied Status</th>
										<th>Editor</th>
										<th>Timestamp</th>
									</tr>
									<?php 
									$editArr = $reviewManager->getEditArr($faStatus, $frStatus, $editorUid, $pageNum, $limitCnt);
									if($editArr){
										$recCnt = 0;
										foreach($editArr as $occid => $edits){
											foreach($edits as $ocedid => $edObj){
												?>
												<tr <?php echo ($recCnt%2?'class="alt"':'') ?>>
													<?php if($mode != 'printmode'){ ?>
														<td>
															<input name="ocedid[]" type="checkbox" value="<?php echo $ocedid; ?>" />
														</td>
													<?php } ?>
													<td>
														<?php 
														if($mode != 'printmode'){ 
															?>
															<a href="javascript:var puRef=window.open('../individual/index.php?occid=<?php echo $occid."','indspec".$occid; ?>','toolbar=1,scrollbars=1,width=870,height=600,left=300,top=20');">
																<?php echo $occid; ?>
															</a>
															<?php 
														}
														else{
															echo $occid;
														} 
														?>
													</td>
													<td>
														<div title="Catalog Number">
															<?php echo $edObj['catnum']; ?>
														</div>
													</td>
													<td>
														<div title="Field Name">
															<?php echo $edObj['fname']; ?>
														</div>
													</td>
													<td>
														<div title="Old Value">
															<?php echo $edObj['fvalueold']; ?>
														</div>
													</td>
													<td>
														<div title="New Status">
															<?php echo $edObj['fvaluenew']; ?>
														</div>
													</td>
													<td>
														<div title="Review Status">
															<?php
															$rStatus = $edObj['rstatus'];
															if($rStatus == 1){
																echo 'OPEN';
															}
															elseif($rStatus == 2){
																echo 'PENDING';
															}
															elseif($rStatus == 3){
																echo 'CLOSED';
															}
															else{
																echo 'UNKNOWN';
															}
															?>
														</div>
													</td>
													<td>
														<div title="Applied Status">
															<?php 
															$aStatus = $edObj['astatus'];
															if($aStatus == 1){
																echo 'APPLIED';
															}
															else{
																echo 'NOT APPLIED';
															}
															?>
														</div>
													</td>
													<td>
														<div title="Editor">
															<?php echo $edObj['uname']; ?>
														</div>
													</td>
													<td>
														<div title="Timestamp">
															<?php echo $edObj['tstamp']; ?>
														</div>
													</td>
												</tr>
												<?php 
											}
											$recCnt++;
										}
										if($mode != 'printmode'){ 
											?>
											<tr><td colspan="10" valign="bottom">
												<div style="font-weight:bold;margin-top:3px;">
													Update Selected Edits:
												</div>
												<div style="margin:10px 10px;">
													<div style="float:left;">
														Applied Status: 
													</div>
													<div style="float:left;margin-bottom:10px;">
														<input name="applytask" type="radio" value="apply" CHECKED title="Apply Edits, if not already done" />Applied<br/>
														<input name="applytask" type="radio" value="revert" title="Revert Edits" />Not Applied (reverts applied edits)
													</div>
													<div style="float:left;margin-left:30px;">
														Review Status:
														<select name="rstatus">
															<option value="0">LEAVE AS IS</option>
															<option value="1">OPEN</option>
															<option value="2">PENDING</option>
															<option value="3">CLOSED</option>
														</select>
													</div>
													<div style="float:left;margin-left:25px;">
														<input name="submitstr" type="submit" value="Perform Update" />
														<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
														<input name="fastatus" type="hidden" value="<?php echo $faStatus; ?>" />
														<input name="frstatus" type="hidden" value="<?php echo $frStatus; ?>" />
														<input name="download" type="hidden" value="" />
													</div>
													<div style="clear:both;margin:20px 0px;">
														<hr/>
													</div>
													<div style="margin:20px;">
														<input name="submitstr" type="submit" value="Delete Edits" onclick="return confirm('Are you sure you want to permanently remove selected edits from history?')" /><br/>
														*Permanently clear selected edit from history. The current applied status of edit will remain.
													</div>
												</div>
												<div style="clear:both;margin-top:10px;">
													<hr/>
													<b>Additional Actions:</b>
												</div>
												<div style="margin:10px 0px 5px 15px;">
													<a href="editreviewer.php?collid=<?php echo $collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=printmode&pagenum='.$pageNum.'&limitcnt='.$limitCnt; ?>">
														Print Friendly Page
													</a>
												</div>
												<div style="margin:5px 0px 10px 15px;">
													<a href="editreviewer.php?collid=<?php echo $collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=export'; ?>">
														Download All Records
													</a>
												</div>
											</td></tr>
											<?php
										}
									}
									else{
										?>
										<tr>
											<td colspan="10">
												<div style="font-weight:bold;font-size:150%;margin:20px;">There are no Edits matching search criteria.</div>
											</td>
										</tr>
										<?php 
									}
									?>
								</table>
								<?php 
								if($mode == 'printmode'){
									echo '<b><a href="editreviewer.php?collid='.$collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&pagenum='.$pageNum.'&limitcnt='.$limitCnt.'">Return to Main Page</a></b>';
								}
								else{
									echo $navStr; 
								}
								?>
							</form>
						</td>
					</tr>
				</table>
				<?php 
			}
			else{
				if($collList = $reviewManager->getCollectionList()){
					?>
					<div style="clear:both;">
						<form name="collidform" action="editreviewer.php" method="post" onsubmit="return validateCollidForm(this);">
							<fieldset>
								<legend><b>Collection Projects</b></legend>
								<div style="margin:15px;">
									<?php 
									foreach($collList as $cId => $cName){
										echo '<input type="radio" name="collid" value="'.$cId.'" /> '.$cName.'<br/>';
									}
									?>
								</div>
								<div style="margin:15px;">
									<input type="submit" name="action" value="Select Collection for Review" />
								</div>
							</fieldset>
						</form>
					</div>
					<?php
				}
				else{
					echo '<div>There are no Collection Project for which you have authority to review</div>';						
				} 
			}
			?>
		</div>
		<?php if($mode != 'printmode') include($serverRoot.'/footer.php');?>
	</body>
</html>
