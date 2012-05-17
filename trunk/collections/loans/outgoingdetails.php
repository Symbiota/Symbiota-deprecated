<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$loanManager = new SpecLoans();
if($collId) $loanManager->setCollId($collId);

?>

<div id="loandiv">
	<?php 
	//Show loan details
	$loanArr = $loanManager->getLoanOutDetails($loanId);
	$specTotal = $loanManager->getSpecTotal($loanId);
	//$loanDetails = $loanManager->getLoanDetails($loanId);
	//foreach($loanDetails as $k => $loanArr){
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
					Date Sent:
				</span>
				<span style="margin-left:55px;">
					Date Due:
				</span>
			</div>
			<div style="padding-bottom:2px;">
				<span>
					<b>Loan Number:</b> <input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" disabled />
				</span>
				<span style="margin-left:25px;">
					<input type="text" autocomplete="off" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['createdbyown']; ?>" onchange=" " disabled />
				</span>
				<span style="margin-left:25px;">
					<input type="text" autocomplete="off" name="processedbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyown']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<input type="text" autocomplete="off" name="datesent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesent']; ?>" onchange="eventDateModified(this);" />
				</span>
				<span style="margin-left:25px;">
					<input type="text" autocomplete="off" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange="eventDateModified(this);" />
				</span>
			</div>
			<div style="padding-top:4px;">
				<span>
					Sent To:
				</span>
			</div>
			<div style="padding-bottom:2px;">
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
			<div style="padding-top:4px;">
				<span>
					Requested for:
				</span>
				<span style="margin-left:340px;">
					# of Boxes:
				</span>
				<span style="margin-left:25px;">
					Shipping Service:
				</span>
			</div>
			<div style="padding-bottom:2px;">
				<span>
					<input type="text" autocomplete="off" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<b>Specimen Total:</b> <input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($specTotal?$specTotal['speccount']:0);?>" onchange=" " disabled />
				</span>
				<span style="margin-left:30px;">
					<input type="text" autocomplete="off" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxes']; ?>" onchange=" " />
				</span>
				<span style="margin-left:30px;">
					<input type="text" autocomplete="off" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethod']; ?>" onchange=" " />
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
					<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['description']; ?></textarea>
				</span>
				<span style="margin-left:40px;">
					<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['notes']; ?></textarea>
				</span>
			</div>
			<hr />
			<div style="padding-top:4px;">
				<span>
					Date Received:
				</span>
				<span style="margin-left:30px;">
					Ret. Processed By:
				</span>
				<span style="margin-left:25px;">
					Date Closed:
				</span>
			</div>
			<div style="padding-bottom:2px;">
				<span>
					<input type="text" autocomplete="off" name="datereceivedown" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedown']; ?>" onchange="eventDateModified(this);" />
				</span>
				<span style="margin-left:25px;">
					<input type="text" autocomplete="off" name="processedbyreturnown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnown']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<input type="text" autocomplete="off" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange="eventDateModified(this);" />
				</span>
			</div>
			<div style="padding-top:4px;">
				<span>
					Additional Invoice Message:
				</span>
			</div>
			<div style="padding-bottom:2px;">
				<span>
					<textarea name="invoicemessageown" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageown']; ?></textarea>
				</span>
			</div>
			<div style="padding-top:8px;">
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