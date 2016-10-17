<?php
//TODO: add code to automatically select hide locality details when taxon/state match name on list
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ObservationSubmitManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/observatoinsubmit.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId  = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$clid  = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$recordedBy = array_key_exists("recordedby",$_REQUEST)?$_REQUEST["recordedby"]:0;

//Sanitation
if(!is_numeric($clid)) $clid = 0;

$obsManager = new ObservationSubmitManager();
$obsManager->setCollid($collId);
$collMap = $obsManager->getCollMap(); 
if(!$collId && $collMap) $collId = $collMap['collid']; 

$isEditor = 0;
$occid = 0;
if($collMap){
	if($isAdmin){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
	if($isEditor && $action == "Submit Observation"){
		$occid = $obsManager->addObservation($_POST);
	}
	if(!$recordedBy) $recordedBy = $obsManager->getUserName();
}
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Observation Submission</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript">
		<?php 
		$maxUpload = ini_get('upload_max_filesize');
		$maxUpload = str_replace("M", "000000", $maxUpload);
		if($maxUpload > 4000000) $maxUpload = 4000000;
		echo 'var maxUpload = '.$maxUpload.";\n";
		?>
	</script>
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.observationsubmit.js"></script>
</head>
<body>

	<?php
	$displayLeftMenu = (isset($collections_editor_observationsubmitMenu)?$collections_editor_observationsubmitMenu:false);
	include($SERVER_ROOT.'/header.php');
	if(isset($collections_editor_observationsubmitCrumbs)){
		echo "<div class='navpath'>";
		echo $collections_editor_observationsubmitCrumbs;
		echo "<b>Observation Submission</b>";
		echo "</div>";
	}
	?>
	<!-- inner text -->
	<div id="innertext">
		<h1><?php echo $collMap['collectionname']; ?></h1>
		<?php
		if($action || (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' && empty($_FILES) && empty($_POST))){
			?>
			<hr />
			<div style="margin:15px;font-weight:bold;">
				<?php
				if($occid){
					?>
					<div style="color:green;">
						SUCCESS: Image loaded successfully!
					</div>
					<div style="font:weight;font-size:120%;margin-top:10px;">
						Open  
						<a href="../individual/index.php?occid=<?php echo $occid; ?>" target="_blank">Occurrence Details Viewer</a> to see the new record 
					</div>
					<?php
				}
				$errArr = $obsManager->getErrorArr();
				if($errArr){
					echo '<div style="color:red;">';
					echo 'ERROR:<ol>';
					foreach($errArr as $e){
						echo '<li>'.$e.'</li>';
					}
					echo '</ol>';
					echo '</div>';
				}
				if(!$action){
					echo 'UNKNOWN ERROR: image file may have been larger than allowed by server. Try uploading a smaller image or have system administrator modify PHP configurations';
				}
				?>
			</div>
			<hr />
			<?php
		}
		if($isEditor){
			?>
			<form id='obsform' name='obsform' action='observationsubmit.php' method='post' enctype='multipart/form-data' onsubmit="return verifyObsForm(this)">
				<fieldset>
					<legend><b>Observation</b></legend>
					<div style="clear:both;" class="p1">
						<div style="float:left;">
							Scientific Name:
							<br/>
							<input type="text" id="sciname" name="sciname" maxlength="250" tabindex="2" style="width:390px;background-color:lightyellow;" />
							<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
						</div>
						<div style="float:left;">
							Author:
							<br/>
							<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="" value="" />
						</div>
					</div>
					<div style="clear:both;margin-left:10px;padding:3px 0px 0px 10px;">
						<span>Family:</span>
						<input type="text" name="family" size="30" maxlength="50" style="" tabindex="0" value="" />
					</div>
					<div style="clear:both;">
						<div style="float:left;">
							Observer:
							<br/>
							<input type="text" name="recordedby" maxlength="255" tabindex="14" style="width:250px;background-color:lightyellow;" value="<?php echo $recordedBy; ?>" />
						</div>
						<div style="float:left">
							Number:
							<br/>
							<input type="text" name="recordnumber" maxlength="45" tabindex="16" style="width:80px;" title="Observer Number, if observer uses a numbering system " />
						</div>
						<div style="float:left;">
							Date:
							<br/>
							<input type="text" id="eventdate" name="eventdate" tabindex="18" style="width:120px;background-color:lightyellow;" onchange="verifyDate(this);" title="format: yyyy-mm-dd" />
						</div>
						<div style="float:left;margin:15px 0px 0px 5px;cursor:pointer;" onclick="toggle('obsextradiv')">
							<img src="../../images/editplus.png" style="width:15px;" />
						</div>
					</div>
					<div id="obsextradiv" style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;display:none;">
						<div style="clear:both;margin-top:5px;">
							Associated Observers:<br />
							<input type="text" name="associatedcollectors" tabindex="20" maxlength="255" style="width:530px;" value="" />
						</div>
						<div style="float:left;margin:3px 0px 0px 10px;">
							Identified By:
							<input type="text" name="identifiedby" maxlength="255" tabindex="6" style="" value="" />
						</div>
						<div style="float:left;margin:3px 0px 0px 10px;">
							Date Identified:
							<input type="text" name="dateidentified" maxlength="45" tabindex="8" style="" value="" />
						</div>
						<div style="clear:both;padding:3px 0px 0px 0px;" >
							ID References:
							<input type="text" name="identificationreferences" tabindex="10" style="width:450px;" title="cf, aff, etc" />
						</div>
						<div style="clear:both;padding:3px 0px 0px 0px;" >
							ID Remarks:
							<input type="text" name="taxonremarks" tabindex="12" style="width:500px;" value="" />
						</div>
					</div>
				</fieldset>
				<fieldset>
					<legend><b>Locality</b></legend>
					<div style="clear:both;">
						<div style="float:left;">
							Country
							<br/>
							<input type="text" name="country" tabindex="32" style="width:150px;background-color:lightyellow;" value="" />
						</div>
						<div style="float:left;">
							State/Province
							<br/>
							<input type="text" name="stateprovince" tabindex="34" style="width:150px;background-color:lightyellow;" value="" />
						</div>
						<div style="float:left;">
							County
							<br/>
							<input type="text" name="county" tabindex="36" style="width:150px;" value="" />
						</div>
					</div>
					<div style="clear:both;margin:4px 0px 2px 0px;">
						Locality:<br />
						<input type="text" name="locality" tabindex="40" style="width:95%;background-color:lightyellow;" value="" />
					</div>
					<div style="clear:both;margin-bottom:5px;">
						<input type="checkbox" name="localitysecurity" tabindex="42" style="" value="1" title="Hide Locality Data from General Public" />
						Hide Locality Details from General Public (rare, threatened, or sensitive species)
					</div>
					<div style="clear:both;">
						<div style="float:left;">
							Latitude
							<br/>
							<input type="text" id="pointlat" name="decimallatitude" tabindex="44" maxlength="10" style="width:88px;background-color:lightyellow;" value="" onchange="verifyLatValue(this.form)" title="Decimal Format (eg 34.5436)" />
						</div>
						<div style="float:left;">
							Longitude
							<br/>
							<input type="text" id="pointlong" name="decimallongitude" tabindex="46" maxlength="13" style="width:88px;background-color:lightyellow;" value="" onchange="verifyLngValue(this.form)" title="Decimal Format (eg -112.5436)" />
							<span style="margin:15px 5px 0px 5px;cursor:pointer;" onclick="openMappingAid('obsform','decimallatitude','decimallongitude');">
								<img src="../../images/world.png" style="width:12px;" title="Coordinate Map Aid" />
							</span>
							<span style="margin:15px 2px 0px 2px;text-align:center;font-size:85%;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggle('dmsdiv');">
								DMS
							</span>
						</div>
						<div style="float:left;">
							Uncertainty(m)
							<br/>
							<input type="text" name="coordinateuncertaintyinmeters" tabindex="48" maxlength="10" style="width:80px;background-color:lightyellow;" onchange="inputIsNumeric(this, 'Lat/long uncertainty')" title="Uncertainty in Meters" />
						</div>
						<div style="float:left;">
							Datum
							<br/>
							<input type="text" name="geodeticdatum" tabindex="50" maxlength="255" style="width:80px;" value="" />
						</div>
						<div style="float:left;">
							Elev. (meters)
							<br/>
							<input type="text" name="minimumelevationinmeters" tabindex="52" maxlength="6" style="width:85px;" value="" onchange="verifyElevValue(this)" title="Minumum Elevation In Meters" />
							<span style="margin:15px 3px 0px 3px;text-align:center;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggle('elevftdiv');">
								ft.
							</span>
						</div>
						<div style="float:left;">
							Georeference Remarks
							<br/>
							<input type="text" name="georeferenceremarks" tabindex="70" maxlength="255" style="width:250px;" value="" />
						</div>
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
						Substrate:
						<input type="text" name="substrate" tabindex="82" style="width:600px;" value="" />
					</div>
					<div style="padding:3px;">
						Associated Taxa:
						<input type="text" name="associatedtaxa" tabindex="84" style="width:600px;background-color:" value="" />
					</div>
					<div style="padding:3px;">
						Description of Organism:
						<input type="text" name="verbatimattributes" tabindex="86" style="width:600px;" value="" />
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
				<?php 
				$clArr = $obsManager->getChecklists(); 
				if($clArr){
					?>
					<fieldset>
						<legend><b>Link to Checklist as Voucher</b></legend>
						Species List: 
						<select name='clid'>
							<option value="0">Select Checklist</option>
							<option value="0">------------------------------</option>
							<?php 
							foreach($clArr as $id => $clName){
								echo '<option value="'.$id.'" '.($id==$clid?'SELECTED':'').'>'.$clName.'</option>';
							}
							?>
						</select>
					</fieldset>
					<?php
				} 
				?>
				<fieldset>
					<legend><b>Images</b></legend>
					<div style='padding:10px;width:675px;border:1px solid yellow;background-color:FFFF99;'>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='4000000' />
						<div>
							Image 1: <input name='imgfile1' type='file' size='70' style="background-color:lightyellow;" onchange="verifyImageSize(this)" />
							<input type="button" value="Reset" onclick="document.obsform.imgfile1.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption1" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes1" type="text" style="width:275px;" />
							</span>
						</div>
						<div style="width:100%;cursor:pointer;text-align:right;margin-top:-15;" onclick="toggle('img2div')" title="Add a Second Image">
							<img src="../../images/add.png" style="width:15px;" />
						</div>
					</div>
					<div id="img2div" style='padding:10px;width:675px;border:1px solid yellow;background-color:FFFF99;display:none;'>
						<div>
							Image 2: <input name="imgfile2" type="file" size="70" onchange="verifyImageSize(this)" />
							<input type="button" value="Reset" onclick="document.obsform.imgfile2.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption2" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes2" type="text" style="width:275px;" />
							</span>
						</div>
						<div style="width:100%;cursor:pointer;text-align:right;margin-top:-15;" onclick="toggle('img3div')" title="Add a third Image">
							<img src="../../images/add.png" style="width:15px;" />
						</div>
					</div>
					<div id="img3div" style='padding:10px;width:675px;border:1px solid yellow;background-color:FFFF99;display:none;'>
						<div>
							Image 3: <input name="imgfile3" type="file" size="70" onchange="verifyImageSize(this)" />
							<input type="button" value="Reset" onclick="document.obsform.imgfile3.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption3" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes3" type="text" style="width:275px;" />
							</span>
						</div>
						<div style="width:100%;cursor:pointer;text-align:right;margin-top:-15;" onclick="toggle('img4div')" title="Add a forth Image">
							<img src="../../images/add.png" style="width:15px;" />
						</div>
					</div>
					<div id="img4div" style='padding:10px;width:700px;border:1px solid yellow;background-color:FFFF99;display:none;'>
						<div>
							Image 4: <input name="imgfile4" type="file" size="70" onchange="verifyImageSize(this)" />
							<input type="button" value="Reset" onclick="document.obsform.imgfile4.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption4" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes4" type="text" style="width:275px;" />
							</span>
						</div>
						<div style="width:100%;cursor:pointer;text-align:right;margin-top:-15;" onclick="toggle('img5div')" title="Add a fifth Image">
							<img src="../../images/add.png" style="width:15px;" />
						</div>
					</div>
					<div id="img5div" style='padding:10px;width:700px;border:1px solid yellow;background-color:FFFF99;display:none;'>
						<div>
							Image 5: <input name="imgfile5" type="file" size="70" onchange="verifyImageSize(this)" />
							<input type="button" value="Reset" onclick="document.obsform.imgfile5.value = ''">
						</div>
						<div style="margin:5px;">
							Caption: 
							<input name="caption5" type="text" style="width:200px;" />
							<span style="margin-left:20px;">
								Image Remarks: 
								<input name="notes5" type="text" style="width:275px;" />
							</span>
						</div>
					</div>
					<div style="margin-left:10px;">* Uploading web-ready images recommended. Upload image size can not be greater than <?php echo ($maxUpload/1000000); ?>MB</div>
				</fieldset>
				<div style="margin:10px;">
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
					<input type="submit" name="action" value="Submit Observation" />
					* Fields with background color are required  
				</div>
			</form>
			<?php 
		}
		else{
			echo 'You are authorized to submit to an observation. ';
			echo '<br/><b>Please contact an administrator to obtain the necessary permissions.</b> ';
		}
		?>
	</div>
<?php 	
	include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>