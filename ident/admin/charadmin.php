<div id="tabs">
	<ul>
		<li><a href="#addeditchar"><span>Add/Edit Characters</span></a></li>
		<li><a href="#chardeldiv" onclick="instTransCheck(visibleiid);"><span>Admin</span></a></li>
	</ul>
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
							Entered By:
						</span>
						<span style="margin-left:50px;">
							Character Name:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<input type="text" autocomplete="off" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
						</span>
						<span style="margin-left:10px;">
							<input type="text" autocomplete="off" name="charname" maxlength="255" style="width:400px;" value="" />
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
			$charList = $keyManager->getCharList();
			if($charList){
				echo '<h3>Characters</h3>';
				echo '<ul>';
				foreach($charList as $k => $charArr){
					echo '<li>';
					echo '<a href="csadmin.php?cid='.$k.'">';
					echo $charArr['charname'];
					echo '</a>';
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
	<div id="chardeldiv">
		<form name="delinstform" action="ariz_addinstitution.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character?')">
			<fieldset style="width:350px;margin:20px;padding:20px;">
				<legend><b>Delete Character</b></legend>
				<input id="hiddeniid" name="hidiid" type="hidden" value="" />
				<input name="formsubmit" type="submit" value="Delete" />
			</fieldset>
		</form>
	</div>
</div>