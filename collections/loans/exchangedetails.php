<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#exchangedetaildiv"><span>Exchange Details</span></a></li>
		<li><a href="#exchangedeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="exchangedetaildiv" style="">
		<?php 
		//Show loan details
		$exchangeArr = $loanManager->getExchangeDetails($exchangeId);
		$exchangeValue = $loanManager->getExchangeValue($exchangeId);
		$exchangeTotal = $loanManager->getExchangeTotal($exchangeId);
		//$specTotal = $loanManager->getSpecTotal($loanId);
		?>
		<form name="editexchangegiftform" action="index.php" method="post">
			<?php
			if($exchangeArr['transactiontype']=='Adjustment'){ ?>
				<fieldset>
					<legend>Edit Adjustment</legend>
					<div style="padding-top:4px;">
						<span style="margin-left:285px;">
							Transaction Type:
						</span>
						<span style="margin-left:45px;">
							Entered By:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<b>Transaction Number:</b> <input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
						</span>
						<span style="margin-left:40px;">
							<select name="transactiontype" style="width:100px;">
								<?php if($exchangeArr['transactiontype']=='Shipment'){ ?>
									<option value="Shipment" <?php echo ($exchangeArr['transactiontype']=='Shipment'?'SELECTED':'');?>>Shipment</option>
								<?php }
								if($exchangeArr['transactiontype']=='Adjustment'){ ?>
									<option value="Adjustment" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'SELECTED':'');?>>Adjustment</option>
								<?php } ?>	
							</select>
						</span>
						<span style="margin-left:40px;">
							<input type="text" autocomplete="off" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" onchange=" " disabled />
						</span>
					</div>
					<div style="padding-top:4px;">
						<span>
							Institution:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<select name="iid" style="width:400px;" >
								<?php 
								$instArr = $loanManager->getInstitutionArr();
								foreach($instArr as $k => $v){
									echo '<option value="'.$k.'" '.($k==$exchangeArr['iid']?'SELECTED':'').'>'.$v.'</option>';
								}
								?>
							</select>
						</span>
						<span style="margin-left:40px;">
							<b>Adjustment Amount:</b>&nbsp;&nbsp;<input type="text" autocomplete="off" name="adjustment" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['adjustment']; ?>" onchange=" " />
						</span>
					</div>
			<?php } 
			else{ ?>
				<fieldset>
					<legend>Edit Gift/Exchange</legend>
					<div style="padding-top:4px;">
						<span style="margin-left:290px;">
							Entered By:
						</span>
						<span style="margin-left:80px;">
							Date Shipped:
						</span>
						<span style="margin-left:50px;">
							Date Received:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<b>Transaction Number:</b> <input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
						</span>
						<span style="margin-left:40px;">
							<input type="text" autocomplete="off" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" onchange=" " disabled />
						</span>
						<span style="margin-left:40px;">
							<input type="text" autocomplete="off" name="datesent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['datesent']; ?>" onchange="eventDateModified(this);" />
						</span>
						<span style="margin-left:40px;">
							<input type="text" autocomplete="off" name="datereceived" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['datereceived']; ?>" onchange="eventDateModified(this);" />
						</span>
					</div>
					<div style="padding-top:4px;">
						<span>
							Institution:
						</span>
						<span style="margin-left:385px;">
							Transaction Type:
						</span>
						<span style="margin-left:45px;">
							In/Out:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<select name="iid" style="width:400px;" >
								<?php 
								$instArr = $loanManager->getInstitutionArr();
								foreach($instArr as $k => $v){
									echo '<option value="'.$k.'" '.($k==$exchangeArr['iid']?'SELECTED':'').'>'.$v.'</option>';
								}
								?>
							</select>
						</span>
						<span style="margin-left:40px;">
							<select name="transactiontype" style="width:100px;">
								<?php if($exchangeArr['transactiontype']=='Shipment'){ ?>
									<option value="Shipment" <?php echo ($exchangeArr['transactiontype']=='Shipment'?'SELECTED':'');?>>Shipment</option>
								<?php }
								if($exchangeArr['transactiontype']=='Adjustment'){ ?>
									<option value="Adjustment" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'SELECTED':'');?>>Adjustment</option>
								<?php } ?>	
							</select>
						</span>
						<span style="margin-left:40px;">
							<select name="in_out" style="width:100px;">
								<?php if($exchangeArr['transactiontype']=='Adjustment'){ ?>
									<option value="" <?php echo (!$exchangeArr['in_out']?'SELECTED':'');?>>   </option>
								<?php }
								if($exchangeArr['transactiontype']=='Shipment'){ ?>
									<option value="Out" <?php echo ('Out'==$exchangeArr['in_out']?'SELECTED':'');?>>Out</option>
									<option value="In" <?php echo ('In'==$exchangeArr['in_out']?'SELECTED':'');?>>In</option>
								<?php } ?>
							</select>
						</span>
					</div>
					<div style="padding-top:8px;padding-bottom:8px;">
						<table class="styledtable">
							<tr>
								<th style="width:220px;text-align:center;">Gift Specimens</th>
								<th style="width:220px;text-align:center;">Exchange Specimens</th>
								<th style="width:220px;text-align:center;">Transaction Totals</th>
							</tr>
							<tr style="text-align:right;">
								<td><b>Total Gifts:</b>&nbsp;&nbsp;<input type="text" autocomplete="off" name="totalgift" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalgift']; ?>" onchange=" " <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
								<td><b>Total Unmounted:</b>&nbsp;&nbsp;<input type="text" autocomplete="off" name="totalexunmounted" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalexunmounted']; ?>" onchange=" " <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
								<td><b>Exchange Value:</b>&nbsp;&nbsp;<input type="text" name="exchangevalue" tabindex="100" maxlength="32" style="width:80px;border:1px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($exchangeValue?$exchangeValue:'');?>" onchange=" " disabled="disabled" /></td>
							</tr>
							<tr style="text-align:right;">
								<td><b>Total Gifts For Det:</b>&nbsp;&nbsp;<input type="text" autocomplete="off" name="totalgiftdet" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalgiftdet']; ?>" onchange=" " <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
								<td><b>Total Mounted:</b>&nbsp;&nbsp;<input type="text" autocomplete="off" name="totalexmounted" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalexmounted']; ?>" onchange=" " <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
								<td><b>Total Specimens:</b>&nbsp;&nbsp;<input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:1px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($exchangeTotal?$exchangeTotal:'');?>" onchange=" " disabled="disabled" /></td>
							</tr>
						</table>	
					</div>
					<div style="padding-top:4px;">
						<span style="margin-left:350px;">
							# of Boxes:
						</span>
						<span style="margin-left:55px;">
							Shipping Service:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span style="margin-left:25px;">
							<b>Current Balance:</b> <input type="text" name="invoicebalance" tabindex="100" maxlength="32" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['invoicebalance']; ?>" onchange=" " disabled />
						</span>
						<span style="margin-left:100px;">
							<input type="text" autocomplete="off" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $exchangeArr['totalboxes']; ?>" onchange=" " />
						</span>
						<span style="margin-left:60px;">
							<input type="text" autocomplete="off" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $exchangeArr['shippingmethod']; ?>" onchange=" " />
						</span>
					</div>
					<div style="padding-top:4px;">
						<span>
							Description:
						</span>
						<span style="margin-left:300px;">
							Notes:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $exchangeArr['description']; ?></textarea>
						</span>
						<span style="margin-left:40px;">
							<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $exchangeArr['notes']; ?></textarea>
						</span>
					</div>
					<hr />
					<div style="padding-top:4px;">
						<span>
							Additional Message:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<textarea name="invoicemessage" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $exchangeArr['invoicemessage']; ?></textarea>
						</span>
					</div>
			<?php } ?>	
				<div style="padding-top:8px;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
					<button name="formsubmit" type="submit" value="Save Exchange">Save</button>
				</div>
			</fieldset>
		</form>
		<?php
		if($exchangeArr['transactiontype']=='Shipment'){ ?>
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
					<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="label">Mailing Label</button>
					<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="envelope">Envelope</button>
				</fieldset>
			</form>
		<?php } ?>
	</div>
	<div id="exchangedeldiv">
		<form name="delexchangeform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this exchange?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Exchange</b></legend>
				<input name="formsubmit" type="submit" value="Delete Exchange" />
				<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
				<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
			</fieldset>
		</form>
	</div>
</div>