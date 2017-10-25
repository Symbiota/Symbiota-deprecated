<?php 
include_once($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php');
?>
<link href="https://fonts.googleapis.com/css?family=EB+Garamond|Playfair+Display+SC" rel="stylesheet" />
<style>
	.header1 { font-family: 'EB Garamond', serif; font-size: 32px; font-style: italic; margin: 15px 10px 0px 70px; }
	.header2 { font-family: 'Playfair Display SC', serif; font-size: 24px; margin: 0px 10px 10px 30px; }
</style>
<script type="text/javascript" src="<?php echo $CLIENT_ROOT; ?>/js/symb/base.js?ver=171023"></script> 
<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<div style="background-color:black;height:110px;">
				<div style="float:right;">
					<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/header_graphic.jpg" />
				</div>
				<div style="float:left;">
					<div class="header1">Replace this</div>
					<div class="header2">text with Portal Title</div>
				</div>
			</div>
			<div id="top_navbar">
				<div id="right_navbarlinks">
					<?php
					if($USER_DISPLAY_NAME){
						?>
						<span style="">
							<?php echo (isset($LANG['WELCOME'])?$LANG['WELCOME']:'Welcome').' '.$USER_DISPLAY_NAME; ?>!
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $CLIENT_ROOT; ?>/profile/viewprofile.php"><?php echo (isset($LANG['MY_PROFILE'])?$LANG['MY_PROFILE']:'My Profile')?></a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $CLIENT_ROOT; ?>/profile/index.php?submit=logout"><?php echo (isset($LANG['LOGOUT'])?$LANG['LOGOUT']:'Logout')?></a>
						</span>
						<?php
						$LANG['LOGIN'] = 'Login';
						$LANG['NEW_ACCOUNT'] = 'New Account';
					}
					else{
						?>
						<span style="">
							<a href="<?php echo $CLIENT_ROOT."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>"><?php echo (isset($LANG['LOGIN'])?$LANG['LOGIN']:'Login')?></a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $CLIENT_ROOT; ?>/profile/newprofile.php"><?php echo (isset($LANG['NEW_ACCOUNT'])?$LANG['NEW_ACCOUNT']:'New Account')?></a>
						</span>
						<?php
					}
					?>
					<span style="margin-left:5px;margin-right:5px;">
						<select onchange="setLanguage(this)">
							<option value="en">English</option>
							<option value="es" <?php echo ($LANG_TAG=='es'?'SELECTED':''); ?>>Espa&ntilde;ol</option>
						</select>
						<?php 
						if($IS_ADMIN){
							echo '<a href="'.$CLIENT_ROOT.'/content/lang/admin/langmanager.php?refurl='.$_SERVER['PHP_SELF'].'"><img src="'.$CLIENT_ROOT.'/images/edit.png" style="width:12px" /></a>';
						}
						?>
					</span>
				</div>
				<ul id="hor_dropdown">
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/index.php" ><?php echo (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a>
					</li>
					<li>
						<a href="#" ><?php echo (isset($LANG['SEARCH'])?$LANG['SEARCH']:'Search'); ?></a>
						<ul>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/index.php" ><?php echo (isset($LANG['COLLECTIONS'])?$LANG['COLLECTIONS']:'Collections'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/map/mapinterface.php" target="_blank"><?php echo (isset($LANG['MAP'])?$LANG['MAP']:'Map'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/checklists/dynamicmap.php?interface=checklist" ><?php echo (isset($LANG['DYN_LISTS'])?$LANG['DYN_LISTS']:'Dynamic Species List'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/taxa/admin/taxonomydynamicdisplay.php" ><?php echo (isset($LANG['TAXONOMIC_EXPLORER'])?$LANG['TAXONOMIC_EXPLORER']:'Taxonomic Explorer'); ?></a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" ><?php echo (isset($LANG['IMAGES'])?$LANG['IMAGES']:'Images'); ?></a>
						<ul>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/index.php" ><?php echo (isset($LANG['IMAGE_BROWSER'])?$LANG['IMAGE_BROWSER']:'Image Browser'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/search.php" ><?php echo (isset($LANG['IMAGE_SEARCH'])?$LANG['IMAGE_SEARCH']:'Search Images'); ?></a>
							</li>
						</ul>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php" ><?php echo (isset($LANG['INVENTORIES'])?$LANG['INVENTORIES']:'Species Checklists'); ?></a>
						<ul>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=516"><?php echo (isset($LANG['CENTRAL_AMER'])?$LANG['CENTRAL_AMER']:'Central America'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=517"><?php echo (isset($LANG['ECUADOR'])?$LANG['ECUADOR']:'Ecuador'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=505"><?php echo (isset($LANG['SUBPOLAR'])?$LANG['SUBPOLAR']:'Subpolar Regions'); ?></a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" ><?php echo (isset($LANG['SYMBIOTA'])?$LANG['SYMBIOTA']:'Symbiota'); ?></a>
						<ul>
							<li>
									<a href="http://symbiota.org/docs/" target="_blank" ><?php echo (isset($LANG['ABOUT_SYMBIOTA'])?$LANG['ABOUT_SYMBIOTA']:'About Symbiota'); ?></a>
							</li>
							<li>
									<a href="http://symbiota.org/docs/symbiota-introduction/symbiota-help-pages/" target="_blank" ><?php echo (isset($LANG['HELP'])?$LANG['HELP']:'Help'); ?></a>
							</li>
							<li>
									<a href="http://symbiota.org/docs/google-group/" target="_blank" ><?php echo (isset($LANG['GOOGLE_GROUP'])?$LANG['GOOGLE_GROUP']:'Google Group'); ?></a>
							</li>
							<li>
								<a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php'><?php echo (isset($LANG['SITEMAP'])?$LANG['SITEMAP']:'Sitemap'); ?></a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" ><?php echo (isset($LANG['CONTACTS'])?$LANG['CONTACTS']:'Contacts'); ?></a>
						<ul>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php" ><?php echo (isset($LANG['PARTNERS'])?$LANG['PARTNERS']:'Partners'); ?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/misc/contacts.php" ><?php echo (isset($LANG['CONTACTS'])?$LANG['CONTACTS']:'Contacts'); ?></a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</td>
	</tr>
	<tr>
		<td class='middlecenter'  colspan="3">
