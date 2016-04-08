<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants Documents for Plants</title>
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
            <h1>vPlants Documents for Plants</h1>
			<div style="margin:20px;">
            	<p>Links to web pages and documents.</p>

				<h3>1. <a href="p_checklist.html" 
				title="Scientific Name Checklist.">Taxon Checklist</a></h3>
				<p>
				Web page with download: vPlants Scientific Name Checklist of all plant species, subspecies and varieties included in vPlants.
				</p>

				<h3>2. <a href="p_concern.html" 
				title="Chicago Region Plants of Concern.">Plants of Concern</a></h3>
				<p>
				Web page with download: All Chicago Region vascular plants currently listed as endangered, threatened, rare, etc.
				</p>

				<h3>3. <a href="p_invasive.html" 
				title="Chicago Region Invasive Plants.">Invasive Plants</a></h3>
				<p>
				Web page with download: All non-native (alien) vascular plants that are considered invasive in the Chicago Region.
				</p>

				<h3>4. <a href="p_terms.html" 
				title="vPlants Accepted Plant Terms.">Accepted Plant Terms</a></h3>
				<p>
				Web page with download: A list of the plant terms that are acceptable for use in the vPlants species descriptions.
				</p>

				<h1>Other Documents for Plants</h1>

				<p>Links to external web pages and documents.</p>

				<h3><a href="http://www.nwi.fws.gov/bha/list96.html" 
				title="External link.">USFWS Wetland Taxa</a></h3>
				<p>
				External web page: United States Fish and Wildlife Service information on wetland plants.
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>