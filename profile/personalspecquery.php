<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PersonalSpecimenManager.php');

$collId = $_REQUEST["collid"];
$occId = array_key_exists("occid",$_REQUEST)?$_REQUEST["occid"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$specHandler = new PersonalSpecimenManager();

$isEditor = 0;
if($symbUid){
	$specHandler->setUid($symbUid);
	if($collId){
		if($isAdmin	|| (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))
			|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
			$isEditor = 1;
		}
	}
}

$statusStr = '';
if($isEditor){
	if($formSubmit){
		if($formSubmit == ''){

		}
		elseif($formSubmit == ''){
			
		}
		
	}
}

?>
<div style="margin:10px;">

<?php 
if($collId && $symbUid){
	//Collection is defined and User is logged-in and have permissions
	if($statusStr){
		?>
		<hr/>
		<div style="margin:15px;color:red;">
			<?php echo $statusStr; ?>
		</div>
		<hr/>
		<?php 
	}
	?>
	<div>
		<form name="obtionsform" action="personalspec.php" method="post">
			Project: 
			<select name="collid" onchange="this.form.submit()">
				<?php 
				foreach($collArr as $k => $v){
					echo '<option value="'.$k.'" '.($collId==$k?'SELECTED':'').'>'.$v.'</option>'."<br/>";
				}
				?>
			</select>
		</form>
	</div>
	<div style="margin:10px;">
		<b>Total Record Count:</b> <?php echo $specHandler->getRecordCount(); ?>
	</div>
	<div>
		<fieldset style="margin:15px;">
			<legend style="font-weight:bold;">Main Menu</legend>
			<ul>
				<li>Show all records</li>
				<li>Query records or open a saved dataset</li>
					<li>Display as list</li>
					<li>Display as table</li>
					
					<li>Edit individual record</li>
					<li>Select records to create dataset</li>
						<li>Save dataset</li>
						<li>Open in label maker</li>
						<li>Export records as CSV</li>
						<li>Mass update certain fields</li>
			</ul>
		</fieldset>
	</div>	
	<?php 
}
else{
	if(!$collId){
		echo '<h2>ERROR: Collection identifier is null</h2>';
	}
	else{
		echo '<h2>Please <a href="../profile/index.php?&refurl='.$clientRoot.'/profile/personalspec.php?collid='.$collId.'">login</a></h2>';
	}
}
?>	
</div>

