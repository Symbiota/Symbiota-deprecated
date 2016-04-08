<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Pluteaceae</title>
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
            <h1>Guide to Pluteaceae</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/PLUT/PLUT.po.jpg" width="250" height="336" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>One genus has a universal veil; the other does not. Partial veil is absent (except for <i class="genus">Chameota</i> which is not found here).
				This family, based on DNA evidence, is closely related to the Amanitaceae and also the Pleurotaceae (oyster mushrooms).</p>



				<table class="key" cellpadding="0" cellspacing="0" border="0">
				<caption>Guide to Genera</caption>
				<tbody>
				<thead>
				<tr ><th>&nbsp;</th><th>Genus</th><th >Spore print</th></tr>
				</thead>


				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/VOLV/VOLV_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus"><a href="volvariella.html">Volvariella</a></i></h3>
				<p>
				Spore print pinkish to salmon.
				<b>Universal veil present</b> as a layer of tissue, usually membrane-like, usually leaving a volva (cup at base).
				Partial veil absent.  Grows on trees, woody debris, soil, or on other mushrooms.
				</p>
				</td>
				<td><div style="background: #e48c61;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/PLUT/PLUT_125_gills.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus"><a href="pluteus.html">Pluteus</a></i></h3>
				<p>
				Spore print pinkish to salmon. 
				<b>Universal veil absent</b>. Partial veil absent.
				Grows on wood, occasionally on ground from buried wood.
				</p>
				</td>
				<td><div style="background: #e48c61;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				</tbody>
				</table>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>