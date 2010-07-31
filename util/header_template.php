<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<!-- <div style="height:110px;background-image:url(--PUT HEADER URL--);background-repeat:no-repeat;position:relative;"> -->
			<div style="clear:both;">
				<div style="clear:both;">
					<img style="" src="<?php echo $clientRoot; ?>/util/images/header.jpg" />
				</div>
			</div>
		</td>
	</tr>
    <tr>
	<?php 
	if($displayLeftMenu){
		?> 
		<td class='middleleft'> 
			<div style="float:left;height:100%;width:20px;">
				<img src="<?php echo $clientRoot;?>/util/images/brown_hor_strip.gif">
			</div>
			<?php include($serverRoot."/util/leftmenu.php"); ?>
		</td>
		<?php 
	}
	else{
		?>
        	<td class="middleleftnomenu" background="<?php echo $clientRoot;?>/util/images/brown_hor_strip.gif">
        		<div style='width:20px;'></div>
        	</td>
        <?php 
	}
	?>
	<td class='middlecenter'>

		