<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;

$loanManager = new SpecLoans();
if($collId) $loanManager->setCollId($collId);

?>

<div style="float:right;margin:10px;">
	<a href="#" onclick="displayNewExchange()">
		<img src="../../images/add.png" alt="Create New Exchange" />
	</a>
</div>
<div id="newexchangediv" style="display:none;">
	<form name="newexchangegiftform" action="index.php" method="post" onsubmit="return verfifyExchangeAddForm(this)">
		<fieldset>
			<legend>New Gift/Exchange</legend>
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
					<b>Transaction Number:</b> 
					<input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
				</span>
				<span style="margin-left:40px;">
					<select name="transactiontype" style="width:100px;" >
						<option value="Shipment" SELECTED >Shipment</option>
						<option value="Adjustment">Adjustment</option>
					</select>
				</span>
				<span style="margin-left:40px;">
					<input type="text" autocomplete="off" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
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
				<button name="formsubmit" type="submit" value="Create Exchange">Create</button>
			</div>
		</fieldset>
	</form>
</div>
<div>
	<?php 
	$transInstList = $loanManager->getTransInstList($collId);
	if($transInstList){
		echo '<h3>Transaction Records by Institution</h3>';
		echo '<ul>';
		foreach($transInstList as $k => $transArr){
			echo '<li>';
			echo '<a href="#" onclick="toggle(\''.$k.'\')">'.$transArr['institutioncode'].'</a>';
			echo ' (Balance: '.$transArr['invoicebalance'].')';
			//echo '<div id="'.$k.'" style="display:none;"><ul><li>'.$transArr['exchangeid'].'</li></ul></div>';
			echo '</li>';
		}
		echo '</ul>';
	}
	else{
		'<div style="font-weight:bold;font-size:120%;">There are no transactions registered for this collection</div>';
	}
	?>
</div>
