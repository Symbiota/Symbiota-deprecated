<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Keys to Nature</title>
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
            <h1>Keys to Nature</h1>

            <div style="margin:20px;">
            	<p class="large">
				We would like to hear from you! For any of these examples:  What features do you like? What features don't you like? Other comments?
				You can e-mail Patrick Leacock at the Field Museum:  <img src="prl_email.gif" width="212" height="18" alt="">
				</p>

				<h2>Guides and keys at selected web sites</h2>

				<p>Visit 
				<a href="http://fm2.fieldmuseum.org/chicagoguides/rcg_intro.asp"><b>Rapid Color Guides for the Chicago Region</b></a> for guides to amphibians, dragonflies, and sedges.  For other guides see this page: 
				<a href="http://fm2.fieldmuseum.org/chicagoguides/">Chicago Region Biological Guides</a>
				</p>

				<p><a href="http://wiki.cs.umb.edu/"><b>Electronic Field Guide Project</b></a> at University of Massachusetts Boston. Polytomous keys. Select a key from the home page or
				<a href="http://efg.cs.umb.edu/keys/html/index.html">this page</a>; or try out <a href="http://efg.cs.umb.edu/keys/html//OdeFamKey_html/index.html">Key to dragonflies and damselflies, Costa Rica</a> or <a href="http://efg.cs.umb.edu/nantucket/">Invasive plants of Nantucket</a>.
				</p>

				<p>
				<a href="http://www.discoverlife.org/"><b>Discover Life</b></a>. Synoptic keys.  Explore the site, or try out one of the <a href="http://pick4.pick.uga.edu/mp/20q?">IDnature guides</a>, such as <a href="http://pick4.pick.uga.edu/mp/20q?guide=Liverworts">Frullania Liverworts of the World</a> by Matt Von Konrat (Field Museum) or <a href="http://pick4.pick.uga.edu/mp/20q?guide=Trees&cl=US/IL/Cook/McDonald_Woods">Tree Identification Guide and Checklist</a> for McDonald Woods by Nyree Zerega (Chicago Botanic Garden).
				</p>

				<p>
				<a href="http://utc.usu.edu/keys/"><b>Tools for Plant Identification</b></a>, Utah State University. Synoptic Keys using Java. Also has <a href="">directed choice dichotomous keys</a>.
				</p>

				<p>
				<a href="http://www.uwgb.edu/biodiversity/herbarium/pteridophytes/pteridophytes_of_wisconsin01.htm"><b>Pteridophytes of Wisconsin:  Ferns and Fern Allies</b></a> 
				-- <a href="http://www.uwgb.edu/biodiversity/herbarium/pteridophytes/pteridophyte_key00.htm">Multi-page dichotomous key</a>, some steps have images.
				</p>

				<p>
				<a href="http://www.missouriplants.com/index.html"><b>Missouri Plants</b></a> 
				-- a picture key similar to the style of the Audubon field guides.
				</p>



				<h2>Sample prototypes at vPlants</h2>
				<p>
				<a href="http://www.vplants.org/plants/guide/growthforms.html">Key to Vascular Plants, Step 1: Growth Form.</a>
				-- an image-based top level entry point for plant identification. [demo only, not functional]
				</p>

				<p>
				<a href="http://www.vplants.org/fungi/guide/">Guide to Fungi of the Chicago Region.</a>
				-- a traditional top level entry point for fungus identification.
				</p>

				<p>
				<a href="http://www.vplants.org/fungi/guide/boletes.html">Guide to Pored Mushrooms, the Boletes.</a>
				-- a traditional dichotomous key to bolete mushrooms.
				</p>

				<p>
				<a href="http://www.vplants.org/fungi/guide/agarics_free.html">Guide to Agarics with Gills Free.</a>
				-- a traditional taxonomic overview for a group of mushrooms. Same style used again for a family <a href="http://www.vplants.org/fungi/guide/agaricaceae.html">Guide to Agaricaceae</a>.
				</p>


				<p>
				<a href="http://www.vplants.org/fungi/guide/agarics_free1.html">Key to Mushroom Genera with Free Gills.</a>
				-- a multipage polytomous key for the same group of mushrooms.
				</p>


				<p>
				<a href="http://www.vplants.org/fungi/guide/amanita_form.html">Synoptic Key to Amanita.</a>
				-- a synoptic key for a genus of mushrooms [demo, not functional].
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>