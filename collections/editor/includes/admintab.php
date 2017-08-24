<?php
include_once('../../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET['occid'];
$occIndex = $_GET['occindex'];
$collId = $_GET['collid'];

$occManager = new OccurrenceEditorManager();
$occManager->setOccId($occid); 
?>
<div id="admindiv">
	<?php
	$editArr = $occManager->getEditArr();
	$externalEdits = $occManager->getExternalEditArr();
	if($editArr || $externalEdits){
		if($editArr){
			?>
			<fieldset style="padding:15px;margin:10px 0px;">
				<legend><b>History of Internal Edits</b></legend>
				<?php 
				if(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin'])){
					?>
					<div style="float:right;" title="Manage Edit History">
						<a href="../editor/editreviewer.php?collid=<?php echo $collId.'&occid='.$occid; ?>" target="_blank"><img src="../../images/edit.png" style="border:0px;width:14px;" /></a>
					</div>
					<?php
				}
				foreach($editArr as $ts => $eArr){
					$reviewStr = 'OPEN';
					if($eArr['reviewstatus'] == 2){
						$reviewStr = 'PENDING';
					}
					elseif($eArr['reviewstatus'] == 3){
						$reviewStr = 'CLOSED';
					}
					?>
					<div>
						<b>Editor:</b> <?php echo $eArr['editor']; ?>
						<span style="margin-left:30px;"><b>Date:</b> <?php echo $ts; ?></span>
					</div>
					<div>
						<span><b>Applied Status:</b> <?php echo ($eArr['appliedstatus']?'applied':'not applied'); ?></span>
						<span style="margin-left:30px;"><b>Review Status:</b> <?php echo $reviewStr; ?></span>
					</div>
					<?php
					$edArr = $eArr['edits'];
					foreach($edArr as $vArr){
						echo '<div style="margin:10px 15px;">';
						echo '<b>Field:</b> '.$vArr['fieldname'].'<br/>';
						echo '<b>Old Value:</b> '.$vArr['old'].'<br/>';
						echo '<b>New Value:</b> '.$vArr['new'].'<br/>';
						echo '</div>';
					}
					echo '<div style="margin:5px 0px;">&nbsp;</div>';
				}
				?>
			</fieldset>
			<?php 
		}
		if($externalEdits){
			?>
			<fieldset style="margin-top:20px;padding:20px;">
				<legend><b>History of External Edits</b></legend>
				<?php 
				foreach($externalEdits as $orid => $eArr){
					foreach($eArr as $appliedStatus => $eArr2){
						$reviewStr = 'OPEN';
						if($eArr2['reviewstatus'] == 2) $reviewStr = 'PENDING';
						elseif($eArr2['reviewstatus'] == 3) $reviewStr = 'CLOSED';
						?>
						<div>
							<b>Editor:</b> <?php echo $eArr2['editor']; ?>
							<span style="margin-left:30px;"><b>Date:</b> <?php echo $eArr2['ts']; ?></span>
							<span style="margin-left:30px;"><b>Source:</b> <?php echo $eArr2['source']; ?></span>
						</div>
						<div>
							<span><b>Applied Status:</b> <?php echo ($appliedStatus?'applied':'not applied'); ?></span>
							<span style="margin-left:30px;"><b>Review Status:</b> <?php echo $reviewStr; ?></span>
						</div>
						<?php
						$edArr = $eArr2['edits'];
						foreach($edArr as $fieldName => $vArr){
							echo '<div style="margin:15px;">';
							echo '<b>Field:</b> '.$fieldName.'<br/>';
							echo '<b>Old Value:</b> '.$vArr['old'].'<br/>';
							echo '<b>New Value:</b> '.$vArr['new'].'<br/>';
							echo '</div>';
						}
						echo '<div style="margin:15px 0px;"><hr/></div>';
					}
				}
				?>
			</fieldset>
			<?php
		}
	}
	else{
		echo '<div style="margin:10px">No previous edits recorded</div>';
	}
	$collAdminList = $occManager->getCollectionList();
	unset($collAdminList[$collId]);
	if($collAdminList){
		?>
		<fieldset style="padding:15px;margin:10px 0px;">
			<legend><b>Transfer Specimen</b></legend>
			<form name="transrecform" method="post" target="occurrenceeditor.php">
				<div>
					<b>Target Collection</b><br />
					<select name="transfercollid">
						<option value="0">Select Collection</option> 
						<option value="0">----------------------</option> 
						<?php 
						foreach($collAdminList as $kCollid => $vCollName){
							echo '<option value="'.$kCollid.'">'.$vCollName.'</option>';
						}
						?>
					</select><br />
					<input name="remainoncoll" type="checkbox" value="1" CHECKED /> Remain on Current Collection
				</div>
				<div style="margin:10px;">
					<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					<input name="submitaction" type="submit" value="Transfer Record" />
				</div>
			</form>
		</fieldset>
		<?php
	}
	?>
	<fieldset style="padding:15px;margin:10px 0px;">
		<legend><b>Delete Occurrence Record</b></legend>
		<form name="deleteform" method="post" action="occurrenceeditor.php" onsubmit="return confirm('Are you sure you want to delete this record?')">
			<div style="margin:15px">
				Record first needs to be evaluated before it can be deleted from the system.
				The evaluation ensures that the deletion of this record will not interfer with
				the integrity of other linked data. Note that all determination and
				comments for this occurrence will be automatically deleted. Links to images, and checklist vouchers
				will have to be individually addressed before can be deleted.
				<div style="margin:15px;display:block;">
					<input name="verifydelete" type="button" value="Evaluate record for deletion" onclick="verifyDeletion(this.form);" />
				</div>
				<div id="delverimgdiv" style="margin:15px;">
					<b>Image Links: </b>
					<span id="delverimgspan" style="color:orange;display:none;">checking image links...</span>
					<div id="delimgfailspan" style="display:none;style:0px 10px 10px 10px;">
						<span style="color:red;">Warning:</span>
						One or more images are linked to this occurrence.
						Continuing will remove all images linked to this specimen record. 
						If you prefer to leave the image in the system only linked to the taxon name, 
						visit the Image Tab to disassociate image from specimen. 
					</div>
					<div id="delimgappdiv" style="display:none;">
						<span style="color:green;">Approved for deletion.</span>
						No images are directly associated with this occurrence record.
					</div>
				</div>
				<div id="delvervoucherdiv" style="margin:15px;">
					<b>Checklist Voucher Links: </b>
					<span id="delvervouspan" style="color:orange;display:none;">checking checklist links...</span>
					<div id="delvouappdiv" style="display:none;">
						<span style="color:green;">Approved for deletion.</span>
						No checklists have been linked to this occurrence record.
					</div>
					<div id="delvoulistdiv" style="display:none;style:0px 10px 10px 10px;">
						<span style="color:red;">Warning:</span>
						This occurrence serves as an occurrence voucher for the following species checklists.
						Deleting this occurrence will remove these association.
						You may want to first verify this action with the checklist administrators.
						<ul id="voucherlist">
						</ul>
					</div>
				</div>
				<div id="delapprovediv" style="margin:15px;display:none;">
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
					<input name="submitaction" type="submit" value="Delete Occurrence" />
				</div>
			</div>
		</form>
	</fieldset>
</div>
