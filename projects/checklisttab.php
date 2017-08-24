<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/InventoryProjectManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$pid = $_REQUEST["pid"]; 

$projManager = new InventoryProjectManager();
$projManager->setPid($pid);

?>
<div id="cltab">
	<div style="margin:10px;">
		<form name='claddform' action='index.php' method='post' onsubmit="return validateChecklistForm(this)">
			<fieldset class="form-color">
				<legend><b>Add a Checklist</b></legend>
				<select name="clid" style="width:450px;">
					<option value="">Select Checklist to Add</option>
					<option value="">-----------------------------------------</option>
					<?php 
					$addArr = $projManager->getClAddArr();
					foreach($addArr as $clid => $clName){
						echo "<option value='".$clid."'>".$clName."</option>\n";
					}
					?>
				</select><br/>
				<input type="hidden" name="pid" value="<?php echo $pid;?>">
				<input type="submit" name="projsubmit" value="Add Checklist" />
			</fieldset>
		</form>
	</div>
	<div style="margin:10px;">
		<form name='cldeleteform' action='index.php' method='post' onsubmit="return validateChecklistForm(this)">
			<fieldset class="form-color">
				<legend><b>Delete a Checklist</b></legend>
				<select name="clid" style="width:450px;">
					<option value="">Select Checklist to Delete</option>
					<option value="">-----------------------------------------</option>
					<?php 
					$delArr = $projManager->getClDeleteArr();
					foreach($delArr as $clid => $clName){
						echo "<option value='".$clid."'>".$clName."</option>\n";
					}
					?>
				</select><br/>
				<input type="hidden" name="pid" value="<?php echo $pid;?>">
				<input type="submit" name="projsubmit" value="Delete Checklist" />
			</fieldset>
		</form>
	</div>
</div>