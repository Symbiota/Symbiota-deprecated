<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Fungal Diversity</title>
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
            <h1>Fungal Diversity in the Chicago Region</h1>

            <div style="margin:20px;">
            	<p>
				The landscape surrounding the Chicago metropolis is a melting pot of woodlands, savannas, wetlands, and prairie, where the eastern deciduous forest meets the farmlands of the plains. And all is wrapped around the south end of Lake Michigan and overlaid on a diverse foundation of old dune ridges, moraines, and other glacial features. <strong>The variety of plant communities supports a very diverse composition of fungi</strong> (mycota or mycobiota). 

				<a href="#table1" title="Table 1: Facts about Chicago Region Fungi.">See Table below.</a> 

				There are more than <strong>1,300 individual species of fungi</strong> found within this area of 30,557 square kilometers (11,798 square miles).  

				The primary reason for the level of fungal diversity is the wide range of <a href="/topics/habitats.html" title="Plant communities.">habitats</a> found here which in turn is based on the <a href="/plants/diversity.html" title="Plant Diversity.">diversity of plants</a> and the <a href="/chicago.html" title="Why the Chicago Region?">geographic and geologic features</a> of the area.  
				</p>

				<p>
				Despite the 130 year history of mycology collections here, much of this region has little or no documentation of its fungi. As of 2010, six of the twenty-four counties have no records and ten counties have 65 or fewer records. Only Cook County, Illinois, and Porter County, Indiana, can be said to be well documented. These two counties comprise 88% of all collections because of the 16 years of research in Cook County Forest Preserves and Indiana Dunes National Lakeshore. Other counties that have over 300 historical and recent collections are DeKalb, DuPage, and Lake Counties in Illinois, and Lake County, Indiana. Of course, this pattern of spotty documentation is similar for many other states.

				There are a dozen type collections of fungi for the region from seven counties. Several undescribed species await further study and publication.
				</p>

				<p>
				Most of the mushrooms and other macro-fungi in the region are thought to be native. Some of the fungi that favor urban areas, as well as cow and horse pastures, are likely introduced. In only a few cases is there enough documentation to determine the probable origin of a species. Fungi are dispersed around the world by the transport of wood, wood chips and mulch, livestock, transplanted trees, and other substrates. Because of the ephemeral nature of fungal fruitbodies, knowledge of their distribution and degree of rarity is often incomplete.  Many European countries have "Red Lists" of endangered, threatened, and special concern species. Some North America fungi are listed in the Pacific Northwest states and Minnesota.
				</p>



				<p>
				<a id="table1" name="table1"></a>
				<table cellpadding="3" cellspacing="0" border="1">
				<caption>Table 1. Facts about Chicago Region fungi.</caption>
				<thead>
				<tr ><th >Chicago Region fungi</th><th >Number and Comment</th></tr>
				</thead>
				<tbody>
				<tr><td><a href="index.html" title="Fungus Directory">Fungal taxa</a> (species, subspecies, varieties, forms)</td>
					<td align="center">approximately 1450 taxa; about 1350 species</td></tr>
				<tr><td><a href="/xsql/fungi/genlist.xsql" title="Genus Index">Genera</a> represented</td>	<td align="center">370</td></tr>
				<tr><td><a href="/xsql/fungi/famlist.xsql" title="Family Index">Families</a> represented</td>
					<td align="center">125</td></tr>
				<tr><td>Species endemic to Chicago Region (not yet documented elsewhere)</td>
					<td align="center">several, plus other Midwest endemic taxa</td></tr>
				<tr><td>Threatened or endangered taxa</td>
					<td align="center">none designated</tr>
				<tr><td>Native fungi</td>
					<td align="center">most of them</td></tr>
				<tr><td>Non-native (alien) taxa</td>
					<td align="center">unknown number, primarily urban fungi</td></tr>
				<tr><td>Invasive taxa</td>
					<td align="center">some plant diseases, such as Dutch Elm Disease</td></tr>
				<tr ><td colspan="2" >
				Also read about 
				<a href="/topics/habitats.html" 
				title="Plant communities.">habitats</a>, or 
				 <a href="/fungi/biology.html" 
				title="How fungi grow.">biology</a>, or
				<a href="/resources/biblio3.html" 
				title="Fungus references.">see recommended books</a>.
				</td></tr>
				</tbody>
				</table></p>


				<!-- can make a second table for LICHENS, or a separate page -->

            </div>
        </div>
		
		<div id="content2">

			<a href="/map.html" title="See State Map for Chicago Region."><img class="border"
			 src="<?php echo $clientRoot; ?>/images.vplants/img/map_grtlakes_250.jpg" width="250" height="212"
			alt="The vPlants Region is located within four states at the south end of Lake Michigan." /></a>

			<img src="<?php echo $clientRoot; ?>/images.vplants/feature/johndenk_250.jpg" width="250" height="376" alt="photos of different colorful mushrooms" title="mushrooms come in all colors" />
			<div class="box imgtext">
			<p>Fungi can be found in all shapes, sizes, and colors.</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>