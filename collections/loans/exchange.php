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
<div id="newexchangediv" style="display:none;width:550px;">
	<form name="newexchangegiftform" action="index.php" method="post" onsubmit="return verfifyExchangeAddForm(this)">
		<fieldset>
			<legend>New Gift/Exchange</legend>
			<div style="padding-top:4px;float:left;">
				<span>
					<b>Transaction Number:</b> 
					<input type="text" autocomplete="off" id="identifier" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" onchange="exIdentCheck(identifier,<?php echo $collId; ?>);" />
				</span>
			</div>
			<div style="padding-top:6px;margin-left:20px;float:left;">
				<span>
					Transaction Type:
				</span><br />
				<span>
					<select name="transactiontype" style="width:100px;" >
						<option value="Shipment" SELECTED >Shipment</option>
						<option value="Adjustment">Adjustment</option>
					</select>
				</span>
			</div>
			<div style="padding-top:6px;margin-left:20px;float:left;">
				<span>
					Entered By:
				</span><br />
				<span>
					<input type="text" autocomplete="off" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
				</span>
			</div><br />
			<div style="padding-top:6px;float:left;">
				<span>
					Institution:
				</span><br />
				<span>
					<select name="iid" style="width:400px;" >
						<option value="">Select Institution</option>
						<option value="">------------------------------------------</option>
						<?php 
						$instArr = $loanManager->getInstitutionArr();
						foreach($instArr as $k => $v){
							echo '<option value="'.$k.'">'.$v.'</option>';
						}
						?>
					</select>
				</span>
			</div>
			<div style="padding-top:20px;float:right;">
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
			echo '<a href="#" onclick="toggle(\''.$k.'\');">'.$transArr['institutioncode'].'</a>';
			echo ' (Balance: '.($transArr['invoicebalance'] < 0?'<span style="color:red;font-weight:bold;">'.$transArr['invoicebalance'].'</span>':$transArr['invoicebalance']).')';
			echo '<div id="'.$k.'" style="display:none;">';
			$transList = $loanManager->getTransactions($collId,$k);
			echo '<ul>';
			foreach($transList as $t => $transArr){
				echo '<li>';
				echo '<a href="index.php?collid='.$collId.'&exchangeid='.$t.'&loantype=exchange">';
				echo '#'.$transArr['identifier'].'</a>: ';
				if($transArr['transactiontype'] == 'Shipment'){
					if($transArr['in_out'] == 'Out'){
						echo 'Outgoing exchange; Sent ';
						echo $transArr['datesent'].'; Including: ';
					}
					else{
						echo 'Incoming exchange, received ';
						echo $transArr['datereceived'].', including: ';
					}
					echo ($transArr['totalexmounted']?$transArr['totalexmounted'].' mounted, ':'');
					echo ($transArr['totalexunmounted']?$transArr['totalexunmounted'].' unmounted, ':'');
					echo ($transArr['totalgift']?$transArr['totalgift'].' gift, ':'');
					echo ($transArr['totalgiftdet']?$transArr['totalgiftdet'].' gift-for-det, ':'');
					echo 'Balance: '.$transArr['invoicebalance'];
				}
				else{
					echo 'Adjustment of '.$transArr['adjustment'].' specimens';
				}
				echo '</li>';
			}
			echo '</ul>';
			echo '</div>';
			echo '</li>';
		}
		echo '</ul>';
	}
	else{
		'<div style="font-weight:bold;font-size:120%;">There are no transactions registered for this collection</div>';
	}
	?>
<ul id="transactionlist"></ul>
</div>
