<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecEditReviewManager.php');

if(!$symbUid) header('Location: ../../profile/index.php?refurl=../collections/editor/editreviewer.php?'.$_SERVER['QUERY_STRING']);
header("Content-Type: text/html; charset=".$CHARSET);

$collid = $_REQUEST['collid'];
$displayMode = array_key_exists('display',$_REQUEST)?$_REQUEST['display']:'1';
$faStatus = array_key_exists('fastatus',$_REQUEST)?$_REQUEST['fastatus']:'';
$frStatus = array_key_exists('frstatus',$_REQUEST)?$_REQUEST['frstatus']:'1,2';
$editor = array_key_exists('editor',$_REQUEST)?$_REQUEST['editor']:'';
$queryOccid = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:'';
$pageNum = array_key_exists('pagenum',$_REQUEST)?$_REQUEST['pagenum']:'0';
$limitCnt = array_key_exists('limitcnt',$_REQUEST)?$_REQUEST['limitcnt']:'1000';
$printMode = (array_key_exists('printsubmit', $_POST)?true:false);

$reviewManager = new SpecEditReviewManager();
$collName = $reviewManager->setCollId($collid);
$reviewManager->setDisplay($displayMode);
if(is_numeric($queryOccid)){
	$reviewManager->setQueryOccidFilter($queryOccid);
	$faStatus = '';
	$frStatus = 0;
}
else{
	$reviewManager->setAppliedStatusFilter($faStatus);
	$reviewManager->setReviewStatusFilter($frStatus);
}
$reviewManager->setEditorFilter($editor);
$reviewManager->setPageNumber($pageNum);
$reviewManager->setLimitNumber($limitCnt);


$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}
elseif($reviewManager->getObsUid()){
	$isEditor = true;
}

$statusStr = "";
if($isEditor){
	if(array_key_exists('updatesubmit', $_POST)){
		if(!$reviewManager->updateRecords($_POST)){
			$statusStr = '<br>'.implode('</br><br>',$reviewManager->getWarningArr()).'</br>';
		}
	}
	elseif(array_key_exists('delsubmit', $_POST)){
		$idStr = implode(',',$_POST['id']);
		$reviewManager->deleteEdits($idStr);
	}
	elseif(array_key_exists('dlsubmit', $_POST)){
		$idStr = implode(',',$_POST['id']);
		if($reviewManager->exportCsvFile($idStr)){
			exit();
		}
		else{
			$statusStr = $reviewManager->getErrorMessage();
		}
	}
	elseif(array_key_exists('dlallsubmit', $_POST)){
		if($reviewManager->exportCsvFile('', true)){
			exit();
		}
		else{
			$statusStr = $reviewManager->getErrorMessage();
		}
	}
}
$recCnt = $reviewManager->getEditCnt();

$subCnt = $limitCnt*($pageNum + 1);
if($subCnt > $recCnt) $subCnt = $recCnt;  
$navPageBase = 'editreviewer.php?collid='.$collid.'&display='.$displayMode.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&editor='.$editor;

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
?>
<html>
	<head>
		<title>Specimen Edit Reviewer</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<script>
			function selectAllId(cbObj){
				var eElements = document.getElementsByName("id[]");
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
				var elements = document.getElementsByName("id[]");
				for(i = 0; i < elements.length; i++){
					var elem = elements[i];
					if(elem.checked) return true;
				}
			   	alert("Please check at least one edit from list below!");
		      	return false;
			}

			function validateDelete(f){
				 if(validateEditForm(f)){
					 return confirm('Are you sure you want to permanently remove selected edits from history?');
				 }
				 return false;
			}

			function openIndPU(occid,clid){
				var newWindow = window.open('../editor/occurrenceeditor.php?occid='+occid,'indspec' + occid,'scrollbars=1,toolbar=1,resizable=1,width=1000,height=700,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
			}
		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js" type="text/javascript" ></script>
	</head>
	<body>
		<?php
		if(!$printMode){
			$displayLeftMenu = false;
			include($SERVER_ROOT.'/header.php');
			echo '<div class="navpath">';
			echo '<a href="../../index.php">Home</a> &gt;&gt; ';
			if($reviewManager->getObsUid()){
				echo '<a href="../../profile/viewprofile.php?tabindex=1">Personal Specimen Management</a> &gt;&gt; ';
			}
			else{
				echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Management Panel</a> &gt;&gt; ';
			}
			echo '<b>Specimen Edits Reviewer</b>';
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($collid && $isEditor){
				?>
				<div style="font-weight:bold;font-size:130%;"><?php echo $collName; ?></div>
				<?php 
				if($statusStr){ 
					?>
					<div style='margin:20px;font-weight:bold;color:red;'>
						<?php echo $statusStr; ?>
					</div>
					<?php 
				}
				if($printMode){
					$retToMenuStr = '<b><a href="editreviewer.php?collid='.$collid.'&display='.$displayMode.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&pagenum='.$pageNum.'&limitcnt='.$limitCnt.'">Return to Main Page</a></b>';
					echo $retToMenuStr;
				}
				else{
					?>
					<div style="float:right;">
						<form name="filter" action="editreviewer.php" method="post">
							<fieldset style="width:300px;text-align:left;">
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
										<option value="1,2" <?php echo ($frStatus=='1,2'?'SELECTED':''); ?>>Open/Pending</option>
										<option value="1" <?php echo ($frStatus=='1'?'SELECTED':''); ?>>Open Only</option>
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
										foreach($editorArr as $id => $e){
											echo '<option value="'.$id.'" '.($editor==$id?'SELECTED':'').'>'.$e.'</option>'."\n";
										}
										?>
									</select>
								</div>
								<?php 
								if($ACTIVATE_GEOLOCATE_TOOLKIT && !$reviewManager->getObsUid()){
									?>
									<div style="margin:3px;">
										Editing Source: 
										<select name="display">
											<option value="1">Internal</option>
											<option value="2" <?php if($displayMode == 2) echo 'SELECTED'; ?>>External (GeoLocate CoGe)</option>
										</select>
									</div>
									<?php 
								}
								?>
								<div style="margin:10px;">
									<input name="submitform" type="submit" value="Update List" />
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								</div>
							</fieldset>
						</form>
					</div>
					<?php
				}
				?>
				<form name="editform" action="editreviewer.php" method="post" >
					<?php 
					if(!$printMode){
						?>
						<div style="margin:10px;float:left;">
							<fieldset>
								<legend><b>Action Panel</b></legend>
								<div style="margin:10px 10px;">
									<div style="float:left;margin-bottom:10px;">
										<input name="applytask" type="radio" value="apply" CHECKED title="Apply Edits, if not already done" />Apply Edits<br/>
										<input name="applytask" type="radio" value="revert" title="Revert Edits" />Revert Edits
									</div>
									<div style="float:left;margin-left:30px;">
										<b>Review Status:</b>
										<select name="rstatus">
											<option value="0">LEAVE AS IS</option>
											<option value="1">OPEN</option>
											<option value="2">PENDING</option>
											<option value="3">CLOSED</option>
										</select>
									</div>
									<div style="clear:both;margin:15px 5px;">
										<input name="updatesubmit" type="submit" value="Update Selected Records" onclick="return validateEditForm(this.form);" />
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="fastatus" type="hidden" value="<?php echo $faStatus; ?>" />
										<input name="frstatus" type="hidden" value="<?php echo $frStatus; ?>" />
										<input name="editor" type="hidden" value="<?php echo $editor; ?>" />
										<input name="occid" type="hidden" value="<?php echo $queryOccid; ?>" />
										<input name="pagenum" type="hidden" value="<?php echo $pageNum; ?>" />
										<input name="limitcnt" type="hidden" value="<?php echo $limitCnt; ?>" />
										<input name="display" type="hidden" value="<?php echo $displayMode; ?>" />
									</div>
								</div>
								<div style="clear:both;margin:15px 0px;">
									<hr/>
									<a href="#" onclick="toggle('additional')"><b>Additional Actions</b></a>
								</div>
								<div id="additional" style="display:none">
									<div style="margin:10px 15px;">
										<input name="delsubmit" type="submit" value="Delete Selected Edits" onclick="return validateDelete(this.form)" />
										<div style="margin:5px 0px 10px 10px;">* Permanently clear selected edit from versioning history. Warning: this action can not be undone!</div>
									</div>
									<div style="margin:5px 0px 10px 15px;">
										<input name="dlsubmit" type="submit" value="Download Selected Records" onclick="return validateEditForm(this.form);" />
									</div>
									<div style="margin:5px 0px 10px 15px;">
										<input name="dlallsubmit" type="submit" value="Download All Records" />
										<div style="margin:5px 0px 10px 10px;">* Based on search parameters in Filter Pane to the right</div>
									</div>
									<div style="margin:10px 15px;">
										<input name="printsubmit" type="submit" value="Print Friendly Page" />
									</div>
								</div>
							</fieldset>
						</div>
						<?php
					}
					echo '<div style="clear:both">'.$navStr.'</div>'; 
					?>
					<table class="styledtable" style="font-family:Arial;font-size:12px;<?php if($printMode) echo "width:90%;"; ?>">
						<tr>
							<?php 
							if(!$printMode){ 
								?>
								<th title="Select/Unselect All"><input name='selectall' type="checkbox" onclick="selectAllId(this)" /></th>
								<?php 
							} 
							?>
							<th>Record #</th>
							<th>Catalog Number</th>
							<th>Review Status</th>
							<th>Applied Status</th>
							<th>Editor</th>
							<th>Timestamp</th>
							<th>Field Name</th>
							<th>Old Value</th>
							<th>New Value</th>
						</tr>
						<?php 
						$editArr = $reviewManager->getEditArr();
						if($editArr){
							$recCnt = 0;
							foreach($editArr as $occid => $edits){
								foreach($edits as $ts => $edObj){
									$fieldArr = $edObj['f'];
									$id = $edObj['id'];
									$fieldCnt = 0;
									foreach($fieldArr as $fieldName => $fieldObj){
										?>
										<tr <?php echo ($recCnt%2?'class="alt"':'') ?>>
											<?php 
											if(!$printMode){ 
												?>
												<td>
													<?php 
													if(!$fieldCnt){
														echo '<input name="id[]" type="checkbox" value="'.$id.'" />';
													}
													?>
												</td>
												<?php 
											} 
											?>
											<td>
												<?php 
												if(!$fieldCnt){
													if(!$printMode){ 
														?>
														<a href="#" onclick="openIndPU(<?php echo $occid; ?>);return false;">
															<?php echo $occid; ?>
														</a>
														<?php 
													}
													else{
														echo $occid;
													}
												}
												?>
											</td>
											<td>
												<div title="Catalog Number">
													<?php if(!$printMode && !$fieldCnt) echo $edObj['catnum']; ?>
												</div>
											</td>
											<td>
												<div title="Review Status">
													<?php
													if(!$fieldCnt){
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
													}
													?>
												</div>
											</td>
											<td>
												<div title="Applied Status">
													<?php 
													if(!$fieldCnt){
														$aStatus = $edObj['astatus'];
														if($aStatus == 1){
															echo 'APPLIED';
														}
														else{
															echo 'NOT APPLIED';
														}
													}
													?>
												</div>
											</td>
											<td>
												<div title="Editor">
													<?php if(!$fieldCnt) echo $edObj['editor']; ?>
												</div>
											</td>
											<td>
												<div title="Timestamp">
													<?php if(!$fieldCnt) echo $ts; ?>
												</div>
											</td>
											<td>
												<div title="Field Name">
													<?php echo $fieldName; ?>
												</div>
											</td>
											<td>
												<div title="Old Value">
													<?php echo wordwrap($fieldObj['old'],40,"<br />\n",true); ?>
												</div>
											</td>
											<td>
												<div title="New Value">
													<?php echo wordwrap($fieldObj['new'],40,"<br />\n",true); ?>
												</div>
											</td>
										</tr>
										<?php
										$fieldCnt++;
									}
									$recCnt++;
								}
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
					if($printMode){
						echo $retToMenuStr;
					}
					else{
						echo $navStr; 
					}
					?>
				</form>
				<?php 
			}
			else{
				echo '<div>Error!</div>';						
			}
			?>
		</div>
		<?php if(!$printMode) include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>