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
				<div>
					<span>
						Character Name:
					</span><br />
					<span>
						<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="" />
					</span>
				</div>
				<div style="padding-top:6px;float:left;">
					<div style="float:left;">
						<span>
							Type:
						</span><br />
						<span>
							<select name="chartype" style="width:180px;">
								<option value="">------------------------</option>
								<option value="UM">Unordered Multi-state</option>
								<option value="IN">Integer</option>
								<option value="RN">Real Number</option>
							</select>
						</span>
					</div>
					<div style="margin-left:30px;float:left;">
						<span>
							Difficulty:
						</span><br />
						<span>
							<select name="difficultyrank" style="width:100px;">
								<option value="">---------------</option>
								<option value="1">Easy</option>
								<option value="2">Intermediate</option>
								<option value="3">Advanced</option>
								<option value="4">Hidden</option>
							</select>
						</span>
					</div>
					<div style="margin-left:30px;float:left;">
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
									echo '<option value="'.$k.'">'.$v.'</option>';
								}
								?>
							</select>
						</span>
					</div>
				</div>
				<div style="width:100%;padding-top:6px;float:left;">
					<div style="float:left;">
						<button name="formsubmit" type="submit" value="Create">Create</button>
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
	<div id="charlist">
		<?php 
		$charHeadList = $keyManager->getCharHeadList();
		if($charHeadList){
			echo '<h3>Characters by Heading</h3>';
			echo '<ul>';
			foreach($charHeadList as $k => $charArr){
				echo '<li>';
				echo '<a href="#" onclick="toggle(\''.$k.'\');">'.$charArr['headingname'].'</a>';
				echo '<div id="'.$k.'" style="display:block;">';
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