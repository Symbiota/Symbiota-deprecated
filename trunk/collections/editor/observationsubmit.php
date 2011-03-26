<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ObservationSubmitManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId  = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$obsManager = new ObservationSubmitManager($symbUid);
$status = "";

$okCollArr = Array();
if($isAdmin) $okCollArr[] = "all";
if(array_key_exists("CollAdmin",$userRights)) $okCollArr = array_merge($okCollArr,$userRights["CollAdmin"]);
if(array_key_exists("CollEditor",$userRights)) $okCollArr = array_merge($okCollArr,$userRights["CollEditor"]);
if($isAdmin || ($collId && in_array($collId,$okCollArr))){
	if($action == "Submit Observation"){
		$status = $obsManager->addObservation($_REQUEST,$symbUid);
	}
}
$okCollArr = $obsManager->getCollArr($okCollArr);

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Observation Submission</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui-1.8.11.custom.min.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.observationsubmit.js"></script>
</head>
<body>

<?php
	$displayLeftMenu = (isset($collections_editor_observationsubmitMenu)?$collections_editor_observationsubmitMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_editor_observationsubmitCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../../index.php'>Home</a> &gt; ";
		echo $collections_editor_observationsubmitCrumbs;
		echo "<b>Observation Submission</b>";
		echo "</div>";
	}
?>
	<!-- inner text -->
	<div id="innertext">
		<?php
		if($status){
			?>
			<hr />
			<div style="margin:10px;font-weight:bold;">
				<?php
				$occid = 0;
				if(is_numeric($status)){
					$occid = $status;
					$status = 'SUCCESS: Image loaded successfully!';
				}
				echo $status;
				if($occid){
					?>
					<br/>
					<div style="font:weight;font-size:120%;margin-top:10px;">
						Open  
						<a href="../individual/index.php?occid=<?php echo $occid; ?>">Occurrence Details Viewer</a> to see the new record 
					</div>
					<?php
				} 
				?>
			</div>
			<hr />
			<?php
		} 
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
						</div>
						<div style="clear:both;" class="p1">
							<span>
								<input type="text" id="sciname" name="sciname" maxlength="250" tabindex="2" style="width:390px;background-color:lightyellow;" value="" onchange="scinameChanged()" />
								<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="" value="" />
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
									<input type="text" name="recordedby" maxlength="255" tabindex="14" style="width:250px;background-color:lightyellow;" value="<?php echo $obsManager->getUsername(); ?>" />
								</span>
								<span style="margin-left:10px;">
									<input type="text" name="recordnumber" maxlength="45" tabindex="16" style="width:80px;" title="Observer Number, if observer uses a numbering system " />
								</span>
								<span style="margin-left:10px;">
									<input type="text" id="eventdate" name="eventdate" tabindex="18" style="width:120px;background-color:lightyellow;" onchange="verifyDate(this);" />
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
							<div id="idrefdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2" >
								ID References:
								<input type="text" name="identificationreferences" tabindex="10" style="width:450px;" title="cf, aff, etc" />
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
							<span style="margin-left:87px;">
								Uncertainty
							</span>
							<span style="margin-left:8px;">
								Datum
							</span>
							<span style="margin-left:43px;">
								Elev. (meters)
							</span>
							<span style="margin-left:35px;">
								Georeference Remarks
							</span>
						</div>
						<div>
							<span>
								<input type="text" id="pointlat" name="decimallatitude" tabindex="44" maxlength="10" style="width:88px;background-color:lightyellow;" value="" onchange="verifyLatValue(this)" title="Decimal Format (eg 34.5436)" />
							</span>
							<span>
								<input type="text" id="pointlong" name="decimallongitude" tabindex="46" maxlength="13" style="width:88px;background-color:lightyellow;" value="" onchange="verifyLngValue(this)" title="Decimal Format (eg -112.5436)" />
							</span>
							<span style="cursor:pointer;" onclick="openMappingAid('obsform','decimallatitude','decimallongitude');">
								<img src="../../images/world40.gif" style="width:12px;" title="Coordinate Map Aid" />
							</span>
							<span style="text-align:center;font-size:85%;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;margin:3px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggle('dmsdiv');">
								DMS
							</span>
							<span>
								<input type="text" name="coordinateuncertaintyinmeters" tabindex="48" maxlength="10" style="width:70px;" onchange="inputIsNumeric(this, 'Lat/long uncertainty')" title="Uncertainty in Meters" />
							</span>
							<span>
								<input type="text" name="geodeticdatum" tabindex="50" maxlength="255" style="width:80px;" value="" />
							</span>
							<span>
								<input type="text" name="minimumelevationinmeters" tabindex="52" maxlength="6" style="width:85px;" value="" onchange="verifyElevValue(this)" title="Minumum Elevation In Meters" />
							</span>
							<span style="text-align:center;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;margin:3px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggle('elevftdiv');">
								ft.
							</span>
							<span>
								<input type="text" name="georeferenceremarks" tabindex="70" maxlength="255" style="width:250px;" value="" />
							</span>
						</div>
						<div id="dmsdiv" style="display:none;float:left;padding:15px;background-color:lightyellow;border:1px solid yellow;width:270px;">
							<div>
								Latitude: 
								<input id="latdeg" style="width:35px;" title="Latitude Degree" />&deg; 
								<input id="latmin" style="width:50px;" title="Latitude Minutes" />' 
								<input id="latsec" style="width:50px;" title="Latitude Seconds" />&quot; 
								<select id="latns">
									<option>N</option>
									<option>S</option>
								</select>
							</div>
							<div>
								Longitude: 
								<input id="lngdeg" style="width:35px;" title="Longitude Degree" />&deg; 
								<input id="lngmin" style="width:50px;" title="Longitude Minutes" />' 
								<input id="lngsec" style="width:50px;" title="Longitude Seconds" />&quot; 
								<select id="lngew">
									<option>E</option>
									<option SELECTED>W</option>
								</select>
							</div>
							<div style="margin:5px;">
								<input type="button" value="Insert Lat/Long Values" onclick="insertLatLng(this.form)" />
							</div>
						</div>
						<div id="elevftdiv" style="display:none;float:right;padding:15px;background-color:lightyellow;border:1px solid yellow;width:180px;margin:0px 160px 10px 0px;">
							Elevation: 
							<input id="elevft" style="width:45px;" /> feet
							<div style="margin:5px;">
								<input type="button" value="Insert Elevation" onclick="insertElevFt(this.form)" />
							</div>
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
							<span title="e.g. sterile, flw, frt, flw/frt ">
								Reproductive Condition:
								<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="width:140px;" value="" />
							</span>
							<span style="margin-left:30px;" title="e.g. planted, seeded, garden excape, etc">
								Establishment Means:
								<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="width:140px;" value="" />
							</span>
							<span style="margin-left:15px;" title="Click if specimen was cultivated ">
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
							<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
							<div>
								Image 1: <input name='imgfile1' type='file' size='70' style="background-color:lightyellow;"/>
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
					<div style="margin:5px;">
						<b>Management:</b> 
						<select name="collid" style="background-color:FFFF99;">
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
							//Voucher Project: stuff
							?>
						</select>
					</div>
					<div style="margin:10px;">
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
			echo "Please <a href='../../profile/index.php?refurl=../collections/editor/observationsubmit.php'>login</a>";
		}
		?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>

</body>
</html>