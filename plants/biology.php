<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Plant Biology</title>
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
					<h1>Plant Biology</h1>

					<div style="margin:20px;">
						<p>Plants exhibit tremendous diversity. Most plants are green and use the sun's energy to produce their own food; others lack chlorophyll and feed off of other organisms. Some plants grow in the water and many grow on the land. Land plants and green algae make up the
						<a href="http://tolweb.org/tree?group=Green_plants&contgroup=Eukaryotes">Kingdom Plantae [external link]</a> or Green Plants.</p>

						<h2>Plants are producers</h2>

						<p>Producers, or autotrophs (self-feeding), are organisms that are capable of creating their own food from inorganic (non-living) substances using light or chemical energy that is available in the environment surrounding them.  Typically, they are able to make their own life-sustaining organic compounds from simple and readily-available gases such as carbon dioxide and inorganic nitrogen, together with water. In certain environments other compounds such as sulfur or methane are used. In most environments on earth (both on land and in water), plants are the major producers. Through photosynthesis, plants trap light energy and use carbon dioxide and water to form life-sustaining sugars, which are converted to downstream building blocks (like carbohydrates and proteins), that allow plants to grow and thrive.</p>

						<p>Luckily for oxygen-consuming organisms (like humans), plants release oxygen as a byproduct from photosynthesis (when water is split).  Without plants, our planet would not have the hospitable atmosphere that exists, and life as we know it today would not have been able to evolve.  Normally, producers act as food sources for other organisms, namely consumers.</p>

						<p>Plants are called primary producers because they form a foundation in food webs and act as starting points in the energy and carbon cycles of an ecosystem.  Some microorganisms, such as cyanobacteria are also producers, but animals and fungi are primary consumers (herbivores) or secondary consumers (carnivores) that rely on plants for food and chemical energy. Omnivores (such as many humans) eat both plants and animals.</p>
					</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2"><!-- start of side content -->
					<!-- any image width should be 250 pixels -->
					 
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/home_250_170.jpg" width="250" height="170" alt="Meadow with flowers and trees in background." title="Habitat near Chicago." />
						<div class="box imgtext">
						<p>
						Grasslands, woodlands, and wetlands, have many species of plants, all of them producing carbohydrates, amino acids, and other compounds, which form the basis of the food web. Algae in the lake do the same.
						</p>
						</div>
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/PARNA.na01.jpg" width="250" height="392" alt="">
						<div class="box imgtext">
						<p>
						Three producers: <i>Gentiana</i>, <i>Parnassia</i>, and <i>Agalinis</i>.
						Their flowers are for reproduction.
						</p>
						</div>


						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="/disclaimer.html" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>