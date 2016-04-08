<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Related Links</title>
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
            <h1>Links to Related Web Sites</h1>

            <div style="margin:20px;">
            	<p>See tabs above for more links to plants and fungi.</p>

				<h3>Herbaria of the Chicago Area:</h3>

				<p>
				The Morton Arboretum Herbarium <br />
				<a href="http://www.mortonarb.org/research/herbarium.html">http://www.mortonarb.org/research/herbarium.html</a>
				</p>

				<p>
				The Field Museum Herbarium<br />
				<a href="http://www.fieldmuseum.org/research_collections/botany/collections.htm">http://www.fieldmuseum.org/research_collections/botany/collections.htm</a><br />
				The Field Museum Botany Collections Database<br />
				<a href="http://emuweb.fieldmuseum.org/botany/Query.php">http://emuweb.fieldmuseum.org/botany/Query.php</a>
				</p>

				<p>
				The Chicago Botanic Garden Herbarium<br />
				<a href="http://www.chicagobotanic.org/research/conservation/cs_floristics.html">http://www.chicagobotanic.org/research/conservation/cs_floristics.html</a>
				</p>

				<h3>Herbaria of the Upper Midwest:</h3>

				<h4>ILLINOIS</h4>
				<p>
				The Illinois Natural History Survey (INHS) Herbarium Collection<br />
				<a href="http://www.inhs.uiuc.edu/cbd/collections/botany/botanyintro.html">http://www.inhs.uiuc.edu/cbd/collections/botany/botanyintro.html</a>
				</p>

				<p>
				 Illinois State Museum collections<br />
				 <a href="http://www.museum.state.il.us/collections/index.html">http://www.museum.state.il.us/collections/</a>
				</p>

				<h4>INDIANA</h4>
				<p>
				<br />
				</p>

				<h4>IOWA</h4>
				<p>
				<br />
				</p>

				<h4>MICHIGAN</h4>
				<p>
				<br />
				</p>

				<h4>MINNESOTA</h4>
				<p>
				University of Minnesota Herbarium, J. F. Bell Museum of Natural History<br />
				<a href="http://www.cbs.umn.edu/herbarium/">http://www.cbs.umn.edu/herbarium/</a>
				</p>

				<h4>WISCONSIN</h4>
				<p>
				Wisconsin State Herbarium, University of Wisconsin - Madison <br />
				<a href="http://botany.wisc.edu/wisflora/">http://botany.wisc.edu/wisflora/</a>
				</p>

				<p>
				Robert W. Freckmann Herbarium, University of Wisconsin - Stevens Point <br />
				<a href="http://wisplants.uwsp.edu/">http://wisplants.uwsp.edu</a>
				</p>

				<h3>Other Web Sites</h3>

				<p>The Biota of North America Program<br />
				<a href="http://www.bonap.org/">http://www.bonap.org/</a>
				</p>

				<!-- End of links -->
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>