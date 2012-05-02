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
	<form name="editloanform" action="loans.php" method="post">
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
					<b>Loan Number:</b> <input type="text" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" disabled />
				</span>
				<span style="margin-left:25px;">
					<input type="text" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['createdbyown']; ?>" onchange=" " disabled />
				</span>
				<span style="margin-left:25px;">
					<input type="text" name="processedbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyown']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<input type="text" name="datesent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesent']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<input type="text" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange=" " />
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
					<input type="text" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<b>Specimen Total:</b> <input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($specTotal?$specTotal['speccount']:0);?>" onchange=" " disabled />
				</span>
				<span style="margin-left:30px;">
					<input type="text" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxes']; ?>" onchange=" " />
				</span>
				<span style="margin-left:30px;">
					<input type="text" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethod']; ?>" onchange=" " />
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
					<input type="text" name="datereceivedown" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedown']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<input type="text" name="processedbyreturnown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnown']; ?>" onchange=" " />
				</span>
				<span style="margin-left:25px;">
					<input type="text" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange=" " />
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
		<fieldset>
			<legend>Generate Loan Paperwork</legend>
			<div style="float:left;margin-right:100px;">
				<input type="radio" name="print" value="browser" checked /> Print in Browser<br/>
				<input type="radio" name="print" value="doc" /> Export to doc
			</div>
			<button name="formsubmit" type="submit" value="Save Outgoing">Invoice</button>
			<button name="formsubmit" type="submit" value="Save Outgoing">Specimen List</button>
			<button name="formsubmit" type="submit" value="Save Outgoing">Mailing Label</button>
			<button name="formsubmit" type="submit" value="Save Outgoing">Envelope</button>
		</fieldset>
	</form>
</div>