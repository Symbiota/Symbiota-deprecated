<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Gyroporus</title>
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
            <h1>Guide to Gyroporus</h1>

            <div style="margin:20px;">
            	<div class="floatimg"></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>These small to medium-sized boletes have a stem that is typically hollow when mature, at least toward the base. Spore print is pale yellow.<!-- why separate from Boletaceae? --></p>



				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Species</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Pores and flesh staining blue or purple. Cap and stem pale cream to yellowish.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k2">&nbsp; &nbsp; &nbsp; &nbsp; 2</a></td>
				</tr><tr >
				<td >1b. Pores not staining blue or purple. Cap and stem darker.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k3">&nbsp; &nbsp; &nbsp; &nbsp; 3</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k2">2a. Exposed flesh greenish yellow, then blue.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=4251"><i class="genus">Gyroporus</i> <i class="epithet">cyanescens</i> var. <i class="epithet">cyanescens</i></a></td>
				</tr><tr >
				<td >2a. Exposed flesh directly deep purple to deep blue.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=4252"><i class="genus">Gyroporus</i> <i class="epithet">cyanescens</i> var. <i class="epithet">violaceotinctus</i></a></td>
				</tr>

				<tr class="keychoice">
				<td id="k3">3a. Cap with purplish tones.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=4254"><i class="genus">Gyroporus</i> <i class="epithet">purpurinus</i></a></td>
				</tr><tr >
				<td >3a. Cap pale brown to orange-brown.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=4249"><i class="genus">Gyroporus</i> <i class="epithet">castaneus</i></a></td>
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