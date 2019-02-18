<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
include_once($SERVER_ROOT.'/content/lang/profile/personalspecmenu.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$specHandler = new ProfileManager();

$collArr = array();
$collEditor = false;
if($SYMB_UID){
	$specHandler->setUid($SYMB_UID);
	$collArr = $specHandler->getPersonalCollectionArr();
}

$statusStr = '';
?>
<div style="margin:10px;">
<?php
if($SYMB_UID){
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
	$genArr = array();
	if(array_key_exists('General Observations',$collArr)){
        $collEditor = true;
	    $genArr = $collArr['General Observations'];
		foreach($genArr as $collId => $cName){
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend style="font-weight:bold;"><b><?php echo $cName; ?></b></legend>
				<div style="margin-left:10px">
					Total Record Count: <?php echo $specHandler->getPersonalOccurrenceCount($collId); ?>
				</div>
				<ul>
					<li>
						<a href="../collections/editor/occurrencetabledisplay.php?collid=<?php echo $collId.'&ouid='.$SYMB_UID; ?>">
							Display All Records
						</a>
					</li>
					<li>
						<a href="../collections/editor/occurrencetabledisplay.php?collid=<?php echo $collId.'&ouid='.$SYMB_UID; ?>&displayquery=1">
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
					<li>
						<a href="../collections/editor/editreviewer.php?display=1&collid=<?php echo $collId; ?>">
							Review/Verify Occurrence Edits
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
					<!--
					<li>
						<a href="../collections/cleaning/index.php?collid=<?php echo $collId; ?>">
							Data Cleaning Module
						</a>
					</li>
					 -->
				</ul>
			</fieldset>
			<?php
		}
	}
	if(array_key_exists('Preserved Specimens',$collArr)){
        $collEditor = true;
	    $cArr = $collArr['Preserved Specimens'];
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Collection Management</b></legend>
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
	if(array_key_exists('Observations',$collArr)){
        $collEditor = true;
	    $cArr = $collArr['Observations'];
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Observation Project Management</b></legend>
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
	if($genArr && isset($USER_RIGHTS['CollAdmin'])){
        $collEditor = true;
	    $genAdminArr = array_intersect_key($genArr,array_flip($USER_RIGHTS['CollAdmin']));
		if($genAdminArr){
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend style="font-weight:bold;"><b>General Observation Administration</b></legend>
				<ul>
					<?php
					foreach($genAdminArr as $id => $name){
						echo '<li><a href="../collections/misc/collprofiles.php?collid='.$id.'&emode=1">'.$name.'</a></li>';
					}
					?>
				</ul>
			</fieldset>
			<?php
		}
	}
    if(!$collEditor){
        echo '<div>'.$LANG['LA_GES'].' (<a href="mailto:'.$adminEmail.'">'.$adminEmail.'</a>) '.$LANG['FOR_ACT_CHAR'].'to activate this feature.</div>';
    }
}
?>
</div>
