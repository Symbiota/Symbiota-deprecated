<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Agarics Gills Free Key 3</title>
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
            <h1></h1>

            <div style="margin:20px;">
            	 <p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<table class="key blocks" cellpadding="0" cellspacing="10" border="0">
				<caption>Key to Mushroom Genera with Free Gills.<br> Step 3: Universal Veil Presence</caption>
				<tbody>


				<tr>
				<td><div style=""><a href="amanita.html" title="Go to Amanita."><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AMAN/AMAN_300_triple.jpg" width="300" height="222" alt=""></a></div>
				<p>
				<b>Universal veil present</b> as a layer of tissue which is membrane-like, usually leaving a volva (cup at base), or which breaks up into warts (loose material on cap and lower stem), or which is a powdery layer, look carefully. Partial veil present or absent. Grows on ground; most species mycorrhizal with trees.
				<br> Go to <a href="amanita.html"><i class="genus">Amanita</i></a>.
				</p>
				</td>
				<td><div style=""><a href="agarics_free4.html" title="Go to Key Step 4."><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/lepiotaceae_300_triple.jpg" width="300" height="222" alt=""></a></div>
				<p>
				<b>Universal veil absent</b>. Stem base may be enlarged but there is no cup or other material at base. Cap smooth or with scales, or powdery. Partial veil present, usually forming a ring. Growing on ground, wood chips, or mulch, in grassy areas or woodlands; decomposers. 
				<br> Go to <a href="agarics_free4.html">Parasol Mushrooms (<i>Lepiota</i> group)</a>
				</p>
				</td>
				</tr>

				<tr>
				<td><div style=""><a href="limacella.html" title="Go to Limacella."><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LIMA/LIMA_300_whole.jpg" width="300" height="222" alt=""></a></div>
				<p>
				<b>Universal veil present as a layer of slime</b> covering cap and most of stem. Partial veil present sometimes leaving a ring. Grows on ground; it is not clear whether these are decomposers or mycorrhizal.
				<br> Go to <a href="limacella.html"><i class="genus">Limacella</i></a>.
				</p>
				</td>
				<td class="empty">&nbsp;
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