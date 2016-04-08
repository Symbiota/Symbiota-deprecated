<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Agaricaceae</title>
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
            <h1>Guide to Agaricaceae</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AGAR/AGARBITO_250_whole.jpg" width="250" height="278" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<h1>Guide to Agaricaceae</h1>

				<p>
				The gilled mushrooms in this family lack a universal veil, so no volva or warts are present. Most have a partial veil that often leaves a ring on the stem. Cap may be smooth, have hairs, or have scales. But cap does not have loose warts or patches. All gilled mushrooms in this family grow on the ground or on organic debris, such as mulch.
				</p>
				<p>
				This family has been expanded, based on DNA evidence, to include the parasol mushrooms (Lepiotaceae), shaggy mane (<i>Coprinus comatus</i>), and certain gastroid fungi, such as the puffballs (Lycoperdaceae).
				</p>




				<table class="key" cellpadding="0" cellspacing="0" border="0">
				<caption>Guide to Genera</caption>
				<tbody>
				<thead>
				<tr ><th>&nbsp;</th><th>Genus</th><th >Spore print</th></tr>
				</thead>


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
				<b>Spore print greenish or white.</b> Partial veil present, leaving fringed ring. Cap large with tan to pale brown scales. Stem smooth, not with banding (though may crack in age). Stem staining reddish to orangish where scratched. Prefers growing in more disturbed and nutrient rich soils, thus common in urban areas. The secotioid (closed) species, <i>C. agaricoides</i>, has a cap that remains closed; the spores are greenish to yellowish brown under the microscope.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/print_green_white.jpg" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LANG/LANGGIGA_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Langermannia</i></h3>
				<p>
				<b>Giant puffball.</b> Spore mass olive brown.  Found on the ground in grassy areas and in woods.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/gleba_olive.jpg" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/CALV/CALV_125_cracks.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Calvatia</i></h3>
				<p>
				<b>Medium to large puffballs.</b> Spore mass olive brown or purplish. Found on the ground in grassy areas and in woods.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/gleba_olive_purplish.jpg" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Mycenastrum</i></h3>
				<p>
				<b>Medium to large puffballs.</b> Spore mass olive brown to dark brown to purplish brown. Found on the ground, prefers pastures.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/gleba_olive_purplish.jpg" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LYCO2/LYCO2PERL_125_group.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Lycoperdon</i></h3>
				<p>
				<b>Medium to small puffballs.</b> Spore mass olive brown or purplish. Found on the ground or on wood in woodlands.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/gleba_olive_purplish.jpg" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Bovista</i>, <i class="genus">Bovistella</i>, and <i class="genus">Disciseda</i></h3>
				<p>
				<b>Tumbling puffballs.</b> Medium to small round puffballs.  Spore mass olive brown. Found on the ground in open areas and in woods.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/gleba_olive.jpg" width="125" height="100" alt=""></div></td>
				</tr>

				<tr class="keychoice">
				<td ><img src="" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus">Vascellum</i></h3>
				<p>
				<b>Small puffballs.</b> Spore mass olive brown. Found on the ground in grassy areas.
				</p>
				</td>
				<td><div><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/spore/gleba_olive.jpg" width="125" height="100" alt=""></div></td>
				</tr>


				</tbody>
				</table>

				<!--  ?? -->
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>