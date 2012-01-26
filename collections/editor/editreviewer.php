<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecEditReviewManager.php');

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$submitStr = array_key_exists('submitstr',$_REQUEST)?$_REQUEST['submitstr']:'';
$mode = array_key_exists('mode',$_REQUEST)?$_REQUEST['mode']:'';
$download = array_key_exists('download',$_REQUEST)?$_REQUEST['download']:'';
$faStatus = array_key_exists('fastatus',$_REQUEST)?$_REQUEST['fastatus']:'';
$frStatus = array_key_exists('frstatus',$_REQUEST)?$_REQUEST['frstatus']:'1';

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
	elseif($submitStr == 'Perform Action'){
		$reviewManager->applyAction($_REQUEST);
	}
}

if($mode == 'export'){
	$reviewManager->exportCsvFile();
}

header("Content-Type: text/html; charset=".$charset);
?>
<html>
	<head>
		<title>Specimen Edit Reviewer</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
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
		</script>
	</head>
	<body>
		<?php
		if($mode != 'printmode'){
			$displayLeftMenu = (isset($collections_editor_editreviewerMenu)?$collections_individual_editreviewerMenu:false);
			include($serverRoot.'/header.php');
			if(isset($collections_editor_editreviewerCrumbs)){
				echo "<div class='navpath'>";
				echo "<a href='../index.php'>Home</a> &gt; ";
				echo $collections_editor_editreviewerCrumbs;
				echo " <b>Specimen Edits Reviewer</b>";
				echo "</div>";
			}
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($symbUid){
				if($collId){
					?>
					<table style="width:100%;">
						<tr>
						<td style="vertical-align:bottom;" <?php if($mode == 'printmode') echo 'colspan="2"'; ?>>
							<div style="font-weight:bold;font-size:130%;"><?php echo $collName; ?></div>
							<?php 
							if($status){ 
								?>
								<div style='margin:20px;font-weight:bold;color:red;'>
									<?php echo $status; ?>
								</div>
								<?php 
							}
							if($mode != 'printmode'){ 
								?>
								<div style="margin:10px 0px 0px 10px;">
									<input name='selectall' type="checkbox" onclick="selectAllOcedid(this)" /> Select/Deselect All
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
												<option value="1-2" <?php echo (!$frStatus||$frStatus=='1-2'?'SELECTED':''); ?>>Open/Pending</option>
												<option value="1" <?php echo ($frStatus=='1'?'SELECTED':''); ?>>Open Only</option>
												<option value="2" <?php echo ($frStatus=='2'?'SELECTED':''); ?>>Pending Only</option>
												<option value="3" <?php echo ($frStatus=='3'?'SELECTED':''); ?>>Closed</option>
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
										$editArr = $reviewManager->getEditArr($faStatus, $frStatus);
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
													<div style="margin:10px;">
														<span>
															<input name="applytask" type="radio" value="apply" CHECKED title="Apply Edits, if not already done" />Apply Edits
															<input name="applytask" type="radio" value="revert" title="Revert Edits" />Revert Edits
														</span>
														<span style="margin-left:25px;">
															Change Status to:
															<select name="rstatus">
																<option value="0">LEAVE AS IS</option>
																<option value="1">OPEN</option>
																<option value="2">PENDING</option>
																<option value="3">CLOSED</option>
															</select>
														</span>
														<span style="margin-left:25px;">
															<input name="submitstr" type="submit" value="Perform Action" />
															<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
															<input name="fastatus" type="hidden" value="<?php echo $faStatus; ?>" />
															<input name="frstatus" type="hidden" value="<?php echo $frStatus; ?>" />
															<input name="download" type="hidden" value="" />
														</span>
													</div>
													<hr/>
													<div>
														<b>Additional Actions:</b>
													</div>
													<div style="margin:5px 0px 10px 15px;">
														<a href="editreviewer.php?collid=<?php echo $collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=export'; ?>">
															Download Selected Records
														</a>
													</div>
													<div style="margin:10px 0px 5px 15px;">
														<a href="editreviewer.php?collid=<?php echo $collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=printmode'; ?>">
															Display as Printable Form
														</a>
													</div>
												</td></tr>
												<?php
											}
										}
										else{
											?>
											<tr>
												<td colspan="9">
													<div style="font-weight:bold;font-size:150%;margin:20px;">There are no Edits matching search criteria</div>
												</td>
											</tr>
											<?php 
										}
										?>
									</table>
									<?php 
									if($mode == 'printmode'){
										echo '<h2><a href="editreviewer.php?collid='.$collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'">Return to form</a></h2>';
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
			}
			else{
				?>
				<div style='font-weight:bold;'>
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/editor/editreviewer.php?collid=<?php echo $collId; ?>'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
		<?php if($mode != 'printmode') include($serverRoot.'/footer.php');?>
	</body>
</html>
