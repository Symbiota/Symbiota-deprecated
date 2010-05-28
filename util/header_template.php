<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<!-- <div style="height:110px;background-image:url(--PUT HEADER URL--);background-repeat:no-repeat;position:relative;"> -->
			<div style="clear:both;">
				<div style="clear:both;">
					<img style="" src="--PUT HEADER URL--" />
				</div>
			</div>
		</td>
	</tr>
    <tr>
	<?php 
	if($displayLeftMenu){
		?> 
		<td valign="top" style="height:100%;width:161px;background-color:#CCCC99;"> 
			<div>
				<img src="<?php echo $clientRoot;?>/util/images/below_header.gif">
			</div>
			<div style="float:left;">
				<?php include($serverRoot."/util/leftmenu.php"); ?>
			</div>
			<div style='vertical-align:top;height:100%;width:12px;background-color:white;float:right;'>
				<img src="<?php echo $clientRoot;?>/util/images/vert_strip_left.gif">
			</div>
		</td>
		<?php 
	}
	else{
		?>
			<td background="<?php echo $clientRoot; ?>/util/images/brown_hor_strip.gif">
				<div style='width:20px;'></div>
			</td>
        <?php 
	}
	?>
	<td class='middlecenter'>

		