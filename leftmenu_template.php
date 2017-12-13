<div class="menu">
	<div class="menuheader">
		<a href="<?php echo $CLIENT_ROOT; ?>/index.php">
			<?php echo $DEFAULT_TITLE; ?> Homepage
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/collections/index.php">
			Search Collections
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/collections/map/index.php" target="_blank">
			Map Search
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php">
			Flora Projects
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=1">
			Blabla Flora
		</a>
	</div>
	<div class="menuitem">
    	<a href="<?php echo $CLIENT_ROOT; ?>/agents/index.php">
    		Agents
    	</a>
    </div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/checklists/dynamicmap.php?interface=checklist">
			Dynamic Checklist
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/checklists/dynamicmap.php?interface=key">
			Dynamic Key
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/index.php">
			Image Library
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/imgsearch.php">
			Search Images
		</a>
	</div>
	<div>
		<hr/>
	</div>
	<?php
	if($userDisplayName){
	?>
		<div class='menuitem'>
			Welcome <?php echo $userDisplayName; ?>!
		</div>
		<div class="menuitem">
			<a href="<?php echo $CLIENT_ROOT; ?>/profile/viewprofile.php">My Profile</a>
		</div>
		<div class="menuitem">
			<a href="<?php echo $CLIENT_ROOT; ?>/profile/index.php?submit=logout">Logout</a>
		</div>
	<?php
	}
	else{
	?>
		<div class="menuitem">
			<a href="<?php echo $CLIENT_ROOT."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">
				Log In
			</a>
		</div>
		<div class="menuitem">
			<a href="<?php echo $CLIENT_ROOT; ?>/profile/newprofile.php">
				New Account
			</a>
		</div>
	<?php
	}
	?>
	<div class='menuitem'>
		<a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php'>Sitemap</a>
	</div>
</div>
