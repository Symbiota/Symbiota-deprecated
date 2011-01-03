<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ObservationSubmitManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId  = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$obsManager = new ObservationSubmitManager($symbUid);

$editable = 0;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
	$editable = 1;
}
if($editable){
	if($action == "Submit Observation"){
		$obsManager->addObservation();
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
	if($symbUid){
		if($editable){
			?>
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
					<div style="float:left;">
						<div style="clear:both;">
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
						<div style="clear:both;">
							<span>
								<input type="text" name="recordedby" maxlength="255" tabindex="14" style="width:250px;background-color:;" value="" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="recordnumber" maxlength="45" tabindex="16" style="width:80px;background-color:;" value="" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="eventdate" tabindex="18" style="width:80px;background-color:;" value="" onchange="verifyDate(this);" />
							</span>
							<span style="margin-left:5px;cursor:pointer;" onclick="toggle('obserextradiv')">
								<img src="../../images/showedit.png" style="width:15px;" />
							</span>
						</div>
					</div>
					<div id="obsextradiv" style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;display:none;">
						<div style="clear:both;margin-top:5px;">
							Associated Observers:<br />
							<input type="text" name="associatedcollectors" tabindex="20" maxlength="255" style="width:430px;background-color:;" value="" />
						</div>
						<div style="float:left;">
							Identified By:
							<input type="text" name="identifiedby" maxlength="255" tabindex="6" style="background-color:;" value="" />
						</div>
						<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
							Date Identified:
							<input type="text" name="dateidentified" maxlength="45" tabindex="8" style="background-color:;" value="" />
						</div>
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
					</div>
					<div>
						<span>
							<input type="text" name="country" tabindex="32" style="width:150px;background-color:;" value="" />
						</span>
						<span>
							<input type="text" name="stateprovince" tabindex="34" style="width:150px;background-color:;" value="" />
						</span>
						<span>
							<input type="text" name="county" tabindex="36" style="width:150px;background-color:;" value="" />
						</span>
					</div>
					<div style="margin:4px 0px 2px 0px;">
						Locality:<br />
						<input type="text" name="locality" tabindex="40" style="width:600px;background-color:;" value="" />
					</div>
					<div style="margin-bottom:5px;">
						<input type="checkbox" name="localitysecurity" tabindex="42" style="background-color:;" value="1" title="Hide Locality Data from General Public" />
						Hide Locality Details from General Public
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
					</div>
					<div>
						<span>
							<input type="text" name="decimallatitude" tabindex="44" maxlength="10" style="width:88px;background-color:;" value="" onchange="inputIsNumeric(this, 'Decimal Latitude')" />
						</span>
						<span>
							<input type="text" name="decimallongitude" tabindex="46" maxlength="13" style="width:88px;background-color:;" value="" onchange="inputIsNumeric(this, 'Decimal Longitude')" />
						</span>
						<span>
							<input type="text" name="coordinateuncertaintyinmeters" tabindex="48" maxlength="10" style="width:70px;background-color:;" value="" onchange="inputIsNumeric(this, 'Coordinate Uncertainty')" title="Uncertainty in Meters" />
						</span>
						<span>
							<input type="text" name="geodeticdatum" tabindex="50" maxlength="255" style="width:80px;background-color:;" value="" />
						</span>
						<span>
							<input type="text" name="minimumelevationinmeters" tabindex="52" maxlength="6" style="width:55px;background-color:;" value="" onchange="inputIsNumeric(this, 'Minumum Elevation')" title="Minumum Elevation In Meters" />
						</span>
					</div>
					<div>
						<div>
							<span style="margin-left:20px;">
								Georeference Remarks
							</span>
						</div>
						<div>
							<span>
								<input type="text" name="georeferenceremarks" tabindex="70" maxlength="255" style="width:160px;background-color:;" value="" />
							</span>
						</div>
					</div>
				</fieldset>
				<fieldset>
					<legend><b>Misc</b></legend>
					<div style="padding:3px;">
						Habitat:
						<input type="text" name="habitat" tabindex="82" style="width:600px;background-color:;" value="" />
					</div>
					<div style="padding:3px;">
						Associated Taxa:
						<input type="text" name="associatedtaxa" tabindex="84" style="width:600px;background-color:" value="" />
					</div>
					<div style="padding:3px;">
						Description of Organism:
						<input type="text" name="dynamicproperties" tabindex="86" style="width:600px;background-color:;" value="" />
					</div>
					<div style="padding:3px;">
						Notes:
						<input type="text" name="occurrenceremarks" tabindex="88" style="width:600px;background-color:;" value="" title="Occurrence Remarks" />
					</div>
					<div style="padding:3px;">
						<span>
							Reproductive Condition:
							<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="width:140px;background-color:;" value="" />
						</span>
						<span style="margin-left:30px;">
							Establishment Means:
							<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="width:140px;background-color:;" value="" />
						</span>
						<span style="margin-left:15px;">
							<input type="checkbox" name="cultivationstatus" tabindex="102" style="background-color:;" value="" />
							Cultivated
						</span>
					</div>
				</fieldset>
				<fieldset>
					<legend><b>Images</b></legend>
					<div style='padding:10px;width:550px;border:1px solid yellow;background-color:FFFF99;'>
						<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
							Select an image file located on your computer that you want to upload:
						</div>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
						<div>
							<input name='imgfile' type='file' size='70'/>
						</div>
						<div style="margin-left:10px;">Note: upload image size can not be greater than 1MB</div>
					</div>
					<div style="clear:both;margin:20px 0px 5px 10px;">
						<b>Caption:</b> 
						<input name="caption" type="text" size="40" value="" />
					</div>
					<div style="margin:0px 0px 5px 10px;">
						<b>Notes:</b> 
						<input name="notes" type="text" size="40" value="" />
					</div>
				</fieldset>
				<div>
					<input type="submit" name="action" value="Submit Observation" />
				</div>
			</form>
		<?php 
		}
	}
	else{
		echo "Please <a href='../../profile/index.php?refurl=/seinet/collections/editor/observationsubmit.php'>login</a>";
	}
	?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>

</body>
</html>