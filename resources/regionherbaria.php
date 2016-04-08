<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Resources - Region Herbaria</title>
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
		<!-- start of inner text and right side content -->
		<div  id="innervplantstext">
			<div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>
						<h1>Herbaria of Chicago and the Upper Midwest</h1>

						<div style="margin:20px;">

							<h3>Illinois</h3>
							 <ul>
							<li><a href="http://www.chicagobotanic.org/research/conservation/regional_floristics/">Chicago Botanic Garden Herbarium</a></li>
							<li><a href="http://www.fieldmuseum.org/research_collections/botany/collections.htm">The Field Museum Herbarium</a> --
							<a href="http://emuweb.fieldmuseum.org/botany/Query.php">Botany Collections Database</a></li>
							<li><a href="http://systematics.mortonarb.org/lab/herbarium.html">The Morton Arboretum Herbarium</a> -- <a href="http://quercus.mortonarb.org">Integrated Plants Database</a></li>
							 <li><a href="http://www.inhs.uiuc.edu/cbd/collections/botany/botanyintro.html">Illinois Natural History Survey (INHS) Herbarium Collection</a></li>
							 <li><a href="http://www.museum.state.il.us/collections/index.html">Illinois State Museum Collections</a></li>
							 </ul>

							<h3>Indiana</h3>
							 <ul>
							 <li><a href="http://www.butler.edu/herbarium/">Friesner Herbarium, Butler University </a></li>
							 <li><a href="http://www.bio.indiana.edu/resources/herbarium/">Indiana University Herbarium</a></li>
							 <li><a href="http://www.btny.purdue.edu/Herbaria/">Purdue University Herbarium</a></li>
							 </ul>


							<h3>Iowa</h3>
							 <ul>
							 <li><a href="http://web.grinnell.edu/individuals/eckhart/herbarium.html">Grinnell College Herbarium</a></li>
							 <li><a href="http://www.public.iastate.edu/%7Eherbarium/">Iowa State University Herbarium<!-- acquired U. of Iowa in 2004 --></a></li>
							 <li><a href="http://www.cgrer.uiowa.edu/herbarium/FragileFlora.htm">University of Iowa Herbarium</a> (herbarium was transferred to Iowa State)</li>
							 </ul>

							<h3>Michigan</h3>
							 <ul>
							 <li><a href="http://herbarium.msu.edu/">Michigan State University Herbarium</a></li>
							 <li><a href="http://herbarium.lsa.umich.edu/">University of Michigan Herbarium</a></li>
							 </ul>


							<h3>Minnesota</h3>
							 <ul>
							 <li><a href="http://www.cbs.umn.edu/herbarium/">University of Minnesota Herbarium, J. F. Bell Museum of Natural History</a></li>
							 </ul>


							<h3>Wisconsin</h3>
							 <ul>
							 <li><a href="http://botany.wisc.edu/wisflora/">Wisconsin State Herbarium, University of Wisconsin - Madison</a></li>
							<li><a href="http://wisplants.uwsp.edu/">Robert W. Freckmann Herbarium, University of Wisconsin - Stevens Point</a></li>
							<li><a href="http://www.uwgb.edu/biodiversity/herbarium/">Herbarium, Cofrin Center for Biodiversity, University of Wisconsin - Green Bay</a></li>
							 </ul>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
       
		
					<div id="content2">

						<div class="box document">
						<h3>vPlants Topics</h3>
						<ul><li>
						<a href="/topics/index.html" 
						title="What is an herbarium?">Herbarium Collections</a>
						</li></ul>
						</div>

						<div class="box external">
						<h3>Related Web Sites</h3>
						<h4>Other Online Resources</h4>
						<ul>
						<li>
						<a href="http://www.bonap.org/">The Biota of North America Program</a>
						</li>
						</ul>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>