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

	<div id="loanindiv" style="">
						<div style="float:right;">
							<form name='optionform' action='loans.php' method='post'>
								<fieldset>
								    <legend><b>Options</b></legend>
							    	<div>
							    		<b>Search:</b> 
										<input type="text" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
									</div>
									<div>
										<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0?'checked':'');?> /> 
										Display outstanding loans only
									</div>
									<div>
										<input type="radio" name="displayall" value="1"<?php echo ($displayAll?'checked':'');?> /> 
										Display all loans
									</div>
									<div>
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="submit" name="formsubmit" value="Refresh List" />
									</div>
								</fieldset>
							</form>	
						</div>
						<div style="float:right;margin:10px;">
							<a href="#" onclick="toggle('newloanindiv')">
								<img src="../../images/add.png" alt="Create New Loan" />
							</a>
						</div>
						<div id="newloanindiv" style="display:none;">
							<form name="newloaninform" action="loans.php" method="post">
								<fieldset>
									<legend><b>New Loan</b></legend>
									<div style="padding-top:4px;">
										<span>
											Entered By:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="createdbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
										<span style="float:right;">
											<b>Loan Identifier: </b> 
											<input type="text" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
									</div>
									<div style="padding-top:6;">
										<span>
											Sent From:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="iidowner" style="width:400px;">
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<button name="formsubmit" type="submit" value="Create Loan In">Create Loan</button>
									</div>
								</fieldset>
							</form>
						</div>
						<div>
							<?php 
							$loansOnWay = $loanManager->getLoanOnWayList();
							if($loansOnWay){
								echo '<h3>Loans on Their Way</h3>';
								echo '<ul>';
								foreach($loansOnWay as $k => $loanArr){
									echo '<li>';
									echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'&loantype=In">';
									echo $loanArr['loanidentifierown'];
									echo ' from '.$loanArr['collectionname'].'</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo '<div style="font-weight:bold;font-size:120%;">There are no loans on their way to this collection.</div>';
							}
							?>
						</div>
						<div>
							<?php 
							$loanInList = $loanManager->getLoanInList($searchTerm,$displayAll);
							if($loanInList){
								echo '<h3>Incoming Loan Records</h3>';
								echo '<ul>';
								foreach($loanInList as $k => $loanArr){
									echo '<li>';
									echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'&loantype=In">';
									echo $loanArr['loanidentifierborr'];
									echo '</a> ('.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>OPEN</b>').')';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo '<div style="font-weight:bold;font-size:120%;">There are no loans in registered for this collection</div>';
							}
							?>
						</div>
						<div style="clear:both;">&nbsp;</div>
					</div>
