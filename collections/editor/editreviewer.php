<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditReview.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/editor/editreviewer.'.$LANG_TAG.'.php');


if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/editreviewer.php?'.$_SERVER['QUERY_STRING']);
header("Content-Type: text/html; charset=".$CHARSET);

$collid = $_REQUEST['collid'];
$displayMode = array_key_exists('display',$_REQUEST)?$_REQUEST['display']:'1';
$faStatus = array_key_exists('fastatus',$_REQUEST)?$_REQUEST['fastatus']:'';
$frStatus = array_key_exists('frstatus',$_REQUEST)?$_REQUEST['frstatus']:'1,2';
$editor = array_key_exists('editor',$_REQUEST)?$_REQUEST['editor']:'';
$queryOccid = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:'';
$startDate = array_key_exists('startdate',$_REQUEST)?$_REQUEST['startdate']:'';
$endDate = array_key_exists('enddate',$_REQUEST)?$_REQUEST['enddate']:'';
$pageNum = array_key_exists('pagenum',$_REQUEST)?$_REQUEST['pagenum']:'0';
$limitCnt = array_key_exists('limitcnt',$_REQUEST)?$_REQUEST['limitcnt']:'1000';

$reviewManager = new OccurrenceEditReview();
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
$reviewManager->setStartDateFilter($startDate);
$reviewManager->setEndDateFilter($endDate);
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
		if($SOLR_MODE){
			$solrManager = new SOLRManager();
			$solrManager->updateSOLR();
		}
	}
	elseif(array_key_exists('delsubmit', $_POST)){
		$idStr = implode(',',$_POST['id']);
		$reviewManager->deleteEdits($idStr);
		if($SOLR_MODE){
			$solrManager = new SOLRManager();
			$solrManager->updateSOLR();
		}
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

$navStr = '<div class="navbarDiv" style="float:right;">';
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
		<link href="<?php echo $CLIENT_ROOT; ?>/css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script>
			function validateFilterForm(f){
				if(f.startdate.value > f.enddate.value){
					alert("Start date cannot be after end date");
					return false;
				}
				return true
			}

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

			function printFriendlyMode(status){
				if(status){
					$(".navpath").hide();
					$(".header").hide();
					$(".navbarDiv").hide();
					$(".returnDiv").show();
					$("#filterDiv").hide();
					$("#actionDiv").hide();
					$(".footer").hide();
				}
				else{
					$(".navpath").show();
					$(".header").show();
					$(".navbarDiv").show();
					$(".returnDiv").hide();
					$("#filterDiv").show();
					$("#actionDiv").show();
					$(".footer").show();
				}
			}

			function openIndPU(occid,clid){
				var newWindow = window.open('../editor/occurrenceeditor.php?occid='+occid,'indspec' + occid,'scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
			}
		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js" type="text/javascript" ></script>
	</head>
	<body>
		<?php
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
		?>
		<!-- This is inner text! -->
		<div id="innertext" style="min-width:1100px">
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
				$retToMenuStr = '<div class="returnDiv" style="display:none"><b><a href="#" onclick="printFriendlyMode(false)">Exit Print Mode</a></b></div>';
				echo $retToMenuStr;
				?>
				<div id="filterDiv" style="float:right;">
					<form name="filter" action="editreviewer.php" method="post" onsubmit="return validateFilterForm(this)">
						<fieldset style="width:375px;text-align:left;">
							<legend><b><?php echo $LANG['FILTER'];?></b></legend>
							<div style="margin:3px;">
								<?php echo $LANG['APPLIED'];?> 
								<select name="fastatus">
									<option value=""><?php echo $LANG['ALL_RECORD'];?></option>
									<option value="0" <?php echo ($faStatus=='0'?'SELECTED':''); ?>><?php echo $LANG['NOT_APLI'];?></option>
									<option value="1" <?php echo ($faStatus=='1'?'SELECTED':''); ?>><?php echo $LANG['APLIED'];?></option>
								</select>
							</div>
							<div style="margin:3px;">
								<?php echo $LANG['REVIEW_STATUS'];?> 
								<select name="frstatus">
									<option value="0"><?php echo $LANG['ALL'];?></option>
									<option value="1,2" <?php echo ($frStatus=='1,2'?'SELECTED':''); ?>><?php echo $LANG['OPEN_PENDING'];?></option>
									<option value="1" <?php echo ($frStatus=='1'?'SELECTED':''); ?>><?php echo $LANG['OPEN_ONLY'];?></option>
									<option value="2" <?php echo ($frStatus=='2'?'SELECTED':''); ?>><?php echo $LANG['PENDING_ONLY'];?></option>
									<option value="3" <?php echo ($frStatus=='3'?'SELECTED':''); ?>><?php echo $LANG['CLOSED'];?></option>
								</select>
							</div>
							<div style="margin:3px;">
								<?php echo $LANG['EDITOR'];?> 
								<select name="editor">
									<option value=""><?php echo $LANG['ALL_EDITOR'];?> </option>
									<option value="">----------------------</option>
									<?php 
									$editorArr = $reviewManager->getEditorList();
									foreach($editorArr as $id => $e){
										echo '<option value="'.$id.'" '.($editor==$id?'SELECTED':'').'>'.$e.'</option>'."\n";
									}
									?>
								</select>
							</div>
							<div style="margin:3px;">
								<?php echo $LANG['DATE'];?>
								<input name="startdate" type="date" value="<?php echo $startDate; ?>" /> <?php echo $LANG['TO'];?> 
								<input name="enddate" type="date" value="<?php echo $endDate; ?>" /> 
							</div>
							<div style="margin:10px;float:right;">
								<button name="submitbutton" type="submit" value="submitfilter"><?php echo $LANG['SUBMIT'];?> </button>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							</div>
							<?php 
							if($reviewManager->hasRevisionRecords() && !$reviewManager->getObsUid()){
								?>
								<div style="margin:3px;">
									<?php echo $LANG['EDITING'];?>
									<select name="display">
										<option value="1"><?php echo $LANG['INTERNAL'];?></option>
										<option value="2" <?php if($displayMode == 2) echo 'SELECTED'; ?>><?php echo $LANG['EXTERNAL'];?></option>
									</select>
								</div>
								<?php 
							}
							?>
						</fieldset>
					</form>
				</div>
				<form name="editform" action="editreviewer.php" method="post" >
					<div id="actionDiv" style="margin:10px;float:left;">
						<fieldset>
							<legend><b><?php echo $LANG['ACTION_PANEL'];?></b></legend>
							<div style="margin:10px 10px;">
								<div style="float:left;margin-bottom:10px;">
									<input name="applytask" type="radio" value="apply" CHECKED title="Apply Edits, if not already done" /><?php echo $LANG['APPLY_EDIT'];?><br/>
									<input name="applytask" type="radio" value="revert" title="Revert Edits" /><?php echo $LANG['REVERT_EDIT'];?>
								</div>
								<div style="float:left;margin-left:30px;">
									<b><?php echo $LANG['REVIEW'];?></b>
									<select name="rstatus">
										<option value="0"><?php echo $LANG['LEAVE'];?></option>
										<option value="1"><?php echo $LANG['OPEN'];?></option>
										<option value="2"><?php echo $LANG['PEN'];?></option>
										<option value="3"><?php echo $LANG['CLO'];?></option>
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
								<a href="#" onclick="toggle('additional')"><b><?php echo $LANG['ADD_ACTION'];?></b></a>
							</div>
							<div id="additional" style="display:none">
								<div style="margin:10px 15px;">
									<input name="delsubmit" type="submit" value="Delete Selected Edits" onclick="return validateDelete(this.form)" />
									<div style="margin:5px 0px 10px 10px;"><?php echo $LANG['PERMANENTLY'];?></div>
								</div>
								<div style="margin:5px 0px 10px 15px;">
									<input name="dlsubmit" type="submit" value="Download Selected Records" onclick="return validateEditForm(this.form);" />
								</div>
								<div style="margin:5px 0px 10px 15px;">
									<input name="dlallsubmit" type="submit" value="Download All Records" />
									<div style="margin:5px 0px 10px 10px;"><?php echo $LANG['BASED'];?></div>
								</div>
								<div style="margin:10px 15px;">
									<input name="printsubmit" type="button" value="Print Friendly Page" onclick="printFriendlyMode(true)" />
								</div>
							</div>
						</fieldset>
					</div>
					<?php
					echo '<div style="clear:both">'.$navStr.'</div>'; 
					?>
					<table class="styledtable" style="font-family:Arial;font-size:12px;">
						<tr>
							<th title="Select/Unselect All"><input name='selectall' type="checkbox" onclick="selectAllId(this)" /></th>
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
							foreach($editArr as $occid => $editArr2){
								foreach($editArr2 as $id => $editArr3){
									foreach($editArr3 as $appliedStatus => $edObj){
										$fieldArr = $edObj['f'];
										$displayAll = true;
										foreach($fieldArr as $fieldName => $fieldObj){
											?>
											<tr <?php echo ($recCnt%2?'class="alt"':'') ?>>
												<td>
													<?php 
													if($displayAll){
														echo '<input name="id[]" type="checkbox" value="'.$id.'" />';
													}
													?>
												</td>
												<td>
													<?php 
													if($displayAll){
														?>
														<a href="#" onclick="openIndPU(<?php echo $occid; ?>);return false;">
															<?php echo $occid; ?>
														</a>
														<?php 
													}
													?>
												</td>
												<td>
													<div title="Catalog Number">
														<?php if($displayAll) echo $edObj['catnum']; ?>
													</div>
												</td>
												<td>
													<div title="Review Status">
														<?php
														if($displayAll){
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
														if($displayAll){
															if($appliedStatus == 1){
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
														<?php 
														
														if($displayAll){
															$editorStr = $edObj['editor'];
															if($displayMode == 2){
																if(!$editorStr) $editorStr = $edObj['exeditor'];
																if($edObj['exsource']) $editorStr = $edObj['exsource'].($editorStr?': '.$editorStr:''); 
															}
															echo $editorStr;
														}
														?>
													</div>
												</td>
												<td>
													<div title="Timestamp">
														<?php if($displayAll) echo $edObj['ts']; ?>
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
											$displayAll = false;
										}
									}
									$recCnt++;
								}
							}
						}
						else{
							?>
							<tr>
								<td colspan="10">
									<div style="font-weight:bold;font-size:150%;margin:20px;"><?php echo $LANG['THERE'];?></div>
								</td>
							</tr>
							<?php 
						}
						?>
					</table>
					<?php 
					echo $retToMenuStr;
					echo $navStr; 
					?>
				</form>
				<?php 
			}
			else{
				echo '<div>Error!</div>';						
			}
			?>
		</div>
		<?php include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>