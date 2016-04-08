<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Plant Links</title>
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
            <h1>Links to Plant Web Sites</h1>

            <div style="margin:20px;">
            	 <p>See also general links on the <a href="links.html">Related Links Page</a>.</p>

				<h3>General:</h3>

				<p>
				United States Department of Agriculture, Plants Database<br />
				<a href="http://plants.usda.gov/">http://plants.usda.gov/</a>
				</p>

				<p>
				Flora of North America Project<br />
				 <a href="http://hua.huh.harvard.edu/FNA/index.html">http://hua.huh.harvard.edu/FNA/index.html</a>
				</p>

				<p>
				Illinois Plant Information Network<br />
				<a href="http://www.fs.fed.us/ne/delaware/ilpin/ilpin.html">http://www.fs.fed.us/ne/delaware/ilpin/ilpin.html</a>
				</p>

				<p>
				Gallery of Illinois Plants, Illinois Natural History Survey<br />
				<a href="http://www.inhs.uiuc.edu/cwe/illinois_plants/PlantsofIllinois.html">http://www.inhs.uiuc.edu/cwe/illinois_plants/PlantsofIllinois.html</a>
				</p>

				<p>
				Illinois' Best Plants, Chicago Botanic Garden<br />
				<a href="http://bestplants.chicago-botanic.org/toc.htm">http://bestplants.chicago-botanic.org/toc.htm</a>
				</p>

				<p>
				Digital Flowers<br />
				<a href="http://www.life.uiuc.edu/plantbio/digitalflowers/index.htm">http://www.life.uiuc.edu/plantbio/digitalflowers/index.htm</a>
				</p>

				<p>
				The International Plant Names Index<br />
				<a href="http://www.ipni.org/">http://www.ipni.org/</a>
				</p>


				<h3>Threatened and Endangered Plants:</h3>

				<p>
				Center for Plant Conservation, National Collection of Endangered Plants<br />
				<a href="http://www.centerforplantconservation.org/NC_Choice.html">http://www.centerforplantconservation.org/NC_Choice.html</a>
				</p>

				<p>
				Illinois Department of Natural Resources, The Illinois Endangered Species Protection Board<br /> <a href="http://www.dnr.state.il.us/espb/">http://www.dnr.state.il.us/espb/</a>
				</p>

				<p>
				Indiana Department of Natural Resources, Endangered Species<br />
				1) List of Endangered, Threatened and Rare Species by county (list of PDF files)<br />
				<a href="http://www.in.gov/dnr/naturepr/species/index.html">http://www.in.gov/dnr/naturepr/species/index.html</a><br />
				2) Endangered, Threatened, and Rare Vascular Plants of Indiana (PDF) <br />
				<a href="http://www.in.gov/dnr/naturepr/endanger/etrplants.pdf">http://www.in.gov/dnr/naturepr/endanger/etrplants.pdf</a>
				</p>

				<p>
				Wisconsin Department of Natural Resources, Wisconsin State Threatened and Endangered Species<br />
				<a href="http://www.dnr.state.wi.us/org/land/er/working_list/taxalists/TandE.asp">http://www.dnr.state.wi.us/org/land/er/working_list/taxalists/TandE.asp</a>
				</p>

				<h3>On-line guides:</h3>

				<p>
				UW-Stevens Point vascular plant page offering identification guides on-line<br />
				<a href="http://143.236.2.135/VascularPlants.html">http://143.236.2.135/VascularPlants.html</a>
				</p>

				<p>
				Discover Life's IDNature Guides <br />
				<a href="http://pick4.pick.uga.edu/mp/20q">http://pick4.pick.uga.edu/mp/20q</a>
				</p>

				<p>
				Plant Systematics.org's on-line key to Dicot Families<br />
				Click on Diagnostic Keys, then choose Key to Families of Dicotyledons<br />
				<a href="http://www.plantsystematics.org/">http://www.plantsystematics.org</a>
				</p>

				<h3>Online plant glossaries:</h3>

				<p>
				GardenWeb Glossary<br />
				 <a href="http://glossary.gardenweb.com/glossary/">http://glossary.gardenweb.com/glossary/</a>
				</p>

				<p>
				California Plant - Botanical Terms<br />
				<a href="http://www.calflora.net/botanicalnames/botanicalterms.html">http://www.calflora.net/botanicalnames/botanicalterms.html</a>
				</p>

				<h3>Information on plants for cultivation or other uses:</h3>

				<p>
				Link to NYBG's Plant Information FAQ page<br />
				<a href="http://www.nybg.org/plants1/more_info.html">http://www.nybg.org/plants1/more_info.html</a>
				</p>


				<!-- End of links -->
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>