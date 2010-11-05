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
	<script type="text/javascript">
		/**
		 * key: input for LOOK(1)
		 * cont: function(res) for return of suggest results
		*/ 
		function getSuggs(key,cont){
			var taxonType = 1;
			var taxonTypeObj = document.getElementById("taxontype");
			if(taxonTypeObj){
				taxonType = taxonTypeObj.value;
			}
			var script_name = 'rpc/taxalist.php';
			var params = { 'q':key , 't':taxonType }
			$.get(script_name,params,
				function(obj){ 
		           // obj is just array of strings
		           var res = [];
		           for(var i=0;i<obj.length;i++){
		             res.push({ id:i , value:obj[i]});
		           }
		           // will build suggestions list
		           cont(res); 
		         },
		         'json');
		}
			
		$(document).ready(function(){
			$('input.complete').autocomplete({ ajax_get : getSuggs, multi: true});
		});

		function checkUpperLat(){
			if(document.harvestparams.upperlat.value != ""){
				if(document.harvestparams.upperlat_NS.value=='N'){
					document.harvestparams.upperlat.value = Math.abs(parseFloat(document.harvestparams.upperlat.value));
				}
				else{
					document.harvestparams.upperlat.value = -1*Math.abs(parseFloat(document.harvestparams.upperlat.value));
				}
			}
		}
			
		function checkBottomLat(){
			if(document.harvestparams.bottomlat.value != ""){
				if(document.harvestparams.bottomlat_NS.value == 'N'){
					document.harvestparams.bottomlat.value = Math.abs(parseFloat(document.harvestparams.bottomlat.value));
				}
				else{
					document.harvestparams.bottomlat.value = -1*Math.abs(parseFloat(document.harvestparams.bottomlat.value));
				}
			}
		}

		function checkRightLong(){
			if(document.harvestparams.rightlong.value != ""){
				if(document.harvestparams.rightlong_EW.value=='E'){
					document.harvestparams.rightlong.value = Math.abs(parseFloat(document.harvestparams.rightlong.value));
				}
				else{
					document.harvestparams.rightlong.value = -1*Math.abs(parseFloat(document.harvestparams.rightlong.value));
				}
			}
		}

		function checkLeftLong(){
			if(document.harvestparams.leftlong.value != ""){
				if(document.harvestparams.leftlong_EW.value=='E'){
					document.harvestparams.leftlong.value = Math.abs(parseFloat(document.harvestparams.leftlong.value));
				}
				else{
					document.harvestparams.leftlong.value = -1*Math.abs(parseFloat(document.harvestparams.leftlong.value));
				}
			}
		}

		function checkPointLat(){
			if(document.harvestparams.pointlat.value != ""){
				if(document.harvestparams.pointlat_NS.value=='N'){
					document.harvestparams.pointlat.value = Math.abs(parseFloat(document.harvestparams.pointlat.value));
				}
				else{
					document.harvestparams.pointlat.value = -1*Math.abs(parseFloat(document.harvestparams.pointlat.value));
				}
			}
		}

		function checkPointLong(){
			if(document.harvestparams.pointlong.value != ""){
				if(document.harvestparams.pointlong_EW.value=='E'){
					document.harvestparams.pointlong.value = Math.abs(parseFloat(document.harvestparams.pointlong.value));
				}
				else{
					document.harvestparams.pointlong.value = -1*Math.abs(parseFloat(document.harvestparams.pointlong.value));
				}
			}
		}


	 function checkForm(){
		var frm = document.harvestparams;

		//make sure they have filled out at least one field.
		if((frm.taxa.value == '') && (frm.country.value == '') && (frm.state.value == '') && (frm.county.value == '') && 
			(frm.locality.value == '') && (frm.upperlat.value == '') && (frm.pointlat.value == '') && 
			(frm.collector.value == '') && (frm.collnum.value == '')){
	        alert("Please fill in at least one search parameter!");
	        return false;
	    }
	 
	    if(frm.upperlat.value != '' || frm.bottomlat.value != '' || frm.leftlong.value != '' || frm.rightlong.value != ''){
	        // if Lat/Long field is filled in, they all should have a value!
	        if(frm.upperlat.value == '' || frm.bottomlat.value == '' || frm.leftlong.value == '' || frm.rightlong.value == ''){
				alert("Error: Please make all Lat/Long bounding box values contain a value or all are empty");
				return false;
	        }

			// Check to make sure lat/longs are valid.
			if(Math.abs(frm.upperlat.value) > 90 || Math.abs(frm.bottomlat.value) > 90 || Math.abs(frm.pointlat.value) > 90){
				alert("Latitude values can not be greater than 90 or less than -90.");
				return false;
			} 
			if(Math.abs(frm.leftlong.value) > 180 || Math.abs(frm.rightlong.value) > 180 || Math.abs(frm.pointlong.value) > 180){
				alert("Longitude values can not be greater than 180 or less than -180.");
				return false;
			} 
			if(frm.upperlat.value < frm.bottomlat.value){
				alert("Your northern latitude value is less then your southern latitude value. Please correct this.");
				return false;
			}
			if(eval(frm.leftlong.value) > eval(frm.rightlong.value)){
				alert("Your western longitude value is greater then your eastern longitude value. Please correct this. Note that western hemisphere longitudes in the decimal format are negitive.");
				return false;
			}
	    }

		//Same with point radius fields
	    if(frm.pointlat.value != '' || frm.pointlong.value != '' || frm.radius.value != ''){
	        if(frm.pointlat.value == '' || frm.pointlong.value == '' || frm.radius.value == ''){
	    		alert("Error: Please make all Lat/Long point-radius values contain a value or all are empty");
	    		return false;
	        }            
	    }

	    return true;
	 }

	 function updateRadius(){
		var radiusUnits = document.getElementById("radiusunits").value;
		var radiusInMiles = document.getElementById("radiustemp").value;
		if(radiusUnits == "km"){
			radiusInMiles = radiusInMiles*0.6214; 
		}
		document.getElementById("radius").value = radiusInMiles;
	 }

	 function openPointRadiusMap() {
	     mapWindow=open("mappointradius.php","pointradius","resizable=0,width=530,height=500,left=20,top=20");
	     if (mapWindow.opener == null) mapWindow.opener = self;
	 }

	 function openBoundingBoxMap() {
	     mapWindow=open("mapboundingbox.php","boundingbox","resizable=0,width=530,height=500,left=20,top=20");
	     if (mapWindow.opener == null) mapWindow.opener = self;
	 }
	</script>	

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
