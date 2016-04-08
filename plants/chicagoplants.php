<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Chicago Region Plants<</title>
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
					<h1>Plants of the Chicago Region</h1>

					<div style="margin:20px;">
						<p>
						Nearly 2,700 different species of <a href="<?php echo $clientRoot; ?>/plants/guide/index.php" title="Guide to plants.">vascular plants</a> are recorded in the
						 <a href="<?php echo $clientRoot; ?>/about/map_county.php" title="See County Map for Chicago Region.">24 counties</a>
						of the 
						 <a href="<?php echo $clientRoot; ?>/about/chicago.php" title="Why the Chicago Region?">Chicago Region</a>. There are an additional 300 subspecies, varieties, or forms.  Within these 
						 <a href="<?php echo $clientRoot; ?>/plants/diversity.php" title="How many plants.">3,000 taxa</a>, approximately 1650 taxa (55% of flora) are native.  Considering the relatively small physical area of the Region, this is a surprisingly large number of species of vascular plants.
						</p>

						<div id="floatimg"><img src="<?php echo $clientRoot; ?>/images/vplants/feature/plant_170_250.jpg" width="170" height="250" alt="detail view of spore cases on leaf." title="Dryopteris marginalis (Photo by W. C. Burger)."></div>

						<p>
						Vascular Plants are the majority of plants we see: wildflowers, grasses, trees, shrubs, vines, and ferns. Many plants are perennial, living for several years to hundreds of years. Other plants are annual or biennial, growing from seed and living one or two seasons.  Identification of plants is based on the whole organism, the reproductive structures (flowers, cones, or sporangia), the leaves, stems, and even the form of the roots.
						</p>

						<p>The <a href="http://www.tolweb.org/Green_plants/">Kingdom Plantae [external link]</a> is comprised of several major groups.
						Most plants treated here are angiosperms, the flowering plants. The conifers and other gymnosperms lack flowers. The ferns, horsetails, clubmosses, and spikemosses are vascular plants that lack seeds.
						</p>

						<h3>Plants not included in vPlants at the present time</h3>

						<p>
						The bryophytes, that is the mosses, liverworts, and hornworts, are not yet included in vPlants.
						Also absent are the various groups of green algae, the basal members of the plant kingdom.
						</p>
					</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2"><!-- start of side content -->
					<!-- any image width should be 250 pixels -->
			 
						<div class="box">
							<h3>Directory and Guides</h3>
							<ul>
								<li><a href="guide/" title="Identification guide">Guide to Plants</a></li>
								<li><a href="../checklists/checklist.php?cl=3503&pid=93" title="List of Plant Species.">Species Index</a></li>
								<li><a href="<?php echo $clientRoot; ?>/resources/biblio.php" title="Guides for Chicago Region">Plant References</a></li>
								<li><a href="<?php echo $clientRoot; ?>/resources/links.php" title="Links to websites">Plant Links</a></li>
							</ul>
						</div>

						<div id="simpleform">
							<fieldset>
								<legend title="Enter name of plant or fungus in one or more of the search fields.">Name Search</legend>
								<?php
								$buttonText = 'Go';
								include_once($serverRoot.'/classes/PluginsManager.php');
								$pluginManager = new PluginsManager();
								$quicksearch = $pluginManager->createQuickSearch($buttonText);
								echo $quicksearch;
								?>
							</fieldset>
						</div>

						<p class="large">
							  <a href="<?php echo $clientRoot; ?>/collections/index.php" 
							   title="Search by Location, Collector, and more.">Go to Advanced Search</a>
						</p>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>