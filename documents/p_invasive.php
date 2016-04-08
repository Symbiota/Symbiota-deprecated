<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Chicago Region Invasive Plant List</title>
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
            <h1>Chicago Region Invasive Plant List</h1>
			<div style="margin:20px;">
            	<p class="large">Download, file format is Excel XLS:<br /> <a href="plants_invasive.xls">Invasive Plants, version, 2005-11-15 (31 KB)</a>
				</p>
				<p>
				This file lists plants that are invasive, or likely to become invasive in the Chicago Region, based on the collective opinion of The vPlants Project team.  The list is for educational purposes only.  It is not intended to imply any legal restrictions on the use of these species.  Sources for the list are defined below and also listed at the bottom of the file. In the file, an asterisk "*" indicates presence in that source; a "W" indicates a Watch List.
				</p>

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
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>