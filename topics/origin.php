<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Native Plants</title>
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
            <h1>Origin</h1>

            <div style="margin:20px;">
            	<p>Plants, fungi, and other organisms have different <a href="distribution.php">distribution</a> patterns and ranges. In a particular place, such as the Chicago Region, the life that occurs there can be placed in several categories based on origin and lifestyle:
				<dl><dt>Native species</dt><dd> are those that we believe naturally occurred in an area for a long time before European settlers arrived. They have evolved with their surrounding ecosystem.</dd>
				<dt>Non-native species</dt><dd> or alien species are those that are originally from somewhere outside the area, and have been introduced by humans either purposely or accidentally. There are many examples of non-native plants, animals, and pathogens introduced to various parts of the world. These introductions can be very dangerous because they can often spread widely since they lack natural predators in the new area, and they also can modify the area in such a way as to decrease the survival efficiency of native species.</dd>
				<dt>Invasive species</dt><dd> are non-native, aggressive species that out-compete native species for space and resources. Invasive species are incredibly threatening to natural communities because they usually spread quickly, modify the environment to their advantage (and the detriment of native species), and drive out native species while depleting ecosystems of their natural resources.</dd></dl>
				</p>

				<h2>Native Plants of the Chicago Region</h2>
				<p>Native plants are adapted to this region and optimized for the specific conditions of the area.  They succeed in areas where the natural habitat is still intact.  While it is sometimes straightforward to classify a particular plant as being native or non-native, it can often be a challenge to decide on the "natural" distribution of a species.  For this project, with few exceptions, we have agreed with the designations made by Swink and Wilhelm (1994).  The assigned status of a particular taxon is indicated on the Description pages under the section "Regional occurrence."  See <a href="../taxa/index.php?taxon=Campanula%20americana" title="description page"><i>Campanula americana</i></a> for an example of a native plant species.</p>


				<h2>Native, non-native, and invasive fungi</h2>
				<p>There is very little known about the pre-settlement distribution of fungi for the Chicago Region and much of North America. Whether a particular fungus species is native or not to a region is speculation and most are assumed to be native. In some cases, particularly with plant diseases, such as Dutch Elm Disease, and other microfungi, we know that these fungi are introduced from other parts of the world. Currently, vPlants is only including macrofungi (mushrooms, brackets, and other large fungi); none of these are listed as invasive species. Recent observations in Illinois suggest that at least one mushroom species (<i>Amanita thiersii</i>) may be extending its range northward or becoming more common with climate change.</p>
            </div>
        </div>
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images/vplants/feature/CAAM6.jpg" width="250" height="321" alt="Blue flowers" title="Campanula americana">
			<div class="box imgtext">
			<p>The tall bellflower, <i>Campanula americana</i> is one of the 1650 native plants in the region.
			</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>