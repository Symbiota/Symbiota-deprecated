<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> About Us - The vPlants Project</title>
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

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>
					<h1>The vPlants Project</h1>
					<div style="margin:20px;">
            	<p><a href="http://www.mortonarb.org/">The Morton Arboretum</a>, the <a href="http://www.fieldmuseum.org/">Field Museum of Natural History</a>, and the <a href="http://www.chicagobotanic.org/">Chicago Botanic Garden</a> developed vPlants (“virtual Plants”) as an online, searchable database to provide free web access to data and digital images of plant specimens collected in the Chicago Region. The project began in January 2001 and was initially funded by the <a href="http://www.imls.gov/">Institute of Museum and Library Services</a>. <a href="http://www.chicagowilderness.org/">Chicago Wilderness</a> and the Newman Family Fund also provided support for the project.
				</p>
				<p>vPlants was built using XML-based software that allowed these three founding institutions to pool data from their disparate hardware and database systems. Information housed at each institution was transferred, using XML, into a single web-searchable database at the vPlants portal. At the time it was a nifty system.
				</p>
				<p>In the ensuing years, some of the elements of the vPlants system did not age well. Although the XML-based elements continued to provide very fast database searches, some of the software pieces that connected the system to the web could not be upgraded. This frustrated our users, who found our search engine increasingly unresponsive over time.
				</p>
				<p>In 2015, we moved the vPlants data and static pages to the well tested <a href="http://www.symbiota.org/">Symbiota</a> platform  (<a href="http://www.ncbi.nlm.nih.gov/pubmed/25057252">Gries et al. 2014</a>).
				</p>
				<p>The original vPlants data pool allowed users to search data from 80,000 plant specimens housed in the herbaria of each of the three founding institutions. The data pool also contained digital images for almost 50,000 of those specimens. By associating ourselves with the much larger network of herbaria that use Symbiota and contribute data via <a href="http://swbiodiversity.org/seinet/projects/index.php">SEINet</a>, users of our new and improved vPlants web portal can search data from over 120,000 plant specimens and have access to new tools.
				</p>
				<p>We hope that our participation in this Symbiota-based network is a significant step towards building a larger online portal for plants found throughout the Western Great Lakes Region.
				</p>
				</div>
					</div>
					</div>
		
					<!-- start of side content -->
					<div id="content2">
						<!-- any image width should be 250 pixels -->

						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/herb_south.jpg" width="250" height="225" alt="Older gray metal herbarium cabinets, placed end to end in rows.">
						<p>Cabinets in the old south herbarium of the Field Museum.</p>

						<div class="box">
						 <h3>Features in production</h3>
						 <p>
						  <a href="/news/" 
						   title="See description pages and more."><img src="<?php echo $clientRoot; ?>/images/vplants/feature/prototype_210.jpg" width="210" height="291" alt="Thumbnail image of prototype description page."></a>
						 </p>
						<ul><li>

						</li></ul>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div>
			</div>
		</div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>