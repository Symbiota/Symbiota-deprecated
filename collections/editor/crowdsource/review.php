<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);
if(!$symbUid) header('Location: ../../../profile/index.php?refurl=../collections/editor/crowdsource/controlpanel.php?'.$_SERVER['QUERY_STRING']);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$uid = array_key_exists('uid',$_REQUEST)?$_REQUEST['uid']:0;
$pStatus = array_key_exists('pstatus',$_REQUEST)?$_REQUEST['pstatus']:'';
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:100;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();
//If collid is null, it will be assumed that current user wants to review their own specimens (they can still edit pending, closed specimen can't be editted)
$csManager->setCollid($collid);
$csManager->setSymbUid($SYMB_UID);

$isEditor = 0; 
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}

$statusStr = '';
if($action == 'Submit Reveiws'){
	$occidArr = $_POST['occid'];
	$pointArr = $_POST['points'];
	$commentArr = $_POST['comment'];
	if($occidArr) $statusStr = $csManager->submitReviews($occidArr,$pointArr,$commentArr);
}

$projArr = $csManager->getProjectDetails();
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Crowdsourcing Reviewer</title>
    <link type="text/css" href="../../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">
		function selectAll(cbObj){
			var cbStatus = cbObj.checked;
			var f = cbObj.form;
			for(var i = 0; i < f.length; i++) {
				if(f.elements[i].name == "occid[]") f.elements[i].checked = cbStatus;
			}
		}

		function expandNotes(textObj){
			textObj.style.width = "300px";
		}

		function collapseNotes(textObj){
			textObj.style.width = "60px";
		}

		function validateReviewForm(f){
			for(var i = 0; i < f.length; i++) {
				if(f.elements[i].name == "occid[]" && f.elements[i].checked) return true;
			}
			alert("No records have been selected");
			return false;
		}
	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../../index.php">Home</a> &gt;&gt;
		<a href="central.php">Source Board</a> &gt;&gt;
		<?php 
		if($collid) echo '<a href="controlpanel.php?collid='.$collid.'">Control Panel</a> &gt;&gt;';
		?>
		<b>Crowdsourcing Review</b>
	</div>
	<!-- inner text -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		} 
		?>
		<div style="font-weight:bold;font-size:130%;float:left;width:465px;"><?php echo ($collid?$projArr['name']:$USER_DISPLAY_NAME); ?></div>
		<div style="float:left;">
			<form name="filter" action="review.php" method="post">
				<fieldset style="width:300px;text-align:left;">
					<legend><b>Filter</b></legend>
					<div style="margin:3px;">
						Processing Status: 
						<select name="pstatus">
							<option value="">All Records</option>
							<option value="">----------------------</option>
							<?php 
							$pStatusArr = $csManager->getProcessingStatusList();
							foreach($pStatusArr as $pStatusValue){
								echo '<option '.($pStatus==$pStatusValue?'SELECTED':'').'>'.$pStatusValue.'</option>'."\n";
							}
							?>
						</select>
					</div>
					<?php 
					if($collid){
						?>
						<div style="margin:3px;">
							Editor: 
							<select name="uid">
								<option value="">All Editors</option>
								<option value="">----------------------</option>
								<?php 
								$editorArr = $csManager->getEditorList();
								foreach($editorArr as $eUid => $eName){
									echo '<option value="'.$eUid.'" '.($eUid==$uid?'SELECTED':'').'>'.$eName.'</option>'."\n";
								}
								?>
							</select>
						</div>
						<?php 
					}
					?>
					<div style="margin:3px;">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="action" type="submit" value="Filter Records" />
					</div>
				</fieldset>
			</form>
		</div>
		<div style="clear:both;">
			<?php 
			if($recArr = $csManager->getReviewArr($start,$limit,$uid,$pStatus)){
				$totalCnt = $recArr['totalcnt'];
				unset($recArr['totalcnt']);
				//Set up navigation string
				$urlPrefix = 'controlpanel.php?collid='.$collid.'&uid='.$uid.'&pstatus='.$pStatus;
				$navStr = '<b>';
				if($start > 0) $navStr .= '<a href="'.$urlPrefix.'&start='.$start.'&limit='.$limit.'">';
				$navStr .= '|&lt; ';
				if($start > 0) $navStr .= '</a>';
				$navStr .= '&nbsp;&nbsp;&nbsp;';
				if($start > 0) $navStr .= '<a href="'.$urlPrefix.'&start='.($start-$limit).'&limit='.$limit.'">';
				$navStr .= '&lt;&lt;';
				if($start > 0) $navStr .= '</a>';
				$navStr .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.($start + 1).' - '.($limit<$totalCnt?$limit:$totalCnt).'&nbsp;&nbsp;|&nbsp;&nbsp;';
				if(count($recArr) > $limit) $navStr .= '<a href="'.$urlPrefix.'&start='.($start+$limit).'&limit='.$limit.'">';
				$navStr .= '&gt;&gt;';
				if(count($recArr) > $limit) $navStr .= '</a>';
				$navStr .= '&nbsp;&nbsp;&nbsp;';
				if($start+(count($recArr)) < $totalCnt) $navStr .= '<a href="'.$urlPrefix.'&start='.($totalCnt-$limit).'&limit='.($limit+2).'">';
				$navStr .= '&gt;|';
				if($start+(count($recArr)) < $totalCnt) $navStr .= '</a> ';
				$navStr .= '</b>';
				?>
				<div>
					<div style="float:left;"><b>Total Record Count:</b> <?php echo $totalCnt; ?></div>
					<div style="float:left;margin-left:500px;"><?php echo $navStr; ?></div>
					<div style="clear:both;">
						<form name="reviewform" method="post" action="review.php" onsubmit="return validateReviewForm(this)">
							<table class="styledtable">
								<tr>
									<?php 
									if($collid) echo '<th><span title="Select All"><input name="selectall" type="checkbox" onclick="selectAll(this)" /></span></th>';
									?>
									<th>Points</th>
									<th>Comments</th>
									<th>Record ID</th>
									<?php 
									$hArr = $recArr['header'];
									unset($recArr['header']);
									foreach($hArr as $f => $v){
										echo '<th>'.$f.'</th>';
									}
									?>
								</tr>
								<?php 
								$cnt = 0;
								foreach($recArr as $occid => $rArr){
								?>
									<tr class="alt">
										<?php 
										$notes = '';
										if(isset($rArr['notes'])) $notes = $rArr['notes'];
										$points = 2;
										if(isset($rArr['points'])) $points = $rArr['points'];
										if($collid){
											echo '<td><input name="occid[]" type="checkbox" value="'.$occid.'" /></td>';
											echo '<td><input name="points[]" type="text" value="'.$points.'" style="width:15px;" /></td>';
											echo '<td><input name="comment[]" type="text" value="'.$notes.'" style="width:60px;" onfocus="expandNotes(this)" onblur="collapseNotes(this)"/></td>';
										}
										else{
											echo '<td><input name="points[]" type="text" value="'.$points.'" style="width:15px;" DISABLED /></td>';
											echo '<td>'.$notes.'</td>';
										}
										?>
										<td>
											<?php 
											$activeLink = false;
											if(in_array($rArr['collid'],$USER_RIGHTS['CollAdmin']) || $rArr['processingstatus'] == 'pending review') $activeLink = true;
											if($activeLink) echo '<a href="../occurrenceeditor.php?csmode=1&occid='.$occid.'" target="_blank">';
											echo $occid;
											if($activeLink) echo '</a>';
											?>
										</td>
										<?php 
										foreach($hArr as $f => $v){
											echo '<td>'.$rArr[$f].'</td>';
										}
										?>
									</tr>
									<?php
									$cnt++; 
								}
								?>
							</table>
							<?php
							if($collid){
								echo '<input name="collid" type="hidden" value="'.$collid.'" />';
								echo '<input name="pstatus" type="hidden" value="'.$pStatus.'" />';
								echo '<input name="uid" type="hidden" value="'.$uid.'" />';
								echo '<input name="action" type="submit" value="Submit Reveiws" />';
							}
							?>
						</form>
					</div>
					<div style="margin-left:500px;">
						<?php echo $navStr; ?>
					</div>
				</div>
				<?php 
			}
			?>
		</div>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>