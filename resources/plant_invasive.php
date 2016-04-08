<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Invasive Plant List</title>
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
						<h1>Chicago Region Invasive Plant List</h1>

						<div style="margin:20px;">
							<p class="large">Download, file format is Excel spreadsheet (.xls):<br> <a href="plants_invasive.xls">Invasive Plants, version, 2007-06-28 (30.5 KB)</a>
							</p>
							<p>
							This file lists plants that are invasive, or likely to become invasive in the Chicago Region, based on the collective opinion of The vPlants Project team.  The list is for educational purposes only.  It is not intended to imply any legal restrictions on the use of these species.  Sources for the list are defined below and also listed at the bottom of the file. In the file, an asterisk "*" indicates presence in that source; a "W" indicates a Watch List.
							</p>

							<h2>List Sources</h2>
							<dl class="small">
							<dt>IL ALA:</dt>

							<dd>List for Illinois from the American Lands Alliance/Faith Campbell, Worst invasive plant species in the conterminous United States (1999).</dd>

							<dt>IN ALA:</dt>
							<dd>List for Indiana from the American Lands Alliance/Faith Campbell, Worst invasive plant species in the conterminous United States (1999).</dd>

							<dt>WI ALA:</dt>
							<dd>List for Wisconsin from the American Lands Alliance/Faith Campbell, Worst invasive plant species in the conterminous United States (1999).</dd>

							<dt>MWRPTF:</dt>
							<dd>Midwest Rare Plant Task Force Invasive Species Team List (1999).</dd>

							<dt>IL DNR:</dt>
							<dd>Illinois Department of Natural Resources, 25 weeds that pose the greatest threat to Illinois forests (1994).</dd>

							<dt>INPS:</dt>
							<dd>Illinois Native Plant Society, list of 60 worst invasive plant species in Illinois (2000).</dd>

							<dt>INPAWS:</dt>
							<dd>Indiana Native Plant and Wildflower Society, 40 worst weeds in Indiana (2000).</dd>

							<dt>WI DNR: </dt>

							<dd>Wisconsin Department of Natural Resources, list of invasive species (2003).</dd>

							<dt>Midewin:</dt>
							<dd>Midewin National Tallgrass Prairie list of invasive species, * = existing problem, W = watch list.</dd>

							<dt>USFS:</dt>
							<dd>US Forest Service Eastern Region, Category 1 invasive plants (highly invasive non-native plants which invade natural habitats and replace native species) and Category 2 (moderately invasive plants).</dd>

							<dt>CW:</dt>
							<dd>Chicago Wilderness invasive species project list (2004).</dd>

							<dt>Other:</dt>
							<dd>based on field observations or other sources as noted. W = watch list.</dd>
							</dl>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<div class="box">
							<h3>Plant Documents</h3>
							<ul>
								<li><a href="docs.php">Plant Documents Main</a></li>
								<li><a href="plant_checklist.php">Taxon Checklist</a></li>
								<li><a href="plant_concern.php">Plants of Concern</a></li>
								<li><strong>Invasive Plants</strong></li>
								<li><a href="plant_terms.php">Accepted Plant Terms</a></li>
							</ul>
						</div>
						
						<div class="box external">
						<h3>Related Web Sites</h3>
						<ul><li>
						<a href="http://www.inhs.uiuc.edu/chf/outreach/VMG/VMG.html">Illinois Natural History Survey Vegetation Management Guideline</a>
						</li><li>
						<a href="http://www.nps.gov/plants/alien/">Plant Conservation Alliance - Alien Plant Working Group</a>
						</li><li>
						<a href="http://www.invasiveplants.net/">Ecology and Management of Invasive Plants Program</a>
						</li><li>
						<a href="http://www.invasive.org/">Invasive and Exotic Species of North America</a>
						</li><li>
						<a href="http://tncweeds.ucdavis.edu/">The Nature Conservancy - Global Invasive Species Initiative
						Species Team</a>
						</li><li>
						<a href="http://dnr.wi.gov/invasives/">Wisconsin Department of Natural Resources - Invasive Species</a>
						</li><li>
						<a href="http://www.ipaw.org/">Invasive Plants Association of Wisconsin</a>
						</li></ul>

						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="/disclaimer.html" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>