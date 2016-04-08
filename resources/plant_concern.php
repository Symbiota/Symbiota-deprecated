<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Plants of Concern List</title>
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
						<h1>Chicago Region Plants of Concern List</h1>

						<div style="margin:20px;">
							<p class="large">Download, file format is Excel spreadsheet (.xls):<br> <a href="plants_of_concern.xls">Plants of Concern, 2004 edited version, 2010-11-3 (154 KB)</a>
							</p>
							<p>
							A compiled list of taxa in the Chicago Region that are listed in Illinois, Indiana, Michigan, Wisconsin, and the Federal lists.  This compilation was created in July 2004 from the most current lists available at that time.  Taxa are listed alphabetically by family and genus according to the vPlants accepted name (marked by a "Y" in the second column).  The list includes both the vPlants accepted name (always listed first within a synonymy group) and the exact name that appears on the individual source lists.  While some updates have been done, for the most current and updated listings, please see the links to the individual state and federal lists on our <a href="/topics/concern.html"
								title="State and Federal.">Regional Lists</a> page.
							</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<div class="box">
							<h3>Plant Documents</h3>
							<ul>
								<li><a href="docs.php">Plant Documents Main</a></li>
								<li><a href="plant_checklist.php">Taxon Checklist</a></li>
								<li><strong>Plants of Concern</strong></li>
								<li><a href="plant_invasive.php">Invasive Plants</a></li>
								<li><a href="plant_terms.php">Accepted Plant Terms</a></li>
							</ul>
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