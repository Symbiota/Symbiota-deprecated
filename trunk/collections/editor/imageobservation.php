<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ImageObservationManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$obsManager = new ImageObservationManager();

$editable = 0;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
	$editable = 1;
}
if($editable){
	if($action == "Submit Observation"){
		$obsManager->submitObservation();
	}
}

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Observation Submission</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.autocomplete-1.4.2.js"></script>
	<script language="javascript" src="../../js/collections.imageobservation.js"></script>
</head>
<body>

<?php
	$displayLeftMenu = (isset($collections_editor_imageObservationMenu)?$collections_individual_imageObservationMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_editor_imageObservationCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_editor_imageObservationCrumbs;
		echo " &gt; <b>Observation Submission</b>";
		echo "</div>";
	}
?>
	<!-- inner text -->
	<div id="innertext">
	<?php 
	if($editable){
		?>
	    <ul id="occedittabs" class="shadetabs">
	        <li><a href="#" rel="shortdiv" class="selected">Specimen Data</a></li>
	        <li><a href="#" rel="determdiv">Determination History</a></li>
	        <li><a href="#" rel="imagediv">Images</a></li>
	    </ul>
		<div style="border:1px solid gray;width:96%;margin-bottom:1em;padding:5px;">
				<form id='fullform' name='fullform' action='occurrenceeditor.php' method='get'>
					<fieldset>
						<legend><b>Observation</b></legend>
						<div style="clear:both;" class="p1">
							<span class="flabel" style="width:125px;">
								Scientific Name:
							</span>
							<span class="flabel" style="margin-left:315px;">
								Author:
							</span>
						</div>
						<div style="clear:both;" class="p1">
							<span>
								<input type="text" name="sciname" maxlength="250" tabindex="2" style="width:390px;background-color:lightyellow;" value="" onfocus="initTaxonList(this)" autocomplete="off" onchange="verifySciName(this);" />
								<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="background-color:;" value="" />
							</span>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;" class="p1">
							<div style="float:left;">
								<span class="flabel">ID Qualifier:</span>
								<input type="text" name="identificationqualifier" tabindex="4" size="5" style="background-color:;" value="" />
							</div>
							<div style="float:left;margin-left:160px;">
								<span class="flabel">Family:</span>
								<input type="text" name="family" size="30" maxlength="50" style="background-color:;" tabindex="0" value="" />
							</div>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;" class="p1">
							<div style="float:left;">
								Identified By:
								<input type="text" name="identifiedby" maxlength="255" tabindex="6" style="background-color:;" value="" />
							</div>
							<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
								Date Identified:
								<input type="text" name="dateidentified" maxlength="45" tabindex="8" style="background-color:;" value="" />
							</div>
							<div style="float:left;margin-left:15px;cursor:pointer;" onclick="toggleIdDetails();">
								<img src="../../images/showedit.png" style="width:15px;" />
							</div>
						</div>
						<div style="clear:both;">
							<div id="idrefdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
								ID References:
								<input type="text" name="identificationreferences" tabindex="10" style="width:450px;background-color:;" value="" />
							</div>
							<div id="taxremdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
								ID Remarks:
								<input type="text" name="taxonremarks" tabindex="12" style="width:500px;background-color:;" value="" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Observer Details</b></legend>
						<div style="float:left;">
							<div style="clear:both;" class="p1">
								<span>
									Observer:
								</span>
								<span style="margin-left:210px;">
									Number:
								</span>
								<span style="margin-left:40px;">
									Date:
								</span>
							</div>
							<div style="clear:both;" class="p1">
								<span>
									<?php $hasValue = array_key_exists("recordedby",$occArr)&&$occArr["recordedby"]["value"]?1:0; ?>
									<input type="text" name="recordedby" maxlength="255" tabindex="14" style="width:250px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["recordedby"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:10px;">
									<?php $hasValue = array_key_exists("recordnumber",$occArr)&&$occArr["recordnumber"]["value"]?1:0; ?>
									<input type="text" name="recordnumber" maxlength="45" tabindex="16" style="width:80px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["recordnumber"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:10px;">
									<?php $hasValue = array_key_exists("eventdate",$occArr)&&$occArr["eventdate"]["value"]?1:0; ?>
									<input type="text" name="eventdate" tabindex="18" style="width:80px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["eventdate"]["value"]:""; ?>" onchange="verifyDate(this);" />
								</span>
								<span style="margin-left:5px;cursor:pointer;" onclick="toggle('dateextradiv')">
									<img src="../../images/showedit.png" style="width:15px;" />
								</span>
							</div>
							<div style="clear:both;margin-top:5px;" class="p1">
								<?php $hasValue = array_key_exists("associatedcollectors",$occArr)&&$occArr["associatedcollectors"]["value"]?1:0; ?>
								Associated Collectors:<br />
								<input type="text" name="associatedcollectors" tabindex="20" maxlength="255" style="width:430px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["associatedcollectors"]["value"]:""; ?>" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Locality</b></legend>
						<div>
							<span style="">
								Country
							</span>
							<span style="margin-left:110px;">
								State/Province
							</span>
							<span style="margin-left:72px;">
								County
							</span>
							<span style="margin-left:112px;">
								Municipality
							</span>
						</div>
						<div>
							<span>
								<?php $hasValue = array_key_exists("country",$occArr)&&$occArr["country"]["value"]?1:0; ?>
								<input type="text" name="country" tabindex="32" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["country"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("stateprovince",$occArr)&&$occArr["stateprovince"]["value"]?1:0; ?>
								<input type="text" name="stateprovince" tabindex="34" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["stateprovince"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("county",$occArr)&&$occArr["county"]["value"]?1:0; ?>
								<input type="text" name="county" tabindex="36" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["county"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("municipality",$occArr)&&$occArr["municipality"]["value"]?1:0; ?>
								<input type="text" name="municipality" tabindex="38" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["municipality"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="margin:4px 0px 2px 0px;">
							Locality:<br />
							<?php $hasValue = array_key_exists("locality",$occArr)&&$occArr["locality"]["value"]?1:0; ?>
							<input type="text" name="locality" tabindex="40" style="width:600px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["locality"]["value"]:""; ?>" />
						</div>
						<div style="margin-bottom:5px;">
							<?php $hasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]["value"]?1:0; ?>
							<input type="checkbox" name="localitysecurity" tabindex="42" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="1" <?php echo $hasValue?"CHECKED":""; ?> title="Hide Locality Data from General Public" />
							Hide Locality Data from Public
						</div>
						<div>
							<span style="">
								Latitude
							</span>
							<span style="margin-left:45px;">
								Longitude
							</span>
							<span style="margin-left:34px;">
								Uncertainty
							</span>
							<span style="margin-left:10px;">
								Datum
							</span>
							<span style="margin-left:43px;">
								Elevation in Meters
							</span>
							<span style="margin-left:15px;">
								Verbatim Elevation
							</span>
						</div>
						<div>
							<span>
								<?php $hasValue = array_key_exists("decimallatitude",$occArr)&&$occArr["decimallatitude"]["value"]?1:0; ?>
								<input type="text" name="decimallatitude" tabindex="44" maxlength="10" style="width:88px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["decimallatitude"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Decimal Latitude')" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("decimallongitude",$occArr)&&$occArr["decimallongitude"]["value"]?1:0; ?>
								<input type="text" name="decimallongitude" tabindex="46" maxlength="13" style="width:88px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["decimallongitude"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Decimal Longitude')" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("coordinateuncertaintyinmeters",$occArr)&&$occArr["coordinateuncertaintyinmeters"]["value"]?1:0; ?>
								<input type="text" name="coordinateuncertaintyinmeters" tabindex="48" maxlength="10" style="width:70px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["coordinateuncertaintyinmeters"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Coordinate Uncertainty')" title="Uncertainty in Meters" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("geodeticdatum",$occArr)&&$occArr["geodeticdatum"]["value"]?1:0; ?>
								<input type="text" name="geodeticdatum" tabindex="50" maxlength="255" style="width:80px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["geodeticdatum"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("minimumelevationinmeters",$occArr)&&$occArr["minimumelevationinmeters"]["value"]?1:0; ?>
								<input type="text" name="minimumelevationinmeters" tabindex="52" maxlength="6" style="width:55px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["minimumelevationinmeters"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Minumum Elevation')" title="Minumum Elevation In Meters" />
							</span> -
							<span>
								<?php $hasValue = array_key_exists("maximumelevationinmeters",$occArr)&&$occArr["maximumelevationinmeters"]["value"]?1:0; ?>
								<input type="text" name="maximumelevationinmeters" tabindex="54" maxlength="6" style="width:55px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["maximumelevationinmeters"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Maximum Elevation')" title="Maximum Elevation In Meters" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("verbatimelevation",$occArr)&&$occArr["verbatimelevation"]["value"]?1:0; ?>
								<input type="text" name="verbatimelevation" tabindex="56" maxlength="255" style="width:100px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimelevation"]["value"]:""; ?>" title="" />
							</span>
							<span style="margin-left:5px;cursor:pointer;" onclick="toggle('locextradiv1');toggle('locextradiv2');">
								<img src="../../images/showedit.png" style="width:15px;" />
							</span>
						</div>
						<?php 
							$locExtraDiv1 = "none";
							if(array_key_exists("verbatimcoordinates",$occArr) && $occArr["verbatimcoordinates"]["value"]){
								$locExtraDiv1 = "block";
							}
							elseif(array_key_exists("verbatimcoordinatesystem",$occArr) && $occArr["verbatimcoordinatesystem"]["value"]){
								$locExtraDiv1 = "block";
							}
							elseif(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]["value"]){
								$locExtraDiv1 = "block";
							}
							elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]["value"]){
								$locExtraDiv1 = "block";
							}
						?>
						<div id="locextradiv1" style="display:<?php echo $locExtraDiv1; ?>;">
							<div>
								<span style="">
									Verbatim Coordinates
								</span>
								<span style="margin-left:32px;">
									Verbatim Coordinate System
								</span>
								<span style="margin-left:18px;">
									Georeferenced By
								</span>
								<span style="margin-left:52px;">
									Georeference Protocol
								</span>
							</div>
							<div>
								<span>
									<?php $hasValue = array_key_exists("verbatimcoordinates",$occArr)&&$occArr["verbatimcoordinates"]["value"]?1:0; ?>
									<input type="text" name="verbatimcoordinates" tabindex="58" maxlength="255" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimcoordinates"]["value"]:""; ?>" title="" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("verbatimcoordinatesystem",$occArr)&&$occArr["verbatimcoordinatesystem"]["value"]?1:0; ?>
									<input type="text" name="verbatimcoordinatesystem" tabindex="60" maxlength="255" style="width:175px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimcoordinatesystem"]["value"]:""; ?>" title="" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferencedby",$occArr)&&$occArr["georeferencedby"]["value"]?1:0; ?>
									<input type="text" name="georeferencedby" tabindex="62" maxlength="255" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferencedby"]["value"]:""; ?>" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferenceprotocol",$occArr)&&$occArr["georeferenceprotocol"]["value"]?1:0; ?>
									<input type="text" name="georeferenceprotocol" tabindex="64" maxlength="255" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferenceprotocol"]["value"]:""; ?>" />
								</span>
							</div>
						</div>
						<?php 
							$locExtraDiv2 = "none";
							if(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]["value"]){
								$locExtraDiv2 = "block";
							}
							elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]["value"]){
								$locExtraDiv2 = "block";
							}
							elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]["value"]){
								$locExtraDiv2 = "block";
							}
						?>
						<div id="locextradiv2" style="display:<?php echo $locExtraDiv2; ?>;">
							<div>
								<span style="">
									Georeference Sources
								</span>
								<span style="margin-left:40px;">
									Georef Verification Status
								</span>
								<span style="margin-left:20px;">
									Georeference Remarks
								</span>
							</div>
							<div>
								<span>
									<?php $hasValue = array_key_exists("georeferencesources",$occArr)&&$occArr["georeferencesources"]["value"]?1:0; ?>
									<input type="text" name="georeferencesources" tabindex="66" maxlength="255" style="width:160px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferencesources"]["value"]:""; ?>" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferenceverificationstatus",$occArr)&&$occArr["georeferenceverificationstatus"]["value"]?1:0; ?>
									<input type="text" name="georeferenceverificationstatus" tabindex="68" maxlength="32" style="width:160px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferenceverificationstatus"]["value"]:""; ?>" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferenceremarks",$occArr)&&$occArr["georeferenceremarks"]["value"]?1:0; ?>
									<input type="text" name="georeferenceremarks" tabindex="70" maxlength="255" style="width:160px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferenceremarks"]["value"]:""; ?>" />
								</span>
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Misc</b></legend>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("habitat",$occArr)&&$occArr["habitat"]["value"]?1:0; ?>
							Habitat:
							<input type="text" name="habitat" tabindex="82" style="width:600px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["habitat"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("associatedtaxa",$occArr)&&$occArr["associatedtaxa"]["value"]?1:0; ?>
							Associated Taxa:
							<input type="text" name="associatedtaxa" tabindex="84" style="width:600px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["associatedtaxa"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("dynamicproperties",$occArr)&&$occArr["dynamicproperties"]["value"]?1:0; ?>
							Description:
							<input type="text" name="dynamicproperties" tabindex="86" style="width:600px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["dynamicproperties"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("occurrenceremarks",$occArr)&&$occArr["occurrenceremarks"]["value"]?1:0; ?>
							Notes:
							<input type="text" name="occurrenceremarks" tabindex="88" style="width:600px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["occurrenceremarks"]["value"]:""; ?>" title="Occurrence Remarks" />
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Curation</b></legend>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("catalognumber",$occArr)&&$occArr["catalognumber"]["value"]?1:0; ?>
								Catalog Number:
								<input type="text" name="catalognumber" tabindex="90" maxlength="32" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["catalognumber"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("occurrenceid",$occArr)&&$occArr["occurrenceid"]["value"]?1:0; ?>
								Occurrence ID (GUID):
								<input type="text" name="occurrenceid" tabindex="92" maxlength="255" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["occurrenceid"]["value"]:""; ?>" title="Global Unique Identifier" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("typestatus",$occArr)&&$occArr["typestatus"]["value"]?1:0; ?>
								Type Status:
								<input type="text" name="typestatus" tabindex="94" maxlength="255" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php $hasValue?$occArr["typestatus"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("disposition",$occArr)&&$occArr["disposition"]["value"]?1:0; ?>
								Disposition:
								<input type="text" name="disposition" tabindex="96" maxlength="32" style="width:200px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["disposition"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("reproductivecondition",$occArr)&&$occArr["reproductivecondition"]["value"]?1:0; ?>
								Reproductive Condition:
								<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="width:140px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["reproductivecondition"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("establishmentmeans",$occArr)&&$occArr["establishmentmeans"]["value"]?1:0; ?>
								Establishment Means:
								<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="width:140px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["establishmentmeans"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:15px;">
								<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]["value"]?1:0; ?>
								<input type="checkbox" name="cultivationstatus" tabindex="102" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["cultivationstatus"]["value"]:0; ?>" />
								Cultivated
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("ownerinstitutioncode",$occArr)&&$occArr["ownerinstitutioncode"]["value"]?1:0; ?>
								Owner InstitutionCode:
								<input type="text" name="ownerinstitutioncode" tabindex="104" maxlength="32" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["ownerinstitutioncode"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("othercatalognumbers",$occArr)&&$occArr["othercatalognumbers"]["value"]?1:0; ?>
								Other Catalog Numbers:
								<input type="text" name="othercatalognumbers" tabindex="106" maxlength="255" style="width:150px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["othercatalognumbers"]["value"]:""; ?>" />
							</span>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Other</b></legend>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("basisofrecord",$occArr)&&$occArr["basisofrecord"]["value"]?1:0; ?>
								Basis of Record:
								<input type="text" name="basisofrecord" tabindex="108" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["basisofrecord"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								<?php $hasValue = array_key_exists("language",$occArr)&&$occArr["language"]["value"]?1:0; ?>
								Language:
								<input type="text" name="language" tabindex="110" maxlength="20" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["language"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("datasetid",$occArr)&&$occArr["datasetid"]["value"]?1:0; ?>
								Dataset ID:
								<input type="text" name="datasetid" tabindex="112" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["datasetid"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								<?php $hasValue = array_key_exists("associatedoccurrences",$occArr)&&$occArr["associatedoccurrences"]["value"]?1:0; ?>
								Associated Occurrences:
								<input type="text" name="associatedoccurrences" size="40" tabindex="114" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["associatedoccurrences"]["value"]:""; ?>" />
							</span>
						</div>
					</fieldset>
					<div>
						<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
						<?php if($occId){ ?>
							<input type="submit" name="action" value="Edit Record" />
						<?php }else{ ?>
							<input type="submit" name="action" value="Add New Record" />
						<?php } ?>
					</div>
				</form>
			</div>
			<div id="determdiv" class="tabcontent" style="margin:10px;">
				
			</div>
			<div id="imagediv" class="tabcontent" style="margin:10px;">
				<div style="float:right;cursor:pointer;margin:10px;" onclick="toggle('addimgdiv');" title="Add a New Image">
					<img style="border:0px;width:12px;" src="../../images/add.png" />
				</div>
				<div id="addimgdiv" style="display:none;">
					<form name="newimgform" action="occurrenceeditor.php" method="post" enctype="multipart/form-data" onsubmit="return submitAddImageForm(this);">
						<fieldset>
							<legend><b>Add a New Image</b></legend>
							<div style='padding:10px;width:550px;border:1px solid yellow;background-color:FFFF99;'>
								<div class="targetdiv" style="display:block;">
									<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
										Select an image file located on your computer that you want to upload:
									</div>
							    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
									<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
									<div>
										<input name='imgfile' type='file' size='70'/>
									</div>
									<div style="margin-left:10px;">Note: upload image size can not be greater than 1MB</div>
									<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
										Link to External Image
									</div>
								</div>
								<div class="targetdiv" style="display:none;">
									<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
										Enter a URL to an image already located on a web server:
									</div>
									<div>
										<b>URL:</b> 
										<input type='text' name='imgurl' size='70'/>
									</div>
									<div>
										<b>Thumbnail URL:</b> 
										<input type='text' name='tnurl' size='70'/>
									</div>
									<div>
										<b>Large URL:</b> 
										<input type='text' name='lgurl' size='70'/>
									</div>
									<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
										Upload Local Image
									</div>
								</div>
							</div>
							<div style="clear:both;margin:20px 0px 5px 10px;">
								<b>Caption:</b> 
								<input name="caption" type="text" size="40" value="" />
							</div>
							<div style='margin:0px 0px 5px 10px;'>
								<b>Photographer:</b> 
								<select name='photographeruid' name='photographeruid'>
									<option value="">Select Photographer</option>
									<option value="">---------------------------------------</option>
									<?php
										$pArr = $occManager->getPhotographerArr();
										foreach($pArr as $id => $uname){
											echo "<option value='".$id."' ".($id == $paramsArr["uid"]?"SELECTED":"").">";
											echo $uname;
											echo "</option>\n";
										}
									?>
								</select>
							</div>
							<div style="margin:0px 0px 5px 10px;">
								<b>Notes:</b> 
								<input name="notes" type="text" size="40" value="" />
							</div>
							<div style="margin:0px 0px 5px 10px;">
								<b>Copyright:</b>
								<input name="copyright" type="text" size="40" value="" />
							</div>
							<div style="margin:0px 0px 5px 10px;">
								<b>Source Webpage:</b>
								<input name="sourceurl" type="text" size="40" value="" />
							</div>
							<div style="margin:0px 0px 5px 10px;">
								<b>Sort Sequence:</b>
								<input name="sortseq" type="text" size="40" value="" />
							</div>
							<div style="margin:10px 0px 10px 20px;">
								<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
								<input type="hidden" name="tid" value="<?php echo $occArr["tidinterpreted"]["value"]; ?>" />
								<input type="hidden" name="institutioncode" value="<?php echo $occArr["institutioncode"]["value"]; ?>" />
								<input type="submit" name="action" value="Submit New Image" />
							</div>
						</fieldset>
					</form>
				</div>
				<div>
					<?php
					if(array_key_exists("images",$occArr)){
						?>
						<table>
						<?php 
						$imagesArr = $occArr["images"];
						foreach($imagesArr as $imgId => $imgArr){
							?>
							<tr>
								<td style="width:45%;text-align:center;padding:20px;">
									<?php
									$imgUrl = $imgArr["url"];
									$origUrl = $imgArr["origurl"];
									$tnUrl = $imgArr["tnurl"];
									if(array_key_exists("imageDomain",$GLOBALS)){
										if(substr($imgUrl,0,1)=="/"){
											$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
										}
										if($origUrl && substr($origUrl,0,1)=="/"){
											$origUrl = $GLOBALS["imageDomain"].$origUrl;
										}
										if($tnUrl && substr($tnUrl,0,1)=="/"){
											$tnUrl = $GLOBALS["imageDomain"].$tnUrl;
										}
									}
									?>
									<a href="<?php echo $imgUrl;?>">
										<img src="<?php echo $imgUrl;?>" style="width:90%;" title="<?php echo $imgArr["caption"]; ?>" />
									</a>
									<?php 
									if($origUrl){
										echo "<div><a href='".$origUrl."'>Click on Image to Enlarge</a></div>";
									}
									?>
								</td>
								<td style="text-align:left;padding:10px 10px 10px 10px;">
									<div style="float:right;cursor:pointer;margin:10px;" onclick="toggleSpan('img-<?php echo $imgId; ?>');" title="Edit Image MetaData">
										<img style="border:0px;width:12px;" src="../../images/edit.png" />
									</div>
									<form name='img<?php echo $imgId; ?>form' action='occurrenceeditor.php' method='post'>
										<div style="clear:both;">
											<b>Caption:</b> 
											<span class="img-<?php echo $imgId; ?>">
												<?php echo $imgArr["caption"]; ?>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="caption" type="text" value="<?php echo $imgArr["caption"]; ?>" style="width:250px;" />
											</span>
										</div>
										<div>
											<b>Photographer:</b> 
											<span class="img-<?php echo $imgId; ?>">
												<?php 
												$pArr = $occManager->getPhotographerArr();
												if($imgArr["photographeruid"]) echo $pArr[$imgArr["photographeruid"]]; 
												?>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<select name='photographeruid' name='photographeruid'>
													<option value="">Select Photographer</option>
													<option value="">---------------------------------------</option>
													<?php
														foreach($pArr as $id => $uname){
															echo "<option value='".$id."' ".($id == $imgArr["photographeruid"]?"SELECTED":"").">";
															echo $uname;
															echo "</option>\n";
														}
													?>
												</select>
											</span>
										</div>
										<div>
											<b>Notes:</b> 
											<span class="img-<?php echo $imgId; ?>">
												<?php echo $imgArr["notes"]; ?>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="notes" type="text" value="<?php echo $imgArr["notes"]; ?>" style="width:300px;" />
											</span>
										</div>
										<div>
											<b>Copyright:</b>
											<span class="img-<?php echo $imgId; ?>">
												<?php echo $imgArr["copyright"]; ?>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="copyright" type="text" value="<?php echo $imgArr["copyright"]; ?>" style="width:250px;" />
											</span>
										</div>
										<div>
											<b>Source Webpage:</b>
											<span class="img-<?php echo $imgId; ?>">
												<a href="<?php echo $imgArr["sourceurl"]; ?>">
													<?php echo $imgArr["sourceurl"]; ?>
												</a>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="sourceurl" type="text" value="<?php echo $imgArr["sourceurl"]; ?>" style="width:250px;" />
											</span>
										</div>
										<div>
											<b>Web URL: </b>
											<span class="img-<?php echo $imgId; ?>">
												<a href="<?php echo $imgArr["url"]; ?>">
													<?php echo $imgArr["url"]; ?>
												</a>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="url" type="text" value="<?php echo $imgArr["url"]; ?>" style="width:300px;" />
												<?php if(stripos($imgArr["url"],$imageRootUrl) === 0){ ?>
												<div style="margin-left:10px;">
													<input type="checkbox" name="renameweburl" value="1" />
													Rename web image file on server to match above edit
												</div>
												<input name='oldurl' type='hidden' value='<?php echo $imgArr["url"];?>' />
												<?php } ?>
											</span>
										</div>
										<div>
											<b>Large Image URL: </b>
											<span class="img-<?php echo $imgId; ?>">
												<a href="<?php echo $imgArr["origurl"]; ?>">
													<?php echo $imgArr["origurl"]; ?>
												</a>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="origurl" type="text" value="<?php echo $imgArr["origurl"]; ?>" style="width:250px;" />
												<?php if(stripos($imgArr["origurl"],$imageRootUrl) === 0){ ?>
												<div style="margin-left:10px;">
													<input type="checkbox" name="renameorigurl" value="1" />
													Rename large image file on server to match above edit
												</div>
												<input name='oldorigurl' type='hidden' value='<?php echo $imgArr["origurl"];?>' />
												<?php } ?>
											</span>
										</div>
										<div>
											<b>Thumbnail URL: </b>
											<span class="img-<?php echo $imgId; ?>">
												<a href="<?php echo $imgArr["tnurl"]; ?>">
													<?php echo $imgArr["tnurl"]; ?>
												</a>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="tnurl" type="text" value="<?php echo $imgArr["tnurl"]; ?>" style="width:250px;" />
												<?php if(stripos($imgArr["tnurl"],$imageRootUrl) === 0){ ?>
												<div style="margin-left:10px;">
													<input type="checkbox" name="renametnurl" value="1" />
													Rename thumbnail image file on server to match above edit
												</div>
												<input name='oldtnurl' type='hidden' value='<?php echo $imgArr["tnurl"];?>' />
												<?php } ?>
											</span>
										</div>
										<div>
											<b>Sort Sequence:</b>
											<span class="img-<?php echo $imgId; ?>">
												<?php echo $imgArr["sortseq"]; ?>
											</span>
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input name="sortseq" type="text" value="<?php echo $imgArr["sortseq"]; ?>" style="width:50px;" />
											</span>
										</div>
										<div style="margin-top:10px;">
											<span class="img-<?php echo $imgId; ?>" style="display:none;">
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
												<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
												<input type="submit" name="action" value="Submit Image Edits" />
											</span>
										</div>
									</form>
								</td>
							</tr>
							<?php 
						}
						?>
						</table>
						<?php 
					}
					else{
						?>
						<h2>No images linked to this collection record.<br/>Click symbol to right to add an image.</h2>
						<?php 
					}
					?>
				</div>
			</div>
		</div>
	<?php 
	}
	else{
		echo "Please <a href='../../profile/index.php?refurl=/seinet/collections/editor/occurrenceeditor.php?occid=".$occId."'>login</a>";
	}
	?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>

</body>
</html>
