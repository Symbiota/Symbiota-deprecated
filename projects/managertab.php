<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/InventoryProjectManager.php');
header("Content-Type: text/html; charset=".$charset);

$pid = $_REQUEST["pid"]; 

$projManager = new InventoryProjectManager();
$projManager->setPid($pid);

$isEditable = 0;
if($isAdmin || (array_key_exists("ProjAdmin",$userRights) && in_array($pid,$userRights["ProjAdmin"]))){
	$isEditable = 1;
}

?>
<div id="managertab">
	<div style="font-weight:bold;margin:10px 0px">Inventory Project Managers</div>
	<ul style="margin:10px">
	<?php 
	$managerArr = $projManager->getManagers();
	if($managerArr){
		foreach($managerArr as $uid => $userName){
			echo '<li title="'.$uid.'">';
			echo $userName.' <a href="index.php?tabindex=1&emode=1&projsubmit=deluid&pid='.$pid.'&uid='.$uid.'" title="Remove manager"><img src="../images/del.png" style="width:13px;" /></a>';
			echo '</li>';
		}
	}
	else{
		echo '<div style="margin:15px">No managers have been assigned to this project</div>';
	}
	?>
	</ul>
	<fieldset style="margin-top:40px;padding:20px;">
		<legend><b>Add a New Manager</b></legend>
		<form name='manageraddform' action='index.php' method='post' onsubmit="return validateManagerAddForm(this)">
			<select name="uid" style="width:450px;">
				<option value="0">Select a User</option>
				<option value="0">------------------------</option>
				<?php 
				$newManagerArr = $projManager->getPotentialManagerArr();
				foreach($newManagerArr as $uid => $userName){
					echo '<option value="'.$uid.'">'.$userName.'</option>';
				}
				?>
			</select>
			<input name="pid" type="hidden" value="<?php echo $pid; ?>" /> 
			<input name="tabindex" type="hidden" value="1" /> 
			<input name="emode" type="hidden" value="1" /> 
			<input name="projsubmit" type="submit" value="Add to Manager List" />
		</form>
	</fieldset>
</div>