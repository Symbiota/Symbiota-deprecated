<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);

$collid= array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$omcsid= array_key_exists('omcsid',$_REQUEST)?$_REQUEST['omcsid']:0;

$csManager = new OccurrenceCrowdSource();
$csManager->setCollid($collid);
if(!$omcsid) $omcsid = $csManager->getOmcsid();

$isEditor = 0; 
if($IS_ADMIN){
	$isEditor = 1;
}
elseif($collId){
	if(array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
		$isEditor = 1;
	}
}

$statusStr = '';
$projArr = $csManager->getProjectDetails();
?>
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
		if($isEditor && $collid && $omcsid){
			?>
			<div style="float:right;"><a href="#" onclick="toggle('projFormDiv')"><img src="../../images/edit.png" /></a></div>
			<div style="font-weight:bold;font-size:130%;"><?php echo (($omcsid && $projArr)?$projArr['name']:''); ?></div>
			<div>
				This module can be used to submit and manage &quot;unprocessed&quot; for data entry by the  
				general public. For more information, see the <a href="http://symbiota.org/tiki/tiki-index.php?page=Crowdsourcing">Symbiota documentation on crowdsourcing</a>.</div>
			</div>
			<div id="projFormDiv" style="display:none">
				<fieldset style="margin:15px;">
					<legend><b>Edit Project</b></legend>
					<form name="csprojform" action="index.php" method="post">
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
							<input name="tabindex" type="hidden" value="2" />
							<input name="submitaction" type="submit" value="Edit Crowdsource Project" />
						</div>
					</form>
				</fieldset>
			</div>
			<?php 
			if($projArr['instr']) echo '<div style="margin-left:15px;"><b>Instructions: </b>'.$projArr['instr'].'</div>';
			if($projArr['url']) echo '<div style="margin-left:15px;"><b>Training:</b> <a href="'.$projArr['url'].'">'.$projArr['url'].'</a></div>'; 
			?>
			<div style="margin:15px;">
				<?php 
				if($statsArr = $csManager->getProjectStats()){
					?>
					<div style="font-weight:bold;text-decoration:underline">Total Counts:</div>
					<div style="margin:15px 0px 25px 15px;">
						<div>
							<b>Records in Queue:</b> 
							<?php
							$unprocessedCnt = 0;
							if(isset($statsArr['rs'][0])) $unprocessedCnt = $statsArr['rs'][0];
							if($unprocessedCnt){
								echo '<a href="../editor/occurrencetabledisplay.php?csmode=1&occindex=0&displayquery=1&reset=1&collid='.$collid.'" target="_blank">'.$unprocessedCnt.'</a>';
							}
							?>
						</div>
						<div>
							<b>Pending Approval:</b> 
							<?php
							$pendingCnt = 0;
							if(isset($statsArr['rs'][5])) $pendingCnt = $statsArr['rs'][5];
							echo $pendingCnt;
							if($pendingCnt){ 
								echo ' (<a href="crowdsource/review.php?rstatus=5&collid='.$collid.'">Review</a>)';
							} 
							?> 
						</div>
						<div>
							<b>Closed (Approved):</b> 
							<?php
							$reviewedCnt = 0;
							if(isset($statsArr['rs'][10])) $reviewedCnt = $statsArr['rs'][10];
							echo $reviewedCnt;
							if($reviewedCnt){
								echo ' (<a href="crowdsource/review.php?rstatus=10&collid='.$collid.'">Review</a>)';
							}
							?> 
						</div>
						<div>
							<b>Available to Add:</b> 
							<?php
							echo $statsArr['rs']['toadd'];
							if($statsArr['rs']['toadd']){
								echo ' (<a href="index.php?submitaction=addtoqueue&tabindex=2&collid='.$collid.'&omcsid='.$omcsid.'">Add to Queue</a>)';
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
								<th>Approved</th>
							</tr>
							<?php 
							if(isset($statsArr['ps'])){
								$psArr = $statsArr['ps'];
								foreach($psArr as $uid => $uArr){
									echo '<tr>';
									echo '<td>'.$uArr['name'].'</td>';
									echo '<td>'.$uArr['score'].'</td>';
									$pendingCnt = (isset($uArr[5])?$uArr[5]:0);
									echo '<td>';
									echo $pendingCnt;
									if($pendingCnt) echo ' (<a href="crowdsource/review.php?rstatus=5&collid='.$collid.'&uid='.$uid.'">Review</a>)';
									echo '</td>';
									//Closed
									$closeCnt = (isset($uArr[10])?$uArr[10]:0);
									echo '<td>';
									echo $closeCnt;
									if($closeCnt) echo ' (<a href="crowdsource/review.php?rstatus=10&collid='.$collid.'&uid='.$uid.'">Review</a>)';
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
					<div style="clear:both;margin-top:50px;font-weight:bold;">
						Visit <a href="crowdsource/central.php">Source Board</a>
					</div>
					<?php 
				}
				?>
			</div>
			<?php 
		}
		else{
			echo 'ERROR: collection id not supplied';
		}
		?>
	</div>
