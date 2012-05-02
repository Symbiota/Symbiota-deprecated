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

<div id="addspecdiv">
	<div style="float:right;margin:10px;">
		<a href="#" onclick="toggle('newspecdiv')">
			<img src="../../images/add.png" alt="Create New Loan" />
		</a>
	</div>
	<div id="newspecdiv" style="display:none;">
		<form name="addspecform" action="loans.php" method="post">
			<fieldset>
				<legend><b>Add Specimen</b></legend>
				<div style="padding-bottom:2px;">
					<span>
						<b>Catalog Number: </b><input type="text" name="catalognumber" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
					</span>
				</div>
				<div style="padding-top:8px;">
					<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<input name="formsubmit" type="button" value="Add Specimen" onclick="addSpecimen(this.form)" />
				</div>
			</fieldset>
		</form>
	</div>
	<?php 
	$specList = $loanManager->getSpecList($loanId);
	if($specList){
	?>
		<h3>Specimens on Loan</h3>
		<div style="margin-top:15px;">
			<span style="float:left;margin-left:15px;">
				<input name="" value="" type="checkbox" onclick="selectAll(this);" />
				Select/Deselect All
			</span>
			<span style="float:right;margin-right:15px;">
				<button name="formsubmit" type="submit" value="Refresh">Refresh List</button>
			</span>
		</div>
		<table class="styledtable">
			<th style="width:25px;text-align:center;"> </th>
			<th style="width:150px;text-align:center;">Catalog Number</th>
			<th style="width:400px;text-align:center;">Scientific Name</th>
			<?php
			foreach($specList as $k => $specArr){
				echo '<tr>';
				echo '<td>';
				echo '<input name="occid[]" type="checkbox" value=" " />';
				echo '</td>';
				echo '<td>'.$specArr['catalognumber'].'</td>';
				echo '<td>'.$specArr['sciname'].'</td>';
				echo '</tr>';
			}
			echo '</table>';
	}
	else{
		echo '<div style="font-weight:bold;font-size:120%;">There are no specimens registered for this loan.</div>';
	}
	?>
	<table>
		<tr>
			<td colspan="10" valign="bottom">
				<div style="margin:10px;">
					<div style="float:left;">
						<input name="applytask" type="radio" value="delete" CHECKED title="Delete Specimens" />Delete Specimens from Loan<br/>
						<input name="applytask" type="radio" value="check" title="Check-in Specimens" />Check-in Specimens
					</div>
					<span style="margin-left:25px;">
						<input name="submitstr" type="submit" value="Perform Action" />
						<input name="collid" type="hidden" value="<?php/* echo $collId; */?>" />
					</span>
				</div>
			</td>
		</tr>
	</table>
</div>