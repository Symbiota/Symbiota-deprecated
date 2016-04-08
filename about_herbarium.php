<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - About Us</title>
	<link href="css/base.css" type="text/css" rel="stylesheet" />
	<link href="css/main.css" type="text/css" rel="stylesheet" />
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
            <h1></h1>

            <div style="margin:20px;">
            	<!-- 
				<h2 >About Collections, Specimens, and Herbaria</h2>

				<p>
				xx
				<p>
				-->

				<h3>What is an herbarium?</h3>

				<p>
				An herbarium (her bear' ee um) is a collection of pressed, dried plants.  Normally herbaria are located at museums, botanic gardens, arboreta, universities, or other research institutions where scientists study botany.  Each herbarium specimen contains actual plant material as well as label information detailing attributes of the specimen such as the collector(s), date of collection, and collection site details (e.g., geopolitical location, <acronym title="Global Positioning System">GPS</acronym> coordinates, or habitat information).  The specimens in a herbarium are normally preserved on archival-quality, acid-free paper and housed within airtight storage cabinets.  As long as the specimens are kept free from damage by insects, rodents, mold, moisture, or drastic temperature changes they can last hundreds of years.  In a herbarium, specimens are arranged acccording to a particular system, not unlike how libraries use different systems to catalogue books.  The details of the organization system at each herbarium differs, but typically the name of the plant on the sheet (normally in Latin) is the key piece of data used to place the specimen at the correct location within the system.
				</p>

				<h3>How are herbarium specimen data useful?</h3>

				<p>
				Herbarium specimens record the past, providing users with documented occurrences of plants in specific locations over time.  Often the data on specimen labels present the best information about past species and community distributions or the historical coverage of particular habitats.  Without databases such as vPlants, this data is hard or even impossible to access.  Further, searching specimen collections from different institutions is time consuming and exhausting even if the data is available electronically.  By providing region-specific plant specimen data from multiple herbaria, the vPlants database makes important information available to anyone, not just scientific researchers.
				</p>

				<h3>Other collections in a herbarium</h3>
				<p>A herbarium may also contain fungus specimens such as mushrooms and lichens. Because these are rarely pressed flat, they are typically stored in boxes or paper packets.  An ethnobotany collection contains man-made products of plants such as clothing, tools, and other objects, as well as resins and other extracts. A paleobotany collection consists of plant fossils.
				</p>

				<p>
				Users of a traditional herbarium may include the following:
				<ul>
				<li>
				Taxonomists
					<ul>
					<li>Identify and validate specimen data</li>
					<li>Annotate the scientific names used to describe the specimen</li>
					<li>Conduct molecular genetics studies</li>
					<li>Compile regional floras or keys</li>
					</ul>
				</li>
				<li>
				Conservation Scientists
					<ul>
					<li>Perform research</li>
					<li>Sample plant material for chemical or genetic analyses</li>
					</ul>
				</li>
				<li>
				Conservation Stewards, Students, and Educators
					<ul>
					<li>Interpret the past for guidance in restoration projects</li>
					<li>Learn characteristics of native and cultivated plants</li>
					<li>Foster future botanists</li>
					</ul>
				</li>
				</ul>
				</p>
            </div>
        </div>
		
		<div id="content2"><!-- start of side content -->
			<p class="hide">
			<a id="secondary" name="secondary"></a>
			<a href="#sitemenu">Skip to site menu.</a>
			</p>

			<!-- image width is 250 pixels -->

			<img src="<?php echo $clientRoot; ?>/images.vplants/feature/compactors.jpg" width="250" height="249" alt="Compactor cabinets that are in rows on tracks and moved manually." />
			<div class="box">
			<p>
			Movable rows (compactors) of herbarium cabinets at the Field Museum, which store a portion of the over 2.7 million specimens at that herbarium.
			</p>
			</div>

			<img src="<?php echo $clientRoot; ?>/images.vplants/feature/V0030596F.jpg" width="250" height="362" alt="Specimen of Hibiscus moscheutos ssp. moscheutos collected in Chicago in 1891." />
			<div class="box">
			<p>
			Herbarium specimen of <i>Hibiscus moscheutos</i> L. ssp. <i>moscheutos</i> that was collected in the Hegewisch neighborhood of Chicago in 1891.
			</p>
			</div>

			<p class="small">
			Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p>
			<p class="small">
			<a class="popup" href="/disclaimer.html" 
			title="Read Disclaimer [opens new window]." 
			onclick="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;" 
			onkeypress="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;">Disclaimer</a>
			</p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>