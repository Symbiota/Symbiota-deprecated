<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants Scientific Name Checklist</title>
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
            <h1>vPlants Scientific Name Checklist</h1>
			<div style="margin:20px;">
            	<p class="large">Download, file format is Excel XLS:<br /> <a href="plants_checklist_v4_6.xls">vPlants Scientific Name Checklist, version 4.6, 2006-12-12 (2.1 MB)</a>
				</p>

				<h3>Origin and explanation of taxon checklist</h3>
				<p>
				The vPlants Scientific Name Checklist contains the names and commonly used synonyms for the vascular plant taxa found in the Chicago Region. 
				</p><p>
				In 2001, the checklist of taxon names for vPlants was originally created by taking the published list of taxa reported in Plants of the Chicago Region, 4th Edition by F. Swink and G. Wilhelm (Indiana Academy of Science, 1994) and overlaying the name codes and synonymy data for those taxa as presented in the United States Department of Agriculture (USDA) on-line PLANTS Database (<a href="http://plants.usda.gov/" title="External link.">http://plants.usda.gov/</a>, external link).  
				</p><p>
				Later updates to this initial list were made in which new taxon names were added (and correspondingly new name codes were created) as based on the specimen data from the three institutions involved in vPlants.  The concept of an "accepted name" was originally followed as provided in the USDA list.  Despite a name being "accepted" by USDA, this did not preclude the usage of other synonyms by each of the three partner herbaria.  Accordingly, for all specimen records in vPlants, the name under which the specimen is filed at the institution is listed in addition to an "accepted name".  
				</p><p>
				In July 2004, the entire checklist was overhauled in order to correct synonymy alignments and other problems such as erroneous authorities or spelling errors.  The basis for much of the synonymy and accepted name changes came from the published volumes of the Flora of North America Project (<a href="http://www.fnh.org/FNA/" title="External link.">www.fnh.org/FNA/</a>, external link).  With this update, a new "accepted name" field was created, the "vPlants accepted name".  In many instances, this accepted name is not the same as the USDA's accepted name.  
				</p><p>
				Again, there are still cases where not all three partner institutions file their specimens under this "vPlants accepted name" and thus the name under which specimens are filed is still given on all specimen record pages.  For the new "species description pages", the vPlants accepted name will be used in all cases, but all synonyms from the checklist are also listed on the page.</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>