<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PersonalSpecimenManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$specHandler = new PersonalSpecimenManager();

$collArr = array();
$isEditor = 0;
if($symbUid){
	$specHandler->setUid($symbUid);
	$collArr = $specHandler->getObservationArr();
	if(!$collId && $collArr) $collId = current(array_keys($collArr));
	if($collId){
		$specHandler->setCollId($collId);
		$specHandler->setCollectionMetadata();
		if($isAdmin	|| (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))
			|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
			$isEditor = 1;
		}
	}
}

$statusStr = '';
if($isEditor){
	if($formSubmit){
		if($formSubmit == ''){

		}
		elseif($formSubmit == ''){
			
		}
		
	}
}

?>
<div style="margin:10px;">

<?php 
if($symbUid){
	//Collection is defined and User is logged-in and have permissions
	if($statusStr){
		?>
		<hr/>
		<div style="margin:15px;color:red;">
			<?php echo $statusStr; ?>
		</div>
		<hr/>
		<?php 
	}
	if($collArr){
		if(count($collArr) > 1){
			?>
			<div style="float:right;">
				<form name="obtionsform" action="viewprofile.php" method="post">
					<fieldset>
						<legend><b>Project:</b></legend> 
						<select name="collid" onchange="this.form.submit()">
							<?php 
							foreach($collArr as $k => $v){
								echo '<option value="'.$k.'" '.($collId==$k?'SELECTED':'').'>'.$v.'</option>'."<br/>";
							}
							?>
						</select>
					</fieldset>
				</form>
			</div>
			<?php
		}
		?>
		<div style="clear:both;">
			<fieldset style="margin:15px;">
				<legend style="font-weight:bold;"><b><?php echo $collArr[$collId]; ?></b></legend>
				<div style="margin-left:10px">
					Total Record Count: <?php echo $specHandler->getRecordCount(); ?>
				</div>
				<ul>
					<li>
						<a href="../collections/editor/occurrencetabledisplay.php?collid=<?php echo $collId.'&ouid='.$symbUid; ?>">
							Display All Records
						</a>
					</li>
					<li>
						<a href="../collections/editor/occurrencetabledisplay.php?collid=<?php echo $collId.'&ouid='.$symbUid; ?>&displayquery=1">
							Search Records
						</a>
					</li>
					<li>
						<a href="../collections/editor/occurrenceeditor.php?gotomode=1&collid=<?php echo $collId; ?>">
							Add a New Record
						</a>
					</li>
					<li>
						<a href="../collections/datasets/index.php?collid=<?php echo $collId; ?>">
							Print Labels
						</a>
					</li>
					
					<?php
					if(stripos($specHandler->getCollType(),'observations') !== 'false'){
						?>
						<li>
							<a href="../collections/editor/observationsubmit.php?collid=<?php echo $collId; ?>">
								Submit image vouchered observation
							</a>
						</li>
						<?php
					}
					?>
					<!-- 
					<li>Import csv file</li>
					 -->
					<li>
						<a href="#" onclick="newWindow = window.open('personalspecbackup.php?collid=<?php echo $collId; ?>','bucollid','scrollbars=1,toolbar=1,resizable=1,width=400,height=200,left=20,top=20');">
							Backup file download (CSV extract)
						</a>
					</li>
				</ul>
			</fieldset>
		</div>	
		<?php
	}
	else{
		echo '<div>Personal specimen management has not been setup for your login. Please contact the site administrator (<a href="mailto:'.$adminEmail.'">'.$adminEmail.'</a>) to active this feature.</div>';
	}
}
else{
	echo '<h2>Please <a href="../profile/index.php?&refurl='.$clientRoot.'/profile/personalspec.php?collid='.$collId.'">login</a></h2>';
}
?>	
</div>

