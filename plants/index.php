<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - About Plants<</title>
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
						<h1>About Plants</h1>

						<div style="margin:20px;">
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/chicagoplants.php">Chicago Plants</a></div>
							<div class="indexdescription"><p>Nearly 2,700 different species of vascular plants are recorded in the 24 counties of the Chicago Region. There are an additional 300 subspecies, varieties, or forms. Within these 3,000 taxa, approximately 1650 taxa (55% of flora) are native. Considering the relatively small physical area of the Region, this is a surprisingly large number of species of vascular plants. <a href="<?php echo $clientRoot; ?>/plants/chicagoplants.php">Learn more</a></p></div>

							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/guide/index.php">Guide</a></div>
							<div class="indexdescription"><p>A guide to the main plant groups. <a href="<?php echo $clientRoot; ?>/plants/guide/index.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/glossary/index.php">Glossary</a></div>
							<div class="indexdescription"><p>A glossary of plant terms based on the printed glossary from the Fourth Edition of <i>Plants of the Chicago Region</i> by Swink and Wilhelm (1994).<a href="<?php echo $clientRoot; ?>/plants/glossary/index.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/biology.php">Biology</a></div>
							<div class="indexdescription"><p>Plants exhibit tremendous diversity. Most plants are green and use the sun's energy to produce their own food; others lack chlorophyll and feed off of other organisms. Some plants grow in the water and many grow on the land. Land plants and green algae make up the <a href="http://tolweb.org/tree?group=Green_plants&contgroup=Eukaryotes">Kingdom Plantae</a> or Green Plants. <a href="<?php echo $clientRoot; ?>/plants/biology.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/diversity.php">Diversity</a></div>
							<div class="indexdescription"><p>Despite the skyscrapers, expanses of concrete, heavy industry, and large population centers, the Chicago Region has a very diverse composition of plants (flora). There are almost 2,700 individual species of vascular plants found within this area of 30,557 square kilometers (11,798 square miles). Compare that to the only 1,400 species that occur in the similarly sized country of Belgium (30,510 sq. km). <a href="<?php echo $clientRoot; ?>/plants/diversity.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/habitats.php">Habitats</a></div>
							<div class="indexdescription"><p>Most habitats are classified by the plants that grow there. Different plants require varying conditions of air and soil moisture, amount of sunlight, temperature range, and soil type. These environmental or abiotic (non-living) factors determine which plants grow and survive in a particular place. The plants, in turn, provide the living structure of the habitat, whether it is hardwood forest, oak savanna, tall-grass prairie, or sedge meadow. <a href="<?php echo $clientRoot; ?>/plants/habitats.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/origin.php">Origin</a></div>
							<div class="indexdescription"><p>Plants, fungi, and other organisms have different distribution patterns and ranges. In a particular place, such as the Chicago Region, the life that occurs there can be placed in several categories based on origin and lifestyle: native, non-native, invasive. <a href="<?php echo $clientRoot; ?>/plants/origin.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/distribution.php">Distribution</a></div>
							<div class="indexdescription"><p>Biogeography is the study of the distribution of life on earth. There are many common patterns for the distribution of plants, fungi, and animals. <a href="<?php echo $clientRoot; ?>/plants/distribution.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/names.php">Names</a></div>
							<div class="indexdescription"><p>What's in a name? It is human nature to name things. We use names to communicate information and assign an identity to people and objects. For plants, fungi, and other organisms there are several kinds of names: taxon, scientific names, synonyms, and common names. <a href="<?php echo $clientRoot; ?>/plants/names.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/collections.php">Collections</a></div>
							<div class="indexdescription"><p>An herbarium (her-bear'-ee-um) is a collection of pressed, dried plants. Normally herbaria are located at museums, botanic gardens, arboreta, universities, or other research institutions where scientists study botany. Each herbarium specimen contains actual plant material as well as label information detailing facts of the specimen such as the collector(s), date of collection, and collection site details. <a href="<?php echo $clientRoot; ?>/plants/collections.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/concern.php">Plants of Special Concern</a></div>
							<div class="indexdescription"><p>Certain species of plants (and animals) that experience decreases in frequency, population size, or number of populations have been assigned special status by state and federal governments. Depending on the listing agency, ranks of Endangered, Threatened, Rare, Special Concern, Watch List or Extirpated are assigned to particular taxa. <a href="<?php echo $clientRoot; ?>/plants/concern.php">Learn more</a></p></div>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>