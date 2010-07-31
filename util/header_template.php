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
			<?php include($serverRoot."/util/leftmenu.php"); ?>
		</td>
		<?php 
	}
	else{
		?>
      	  	<td class='middleleftnomenu'></td>
        <?php 
	}
	?>
	<td class='middlecenter'>

		