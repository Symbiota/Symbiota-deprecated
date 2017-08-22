<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceIndividualManager.php');
header("Content-Type: text/html; charset=".$charset);

$occid = $_GET["occid"];
$tid = $_GET["tid"];
$collId = $_GET["collid"];
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;

//Sanitize input variables
if(!is_numeric($occid)) $occid = 0;
if(!is_numeric($tid)) $tid = 0;
if(!is_numeric($collId)) $collId = 0;
if(!is_numeric($clid)) $clid = 0;

$indManager = new OccurrenceIndividualManager();
$indManager->setOccid($occid);

?>
<div id='innertext' style='width:95%;min-height:400px;clear:both;background-color:white;'>
	<fieldset style="padding:20px;margin:15px;">
		<legend><b>Species Checklist Relationships</b></legend>
		<?php 	
		$vClArr = $indManager->getVoucherChecklists();
		if($vClArr){
			echo '<div style="font-weight:bold"><u>Specimen voucher of the following checklists</u></div>';
			echo '<ul style="margin:15px 0px 25px 0px;">';
			foreach($vClArr as $id => $clName){
				echo '<li>';
				echo '<a href="../../checklists/checklist.php?showvouchers=1&cl='.$id.'" target="_blank">'.$clName.'</a>&nbsp;&nbsp;';
				if(isset($USER_RIGHTS['ClAdmin']) && in_array($id,$USER_RIGHTS['ClAdmin'])){
					echo '<a href="index.php?delvouch='.$id.'&occid='.$occid.'" title="Delete voucher link" onclick="return confirm(\"Are you sure you want to remove this voucher link?\")"><img src="../../images/drop.png" style="width:12px;" /></a>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
		else{
			echo '<h3>Specimen has not been designated as a voucher for a species checklist</h3>';
		}
		if($IS_ADMIN || array_key_exists("ClAdmin",$USER_RIGHTS)){
			?>
			<div style='margin-top:15px;'>
				<?php 
				if($clArr = $indManager->getChecklists(array_keys($vClArr))){
					?>
					<fieldset style='margin-top:20px;padding:15px;'>
						<legend><b>New Voucher Assignment</b></legend>
						<?php
						if($tid){
							?>
							<div style='margin:10px;'>
								<form action="../../checklists/clsppeditor.php" method="post" onsubmit="return verifyVoucherForm(this);">
									<div>
										Add as voucher to checklist: 
										<input name='voccid' type='hidden' value='<?php echo $occid; ?>'>
										<input name='tid' type='hidden' value='<?php echo $tid; ?>'>
										<select id='clid' name='clid'>
							  				<option value='0'>Select a Checklist</option>
							  				<option value='0'>--------------------------</option>
							  				<?php 
								  			foreach($clArr as $clKey => $clValue){
								  				echo "<option value='".$clKey."' ".($clid==$clKey?"SELECTED":"").">$clValue</option>\n";
											}
											?>
										</select>
									</div>
									<div style='margin:5px 0px 0px 10px;'>
										Notes: 
										<input name='vnotes' type='text' size='50' title='Viewable to public'>
									</div>
									<div style='margin:5px 0px 0px 10px;'>
										Editor Notes: 
										<input name='veditnotes' type='text' size='50' title='Viewable only to checklist editors'>
									</div>
									<div>
										<input type='submit' name='action' value='Add Voucher'>
									</div>
								</form>
							</div>
							<?php 
						}
						else{
							?>
							<div style='margin:20px;'>
								Unable to use this specimen record as a voucher because  
								scientific name counld not be verified in the taxonomic thesaurus (misspelled?)
							</div>
							<?php 
						}
						?>
					</fieldset>
					<?php
				}
				?>
			</div>
			<?php 
		}
		?>
	</fieldset>
	<?php 
	if($SYMB_UID){
		?>
		<fieldset style="padding:20px;margin:15px;">
			<legend><b>Dataset Linkages</b></legend>
			<?php
			$displayStr = '';
			$datasets = $indManager->getDatasetArr($SYMB_UID); 
			foreach($datasets as $dsid => $dsArr){
				if(array_key_exists('linked',$dsArr)){
					$displayStr .= '<li>';
					$displayStr .= '<a href="../datasets/datasetmanager.php?datasetid='.$dsid.'" target="_blank">'.$dsArr['name'].'</a>';
					if($dsArr['linked']) $displayStr .= ' ('.$dsArr['linked'].')';
					$displayStr .= '</li>';
				}
			}
			if($displayStr){
				echo '<div style="font-weight:bold"><u>Member of the following datasets</u></div>';
				echo '<ul>'.$displayStr.'</ul>';
			}
			else{
				echo '<h3>Occurrence is not linked to a dataset</h3>';
			}
			?>	
			<fieldset style='padding:15px;margin-top:30px;'>
				<legend><b>Create New Dataset Relationship</b></legend>
				<form action="index.php" method="post" onsubmit="return verifyDatasetForm(this);">
					<div style="margin:3px">
						<?php 
						if($datasets){
							?>
							<select name="dsid">
								<option value="">Select an Existing Dataset</option>
								<option value="">----------------------------------</option>
								<?php 
								foreach($datasets as $dsid => $dsArr){
									if(!array_key_exists('linked',$dsArr)){
										echo '<option value="'.$dsid.'">'.$dsArr['name'].'</option>';
									}
								}
								?>
							</select> 
							<b>Or Enter</b> 
							<?php
						}
						?>
						<b>New Dataset Name:</b> 
						<input name="dsname" type="text" value="" maxlength="100" style="width:200px;" />						
					</div>
					<div style="margin:5px">
						<b>Notes:</b><br/> 
						<input name="notes" type="text" value="" maxlength="250" style="width:90%;" /> 
					</div>
					<div style="margin:15px">
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
						<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
						<input name="formsubmit" type="submit" value="Link to Dataset" />
					</div>
				</form>
			</fieldset>
		</fieldset>
		<?php
	} 
	?>
	
</div>
