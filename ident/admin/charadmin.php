<div id="addeditchar">
	<div style="float:right;margin:10px;">
		<a href="#" onclick="toggle('addchardiv');">
			<img src="../../images/add.png" alt="Create New Character" />
		</a>
	</div>
	<div id="addchardiv" style="display:none;">
		<form name="newcharform" action="index.php" method="post" onsubmit=" ">
			<fieldset>
				<legend><b>New Character</b></legend>
				<div style="padding-top:4px;">
					<span>
						Character Name:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="" />
					</span>
				</div>
				<div style="padding-top:4px;">
					<span>
						Entered By:
					</span>
					<span style="margin-left:55px;">
						Type:
					</span>
					<span style="margin-left:40px;">
						Difficulty:
					</span>
					<span style="margin-left:30px;">
						Language:
					</span>
					<span style="margin-left:55px;">
						Heading:
					</span>
				</div>
				<div style="padding-bottom:2px;">
					<span>
						<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
					</span>
					<span style="margin-left:15px;">
						<select name="chartype" style="width:55px;">
							<option value="">--</option>
							<option value="IN">IN</option>
							<option value="OM">OM</option>
							<option value="RN">RN</option>
							<option value="TE">TE</option>
							<option value="UM">UM</option>
						</select>
					</span>
					<span style="margin-left:15px;">
						<input type="text" autocomplete="off" name="difficultyrank" tabindex="96" maxlength="32" style="width:60px;" value="<?php //echo $loanArr['processedbyown']; ?>" onchange=" " />
					</span>
					<span style="margin-left:15px;">
						<select name="defaultlang" style="width:100px;">
							<option value="English">English</option>
							<option value="Spanish">Spanish</option>
						</select>
					</span>
					<span style="margin-left:15px;">
						<select name="hid" style="width:125px;">
							<option value="">Select Heading</option>
							<option value="">---------------------</option>
							<?php 
							$headingArr = $keyManager->getHeadingArr();
							foreach($headingArr as $k => $v){
								echo '<option value="'.$k.'">'.$v.'</option>';
							}
							?>
						</select>
					</span>
				</div>
				<div style="padding-top:8px;">
					<button name="formsubmit" type="submit" value="Create">Create</button>
				</div>
			</fieldset>
		</form>
	</div>
	<div id="charlist">
		<?php 
		$charHeadList = $keyManager->getCharHeadList();
		if($charHeadList){
			echo '<h3>Characters by Heading</h3>';
			echo '<ul>';
			foreach($charHeadList as $k => $charArr){
				echo '<li>';
				echo '<a href="#" onclick="toggle(\''.$k.'\');">'.$charArr['headingname'].'</a>';
				echo '<div id="'.$k.'" style="display:none;">';
				$charList = $keyManager->getCharacters($k);
				echo '<ul>';
				foreach($charList as $c => $charArr){
					echo '<li>';
					echo '<a href="index.php?cid='.$c.'">';
					echo $charArr['charname'].'</a>';
					echo '</li>';
				}
				echo '</ul>';
				echo '</div>';
				echo '</li>';
			}
			echo '</ul>';
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">There are no existing characters</div>';
		}
		?>
	</div>
</div>