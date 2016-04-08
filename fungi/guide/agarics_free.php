<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Agarics Gills Free</title>
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
            <h1>Guide to Agarics with Gills Free</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AGAR/AGARCAMP2_250_freegills.jpg" width="250" height="352" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>Three different guides to choose from.</p>
				<ul><li>Overview of families and genera is found below.</li>
				<li>Single page polytomous key to genus groups.</li>
				<li><a href="agarics_free1.html">Multiple page polytomous key</a> to genus groups (larger images).</li>
				</ul>

				<table class="key" cellpadding="0" cellspacing="0" border="0">
				<caption>Taxonomic Guide to Families, and Genera with Free Gills</caption>
				<tbody>

				<tr class="keydivision">
				<td colspan="2">
				<h2><a href="amanitaceae.html">Family Amanitaceae</a></h2>
				<p>
				Members of this family have a universal veil. The base of the stem may have a volva or ridges or powdery remnants of the veil. The cap may have warts or one or several patches or powdery remnants of the veil or be smooth. But caps do not have scales. Some have a partial veil that often leaves a skirt-like ring on the stem.
				This family, based on DNA evidence, is closely related to the Pluteaceae and also the Pleurotaceae (oyster mushrooms).
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_amanitaceae.gif" width="125" height="100" alt=""></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AMAN/AMAN_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus"><a href="amanita.html">Amanita</a></i></h3>
				<p>
				<b>Spore print white.</b> 
				Universal veil present as a layer of tissue which is membrane-like, usually leaving a volva (cup at base), or which breaks up into warts (loose material on cap and lower stem), or which is a powdery layer.
				Partial veil present or absent.
				Grows on ground, most species mycorrhizal with trees.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LIMA/LIMA_125_cap.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus"><a href="limacella.html">Limacella</a></i></h3>
				<p>
				<b>Spore print white.</b> 
				Universal veil present as a layer of slime covering cap and most of stem.
				Partial veil present sometimes leaving a ring.
				Grows on ground, unclear whether decomposers or mycorrhizal.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keydivision">
				<td colspan="2">
				<h2><a href="pluteaceae.html">Family Pluteaceae</a></h2>
				<p>
				One genus has a universal veil; the other does not. Partial veil is absent (except for <i class="genus">Chameota</i> which is not found here).
				This family, based on DNA evidence, is closely related to the Amanitaceae and also the Pleurotaceae (oyster mushrooms).
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_amanitaceae.gif" width="125" height="100" alt=""></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/VOLV/VOLV_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus"><a href="volvariella.html">Volvariella</a></i></h3>
				<p>
				<b>Spore print pinkish to salmon.</b>
				Universal veil present as a layer of tissue, usually membrane-like, usually leaving a volva (cup at base).
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
				<b>Spore print pinkish to salmon.</b> 
				Universal veil absent. Partial veil absent.
				Grows on wood, occasionally on ground from buried wood.
				</p>
				</td>
				<td><div style="background: #e48c61;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keydivision">
				<td colspan="2">
				<h2><a href="agaricaceae.html">Family Agaricaceae</a></h2>
				<p>
				The gilled mushrooms in this family lack a universal veil, so no volva or warts are present. Most have a partial veil that often leaves a ring on the stem. Cap may be smooth, have hairs, or have scales. But cap does not have loose warts or patches. All gilled mushrooms in this family grow on the ground or on organic debris, such as mulch.
				This family has been expanded, based on DNA evidence, to include the parasol mushrooms (Lepiotaceae), shaggy mane (<i>Coprinus comatus</i>), and certain gastroid fungi, such as the puffballs (Lycoperdaceae).
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_agaricaceae.gif" width="125" height="100" alt=""></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AGAR/AGARBITO_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Agaricus</i></h3>
				<p>
				<b>Spore print dark brown to chocolate brown.</b> Partial veil present, often leaving a ring on stem.
				</p>
				</td>
				<td><div style="background: #604030;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/temp/melanophyllum_125.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Melanophyllum</i></h3>
				<p>
				<b>Spore print reddish or greenish when fresh, drying darker brown.</b> Partial veil present, often leaving fragments on cap edge.
				</p>
				</td>
				<td><div style="background: #a0413e;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/COPR3/COPR3COMA_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Coprinus</i> (in the strict sense)</h3>
				<p>
				<b>Spore print black.</b> Partial veil present, often leaving small loose ring on lower stem. Large elongated cap with extremely crowded gills which turn salmon then black and inky.
				</p>
				</td>
				<td><div style="background: #000000;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LEPI2/LEPI2_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Lepiota</i></h3>
				<p>
				<b>Spore print white.</b> Partial veil present, either leaving delicate ring on stem or cottony patches.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LEUC2/LEUC2CEPA_125_cap.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Leucocoprinus</i></h3>
				<p>
				<b>Spore print white.</b> Partial veil present, often leaving ring. Cap more egg-shaped before opening. Cap surface powdery or minutely scaly. Growing in compost or in greenhouses and potted plants.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LEUC1/LEUC1AMER_125_cap.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Leucoagaricus</i></h3>
				<p>
				<b>Spore print white.</b> Partial veil present, leaving ring on stem. Cap smooth or scaly. Some species staining yellowish or reddish to brown.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/temp/MACRPROC_125_kuo.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Macrolepiota</i></h3>
				<p>
				<b>Spore print white.</b> Partial veil present, often leaving ring. Cap large and scaly. Stem covered in plush-like material which forms bands when the stem elongates.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/CHLO2/CHLO2RACH_125_stain.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Chlorophyllum</i></h3>
				<p>
				<b>Spore print greenish or white.</b> Partial veil present, leaving fringed ring. Cap large with tan to pale brown scales. Stem smooth, not with banding (though may crack in age). Stem staining reddish to orangish where scratched. Prefers growing in more disturbed and nutrient rich soils, thus common in urban areas.  The secotioid (closed) species, <i>C. agaricoides</i>, has a cap that remains closed; the spores are greenish to yellowish brown under the microscope.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/print_green_white.jpg" width="125" height="100" alt=""></div></td>
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