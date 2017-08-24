<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<!-- <div style="height:110px;background-image:url(<?php echo $clientRoot; ?>/images/layout/defaultheader.jpg);background-repeat:no-repeat;position:relative;"> -->
			<div style="clear:both;">
				<div style="clear:both;">
					<img style="" src="<?php echo $clientRoot; ?>/images/layout/defaultheader.jpg" />
				</div>
			</div>
		</td>
	</tr>
    <tr>
	<?php 
	if($displayLeftMenu){
		?> 
		<td class='middleleft' background="<?php echo $clientRoot;?>/images/layout/defaultleftstrip.gif" style="background-repeat:repeat-y;"> 
			<div style="">
				<?php include($serverRoot."/leftmenu.php"); ?>
			</div>
		</td>
		<?php 
	}
	else{
		?>
        	<td class="middleleftnomenu" background="<?php echo $clientRoot;?>/images/layout/defaultleftstrip.gif">
        		<div style='width:20px;'></div>
        	</td>
        <?php 
	}
	?>
	<td class='middlecenter'>

		