<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);
if(!$symbUid) header('Location: ../../../profile/index.php?refurl=../collections/editor/crowdsource/controlpanel.php?'.$_SERVER['QUERY_STRING']);

$collid= array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$omcsid= array_key_exists('omcsid',$_REQUEST)?$_REQUEST['omcsid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();
$csManager->setCollid($collid);
if(!$omcsid) $omcsid = $csManager->getOmcsid();

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
elseif($action == 'Edit Project'){
	$statusStr = $csManager->editProject($omcsid,$_POST['instr'],$_POST['url']);
}
elseif($action == 'Create Project'){
	$statusStr = $csManager->createProject($collid,$_POST['instr'],$_POST['url']);
	$omcsid = $csManager->getOmcsid();
}

$projArr = $csManager->getProjectDetails();
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
		<a href="central.php">Crowdsourcing Score Board</a> &gt;&gt;
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
			if(!$projArr || !$omcsid){
				?>
				<div style="clear:both;font-weight:bold;">
					There are currently no crowdsourcing projects for this collection. To begin crowdsourcing, please create a project in the box below.
				</div>
				<?php
			}
			if($omcsid) echo '<div style="float:right;"><a href="#" onclick="toggle(\'projFormDiv\')"><img src="../../../images/edit.png" /></a></div>';
			?>
			<div style="font-weight:bold;font-size:130%;"><?php echo (($omcsid && $projArr)?$projArr['name']:''); ?></div>
			<div id="projFormDiv" style="display:<?php echo ($omcsid?'none':'block'); ?>">
				<fieldset style="margin:15px;">
					<legend><b><?php echo ($omcsid?'Edit Project':'Add New Project'); ?></b></legend>
					<form name="projform.php" action="controlpanel.php" method="post">
						<div style="margin:3px;">
							<b>General Instructions:</b><br/> 
							<textarea name="instr" style="width:500px;height:100px;"><?php echo (($omcsid && $projArr)?$projArr['instr']:''); ?></textarea>
						</div>
						<div style="margin:3px;">
							<b>Training Url:</b><br/>
							<input name="url" type="text" value="<?php echo (($omcsid && $projArr)?$projArr['url']:''); ?>" style="width:500px;" />
						</div>
						<div>
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="omcsid" type="hidden" value="<?php echo $omcsid; ?>" />
							<input name="action" type="submit" value="<?php echo ($omcsid?'Edit Project':'Create Project'); ?>" />
						</div>
					</form>
				</fieldset>
			</div>
			<?php 
			if($omcsid){
				?>
				<div style="margin-left:15px;"><b>Instructions: </b><?php echo $projArr['instr']; ?></div>
				<?php if($projArr['url']) echo 'Training: <a href="'.$projArr['url'].'">'.$projArr['url'].'</a>'; ?>
				<div style="margin:15px;">
					<?php 
					if($statsArr = $csManager->getProjectStats()){
						?>
						<div style="font-weight:bold;text-decoration:underline">Total Counts:</div>
						<?php 
						$rsArr = $statsArr['rs'];
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
									echo ' (<a href="review.php?pstatus=pending&collid='.$collid.'">Review</a>)';
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
								echo ' (<a href="review.php?pstatus=other&collid='.$collid.'">Review</a>)';
								echo '</div>';
							}
							?>
							<div>
								<b>Closed (Approved):</b> 
								<?php
								if($reviewedCnt){
									echo $reviewedCnt;
									echo ' (<a href="review.php?pstatus=reviewed&collid='.$collid.'">Review</a>)';
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
									echo ' (<a href="controlpanel.php?action=addtoqueue&collid='.$collid.'&omcsid='.$omcsid.'">Add to Queue</a>)';
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
								if(isset($statsArr['ps'])){
									$psArr = $statsArr['ps'];
									foreach($psArr as $username => $statsArr){
										echo '<tr>';
										//User
										$uid = $statsArr['uid'];
										unset($statsArr['uid']);
										echo '<td>'.$username.'</td>';
										//Score
										echo '<td>'.$statsArr['score'].'</td>';
										unset($statsArr['score']);
										//Pending records
										$pendingCnt = (isset($statsArr['pending review'])?$statsArr['pending review']:0);
										unset($statsArr['pending review']);
										echo '<td>';
										echo $pendingCnt;
										if($pendingCnt) echo ' (<a href="review.php?pstatus=pending&collid='.$collid.'&uid='.$uid.'">Review</a>)';
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
										if($closeCnt) echo ' (<a href="review.php?pstatus=reviewed&collid='.$collid.'&uid='.$uid.'">Review</a>)';
										echo '</td>';
										echo '</tr>';
									}
								}
								else{
									echo '<tr><td colspan="5">No records processed</td></tr>';
								}
								?>
							</table>
						</div>
						<?php 
					}
					?>
				</div>
				<?php 
			}
			
			
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