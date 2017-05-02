<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}

?>
<div id="innertext" style="background-color:white;">
	<h2>Possible Voucher Conflicts</h2>
	<div style="margin-bottom:10px;">
		List of specimen vouchers where the current identifications conflict with the checklist. 
		Voucher conflicts are typically due to recent annotations of specimens located within collection.
		Click on Checklist ID to open the editing pane for that record. 
	</div>
	<?php 
	if($conflictArr = $vManager->getConflictVouchers()){
		echo '<div style="font-weight:bold;">Conflict Count: '.count($conflictArr).'</div>';
		?>
		<table class="styledtable" style="font-family:Arial;font-size:12px;">
			<tr><th><b>Checklist ID</b></th><th><b>Collector</b></th><th><b>Specimen ID</b></th><th><b>Identified By</b></th></tr>
			<?php
			foreach($conflictArr as $id => $vArr){
				?>
				<tr>
					<td>
						<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $vArr['tid']."&clid=".$vArr['clid']; ?>','editorwindow');">
							<?php 
							echo $vArr['listid'];
							?>
						</a>
						<?php 
						if($vArr['clid'] != $clid) echo '<br/>(from child checklists)';
						?>
					</td>
					<td>
						<a href="#" onclick="return openPopup('../collections/individual/index.php?occid=<?php echo $vArr['occid']; ?>','occwindow');">
							<?php echo $vArr['recordnumber']; ?>
						</a>
					</td>
					<td>
						<?php echo $vArr['specid'] ?>
					</td>
					<td>
						<?php echo $vArr['identifiedby'] ?>
					</td>
				</tr>
				<?php 
			}
			?>
		</table>
		<?php 
	}
	else{
		echo '<h3>No conflicts exist</h3>';
	}
	?>
</div>