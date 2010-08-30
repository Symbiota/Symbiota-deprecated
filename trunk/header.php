<?php
if(!isset($hideHeader) || !$hideHeader){
	$displayLogin = 1;
	if(strpos($_SERVER['PHP_SELF'],"/taxa/")){
		$bgUrl = $clientRoot."/images/layout/seinet_sl.jpg";
		$displayLogin = 0;
	}
	elseif($displayLeftMenu){
		$bgUrl = $clientRoot."/images/layout/header.gif";
	}
	else{
		$bgUrl = $clientRoot."/images/layout/header_nostrip.gif";
	}
	?>
<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<!-- <div style="height:110px;background-image:url(<?php echo $bgUrl; ?>);background-repeat:no-repeat;position:relative;"> -->
			<div style="clear:both;">
				<div style="clear:both;"><img style="" src="<?php echo $bgUrl; ?>" /></div>
			    <!-- this is the 'login' cell -->
			    <?php if($displayLogin){ ?>
				    <div style="clear:both;width:200px;position:absolute;top:62px;left:20px;font-weight:bold;">
					<?php
					if($userDisplayName){
					    echo "Welcome ".$userDisplayName."!";
					}
					else{
					    echo "<a href='".$clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."' class='headerlogin'>Log In</a>";
					}
					?>
					</div>
					
					<?php
					if($userDisplayName){
					    echo "<div style='width:150px;position:absolute;top:91px;left:20px;'><a href='".$clientRoot."/profile/index.php?submit=logout' class='headermenu'>Logout</a></div>";
					    echo "<div style='width:150px;position:absolute;top:91px;left:180px;'><a href='".$clientRoot."/profile/viewprofile.php' class='headermenu'>My Profile</a></div>";
					}
					else{
					    echo "<div style='width:200px;position:absolute;top:91px;left:20px;'><a href='".$clientRoot."/profile/newprofile.php' class='headermenu'>New SEINet Account</a></div>";
					}
				    echo "<div style='position:absolute;top:91px;left:560px;'><a href='http://symbiota.org/tiki/tiki-index.php?page=HelpPages' class='headermenu' target='_blank'>Help</a></div>";
			    }
				?>
			</div>
		</td>
	</tr>
    <tr>
	<?php 
	if($displayLeftMenu){ 
		?>
		<td valign='top' style='height:100%;width:161px;background-color:#CCCC99;'> 
			<div>
				<img src="<?php echo $clientRoot;?>/images/layout/below_header.gif">
			</div>
			<div style='float:left;'>
				<?php include($serverRoot."/leftmenu.php");?>
			</div>
			<div style="vertical-align:top;height:100%;width:12px;background-color:white;float:right;">
				<img src="<?php echo $clientRoot;?>/images/layout/vert_strip_left.gif" />
			</div>
		</td>
		<?php 
	}
	else{
		?>
        <td class="middleleftnomenu" background="<?php echo $clientRoot;?>/images/layout/brown_hor_strip.gif">
        	<div style='width:20px;'></div>
        </td>
        <?php 
	}
	?>
	<td class="middlecenter">
	<?php 
}
	?>
			