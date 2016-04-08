<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Resources - Fungus Links</title>
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
            <h1>Fungus Links</h1>

            <div style="margin:20px;">
            	<h2>Mycology Herbaria</h2>

				<ul>
				<li><a href="http://www.fieldmuseum.org/research_collections/botany/collections_fungi.htm">The Field Museum Mycology Herbarium - Fungi</a></li>
				<li><a href="http://www.fieldmuseum.org/research_collections/botany/collections_lichens.htm">The Field Museum Mycology Herbarium - Lichens</a></li>
				<li><a href="http://fungi.umn.edu/">University of Minnesota Herbarium - Fungi</a></li>
				<li><a href="http://www.tc.umn.edu/~wetmore/Herbarium/HERBHOME.htm">University of Minnesota Herbarium - Lichens</a>
				</li>
				</ul>

				<h2>Fungal Web Sites for Upper Midwest:</h2> 

				<ul>
				<li><a href="http://www.tomvolkfungi.net">Tom Volk's Fungi, University of Wisconsin - La Crosse</a></li>
				<li><a href="http://www.mushroomexpert.com/">Michael Kuo, Eastern Illinois University</a></li>
				<li><a href="http://www.ilmyco.gen.chicago.il.us/">Illinois Mycological Association, based in Chicago</a>
				</li>
				</ul>
            </div>
        </div>
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images/vplants/feature/CLIT1GIBB.po.jpg" width="250" height="300" alt="Clitocybe gibba" title="Clitocybe gibba" />
			<p><i>Clitocybe gibba</i></p>

			<div class="box">
			<h3>Related Pages</h3>
			<ul><li><a href="docs3.php"
			   title="View or download files and working documents.">Fungus Documents</a>
			</li><li><a href="biblio3.php"
			   title="List of Books and other literature.">Fungus References</a>
			</li></ul>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>