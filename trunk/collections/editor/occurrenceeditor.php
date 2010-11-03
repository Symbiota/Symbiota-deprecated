<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = array_key_exists("occid",$_REQUEST)?$_REQUEST["occid"]:"";

$occManager = new OccurrenceEditorManager();
$occArr = Array();
$editable = 0;
if($occId){
	$occArr = $occManager->getOccurArr($occId);
	$collId = $occArr["collid"]["value"];
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$editable = 1;
	}
}

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Detailed Collection Record Information</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<link rel="stylesheet" type="text/css" href="../../css/tabcontent.css" />
	<script type="text/javascript" src="../../js/tabcontent.js"></script>
	<script language="javascript">
		function initTabs(tabObjId){
			var dTabs=new ddtabcontent(tabObjId); 
			dTabs.setpersist(true);
			dTabs.setselectedClassTarget("link"); 
			dTabs.init();
		}

		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="none"){
					ele.style.display="block";
		  		}
			 	else {
			 		ele.style.display="none";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="inline";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}

	</script>
</head>
<body onload="initTabs('occedittabs');">

<?php
	$displayLeftMenu = (isset($collections_individual_occurrenceEditorMenu)?$collections_individual_occurrenceEditorMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_individual_occurrenceEditorCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_individual_occurrenceEditorCrumbs;
		echo " &gt; <b>Occurrence Editor</b>";
		echo "</div>";
	}
?>
	<!-- inner text -->
	<div id="innertext">
	<?php 
	if($editable){
		?>
	    <ul id="occedittabs" class="shadetabs">
	        <li><a href="#" rel="shortdiv" class="selected">All Fields</a></li>
	        <li><a href="#" rel="eventdiv">Occurrence Event</a></li>
	        <li><a href="#" rel="identdiv">Identification</a></li>
	        <li><a href="#" rel="localitydiv">Locality</a></li>
	    </ul>
		<div style="border:1px solid gray;width:800px;margin-bottom:1em;padding:10px;">
			<div id="shortdiv" class="tabcontent" style="margin:10px;">
				<form id='fullform' name='fullform' action='occurrenceeditor.php' method='get'>
					<fieldset>
						<legend><b>Latest Description</b></legend>
						<div style="clear:both;" class="p1">
							<span style="width:125px;">
								Scientific Name:
							</span>
							<span style="margin-left:310px;">
								Author:
							</span>
						</div>
						<div style="clear:both;" class="p1">
							<span>
								<input type="text" name="sciname" size="60" maxlength="250" tabindex="1" value="<?php echo array_key_exists("sciname",$occArr)?$occArr["sciname"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="author" maxlength="100" tabindex="0" value="<?php echo array_key_exists("author",$occArr)?$occArr["author"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;" class="p1">
							<div style="float:left;">
								ID Qualifier:
								<input type="text" name="identificationqualifier" tabindex="2" size="5" value="<?php echo array_key_exists("identificationqualifier",$occArr)?$occArr["identificationqualifier"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:160px;">
								Family:
								<input type="text" name="family" size="30" maxlength="50" tabindex="0" value="<?php echo array_key_exists("family",$occArr)?$occArr["family"]["value"]:""; ?>" />
							</div>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;" class="p1">
							<div style="float:left;">
								Identified By:
								<input type="text" name="identifiedby" maxlength="255" tabindex="3" value="<?php echo array_key_exists("identifiedby",$occArr)?$occArr["identifiedby"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
								Date Identified:
								<input type="text" name="dateidentified" maxlength="45" tabindex="4" value="<?php echo array_key_exists("dateidentified",$occArr)?$occArr["dateidentified"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:15px;cursor:pointer;" onclick="toggle('iddetails')">
								<img src="../../images/showedit.png" style="width:15px;" />
							</div>
						</div>
						<div id="iddetails" style="clear:both;display:none;">
							<div style="padding:3px 0px 0px 10px;">
								ID References:
								<input type="text" name="identificationreferences" tabindex="5" size="60" value="<?php echo array_key_exists("identificationreferences",$occArr)?$occArr["identificationreferences"]["value"]:""; ?>" />
							</div>
							<div style="padding:3px 0px 0px 10px;">
								ID Remarks:
								<input type="text" name="taxonremarks" tabindex="6" size="60" value="<?php echo array_key_exists("identificationremarks",$occArr)?$occArr["identificationremarks"]["value"]:""; ?>" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Collector Info</b></legend>
						<div style="float:left;">
							<div style="clear:both;" class="p1">
								<span>
									Collector:
								</span>
								<span style="margin-left:210px;">
									Number:
								</span>
								<span style="margin-left:60px;">
									Date:
								</span>
							</div>
							<div style="clear:both;" class="p1">
								<span>
									<input type="text" name="recordedby" size="35" maxlength="255" tabindex="7" value="<?php echo array_key_exists("recordedby",$occArr)?$occArr["recordedby"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:20px;">
									<input type="text" name="recordnumber" size="10" maxlength="45" tabindex="8" value="<?php echo array_key_exists("recordnumber",$occArr)?$occArr["recordnumber"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:20px;">
									<input type="text" name="eventdate" size="10" tabindex="12" value="<?php echo array_key_exists("eventdate",$occArr)?$occArr["eventdate"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:5px;cursor:pointer;" onclick="toggle('dateextradiv')">
									<img src="../../images/showedit.png" style="width:15px;" />
								</span>
							</div>
							<div style="clear:both;padding:5px 0px 0px 10px;">
								Associated Collectors:
								<input type="text" name="associatedcollectors" tabindex="15" maxlength="255" size="50" value="<?php echo array_key_exists("associatedcollectors",$occArr)?$occArr["associatedcollectors"]["value"]:""; ?>" />
							</div>
						</div>
						<div id="dateextradiv" style="float:left;padding:5px;margin-left:10px;border:1px solid gray;display:none;">
							<div>
								<span>
									Verbatim Date:
								</span>
								<span style="margin-left:7px;">
									<input type="text" name="verbatimeventdate" size="10" tabindex="0" maxlength="255" value="<?php echo array_key_exists("verbatimeventdate",$occArr)?$occArr["verbatimeventdate"]["value"]:""; ?>" />
								</span>
							</div>
							<div>
								<span>
									Month/Day/Year:
								</span>
								<span>
									<input type="text" name="month" tabindex="0" size="1" value="<?php echo array_key_exists("month",$occArr)?$occArr["month"]["value"]:""; ?>" title="Month" />/
								</span>
								<span>
									<input type="text" name="day" tabindex="0" size="1" value="<?php echo array_key_exists("day",$occArr)?$occArr["day"]["value"]:""; ?>" title="Day" />/
								</span>
								<span>
									<input type="text" name="year" tabindex="0" size="2" value="<?php echo array_key_exists("year",$occArr)?$occArr["year"]["value"]:""; ?>" title="Year" />
								</span>
							</div>
							<div>
								<span>
									Day of Year:
								</span>
								<span style="margin-left:22px;">
									<input type="text" name="startdayofyear" tabindex="0" size="3" value="<?php echo array_key_exists("startdayofyear",$occArr)?$occArr["startdayofyear"]["value"]:""; ?>" title="Start Day of Year" /> -
								</span>
								<span>
									<input type="text" name="enddayofyear" tabindex="0" size="3" value="<?php echo array_key_exists("enddayofyear",$occArr)?$occArr["enddayofyear"]["value"]:""; ?>" title="End Day of Year" />
								</span>
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Locality</b></legend>
						<div>
							<span style="">
								Country
							</span>
							<span style="margin-left:109px;">
								State/Province
							</span>
							<span style="margin-left:70px;">
								County
							</span>
							<span style="margin-left:110px;">
								Municipality
							</span>
						</div>
						<div>
							<span>
								<input type="text" name="country" tabindex="" size="" value="<?php echo array_key_exists("country",$occArr)?$occArr["country"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="stateprovince" tabindex="" size="" value="<?php echo array_key_exists("stateprovince",$occArr)?$occArr["stateprovince"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="county" tabindex="" size="" value="<?php echo array_key_exists("county",$occArr)?$occArr["county"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="municipality" tabindex="" size="" value="<?php echo array_key_exists("municipality",$occArr)?$occArr["municipality"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="margin:4px 0px 2px 0px;">
							<span>
								Locality:
							</span>
							<span>
								<input type="text" name="locality" tabindex="" size="100" value="<?php echo array_key_exists("locality",$occArr)?$occArr["locality"]["value"]:""; ?>" />
							</span>
						</div>
						<div>
							<span>
								<input type="checkbox" name="localitysecurity" tabindex="" value="1" <?php echo (array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]["value"]?"CHECKED":""); ?> title="Hide Locality Data from General Public" />
								Hidden Locality Data
							</span>
						</div>
						<div>
							<span style="">
								Latitude
							</span>
							<span style="margin-left:45px;">
								Longitude
							</span>
							<span style="margin-left:35px;">
								Uncertainty
							</span>
							<span style="margin-left:10px;">
								Datum
							</span>
							<span style="margin-left:53px;">
								Elevation in Meters
							</span>
							<span style="margin-left:25px;">
								Verbatim Elevation
							</span>
						</div>
						<div>
							<span>
								<input type="text" name="decimallatitude" tabindex="" size="10" maxlength="10" value="<?php echo array_key_exists("decimallatitude",$occArr)?$occArr["decimallatitude"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="decimallongitude" tabindex="" size="10" maxlength="13" value="<?php echo array_key_exists("decimallongitude",$occArr)?$occArr["decimallongitude"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="coordinateuncertaintyinmeters" tabindex="" size="7" maxlength="10" value="<?php echo array_key_exists("coordinateuncertaintyinmeters",$occArr)?$occArr["coordinateuncertaintyinmeters"]["value"]:""; ?>" title="Uncertainty in Meters" />
							</span>
							<span>
								<input type="text" name="geodeticdatum" tabindex="" size="10" maxlength="255" value="<?php echo array_key_exists("geodeticdatum",$occArr)?$occArr["geodeticdatum"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="minimumelevationinmeters" tabindex="" size="5" maxlength="6" value="<?php echo array_key_exists("minimumelevationinmeters",$occArr)?$occArr["minimumelevationinmeters"]["value"]:""; ?>" title="Minumum Elevation In Meters" />
							</span> -
							<span>
								<input type="text" name="maximumelevationinmeters" tabindex="" size="5" maxlength="6" value="<?php echo array_key_exists("maximumelevationinmeters",$occArr)?$occArr["maximumelevationinmeters"]["value"]:""; ?>" title="Maximum Elevation In Meters" />
							</span>
							<span>
								<input type="text" name="verbatimelevation" tabindex="" size="" maxlength="255" value="<?php echo array_key_exists("verbatimelevation",$occArr)?$occArr["verbatimelevation"]["value"]:""; ?>" title="" />
							</span>
						</div>
						<div>
							<span style="">
								Verbatim Coordinates
							</span>
							<span style="margin-left:30px;">
								Verbatim Coordinate System
							</span>
							<span style="margin-left:22px;">
								Georeferenced By
							</span>
							<span style="margin-left:50px;">
								Georeference Protocol
							</span>
						</div>
						<div>
							<span>
								<input type="text" name="verbatimCoordinates" tabindex="" size="" maxlength="255" value="<?php echo array_key_exists("verbatimCoordinates",$occArr)?$occArr["verbatimCoordinates"]["value"]:""; ?>" title="" />
							</span>
							<span>
								<input type="text" name="verbatimCoordinateSystem" tabindex="" size="25" maxlength="255" value="<?php echo array_key_exists("verbatimCoordinateSystem",$occArr)?$occArr["verbatimCoordinateSystem"]["value"]:""; ?>" title="" />
							</span>
							<span>
								<input type="text" name="georeferencedby" tabindex="" maxlength="255" value="<?php echo array_key_exists("georeferencedby",$occArr)?$occArr["georeferencedby"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="georeferenceprotocol" tabindex="" maxlength="255" value="<?php echo array_key_exists("georeferenceprotocol",$occArr)?$occArr["georeferenceprotocol"]["value"]:""; ?>" />
							</span>
						</div>
						<div>
							<span style="">
								Georeference Sources
							</span>
							<span style="margin-left:28px;">
								Georef Verification Status
							</span>
							<span style="margin-left:10px;">
								Georeference Remarks
							</span>
						</div>
						<div>
							<span>
								<input type="text" name="georeferencesources" tabindex="" maxlength="255" value="<?php echo array_key_exists("georeferencesources",$occArr)?$occArr["georeferencesources"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="georeferenceverificationstatus" tabindex="" size="" maxlength="32" value="<?php echo array_key_exists("georeferenceverificationstatus",$occArr)?$occArr["georeferenceverificationstatus"]["value"]:""; ?>" />
							</span>
							<span>
								<input type="text" name="georeferenceremarks" tabindex="" size="50" maxlength="255" value="<?php echo array_key_exists("georeferenceremarks",$occArr)?$occArr["georeferenceremarks"]["value"]:""; ?>" />
							</span>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Misc</b></legend>
						<div style="padding:3px;">
							Habitat:
							<input type="text" name="habitat" tabindex="18" size="105" value="<?php echo array_key_exists("habitat",$occArr)?$occArr["habitat"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Associated Taxa:
							<input type="text" name="associatedtaxa" tabindex="20" size="97" value="<?php echo array_key_exists("associatedtaxa",$occArr)?$occArr["associatedtaxa"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Description:
							<input type="text" name="attributes" tabindex="23" size="101" value="<?php echo array_key_exists("attributes",$occArr)?$occArr["attributes"]["value"]:""; ?>" title="Description of Organism" />
						</div>
						<div style="padding:3px;">
							Notes:
							<input type="text" name="occurrenceremarks" tabindex="25" size="106" value="<?php echo array_key_exists("occurrenceremarks",$occArr)?$occArr["occurrenceremarks"]["value"]:""; ?>" title="Occurrence Remarks" />
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Curation</b></legend>
						<div style="padding:3px;">
							<span>
								Catalog Number:
								<input type="text" name="catalognumber" tabindex="28" maxlength="32" value="<?php echo array_key_exists("catalognumber",$occArr)?$occArr["catalognumber"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Occurrence ID (GUID):
								<input type="text" name="occurrenceid" tabindex="30" maxlength="255" value="<?php echo array_key_exists("occurrenceid",$occArr)?$occArr["occurrenceid"]["value"]:""; ?>" title="Global Unique Identifier" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								Type Status:
								<input type="text" name="typestatus" tabindex="33" maxlength="255" value="<?php echo array_key_exists("typestatus",$occArr)?$occArr["typestatus"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Disposition:
								<input type="text" name="disposition" tabindex="35" maxlength="32" value="<?php echo array_key_exists("disposition",$occArr)?$occArr["disposition"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								Reproductive Condition:
								<input type="text" name="reproductivecondition" tabindex="38" maxlength="255" value="<?php echo array_key_exists("reproductivecondition",$occArr)?$occArr["reproductivecondition"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Establishment Means:
								<input type="text" name="establishmentmeans" tabindex="45" maxlength="32" value="<?php echo array_key_exists("establishmentmeans",$occArr)?$occArr["establishmentmeans"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<input type="checkbox" name="cultivationstatus" tabindex="48" value="<?php echo array_key_exists("cultivationstatus",$occArr)?$occArr["cultivationstatus"]["value"]:0; ?>" />
								Cultivated
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								Owner InstitutionCode:
								<input type="text" name="ownerinstitutioncode" tabindex="50" maxlength="32" value="<?php echo array_key_exists("ownerinstitutioncode",$occArr)?$occArr["ownerinstitutioncode"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Other Catalog Numbers:
								<input type="text" name="othercatalognumbers" tabindex="53" maxlength="255" value="<?php echo array_key_exists("othercatalognumbers",$occArr)?$occArr["othercatalognumbers"]["value"]:""; ?>" />
							</span>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Other</b></legend>
						<div style="padding:3px;">
							<span>
								Basis of Record:
								<input type="text" name="basisofrecord" tabindex="55" maxlength="32" value="<?php echo array_key_exists("basisofrecord",$occArr)?$occArr["basisofrecord"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								Language:
								<input type="text" name="language" tabindex="53" maxlength="20" value="<?php echo array_key_exists("language",$occArr)?$occArr["language"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								Dataset ID:
								<input type="text" name="datasetid" tabindex="53" maxlength="255" value="<?php echo array_key_exists("datasetid",$occArr)?$occArr["datasetid"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							Associated Occurrences:
							<input type="text" name="associatedoccurrences" tabindex="53" value="<?php echo array_key_exists("associatedoccurrences",$occArr)?$occArr["associatedoccurrences"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Field Notes:
							<input type="text" name="fieldnotes" tabindex="53" value="<?php echo array_key_exists("fieldnotes",$occArr)?$occArr["fieldnotes"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Dynamic Properties:
							<input type="text" name="dynamicproperties" tabindex="53" value="<?php echo array_key_exists("dynamicproperties",$occArr)?$occArr["dynamicproperties"]["value"]:""; ?>" />
						</div>
						
					</fieldset>
				</form>
			</div>
			<form id='longform' name='longform' action='occurrenceeditor.php' method='get'>
				<div id="eventdiv" class="tabcontent" style="margin:10px;">
					
				</div>
				<div id="identdiv" class="tabcontent" style="margin:10px;">
				</div>
				<div id="localitydiv" class="tabcontent" style="margin:10px;">
					
				</div>
			</form>
		</div>
	<?php 
	}
	?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>

</body>
</html>
