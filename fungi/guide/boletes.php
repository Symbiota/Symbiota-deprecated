<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Boletes</title>
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
            <h1>Guide to Pored Mushrooms, the Boletes</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/LECC/LECC.po.jpg" width="250" height="311" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>.</p>



				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Families or Subgroups</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Cap slimy, viscid, or sticky (may be smooth and shiny when dry); partial veil or ring present or absent.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k2">&nbsp; &nbsp; &nbsp; &nbsp; 2</a></td>
				</tr><tr >
				<td >1b. Cap dry; veil or ring absent.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k3">&nbsp; &nbsp; &nbsp; &nbsp; 3</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k2">2a. Cap large, veil large, thick, connecting cap edge to middle or lower stem.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/PARA1/PARA1SPHA_whole.jpg" width="125" height="100" alt=""></td>
				<td ><a href="paxillaceae.html">Family Paxillaceae</a>, <a href="paragyrodon.html"><i class="genus">Paragyrodon</i></a></td>
				</tr><tr >
				<td>2b. Veil, if present, leaves gelatinous ring, thin ring, or zone on middle or upper stem.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/SUIL/SUILBREV.key_situ.jpg" width="125" height="100" alt=""></td>
				<td ><a href="suillaceae.html">Family Suillaceae</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k3">3a. Cap brown, stem off-center or lateral and short to stubby, pores radially elongated and/or irregular with cross-veins. Growing near ash tree (<i>Fraxinus</i>).</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="paxillaceae.html">Family Paxillaceae</a>, <a href="gyrodon.html"><i class="genus">Gyrodon</i></a></td>
				</tr><tr >
				<td>3b. Not with the above combination of characters.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k4">&nbsp; &nbsp; &nbsp; &nbsp; 4</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k4">4a. Stem hollow or chambered, or hollow at maturity, flesh brittle or spongy. Spore print pale yellow to yellow. Cap often wrinkled. Pore surface pale when young, later pale yellow. Cap coloration one of the following: cinnamon brown or purplish and flesh staining brown; pale cream or yellow and flesh staining green-yellow then blue, or staining deep purple.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k3"><a href="gyroporaceae.html">Family Gyroporaceae</a>, <a href="gyroporus.html"><i class="genus">Gyroporus</i></a></a></td>
				</tr><tr >
				<td>4b. Not with the above combination of characters.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="boletaceae.html">Family Boletaceae</a></td>
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