<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Boletaceae</title>
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
            <h1>Guide to Boletaceae</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/BOLE/BOLEBICO.po.jpg" width="250" height="203" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>.</p>



				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Genera</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Cap with gills.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="phylloporus.html"><i class="genus">Phylloporus</i></a></td>
				</tr><tr >
				<td >1b. Cap with pores.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k2">&nbsp; &nbsp; &nbsp; &nbsp; 2</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k2">2a. Cap and stem with dark brown, gray or blackish scales. Pores whitish then gray to black when mature. Partial veil present. Flesh stains reddish. Spore print dark brown to black.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="strobilomyces.html"><i class="genus">Strobilomyces</i></a></td>
				</tr><tr >
				<td>2b. Not with above combination of characters.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k3">&nbsp; &nbsp; &nbsp; &nbsp; 3</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k3">3a. Stem rough with tufts of hairs or scales (scabers) which often darken with age. Stem may be weakly reticulate (netted pattern). Spore print brown.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="leccinum.html"><i class="genus">Leccinum</i></a></td>
				</tr><tr >
				<td>3b. Not with above combination of characters.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k4">&nbsp; &nbsp; &nbsp; &nbsp; 4</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k4">4a. Spore print pinkish brown to reddish brown or darker. Pores white to pinkish, gray, reddish brown to brown. Pores may stain some color, but if pores staining blue then pores are not yellow. Stem smooth or reticulate (netted pattern).</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/TYLO/TYLO.key_situ.jpg" width="125" height="100" alt=""></td>
				<td ><!-- image --></td>
				<td ><a href="tylopilus.html"><i class="genus">Tylopilus</i></a></td>
				</tr><tr >
				<td>4b. Spore print bright yellow brown.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="xanthoconium.html"><i class="genus">Xanthoconium</i></a></td>
				</tr><tr >
				<td>4c. Spore print olive, olive brown, to brown or cinnamon.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k5">&nbsp; &nbsp; &nbsp; &nbsp; 5</a></td>
				</tr>


				<tr class="keychoice">
				<td id="k5">5a. Spores with longitudinal wrinkles, ridges or grooves.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="boletellus.html"><i class="genus">Boletellus</i></a></td>
				</tr><tr >
				<td>5b. Spores smooth.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k6">&nbsp; &nbsp; &nbsp; &nbsp; 6</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k6">6a. Small bolete with peppery taste (chew small piece of cap then spit out).</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="chalciporus.html"><i class="genus">Chalciporus</i></a></td>
				</tr><tr >
				<td>6b. Taste mild, bitter, or sour, not peppery.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="boletus.html"><i class="genus">Boletus</i></a></td>
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