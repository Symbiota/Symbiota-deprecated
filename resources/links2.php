<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Resources - Plant Links</title>
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
        </div>
		
		<div id="content2">
			<img src="<?php echo $clientRoot; ?>/images/vplants/feature/IMPA.po.jpg" width="250" height="244"  alt="Pendant yellow flowers with spurs" title="Impatiens" />
			<p>Yellow jewelweed</p>

			<div class="box">
			<h3>Related Pages</h3>
			<ul><li><a href="docs2.php"
			   title="View or download files and working documents.">Plant Documents</a>
			</li><li><a href="biblio2.php"
			   title="List of Books and other literature.">Plant References</a>
			</li></ul>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>