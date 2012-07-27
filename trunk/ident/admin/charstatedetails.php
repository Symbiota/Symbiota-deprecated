<div id="tabs" style="margin:0px;">
    <ul>
		<li><a href="#charstatedetaildiv"><span>Details</span></a></li>
		<li><a href="#chardeldiv"><span>Admin</span></a></li>
	</ul>
	<div id="charstatedetaildiv">
		<?php 
		//Show character details
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
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="<?php echo $charArr['charname']; ?>" />
					</span>
					<span style="margin-left:15px;">
						<select name="defaultlang" style="width:100px;">
							<option value="English" <?php echo ($charArr['defaultlang']=='English'?'SELECTED':'');?>>English</option>
							<option value="Spanish" <?php echo ($charArr['defaultlang']=='Spanish'?'SELECTED':'');?>>Spanish</option>
						</select>
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
					<span style="margin-left:65px;">
						Illustration URL:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charArr['enteredby']; ?>" onchange=" " disabled />
					</span>
					<span style="margin-left:15px;">
						<input type="text" autocomplete="off" name="helpurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php echo $charArr['helpurl']; ?>" onchange=" " />
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
			<a href="#" onclick="toggle('newstatediv');toggle('refreshbut');">
				<img src="../../images/add.png" alt="Create New Character State" />
			</a>
		</div>
		<div id="newstatediv" style="display:none;">
			<form name="addstateform" action="index.php" method="post" onsubmit="return false">
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
			<div style="height:25px;margin-top:15px;">
				<span id="refreshbut" style="display:none;float:right;margin-right:15px;">
					<form name="refreshstatelist" action="index.php?cid=<?php echo $cId; ?>#charstatediv" method="post">
						<button name="formsubmit" type="submit" value="Refresh">Refresh List</button>
					</form>
				</span>
			</div>
			<form name="stateeditform" action="index.php?cid=<?php echo $cId; ?>#charstatediv" method="post" onsubmit=" " >
				<?php 
				echo '<h3>Character States</h3>';
				echo '<ul>';
				foreach($charStateList as $k => $stateArr){
					echo '<li>';
					//echo '<a href="index.php?cid='.$k.'">';
					echo $stateArr['charstatename'];
					//echo '</a>';
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