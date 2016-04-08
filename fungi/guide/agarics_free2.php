<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Agarics Gills Free Key 2</title>
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

            <div style="margin:20px;">
            	 <p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<table class="key blocks" cellpadding="0" cellspacing="10" border="0">
				<caption>Key to Mushroom Genera with Free Gills.<br> Step 2: Volva Presence</caption>
				<tbody>


				<tr>
				<td><div style=""><a href="volvariella.html" title="Go to Volvariella."><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/VOLV/VOLV_300_volva.jpg" width="300" height="222" alt=""></a></div>
				<p>
				Universal veil present, forming cup (volva) at stem base, look carefully. Growing on trees, woody debris, soil, or on other mushrooms.
				<br> Go to <a href="volvariella.html"><i class="genus">Volvariella</i></a>.
				</p>
				</td>
				<td><div style=""><a href="pluteus.html" title="Go to Pluteus."><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/PLUT/PLUT_300_novolva.jpg" width="300" height="222" alt=""></a></div>
				<p>
				Universal veil absent. Growing on wood, wood chips, occasionally on ground from buried wood.
				<br> Go to <a href="pluteus.html"><i class="genus">Pluteus</i></a>.
				</p>
				</td>
				</tr>


				</tbody>
				</table>

				<p class="small">All images by Patrick Leacock unless noted.</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>