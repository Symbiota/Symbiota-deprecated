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
					<div style="padding-top:4px;float:left;">
						<div style="padding-top:12px;float:left;">
							<span>
								<b>Transaction Number:</b> <input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
							</span>
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								Transaction Type:
							</span><br />
							<span>
								<select name="transactiontype" style="width:100px;">
									<?php if($exchangeArr['transactiontype']=='Shipment'){ ?>
										<option value="Shipment" <?php echo ($exchangeArr['transactiontype']=='Shipment'?'SELECTED':'');?>>Shipment</option>
									<?php }
									if($exchangeArr['transactiontype']=='Adjustment'){ ?>
										<option value="Adjustment" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'SELECTED':'');?>>Adjustment</option>
									<?php } ?>	
								</select>
							</span>
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								Entered By:
							</span><br />
							<span>
								<input type="text" autocomplete="off" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" onchange=" " disabled />
							</span>
						</div>
					</div>
					<div style="padding-top:8px;float:left;">
						<div style="float:left;">
							<span>
								Institution:
							</span>
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
						</div>
						<div style="float:left;">
							<span style="margin-left:40px;">
								<b>Adjustment Amount:</b>&nbsp;&nbsp;<input type="text" autocomplete="off" name="adjustment" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['adjustment']; ?>" onchange=" " />
							</span>
						</div>
					</div>
			<?php } 
			else{ ?>
				<fieldset>
					<legend>Edit Gift/Exchange</legend>
					<div style="padding-top:4px;float:left;">
						<div style="padding-top:12px;float:left;">
							<span>
								<b>Transaction Number:</b> <input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
							</span>
						</div>
						<div style="margin-left:40px;float:left;">	
							<span>
								Entered By:
							</span><br />
							<span>
								<input type="text" autocomplete="off" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" onchange=" " disabled />
							</span>
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								Date Shipped:
							</span><br />
							<span>
								<input type="text" autocomplete="off" name="datesent" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['datesent']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
							</span>
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								Date Received:
							</span><br />
							<span>
								<input type="text" autocomplete="off" name="datereceived" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['datereceived']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
							</span>
						</div>
					</div>
					<div style="padding-top:8px;padding-bottom:8px;float:left;">
						<div style="float:left;">
							<span>
								Institution:
							</span><br />
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
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								Transaction Type:
							</span><br />
							<span>
								<select name="transactiontype" style="width:100px;">
									<?php if($exchangeArr['transactiontype']=='Shipment'){ ?>
										<option value="Shipment" <?php echo ($exchangeArr['transactiontype']=='Shipment'?'SELECTED':'');?>>Shipment</option>
									<?php }
									if($exchangeArr['transactiontype']=='Adjustment'){ ?>
										<option value="Adjustment" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'SELECTED':'');?>>Adjustment</option>
									<?php } ?>	
								</select>
							</span>
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								In/Out:
							</span><br />
							<span>
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
					</div>
					<div style="padding-top:8px;padding-bottom:8px;">
						<table class="styledtable" style="font-family:Arial;font-size:12px;">
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
					<div style="padding-top:8px;float:left;">
						<div style="padding-top:15px;float:left;">
							<span style="margin-left:25px;">
								<b>Current Balance:</b> <input type="text" name="invoicebalance" tabindex="100" maxlength="32" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['invoicebalance']; ?>" onchange=" " disabled />
							</span>
						</div>
						<div style="margin-left:100px;float:left;">
							<span>
								# of Boxes:
							</span><br />
							<span>
								<input type="text" autocomplete="off" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $exchangeArr['totalboxes']; ?>" onchange=" " />
							</span>
						</div>
						<div style="margin-left:60px;float:left;">
							<span>
								Shipping Service:
							</span><br />
							<span>
								<input type="text" autocomplete="off" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $exchangeArr['shippingmethod']; ?>" onchange=" " />
							</span>
						</div>
					</div>
					<div style="padding-top:8px;float:left;">
						<div style="float:left;">
							<span>
								Description:
							</span><br />
							<span>
								<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $exchangeArr['description']; ?></textarea>
							</span>
						</div>
						<div style="margin-left:40px;float:left;">
							<span>
								Notes:
							</span><br />
							<span>
								<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $exchangeArr['notes']; ?></textarea>
							</span>
						</div>
					</div>
					<div style="width:100%;padding-top:8px;float:left;">
						<hr />
					</div>
					<div style="padding-top:8px;float:left;">
						<span>
							Additional Message:
						</span><br />
						<span>
							<textarea name="invoicemessage" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $exchangeArr['invoicemessage']; ?></textarea>
						</span>
					</div>
			<?php } ?>	
				<div style="clear:both;padding-top:8px;float:right;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
					<button name="formsubmit" type="submit" value="Save Exchange">Save</button>
				</div>
			</fieldset>
		</form>
		<?php
		if($exchangeArr['transactiontype']=='Shipment'){ ?>
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
					<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
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