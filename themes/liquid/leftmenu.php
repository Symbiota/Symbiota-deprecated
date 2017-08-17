<div class="menu">
	<div class="menuheader">
		<a href="<?php echo $clientRoot; ?>/index.php">
			<?php echo $defaultTitle; ?> Homepage
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/collections/index.php">
			Search Collections
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/collections/map/mapinterface.php" target="_blank">
			Map Search
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/projects/index.php">
			Flora Projects
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=1">
			Blabla Flora
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist">
			Dynamic Checklist
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key">
			Dynamic Key
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/imagelib/index.php">
			Image Library
		</a>
	</div>
	<div class="menuitem">
		<a href="<?php echo $clientRoot; ?>/imagelib/imgsearch.php">
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
			<a href="<?php echo $clientRoot; ?>/profile/viewprofile.php">My Profile</a>
		</div>
		<div class="menuitem">
			<a href="<?php echo $clientRoot; ?>/profile/index.php?submit=logout">Logout</a>
		</div>
	<?php
	}
	else{
	?>
		<div class="menuitem">
			<a href="<?php echo $clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">
				Log In
			</a>
		</div>
		<div class="menuitem">
			<a href="<?php echo $clientRoot; ?>/profile/newprofile.php">
				New Account
			</a>
		</div>
	<?php
	}
	?>
	<div class='menuitem'>
		<a href='<?php echo $clientRoot; ?>/sitemap.php'>Sitemap</a>
	</div>
</div>


