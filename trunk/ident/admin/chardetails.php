<?php 
$charStateList = $keyManager->getCharStateList($cId);
?>
<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#chardetaildiv"><span>Details</span></a></li>
		<li><a href="#charstatediv"><span>Character States</span></a></li>
		<li><a href="#chardeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="chardetaildiv">
		<?php 
		//Show character details
		$charArr = $keyManager->getCharDetails($cId);
		?>
		<form name="editcharform" action="index.php" method="post">
			<fieldset>
				<legend>Character Details</legend>
				<div style="padding-top:4px;">
					<span>
						Character Name:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="<?php echo $charArr['charname']; ?>" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
					<span style="margin-left:65px;">
						Type:
					</span>
					<span style="margin-left:50px;">
						Difficulty:
					</span>
					<span style="margin-left:40px;">
						Language:
					</span>
					<span style="margin-left:65px;">
						Units:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charArr['enteredby']; ?>" onchange=" " disabled />
					</span>
					<span style="margin-left:25px;">
						<select name="chartype" style="width:55px;">
							<option value="" <?php echo ($charArr['chartype']==''?'SELECTED':'');?>>--</option>
							<option value="IN" <?php echo ($charArr['chartype']=='IN'?'SELECTED':'');?>>IN</option>
							<option value="OM" <?php echo ($charArr['chartype']=='OM'?'SELECTED':'');?>>OM</option>
							<option value="RN" <?php echo ($charArr['chartype']=='RN'?'SELECTED':'');?>>RN</option>
							<option value="TE" <?php echo ($charArr['chartype']=='TE'?'SELECTED':'');?>>TE</option>
							<option value="UM" <?php echo ($charArr['chartype']=='UM'?'SELECTED':'');?>>UM</option>
						</select>
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="difficultyrank" tabindex="96" maxlength="32" style="width:60px;" value="<?php echo $charArr['difficultyrank']; ?>" onchange=" " />
					</span>
					<span style="margin-left:25px;">
						<select name="defaultlang" style="width:100px;">
							<option value="English" <?php echo ($charArr['defaultlang']=='English'?'SELECTED':'');?>>English</option>
							<option value="Spanish" <?php echo ($charArr['defaultlang']=='Spanish'?'SELECTED':'');?>>Spanish</option>
						</select>
					</span>
					<span style="margin-left:25px;">
						<input type="text" autocomplete="off" name="units" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $charArr['units']; ?>" onchange="" title="" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Heading:
					</span>
					<span style="margin-left:90px;">
						Help URL:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<select name="hid" style="width:125px;">
							<option value="">Select Heading</option>
							<option value="">---------------------</option>
							<?php 
							$headingArr = $keyManager->getHeadingArr();
							foreach($headingArr as $k => $v){
								echo '<option value="'.$k.'" '.($k==$charArr['hid']?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</span>
					<span style="margin-left:15px;">
						<input type="text" autocomplete="off" name="helpurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php echo $charArr['helpurl']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Description:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="description" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['description']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Notes:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="notes" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['notes']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:8px;">
					<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
					<button name="formsubmit" type="submit" value="Save Char">Save</button>
				</div>
			</fieldset>
		</form>
	</div>
	<div id="charstatediv">
		<div style="float:right;margin:10px;">
			<a href="#" onclick="toggle('newstatediv');">
				<img src="../../images/add.png" alt="Create New Character State" />
			</a>
		</div>
		<div id="newstatediv" style="display:none;">
			<form name="addstateform" action="index.php" method="post" onsubmit="">
				<fieldset>
					<legend><b>Add Character State</b></legend>
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
							<input type="text" autocomplete="off" name="charstatename" maxlength="255" style="width:400px;" value="" />
						</span>
						<span style="margin-left:15px;">
							<select name="language" style="width:100px;">
								<option value="English">English</option>
								<option value="Spanish">Spanish</option>
							</select>
						</span>
					</div>
					<div style="padding-top:4px;">
						<span>
							Entered By:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
						</span>
					</div>
					<div style="padding-top:8px;clear:both;">
						<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
						<button name="formsubmit" type="submit" value="Add State">Add Character State</button>
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		if($charStateList){
		?>
			<form name="stateeditform" action="index.php?cid=<?php echo $cId; ?>#charstatediv" method="post" onsubmit=" " >
				<?php 
				echo '<h3>Character States</h3>';
				echo '<ul>';
				foreach($charStateList as $k => $stateArr){
					echo '<li>';
					echo '<a href="index.php?cid='.$cId.'&cs='.$k.'">';
					echo $stateArr['charstatename'];
					echo '</a>';
					echo '</li>';
				}
				echo '</ul>';
				?>
			</form>
		<?php
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">There are no character states for this character.</div>';
		}
		?>
	</div>
	<div id="chardeldiv">
		<form name="delcharform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Character</b></legend>
				<?php 
				if($charStateList){
					echo '<div style="font-weight:bold;margin-bottom:15px;">';
					echo 'Character cannot be deleted until all character states are removed';
					echo '</div>';
				}
				?>
				<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
				<button name="formsubmit" type="submit" value="Delete Char" <?php if($charStateList) echo 'DISABLED'; ?>>Delete</button>
			</fieldset>
		</form>
	</div>
</div>