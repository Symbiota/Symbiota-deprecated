<?php 
$specList = $loanManager->getSpecList($loanId);
?>
<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#outloandetaildiv"><span>Loan Details</span></a></li>
		<li><a href="#outloanspecdiv"><span>Specimens</span></a></li>
		<li><a href="#outloandeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="outloandetaildiv">
		<?php 
		//Show loan details
		$loanArr = $loanManager->getLoanOutDetails($loanId);
		?>
		<form name="editloanform" action="index.php" method="post">
			<fieldset>
				<legend>Loan Details</legend>
				<div style="padding-top:18px;float:left;">
					<span>
						<b>Loan Number:</b> <input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" />
					</span>
				</div>
				<div style="margin-left:20px;padding-top:4px;float:left;">
					<span>
						Entered By:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['createdbyown']; ?>" onchange=" " disabled />
					</span>
				</div>
				<div style="margin-left:20px;padding-top:4px;float:left;">
					<span>
						Processed By:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="processedbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyown']; ?>" onchange=" " />
					</span>
				</div>
				<div style="margin-left:20px;padding-top:4px;float:left;">
					<span>
						Date Sent:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="datesent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesent']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
					</span>
				</div>
				<div style="margin-left:20px;padding-top:4px;float:left;">
					<span>
						Date Due:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange="verifyDueDate(this);" title="format: yyyy-mm-dd" />
					</span>
				</div>
				<div style="padding-top:8px;float:left;">
					<span>
						Sent To:
					</span><br />
					<span>
						<select name="iidborrower" style="width:400px;" disabled >
							<?php 
							$instArr = $loanManager->getInstitutionArr();
							foreach($instArr as $k => $v){
								echo '<option value="'.$k.'" '.($k==$loanArr['iidborrower']?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</span>
				</div>
				<div style="padding-top:8px;float:left;">
					<div style="float:left;">
						<span>
							Requested for:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
						</span>
					</div>
					<div style="padding-top:15px;margin-left:20px;float:left;">
						<span>
							<b>Specimen Total:</b> <input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo count($specList); ?>" onchange=" " disabled />
						</span>
					</div>
					<div style="margin-left:20px;float:left;">
						<span>
							# of Boxes:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxes']; ?>" onchange=" " />
						</span>
					</div>
					<div style="margin-left:20px;float:left;">
						<span>
							Shipping Service:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethod']; ?>" onchange=" " />
						</span>
					</div>
				</div>
				<div style="padding-top:8px;float:left;">
					<div style="float:left;">
						<span>
							Loan Description:
						</span><br />
						<span>
							<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['description']; ?></textarea>
						</span>
					</div>
					<div style="margin-left:20px;float:left;">
						<span>
							Notes:
						</span><br />
						<span>
							<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['notes']; ?></textarea>
						</span>
					</div>
				</div>
				<div style="width:100%;padding-top:8px;float:left;">
					<hr />
				</div>
				<div style="padding-top:8px;float:left;">
					<div style="float:left;">
						<span>
							Date Received:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="datereceivedown" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedown']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
						</span>
					</div>
					<div style="margin-left:40px;float:left;">
						<span>
							Ret. Processed By:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="processedbyreturnown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnown']; ?>" onchange=" " />
						</span>
					</div>
					<div style="margin-left:40px;float:left;">
						<span>
							Date Closed:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
						</span>
					</div>
				</div>
				<div style="padding-top:8px;float:left;">
					<span>
						Additional Invoice Message:
					</span>
					<span>
						<textarea name="invoicemessageown" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageown']; ?></textarea>
					</span>
				</div>
				<div style="padding-top:8px;float:left;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<button name="formsubmit" type="submit" value="Save Outgoing">Save</button>
				</div>
			</fieldset>
		</form>
		<form name="reportsform" onsubmit="return ProcessReport();" method="post" onsubmit="" target="_blank">
			<fieldset>
				<legend>Generate Loan Paperwork</legend>
				<div style="float:right;">
					<b>International Shipment:</b> <input type="checkbox" name="international" value="1" /><br /><br />
					<b>Mailing Account #:</b> <input type="text" autocomplete="off" name="mailaccnum" tabindex="100" maxlength="32" style="width:100px;" value="" />
				</div>
				<div style="padding-bottom:2px;">
					<b>Print Method:</b> <input type="radio" name="print" value="browser" checked /> Print in Browser
					<input type="radio" name="print" value="doc" /> Export to doc
				</div>
				<div style="padding-bottom:8px;">
					<b>Invoice Language:</b> <input type="radio" name="languagedef" value="0" checked /> English
					<input type="radio" name="languagedef" value="1" /> English/Spanish
					<input type="radio" name="languagedef" value="2" /> Spanish
				</div>
				<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
				<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
				<input name="loantype" type="hidden" value="<?php echo $loanType; ?>" />
				<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="invoice">Invoice</button>
				<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="spec">Specimen List</button>
				<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="label">Mailing Label</button>
				<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="envelope">Envelope</button>
			</fieldset>
		</form>
	</div>
	<div id="outloanspecdiv">
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
						<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
						<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
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
					<form name="refreshspeclist" action="index.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>#addspecdiv" method="post">
						<button name="formsubmit" type="submit" value="Refresh">Refresh List</button>
					</form>
				</span>
			</div>
			<form name="speceditform" action="index.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>#addspecdiv" method="post" onsubmit="return verifyspeceditform(this)" >
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
								<input name="occid[]" type="checkbox" value="<?php echo $specArr['occid']; ?>" />
							</td>
							<td>
								<a href="#" onclick="openOccurrenceDetails(<?php echo $k; ?>);">
									<?php echo $specArr['catalognumber']; ?>
								</a>
							</td>
							<td>
								<?php 
								$loc = $specArr['locality'];
								if(strlen($loc) > 500) $loc = substr($loc,400);
								echo '<i>'.$specArr['sciname'].'</i>; ';
								echo  $specArr['collector'].'; '.$loc;
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
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
									<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
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
	<div id="outloandeldiv">
		<form name="deloutloanform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this loan?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Outgoing Loan</b></legend>
				<?php 
				if($specList){
					echo '<div style="font-weight:bold;margin-bottom:15px;">';
					echo 'Loan cannot be deleted until all linked specimens are removed';
					echo '</div>';
				}
				?>
				<input name="formsubmit" type="submit" value="Delete Loan" <?php if($specList) echo 'DISABLED'; ?> />
				<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
				<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
			</fieldset>
		</form>
	</div>
</div>
