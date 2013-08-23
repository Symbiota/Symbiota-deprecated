<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);
if(!$symbUid) header('Location: ../../profile/index.php?refurl=../collections/editor/crowdsourcing/controlpanel.php?'.$_SERVER['QUERY_STRING']);

$collid= array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$pStatus = array_key_exists('pstatus',$_REQUEST)?$_REQUEST['pstatus']:'unprocessed';
$uid = array_key_exists('uid',$_REQUEST)?$_REQUEST['uid']:0;
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:100;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();
$csManager->setCollid($collid);
$projArr = $csManager->getProjectDetails();

$isEditor = 0; 
if($isAdmin){
	$isEditor = 1;
}
elseif($collId){
	if(array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
		$isEditor = 1;
	}
}

$statusStr = '';
if($action == 'addtoqueue'){
	$statusStr = $csManager->addToQueue();
	$action = '';
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Crowdsourcing Control Panel</title>
    <link type="text/css" href="../../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">
		function verifyProjForm(f){
			if(f.name.value == ""){
				alert("Project must have a name");
				return false;
			}
			return true
		}

		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="block"){
					ele.style.display="none";
		  		}
			 	else {
			 		ele.style.display="block";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="inline";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
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
		<a href="central.php">Crowdsourcing Source Board</a> &gt;&gt;
		<b>Crowdsourcing Control Panel</b>
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
		if($collid){
			if($projArr){
				?>
				<div style="float:right;"><a href="#" onclick="toggle('projectformDiv')"><img src="../../../images/edit.png" /></a></div>
				<div style="font-weight:bold;font-size:130%;"><?php echo $projArr['name']; ?></div>
				<div style="margin-left:15px;"><b>Instructions: </b><?php echo $projArr['instr']; ?></div>
				<?php if($projArr['url']) echo 'Training: <a href="'.$projArr['url'].'">'.$projArr['url'].'</a>'; ?>
				<div style="margin:15px;">
					<?php 
					if(!$action){
						?>
						<div style="font-weight:bold;text-decoration:underline">Total Counts:</div>
						<?php 
						$rsArr = $projArr['rs'];
						$unprocessedCnt = (isset($rsArr['unprocessed'])?$rsArr['unprocessed']:0);
						unset($rsArr['unprocessed']);
						$pendingCnt = (isset($rsArr['pending review'])?$rsArr['pending review']:0);
						unset($rsArr['pending review']);
						$reviewedCnt = (isset($rsArr['reviewed'])?$rsArr['reviewed']:0);
						unset($rsArr['reviewed']);
						if(isset($rsArr['closed'])) $reviewedCnt += $rsArr['closed'];
						unset($rsArr['closed']);
						$toAddCnt = (isset($rsArr['toadd'])?$rsArr['toadd']:0);
						unset($rsArr['toadd']); 
						?>
						<div style="margin:15px 0px 25px 15px;">
							<div>
								<b>Records in Queue:</b> 
								<?php
								if($unprocessedCnt){
									echo '<a href="../occurrencetabledisplay.php?csmode=1&occindex=0&displayquery=1&reset=1&collid='.$collid.'" target="_blank">'.$unprocessedCnt.'</a>';
								}
								else{
									echo 0;
								}
								?>
							</div>
							<div>
								<b>Pending Approval:</b> 
								<?php
								if($pendingCnt){ 
									echo $pendingCnt;
									echo ' (<a href="controlpanel.php?action=review&pstatus=pending&collid='.$collid.'">Review</a>)';
								} 
								else{
									echo 0;
								}
								?> 
							</div>
							<?php 
							foreach($rsArr as $psStr => $cnt){
								echo '<div>';
								echo '<b>'.$psStr.':</b> '.$cnt;
								echo ' (<a href="controlpanel.php?action=review&pstatus=other&collid='.$collid.'">Review</a>)';
								echo '</div>';
							}
							?>
							<div>
								<b>Closed (Approved):</b> 
								<?php
								if($reviewedCnt){
									echo $reviewedCnt;
									echo ' (<a href="controlpanel.php?action=review&pstatus=reviewed&collid='.$collid.'">Review</a>)';
								}
								else{
									echo 0;
								}
								?> 
							</div>
							<div>
								<b>Available to Add:</b> 
								<?php
								if($toAddCnt){
									echo $toAddCnt;
									echo ' (<a href="controlpanel.php?action=addtoqueue&collid='.$collid.'">Add to Queue</a>)';
								}
								else{
									echo 0;
								}
								?>
							</div>
						</div>
						<div style="font-weight:bold;text-decoration:underline">By User:</div>
						<div style="margin:15px 0px 25px 15px;">
							<table class="styledtable" style="width:500px;">
								<tr>
									<th>User</th>
									<th>Score</th>
									<th>Pending Review</th>
									<th>Other</th>
									<th>Approved</th>
								</tr>
								<?php 
								if(isset($projArr['ps'])){
									$psArr = $projArr['ps'];
									foreach($psArr as $username => $statsArr){
										echo '<tr>';
										//User
										$uid = $statsArr['uid'];
										unset($statsArr['uid']);
										echo '<td><a href="'.$uid.'">'.$username.'</a></td>';
										//Score
										echo '<td>'.$statsArr['score'].'</td>';
										unset($statsArr['score']);
										//Pending records
										$pendingCnt = (isset($statsArr['pending review'])?$statsArr['pending review']:0);
										unset($statsArr['pending review']);
										echo '<td>';
										echo $pendingCnt;
										if($pendingCnt) echo ' (<a href="controlpanel.php?action=review&pstatus=pending&collid='.$collid.'&uid='.$uid.'">Review</a>)';
										echo '</td>';
										//Other
										$closeCnt = (isset($statsArr['reviewed'])?$statsArr['reviewed']:0);
										unset($statsArr['reviewed']);
										$otherCnt = 0;
										foreach($statsArr as $rsStr => $cnt){
											if(is_numeric($cnt)) $otherCnt += $cnt;
										}
										echo '<td>';
										echo $otherCnt;
										echo '</td>';
										//Closed
										echo '<td>';
										echo $closeCnt;
										if($closeCnt) echo ' (<a href="controlpanel.php?action=review&pstatus=reveiwed&collid='.$collid.'&uid='.$uid.'">Review</a>)';
										echo '</td>';
										echo '</tr>';
									}
								}
								else{
									echo '<tr><td colspan="4">No records processed</td></tr>';
								}
								?>
							</table>
						</div>
						<?php 
					}
					elseif($action == 'editproject'){
						?>
						<form name="projeditform.php" action="controlpanel" method="post">
							<div style="margin:3px;">
								<b>Project Name:</b> 
								<input name="name" type="text" value="<?php echo $projArr['name']; ?>" />
							</div>
							<div style="margin:3px;">
								<b>General Instructions:</b> 
								<textarea name="instr"><?php echo $projArr['instr']; ?></textarea>
							</div>
							<div style="margin:3px;">
								<b>Training Url:</b> 
								<input name="url" type="text" value="<?php echo $projArr['url']; ?>" />
							</div>
							<div>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="formsubmit" type="submit" value="Create Project" />
							</div>
						</form>
						<?php 
					}
					elseif($action == 'review'){
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
							<div style="margin:20px;" style="width:90%;">
								<div style="font-weight:bold;font-size:120%;">Record Review</div>
								<div style="float:right;"><?php echo $navStr; ?></div>
								<div><b>Total Record Count:</b> <?php echo $totalCnt; ?></div>
								<table class="styledtable">
									<tr>
										<th><span title="Select All"><input name="selectall" type="checkbox" onselect="" /></span></th>
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
											<td>
												<input name="occ[]" type="checkbox" value="<?php echo $occid; ?>" />
											</td>
											<td>
												<a href="../occurrenceeditor.php?occid=<?php echo $occid; ?>" target="_blank"><?php echo $occid; ?></a>
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
								<div style="float:right;">
									<?php echo $navStr; ?>
								</div>
							</div>
							<?php 
						}
					}
					?>
				</div>
				<?php 
			}
			?>
			<div id="projectformDiv" style="display:<?php echo ($projArr?'none':'block'); ?>">
				<form name="projform" action="controlpanel.php" method="post" onsubmit="return verifyProjForm(this)">
					<div style="margin:3px;">
						<b>Project Name:</b> 
						<input name="name" type="text" value="" />
					</div>
					<div style="margin:3px;">
						<b>General Instructions:</b> 
						<textarea name="instr"></textarea>
					</div>
					<div style="margin:3px;">
						<b>Training Url:</b> 
						<input name="url" type="text" value="" />
					</div>
					<div>
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="formsubmit" type="submit" value="Create Project" />
					</div>
				</form>
			</div>
			<?php 
		}
		else{
			echo 'ERROR: collection id not supplied';
		}
		?>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>