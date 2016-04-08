<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Disjuncts</title>
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
            <div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->
					<div id="content1wrap"><!--  for content1 only -->
						<div id="content1">
							<h1>Disjunct Distributions and Range Extremes</h1>

							<div style="margin:20px;">
								<dl><dt>Disjunct</dt>
								<dd>&#151; discontinuous or separated.</dd>
								</dl>

								<p>The Chicago Region is an area where floristic zones come together from all directions.  Here the eastern deciduous forests meet the prairies and savannas from the west.  Likewise, the northern and boreal habitats of bogs and swamp forests mesh with disjunct Atlantic coastal plain zones.  There are many populations of vascular plant species found in the Chicago Region that are far-separated from their next closest populations.  Their location seems out of place in terms of geography, but when the geologic history is taken into account, the biogeographic patterns make sense.</p>

								<dl><dt>Disjunct Distributions</dt>
								<dd>&#151; Distributions of species are considered disjunct when a portion of the area where the species occurs is far-separated from the majority of the area the species occupies.</dd>
								</dl>

								<p>
								There is a major disjunct trend of the Atlantic Coastal Plain that appears in the western Great Lakes.  There are discontinuous populations of plants (and invertebrate animals) that typically occur along the eastern and southeastern coast of the United States that are found in these inland areas (although coastal in respect to the Great Lakes).
								</p>

								<dl><dt>Distribution Range Extremes</dt>
								<dd>&#151; A species range extreme occurs at the edge of a distribution area, normally in a somewhat different floristic zone compared to the central distribution area.</dd>
								</dl>

								<p>
								There are several species in the Chicago Region that exist at the farthest part of their range.  Some are at the southern-most extent from the Boreal Forests of the north.  Other species are the most eastern representatives of the Great Plains of the west.  Still others are at the most west edge of the deciduous forests of the east.
								</p>

								<h3>Localization in the Dune Areas</h3>

								<p>
								Many of the Chicago Region disjunct species and those at the extremes of their range occur in the dune areas close to Lake Michigan.  Some examples of species which have their typical ranges nested in the Atlantic Coastal Plain, the northern Boreal Forests, or the Western Great Plains are listed at the right.
								</p>
							</div>
						</div>
					</div>
					<div id="content2">

						<div class="box">
						<h3>Atlantic Coastal Plain Disjunct Plants</h3>
						<p>
						<a href="../taxa/index.php?taxon=Eleocharis%20melanocarpa"><i>Eleocharis melanocarpa</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Rhexia%20virginica"><i>Rhexia virginica</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Rhynchospora%20macrostachya"><i>Rhynchospora macrostachya</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Sabatia%20campanulata"><i>Sabatia campanulata</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Utricularia%20radiata"><i>Utricularia radiata</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Utricularia%20subulata"><i>Utricularia subulata</i></a>
						</p>
						</div>

						<div class="box">
						<h3>Boreal Plants</h3>
						<p>
						<a href="../taxa/index.php?taxon=Arctostaphylos%20uva-ursi"><i>Arctostaphylos uva-ursi</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Callitriche%20verna"><i>Callitriche verna</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Carex%20limosa"><i>Carex limosa</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Cornus%20canadensis"><i>Cornus canadensis</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Lycopodiella%20inundata"><i>Lycopodiella inundata</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Pinus%20banksiana"><i>Pinus banksiana</i></a>
						</p>
						</div>

						<div class="box">
						<h3>Western Plains Plants</h3>
						<p>
						<a href="../taxa/index.php?taxon=Lactuca%20ludoviciana"><i>Lactuca ludoviciana</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Lithospermum%20incisum"><i>Lithospermum incisum</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Opuntia%20macrorhiza"><i>Opuntia macrorhiza</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Ranunculus%20rhomboideus"><i>Ranunculus rhomboideus</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Sabatia%20campestris"><i>Sabatia campestris</i></a>
						</p><p>
						<a href="../taxa/index.php?taxon=Sisyrinchium%20campestre"><i>Sisyrinchium campestre</i></a>
						</p>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div>
			</div>
		</div>
	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>