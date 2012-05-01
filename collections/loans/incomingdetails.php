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
						$loanArr = $loanManager->getLoanInDetails($loanId);
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
											Date Received:
										</span>
										<span style="margin-left:30px;">
											Date Due:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<b>Loan Number:</b> <input type="text" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($loanArr['loanidentifierborr']?$loanArr['loanidentifierborr']:$loanArr['loanidentifierown']); ?>" />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="createdbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo ($loanArr['createdbyborr']?$loanArr['createdbyborr']:$paramsArr['un']); ?>" onchange=" " disabled />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyborr']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="datereceivedborr" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedborr']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
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
											<input type="text" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" <?php echo ($loanArr['collidown']?'disabled':''); ?> />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Requested for:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<b>Specimen Total:</b> <input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php if($loanArr['collidown']){echo ($specTotal?$specTotal['speccount']:0) ;}else{echo $loanArr['numspecimens'] ;} ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
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
											<input type="text" name="datesentreturn" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesentreturn']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedbyreturnborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnborr']; ?>" onchange=" " />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="totalboxesreturned" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxesreturned']; ?>" onchange=" " />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="shippingmethodreturn" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethodreturn']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
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
							<fieldset>
							<legend>Generate Loan Paperwork</legend>
								<button name="formsubmit" type="submit" value="Save Outgoing">Invoice</button>
								<button name="formsubmit" type="submit" value="Save Outgoing">Mailing Label</button>
								<button name="formsubmit" type="submit" value="Save Outgoing">Envelope</button>
							</fieldset>
						</form>
						<?php
						//}
						?>
					</div>