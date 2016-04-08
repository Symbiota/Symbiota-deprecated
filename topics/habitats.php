<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Habitats</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
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
        <div  id="innertext">
            <h1>Habitats of the Chicago Region</h1>

            <div style="margin:20px;">
            	<h2>Plant Communities: plants provide habitat</h2>
				<!-- Chicago Wilderness definitions -->

				<p>Most habitats are classified by the plants that grow there. Different plants require varying conditions of air and soil moisture, amount of sunlight, temperature range, and soil type. These environmental or abiotic (non-living) factors determine which plants grow and survive in a particular place. The plants, in turn, provide the living structure of the habitat, whether it is hardwood forest, oak savanna, tall-grass prairie, or sedge meadow. The major plants of a habitat modify the environment.  For example, woodland trees provide shade and may raise soil moisture, allowing other plants to grow there. The entire plant community supports the diversity of other organisms, such as animals, fungi, and micro-organisms, within that community.  In short, plants define the community.</p>
            </div>
        </div>
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images/vplants/feature/ammophila.jpg" width="250" height="378" alt="dunes grass" title="Ammophila breviligulata">

			<div class="box imgtext">
			<p>The marram grass <i>Ammophila breviligulata</i>, a primary colonizer in dune habitats, helps stabilize the shifting sands, and consequently provides habitat for other plants and animals.
			</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>