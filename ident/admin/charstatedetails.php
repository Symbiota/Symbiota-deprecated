<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#charstatedetaildiv"><span>Details</span></a></li>
		<li><a href="#charstatedeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="charstatedetaildiv">
		<?php 
		//Show character state details
		$charStateArr = $keyManager->getCharStateDetails($cId,$cs);
		?>
		<form name="editcharstateform" action="index.php" method="post">
			<fieldset>
				<legend>Character State Details</legend>
				<div style="padding-top:4px;">
					<span>
						Character State Name:
					</span>
					<span style="margin-left:290px;">
						Language:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="charstatename" maxlength="255" style="width:400px;" value="<?php echo $charStateArr['charstatename']; ?>" />
					</span>
					<span style="margin-left:15px;">
						<select name="language" style="width:100px;">
							<option value="English" <?php echo ($charStateArr['language']=='English'?'SELECTED':'');?>>English</option>
							<option value="Spanish" <?php echo ($charStateArr['language']=='Spanish'?'SELECTED':'');?>>Spanish</option>
						</select>
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
					<span style="margin-left:55px;">
						Illustration URL:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charStateArr['enteredby']; ?>" onchange=" " disabled />
					</span>
					<span style="margin-left:15px;">
						<input type="text" autocomplete="off" name="illustrationurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php echo $charStateArr['illustrationurl']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Description:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="description" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charStateArr['description']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Notes:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="notes" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charStateArr['notes']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:8px;">
					<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
					<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
					<button name="formsubmit" type="submit" value="Save State">Save</button>
					<span style="float:right;">
						<a href="index.php?cid=<?php echo $cId; ?>#charstatediv">Back to character state list</a>
					</span>
				</div>
			</fieldset>
		</form>
	</div>
	<div id="charstatedeldiv">
		<form name="delcharstateform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character state?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Character State</b></legend>
				<?php 
				//if($charStateList){
				//	echo '<div style="font-weight:bold;margin-bottom:15px;">';
				//	echo 'Character state cannot be deleted until all character states are removed';
				//	echo '</div>';
				//}
				?>
				<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
				<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
				<button name="formsubmit" type="submit" value="Delete State">Delete</button>
			</fieldset>
		</form>
	</div>
</div>