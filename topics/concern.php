<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Special Concern</title>
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
            <h1>Plants of Special Concern</h1>

            <div style="margin:20px;">
            	<h2>Regional Lists</h2>

				<p>The Chicago Region covered by vPlants includes four states: Illinois, Indiana, Michigan, and Wisconsin. Each state has their own ranking system and different protection laws or methods associated with those ranks. In addition, the U.S. federal government has lists of species for the whole country. The taxa on these lists are often referred collectively by the following categories: Species of Concern, Sensitive Species, or T and E species (short for threatened and endangered species).
				</p>

				<p>
				For The vPlants Project, we have compiled a list of <a href="../resources/plant_concern.php" 
				title="See this document">Chicago Region Plants of Concern</a> that are listed for any of the four states of the area (Illinois, Indiana, Michigan, Wisconsin), plus the federal listed species.  View further information about this list as well as visit the state and federal web sites using the links on this page.
				</p>

				<h2>Are there any special concern fungi?</h2>

				<p>Yes, see links on this page. Here in the Midwest, as of 2006, Minnesota is the only state that has listed threatened and endangered fungi. The U.S. Federal listing includes 2 lichens (lichenized fungi). Many European countries have Red Lists for fungi.  As with the plants, if any fungi become listed for our region then vPlants will add the Regional Conservation Status section for those species.</p>
            </div>
        </div>
		
		<div id="content2">

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

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>