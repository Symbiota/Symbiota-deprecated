<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Suillaceae</title>
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
            <h1>Guide to Suillacae</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p><!-- why separate from Boletaceae? -->

				</p>


				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Genera</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Cap slimy, viscid, tacky, or dry. Pores often decurrent on stem. Partial veil present or absent, if present may leave a ring, band, or zone on stem.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/SUIL/SUILBREV.key_situ.jpg" width="125" height="100" alt=""></td>
				<td ><a href="suillus.html"><i class="genus">Suillus</i></a></td>
				</tr><tr >
				<td ></td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ></td>
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