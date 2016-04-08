<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Topics - Herbarium Collections</title>
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
        <div  id="innervplantstext">
            <h1>Herbarium Collections</h1>

            <div style="margin:20px;">
            	<h2>What is an Herbarium?</h2>
				<!-- how collections made, preserved,  -->

				<p>
				An herbarium (her-bear'-ee-um) is a collection of pressed, dried plants.  Normally herbaria are located at museums, botanic gardens, arboreta, universities, or other research institutions where scientists study botany.  Each herbarium specimen contains actual plant material as well as label information detailing facts of the specimen such as the collector(s), date of collection, and collection site details (e.g., geopolitical location, <acronym title="Global Positioning System">GPS</acronym> coordinates, or habitat information).  The specimens in a herbarium are normally preserved on archival-quality, acid-free paper and housed within airtight storage cabinets.  As long as the specimens are kept free from damage by insects, rodents, mold, moisture, or drastic temperature changes they can last hundreds of years.  In a herbarium, specimens are arranged according to a particular system, not unlike how libraries use different systems to catalogue books.  The details of the organization system at each herbarium differs, but typically the name of the plant on the sheet (normally in Latin) is the key identifier used to place the specimen at the correct location within the system. Rarely does a herbarium file specimens by collector name instead of species.
				</p>

				<h2>How are herbarium specimen data useful?</h2>

				<p>
				Herbarium specimens record the past, providing users with documented occurrences of plants in specific locations over time.  Often the data on specimen labels present the best information about past species and community distributions or the historical coverage of particular habitats.  Without databases such as vPlants, these data are hard or even impossible to access.  Further, searching specimen collections from different institutions is time consuming and exhausting even if the data are available electronically.  By providing region-specific plant specimen data from multiple herbaria, the vPlants database makes important information available to anyone, not just scientific researchers.
				</p>



				<p>
				Users of a traditional herbarium may include the following:
				<ul>
				<li>
				Taxonomists
					<ul>
					<li>Identify specimens or use them to identify other collections</li>
					<li>Annotate (correct, update, validate) scientific names</li>
					<li>Study variation in morphological and chemical characters</li>
					<li>Compile regional floras or keys</li>
					<li>Conduct molecular genetics studies (<acronym title="deoxyribonucleic acid">DNA</acronym>)</li>
				 <li>All of which promotes systematics and understanding of diversity</li>
					</ul>
				</li>
				<li>
				Conservation Scientists
					<ul>
					<li>Perform research, such as examine distribution and rarity</li>
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
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images.vplants/feature/herb_compactors.jpg" width="250" height="249" alt="Rows of cabinets on tracks that are moved using a crank." title="Mechanical compactor cabinets at The Field Museum.">
			<p>Compactor cabinets that are in rows on tracks and moved manually for easy access.</p>

			<div class="box">
			<h3>More than just plants</h3>
			<h4>A mycology herbarium</h4> <p>contains fungus specimens such as mushrooms and lichens. Because these are rarely pressed flat, they are typically stored in boxes or paper packets. Microscopic fungi and protozoa may be preserved on sealed microscope slides.</p>
			<h4>An ethnobotany collection</h4> <p>holds man-made products of plants such as clothing, tools, and other objects, as well as resins and other extracts. Various containers are used and placed on open shelves.</p>
			<h4>A paleobotany collection</h4> <p>consists of plant fossils. These are often arranged by geologic period in drawers, trays, and boxes.</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>