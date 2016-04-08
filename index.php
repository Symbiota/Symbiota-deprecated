<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Home</title>
	<link href="css/base.css" type="text/css" rel="stylesheet" />
	<link href="css/main.css" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once('config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innervplantstext">
			<div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>

					<h2><a href="about/" title="About vPlants and its partners.">vPlants</a>: a Virtual Herbarium of the Chicago Region.</h2>

					<p class="large">The online resource for <a href="plants/" title="Plant directory."><strong>plants</strong></a> of the Chicago Region, offering:</p>
					<ul class="large">
					<li>Specimen data and images</li>
					<li>Species descriptions</li>
					<li>Distribution by county</li>
					<li>Photo galleries</li>
					</ul>

					<div id="floatimg"><img src="images/vplants/feature/home_170_250.jpg" width="170" height="250" alt="meadow of flowers along edge of lake." title="DeKalb County, west of Chicago."></div><p class="large">Currently the site contains data for more than 120,000 plant specimens records from more than 30 institutions. The core of these collections, ca, 80,000, come from three institution with particularly rich Chicago Region collections: the Field Museum of Natural History, The Morton Arboretum, and the Chicago Botanic Garden. These three institutions formed vPlants in 2001.</p>
					<p>The original vPlants system was built to be a scalable herbarium data portal for the Chicago region. In 2015, the system was migrated to Symbiota, where data are now combined with data from herbaria spread across the U.S., many contributing specimens that increase our knowledge of the Chicagoland flora.</p>

					<div id="gomenu">Next &#187; <a href="chicago.php">Why the Chicago Region?</a></div>

					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->

					<div id="content2"><!-- start of side content -->
					<!-- any image width should be 250 pixels -->

					<div class="maps">
					<a href="chicago.php" title="Why the Chicago Region?"><img src="images/vplants/img/map_na_65.gif" width="65" height="65" alt="Map of North America showing location of Chicago Region."></a>
					<a href="map.php" title="See State Map for Chicago Region."><img src="images/vplants/img/map_grtlakes_65.gif" width="65" height="65" alt="The vPlants Region is located within four states at the south end of Lake Michigan."></a>
					<a href="map_county.php" title="See County Map for Chicago Region."><img src="images/vplants/img/map_vplants_65.gif" width="65" height="65" alt="The vPlants Region includes 24 counties."></a></div>

					<p><a href="chicago.php" title="Why the Chicago Region?">Why focus on the Chicago Region?</a></p>
					<p><a href="topics/" title="What is a herbarium?">What is a herbarium?</a></p>

					<div id="simpleform">
						<fieldset>
							<legend title="Enter name of plant in one or more of the search fields.">Name Search</legend>
							<?php
							$buttonText = 'Go';
							include_once($serverRoot.'/classes/PluginsManager.php');
							$pluginManager = new PluginsManager();
							$quicksearch = $pluginManager->createQuickSearch($buttonText);
							echo $quicksearch;
							?>
						</fieldset>
					</div>

					<p class="large"><a href="<?php echo $clientRoot; ?>/collections/index.php" title="Search by Location, Collector, and more.">Go to Advanced Search</a></p>

					<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p>
					<p class="small noprint"><a href="disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->

				</div><!-- end of #wrapper1 -->
			</div><!-- End of #bodywrap -->
		</div>
	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>