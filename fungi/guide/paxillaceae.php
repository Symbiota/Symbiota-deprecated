<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Paxillaceae</title>
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
            <h1>Guide to Paxillaceae</h1>

            <div style="margin:20px;">
            	<div class="floatimg"></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>This family is related to the boletes and contains members with various spore producing surfaces including pores, gills, and ridges</p>



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
				<td ><a href="paxillus.html"><i class="genus">Paxillus</i></a></td>
				</tr><tr >
				<td >1b. Cap with pores.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k2">&nbsp; &nbsp; &nbsp; &nbsp; 2</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k2">2a. Cap large, veil large, thick, connecting cap edge to lower stem.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/PARA1/PARA1SPHA_whole.jpg" width="125" height="100" alt=""></td>
				<td ><a href="paragyrodon.html"><i class="genus">Paragyrodon</i></a></td>
				</tr><tr >
				<td>2b. Cap brown, stem off-center or lateral and short to stubby, pores radially elongated and/or irregular with cross-veins. Growing near ash tree (<i>Fraxinus</i>).</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="gyrodon.html"><i class="genus">Gyrodon</i></a></td>
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