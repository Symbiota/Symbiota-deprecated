<?php 
//$specList = $loanManager->getSpecList($loanId);
?>
<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#chardetaildiv"><span>Details</span></a></li>
		<li><a href="#charstatediv"><span>Character States</span></a></li>
		<li><a href="#chardeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="chardetaildiv">
		<?php 
		//Show loan details
		//$loanArr = $loanManager->getLoanOutDetails($loanId);
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
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
					<span style="margin-left:20px;">
						Type:
					</span>
					<span style="margin-left:70px;">
						Difficulty:
					</span>
					<span style="margin-left:50px;">
						Language:
					</span>
					<span style="margin-left:55px;">
						Units:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php //echo $loanArr['createdbyown']; ?>" onchange=" " disabled />
					</span>
					<span style="margin-left:25px;">
						<select name="chartype" style="width:55px;">
							<option value="" <?php //echo ($exchangeArr['transactiontype']==''?'SELECTED':'');?>>--</option>
							<option value="IN" <?php //echo ($exchangeArr['transactiontype']=='IN'?'SELECTED':'');?>>IN</option>
							<option value="OM" <?php //echo ($exchangeArr['transactiontype']=='OM'?'SELECTED':'');?>>OM</option>
							<option value="RN" <?php //echo ($exchangeArr['transactiontype']=='RN'?'SELECTED':'');?>>RN</option>
							<option value="TE" <?php //echo ($exchangeArr['transactiontype']=='TE'?'SELECTED':'');?>>TE</option>
							<option value="UM" <?php //echo ($exchangeArr['transactiontype']=='UM'?'SELECTED':'');?>>UM</option>
						</select>
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="difficultyrank" tabindex="96" maxlength="32" style="width:60px;" value="<?php //echo $loanArr['processedbyown']; ?>" onchange=" " />
					</span>
					<span style="margin-left:25px;">
						<select name="defaultlang" style="width:100px;">
							<option value="" <?php //echo ($exchangeArr['transactiontype']==''?'SELECTED':'');?>>-------</option>
							<option value="English" <?php //echo ($exchangeArr['transactiontype']=='English'?'SELECTED':'');?>>English</option>
							<option value="Spanish" <?php //echo ($exchangeArr['transactiontype']=='Spanish'?'SELECTED':'');?>>Spanish</option>
						</select>
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="units" tabindex="100" maxlength="32" style="width:100px;" value="<?php //echo $loanArr['datedue']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Heading:
					</span>
					<span style="margin-left:340px;">
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
								echo '<option value="'.$k.'">'.$v.'</option>';
							}
							?>
						</select>
					</span>
					<span style="margin-left:15px;">
						<input type="text" autocomplete="off" name="helpurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php //echo $loanArr['totalboxes']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Description:
					</span>
					<span style="margin-left:270px;">
						Notes:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<textarea name="description" rows="10" style="width:200px;resize:vertical;" onchange=" "><?php //echo $loanArr['description']; ?></textarea>
					</span>
					<span style="margin-left:40px;">
						<textarea name="notes" rows="10" style="width:200px;resize:vertical;" onchange=" "><?php //echo $loanArr['notes']; ?></textarea>
					</span>
				</div>
				<div style="padding-top:8px;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<button name="formsubmit" type="submit" value="Save Outgoing">Save</button>
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
		if($specList){
		?>
			<div style="height:25px;margin-top:15px;">
				<span style="float:left;margin-left:15px;">
					<input name="" value="" type="checkbox" onclick="selectAll(this);" />
					Select/Deselect All
				</span>
				<span id="refreshbut" style="display:none;float:right;margin-right:15px;">
					<form name="refreshspeclist" action="index.php?collid=<?php //echo $collId; ?>&loanid=<?php //echo $loanId; ?>&loantype=<?php //echo $loanType; ?>#addspecdiv" method="post">
						<button name="formsubmit" type="submit" value="Refresh">Refresh List</button>
					</form>
				</span>
			</div>
			<form name="speceditform" action="index.php?collid=<?php //echo $collId; ?>&loanid=<?php //echo $loanId; ?>&loantype=<?php //echo $loanType; ?>#addspecdiv" method="post" onsubmit="return verifyspeceditform(this)" >
				<table class="styledtable">
					<tr>
						<th style="width:25px;text-align:center;">&nbsp;</th>
						<th style="width:100px;text-align:center;">Catalog Number</th>
						<th style="width:375px;text-align:center;">Details</th>
						<th style="width:75px;text-align:center;">Date Returned</th>
					</tr>
					<?php
					foreach($specList as $k => $specArr){
						?>
						<tr>
							<td>
								<input name="occid[]" type="checkbox" value="<?php //echo $specArr['occid']; ?>" />
							</td>
							<td>
								<a href="#" onclick="openOccurrenceDetails(<?php //echo $k; ?>);">
									<?php //echo $specArr['catalognumber']; ?>
								</a>
							</td>
							<td>
								<?php 
								//$loc = $specArr['locality'];
								//if(strlen($loc) > 500) $loc = substr($loc,400);
								//echo '<i>'.$specArr['sciname'].'</i>; ';
								//echo  $specArr['collector'].'; '.$loc;
								?> 
								
							</td>
							<td><?php echo $specArr['returndate']; ?></td>
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
		<form name="deloutloanform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this loan?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Outgoing Loan</b></legend>
				<?php 
				if($specList){
					echo '<div style="font-weight:bold;margin-bottom:15px;">';
					echo 'Loan cannot be delted until all linked specimens are removed';
					echo '</div>';
				}
				?>
				<input name="formsubmit" type="submit" value="Delete Loan" <?php //if($specList) echo 'DISABLED'; ?> />
				<input name="collid" type="hidden" value="<?php //echo $collId; ?>" />
			</fieldset>
		</form>
	</div>
</div>