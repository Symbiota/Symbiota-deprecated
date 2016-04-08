<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Topics - Special Concern</title>
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

						<h1>Plants of Special Concern</h1>
						
						<div style="margin:20px;">
							<h2>Causes for Concern</h2>
							<p>Over the past 200 years the Chicago Region has changed from the sprawling woodlands, wetlands, and prairie to a bustling metropolis, transportation and industrial center, and network of cities.  As the open wildlands shrink, natural plant communities have decreased in size, and overall biodiversity has declined.  Habitats become smaller and fragmented.  More plants and animals become less common and harder to find.  This causes great concern since the end result leads to extinction of species.  We should act as stewards of the natural world around us, if for no other reason than for our own benefit in terms of health, resources, and serenity.</p>
						
							<h2>The Listing of Plants</h2>
							<p>Certain species of plants (and animals) that experience decreases in frequency, population size, or number of populations have been assigned special status by state and federal governments.  Depending on the listing agency, ranks of Endangered, Threatened, Rare, Special Concern, Watch List or Extirpated are assigned to particular taxa (species, subspecies, or variety).  Typically, the taxa at highest risk for becoming extinct are listed as endangered, with the risk decreasing down to threatened, rare, etc.  Ideally, by protecting populations of species that are ranked on these lists, the extirpation (local extinction) of species in particular areas can be avoided.  Each state usually has their own ranking system and different protection laws or methods associated with those ranks.</p>
						
							<h2>Regional Lists</h2>
							<p>The Chicago Region covered by vPlants includes four states: Illinois, Indiana, Michigan, and Wisconsin. Each state has their own ranking system and different protection laws or methods associated with those ranks. In addition, the U.S. federal government has lists of species for the whole country. The taxa on these lists are often referred collectively by the following categories: Species of Concern, Sensitive Species, or T and E species (short for threatened and endangered species).
							</p>
							<p>
							For The vPlants Project, we have compiled a list of <a href="../resources/plant_concern.php" 
							title="See this document">Chicago Region Plants of Concern</a> that are listed for any of the four states of the area (Illinois, Indiana, Michigan, Wisconsin), plus the federal listed species.  View further information about this list as well as visit the state and federal web sites using the links on this page.
							</p>
						
							<h2>Protection</h2>
							<!-- Laws and preserves -->
							<p>&nbsp;</p>
							<p>&nbsp;</p>
							<p>&nbsp;</p>
							<p>&nbsp;</p>
							<p>&nbsp;</p>
							<p>&nbsp;</p>
							<p>&nbsp;</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/CIPI.jpg" width="250" height="337" alt="thistle growing in sand." title="Cirsium pitcheri" />
						<div class="box imgtext">
						<p>Pitcher's thistle, <a href="../taxa/index.php?taxon=Cirsium%20pitcheri"><i>Cirsium pitcheri</i></a>, is Federally listed as Threatened.  Its required habitat of open dune faces has become rarer through loss to development.</p>
						</div>
						
						<div class="box document">
						<h3>vPlants Documents</h3>
						<ul><li>
						<a href="../resources/plant_concern.php" 
						title="Document information">Chicago Region Plants of Concern</a>
						</li></ul>
						</div>

						<div class="box external">
						<h3>Related Web Sites</h3>
						<h4>Plant Special Concern Lists</h4>
						<ul><li>
						<a href="http://dnr.state.il.us/ESPB/" 
						title="External Link">Illinois Endangered Species Protection Board</a>
						</li><li>
						<a href="http://www.in.gov/dnr/naturepreserve/4878.htm" 
						title="External Link">Indiana plants</a>
						</li><li>
						<a href="http://web4.msue.msu.edu/mnfi/data/specialplants.cfm" 
						title="External Link">Michigan plants</a>
						</li><li>
						<a href="http://www.dnr.wi.gov/org/land/er/wlist/" 
						title="External Link">Wisconsin plants</a>
						</li><li>
						<a href="http://ecos.fws.gov/tess_public/SpeciesReport.do?groups=Q&amp;listingType=L" 
						title="External Link">Federal flowering plants</a>
						</li><li>
						<a href="http://ecos.fws.gov/tess_public/SpeciesReport.do?groups=R&amp;listingType=L" 
						title="External Link">Federal gymnosperms</a>
						</li><li>
						<a href="http://ecos.fws.gov/tess_public/SpeciesReport.do?groups=S&amp;listingType=L" 
						title="External Link">Federal ferns and fern allies</a>
						</li></ul>

						<h4>Fungus Special Concern Lists</h4>
						<ul><li>
						<a href="http://www.dnr.state.mn.us/ets/lichens.html" 
						title="External Link">Minnesota Lichens, mosses, fungi</a>
						</li><li>
						<a href="http://ecos.fws.gov/tess_public/SpeciesReport.do?groups=U&amp;listingType=L" 
						title="External Link">Federal lichens</a>
						</li></ul>

						<h4>World and National Red Lists</h4>
						<ul><li>
						<a href="http://www.iucnredlist.org/" 
						title="External Link">World Conservation Union Red List of Threatened Species</a>
						</li><li>
						<a href="http://www.wsl.ch/eccf/redlists-en.ehtml" 
						title="External Link">European Council for the Conservation of Fungi - links to Red Lists</a>
						</li></ul>
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