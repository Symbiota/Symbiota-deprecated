<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecEditReviewManager.php');

if(!$symbUid){
	header('Location: ../../profile/index.php?refurl=../collections/editor/editreviewer.php?'.$_SERVER['QUERY_STRING']);
}
header("Content-Type: text/html; charset=".$CHARSET);

$collid = $_REQUEST['collid'];
$submitStr = array_key_exists('submitstr',$_REQUEST)?$_REQUEST['submitstr']:'';
$mode = array_key_exists('mode',$_REQUEST)?$_REQUEST['mode']:'';
$download = array_key_exists('download',$_REQUEST)?$_REQUEST['download']:'';
$faStatus = array_key_exists('fastatus',$_REQUEST)?$_REQUEST['fastatus']:'';
$frStatus = array_key_exists('frstatus',$_REQUEST)?$_REQUEST['frstatus']:'1,2';
$editorUid = array_key_exists('editor',$_REQUEST)?$_REQUEST['editor']:'';
$queryOccid = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:'';
$pageNum = array_key_exists('pagenum',$_REQUEST)?$_REQUEST['pagenum']:'0';
$limitCnt = array_key_exists('limitcnt',$_REQUEST)?$_REQUEST['limitcnt']:'1000';

$reviewManager = new SpecEditReviewManager();
$collName = $reviewManager->setCollId($collid);
$reviewManager->setAppliedStatusFilter($faStatus);
$reviewManager->setReviewStatusFilter($frStatus);
$reviewManager->setEditorUidFilter($editorUid);
$reviewManager->setQueryOccidFilter($queryOccid);
$reviewManager->setPageNumber($pageNum);
$reviewManager->setLimitNumber($limitCnt);


$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

$status = "";
if($isEditor){
	if($download){
		$reviewManager->downloadRecords($_REQUEST);
		exit();
	}
	elseif($submitStr == 'Update Selected Records'){
		$reviewManager->applyAction($_POST);
	}
	elseif($submitStr == 'Delete Edits'){
		$reviewManager->deleteEdits($_POST);
	}
	if($mode == 'export'){
		$reviewManager->exportCsvFile();
	}
}

$recCnt = $reviewManager->getRecCnt();
$subCnt = $limitCnt*($pageNum + 1);
if($recCnt < ($pageNum+1)*$limitCnt) $subCnt = $recCnt - ($pageNum)*$limitCnt;  
$navPageBase = 'editreviewer.php?collid='.$collid.'&mode='.$mode.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&editor='.$editorUid;

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
			   	alert("Please check at least one edit from list below!");
		      	return false;
			}

			function openIndPU(occid,clid){
				var newWindow = window.open('../individual/index.php?occid='+occid,'indspec' + occid,'scrollbars=1,toolbar=1,resizable=1,width=1000,height=700,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
			}
		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js" type="text/javascript" ></script>
	</head>
	<body>
		<?php
		if($mode != 'printmode'){
			$displayLeftMenu = false;
			include($SERVER_ROOT.'/header.php');
			echo '<div class="navpath">';
			echo '<a href="../../index.php">Home</a> &gt;&gt; ';
			echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Management Panel</a> &gt;&gt; ';
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
				if($status){ 
					?>
					<div style='margin:20px;font-weight:bold;color:red;'>
						<?php echo $status; ?>
					</div>
					<?php 
				}
				if($mode == 'printmode'){
					echo '<b><a href="editreviewer.php?collid='.$collid.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&pagenum='.$pageNum.'&limitcnt='.$limitCnt.'">Return to Main Page</a></b>';
				}
				else{
					?>
					<div style="float:right;">
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
										foreach($editorArr as $uid => $e){
											echo '<option value="'.$uid.'" '.($editorUid==$uid?'SELECTED':'').'>'.$e.'</option>'."\n";
										}
										?>
									</select>
								</div>
								<div style="margin:10px;">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="action" type="submit" value="Filter Records" />
								</div>
							</fieldset>
						</form>
					</div>
					<?php
				}
				?>
				<form name="editform" action="editreviewer.php" method="post" onsubmit="return validateEditForm(this);" >
					<?php 
					if($mode != 'printmode'){
						?>
						<div style="margin:10px;float:left;">
							<fieldset>
								<legend><b>Action Panel</b></legend>
								<div style="margin:10px 10px;">
									<div style="float:left;">
										<b>Applied Status:</b> 
									</div>
									<div style="float:left;margin-bottom:10px;">
										<input name="applytask" type="radio" value="apply" CHECKED title="Apply Edits, if not already done" />Applied<br/>
										<input name="applytask" type="radio" value="revert" title="Revert Edits" />Not Applied (reverts applied edits)
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
										<input name="submitstr" type="submit" value="Update Selected Records" />
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="fastatus" type="hidden" value="<?php echo $faStatus; ?>" />
										<input name="frstatus" type="hidden" value="<?php echo $frStatus; ?>" />
										<input name="editor" type="hidden" value="<?php echo $editorUid; ?>" />
										<input name="occid" type="hidden" value="<?php echo $queryOccid; ?>" />
									</div>
								</div>
								<div style="clear:both;margin:20px 0px;">
									<hr/>
								</div>
								<div style="margin:10px 10px;">
									<div style="margin:20px;">
										<input name="submitstr" type="submit" value="Delete Edits" onclick="return confirm('Are you sure you want to permanently remove selected edits from history?')" /><br/>
										*Permanently clear selected edit from versioning history. Warning: this action can not be undone!
									</div>
								</div>
								<div style="clear:both;margin-top:10px;">
									<hr/>
									<a href="#" onclick="toggle('additional')"><b>Additional Actions</b></a>
								</div>
								<div id="additional" style="display:none">
									<div style="margin:10px 0px 5px 15px;">
										<a href="editreviewer.php?collid=<?php echo $collid.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=printmode&pagenum='.$pageNum.'&limitcnt='.$limitCnt; ?>">
											Print Friendly Page
										</a>
									</div>
									<div style="margin:5px 0px 10px 15px;">
										<a href="editreviewer.php?collid=<?php echo $collid.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=export'; ?>">
											Download All Records
										</a>
									</div>
								</div>
							</fieldset>
						</div>
						<?php
					}
					echo '<div style="clear:both">'.$navStr.'</div>'; 
					?>
					<table class="styledtable" style="font-family:Arial;font-size:12px;<?php if($mode == 'printmode') echo "width:90%;"; ?>">
						<tr>
							<?php 
							if($mode != 'printmode'){ 
								?>
								<th title="Select/Unselect All"><input name='selectall' type="checkbox" onclick="selectAllOcedid(this)" /></th>
								<?php 
							} 
							?>
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
						$editArr = $reviewManager->getEditArr();
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
												<a href="#" onclick="openIndPU(<?php echo $occid; ?>)">
													<?php echo $occid; ?>
													<img src="../../images/info.png" style="border:0px;width:14px" />
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
												<?php echo wordwrap($edObj['fvalueold'],40,"<br />\n",true); ?>
											</div>
										</td>
										<td>
											<div title="New Value">
												<?php echo wordwrap($edObj['fvaluenew'],40,"<br />\n",true); ?>
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
						echo '<b><a href="editreviewer.php?collid='.$collid.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&pagenum='.$pageNum.'&limitcnt='.$limitCnt.'">Return to Main Page</a></b>';
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
		<?php if($mode != 'printmode') include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>
