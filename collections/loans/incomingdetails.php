<?php 
$specList = $loanManager->getSpecList($loanId);
?>
<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#loandiv"><span>Loan Details</span></a></li>
		<?php 
		if($specList){
			?>
			<li><a href="#specdiv"><span>Specimens</span></a></li>
			<?php 
		}
		?>
		<li><a href="#inloandeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="loandiv">
		<?php 
		//Show loan details
		$loanArr = $loanManager->getLoanInDetails($loanId);
		?>
		<form name="editloanform" action="index.php" method="post">
			<fieldset>
				<legend>Loan Details</legend>
				<div style="padding-top:4px;">
					<span style="margin-left:235px;">
						Entered By:
					</span>
					<span style="margin-left:70px;">
						Processed By:
					</span>
					<span style="margin-left:50px;">
						Date Received:
					</span>
					<span style="margin-left:30px;">
						Date Due:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<b>Loan Number:</b> <input type="text" autocomplete="off" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($loanArr['loanidentifierborr']?$loanArr['loanidentifierborr']:$loanArr['loanidentifierown']); ?>" />
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="createdbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo ($loanArr['createdbyborr']?$loanArr['createdbyborr']:$paramsArr['un']); ?>" onchange=" " disabled />
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="processedbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyborr']; ?>" onchange=" " />
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="datereceivedborr" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedborr']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange="verifyDueDate(this);" title="format: yyyy-mm-dd" <?php echo ($loanArr['collidown']?'disabled':''); ?> />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Sent From:
					</span>
					<span style="margin-left:430px;">
						Sender's Loan Number:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<select name="iidowner" style="width:400px;" disabled >
							<?php 
							$instArr = $loanManager->getInstitutionArr();
							foreach($instArr as $k => $v){
								echo '<option value="'.$k.'" '.($k==$loanArr['iidowner']?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</span>
					<span style="margin-left:90px;">
						<input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" <?php echo ($loanArr['collidown']?'disabled':''); ?> />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Requested for:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
					</span>
					<span style="margin-left:25px;">
						<b>Specimen Total:</b> <input type="text" autocomplete="off" name="numspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($loanArr['collidown']?count($specList):$loanArr['numspecimens']); ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Loan Description:
					</span>
					<span style="margin-left:270px;">
						Notes:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" " <?php echo ($loanArr['collidown']?'disabled="disabled"':''); ?> ><?php echo $loanArr['description']; ?></textarea>
					</span>
					<span style="margin-left:40px;">
						<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" " <?php echo ($loanArr['collidown']?'disabled="disabled"':''); ?> ><?php echo $loanArr['notes']; ?></textarea>
					</span>
				</div>
				<hr />
				<div style="padding-top:4px;">
					<span>
						Date Returned:
					</span>
					<span style="margin-left:30px;">
						Ret. Processed By:
					</span>
					<span style="margin-left:30px;">
						# of Boxes:
					</span>
					<span style="margin-left:25px;">
						Shipping Service:
					</span>
					<span style="margin-left:115px;">
						Date Closed:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="datesentreturn" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesentreturn']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="processedbyreturnborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnborr']; ?>" onchange=" " />
					</span>
					<span style="margin-left:30px;">
						<input type="text" autocomplete="off" name="totalboxesreturned" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxesreturned']; ?>" onchange=" " />
					</span>
					<span style="margin-left:30px;">
						<input type="text" autocomplete="off" name="shippingmethodreturn" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethodreturn']; ?>" onchange=" " />
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange="verifyDate(this);" title="format: yyyy-mm-dd" <?php echo ($loanArr['collidown']?'disabled':''); ?> />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Additional Invoice Message:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<textarea name="invoicemessageborr" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageborr']; ?></textarea>
					</span>
				</div>
				<div style="padding-top:8px;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<input name="collidborr" type="hidden" value="<?php echo $collId; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<button name="formsubmit" type="submit" value="Save Incoming">Save</button>
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
				<?php 
				if($specList){ ?>
					<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="spec">Specimen List</button>
				<?php } ?>
				<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="label">Mailing Label</button>
				<button name="formsubmit" type="submit" onclick="document.pressed=this.value" value="envelope">Envelope</button>
			</fieldset>
		</form>
	</div>
	<?php 
	if($specList){
		?>
		<div id="specdiv">
			<table class="styledtable">
				<tr>
					<th style="width:100px;text-align:center;">Catalog Number</th>
					<th style="width:375px;text-align:center;">Details</th>
					<th style="width:75px;text-align:center;">Date Returned</th>
				</tr>
				<?php
				foreach($specList as $k => $specArr){
					?>
					<tr>
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
		</div>
		<?php 
	}
	?>
	<div id="inloandeldiv">
		<form name="delinloanform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this loan?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Incoming Loan</b></legend>
				<?php 
				if($specList){
					echo '<div style="font-weight:bold;margin-bottom:15px;">';
					echo 'Loan cannot be delted until all linked specimens are removed';
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