<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - About Plants - Habitats</title>
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
		<!-- start of inner text and right side content -->
		<div  id="innervplantstext">
			<div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>
					<h1>Habitats of the Chicago Region</h1>

					<div style="margin:20px;">
						<h2>Plant Communities: plants provide habitat</h2>
						<!-- Chicago Wilderness definitions -->

						<p>Most habitats are classified by the plants that grow there. Different plants require varying conditions of air and soil moisture, amount of sunlight, temperature range, and soil type. These environmental or abiotic (non-living) factors determine which plants grow and survive in a particular place. The plants, in turn, provide the living structure of the habitat, whether it is hardwood forest, oak savanna, tall-grass prairie, or sedge meadow. The major plants of a habitat modify the environment.  For example, woodland trees provide shade and may raise soil moisture, allowing other plants to grow there. The entire plant community supports the diversity of other organisms, such as animals, fungi, and micro-organisms, within that community.  In short, plants define the community.</p>
						
						<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/habitats2.php">Woodlands</a></div>
						<div class="indexdescription"><p>Add description here...<a href="<?php echo $clientRoot; ?>/plants/habitats2.php">Learn more</a></p></div>
						
						<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/habitats3.php">Grasslands</a></div>
						<div class="indexdescription"><p>Add description here...<a href="<?php echo $clientRoot; ?>/plants/habitats3.php">Learn more</a></p></div>
						
						<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/habitats4.php">Wetlands</a></div>
						<div class="indexdescription"><p>There are many different types of wetlands found in the Chicago Region. Some examples include bogs, fens, marshes, pannes, ponds, and swamps (see list below). Many of our plants grow only in or around wetlands, yet wetlands are at high risk from human impacts such as drainage, pollution, and invasion by alien plants. <a href="<?php echo $clientRoot; ?>/plants/habitats4.php">Learn more</a></p></div>
						
						<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/habitats5.php">Urban Areas</a></div>
						<div class="indexdescription"><p>Add description here...<a href="<?php echo $clientRoot; ?>/plants/habitats5.php">Learn more</a></p></div>
					</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
				
					<div id="content2">

						<div class="box">
							<h3>Habitats of the Chicago Region</h3>
							<ul>
								<li><strong>Habitats Main</strong></li>
								<li><a href="habitats2.php">Woodlands</a></li>
								<li><a href="habitats3.php">Grasslands</a></li>
								<li><a href="habitats4.php">Wetlands</a></li>
								<li><a href="habitats5.php">Urban Areas</a></li>
							</ul>
						</div>
						
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/ammophila.jpg" width="250" height="378" alt="dunes grass" title="Ammophila breviligulata">

						<div class="box imgtext">
						<p>The marram grass <i>Ammophila breviligulata</i>, a primary colonizer in dune habitats, helps stabilize the shifting sands, and consequently provides habitat for other plants and animals.
						</p>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>