<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Help with Specimens</title>
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
            <h1>Help with Specimens</h1>

            <div style="margin:20px;">
            	<p>
				All specimen records indicate two scientific names at the top of the detailed specimen page. The first name indicated is the vPlants accepted name (see below).  The second name listed is the name under which that specimen is filed in the institution's herbarium.  Often these two names are the same, but in many cases they are different because each herbarium has its own rules for filing specimens and its own criteria for assigning names to specimens.
				</p><p>
				The vPlants accepted name is the particular Latin name that the vPlants partners have agreed to use when writing the description for that taxon.  Commonly used synonyms are grouped together under this single name. Due to the complexity of the application of scientific names, the vPlants project developed a checklist of Latin names that are commonly used for the region's plants (see <a  href="/resources/plant_checklist.html">Taxon Checklist for Plants</a> for more information).
				</p><p>
				Due to the limited nature of data recorded on many specimen labels, several rules were developed at each institution to deal with incomplete locality data and the consequential decision to include or exclude the specimen from the dataset.  See the following detailed explanations.
				</p><p>
				Specimens that only have a state indicated on the label are normally included in the dataset since we could not discern whether the specimens were collected inside or outside of the Chicago Region.  We chose to err on the side of caution rather than eliminate specimens that may have been collected in the area.  The only exceptions to this general inclusion were for specimens in taxa that have never been reported in the area and also were outside of the typical distribution range for that taxon.
				</p><p>
				Similarly, there are specimens included in the dataset that may appear to be from localities outside of the Chicago Region.  However, since there are several cities, towns, and townships with the same name that occur in many counties in a particular state, unless the county is specified on the label, we cannot assume which locality is indicated by the label.  In such cases, we again erred on the side of caution and included the specimens rather than eliminate specimens that may actually have been collected in the Chicago Region.
				</p><p>
				By default we place a locality name in city / town unless it is specifically indicated as a Township on the label.  The Advanced Search combines these two separate fields into a single search box for users.  The specimen detail page will separate the fields into a city / town field or township field if the specific data were indicated on the label.  Otherwise the data are placed in the city / town field.
				</p><p>
				Many records from the Field Museum's collections have data that are displayed in square brackets [ ].  This notation indicates that the data inside the brackets were not shown on the specimen label, but are an editorial addition.  In some cases the added data are clearly obvious, such as adding Cook County when the locality is indicated as Chicago, Illinois.  In other instances, the bracketed data are a correction of the label data (e.g. many Bensenville collections record the county as Cook on the labels, but this locality is in DuPage County).  Sometimes the bracketed data are translations from abbreviations.  In all cases, the data displayed in brackets were added by the institution that houses the specimen.
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>