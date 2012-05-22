<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;

$loanManager = new SpecLoans();
$loanManager->setCollId($collId);

?>
<div id="loanoutdiv" style="">
	<div style="float:right;">
		<form name='optionform' action='index.php' method='post'>
			<fieldset>
				<legend><b>Options</b></legend>
				<div>
					<b>Search: </b>
					<input type="text" autocomplete="off" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
				</div>
				<div>
					<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0?'checked':'');?> /> Display outstanding loans only
				</div>
				<div>
					<input type="radio" name="displayall" value="1"<?php echo ($displayAll?'checked':'');?> /> Display all loans
				</div>
				<div>
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
					<input type="submit" name="formsubmit" value="Refresh List" />
				</div>
			</fieldset>
		</form>	
	</div>
	<div style="float:right;margin:10px;">
		<a href="#" onclick="displayNewLoanOut();">
			<img src="../../images/add.png" alt="Create New Loan" />
		</a>
	</div>
	<div id="newloanoutdiv" style="display:none;">
		<form name="newloanoutform" action="index.php" method="post" onsubmit="return verfifyLoanOutAddForm(this)">
			<fieldset>
				<legend><b>New Loan</b></legend>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
					</span>
					<span style="float:right;">
						<b>Loan Identifier: </b><input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Send to Institution:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<select name="reqinstitution" style="width:400px;">
							<option value="0">Select Institution</option>
							<option value="0">------------------------------------------</option>
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
					<button name="formsubmit" type="submit" value="Create Loan Out">Create Loan</button>
				</div>
			</fieldset>
		</form>
	</div>
	<div>
		<?php 
		$loanOutList = $loanManager->getLoanOutList($searchTerm,$displayAll);
		if($loanOutList){
			echo '<h3>Outgoing Loan Records</h3>';
			echo '<ul>';
			foreach($loanOutList as $k => $loanArr){
				echo '<li>';
				echo '<a href="index.php?collid='.$collId.'&loanid='.$k.'&loantype=out">';
				echo $loanArr['loanidentifierown'];
				echo '</a>: '.$loanArr['institutioncode'].' ('.$loanArr['forwhom'].')';
				echo ' - '.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>OPEN</b>');
				echo '</li>';
			}
			echo '</ul>';
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">There are no loans out registered for this collection</div>';
		}
		?>
	</div>
	<div style="clear:both;">&nbsp;</div>
</div>
