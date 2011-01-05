<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ObservationSubmitManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId  = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$obsManager = new ObservationSubmitManager($symbUid);

$okCollArr = Array();
if($isAdmin) $okCollArr[] = "all";
if(array_key_exists("CollAdmin",$userRights)) $okCollArr = array_merge($okCollArr,$userRights["CollAdmin"]);
if(array_key_exists("CollEditor",$userRights)) $okCollArr = array_merge($okCollArr,$userRights["CollEditor"]);
if($isAdmin || ($collId && in_array($collId,$okCollArr))){
	if($action == "Submit Observation"){
		$obsManager->addObservation($_REQUEST);
	}
}
$okCollArr = $obsManager->getCollArr($okCollArr);

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Observation Submission</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
    <link rel="stylesheet" href="../../css/jqac.css" type="text/css">
	<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.autocomplete-1.4.2.js"></script>
	<script language="javascript" src="../../js/collections.observationsubmit.js"></script>
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
		if($okCollArr){
			?>
			<form id='obsform' name='obsform' action='observationsubmit.php' method='post' enctype='multipart/form-data' onsubmit="return submitObsForm(this)">
				<fieldset>
					<legend><b>Observation</b></legend>
					<div style="clear:both;" class="p1">
						<span style="width:125px;">
							Scientific Name:
						</span>
						<span style="margin-left:315px;">
							Author:
						</span>
						<span style="margin-left:120px;">
							ID Qualifier:
						</span>
					</div>
					<div style="clear:both;" class="p1">
						<span>
							<input type="text" name="sciname" maxlength="250" tabindex="2" style="width:390px;background-color:lightyellow;" value="" onfocus="initTaxonList(this)" onchange="scinameChanged()" autocomplete="off" />
							<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
						</span>
						<span style="margin-left:10px;">
							<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="" value="" />
						</span>
						<span style="margin-left:10px;">
							<input type="text" name="identificationqualifier" tabindex="0" size="5" style="" value="" />
						</span>
					</div>
					<div style="clear:both;margin-left:10px;padding:3px 0px 0px 10px;">
						<span>Family:</span>
						<input type="text" name="family" size="30" maxlength="50" style="" tabindex="0" value="" />
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
								<input type="text" name="recordedby" maxlength="255" tabindex="14" style="width:250px;background-color:lightyellow;" value="<?php echo $obsManager->getUsername(); ?>" onfocus="verifySciName()" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="recordnumber" maxlength="45" tabindex="16" style="width:80px;" value="" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" id="eventdate" name="eventdate" tabindex="18" style="width:120px;background-color:lightyellow;" value="" onchange="verifyDate(this);" />
							</span>
							<span style="margin-left:5px;cursor:pointer;" onclick="toggle('obsextradiv')">
								<img src="../../images/showedit.png" style="width:15px;" />
							</span>
						</div>
					</div>
					<div id="obsextradiv" style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;display:none;">
						<div style="clear:both;margin-top:5px;">
							Associated Observers:<br />
							<input type="text" name="associatedcollectors" tabindex="20" maxlength="255" style="width:430px;" value="" />
						</div>
						<div style="float:left;">
							Identified By:
							<input type="text" name="identifiedby" maxlength="255" tabindex="6" style="" value="" />
						</div>
						<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
							Date Identified:
							<input type="text" name="dateidentified" maxlength="45" tabindex="8" style="" value="" />
						</div>
						<div id="idrefdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
							ID References:
							<input type="text" name="identificationreferences" tabindex="10" style="width:450px;" value="" />
						</div>
						<div id="taxremdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
							ID Remarks:
							<input type="text" name="taxonremarks" tabindex="12" style="width:500px;" value="" />
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
							<input type="text" name="country" tabindex="32" style="width:150px;background-color:lightyellow;" value="" />
						</span>
						<span>
							<input type="text" name="stateprovince" tabindex="34" style="width:150px;background-color:lightyellow;" value="" />
						</span>
						<span>
							<input type="text" name="county" tabindex="36" style="width:150px;" value="" />
						</span>
					</div>
					<div style="margin:4px 0px 2px 0px;">
						Locality:<br />
						<input type="text" name="locality" tabindex="40" style="width:600px;background-color:lightyellow;" value="" />
					</div>
					<div style="margin-bottom:5px;">
						<input type="checkbox" name="localitysecurity" tabindex="42" style="" value="1" title="Hide Locality Data from General Public" />
						Hide Locality Details from General Public (rare, threatened, or sensitive species)
					</div>
					<div>
						<span style="">
							Latitude
						</span>
						<span style="margin-left:45px;">
							Longitude
						</span>
						<span style="margin-left:50px;">
							Uncertainty
						</span>
						<span style="margin-left:8px;">
							Datum
						</span>
						<span style="margin-left:43px;">
							Elev. (meters)
						</span>
						<span style="margin-left:9px;">
							Georeference Remarks
						</span>
					</div>
					<div>
						<span>
							<input type="text" id="pointlat" name="decimallatitude" tabindex="44" maxlength="10" style="width:88px;background-color:lightyellow;" value="" onchange="inputIsNumeric(this, 'Decimal Latitude')" title="Decimal Format (eg 34.5436)" />
						</span>
						<span>
							<input type="text" id="pointlong" name="decimallongitude" tabindex="46" maxlength="13" style="width:88px;background-color:lightyellow;" value="" onchange="inputIsNumeric(this, 'Decimal Longitude')" title="Decimal Format (eg -112.5436)" />
						</span>
						<span style="cursor:pointer;" onclick="openPointMap();">
							<img src="../../images/world40.gif" style="width:12px;" title="Coordinate Map Aid" />
						</span>
						<span>
							<input type="text" name="coordinateuncertaintyinmeters" tabindex="48" maxlength="10" style="width:70px;" value="" onchange="inputIsNumeric(this, 'Coordinate Uncertainty')" title="Uncertainty in Meters" />
						</span>
						<span>
							<input type="text" name="geodeticdatum" tabindex="50" maxlength="255" style="width:80px;" value="" />
						</span>
						<span>
							<input type="text" name="minimumelevationinmeters" tabindex="52" maxlength="6" style="width:85px;" value="" onchange="inputIsNumeric(this, 'Minumum Elevation')" title="Minumum Elevation In Meters" />
						</span>
						<span>
							<input type="text" name="georeferenceremarks" tabindex="70" maxlength="255" style="width:250px;" value="" />
						</span>
					</div>
				</fieldset>
				<fieldset>
					<legend><b>Misc</b></legend>
					<div style="padding:3px;">
						Habitat:
						<input type="text" name="habitat" tabindex="82" style="width:600px;" value="" />
					</div>
					<div style="padding:3px;">
						Associated Taxa:
						<input type="text" name="associatedtaxa" tabindex="84" style="width:600px;background-color:" value="" />
					</div>
					<div style="padding:3px;">
						Description of Organism:
						<input type="text" name="dynamicproperties" tabindex="86" style="width:600px;" value="" />
					</div>
					<div style="padding:3px;">
						General Notes:
						<input type="text" name="occurrenceremarks" tabindex="88" style="width:600px;" value="" title="Occurrence Remarks" />
					</div>
					<div style="padding:3px;">
						<span>
							Reproductive Condition:
							<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="width:140px;" value="" />
						</span>
						<span style="margin-left:30px;">
							Establishment Means:
							<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="width:140px;" value="" />
						</span>
						<span style="margin-left:15px;">
							<input type="checkbox" name="cultivationstatus" tabindex="102" style="" value="" />
							Cultivated
						</span>
					</div>
				</fieldset>
				<fieldset>
					<legend><b>Images</b></legend>
					<div style="margin-left:10px;">Note: upload image size can not be greater than 1MB</div>
					<div style='padding:10px;width:675px;border:1px solid yellow;background-color:FFFF99;'>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='1200000' />
						<div>
							Image 1: <input name='imgfile1' type='file' size='70'/>
							<input type="button" value="Reset" style="background-color:lightyellow;" onclick="document.obsform.imgfile1.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes" type="text" style="width:275px;" />
							</span>
						</div>
						<div style="width:100%;cursor:pointer;text-align:right;margin-top:-15;" onclick="toggle('img2div')" title="Add a Second Image">
							<img src="../../images/add.png" style="width:15px;" />
						</div>
					</div>
					<div id="img2div" style='padding:10px;width:675px;border:1px solid yellow;background-color:FFFF99;display:none;'>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
						<div>
							Image 2: <input name='imgfile2' type='file' size='70'/>
							<input type="button" value="Reset" onclick="document.obsform.imgfile1.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes" type="text" style="width:275px;" />
							</span>
						</div>
						<div style="width:100%;cursor:pointer;text-align:right;margin-top:-15;" onclick="toggle('img3div')" title="Add a third Image">
							<img src="../../images/add.png" style="width:15px;" />
						</div>
					</div>
					<div id="img3div" style='padding:10px;width:675px;border:1px solid yellow;background-color:FFFF99;display:none;'>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
						<div>
							Image 3: <input name='imgfile3' type='file' size='70'/>
							<input type="button" value="Reset" onclick="document.obsform.imgfile1.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes" type="text" style="width:275px;" />
							</span>
						</div>
					</div>
				</fieldset>
				<div>
					<b>Observation Project:</b> 
					<select name="collid">
						<option value="">Select an Observation Project</option>
						<option value="">-----------------------------------</option>
						<?php 
						foreach($okCollArr as $collId => $collName){
							$selectStr = "";
							if(strpos($collName,"[default]")){
								$collName = str_replace("[default]","",$collName);
								$selectStr = "SELECTED";
							}
							echo "<option value='".$collId."' ".$selectStr.">".$collName."</option>\n";
						}
						?>
					</select>
				</div>
				<div>
					<input type="submit" name="action" value="Submit Observation" />
					* Fields with background color are required  
				</div>
			</form>
		<?php 
		}
		else{
			echo "There are no observation projects that you are authorized to submit to. Please contact an administrator to obtain access. ";
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