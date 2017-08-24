<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<div style="clear:both;">
				<div style="clear:both;">
					<img style="" src="<?php echo $clientRoot; ?>/images/layout/defaultheader.jpg" />
				</div>
			</div>
			<div id="top_navbar">
				<div id="right_navbarlinks">
					<?php
					if($userDisplayName){
					?>
						<span style="">
							Welcome <?php echo $userDisplayName; ?>!
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/viewprofile.php">My Profile</a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/index.php?submit=logout">Logout</a>
						</span>
					<?php
					}
					else{
					?>
						<span style="">
							<a href="<?php echo $clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">
								Log In
							</a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/newprofile.php">
								New Account
							</a>
						</span>
					<?php
					}
					?>
					<span style="margin-left:5px;margin-right:5px;">
						<a href='<?php echo $clientRoot; ?>/sitemap.php'>Sitemap</a>
					</span>
					
				</div>
				<ul id="hor_dropdown">
					<li>
						<a href="<?php echo $clientRoot; ?>/index.php" >Home</a>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/collections/index.php" >Search Collections</a>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/collections/map/mapinterface.php" target="_blank">Map Search</a>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/imagelib/imgsearch.php" >Image Search</a>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/imagelib/index.php" >Browse Images</a>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/projects/index.php?" >Inventories</a>
						<!-- <ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=1" >Project 1</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=2" >Project 2</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=3" >Project 3</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=4" >Project 4</a>
							</li>
						</ul> -->
					</li>
					<li>
						<a href="#" >Interactive Tools</a>
						<!-- <ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist&tid=1" >Dynamic Checklist 1</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist&tid=2" >Dynamic Checklist 2</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist&tid=3" >Dynamic Checklist 3</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist&tid=4" >Dynamic Checklist 4</a>
							</li>
						</ul> -->
					</li>
				</ul>
			</div>
		</td>
	</tr>
    <tr>
		<td class='middlecenter'  colspan="3">

		