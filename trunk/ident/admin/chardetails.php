<?php 
$charStateList = $keyManager->getCharStateList($cId);
?>
<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#chardetaildiv"><span>Details</span></a></li>
		<li><a href="#charstatediv"><span>Character States</span></a></li>
		<li><a href="#chardeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="chardetaildiv">
		<?php 
		//Show character details
		$charArr = $keyManager->getCharDetails($cId);
		?>
		<form name="editcharform" action="index.php" method="post">
			<fieldset>
				<legend>Character Details</legend>
				<div style="padding-top:4px;">
					<span>
						Character Name:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="<?php echo $charArr['charname']; ?>" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
					<span style="margin-left:65px;">
						Type:
					</span>
					<span style="margin-left:50px;">
						Difficulty:
					</span>
					<span style="margin-left:40px;">
						Language:
					</span>
					<span style="margin-left:65px;">
						Units:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charArr['enteredby']; ?>" onchange=" " disabled />
					</span>
					<span style="margin-left:25px;">
						<select name="chartype" style="width:55px;">
							<option value="" <?php echo ($charArr['chartype']==''?'SELECTED':'');?>>--</option>
							<option value="IN" <?php echo ($charArr['chartype']=='IN'?'SELECTED':'');?>>IN</option>
							<option value="OM" <?php echo ($charArr['chartype']=='OM'?'SELECTED':'');?>>OM</option>
							<option value="RN" <?php echo ($charArr['chartype']=='RN'?'SELECTED':'');?>>RN</option>
							<option value="TE" <?php echo ($charArr['chartype']=='TE'?'SELECTED':'');?>>TE</option>
							<option value="UM" <?php echo ($charArr['chartype']=='UM'?'SELECTED':'');?>>UM</option>
						</select>
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="difficultyrank" tabindex="96" maxlength="32" style="width:60px;" value="<?php echo $charArr['difficultyrank']; ?>" onchange=" " />
					</span>
					<span style="margin-left:25px;">
						<select name="defaultlang" style="width:100px;">
							<option value="English" <?php echo ($charArr['defaultlang']=='English'?'SELECTED':'');?>>English</option>
							<option value="Spanish" <?php echo ($charArr['defaultlang']=='Spanish'?'SELECTED':'');?>>Spanish</option>
						</select>
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="units" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $charArr['units']; ?>" onchange="" title="" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Heading:
					</span>
					<span style="margin-left:90px;">
						Help URL:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<select name="hid" style="width:125px;">
							<option value="">Select Heading</option>
							<option value="">---------------------</option>
							<?php 
							$headingArr = $keyManager->getHeadingArr();
							foreach($headingArr as $k => $v){
								echo '<option value="'.$k.'" '.($k==$charArr['hid']?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</span>
					<span style="margin-left:15px;">
						<input type="text" autocomplete="off" name="helpurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php echo $charArr['helpurl']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Description:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="description" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['description']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Notes:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="notes" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['notes']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:8px;">
					<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
					<button name="formsubmit" type="submit" value="Save Char">Save</button>
				</div>
			</fieldset>
		</form>
	</div>
	<div id="charstatediv">
		<div style="float:right;margin:10px;">
			<a href="#" onclick="toggle('newspecdiv');toggle('refreshbut');">
				<img src="../../images/add.png" alt="Create New Loan" />
			</a>
		</div>
		<div id="newspecdiv" style="display:none;">
			<form name="addspecform" action="index.php" method="post" onsubmit="return false">
				<fieldset>
					<legend><b>Add Specimen</b></legend>
					<div style="float:left;padding-bottom:2px;">
						<b>Catalog Number: </b><input type="text" autocomplete="off" name="catalognumber" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
					</div>
					<div id="addspecsuccess" style="float:left;margin-left:30px;padding-bottom:2px;color:green;display:none;">
						SUCCESS: Specimen record added to loan.
					</div>
					<div id="addspecerr1" style="float:left;margin-left:30px;padding-bottom:2px;color:red;display:none;">
						ERROR: No specimens found with that catalog number.
					</div>
					<div id="addspecerr2" style="float:left;margin-left:30px;padding-bottom:2px;color:red;display:none;">
						ERROR: More than one specimen located with same catalog number.
					</div>
					<div id="addspecerr3" style="float:left;margin-left:30px;padding-bottom:2px;color:orange;display:none;">
						Warning: Specimen already linked to loan.
					</div>
					<div style="padding-top:8px;clear:both;">
						<input name="collid" type="hidden" value="<?php //echo $collId; ?>" />
						<input name="formsubmit" type="button" value="Add Specimen" onclick="addSpecimen(this.form)" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		if($charStateList){
		?>
			<div style="height:25px;margin-top:15px;">
				<span style="float:left;margin-left:15px;">
					<input name="" value="" type="checkbox" onclick="selectAll(this);" />
					Select/Deselect All
				</span>
				<span id="refreshbut" style="display:none;float:right;margin-right:15px;">
					<form name="refreshstatelist" action="index.php?cid=<?php echo $cId; ?>#charstatediv" method="post">
						<button name="formsubmit" type="submit" value="Refresh">Refresh List</button>
					</form>
				</span>
			</div>
			<form name="speceditform" action="index.php?cid=<?php echo $cId; ?>#charstatediv" method="post" onsubmit=" " >
				<table class="styledtable">
					<tr>
						<th style="width:25px;text-align:center;">&nbsp;</th>
						<th style="width:550px;text-align:center;">Details</th>
					</tr>
					<?php
					foreach($charStateList as $k => $stateArr){
						?>
						<tr>
							<td>
								<input name="cs[]" type="checkbox" value="<?php echo $stateArr['cs']; ?>" />
							</td>
							<td>
								<?php 
								//echo '<a href="index.php?cid='.$k.'">';
								echo $stateArr['charstatename'];
								//echo '</a>';
								?> 
								
							</td>
						</tr>
						<?php 
					}
				?>
				</table>
				<table>
					<tr>
						<td colspan="10" valign="bottom">
							<div style="margin:10px;">
								<div style="float:left;">
									<input name="applytask" type="radio" value="check" CHECKED title="Check-in Specimens" />Check-in Specimens<br/>
									<input name="applytask" type="radio" value="delete" title="Delete Specimens" />Delete Specimens from Loan
								</div>
								<span style="margin-left:25px;">
									<input name="formsubmit" type="submit" value="Perform Action" />
									<input name="collid" type="hidden" value="<?php //echo $collId; ?>" />
									<input name="loanid" type="hidden" value="<?php //echo $loanId; ?>" />
								</span>
							</div>
						</td>
					</tr>
				</table>
			</form>
		<?php
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">There are no specimens registered for this loan.</div>';
		}
		?>
	</div>
	<div id="chardeldiv">
		<form name="delcharform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Character</b></legend>
				<?php 
				//if($specList){
				//	echo '<div style="font-weight:bold;margin-bottom:15px;">';
				//	echo 'Character cannot be deleted until all linked character states are removed';
				//	echo '</div>';
				//}
				?>
				<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
				<input name="formsubmit" type="submit" value="Delete Char" <?php //if($specList) echo 'DISABLED'; ?> />
			</fieldset>
		</form>
	</div>
</div>