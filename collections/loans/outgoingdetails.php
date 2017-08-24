<?php 
$specList = $loanManager->getSpecList($loanId);
?>
<script type="text/javascript" src="../../js/symb/collections.occureditormain.js?ver=141210"></script>
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
				<legend>Loan Out Details</legend>
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
						<input type="text" autocomplete="off" name="datesent" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $loanArr['datesent']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
					</span>
				</div>
				<div style="margin-left:20px;padding-top:4px;float:left;">
					<span>
						Date Due:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="datedue" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $loanArr['datedue']; ?>" onchange="verifyDueDate(this);" title="format: yyyy-mm-dd" />
					</span>
				</div>
				<div style="padding-top:8px;float:left;">
					<span>
						Sent To:
					</span><br />
					<span>
						<select name="iidborrower" style="width:400px;">
							<?php 
							$instArr = $loanManager->getInstitutionArr();
							foreach($instArr as $k => $v){
								echo '<option value="'.$k.'" '.($loanArr['iidborrower']==$k?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</span>
					<?php
					if($isAdmin){
						?>
						<span>
							<a href="../admin/institutioneditor.php?iid=<?php echo $loanArr['iidborrower']; ?>" target="_blank" title="Edit institution details (option available only to Super Admin)">
								<img src="../../images/edit.png" style="width:15px;" />
							</a>
						</span>
						<?php 
					}
					?>
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
							<input type="text" autocomplete="off" name="datereceivedown" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $loanArr['datereceivedown']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
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
							<input type="text" autocomplete="off" name="dateclosed" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
						</span>
					</div>
				</div>
				<div style="clear:left;padding-top:8px;float:left;">
					<span>
						Additional Invoice Message:
					</span><br />
					<span>
						<textarea name="invoicemessageown" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageown']; ?></textarea>
					</span>
				</div>
				<div style="clear:both;padding-top:8px;float:right;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<button name="formsubmit" type="submit" value="Save Outgoing">Save</button>
				</div>
			</fieldset>
		</form>
		<form name="reportsform" onsubmit="return ProcessReport();" method="post" onsubmit="">
			<fieldset>
				<legend>Generate Loan Paperwork</legend>
				<div style="float:right;">
					<b>International Shipment:</b> <input type="checkbox" name="international" value="1" /><br /><br />
					<b>Mailing Account #:</b> <input type="text" autocomplete="off" name="mailaccnum" tabindex="100" maxlength="32" style="width:100px;" value="" />
				</div>
				<div style="padding-bottom:2px;">
					<b>Print Method:</b> <input type="radio" name="print" id="printbrowser" value="browser" checked /> Print in Browser
					<input type="radio" name="print" id="printdoc" value="doc" /> Export to DOCX
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
			<a href="#" onclick="toggle('newspecdiv');">
				<img src="../../images/add.png" title="Add New Specimen" />
			</a>
		</div>
		<div id="newspecdiv" style="display:<?php echo ($eMode?'block':'none'); ?>;">
			<fieldset style="padding:10px;">
				<form name="addspecform" style="margin-bottom:0px;padding-bottom:0px;" action="index.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>#addspecdiv" method="post" onsubmit="return addSpecimen(this,<?php echo (!$specList?'0':'1'); ?>);">
					<legend><b>Add Specimen</b></legend>
					<div style="float:left;padding-bottom:2px;">
						<b>Catalog Number: </b><input type="text" autocomplete="off" name="catalognumber" maxlength="255" style="width:200px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
					</div>
					<div id="addspecsuccess" style="float:left;margin-left:30px;padding-bottom:2px;color:green;display:<?php echo ($eMode?'block':'none'); ?>;">
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
					<div style="padding-top:8px;clear:left;float:left;">
						<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
						<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
						<input name="formsubmit" type="submit" value="Add Specimen" />
					</div>
				</form>
				<div id="refreshbut" style="float:left;padding-top:10px;margin-left:10px;">
					<form style="margin-bottom:0px;" name="refreshspeclist" action="index.php" method="post">
						<input name="loantype" type="hidden" value="<?php echo $loanType; ?>" />
						<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
						<input name="emode" type="hidden" value="0" />
						<input name="tabindex" type="hidden" value="1" />
						<input name="formsubmit" type="submit" value="Refresh List" />
					</form>
				</div>
			</fieldset>
		</div>
		<div id="speclistdiv" style="<?php echo (!$specList?'display:none;':''); ?>">	
			<div style="height:25px;margin-top:15px;">
				<div style="float:left;margin-left:15px;">
					<input name="" value="" type="checkbox" onclick="selectAll(this);" />
					Select/Deselect All
				</div>
				<div id="refreshbut" style="display:none;float:right;margin-right:15px;">
					<form name="refreshspeclist" action="index.php" method="post">
						<input name="loantype" type="hidden" value="<?php echo $loanType; ?>" />
						<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
						<input name="emode" type="hidden" value="0" />
						<input name="tabindex" type="hidden" value="1" />
						<input name="formsubmit" type="submit" value="Refresh List" />
					</form>
				</div>
			</div>
			<form name="speceditform" action="index.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>#addspecdiv" method="post" onsubmit="return verifySpecEditForm(this)" >
				<table class="styledtable" style="font-family:Arial;font-size:12px;">
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
								<a href="#" onclick="openIndPopup(<?php echo $specArr['occid']; ?>); return false;">
									<?php echo $specArr['catalognumber']; ?>
								</a>
								<a href="#" onclick="openEditorPopup(<?php echo $specArr['occid']; ?>); return false;">
									<img src="../../images/edit.png" />
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
				<table style="width:100%;">
					<tr>
						<td colspan="10" valign="bottom">
							<div id="newdetdiv" style="display:none;">
								<fieldset style="margin: 15px 15px 0px 15px;padding:15px;">
									<legend><b>Add a New Determinations</b></legend>
									<div style='margin:3px;'>
										<b>Identification Qualifier:</b>
										<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
									</div>
									<div style='margin:3px;'>
										<b>Scientific Name:</b> 
										<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initLoanDetAutocomplete(this.form)" />
										<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
										<input type="hidden" name="family" value="" />
									</div>
									<div style='margin:3px;'>
										<b>Author:</b> 
										<input type="text" name="scientificnameauthorship" style="width:200px;" />
									</div>
									<div style='margin:3px;'>
										<b>Confidence of Determination:</b> 
										<select name="confidenceranking">
											<option value="8">High</option>
											<option value="5" selected>Medium</option>
											<option value="2">Low</option>
										</select>
									</div>
									<div style='margin:3px;'>
										<b>Determiner:</b> 
										<input type="text" name="identifiedby" id="identifiedby" style="background-color:lightyellow;width:200px;" />
									</div>
									<div style='margin:3px;'>
										<b>Date:</b> 
										<input type="text" name="dateidentified" id="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
									</div>
									<div style='margin:3px;'>
										<b>Reference:</b> 
										<input type="text" name="identificationreferences" style="width:350px;" />
									</div>
									<div style='margin:3px;'>
										<b>Notes:</b> 
										<input type="text" name="identificationremarks" style="width:350px;" />
									</div>
									<div style='margin:3px;'>
										<input type="checkbox" name="makecurrent" value="1" /> Make this the current determination
									</div>
									<div style='margin:3px;'>
										<input type="checkbox" name="printqueue" value="1" /> Add to Annotation Queue
									</div>
									<?php 
									global $fpEnabled;
									if($fpEnabled){
										echo '<div style="float:left;margin-left:30px;">';
										echo '<input type="checkbox" name="fpsubmit" value="1" checked="true" /> Submit determination to Filtered Push network';
										echo '</div>';
									}
									?>
									<div style='margin:15px;'>
										<div style="float:left;">
											<input type="submit" name="formsubmit" onclick="verifyLoanDet();" value="Add New Determinations" />
										</div>
									</div>
								</fieldset>
							</div>
							<div style="margin:10px;float:left;">
								<div style="float:left;">
									<input name="applytask" type="radio" value="check" title="Check-in Specimens" CHECKED />Check-in Specimens<br/>
									<input name="applytask" type="radio" value="delete" title="Delete Specimens" />Delete Specimens from Loan
								</div>
								<span style="margin-left:25px;float:left;">
									<input name="formsubmit" type="submit" value="Perform Action" />
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
									<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
									<input name="tabindex" type="hidden" value="1" />
								</span>
							</div>
							<div style="margin:10px;float:right;">
								<div id="detAddToggleDiv" onclick="toggle('newdetdiv');">
									<a href="#" onclick="return false;">Add New Determinations</a>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</form>
		</div>	
		<div id="nospecdiv" style="font-weight:bold;font-size:120%;<?php echo ($specList?'display:none;':''); ?>">There are no specimens registered for this loan.</div>
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
