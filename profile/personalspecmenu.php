<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$specHandler = new ProfileManager();
$specHandler->setUid($SYMB_UID);

$genArr = array();
$cArr = array();
$oArr = array();
$collArr = $specHandler->getCollectionArr($SYMB_UID);
foreach($collArr as $id => $collectionArr){
	if($collectionArr['colltype'] == 'General Observations') $genArr[$id] = $collectionArr;
	elseif($collectionArr['colltype'] == 'Preserved Specimens') $cArr[$id] = $collectionArr;
	elseif($collectionArr['colltype'] == 'Observations') $oArr[$id] = $collectionArr;
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
	foreach($genArr as $collId => $secArr){
		$cName = $secArr['collectionname'].' ('.$secArr['institutioncode'].($secArr['collectioncode']?'-'.$secArr['collectioncode']:'').')';
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
					<a href="#" onclick="newWindow = window.open('personalspecbackup.php?collid=<?php echo $collId; ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=400,height=200,left=20,top=20');">
						Backup file download (CSV extract)
					</a>
				</li>
				<li>
					<a href="../collections/misc/commentlist.php?collid=<?php echo $collId; ?>">
						View User Comments
					</a>
					<?php if($commCnt = $specHandler->unreviewedCommentsExist($collId)) echo '- <span style="color:orange">'.$commCnt.' unreviewed comments</span>'; ?>
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
	if($cArr){
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Collection Management</b></legend>
			<ul>
				<?php
				foreach($cArr as $collId => $secArr){
					$cName = $secArr['collectionname'].' ('.$secArr['institutioncode'].($secArr['collectioncode']?'-'.$secArr['collectioncode']:'').')';
					echo '<li><a href="../collections/misc/collprofiles.php?collid='.$collId.'&emode=1">'.$cName.'</a></li>';
				}
				?>
			</ul>
		</fieldset>
		<?php
	}
	if($oArr){
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Observation Project Management</b></legend>
			<ul>
				<?php
				foreach($oArr as $collId => $secArr){
					$cName = $secArr['collectionname'].' ('.$secArr['institutioncode'].($secArr['collectioncode']?'-'.$secArr['collectioncode']:'').')';
					echo '<li><a href="../collections/misc/collprofiles.php?collid='.$collId.'&emode=1">'.$cName.'</a></li>';
				}
				?>
			</ul>
		</fieldset>
		<?php
	}
	$genAdminArr = array();
	if($genArr && isset($USER_RIGHTS['CollAdmin'])){
		$genAdminArr = array_intersect_key($genArr,array_flip($USER_RIGHTS['CollAdmin']));
		if($genAdminArr){
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend style="font-weight:bold;"><b>General Observation Administration</b></legend>
				<ul>
					<?php
					foreach($genAdminArr as $id => $secArr){
						$cName = $secArr['collectionname'].' ('.$secArr['institutioncode'].($secArr['collectioncode']?'-'.$secArr['collectioncode']:'').')';
						echo '<li><a href="../collections/misc/collprofiles.php?collid='.$id.'&emode=1">'.$cName.'</a></li>';
					}
					?>
				</ul>
			</fieldset>
			<?php
		}
	}
	if((count($cArr)+count($oArr)) > 1){
		?>
		<fieldset style="margin:15px;padding:15px;">
			<legend style="font-weight:bold;"><b>Cross Collection Batch Editing Tools</b></legend>
			<ul>
			<li><a href="../collections/georef/batchgeoreftool.php">Georeferencing Tool</a></li>
			<?php
			if(isset($USER_RIGHTS['CollAdmin']) && count(array_diff($USER_RIGHTS['CollAdmin'],array_keys($genAdminArr))) > 1){
				echo '<li><a href="../collections/cleaning/taxonomycleaner.php">Taxonomy Cleaning Tool</a></li>';
			}
			?>
			</ul>
		</fieldset>
		<?php
	}
}
?>
</div>