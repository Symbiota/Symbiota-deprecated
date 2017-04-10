<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$menu = array_key_exists('menu',$_REQUEST)?$_REQUEST['menu']:'';

$tabIndex = 4;

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
 	$isEditor = true;
}
?>
<div id="innertext" style="background-color:white;">
	<div style="float:right;width:165px;">
		<fieldset>
			<legend><b>Sub-Menu</b></legend>
			<ul>
				<li><a href="index.php?tabindex=<?php echo $tabIndex.'&collid='.$collid; ?>">General Stats</a><br/></li>
				<li><a href="index.php?menu=user&tabindex=<?php echo $tabIndex.'&collid='.$collid; ?>">User Stats</a><br/></li>
				<li><a href="index.php?menu=issues&tabindex=<?php echo $tabIndex.'&collid='.$collid; ?>">Possible Issues</a><br/></li>
			</ul>
		</fieldset>
	</div>
	<?php 
	if($isEditor){
		$urlBase = '&csmode=0&occindex=0&occid=&q_processingstatus=&q_recordedby=&q_recordnumber=&q_eventdate=&q_catalognumber=&q_othercatalognumbers='.
			'&q_observeruid=&q_datelastmodified=&q_imgonly=&q_withoutimg=&q_customfield1=&q_customtype1=EQUALS&q_customvalue1='.
			'&q_customfield2=&q_customtype2=EQUALS&q_customvalue2=&q_customfield3=&q_customtype3=&q_customvalue3=';
		$eUrl = '../editor/occurrenceeditor.php?collid='.$collid; 
		$beUrl = '../editor/occurrencetabledisplay.php?collid='.$collid;
		if($menu == 'user'){
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
							$eUrlInner = $eUrl.'&q_recordenteredby='.$username.str_replace(array('&q_recordenteredby='),'',$urlBase); 
							$beUrlInner = $beUrl.'&q_recordenteredby='.$username.'&bufieldname=processingstatus'.str_replace(array('&q_recordenteredby='),'',$urlBase);
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
		elseif($menu == 'issues'){
			$issueArr = $procManager->getIssues();
			echo '<div style="margin:10px;height:400px;">';
			if($issueArr['loc']){
				$eUrl .= str_replace(array('&q_customfield1=','&q_customfield2=','&q_customtype1=EQUALS','&q_customtype2=EQUALS'),'',$urlBase).
					'&q_processingstatus=unprocessed&q_customfield1=locality&q_customtype1=NOTNULL&q_customfield2=stateProvince&q_customtype2=NOTNULL';
				$beUrl .= str_replace(array('&q_customfield1=','&q_customfield2=','&q_customtype1=EQUALS','&q_customtype2=EQUALS'),'',$urlBase).
					'&q_processingstatus=unprocessed&bufieldname=processingstatus&buoldvalue=unprocessed'.
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
		else{
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
						$eUrl1 = $eUrl.str_replace(array('&q_withoutimg='),'',$urlBase).'&q_withoutimg=1';
						$beUrl1 = $beUrl.str_replace(array('&q_withoutimg='),'',$urlBase).'&q_withoutimg=1';
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
							$eUrl2 = $eUrl.str_replace(array('&q_withoutimg=','&q_processingstatus='),'',$urlBase).'&q_processingstatus=unprocessed&q_withoutimg=1';
							$beUrl2 = $beUrl.str_replace(array('&q_withoutimg=','&q_processingstatus='),'',$urlBase).'&q_processingstatus=unprocessed&q_withoutimg=1';
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
							$eUrl3 = $eUrl.str_replace(array('&q_processingstatus=','q_customtype1=','&q_customfield1=','q_customtype2=','&q_customfield2='),'',$urlBase).
								'&q_processingstatus=unprocessed&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=sciname&q_customtype2=NULL';
							$beUrl3 = $beUrl.str_replace(array('&q_processingstatus=','q_customtype1=','&q_customfield1=','q_customtype2=','&q_customfield2='),'',$urlBase).
								'&q_processingstatus=unprocessed&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=sciname&q_customtype2=NULL';
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
								$eUrl4 = $eUrl.str_replace(array('&q_processingstatus='),'',$urlBase).'&q_processingstatus='.$processingStatus;
								$beUrl4 = $beUrl.str_replace(array('&q_processingstatus='),'',$urlBase).'&q_processingstatus='.$processingStatus;
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
	}
	?>
</div>
