<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Chlorophyllum</title>
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
            <h1>Guide to Chlorophyllum</h1>

            <div style="margin:20px;">
				<div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/CHLO2/CHLO2RACH_250.jpg" width="250" height="255" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>
			
            	<p>Three species in Chicago Region: <i>Chlorophyllum molybdites</i> with green spores, <i>Chlorophyllum rhacodes</i> (formerly <i>Lepiota</i> and <i>Macrolepiota</i>) with white spores, and <i>Chlorophyllum agaricoides</i> (formerly <i>Endoptychum</i>) with brown spores and a gastroid fruitbody that does not open.</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>