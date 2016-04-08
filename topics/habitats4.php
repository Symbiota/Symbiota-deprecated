<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Wetlands</title>
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
            <h1>Wetlands of the Chicago Region</h1>

            <div style="margin:20px;">
            	<!-- Chicago Wilderness definitions -->
				<!-- definitions plus emergent-Typha, floating-Lemna, submersed-Myriophyllum, anchored-floating-Nymphaea, etc., wetland families -->

				<p>
				There are many different types of wetlands found in the Chicago Region. Some examples include bogs, fens, marshes, pannes, ponds, and swamps (see list below).  Many of our plants grow only in or around wetlands, yet wetlands are at high risk from human impacts such as drainage, pollution, and invasion by alien plants.
				</p>


				<ul>
				<li>
				It is estimated that between 650 - 800 species (about 25%) of plants  in the Chicago Region would be found in wetlands more than 66% of the time.
				</li>
				<li>
				Close to 40% of the area's native plants are considered wetland species.
				</li>
				<li>
				The rarest plant of our area, the endemic and possibly extinct <i>Thismia americana</i>, is a wetland species.
				</li>
				</ul>

				<p>
				View the wetland indicator status for species in the area (found in the North Central Region) and other information at the United States Fish and Wildlife Service:
				<a href="http://www.nwi.fws.gov/bha/list96.html">National Wetland Inventory page [external link]</a>.
				</p>
				
				

				<h3>An Exemplar Wetland in the Chicago Region</h3>

				<p>
				The Cowles Bog Wetland Complex, located in Porter County, Indiana, and owned and managed by the National Park Service, is one of several rich wetland areas in the Chicago Region.  Though detrimentally altered by human impact during the Twentieth Century, this wetland area is listed as a National Natural Landmark.  It still encompasses several types of wetlands including: a forested swamp with yellow birch, red maple, and a rich understory of ferns and small flowering plants; expansive sedge meadow and fen areas; and a mounded, floating bog-like area that supports one of the few surviving populations of eastern white cedar, <i>Thuja occidentalis</i>, in the Chicago Region. This wetland area and the northern-bordering dune forests and active dunes of the Lake Michigan shore is the place where Henry C. Cowles, the originator of the ecological theory of succession, carried out his monumental research in the late 1890's. Today, the National Park Service is involved in restoring sections of this wetland to more natural conditions. Much of the work involves removing invasive plants such as cattail, reintroducing native sedges, and analyzing the current hydrology (water levels).
				</p>


				<h3>Wetland Types</h3>

				<dl>

				<dt>Bog</dt>
				<dd>&#151; A wetland, usually peaty, in which the substrate is typically acid (pH &lt; 7). In the strict sense, the only source of water and nutrients for a bog is rainfall; there is no recharge from the surrounding ground water. Thus a bog is nutrient poor.</dd>

				<dt>Fen, Calcareous fen</dt>
				<dd>&#151; A general term used in reference to habitats which are calcareous in nature (pH &gt; 7) and which are fed throughout the year by a flow of water at or just beneath the surface.
				Many fens are erroneously called bogs but are separated by having the influx of nutrients and water from sources in addition to rainfall.
				</dd>

				<dt>Lake</dt>
				<dd>&#151; An inland, open body of water that is relatively large.</dd>

				<dt>Marsh</dt>
				<dd>&#151; A wet or periodically wet area with mineral soil and predominantly herbaceous plants. One example is a cattail marsh.</dd>

				<dt>Panne</dt>
				<dd>&#151; Typically, a moist interdunal depression, often scoured down to the water table, in calcareous sands on the lee sides of dunes near Lake Michigan &#151; the vegetation quite fen-like in composition.</dd>

				<dt>Pond</dt>
				<dd>&#151; An inland, open body of water that is relatively small. Ephemeral ponds are dry for part of the year.</dd>

				<dt>River, Stream</dt>
				<dd>&#151; A body of running water.</dd>

				<dt>Sedge meadow</dt>
				<dd>&#151; grass-like forbs (sedges, grasses, rushes) occurring in relatively moist area.</dd>

				<dt>Swamp</dt>
				<dd>&#151; A wet or periodically wet area with mineral soil and predominantly woody plants. Types include shrub swamp and forested swamp.</dd>

				</dl>

            </div>
        </div>
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images/vplants/feature/cowles.jpg" width="250" height="195" alt="Cowles Bog" />
			<div class="box imgtext">
			<p>
			An early spring view of forested swamp, at the Cowles Bog Wetland Complex in the Indiana Dunes National Lakeshore, Porter, Indiana
			</p>
			</div>


			<div class="box">
			<h3>Related Web Sites</h3>
			<ul>
			<li>
			<a href="http://www.glhabitat.org/news/glnews215.html">Restoration efforts at Cowles Bog: Great Lakes Aquatic Habitat network newsletter, April 2002</a>
			</li>
			<li>
			<a href="http://www.chicagowildernessmag.org/issues/spring2003/news/cowlesbog.html">News on Cowles Bog: Chicago Wilderness Magazine, Spring, 2003</a>
			</li>
			<li>
			<a href="http://www.chicagowildernessmag.org/issues/fall1998/IWcowlesbog.html">General information on Cowles Bog: Chicago Wilderness Magazine, Fall 1998</a>
			</li>
			<li>
			<a href="http://www.epa.gov/glnpo/fund/2000/guidreview/106.pdf">PDF document of original restoration proposal</a>
			</li>
			<li>
			<a href="http://memory.loc.gov/ammem/award97/icuhtml/aepsp4.html">Library of Congress page on Henry C. Cowles</a> 
			</li>
			</ul>
			</div>


			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>