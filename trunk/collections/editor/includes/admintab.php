<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = $_GET['occid'];
$occIndex = $_GET['occindex'];

$occManager = new OccurrenceEditorManager();
$occManager->setOccId($occId); 
?>
<div id="admindiv">
	<fieldset style="padding:15px;margin:10px 0px;">
		<legend><b>Edit History</b></legend>
		<?php
		$editArr = $occManager->getEditArr();
		foreach($editArr as $k => $eArr){
			?>
			<div>
				<b>Editor:</b> <?php echo $eArr['editor']; ?>
				<span style="margin-left:30px;"><b>Date:</b> <?php echo $eArr['ts']; ?></span>
			</div>
			<?php
			unset($eArr['editor']);
			unset($eArr['ts']);
			foreach($eArr as $vArr){
				echo '<div style="margin:10px 15px;">';
				echo '<b>Field:</b> '.$vArr['fieldname'].'<br/>';
				echo '<b>Old Value:</b> '.$vArr['old'].'<br/>';
				echo '<b>New Value:</b> '.$vArr['new'].'<br/>';
				$reviewStr = 'OPEN';
				if($vArr['reviewstatus'] == 2){
					$reviewStr = 'PENDING';
				}
				elseif($vArr['reviewstatus'] == 3){
					$reviewStr = 'CLOSED';
				}
				echo 'Edit '.($vArr['appliedstatus']?'applied':'not applied').'; status '.$reviewStr;
				echo '</div>';
			}
			echo '<div style="margin:5px 0px;">&nbsp;</div>';
		}
		if(!$editArr) echo '<div style="margin:10px">No previous edits recorded</div>';
		?>
	</fieldset>
	<?php 
	if($collAdminList = $occManager->getCollectionList()){
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
					<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
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
				comments for this occurrence will be automatically deleted. Links to images, checklist vouchers,
				and surveys will have to be individually addressed before can be deleted.
				<div style="margin:15px;display:block;">
					<input name="verifydelete" type="button" value="Evaluate record for deletion" onclick="verifyDeletion(this.form);" />
				</div>
				<div id="delverimgdiv" style="margin:15px;">
					<b>Image Links: </b>
					<span id="delverimgspan" style="color:orange;display:none;">checking image links...</span>
					<div id="delimgfailspan" style="display:none;style:0px 10px 10px 10px;">
						<span style="color:red;">Warning:</span>
						One or more images are linked to this occurrence.
						Before this specimen can be deleted, images have to be deleted or disassociated
						with this occurrence record. Continuing will remove associations to
						the occurrence record being deleted but leave image in system linked only to the scientific name.
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
				<div id="delversurveydiv" style="margin:15px;">
					<b>Survey Voucher Links: </b>
					<span id="delversurspan" style="color:orange;display:none;">checking survey links...</span>
					<div id="delsurappdiv" style="display:none;">
						<span style="color:green;">Approved for deletion.</span>
						No survey projects have been linked to this occurrence record.
					</div>
					<div id="delsurlistdiv" style="display:none;style:0px 10px 10px 10px;">
						<span style="color:red;">Warning:</span>
						This occurrence serves as an occurrence voucher for the following survey projects.
						Deleting this occurrence will remove these association.
						You may want to first verify this action with the project administrators.
						<ul id="surveylist">
						</ul>
					</div>
				</div>
				<div id="delapprovediv" style="margin:15px;display:none;">
					<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
					<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
					<input name="submitaction" type="submit" value="Delete Occurrence" />
				</div>
			</div>
		</form>
	</fieldset>
</div>
