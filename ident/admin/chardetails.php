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
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="<?php echo $charArr['charname']; ?>" />
					</span>
				</div>
				<div style="padding-top:8px;float:left;">
					<div style="float:left;">
						<span>
							Type:
						</span><br />
						<span>
							<select id="type" name="chartype" style="width:180px;" onchange="updateUnits(type);">
								<option value="" <?php echo ($charArr['chartype']==''?'SELECTED':'');?>>------------------------</option>
								<option value="UM" <?php echo ($charArr['chartype']=='UM'?'SELECTED':'');?>>Unordered Multi-state</option>
								<option value="IN" <?php echo ($charArr['chartype']=='IN'?'SELECTED':'');?>>Integer</option>
								<option value="RN" <?php echo ($charArr['chartype']=='RN'?'SELECTED':'');?>>Real Number</option>
							</select>
						</span>
					</div>
					<div style="margin-left:30px;float:left;">
						<span>
							Difficulty:
						</span><br />
						<span>
							<select name="difficultyrank" style="width:100px;">
								<option value="" <?php echo ($charArr['difficultyrank']==''?'SELECTED':'');?>>---------------</option>
								<option value="1" <?php echo ($charArr['difficultyrank']=='1'?'SELECTED':'');?>>Easy</option>
								<option value="2" <?php echo ($charArr['difficultyrank']=='2'?'SELECTED':'');?>>Intermediate</option>
								<option value="3" <?php echo ($charArr['difficultyrank']=='3'?'SELECTED':'');?>>Advanced</option>
								<option value="4" <?php echo ($charArr['difficultyrank']=='4'?'SELECTED':'');?>>Hidden</option>
							</select>
						</span>
					</div>
					<div id="units" style="display:<?php echo ((($charArr['chartype']=='IN')||($charArr['chartype']=='RN'))?'block':'none');?>;margin-left:30px;float:left;">
						<span>
							Units:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="units" tabindex="100" maxlength="32" style="width:100px;" value="<?php echo $charArr['units']; ?>" onchange="" title="" />
						</span>
					</div>
				</div>
				<div style="padding-top:8px;float:left;">
					<div style="float:left;">
						<span>
							Heading:
						</span><br />
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
					</div>
					<div style="margin-left:30px;float:left;">
						<span>
							Help URL:
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="helpurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php echo $charArr['helpurl']; ?>" onchange=" " />
						</span>
					</div>
				</div>
				<div style="padding-top:8px;float:left;">
					<span>
						Description:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="description" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['description']; ?>" onchange=" " />
					</span>
				</div>
				<div style="padding-top:8px;float:left;">
					<span>
						Notes:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="notes" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['notes']; ?>" onchange=" " />
					</span>
				</div>
				<div style="width:100%;padding-top:6px;float:left;">
					<div style="float:left;">
						<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
						<button name="formsubmit" type="submit" value="Save Char">Save</button>
					</div>
					<div style="float:right;">
						<span>
							Entered By:
						</span>
						<span>
							<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charArr['enteredby']; ?>" onchange=" " disabled />
						</span>
					</div>
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
						</span><br />
						<span>
							<input type="text" autocomplete="off" name="charstatename" maxlength="255" style="width:400px;" value="" />
						</span>
					</div>
					<div style="width:100%;padding-top:6px;float:left;">
						<div style="float:left;">
							<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
							<button name="formsubmit" type="submit" value="Add State">Add Character State</button>
						</div>
						<div style="float:right;">
							<span>
								Entered By:
							</span>
							<span>
								<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
							</span>
						</div>
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
					echo '<a href="#" onclick="toggle(\''.$k.'\');">'.$stateArr['charstatename'].'</a>';
					?>
					<div id="<?php echo $k; ?>" style="display:none;">
						<?php 
						//Show character state details
						$charStateArr = $keyManager->getCharStateDetails($cId,$k);
						?>
						<form name="editcharstateform" action="index.php" method="post">
							<fieldset>
								<legend>Character State Details</legend>
								<div>
									<span>
										Character State Name:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="charstatename" maxlength="255" style="width:400px;" value="<?php echo $charStateArr['charstatename']; ?>" />
									</span>
								</div>
								<div style="padding-top:2px;float:left;">
									<span>
										Illustration URL:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="illustrationurl" tabindex="100" maxlength="32" style="width:400px;" value="<?php echo $charStateArr['illustrationurl']; ?>" onchange=" " />
									</span>
								</div>
								<div style="padding-top:2px;float:left;">
									<span>
										Description:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="description" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charStateArr['description']; ?>" onchange=" " />
									</span>
								</div>
								<div style="padding-top:2px;float:left;">
									<span>
										Notes:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="notes" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charStateArr['notes']; ?>" onchange=" " />
									</span>
								</div>
								<div style="width:100%;padding-top:4px;float:left;">
									<div style="float:left;">
										<input name="cid" type="hidden" value="<?php echo $cId; ?>" />
										<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
										<button name="formsubmit" type="submit" value="Save State">Save</button>
									</div>
									<div style="margin-left:5px;float:left;">
										<button name="formsubmit" type="submit" value="Delete State">Delete</button>
									</div>
									<div style="float:right;">
										<span>
											Entered By:
										</span>
										<span>
											<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charStateArr['enteredby']; ?>" onchange=" " disabled />
										</span>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
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