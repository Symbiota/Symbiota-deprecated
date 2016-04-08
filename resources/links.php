<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Resources - Links</title>
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
						<h1>Plant Links</h1>

						<div style="margin:20px;">
							<h2>General</h2>

							<ul><li><a href="http://plants.usda.gov/">United States Department of Agriculture, Plants Database</a></li>
							<li><a href="http://hua.huh.harvard.edu/FNA/index.html">Flora of North America Project</a></li>
							<li><a href="http://www.fs.fed.us/ne/delaware/ilpin/ilpin.html">Illinois Plant Information Network</a></li>
							<li><a href="http://www.inhs.uiuc.edu/cwe/illinois_plants/PlantsofIllinois.html">Gallery of Illinois Plants, Illinois Natural History Survey</a></li>
							<li><a href="http://bestplants.chicago-botanic.org/toc.htm">Illinois' Best Plants, Chicago Botanic Garden</a></li>
							<li><a href="http://www.life.uiuc.edu/plantbio/digitalflowers/index.htm">Digital Flowers</a></li>
							<li><a href="http://www.ipni.org/">The International Plant Names Index</a></li>
							</ul>

							<h2>Threatened and Endangered Plants:</h2>

							<ul><li><a href="http://www.centerforplantconservation.org/NC_Choice.html">Center for Plant Conservation, National Collection of Endangered Plants</a></li>
							<li><a href="http://www.dnr.state.il.us/espb/">Illinois Department of Natural Resources, The Illinois Endangered Species Protection Board</a></li>
							<li>Indiana Department of Natural Resources, Endangered Species<ol>
							 <li><a href="http://www.in.gov/dnr/naturepr/species/index.html">List of Endangered, Threatened and Rare Species by county (list of PDF files)</a></li>
							 <li><a href="http://www.in.gov/dnr/naturepr/endanger/etrplants.pdf">Endangered, Threatened, and Rare Vascular Plants of Indiana (PDF)</a></li>
							</ol></li>
							<li><a href="http://www.dnr.state.wi.us/org/land/er/working_list/taxalists/TandE.asp">Wisconsin Department of Natural Resources, Wisconsin State Threatened and Endangered Species</a></li>
							</ul>

							<h2>On-line guides:</h2>

							<ul><li><a href="http://wisplants.uwsp.edu/WisPlants.html">UW-Stevens Point -- Plants of Wisconsin</a></li>
							<li><a href="http://www.uwgb.edu/biodiversity/herbarium/pteridophytes/pteridophytes_of_wisconsin01.htm">UW-Green Bay -- Pteridophytes of Wisconsin:  Ferns and Fern Allies</a></li>
							<li><a href="http://www.missouriplants.com/">Missouri Plants</a> - Photographs and descriptions</li>
							<li><a href="http://pick4.pick.uga.edu/mp/20q">Discover Life's IDNature Guides</a></li>
							<li><a href="http://www.plantsystematics.org/">Plant Systematics.org's on-line key to Dicot Families</a> - Click on Diagnostic Keys, then choose Key to Families of Dicotyledons</li>
							</ul>

							<h2>Online plant glossaries:</h2>

							<ul><li><a href="http://glossary.gardenweb.com/glossary/index.html">GardenWeb Glossary</a></li>
							<li><a href="http://www.calflora.net/botanicalnames/botanicalterms.html">California Plant - Botanical Terms</a></li>
							</ul>

							<h2>Information on plants for cultivation or other uses:</h2>

							<ul><li><a href="http://www.chicagobotanic.org/plantinfo/">Chicago Botanic Garden - Plant Information</a></li>
							 <li><a href="http://bestplants.chicago-botanic.org/">Chicago Botanic Garden - Illinois' Best Plants</a></li>
							 <li><a href="http://www.chicagowilderness.org/wildchi/landscape/index.cfm">Chicago Wilderness - Landscaping with Native Plants</a></li>
							 <li><a href="http://www.nybg.org/plants1/more_info.html">New York Botanical Garden - Home Gardening Online</a> (requires Free Online Subscription)</li>
							</ul>
						</div>
						 
						<h1>Natural History Education</h1>

						<div style="margin:20px;">

							<h3><a href="http://www.aapcc.org/">Poison Control Center</a> 800-222-1222</h3>
							
							<h3>Illinois</h3>
							<ul>
							 <li><a href="http://www.mortonarb.org/main.taf?p=4,2,5,2">Naturalist Certificate Program</a></li>
							 <li><a href="http://www.mortonarb.org/main.taf?p=4">The Morton Arboretum</a></li>
							 <li><a href="http://www.fieldmuseum.org/education/">The Field Museum</a></li>
							 <li><a href="http://www.chicagobotanic.org/education/">Chicago Botanic Garden</a></li>
							</ul>

							<h3>Indiana</h3>
							<ul>
							 <li><a href="http://www.in.gov/dnr/masternaturalist/">Indiana Master Naturalist Program</a></li>
							</ul>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/IMPA.po.jpg" width="250" height="244"  alt="Pendant yellow flowers with spurs" title="Impatiens" />
						<p>Yellow jewelweed</p>

						<div class="box external">
						<h3>vPlants Partners</h3>
						<ul><li><a href="http://www.mortonarb.org">The Morton Arboretum</a></li>
						<li><a href="http://www.fieldmuseum.org">The Field Museum</a></li>
						<li><a href="http://www.chicagobotanic.org">Chicago Botanic Garden</a></li>
						 <li><a href="http://www.chias.org/">Chicago Academy of Sciences [Notebaert Nature Museum]</a></li>
						<li><a href="http://www.inhs.uiuc.edu">Illinois Natural History Survey</a></li>
						</ul>
						</div>

						<div class="box external">
						<h3>vPlants Affiliates</h3>
						<ul><li><a href="http://www.chicagowilderness.org/">Chicago Wilderness</a></li>
						</ul>
						</div>

						<div class="box">
						<h3>vPlants funded by</h3>
						<ul><li><a href="http://www.imls.gov">Institute of Museum and Library Service</a></li>
						</ul>
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