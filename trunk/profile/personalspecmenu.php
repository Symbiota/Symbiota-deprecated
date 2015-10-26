<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$specHandler = new ProfileManager();

$collArr = array();
if($symbUid){
	$specHandler->setUid($symbUid);
	$collArr = $specHandler->getPersonalCollectionArr();
}

$statusStr = '';
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
	if(array_key_exists('observation',$collArr)){
		$genArr = $collArr['observation'];
		foreach($genArr as $collId => $cName){
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend style="font-weight:bold;"><b><?php echo $cName; ?></b></legend>
				<div style="margin-left:10px">
					Total Record Count: <?php echo $specHandler->getPersonalOccurrenceCount($collId); ?>
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
						<a href="../collections/reports/labelmanager.php?collid=<?php echo $collId; ?>">
							Print Labels
						</a>
					</li>
					<li>
						<a href="../collections/editor/observationsubmit.php?collid=<?php echo $collId; ?>">
							Submit image vouchered observation
						</a>
					</li>
					<!-- 
					<li>Import csv file</li>
					 -->
					<li>
						<a href="#" onclick="newWindow = window.open('personalspecbackup.php?collid=<?php echo $collId; ?>','bucollid','scrollbars=1,toolbar=1,resizable=1,width=400,height=200,left=20,top=20');">
							Backup file download (CSV extract)
						</a>
					</li>
					<li>
						<a href="../collections/editor/occurrencecleaner.php?collid=<?php echo $collId; ?>">
							Data Cleaning Module
						</a>
					</li>
				</ul>
			</fieldset>
			<?php
		}
	}
	else{
		echo '<div>Personal specimen management has not been setup for your login. Please contact the site administrator (<a href="mailto:'.$adminEmail.'">'.$adminEmail.'</a>) to activate this feature.</div>';
	}
	if(array_key_exists('preserved specimens',$collArr)){
		$cArr = $collArr['preserved specimens'];
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Collection Management</b></legend>
			<div>
				List of collections to which you have explicit editing rights. 
				Click a collection to be taken to the managment menu for that collection.   
			</div>
			<ul>
				<?php 
				foreach($cArr as $collId => $cName){
					echo '<li><a href="../collections/misc/collprofiles.php?collid='.$collId.'&emode=1">'.$cName.'</a></li>';
				}
				?>
			</ul>
		</fieldset>
		<?php 
	}
	if(array_key_exists('observations',$collArr)){
		$cArr = $collArr['observations'];
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Observation Project Management</b></legend>
			<div>
				List of observation projects to which you have explicit editing rights. 
			</div>
			<ul>
				<?php 
				foreach($cArr as $collId => $cName){
					echo '<li><a href="../collections/misc/collprofiles.php?collid='.$collId.'&emode=1">'.$cName.'</a></li>';
				}
				?>
			</ul>
		</fieldset>
		<?php 
	}
}
else{
	echo '<h2>Please <a href="../profile/index.php?&refurl='.$clientRoot.'/profile/personalspec.php?collid='.$collId.'">login</a></h2>';
}
?>	
</div>