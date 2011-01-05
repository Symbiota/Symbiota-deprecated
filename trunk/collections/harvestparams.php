<?php
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/OccurrenceManager.php');
 header("Content-Type: text/html; charset=".$charset);
 header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
 header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
 
 $collManager = new OccurrenceManager(); 
 $collArray = $collManager->getSearchTerms();
?>
 
<html>
<head>
    <title><?php echo $defaultTitle; ?> Collection Search Parameters</title>
    <link rel="stylesheet" href="../css/main.css" type="text/css">
    <link rel="stylesheet" href="../css/jqac.css" type="text/css">
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete-1.4.2.js"></script>
	<script language="javascript" src="../js/collections.harvestparams.js"></script>
</head>
<body>

<?php
	$displayLeftMenu = (isset($collections_harvestparamsMenu)?$collections_harvestparamsMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($collections_harvestparamsCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_harvestparamsCrumbs;
		echo " &gt; <b>Search Criteria</b>";
		echo "</div>";
	}
	?>

	<div id="innertext">
		<h1>Select Search Parameters</h1>
		Fill in one or more of the following query criteria and click 'Search' to view your results.

		<form name="harvestparams" id="harvestparams" action="list.php" method="get" onsubmit="return checkForm()">
			<div style="margin:10 0 10 0;"><hr></div>
			<div style='float:right;margin:10px;'>
				<input type="image" name="display1" id="display1" class="hoverHand" src='../images/search.gif'
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
						<option id='classorder' value='4' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "4") echo "SELECTED"; ?> >Class / Order</option>
						<option id='commonname' value='5' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "5") echo "SELECTED"; ?> >Common Name</option>
					</select>: 
					<input class="complete" id="taxa" type="text" size="30" name="taxa" value="<?php if(array_key_exists("taxa",$collArray)) echo $collArray["taxa"]; ?>" title="Seperate multiple taxa w/ commas" />
				</div>
			</div>
			<div style="margin:10 0 10 0;"><hr></div>
			<div>
				<h1>Locality Criteria:</h1>
			</div>
			<div>
				Country: <input type="text" id="country" size="43" name="country" value="<?php if(array_key_exists("country",$collArray)) echo $collArray["country"]; ?>" title="Seperate multiple terms w/ commas" />
			</div>
			<div>
				State/Province: <input type="text" id="state" size="37" name="state" value="<?php if(array_key_exists("state",$collArray)) echo $collArray["state"]; ?>" title="Seperate multiple terms w/ commas" />
			</div>
			<div>
				County/Municipio: <input type="text" id="county" size="37"  name="county" value="<?php if(array_key_exists("county",$collArray)) echo $collArray["county"]; ?>" title="Seperate multiple terms w/ commas" />
			</div>
			<div>
				Locality: <input type="text" id="locality" size="43" name="local" value="<?php if(array_key_exists("local",$collArray)) echo $collArray["local"]; ?>" />
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
						<option value="mi">Miles</option>
						<option value="km">Kilometers</option>
					</select>
					<input type="hidden" id="radius" name="radius" value="" />
				</div>
			</div>
			<div style=";clear:both;"><hr/></div>
			<div>
				<h1>Collector Criteria:</h1>
			</div>
			<div>
				Collector's Last Name: <input type="text" id="collector" size="32" name="collector" value="<?php if(array_key_exists("collector",$collArray)) echo $collArray["collector"]; ?>" title="Seperate multiple terms w/ commas" />
			</div>
			<div>
				Collector's Number: <input type="text" id="collnum" size="31" name="collnum" value="<?php if(array_key_exists("collnum",$collArray)) echo $collArray["collnum"]; ?>" title="Seperate multiple terms w/ commas" />
			</div>
			<div><hr></div>
			<div>
				<!--  <a href="javascript:var popupReference=window.open('support/help.html','technical','toolbar=1,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=800,height=700,left=20,top=20');" class="bodylink">
					Click Here</a> for more information on how this query page works... -->
			</div>
			<div>
				<input id="display2" name="display2" type="image" class="hoverHand" src='../images/search.gif' 
					onmouseover="javascript:this.src = '../images/search_rollover.gif';" 
					onmouseout="javascript:this.src = '../images/search.gif';"
					title="Click button to display the results of your search">
			</div>
			<input type="hidden" name="reset" value="1" />
		</form>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>
