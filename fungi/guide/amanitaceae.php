<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Amanitaceae</title>
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
            <h1>Guide to Amanitaceae</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AMAN/AMANBISP.po.jpg" width="250" height="300" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>

				<p>
				Members of this family have a universal veil. The base of the stem may have a volva or ridges or powdery remnants of the veil. The cap may have warts or one or several patches or powdery remnants of the veil or be smooth. But caps do not have scales. Some have a partial veil that often leaves a skirt-like ring on the stem.
				This family, based on DNA evidence, is closely related to the Pluteaceae and also the Pleurotaceae (oyster mushrooms).
				</p>

				

				<table class="key" cellpadding="0" cellspacing="0" border="0">
				<caption>Guide to Genera</caption>
				<tbody>
				<thead>
				<tr ><th>&nbsp;</th><th>Genus</th><th >Spore print</th></tr>
				</thead>

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/AMAN/AMAN_125_whole.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3><i class="genus"><a href="amanita.html">Amanita</a></i></h3>
				<p>
				Spore print white. 
				<b>Universal veil present as a layer of tissue</b> which is membrane-like, usually leaving a volva (cup at base), or which breaks up into warts (loose material on cap and lower stem), or which is a powdery layer.
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
				Spore print white. 
				<b>Universal veil present as a layer of slime</b> covering cap and most of stem.
				Partial veil present sometimes leaving a ring.
				Grows on ground, unclear whether decomposers or mycorrhizal.
				</p>
				</td>
				<td><div style="background: #f9f9f9;"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/spore125.gif" width="125" height="100" alt=""></div></td>
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