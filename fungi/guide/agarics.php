<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Agarics</title>
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
            <h1>Guide to Gilled Mushrooms, the Agarics</h1>

            <div style="margin:20px;">
			
				<div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AGAR/AGARBITO_250_whole.jpg" width="250" height="278" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>

            	<p>Page under construction. The key below will be replaced and currently serves simply to get to the demo guides for mushrooms with free gills.</p>



				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Families or Subgroups</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Gills not attached to stem or very narrowly attached.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AGAR/AGARCAMP2_125_freegills.jpg" width="125" height="100" alt=""></td>
				<td ><a href="agarics_free.html">Agarics with free gills</a></td>
				</tr><tr >
				<td id="k1">1a. Gills attached to stem but not decurrent.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/TRIC5/TRIC5CALI_125_notchgills.jpg" width="125" height="100" alt=""></td>
				<td >Agarics with attached gills</td>
				</tr><tr >
				<td id="k1">1a. Gills attached broadly to stem, decurrent.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/CAMA1/CAMA1PRAT_125_decurrentgills.jpg" width="125" height="100" alt=""></td>
				<td >Agarics with decurrent gills</td>
				</tr>

				</tbody>
				</table>


				<p class="small">All images by Patrick Leacock unless noted.</p>

				<p><a href="http://americanmushrooms.com/monenaag.htm">Key to the
				Genera of Gilled Mushrooms
				from the book
				Mushrooms of Northeastern North America</a>
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>