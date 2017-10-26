<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$menu = array_key_exists('menu',$_REQUEST)&&$_REQUEST['menu']?$_REQUEST['menu']:0;

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);
$tabIndex = 4;

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}
?>
<div id="innertext" style="background-color:white;">
	<?php 
	if($isEditor){
		$reportTypes = array(0 => 'General Stats', 1 => 'User Stats', 2 => 'Possible Issues', 3 => 'User Stats - Old');
		?>
		<form name="filterForm" action="index.php" method="post">
			<b>Report Type:</b> 
			<select name="menu" onchange="this.form.submit()">
				<?php 
				foreach($reportTypes as $k => $v){
					echo '<option value="'.$k.'" '.($menu==$k?'SELECTED':'').'>'.$v.'</option>';
				}
				?>
			</select>
			<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
			<input name="tabindex" type="hidden" value="<?php echo $tabIndex; ?>" />
		</form>
		
		<fieldset style="padding:15px">
			<legend><b><?php echo $reportTypes[$menu]; ?></b></legend>
			<?php 
			$urlBase = '&occindex=0&q_catalognumber=';
			$eUrl = '../editor/occurrenceeditor.php?collid='.$collid; 
			$beUrl = '../editor/occurrencetabledisplay.php?collid='.$collid;
			if(!$menu){
				//General stats
				$statsArr = $procManager->getProcessingStats();
				?>
				<div style="margin:10px;height:400px;">
					<div style="margin:5px;">
						<b>Total Specimens:</b> 
						<?php 
						echo $statsArr['total'];
						if($statsArr['total']){ 
							echo '<span style="margin-left:10px;"><a href="'.$eUrl.$urlBase.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="'.$beUrl.$urlBase.'" target="_blank" title="Editor in Table View"><img src="../../images/list.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="../misc/collbackup.php?collid='.$collid.'" target="_blank" title="Download Full Data"><img src="../../images/dl.png" style="width:13px;" /></a></span>';
						}
						?>
					</div>
					<div style="margin:5px;">
						<b>Specimens without Images:</b> 
						<?php 
						echo $statsArr['noimg'];
						if($statsArr['noimg']){ 
							$eUrl1 = $eUrl.$urlBase.'&q_withoutimg=1';
							$beUrl1 = $beUrl.$urlBase.'&q_withoutimg=1';
							echo '<span style="margin-left:10px;"><a href="'.$eUrl1.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="'.$beUrl1.'" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=dlnoimg&tabindex='.$tabIndex.'&collid='.$collid.'" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:13px;" /></a></span>';
						}
						?>
					</div>
					<?php 
					if($statsArr['unprocnoimg']){
						?>
						<div style="margin:5px;">
							<b>Unprocessed Specimens without Images:</b> 
							<?php 
							echo $statsArr['unprocnoimg'];
							if($statsArr['unprocnoimg']){ 
								$eUrl2 = $eUrl.$urlBase.'&q_processingstatus=unprocessed&q_withoutimg=1';
								$beUrl2 = $beUrl.$urlBase.'&q_processingstatus=unprocessed&q_withoutimg=1';
								echo '<span style="margin-left:10px;"><a href="'.$eUrl2.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="'.$beUrl2.'" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=unprocnoimg&tabindex='.$tabIndex.'&collid='.$collid.'" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:13px;" /></a></span>';
							}
							?>
						</div>
						<?php 
					}
					if($statsArr['noskel']){
						?>
						<div style="margin:5px;">
							<b>Unprocessed Specimens without Skeletal Data:</b> 
							<?php 
							echo $statsArr['noskel'];
							if($statsArr['noskel']){ 
								$eUrl3 = $eUrl.$urlBase.'&q_processingstatus=unprocessed&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=sciname&q_customtype2=NULL';
								$beUrl3 = $beUrl.$urlBase.'&q_processingstatus=unprocessed&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=sciname&q_customtype2=NULL';
								echo '<span style="margin-left:10px;"><a href="'.$eUrl3.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="'.$beUrl3.'" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=noskel&tabindex='.$tabIndex.'&collid='.$collid.'" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:14px;" /></a></span>';
							}
							?>
						</div>
						<?php 
					}
					?>
					<div style="margin:20px 5px;">
						<table class="styledtable" style="font-family:Arial;font-size:12px;width:400px;">
							<tr><th>Processing Status</th><th>Count</th></tr>
							<?php 
							foreach($statsArr['ps'] as $processingStatus => $cnt){
								if(!$processingStatus) $processingStatus = 'No Status Set';
								echo '<tr>';
								echo '<td>'.$processingStatus.'</td>';
								echo '<td>';
								echo $cnt;
								if($cnt){
									$eUrl4 = $eUrl.$urlBase.'&q_processingstatus='.$processingStatus;
									$beUrl4 = $beUrl.$urlBase.'&q_processingstatus='.$processingStatus;
									echo '<span style="margin-left:10px;"><a href="'.$eUrl4.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
									echo '<span style="margin-left:10px;"><a href="'.$beUrl4.'" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								}
								echo '</td>';
								echo '</tr>';
							}
							?>
						</table>
					</div>
				</div>
				<?php 
			}
			elseif($menu == 1){
				?>
				<fieldset>
					<legend><b>User Stats Filter</b></legend>
					<form name="userStatsFilterForm" method="post" action="index.php">
						<select name="uid">
							<option value="0">Show all users</option>
							<?php 
							$userArr = $procManager->getUserList();
							foreach($userArr as $id => $uname){
								echo '<option value="'.$id.'">'.$uname.'<option>';
							}
							?>
						</select>
						
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="menu" type="hidden" value="1" />
						<input name="tabindex" type="hidden" value="<?php echo $tabIndex; ?>" />
					</form>
				</fieldset>
				<?php 
			}
			elseif($menu == 2){
				//Possible issues
				$issueArr = $procManager->getIssues();
				echo '<div style="margin:10px;height:400px;">';
				if($issueArr['loc']){
					$eUrl .= $urlBase.'&q_processingstatus=unprocessed&q_customfield1=locality&q_customtype1=NOTNULL&q_customfield2=stateProvince&q_customtype2=NOTNULL';
					$beUrl .= $urlBase.'&q_processingstatus=unprocessed&bufieldname=processingstatus&buoldvalue=unprocessed'.
							'&q_customfield1=locality&q_customtype1=NOTNULL&q_customfield2=stateProvince&q_customtype2=NOTNULL';
					echo '<b>Mark as unprocessed but apparently with data:</b> ';
					echo $issueArr['loc'];
					echo '<span style="margin-left:10px;"><a href="'.$eUrl.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
					echo '<span style="margin-left:10px;"><a href="'.$beUrl.'" target="_blank" title="Batch Edit Records"><span style="font-size:70%;">batch</span><img src="../../images/list.png" style="width:12px;" /></a></span>';
				}
				else{
					echo '<div><b>No issues identified</b></div>';
				}
				echo '</div>';
			}
			elseif($menu == 3){
				//User Stats - old
				?>
				<div style="margin:15px 0px 25px 15px;">
					<table class="styledtable" style="font-family:Arial;font-size:12px;width:500px;">
						<tr>
							<th>User</th>
							<th>Processing Status</th>
							<th>Count</th>
						</tr>
						<?php 
						if($userStats = $procManager->getUserStats()){
							$orderArr = array('unprocessed','stage 1','stage 2','stage 3','pending duplicate','pending review-nfn','pending review','expert required','reviewed','closed','empty status');
							foreach($userStats as $username => $psArr){
								$eUrlInner = $eUrl.'&q_recordenteredby='.$username.$urlBase; 
								$beUrlInner = $beUrl.'&q_recordenteredby='.$username.'&bufieldname=processingstatus'.$urlBase;
								foreach($orderArr as $ps){
									if(array_key_exists($ps,$psArr)){
										echo '<tr>';
										echo '<td>'.$username.'</td>';
										echo '<td>'.$ps.'</td>';
										echo '<td>';
										echo $psArr[$ps];
										if($psArr[$ps]){
											$eUrlInner2 = $eUrlInner.'&q_processingstatus='.$ps;
											$beUrlInner2 = $beUrlInner.'&q_processingstatus='.$ps.'&buoldvalue='.$ps;
											echo '<span style="margin-left:10px;"><a href="'.$eUrlInner2.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
											echo '<span style="margin-left:10px;"><a href="'.$beUrlInner2.'" target="_blank" title="Batch Edit Records"><span style="font-size:70%;">batch</span><img src="../../images/list.png" style="width:12px;" /></a></span>';
										}
										echo '</td>';
										echo '</tr>';
										unset($psArr[$ps]);
									}
								}
								foreach($psArr as $pStatus => $cnt){
									if($pStatus){
										$eUrlInner3 = $eUrlInner.'&q_processingstatus='.$pStatus;
										$beUrlInner3 = $beUrlInner.'&q_processingstatus='.$pStatus.'&buoldvalue='.$pStatus;
									}
									else{
										$eUrlInner3 = $eUrlInner.'&q_processingstatus=isnull';
										$beUrlInner3 = $beUrlInner.'&q_processingstatus=isnull';
										$pStatus = 'Not Set';
									}
									echo '<tr>';
									echo '<td>'.$username.'</td>';
									echo '<td>'.$pStatus.'</td>';
									echo '<td>';
									echo $cnt;
									if($cnt){
										echo '<span style="margin-left:10px;"><a href="'.$eUrlInner3.'" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
										echo '<span style="margin-left:10px;"><a href="'.$beUrlInner3.'" target="_blank" title="Batch Edit Records"><span style="font-size:70%;">batch</span><img src="../../images/list.png" style="width:12px;" /></a></span>';
									}
									echo '</td>';
									echo '</tr>';
								}
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
		</fieldset>
		<?php 
	}
	?>
</div>