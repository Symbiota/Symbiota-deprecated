<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Plant Taxon Checklist</title>
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
						<h1>Taxon Checklist for Plants</h1>

						<div style="margin:20px;">
							<h2 >vPlants Scientific Name Checklist</h2>

							<p class="large">Download, file format is Excel spreadsheet (.xls):<br> <a href="plants_checklist_v5_8.xls">vPlants Scientific Name Checklist, version 5.8, 2011-2-3 (2.2 MB)</a>
							</p>

							<h3>Origin and explanation of taxon checklist</h3>
							<p>
							The vPlants Scientific Name Checklist contains the names and commonly used synonyms for the vascular plant taxa found in the Chicago Region. 
							</p><p>

							In 2001, the checklist of taxon names for vPlants was originally created by taking the published list of taxa reported in <i>Plants of the Chicago Region</i>, 4th Edition by F. Swink and G. Wilhelm (Indiana Academy of Science, 1994) and overlaying the name codes and synonymy data for those taxa as presented in the United States Department of Agriculture (USDA) on-line PLANTS Database (<a href="http://plants.usda.gov/" title="External link.">http://plants.usda.gov/</a>, external link).  Note, the USDA PLANTS Database has since undergone updates, but our data is still based on the data as it existed in 2001.  We have made no attempts to keep our data aligned to the current version of the PLANTS database. Accordingly, if the PLANTS database has changed a name code for a taxon, we cannot promise that our listing of a USDA PLANTS name code is correct in the downloadable checklist.
							</p><p>
							Based on the specimen data from the initial three institutions involved in vPlants, later updates to this initial list were made (new taxon names were added, and correspondingly new name codes were created) .  The concept of an "accepted name" was originally followed as provided in the USDA PLANTS list.  Despite a name being "accepted" by USDA, this did not preclude the usage of other synonyms by each of the three partner herbaria.  Accordingly, for all specimen records in vPlants, the name under which a specimen is filed at the institution is listed in addition to the "accepted name".  
							</p><p>
							Beginning in July 2004, the entire checklist was overhauled in order to correct synonymy alignments and other problems such as erroneous authorities or spelling errors.  The basis for much of the synonymy and accepted name changes came from the published volumes of the Flora of North America Project (<a href="http://www.efloras.org/flora_page.aspx?flora_id=1" title="External link.">as available through eFloras.org</a>, external link).  With this update, a new "accepted name" field was created, the "vPlants accepted name".  In many instances, this accepted name is not the same as the USDA PLANTS accepted name. Changes and updates to the vPlants checklist continue as new names are required to be added, accepted status changes, and other items are addressed.  
							</p><p>
							Again, there are still cases where not all partner institutions file their specimens under this "vPlants accepted name", and thus the name under which specimens are filed is still given on all specimen record pages.  For the "species description pages", the vPlants accepted name is used in all cases, but all synonyms from the checklist are also listed on the page.</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<div class="box">
							<h3>Plant Documents</h3>
							<ul>
								<li><a href="docs.php">Plant Documents Main</a></li>
								<li><strong>Taxon Checklist</strong></li>
								<li><a href="plant_concern.php">Plants of Concern</a></li>
								<li><a href="plant_invasive.php">Invasive Plants</a></li>
								<li><a href="plant_terms.php">Accepted Plant Terms</a></li>
							</ul>
						</div>
						
						<div class="box">
						<h3>Related Pages</h3>
						<ul><li><a href="../plants/index.html">Plant Directory.</a>
						<br>Explanation of plants included in vPlants.
						</li><li><a href="../checklists/checklist.php?cl=3503&pid=93" 
						title="Index of species">Species Index.</a>
						<br>The Species Index is an alphabetical listing of the vascular plant genera found in the Chicago Region that link to information about each species.

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