<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$menu = array_key_exists('menu',$_REQUEST)&&$_REQUEST['menu']?$_REQUEST['menu']:0;
$formAction = array_key_exists('formaction',$_REQUEST)?$_REQUEST['formaction']:0;

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
		$reportTypes = array(0 => 'General Stats', 1 => 'User Stats', 2 => 'Possible Issues');
		?>
		<form name="filterForm" action="index.php" method="get">
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
				$uid = (isset($_GET['uid'])?$_GET['uid']:'');
				$interval= (isset($_GET['interval'])?$_GET['interval']:'day');
				$startDate = (isset($_GET['startdate'])?$_GET['startdate']:'');
				$endDate = (isset($_GET['enddate'])?$_GET['enddate']:'');
				$processingStatus = (isset($_GET['processingstatus'])?$_GET['processingstatus']:'IGNORE');
				?>
				<fieldset style="padding:15px;width:400px">
					<legend><b>Filter</b></legend>
					<form name="userStatsFilterForm" method="get" action="index.php">
						<div style="margin:2px">
							Editors: 
							<select name="uid">
								<option value="0">Show all users</option>
								<?php 
								$userArr = $procManager->getUserList();
								foreach($userArr as $id => $uname){
									echo '<option value="'.$id.'" '.($uid==$id?'SELECTED':'').'>'.$uname.'</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:2px">
							Interval: 
							<select name="interval">
								<option value="hour" <?php echo ($interval=='hour'?'SELECTED':''); ?>>Hour</option>
								<option value="day" <?php echo ($interval=='day'?'SELECTED':''); ?>>Day</option>
								<option value="week" <?php echo ($interval=='week'?'SELECTED':''); ?>>Week</option>
								<option value="month" <?php echo ($interval=='month'?'SELECTED':''); ?>>Month</option>
							</select>
						</div>
						<div style="margin:2px">
							Date: <input name="startdate" type="date" value="<?php echo $startDate; ?>" /> 
							to <input name="enddate" type="date" value="<?php echo (isset($_GET['enddate'])?$_GET['enddate']:''); ?>" />
						</div>
						<div style="margin:2px">
							Processing Status: 
							<select name="processingstatus">
								<option value="0">Show all</option>
								<option value="IGNORE" <?php echo ($processingStatus=='IGNORE'?'SELECTED':''); ?>>Ignore Processing Status</option>
								<option value="IGNORE">-----------------------</option>
								<option value="ISNULL" <?php echo ($processingStatus=='ISNULL'?'SELECTED':''); ?>>Processing Status Not Set</option>
								<?php 
								$psArr = $procManager->getProcessingStatusList();
								foreach($psArr as $psValue){
									echo '<option value="'.$psValue.'" '.($processingStatus==$psValue?'SELECTED':'').'>'.$psValue.'</option>';
								}
								?>
							</select>
						</div>
						<div style="float:right;margin-top:25px;">
							<?php 
							$editReviewUrl = '../editor/editreviewer.php?collid='.$collid.'&editor='.$uid.'&startdate='.$startDate.'&enddate='.$endDate;
							echo '<a href="'.$editReviewUrl.'" target="_blank">Visit Edit Reviewer</a>';
							?>
						</div>
						<div style="margin-top:15px">
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="menu" type="hidden" value="1" />
							<input name="tabindex" type="hidden" value="<?php echo $tabIndex; ?>" />
							<button name="formaction" type="submit" value="displayReport">Display Report</button>
						</div>
					</form>
				</fieldset>
				<?php
				if($formAction && $formAction == 'displayReport'){
					echo '<table class="styledtable" style="width:500px">';
					echo '<tr><th>Time Period</th>';
					echo '<th>User</th>';
					if($processingStatus!='IGNORE') echo '<th>Processing Status</th>';
					echo '<th>Counts</th></tr>';
					$repArr = $procManager->getFullStatReport($_GET);
					if($repArr){
						$orderArr = array('SKIP','unprocessed','stage 1','stage 2','stage 3','pending duplicate','pending review-nfn','pending review','expert required','reviewed','closed','');
						//$editReviewUrl = '../editor/editreviewer.php?collid='.$collid.'&editor='.$uid.'&startdate='.$startDate.'&enddate='.$endDate;
						foreach($repArr as $t => $arr2){
							foreach($arr2 as $u => $arr3){
								foreach($orderArr as $o){
									if(array_key_exists($o, $arr3)){
										echo '<tr><td>'.$t.'</td>';
										echo '<td>'.$u.'</td>';
										if($o != 'SKIP') echo '<td>'.$o.'</td>';
										//echo '<td>'.$arr3[$o].' <a href="'.$editReviewUrl.'" target="_blank" style="float:right;"><img src="../../images/edit.png" style="width:13px" /></a></td>';
										echo '<td>'.$arr3[$o].'</td>';
										echo '</tr>';
										if($o == 'SKIP') break;
									}
								}
							}
						}
					}
					else{
						echo '<div style="font-weight:bold">No Records Returned</div>';
					}
					echo '</table>';
				}
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
			?>
		</fieldset>
		<?php 
	}
	?>
</div>