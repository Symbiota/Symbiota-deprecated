<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Synoptic Key to Amanita</title>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innervplantstext">
            <h1>Synoptic Key to Amanita</h1>

            <div style="margin:20px;">
            	 <p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>

				<p>Select the features that match an unknown <i>Amanita</i> and click on search. [Prototype, not functional]</p>

				<style type="text/css">
				body.guide table.key {
				 width: auto;
					border-left: 1px solid #440; 
					border-bottom: 1px solid #440; 
				}
				</style>

				<!-- start form -->
				<div id="synopticform">
				<form
				 name="advanced"
				 method="post"
				 onsubmit="return submitform();" 
				action="">

				<p class="actions">
				<input id="submit1" name="submit" type="submit" value="Search"
				 title="Perform Search.">
				</p>

				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>1. <strong>Cap Color</strong></caption><tr ><td >
				<p><input id="white" name="white" type="checkbox" value="white">
				<label for="white">White</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/white.gif" width="125" height="100" alt="">
				</td><td >
				<p><input id="yellow" name="yellow" type="checkbox" value="yellow">
				<label for="yellow">Yellow</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/yellow.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="orange" name="orange" type="checkbox" value="orange">
				<label for="orange">Orange</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/orange.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="redbrown" name="redbrown" type="checkbox" value="redbrown">
				<label for="redbrown">Reddish Brown</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/redbrown.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="yellowbrown" name="yellowbrown" type="checkbox" value="yellowbrown">
				<label for="yellowbrown">Yellow Brown</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/yellowbrown.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="brown" name="brown" type="checkbox" value="brown">
				<label for="brown">Brown</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/brown.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="graybrown" name="graybrown" type="checkbox" value="graybrown">
				<label for="graybrown">Grayish Brown</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/graybrown.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="gray" name="gray" type="checkbox" value="gray">
				<label for="gray">Gray</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/gray.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>


				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>2. <strong>Cap Margin</strong></caption><tr ><td >
				<p><input id="margin_smooth" name="margin_smooth" type="checkbox" value="margin_smooth">
				<label for="margin_smooth">Smooth</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/margin_smooth.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="margin_striate" name="margin_striate" type="checkbox" value="margin_striate">
				<label for="margin_striate">Striate, Grooved</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/margin_striate.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="margin_appendiculate" name="margin_appendiculate" type="checkbox" value="margin_appendiculate">
				<label for="margin_appendiculate">Appendiculate</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/margin_appendiculate.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>

				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>3. <strong>Universal Veil Remnants on Cap</strong></caption><tr ><td >
				<p><input id="warts_none" name="warts_none" type="checkbox" value="warts_none">
				<label for="warts_none">None</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/warts_none.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="warts_patch" name="warts_patch" type="checkbox" value="warts_patch">
				<label for="warts_patch">Patch(es)</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/warts_patch.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="warts_erect" name="warts_erect" type="checkbox" value="warts_erect">
				<label for="warts_erect">Erect Warts</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/warts_erect.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="warts_flat" name="warts_flat" type="checkbox" value="warts_flat">
				<label for="warts_flat">Flat Warts</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/warts_flat.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="warts_felty" name="warts_felty" type="checkbox" value="warts_felty">
				<label for="warts_felty">Felty Patches</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/warts_felty.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="warts_powdery" name="warts_powdery" type="checkbox" value="warts_powdery">
				<label for="warts_powdery">Powdery</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/cap/warts_powdery.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>


				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>4. <strong>Universal Veil Remnants at Stem Base</strong></caption><tr ><td >
				<p><input id="base_cup" name="base_cup" type="checkbox" value="base_cup">
				<label for="base_cup">Cup</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_cup.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="base_deepcup" name="base_deepcup" type="checkbox" value="base_deepcup">
				<label for="base_deepcup">Deep Cup</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_deepcup.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="base_collar" name="base_collar" type="checkbox" value="base_collar">
				<label for="base_collar">Collar</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_collar.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="base_rim" name="base_rim" type="checkbox" value="base_rim">
				<label for="base_rim">Rim</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_rim.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="base_ridge" name="base_ridge" type="checkbox" value="base_ridge">
				<label for="base_ridge">Ridge</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_ridge.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="base_indistinct" name="base_indistinct" type="checkbox" value="base_indistinct">
				<label for="base_indistinct">Indistinct</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_indistinct.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="base_powdery" name="base_powdery" type="checkbox" value="base_powdery">
				<label for="base_powdery">Powdery</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/base_powdery.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>


				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>5. <strong>Universal Veil, Predominant Color of cup, or remnants on cap: patches, warts, or powder</strong></caption><tr ><td >
				<p><input id="white" name="white" type="checkbox" value="white">
				<label for="white">White</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/white.gif" width="125" height="100" alt="">
				</td><td >
				<p><input id="yellow" name="yellow" type="checkbox" value="yellow">
				<label for="yellow">Yellow</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/yellow.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="orange" name="orange" type="checkbox" value="orange">
				<label for="orange">Orange</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/orange.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="pinkishtan" name="pinkishtan" type="checkbox" value="pinkishtan">
				<label for="pinkishtan">Pinkish Tan</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/pinkishtan.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="graybrown" name="graybrown" type="checkbox" value="graybrown">
				<label for="graybrown">Grayish Brown</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/graybrown.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="gray" name="gray" type="checkbox" value="gray">
				<label for="gray">Gray</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/color/gray.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>


				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>6. <strong>Partial Veil or Ring, Presence and Appearance</strong></caption><tr><td >
				<p><input id="pveil_absent" name="pveil_absent" type="checkbox" value="pveil_absent">
				<label for="pveil_absent">Absent, None</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/pveil_absent.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="pveil_ring" name="pveil_ring" type="checkbox" value="pveil_ring">
				<label for="pveil_ring">Ring or Collar</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/pveil_ring.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="pveil_skirt" name="pveil_skirt" type="checkbox" value="pveil_skirt">
				<label for="pveil_skirt">Skirt or Hanging</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/pveil_skirt.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="pveil_cotton" name="pveil_cotton" type="checkbox" value="pveil_cotton">
				<label for="pveil_cotton">Cottony or Hairy</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/pveil_cotton.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>


				<table class="key" border="0" cellpadding="3" cellspacing="5">
				<caption>7. <strong>Bruising or Staining Reactions</strong></caption><tr><td >
				<p><input id="stain_none" name="stain_none" type="checkbox" value="stain_none">
				<label for="stain_none">None</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/none.gif" width="125" height="100" alt="">
				</td><td >
				<p><input id="koh_yellow" name="koh_yellow" type="checkbox" value="koh_yellow">
				<label for="koh_yellow">Yellow with KOH</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/temp/koh_yellow.jpg" width="125" height="100" alt="">
				</td><td >
				<p><input id="stain_red" name="stain_red" type="checkbox" value="stain_red">
				<label for="stain_red">Staining Reddish</label></p>
				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/stem/stain_red.jpg" width="125" height="100" alt="">
				</td>
				</tr></table>


				</form>
				</div><!-- End form -->
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>