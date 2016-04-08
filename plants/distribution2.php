<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>About Plants - Distribution - Endemics</title>
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
						<h1>Endemics</h1>

						<div style="margin:20px;">
							<h2>What is found here and nowhere else?</h2>

							<dl><dt>Endemic</dt>
							<dd>&#151; The term endemic means that something is restricted to a locality or region.  In biology, endemism refers to species that have evolved to the specific conditions of their habitats in a particular area, and consequently do not exist naturally anywhere else in the world but that locality of origin.</dd>
							</dl>

							<h2>Endemic plants of the Chicago Region</h2>

							<p>
							In addition to having a high diversity of vascular plant species, the Chicago Region has two endemic species of plants: 
							</p>

							<h3><i>Thismia americana</i> (Burmanniaceae)</h3>

							<p>Most other members of the genus <i>Thismia</i> occur in either Malesian or South American tropical rainforests.  There are a few temperate species of <i>Thismia</i>, but they occur on the other side of the world in Japan, Taiwan, and Australia.  This incredibly small saprophytic plant was first discovered in a wet prairie near Lake Calumet (Cook county, Illinois) in 1912 and has not been seen again since 1916.</p>

							<h3><i>Iliamna remota</i> (Malvaceae)</h3>

							<p>A member of the Hibiscus family, the Kankakee mallow is a bushy perennial herb with showy, rose to pink-purple flowers. It grows naturally only on an island in the Kankakee River near the town of Altorf (Kankakee County, Illinois).  However, some very similar plants from a mountain top population in Virginia are sometimes placed in this same species.</p>

							<h2>Endemic plants of the Great Lakes area</h2>

							<p>
							Aside from these Chicago Region endemic species, the area also supports populations of several Great Lakes endemic plant taxa (species, subspecies, or variety).  Again these plants have evolved with the physical landscape and can only be found in a limited range surrounding the Great Lakes of North America.  Most of these species occur in dune habitats.  In the Chicago Region, the Great Lakes endemic plant taxa include <i>Cirsium pitcheri</i> (Asteraceae), <i>Hypericum kalmianum</i> (Clusiaceae), <i>Iris lacustris</i> (Iridaceae), <i>Cakile edentula</i> var. <i>lacustris</i> (Brassicaceae), and <i>Solidago simplex</i> ssp. <i>randii</i> var. <i>gillmanii</i> (Asteraceae).
							</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<div class="box">
							<h3>Distribution of Plants</h3>
							<ul>
								<li><a href="distribution.php">Plant Distribution Main</a></li>
								<li><strong>Endemics</strong></li>
								<li><a href="distribution3.php">Disjuncts</a></li>
							</ul>
						</div>
						
						<div class="box document">
						<h3>Chicago Region Endemics</h3>
						<ul><li>
						<a href="../taxa/index.php?taxon=Thismia%20americana"><i>Thismia americana</i></a>
						</li><li>
						<a href="../taxa/index.php?taxon=Iliamna%20remota"><i>Iliamna remota</i></a>
						</li><li>
						</div>

						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/V0030621F.jpg" width="250" height="361" alt="1912 specimen of Kankakee Mallow.">
						<div class="box imgtext">
						<p>Specimen of the endemic Kankakee mallow, <i>Iliamna remota</i>, collected by E. J. Hill in 1912.
						</p></div>

						<div class="box">
						<h3>Great Lakes Endemics</h3>
						<ul><li>
						<a href="../taxa/index.php?taxon=Cirsium%20pitcheri"><i>Cirsium pitcheri</i></a>
						</li><li>
						<a href="../taxa/index.php?taxon=Hypericum%20kalmianum"><i>Hypericum kalmianum</i></a>
						</li><li>
						<a href="../taxa/index.php?taxon=Iris%20lacustris"><i>Iris lacustris</i></a>
						</li><li>
						<a href="../taxa/index.php?taxon=Cakile%20edentula%20var.%20lacustris"><i>Cakile edentula</i> var. <i>lacustris</i></a>
						</li><li>
						<a href="../taxa/index.php?taxon=Solidago%20simplex%20var.%20gillmanii"><i>Solidago simplex</i> ssp. <i>randii</i> var. <i>gillmanii</i></a>
						</li><li>
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