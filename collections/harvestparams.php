<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$collManager = new OccurrenceManager();
$collArray = $collManager->getSearchTerms();
$collManager->reset();
?>

<html>
<head>
    <title><?php echo $defaultTitle; ?> Collection Search Parameters</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/collections.harvestparams.js?var=1303"></script>
</head>
<body>

<?php
	$displayLeftMenu = (isset($collections_harvestparamsMenu)?$collections_harvestparamsMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_harvestparamsCrumbs)){
		if($collections_harvestparamsCrumbs){
			echo "<div class='navpath'>";
			echo $collections_harvestparamsCrumbs.' &gt;&gt; ';
			echo "<b>Search Criteria</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt;
			<a href='index.php'>Collections</a> &gt;&gt;
			<b>Search Criteria</b>
		</div>
		<?php
	}
	?>

	<div id="innertext">
		<h1>Select Search Parameters</h1>
		Fill in one or more of the following query criteria and click 'Search' to view your results.

		<form name="harvestparams" id="harvestparams" action="list.php" method="get" onsubmit="return checkForm()">
			<div style="margin:10 0 10 0;"><hr></div>
			<div style='float:right;margin:10px;'>
				<input style="border: 1px solid gray;" type="image" name="display1" id="display1" class="hoverHand" src='../images/search.gif'
					onmouseover="javascript: this.src = '../images/search_rollover.gif';"
					onmouseout="javascript: this.src = '../images/search.gif';"
					title="Click button to display the results of your search">
			</div>
			<div>
				<h1>Taxonomic Criteria:</h1>
				<span style="margin-left:5px;"><input type='checkbox' name='thes' value='1' <?php if(array_key_exists("usethes",$collArray) && $collArray["usethes"]) echo "CHECKED"; ?> >Include Synonyms from Taxonomic Thesaurus<!--<a href="support/TaxThesDescription.php" target="_blank" class="bodylink">  What's This?</a>--></SPAN>
			</div>
			<div id="taxonSearch0">
				<div>
					<select id="taxontype" name="type">
						<option id='familysciname' value='1' <?php if(!array_key_exists("taxontype",$collArray) || $collArray["taxontype"] == "1") echo "SELECTED"; ?> >Family or Scientific Name</option>
						<option id='family' value='2' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "2") echo "SELECTED"; ?> >Family only</option>
						<option id='sciname' value='3' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "3") echo "SELECTED"; ?> >Scientific Name only</option>
						<option id='highertaxon' value='4' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "4") echo "SELECTED"; ?> >Higher Taxonomy</option>
						<option id='commonname' value='5' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "5") echo "SELECTED"; ?> >Common Name</option>
					</select>:
					<input id="taxa" type="text" size="60" name="taxa" value="<?php if(array_key_exists("taxa",$collArray)) echo $collArray["taxa"]; ?>" title="Separate multiple taxa w/ commas" />
				</div>
			</div>
			<div style="margin:10 0 10 0;"><hr></div>
			<div>
				<h1>Locality Criteria:</h1>
			</div>
			<div>
				Country: <input type="text" id="country" size="43" name="country" value="<?php if(array_key_exists("country",$collArray)) echo $collArray["country"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
			<div>
				State/Province: <input type="text" id="state" size="37" name="state" value="<?php if(array_key_exists("state",$collArray)) echo $collArray["state"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
			<div>
				County: <input type="text" id="county" size="37"  name="county" value="<?php if(array_key_exists("county",$collArray)) echo $collArray["county"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
			<div>
				Locality: <input type="text" id="locality" size="43" name="local" value="<?php if(array_key_exists("local",$collArray)) echo $collArray["local"]; ?>" />
			</div>
			<div>
				Elevation: <input type="text" id="elevlow" size="10" name="elevlow" value="<?php if(array_key_exists("elevlow",$collArray)) echo $collArray["elevlow"]; ?>" /> to
				<input type="text" id="elevhigh" size="10" name="elevhigh" value="<?php if(array_key_exists("elevhigh",$collArray)) echo $collArray["elevhigh"]; ?>" />
			</div>
			<div style="margin:10 0 10 0;">
				<hr>
				<h1>Latitude and Longitude: </h1>
			</div>
			<div style="width:270px;float:left;border:2px solid brown;padding:10px;margin-bottom:10px;">
				<div style="font-weight:bold;">
					Bounding box coordinates in decimal degrees
				</div>
				<?php
					$upperLat = "";$bottomLat = "";$leftLong = "";$rightLong = "";
					if(array_key_exists("llbound",$collArray)){
						$llBoundArr = explode(";",$collArray["llbound"]);
						$upperLat = $llBoundArr[0];
						$bottomLat = $llBoundArr[1];
						$leftLong = $llBoundArr[2];
						$rightLong = $llBoundArr[3];
					}

				?>
				<div title="Northern hemisphere is positive; Southern is negative">
					Northern Latitude: <input type="text" id="upperlat" name="upperlat" size="7" value="<?php echo $upperLat; ?>" onchange="javascript:checkUpperLat();" style="margin-left:9px;">
					<select id="upperlat_NS" name="upperlat_NS" onchange="javascript:checkUpperLat();">
						<option id="nlN" value="N">N</option>
						<option id="nlS" value="S">S</option>
					</select>
				</div>
				<div>
					Southern Latitude: <input type="text" id="bottomlat" name="bottomlat" size="7" value="<?php echo $bottomLat; ?>" onchange="javascript:checkBottomLat();" style="margin-left:7px;">
					<select id="bottomlat_NS" name="bottomlat_NS" onchange="javascript:checkBottomLat();">
						<option id="blN" value="N">N</option>
						<option id="blS" value="S">S</option>
					</select>
				</div>
				<div title="Easterm hemisphere is positive; Western is negative">
					Western Longitude: <input type="text" id="leftlong" name="leftlong" size="7" value="<?php echo $leftLong; ?>" onchange="javascript:checkLeftLong();">
					<select id="leftlong_EW" name="leftlong_EW" onchange="javascript:checkLeftLong();">
						<option id="llW" value="W">W</option>
						<option id="llE" value="E">E</option>
					</select>
				</div>
				<div style="float:right;cursor:pointer;" onclick="openBoundingBoxMap();">
					<img src="../images/world40.gif" width="15px" title="Find Coordinate" />
				</div>
				<div title="Easterm hemisphere is positive; Western is negative">
					Eastern Longitude: <input type="text" id="rightlong" name="rightlong" size="7" value="<?php echo $rightLong; ?>" onchange="javascript:checkRightLong();" style="margin-left:3px;">
					<select id="rightlong_EW" name="rightlong_EW" onchange="javascript:checkRightLong();">
						<option id="rlW" value="W">W</option>
						<option id="rlE" value="E">E</option>
					</select>
				</div>
			</div>
			<div style="width:240px; float:left;border:2px solid brown;padding:10px;margin-left:10px;">
				<div style="font-weight:bold;">
					Point-Radius search
				</div>
				<?php
					$pointLat = "";$pointLong = "";$radius = "";
					if(array_key_exists("llpoint",$collArray)){
						$llPointArr = explode(";",$collArray["llpoint"]);
						$pointLat = $llPointArr[0];
						$pointLong = $llPointArr[1];
						$radius = $llPointArr[2];
					}

				?>
				<div title="Northern hemisphere is positive; Southern is negative">
					Latitude: <input type="text" id="pointlat" name="pointlat" size="7" value="<?php echo $pointLat; ?>" onchange="javascript:checkPointLat();" style="margin-left:11px;">
					<select id="pointlat_NS" name="pointlat_NS" onchange="javascript:checkPointLat();">
						<option id="N" value="N">N</option>
						<option id="S" value="S">S</option>
					</select>
				</div>
				<div title="Easterm hemisphere is positive; Western is negative">
					Longitude: <input type="text" id="pointlong" name="pointlong" size="7" value="<?php echo $pointLong; ?>" onchange="javascript:checkPointLong();">
					<select id="pointlong_EW" name="pointlong_EW" onchange="javascript:checkPointLong();">
						<option id="W" value="W">W</option>
						<option id="E" value="E">E</option>
					</select>
				</div>
				<div style="float:right;cursor:pointer;" onclick="openPointRadiusMap();">
					<img src="../images/world40.gif" width="15px" title="Find Coordinate" />
				</div>
				<div>
					Radius: <input type="text" id="radiustemp" name="radiustemp" size="5" value="<?php echo $radius; ?>" style="margin-left:15px;" onchange="updateRadius();">
					<select id="radiusunits" name="radiusunits" onchange="updateRadius();">
						<option value="km">Kilometers</option>
						<option value="mi">Miles</option>
					</select>
					<input type="hidden" id="radius" name="radius" value="" />
				</div>
			</div>
			<div style=";clear:both;"><hr/></div>
			<div>
				<h1>Collector Criteria:</h1>
			</div>
			<div>
				Collector's Last Name:
				<input type="text" id="collector" size="32" name="collector" value="<?php if(array_key_exists("collector",$collArray)) echo $collArray["collector"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
			<div>
				Collector's Number:
				<input type="text" id="collnum" size="31" name="collnum" value="<?php if(array_key_exists("collnum",$collArray)) echo $collArray["collnum"]; ?>" title="Separate multiple terms by commas and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750" />
			</div>
			<div>
				Collection Date:
				<input type="text" id="eventdate1" size="32" name="eventdate1" style="width:100px;" value="<?php if(array_key_exists("eventdate1",$collArray)) echo $collArray["eventdate1"]; ?>" title="Single date or start date of range" /> -
				<input type="text" id="eventdate2" size="32" name="eventdate2" style="width:100px;" value="<?php if(array_key_exists("eventdate2",$collArray)) echo $collArray["eventdate2"]; ?>" title="End date of range; leave blank if searching for single date" />
			</div>
			<div style="float:right;">
				<input style="border: 1px solid gray;" id="display2" name="display2" type="image" class="hoverHand" src='../images/search.gif'
					onmouseover="javascript:this.src = '../images/search_rollover.gif';"
					onmouseout="javascript:this.src = '../images/search.gif';"
					title="Click button to display the results of your search">
			</div>
			<div>
				<h1>Collection Object Criteria:</h1>
			</div>
			<div>
				Catalog Number:
                <input type="text" id="catnum" size="32" name="catnum" value="<?php if(array_key_exists("catnum",$collArray)) echo $collArray["catnum"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
				Other CatalogNumbers:
				<input type="text" id="othercatnum" size="32" name="othercatnum" value="<?php if(array_key_exists("othercatnum",$collArray)) echo $collArray["othercatnum"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
			
			<!--
			<div>
				Type Status:
                <input type="text" size="32" id="typestatus" name="typestatus" value="<?php if(array_key_exists("typestatus",$collArray)) echo $collArray["typestatus"]; ?>" title="Separate multiple terms w/ commas" />
			</div>
			 -->
			<div>
				<!--  <a href="javascript:var popupReference=window.open('support/help.html','technical','toolbar=1,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=800,height=700,left=20,top=20');" class="bodylink">
					Click Here</a> for more information on how this query page works... -->
			</div>
			<input type="hidden" name="reset" value="1" />
		</form>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>
