<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Fungus Links</title>
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
            <h1>Links to Fungus Web Sites</h1>

            <div style="margin:20px;">
            	<p>See also general links on the <a href="links.html">Related Links Page</a>.</p>

				<h3>Mycology Herbaria:</h3>
				<p>
				The Field Museum Mycology Herbarium - Fungi:<br />
				<a href="http://www.fieldmuseum.org/research_collections/botany/collections_fungi.htm">http://www.fieldmuseum.org/research_collections/botany/collections_fungi.htm</a><br />
				The Field Museum Mycology Herbarium - Lichens:<br />
				<a href="http://www.fieldmuseum.org/research_collections/botany/collections_lichens.htm">http://www.fieldmuseum.org/research_collections/botany/collections_lichens.htm</a>
				</p>
				<p>
				University of Minnesota Herbarium Fungal Collection: <br />
				<a href="http://fungi.umn.edu/">http://fungi.umn.edu/</a><br />
				University of Minnesota Herbarium Lichen Collection: <br />
				<a href="http://www.tc.umn.edu/~wetmore/Herbarium/HERBHOME.htm">http://www.tc.umn.edu/~wetmore/Herbarium/HERBHOME.htm</a>
				</p>

				<h3>Fungal Web Sites for Upper Midwest:</h3> 

				<p>
				Tom Volk's Fungi, University of Wisconsin - La Crosse:<br />
				<a href="http://www.tomvolkfungi.net">http://www.tomvolkfungi.net</a>
				</p>
				<p>
				Michael Kuo, Eastern Illinois University:<br />
				<a href="http://www.bluewillowpages.com/mushroomexpert/">http://www.bluewillowpages.com/mushroomexpert/</a>
				</p>
				<p>
				Illinois Mycological Association, based in Chicago:<br />
				<a href="http://www.ilmyco.gen.chicago.il.us/">http://www.ilmyco.gen.chicago.il.us/</a>
				</p>

				<p>
				More links to be added.....
				</p>
				<!-- End of links -->
            </div>
        </div>
		
		<div id="content2"><!-- start of side content -->
			<p class="hide">
			<a id="secondary" name="secondary"></a>
			<a href="#sitemenu">Skip to site menu.</a>
			</p>

			<!-- image width is 250 pixels -->
			<img src="<?php echo $clientRoot; ?>/images.vplants/feature/CLIT1GIBB.po.jpg" width="250" height="300" 
			 alt="Clitocybe gibba" title="Clitocybe gibba" />

			<div class="box">
			<p>
			<em>Clitocybe gibba</em>
			</p>
			</div>

			<p class="small">
			Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p>
			<p class="small">
			<a class="popup" href="/disclaimer.html" 
			title="Read Disclaimer [opens new window]." 
			onclick="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;" 
			onkeypress="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;">Disclaimer</a>
			</p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>