<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:0;
$tabTarget = array_key_exists('tabtarget',$_REQUEST)?$_REQUEST['tabtarget']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$gotoMode = array_key_exists('gotomode',$_REQUEST)?$_REQUEST['gotomode']:1;
$carryLoc = array_key_exists('carryloc',$_REQUEST)?$_REQUEST['carryloc']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
if(!$action){
	$action = array_key_exists("gotonew",$_REQUEST)?$_REQUEST["gotonew"]:"";
}

$occManager = new OccurrenceEditorManager();
if($occId) $occManager->setOccId($occId); 
if($occId && !$collId){
	$collId = $occManager->getCollId();
}
$occArr = Array();
$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences
$statusStr = '';
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
	if($action){
		if(!$isEditor && $occManager->getObserverUid() == $symbUid){
			$isEditor = 1;
		}
		if($action == "Save Edits"){
			$statusStr = $occManager->editOccurrence($_REQUEST,$symbUid,$isEditor);
		}
		if($isEditor){
			if($action == "Add Record"){
				$statusStr = $occManager->addOccurrence($_REQUEST);
				$occId = $occManager->getOccId();
				if($gotoMode <= 2){
					if($gotoMode == 2){
						$occArr = $occManager->carryOverValues($_REQUEST);
					}
					$occId = 0;
				}
				elseif($gotoMode == 4){
					header('Location: ../individual/index.php?occid='.$occId);
				}
			}
			elseif($action == "Go to New Occurrence Record"){
				if($carryLoc){
					$occArr = $occManager->carryOverValues($_REQUEST);
				}
				$occId = 0;
			}
			elseif($action == "carryoverdup"){
				$occArr = $occManager->carryOverDuplicate($_REQUEST['targetoccid']);
				$occId = 0;
			}
			elseif($action == "Submit Image Edits"){
				$statusStr = $occManager->editImage($_REQUEST);
			}
			elseif($action == "Submit New Image"){
				$statusStr = $occManager->addImage($_REQUEST);
			}
			elseif($action == "Delete Image"){
				$removeImg = (array_key_exists("removeimg",$_REQUEST)?$_REQUEST["removeimg"]:0);
				$statusStr = $occManager->deleteImage($_REQUEST["imgid"], $removeImg);
			}
			elseif($action == "Remap Image"){
				$statusStr = $occManager->remapImage($_REQUEST["imgid"], $_REQUEST["occid"]);
			}
			elseif($action == "Add New Determination"){
				$statusStr = $occManager->addDetermination($_REQUEST);
			}
			elseif($action == "Submit Determination Edits"){
				$statusStr = $occManager->editDetermination($_REQUEST);
			}
			elseif($action == "Delete Determination"){
				$statusStr = $occManager->deleteDetermination($_REQUEST['detid']);
			}
			elseif($action == "Make Current"){
				$remapImages = array_key_exists('remapimages',$_REQUEST)?$_REQUEST['remapimages']:0;
				$statusStr = $occManager->makeDeterminationCurrent($_REQUEST['detid'],$remapImages);
			}
		}
	}
	if($occId && !$occArr){
		$occArr = $occManager->getOccurMap();
	}
	else if($collId){
		$occArr = array_merge($occArr,$occManager->setCollId($collId));
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Editor</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui-1.8.11.custom.min.js"></script>
	<script type="text/javascript">
		var collId = "<?php echo $collId; ?>";
		var countryArr = new Array(<?php $occManager->echoCountryList($collId);?>);
	</script>
	<script type="text/javascript" src="../../js/symb/collections.occurrenceeditor.js"></script>
</head>
<body>

	<?php
	$displayLeftMenu = (isset($collections_editor_occurrenceeditorMenu)?$collections_individual_occurrenceeditorMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_editor_occurrenceeditorCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../../index.php'>Home</a> &gt; ";
		echo $collections_editor_occurrenceeditorCrumbs;
		echo "<b>Occurrence Editor</b>";
		echo "</div>";
	}
	?>
	<!-- inner text -->
	<div id="innertext">
		<?php 
		if(!$symbUid){
			?>
			Please <a href="../../profile/index.php?refurl=<?php echo $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">LOGIN</a> to edit or add an occurrence record 
			<?php 
		}
		else{
			if($occArr){
				echo '<h2>'.$occArr['collectionname'].' ('.$occArr['institutioncode'].($occArr['collectioncode']?':'.$occArr['collectioncode']:'').')</h2>';
			}
			if($statusStr){
				?>
				<div id="statusdiv">
					<fieldset style="margin:10px;padding:10px;">
						<legend><b>Action Status</b></legend>
						<div style="margin:10px;color:red;">
							<?php echo $statusStr; ?>
						</div>
						<div style="margin:10px;">
							Go to <a href="../individual/index.php?occid=<?php echo $occManager->getOccId(); ?>">Occurrence Display Page</a>
						</div>
					</fieldset>
				</div>
				<?php 
			}
			if($occId || $isEditor){
				if(!$occId && !$collId){
					?>
					<div style="margin:10px;">
						Select the collection to which you wish to add a new record:
						<?php
						$collList = Array();
						if(!$isAdmin){
							if(array_key_exists("CollAdmin",$userRights)){
								$collList = $userRights["CollAdmin"];
							}
							if(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
								$collList = array_merge($collList,$userRights["CollEditor"]);
							}
						}
						$collList = $occManager->getCollectionList($collList);
						if($collList){
							echo '<ul>';
							foreach($collList as $cid => $cName){
								echo '<li><a href="occurrenceeditor.php?collid='.$cid.'">'.$cName.'</a></li>';
							}
							echo '</ul>';
						}
						else{
							echo '<h2>You are not authorized to add occurrence records</h2>';
						}
						?>
					</div>
					<?php 
				}
				else{
					?>
					<div id="occedittabs">
						<ul>
							<li>
								<a href="#occdiv" <?php echo (!$tabTarget||$tabTarget=='occdiv'?'class="selected"':''); ?> style="margin:0px 20px 0px 20px;">
									<?php echo ($occId?'Occurrence Data':'Add a New Occurrence Record'); ?>
								</a>
							</li>
							<?php
							if($occId && $isEditor){
								?>
								<li>
									<a href="#determdiv" <?php echo ($tabTarget=='determdiv'?'class="selected"':''); ?> style="margin:0px 20px 0px 20px;">
									Determination History
									</a>
								</li>
								<li>
									<a href="#imagediv" <?php echo ($tabTarget=='imagediv'?'class="selected"':''); ?> style="margin:0px 20px 0px 20px;">
										Images
									</a>
								</li>
								<?php
							}
							?>
						</ul>
						<div id="occdiv" style="">
							<form id="fullform" name="fullform" action="occurrenceeditor.php" method="post" onsubmit="return verifyFullForm(this)">
								<fieldset>
									<legend><b>Collector Info</b></legend>
									<div style="float:left;">
										<div style="clear:both;">
											<span style="margin-left:2px;">
												Catalog Number
											</span>
											<span style="margin-left:8px;">
												Occurrence ID
											</span>
											<span style="margin-left:32px;">
												Collector
											</span>
											<span style="margin-left:182px;">
												Number
											</span>
											<span style="margin-left:25px;">
												Date
											</span>
										</div>
										<div style="clear:both;">
											<span>
												<input type="text" name="catalognumber" tabindex="2" maxlength="32" style="width:100px;" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" onchange="catalogNumberChanged(this.value)" />
											</span>
											<span>
												<input type="text" name="occurrenceid" tabindex="4" maxlength="255" style="width:110px;" value="<?php echo array_key_exists('occurrenceid',$occArr)?$occArr['occurrenceid']:''; ?>" onchange="occurrenceIdChanged(this.value);" title="Global Unique Identifier (GUID)" />
											</span>
											<span>
												<input type="text" name="recordedby" tabindex="6" maxlength="255" style="width:220px;background-color:lightyellow;" value="<?php echo array_key_exists('recordedby',$occArr)?$occArr['recordedby']:''; ?>" onchange="fieldChanged('recordedby');" />
											</span>
											<span style="margin-left:10px;">
												<input type="text" name="recordnumber" tabindex="8" maxlength="45" style="width:60px;" value="<?php echo array_key_exists('recordnumber',$occArr)?$occArr['recordnumber']:''; ?>" onchange="fieldChanged('recordnumber');" />
											</span>
											<span style="margin-left:10px;">
												<input type="text" name="eventdate" tabindex="10" style="width:110px;" value="<?php echo array_key_exists('eventdate',$occArr)?$occArr['eventdate']:''; ?>" onchange="verifyDate(this);fieldChanged('eventdate');" />
											</span>
											<?php if(!$occId){ ?>
											<span style="margin-left:5px;cursor:pointer;" onclick="">
												<input type="button" value="Dups" tabindex="12" onclick="lookForDups(this.form);" />
											</span>
											<?php } ?>
										</div>
										<div style="clear:both;margin-top:5px;">
											Associated Collectors:<br />
											<input type="text" name="associatedcollectors" tabindex="14" maxlength="255" style="width:430px;" value="<?php echo array_key_exists('associatedcollectors',$occArr)?$occArr['associatedcollectors']:''; ?>" onchange="fieldChanged('associatedcollectors');" />
											<span style="margin-left:5px;cursor:pointer;" onclick="toggle('dateextradiv')">
												<img src="../../images/showedit.png" style="width:15px;" />
											</span>
											<div id="dupspan" style="display:none;float:right;width:150px;border:2px outset blue;padding:3px;font-weight:bold;">
												<span id="dupsearchspan">Looking for Dups...</span>
												<span id="dupnonespan" style="display:none;color:red;">No Dups Found</span>
												<span id="dupdisplayspan" style="display:none;color:red;">Displaying Dups</span>
											</div>
										</div>
										<div id="dateextradiv" style="padding:10px;margin:5px;border:1px solid gray;display:none;">
											<span>
												Verbatim Date:
												<input type="text" name="verbatimeventdate" tabindex="16" maxlength="255" style="width:120px;" value="<?php echo array_key_exists('verbatimeventdate',$occArr)?$occArr['verbatimeventdate']:''; ?>" onchange="fieldChanged('verbatimeventdate');" />
											</span>
											<span style="margin-left:15px;">
												MM/DD/YYYY:
												<span style="margin:8px;">
													<input type="text" name="month" tabindex="18" style="width:30px;" value="<?php echo array_key_exists('month',$occArr)?$occArr['month']:''; ?>" onchange="inputIsNumeric(this, 'Month');fieldChanged('month');" title="Numeric Month" />/
													<input type="text" name="day" tabindex="20" style="width:30px;" value="<?php echo array_key_exists('day',$occArr)?$occArr['day']:''; ?>" onchange="inputIsNumeric(this, 'Day');fieldChanged('day');" title="Numeric Day" />/
													<input type="text" name="year" tabindex="22" style="width:45px;" value="<?php echo array_key_exists('year',$occArr)?$occArr['year']:''; ?>" onchange="inputIsNumeric(this, 'Year');fieldChanged('year');" title="Numeric Year" />
												</span>
											</span>
											<span style="margin-left:15px;">
												Day of Year:
												<span style="margin:16px;">
													<input type="text" name="startdayofyear" tabindex="24" style="width:40px;" value="<?php echo array_key_exists('startdayofyear',$occArr)?$occArr['startdayofyear']:''; ?>" onchange="inputIsNumeric(this, 'Start Day of Year');fieldChanged('startdayofyear');" title="Start Day of Year" /> -
													<input type="text" name="enddayofyear" tabindex="26" style="width:40px;" value="<?php echo array_key_exists('enddayofyear',$occArr)?$occArr['enddayofyear']:''; ?>" onchange="inputIsNumeric(this, 'End Day of Year');fieldChanged('enddayofyear');" title="End Day of Year" />
												</span>
											</span>
										</div>
									</div>
								</fieldset>
								<fieldset>
									<legend><b>Latest Identification</b></legend>
									<div style="clear:both;">
										<span style="width:125px;">
											Scientific Name:
										</span>
										<span style="margin-left:315px;">
											Author:
										</span>
									</div>
									<div style="clear:both;">
										<span>
											<input type="text" id="ffsciname" name="sciname" maxlength="250" tabindex="28" style="width:390px;background-color:lightyellow;" value="<?php echo array_key_exists('sciname',$occArr)?$occArr['sciname']:''; ?>" <?php echo ($isEditor?'':'disabled '); ?> />
											<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
										</span>
										<span style="margin-left:10px;">
											<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="" value="<?php echo array_key_exists('scientificnameauthorship',$occArr)?$occArr['scientificnameauthorship']:''; ?>" onchange="fieldChanged('scientificnameauthorship');" <?php echo ($isEditor?'':'disabled '); ?> />
										</span>
										<?php if(!$isEditor) echo '<div style="color:red;margin-left:5px;">Note: Full editing permissions are needed to edit an identification</div>' ?>
										<div></div>
									</div>
									<div style="clear:both;padding:3px 0px 0px 10px;">
										<div style="float:left;">
											<span>ID Qualifier:</span>
											<input type="text" name="identificationqualifier" tabindex="30" size="5" style="" value="<?php echo array_key_exists('identificationqualifier',$occArr)?$occArr['identificationqualifier']:''; ?>" onchange="fieldChanged('identificationqualifier');" <?php echo ($isEditor?'':'disabled '); ?> />
										</div>
										<div style="float:left;margin-left:160px;">
											<span>Family:</span>
											<input type="text" name="family" size="30" maxlength="50" style="" tabindex="0" value="<?php echo array_key_exists('family',$occArr)?$occArr['family']:''; ?>" onchange="fieldChanged('family');" />
										</div>
									</div>
									<div style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;">
										<div style="float:left;">
											Identified By:
											<input type="text" name="identifiedby" maxlength="255" tabindex="32" style="" value="<?php echo array_key_exists('identifiedby',$occArr)?$occArr['identifiedby']:''; ?>" onchange="fieldChanged('identifiedby');" />
										</div>
										<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
											Date Identified:
											<input type="text" name="dateidentified" maxlength="45" tabindex="34" style="" value="<?php echo array_key_exists('dateidentified',$occArr)?$occArr['dateidentified']:''; ?>" onchange="fieldChanged('dateidentified');" />
										</div>
										<div style="float:left;margin-left:15px;cursor:pointer;" onclick="toggleIdDetails();">
											<img src="../../images/showedit.png" style="width:15px;" />
										</div>
									</div>
									<div style="clear:both;">
										<div id="idrefdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
											ID References:
											<input type="text" name="identificationreferences" tabindex="36" style="width:450px;" value="<?php echo array_key_exists('identificationreferences',$occArr)?$occArr['identificationreferences']:''; ?>" onchange="fieldChanged('identificationreferences');" />
										</div>
										<div id="idremdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
											ID Remarks:
											<input type="text" name="identificationremarks" tabindex="38" style="width:500px;" value="<?php echo array_key_exists('identificationremarks',$occArr)?$occArr['identificationremarks']:''; ?>" onchange="fieldChanged('identificationremarks');" />
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
											<input type="text" id="ffcountry" name="country" tabindex="40" style="width:150px;background-color:lightyellow;" value="<?php echo array_key_exists('country',$occArr)?$occArr['country']:''; ?>" onchange="fieldChanged('country');" />
										</span>
										<span>
											<input type="text" id="ffstate" name="stateprovince" tabindex="42" style="width:150px;background-color:lightyellow;" value="<?php echo array_key_exists('stateprovince',$occArr)?$occArr['stateprovince']:''; ?>" onchange="fieldChanged('stateprovince');" />
										</span>
										<span>
											<input type="text" id="ffcounty" name="county" tabindex="44" style="width:150px;" value="<?php echo array_key_exists('county',$occArr)?$occArr['county']:''; ?>" onchange="fieldChanged('county');" />
										</span>
									</div>
									<div style="margin:4px 0px 2px 0px;">
										Locality:<br />
										<input type="text" name="locality" tabindex="46" style="width:600px;background-color:lightyellow;" value="<?php echo array_key_exists('locality',$occArr)?$occArr['locality']:''; ?>" onchange="fieldChanged('locality');" />
									</div>
									<div style="margin-bottom:5px;">
										<?php $hasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]?1:0; ?>
										<input type="checkbox" name="localitysecurity" tabindex="0" style="" value="1" <?php echo $hasValue?"CHECKED":""; ?> onchange="fieldChanged('localitysecurity');toogleLocSecReason(this.form);" title="Hide Locality Data from General Public" />
										Locality Security
										<span id="locsecreason" style="margin-left:40px;display:<?php echo ($hasValue?'inline':'none') ?>">
											<?php $lsrValue = array_key_exists('localitysecurityreason',$occArr)?$occArr['localitysecurityreason']:''; ?>
											Security Reason Override: <input type="text" name="localitysecurityreason" tabindex="0" onchange="fieldChanged('localitysecurityreason');" value="<?php echo $lsrValue; ?>" title="Leave blank for default rare, threatened, or sensitive status" />
										</span>
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
										<span style="margin-left:70px;">
											Datum
										</span>
										<span style="margin-left:43px;">
											Elevation in Meters
										</span>
										<span style="margin-left:40px;">
											Verbatim Elevation
										</span>
									</div>
									<div>
										<span>
											<?php
											$latValue = "";
											if(array_key_exists("decimallatitude",$occArr) && $occArr["decimallatitude"] != "") {
												$latValue = $occArr["decimallatitude"];
											}
											?>
											<input type="text" name="decimallatitude" tabindex="50" maxlength="10" style="width:88px;background-color:lightyellow" value="<?php echo $latValue; ?>" onchange="inputIsNumeric(this, 'Decimal Latitude');fieldChanged('decimallatitude');" />
										</span>
										<span>
											<?php
											$longValue = "";
											if(array_key_exists("decimallongitude",$occArr) && $occArr["decimallongitude"] != "") {
												$longValue = $occArr["decimallongitude"];
											}
											$zoomValue = 5;
											if($latValue || $longValue){
												$zoomValue = 9;
											} 
											?>
											<input type="text" name="decimallongitude" tabindex="52" maxlength="13" style="width:88px;background-color:lightyellow" value="<?php echo $longValue; ?>" onchange="inputIsNumeric(this, 'Decimal Longitude');fieldChanged('decimallongitude');" />
										</span>
										<span>
											<input type="text" name="coordinateuncertaintyinmeters" tabindex="54" maxlength="10" style="width:70px;" value="<?php echo array_key_exists('coordinateuncertaintyinmeters',$occArr)?$occArr['coordinateuncertaintyinmeters']:''; ?>" onchange="inputIsNumeric(this, 'Coordinate Uncertainty');fieldChanged('coordinateuncertaintyinmeters');" title="Uncertainty in Meters" />
										</span>
										<span style="cursor:pointer;padding:3px;" onclick="openMappingAid(<?php echo ($latValue?$latValue:'0').','.($longValue?$longValue:'0').','.$zoomValue; ?>);">
											<img src="../../images/world40.gif" style="border:0px;width:13px;"  />
										</span>
										<span style="text-align:center;font-size:85%;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;margin:3px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggleCoordDiv();">
											UTM
										</span>
										<span>
											<input type="text" name="geodeticdatum" tabindex="56" maxlength="255" style="width:80px;" value="<?php echo array_key_exists('geodeticdatum',$occArr)?$occArr['geodeticdatum']:''; ?>" onchanged="fieldChanged('geodeticdatum');" />
										</span>
										<span>
											<input type="text" name="minimumelevationinmeters" tabindex="58" maxlength="6" style="width:55px;" value="<?php echo array_key_exists('minimumelevationinmeters',$occArr)?$occArr['minimumelevationinmeters']:''; ?>" onchange="inputIsNumeric(this, 'Minumum Elevation');fieldChanged('minimumelevationinmeters');" title="Minumum Elevation In Meters" />
										</span> -
										<span>
											<input type="text" name="maximumelevationinmeters" tabindex="60" maxlength="6" style="width:55px;" value="<?php echo array_key_exists('maximumelevationinmeters',$occArr)?$occArr['maximumelevationinmeters']:''; ?>" onchange="inputIsNumeric(this, 'Maximum Elevation');fieldChanged('maximumelevationinmeters');" title="Maximum Elevation In Meters" />
										</span>
										<span style="text-align:center;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;margin:3px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggle('elevaiddiv');">
											ft.
										</span>
										<span>
											<input type="text" name="verbatimelevation" tabindex="62" maxlength="255" style="width:100px;" value="<?php echo array_key_exists('verbatimelevation',$occArr)?$occArr['verbatimelevation']:''; ?>" onchange="fieldChanged('verbatimelevation');" title="" />
										</span>
										<span style="margin-left:5px;cursor:pointer;" onclick="toggle('locextradiv1');toggle('locextradiv2');">
											<img src="../../images/showedit.png" style="width:15px;" />
										</span>
									</div>
									<?php 
										$locExtraDiv1 = "none";
										if(array_key_exists("verbatimcoordinates",$occArr) && $occArr["verbatimcoordinates"]){
											$locExtraDiv1 = "block";
										}
										elseif(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]){
											$locExtraDiv1 = "block";
										}
										elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]){
											$locExtraDiv1 = "block";
										}
									?>
									<div>
										<div id="coordaiddiv" style="display:none;">
											<div style="float:left;padding:15px;background-color:lightyellow;border:1px solid yellow;width:150px;margin-bottom:10px;">
												Zone: <input id="utmzone" style="width:40px;" />
												<select id="zonens" title="Use hemisphere designator (e.g. 12N) rather than grid zone ">
													<option>N</option>
													<option>S</option>
												</select><br/>
												East: <input id="utmeast" type="text" style="width:100px;" /><br/>
												North: <input id="utmnorth" type="text" style="width:100px;" /><br/>
												<div style="margin:5px;">
													<input type="button" value="Insert UTM Values" onclick="insertUtm(this.form)" />
												</div>
											</div>
											<div style="float:left;padding:15px;background-color:lightyellow;border:1px solid yellow;width:270px;">
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
										</div>
										<div id="elevaiddiv" style="display:none;">
											<div style="float:right;padding:15px;background-color:lightyellow;border:1px solid yellow;width:180px;margin-bottom:10px;">
												Elevation: 
												<input id="elevminft" style="width:45px;" /> - 
												<input id="elevmaxft" style="width:45px;" /> feet
												<div style="margin:5px;">
													<input type="button" value="Insert Elevation" onclick="insertElevFt(this.form)" />
												</div>
											</div>
										</div>
									</div>
									<div id="locextradiv1" style="position:relative;clear:both;display:<?php echo $locExtraDiv1; ?>;">
										<div>
											<span style="">
												Verbatim Coordinates
											</span>
											<span style="margin-left:130px;">
												Georeferenced By
											</span>
											<span style="margin-left:52px;">
												Georeference Protocol
											</span>
										</div>
										<div>
											<span>
												<input type="text" name="verbatimcoordinates" tabindex="64" maxlength="255" style="width:250px;" value="<?php echo array_key_exists('verbatimcoordinates',$occArr)?$occArr['verbatimcoordinates']:''; ?>" onchange="fieldChanged('verbatimcoordinates');" title="" />
											</span>
											<span>
												<input type="text" name="georeferencedby" tabindex="66" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('georeferencedby',$occArr)?$occArr['georeferencedby']:''; ?>" onchange="fieldChanged('georeferencedby');" />
											</span>
											<span>
												<input type="text" name="georeferenceprotocol" tabindex="68" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('georeferenceprotocol',$occArr)?$occArr['georeferenceprotocol']:''; ?>" onchange="fieldChanged('georeferenceprotocol');" />
											</span>
										</div>
									</div>
									<?php 
										$locExtraDiv2 = "none";
										if(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]){
											$locExtraDiv2 = "block";
										}
										elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]){
											$locExtraDiv2 = "block";
										}
										elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]){
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
												<input type="text" name="georeferencesources" tabindex="70" maxlength="255" style="width:160px;" value="<?php echo array_key_exists('georeferencesources',$occArr)?$occArr['georeferencesources']:''; ?>" onchange="fieldChanged('georeferencesources');" />
											</span>
											<span>
												<input type="text" name="georeferenceverificationstatus" tabindex="72" maxlength="32" style="width:160px;" value="<?php echo array_key_exists('georeferenceverificationstatus',$occArr)?$occArr['georeferenceverificationstatus']:''; ?>" onchange="fieldChanged('georeferenceverificationstatus');" />
											</span>
											<span>
												<input type="text" name="georeferenceremarks" tabindex="74" maxlength="255" style="width:160px;" value="<?php echo array_key_exists('georeferenceremarks',$occArr)?$occArr['georeferenceremarks']:''; ?>" onchange="fieldChanged('georeferenceremarks');" />
											</span>
										</div>
									</div>
								</fieldset>
								<fieldset>
									<legend><b>Misc</b></legend>
									<div style="padding:3px;">
										Habitat:
										<input type="text" name="habitat" tabindex="82" style="width:600px;" value="<?php echo array_key_exists('habitat',$occArr)?$occArr['habitat']:''; ?>" onchange="fieldChanged('habitat');" />
									</div>
									<div style="padding:3px;">
										Associated Taxa:
										<input type="text" name="associatedtaxa" tabindex="84" style="width:600px;" value="<?php echo array_key_exists('associatedtaxa',$occArr)?$occArr['associatedtaxa']:''; ?>" onchange="fieldChanged('associatedtaxa');" />
									</div>
									<div style="padding:3px;">
										Description:
										<input type="text" name="dynamicproperties" tabindex="86" style="width:600px;" value="<?php echo array_key_exists('dynamicproperties',$occArr)?$occArr['dynamicproperties']:''; ?>" onchange="fieldChanged('dynamicproperties');" />
									</div>
									<div style="padding:3px;">
										Notes:
										<input type="text" name="occurrenceremarks" tabindex="88" style="width:600px;" value="<?php echo array_key_exists('occurrenceremarks',$occArr)?$occArr['occurrenceremarks']:''; ?>" onchange="fieldChanged('occurrenceremarks');" title="Occurrence Remarks" />
									</div>
								</fieldset>
								<fieldset>
									<legend><b>Curation</b></legend>
									<div style="padding:3px;">
										<span>
											Type Status:
											<input type="text" name="typestatus" tabindex="94" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('typestatus',$occArr)?$occArr['typestatus']:''; ?>" onchange="fieldChanged('typestatus');" />
										</span>
										<span style="margin-left:30px;">
											Disposition:
											<input type="text" name="disposition" tabindex="96" maxlength="32" style="width:200px;" value="<?php echo array_key_exists('disposition',$occArr)?$occArr['disposition']:''; ?>" onchange="fieldChanged('disposition');" />
										</span>
									</div>
									<div style="padding:3px;">
										<span>
											Reproductive Condition:
											<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="width:140px;" value="<?php echo array_key_exists('reproductivecondition',$occArr)?$occArr['reproductivecondition']:''; ?>" onchange="fieldChanged('reproductivecondition');" />
										</span>
										<span style="margin-left:30px;">
											Establishment Means:
											<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="width:140px;" value="<?php echo array_key_exists('establishmentmeans',$occArr)?$occArr['establishmentmeans']:''; ?>" onchange="fieldChanged('establishmentmeans');" />
										</span>
										<span style="margin-left:15px;">
											<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]?1:0; ?>
											<input type="checkbox" name="cultivationstatus" tabindex="102" style="" value="1" <?php echo $hasValue?'CHECKED':''; ?> onchange="fieldChanged('cultivationstatus');" />
											Cultivated
										</span>
									</div>
									<div style="padding:3px;">
										<span>
											Owner InstitutionCode:
											<input type="text" name="ownerinstitutioncode" tabindex="104" maxlength="32" style="width:150px;" value="<?php echo array_key_exists('ownerinstitutioncode',$occArr)?$occArr['ownerinstitutioncode']:''; ?>" onchange="fieldChanged('ownerinstitutioncode');" />
										</span>
										<span style="margin-left:30px;">
											Other Catalog Numbers:
											<input type="text" name="othercatalognumbers" tabindex="106" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('othercatalognumbers',$occArr)?$occArr['othercatalognumbers']:''; ?>" onchange="fieldChanged('othercatalognumbers');" />
										</span>
									</div>
								</fieldset>
								<fieldset>
									<legend><b>Other</b></legend>
									<div style="padding:3px;">
										<span>
											Basis of Record:
											<input type="text" name="basisofrecord" tabindex="108" maxlength="32" style="" value="<?php echo array_key_exists('basisofrecord',$occArr)?$occArr['basisofrecord']:''; ?>" onchange="fieldChanged('basisofrecord');" />
										</span>
										<span style="margin-left:20px;">
											Language:
											<input type="text" name="language" tabindex="110" maxlength="20" style="" value="<?php echo array_key_exists('language',$occArr)?$occArr['language']:''; ?>" onchange="fieldChanged('language');" />
										</span>
									</div>
								</fieldset>
								<div style="padding:10px;">
									<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
									<input type="hidden" name="editedfields" value="" />
									<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
									<?php if($occId){ ?>
										<div style="margin:15px 0px 20px 30px;">
											<input type="submit" name="submitaction" value="Save Edits" style="width:150px;" /><br/>
										</div>
										<div style="width:250px;border:1px solid black;background-color:lightyellow;padding:10px;margin:20px;">
											<input type="submit" name="gotonew" value="Go to New Occurrence Record" onclick="return verifyGotoNew(this.form);" /><br/>
											<input type="checkbox" name="carryloc" value="1" /> Carry over locality values<br/>
											* For data entry purposes only 
										</div>
									<?php }else{ ?>
										<div style="width:450px;border:1px solid black;background-color:lightyellow;padding:10px;margin:20px;">
											<input type="submit" name="submitaction" value="Add Record" style="width:150px;font-weight:bold;margin:10px;" />
											<div style="margin-left:15px;font-weight:bold;">
												Follow-up Action:
											</div>
											<div style="margin-left:20px;">
												<input type="radio" name="gotomode" value="1" <?php echo ($gotoMode>1?'':'CHECKED'); ?> /> Go to New Record<br/>
												<input type="radio" name="gotomode" value="2" <?php echo ($gotoMode==2?'CHECKED':''); ?> /> Go to New Record and Carryover Locality Information<br/> 
												<input type="radio" name="gotomode" value="3" <?php echo ($gotoMode==3?'CHECKED':''); ?> /> Remain on Editing Page (add images, determinations, etc)<br/>
												<input type="radio" name="gotomode" value="4" <?php echo ($gotoMode==4?'CHECKED':''); ?> /> Go to Specimen Display Page 
											</div>
										</div>
									<?php } ?>
								</div>
								<div style="clear:both;">&nbsp;</div>
							</form>
						</div>
						<?php
						if($occId && $isEditor){
							?>
							<div id="determdiv" style="">
								<div style="text-align:right;width:100%;">
									<img style="border:0px;width:12px;cursor:pointer;" src="../../images/add.png" onclick="toggle('newdetdiv');" title="Add New Determination" />
								</div>
								<div id="newdetdiv" style="display:none;">
									<form name="detaddform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetAddForm(this)">
										<fieldset>
											<legend><b>Add a New Determination</b></legend>
											<div style='margin:3px;'>
												<b>Identification Qualifier:</b>
												<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
											</div>
											<div style='margin:3px;'>
												<b>Scientific Name:</b> 
												<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" />
												<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
												<input type="hidden" name="family" value="" />
											</div>
											<div style='margin:3px;'>
												<b>Author:</b> 
												<input type="text" name="scientificnameauthorship" style="width:200px;" />
											</div>
											<div style='margin:3px;'>
												<b>Determiner:</b> 
												<input type="text" name="identifiedby" style="background-color:lightyellow;width:200px;" />
											</div>
											<div style='margin:3px;'>
												<b>Date:</b> 
												<input type="text" name="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
											</div>
											<div style='margin:3px;'>
												<b>Reference:</b> 
												<input type="text" name="identificationreferences" style="width:350px;" />
											</div>
											<div style='margin:3px;'>
												<b>Notes:</b> 
												<input type="text" name="identificationremarks" style="width:350px;" />
											</div>
											<div style='margin:15px;'>
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
												<div style="float:left;">
													<input type="submit" name="submitaction" value="Add New Determination" />
												</div>
												<div style="float:left;margin-left:30px;">
													<input type="checkbox" name="makecurrent" value="1" /> Make this the current determination <br/>
													<input type="checkbox" name="remapimages" value="1" /> Remap images to new taxonomic name
												</div>
											</div>
										</fieldset>
									</form>
								</div>
								<div class="fieldset">
									<div class="legend"><b>Determination History</b></div>
									<?php
									if(array_key_exists('dets',$occArr)){
										$detArr = $occArr['dets'];
										foreach($detArr as $detId => $detRec){
											if(!array_key_exists('iscurrent',$detRec)){
												?>
												<div style="float:right;cursor:pointer;margin:10px;" onclick="toggle('editdetdiv-<?php echo $detId;?>');" title="Edit Determination">
													<img style="border:0px;width:12px;" src="../../images/edit.png" />
												</div>
												<?php 
											} 
											?>
											<div id="detdiv-<?php echo $detId;?>">
												<div>
													<?php 
													if($detRec['identificationqualifier']) echo $detRec['identificationqualifier'].' ';
													echo '<b><i>'.$detRec['sciname'].'</i></b> '.$detRec['scientificnameauthorship'];
													if(array_key_exists('iscurrent',$detRec)){
														echo '<span style="margin-left:10px;color:red;">CURRENT DETERMINATION</span>';	
													}
													?>
												</div>
												<div style='margin:3px 0px 0px 15px;'>
													<b>Determiner:</b> <?php echo $detRec['identifiedby']; ?>
													<span style="margin-left:40px;">
														<b>Date:</b> <?php echo $detRec['dateidentified']; ?>
													</span>
												</div>
												<?php 
												if($detRec['identificationreferences']){
													?>
													<div style='margin:3px 0px 0px 15px;'>
														<b>Reference:</b> <?php echo $detRec['identificationreferences']; ?>
													</div>
													<?php 
												}
												if($detRec['identificationremarks']){
													?>
													<div style='margin:3px 0px 0px 15px;'>
														<b>Notes:</b> <?php echo $detRec['identificationremarks']; ?>
													</div>
													<?php 
												}
												?>
											</div>
											<?php if(!array_key_exists('iscurrent',$detRec)){ ?>
											<div id="editdetdiv-<?php echo $detId;?>" style="display:none;">
												<fieldset>
													<form name="deteditform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetEditForm(this);">
														<legend><b>Edit Determination</b></legend>
														<div style='margin:3px;'>
															<b>Identification Qualifier:</b>
															<input type="text" name="identificationqualifier" value="<?php echo $detRec['identificationqualifier']; ?>" title="e.g. cf, aff, etc" />
														</div>
														<div style='margin:3px;'>
															<b>Scientific Name:</b> 
															<input type="text" id="defsciname" name="sciname" value="<?php echo $detRec['sciname']; ?>" style="background-color:lightyellow;width:350;" />
															<input type="hidden" id="deftidtoadd" name="tidtoadd" value="" />
														</div>
														<div style='margin:3px;'>
															<b>Author:</b> 
															<input type="text" name="scientificnameauthorship" value="<?php echo $detRec['scientificnameauthorship']; ?>" style="width:200;" />
														</div>
														<div style='margin:3px;'>
															<b>Determiner:</b> 
															<input type="text" name="identifiedby" value="<?php echo $detRec['identifiedby']; ?>" style="background-color:lightyellow;width:200;" />
														</div>
														<div style='margin:3px;'>
															<b>Date:</b> 
															<input type="text" name="dateidentified" value="<?php echo $detRec['dateidentified']; ?>" style="background-color:lightyellow;" />
														</div>
														<div style='margin:3px;'>
															<b>Reference:</b> 
															<input type="text" name="identificationreferences" value="<?php echo $detRec['identificationreferences']; ?>" style="width:350;" />
														</div>
														<div style='margin:3px;'>
															<b>Notes:</b> 
															<input type="text" name="identificationremarks" value="<?php echo $detRec['identificationremarks']; ?>" style="width:350;" />
														</div>
														<div style='margin:3px;'>
															<b>Sort Sequence:</b> 
															<input type="text" name="sortsequence" value="<?php echo $detRec['sortsequence']; ?>" style="width:40px;" />
														</div>
														<div style='margin:3px;margin:15px;'>
															<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
															<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
															<input type="submit" name="submitaction" value="Submit Determination Edits" />
														</div>
													</form>
													<form name="detdelform" action="occurrenceeditor.php" method="post" onsubmit="return window.confirm('Are you sure you want to delete this specimen determination?');">
														<div style="padding:15px;background-color:lightblue;width:155px;margin:15px;">
															<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
															<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
															<input type="submit" name="submitaction" value="Delete Determination" />
														</div>
													</form>
													<form name="detdelform" action="occurrenceeditor.php" method="post" onsubmit="return window.confirm('Are you sure you want to make this the most current determination?');">
														<div style="padding:15px;background-color:lightgreen;width:280px;margin:15px;">
															<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
															<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
															<input type="submit" name="submitaction" value="Make Current" /><br/>
															<input type="checkbox" name="remapimages" value="1" CHECKED /> Remap images to this taxonomic name
														</div>
													</form>
												</fieldset>
											</div>
											<?php } ?>
											<hr style='margin:10px 0px 10px 0px;' />
											<?php 
										}
									}
									else{
										?>
										<div style="font-weight:bold;margin:10px 0px 20px 20px;font-size:120%;">There are no historic annotations for this specimen</div>
										<?php 
									}
									?>
								</div>
							</div>
							<div id="imagediv" style="">
								<div style="float:right;cursor:pointer;" onclick="toggle('addimgdiv');" title="Add a New Image">
									<img style="border:0px;width:12px;" src="../../images/add.png" />
								</div>
								<div id="addimgdiv" style="display:none;">
									<form name="imgnewform" action="occurrenceeditor.php" method="post" enctype="multipart/form-data" onsubmit="return verifyImgAddForm(this);">
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
													<div style="margin-left:10px;">
														<input type="checkbox" name="createlargeimg" value="1" /> Create a large version of image, when applicable
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
											<div style="margin:10px 0px 10px 20px;">
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
												<input type="hidden" name="tid" value="<?php echo $occArr["tidinterpreted"]; ?>" />
												<input type="hidden" name="institutioncode" value="<?php echo $occArr["institutioncode"]; ?>" />
												<input type="submit" name="submitaction" value="Submit New Image" />
											</div>
										</fieldset>
									</form>
								</div>
								<div style="clear:both;">
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
												<td style="text-align:left;padding:10px;">
													<div style="float:right;cursor:pointer;" onclick="toggle('img<?php echo $imgId; ?>editdiv');" title="Edit Image MetaData">
														<img style="border:0px;width:12px;" src="../../images/edit.png" />
													</div>
													<div style="margin-top:30px;">
														<div>
															<b>Caption:</b> 
															<?php echo $imgArr["caption"]; ?>
														</div>
														<div>
															<b>Photographer:</b> 
															<?php 
															if($imgArr["photographeruid"]){
																$pArr = $occManager->getPhotographerArr();
																echo $pArr[$imgArr["photographeruid"]];
															} 
															?>
														</div>
														<div>
															<b>Notes:</b> 
															<?php echo $imgArr["notes"]; ?>
														</div>
														<div>
															<b>Copyright:</b>
															<?php echo $imgArr["copyright"]; ?>
														</div>
														<div>
															<b>Source Webpage:</b>
															<a href="<?php echo $imgArr["sourceurl"]; ?>">
																<?php echo $imgArr["sourceurl"]; ?>
															</a>
														</div>
														<div>
															<b>Web URL: </b>
															<a href="<?php echo $imgArr["url"]; ?>">
																<?php echo $imgArr["url"]; ?>
															</a>
														</div>
														<div>
															<b>Large Image URL: </b>
															<a href="<?php echo $imgArr["origurl"]; ?>">
																<?php echo $imgArr["origurl"]; ?>
															</a>
														</div>
														<div>
															<b>Thumbnail URL: </b>
															<a href="<?php echo $imgArr["tnurl"]; ?>">
																<?php echo $imgArr["tnurl"]; ?>
															</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<div id="img<?php echo $imgId; ?>editdiv" style="display:none;clear:both;">
														<form name="img<?php echo $imgId; ?>editform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgEditForm(this);">
															<fieldset>
																<legend><b>Edit Image Data</b></legend>
																<div>
																	<b>Caption:</b><br/> 
																	<input name="caption" type="text" value="<?php echo $imgArr["caption"]; ?>" style="width:300px;" />
																</div>
																<div>
																	<b>Photographer:</b><br/> 
																	<select name='photographeruid' name='photographeruid'>
																		<option value="">Select Photographer</option>
																		<option value="">---------------------------------------</option>
																		<?php
																		$pArr = $occManager->getPhotographerArr();
																		foreach($pArr as $id => $uname){
																			echo "<option value='".$id."' ".($id == $imgArr["photographeruid"]?"SELECTED":"").">";
																			echo $uname;
																			echo "</option>\n";
																		}
																		?>
																	</select>
																</div>
																<div>
																	<b>Notes:</b><br/>
																	<input name="notes" type="text" value="<?php echo $imgArr["notes"]; ?>" style="width:90%;" />
																</div>
																<div>
																	<b>Copyright:</b><br/>
																	<input name="copyright" type="text" value="<?php echo $imgArr["copyright"]; ?>" style="width:90%;" />
																</div>
																<div>
																	<b>Source Webpage:</b><br/>
																	<input name="sourceurl" type="text" value="<?php echo $imgArr["sourceurl"]; ?>" style="width:90%;" />
																</div>
																<div>
																	<b>Web URL: </b><br/>
																	<input name="url" type="text" value="<?php echo $imgArr["url"]; ?>" style="width:90%;" />
																	<?php if(stripos($imgArr["url"],$imageRootUrl) === 0){ ?>
																		<div style="margin-left:10px;">
																			<input type="checkbox" name="renameweburl" value="1" />
																			Rename web image file on server to match above edit
																		</div>
																		<input name='oldurl' type='hidden' value='<?php echo $imgArr["url"];?>' />
																	<?php } ?>
																</div>
																<div>
																	<b>Large Image URL: </b><br/>
																	<input name="origurl" type="text" value="<?php echo $imgArr["origurl"]; ?>" style="width:90%;" />
																	<?php if(stripos($imgArr["origurl"],$imageRootUrl) === 0){ ?>
																		<div style="margin-left:10px;">
																			<input type="checkbox" name="renameorigurl" value="1" />
																			Rename large image file on server to match above edit
																		</div>
																		<input name='oldorigurl' type='hidden' value='<?php echo $imgArr["origurl"];?>' />
																	<?php } ?>
																</div>
																<div>
																	<b>Thumbnail URL: </b><br/>
																	<input name="tnurl" type="text" value="<?php echo $imgArr["tnurl"]; ?>" style="width:90%;" />
																	<?php if(stripos($imgArr["tnurl"],$imageRootUrl) === 0){ ?>
																		<div style="margin-left:10px;">
																			<input type="checkbox" name="renametnurl" value="1" />
																			Rename thumbnail file on server to match above edit
																		</div>
																		<input name='oldtnurl' type='hidden' value='<?php echo $imgArr["tnurl"];?>' />
																	<?php } ?>
																</div>
																<div style="margin-top:10px;">
																	<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
																	<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
																	<input type="submit" name="submitaction" value="Submit Image Edits" />
																</div>
															</fieldset>
														</form>
														<form name="img<?php echo $imgId; ?>delform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgDelForm(this);">
															<fieldset>
																<legend><b>Delete Image</b></legend>
																<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
																<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
																<input name="removeimg" type="checkbox" value="1" CHECKED /> Remove image from server 
																<div style="margin-left:20px;">
																	(Note: leaving unchecked removes image from database w/o removing from server)
																</div>
																<input type="submit" name="submitaction" value="Delete Image" />
															</fieldset>
														</form>
														<form name="img<?php echo $imgId; ?>remapform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgRemapForm(this);">
															<fieldset>
																<legend><b>Remap to Another Specimen</b></legend>
																<div>
																	<b>Occurrence Record #:</b> 
																	<input id="imgoccid" name="occid" type="text" value="<?php  echo $imgArr["occid"];?>" />
																	<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('imgoccid')">
																		Open Occurrence Linking Aid
																	</span>
																</div>
																<div style="margin-left:20px;">
																	* Leave Occurrence Record Number blank to completely remove mapping to a specimen record <br/>
																	<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
																	<input type="submit" name="submitaction" value="Remap Image" />
																</div>
															</fieldset>
														</form>
													</div>
													<hr/>
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
							<?php
						}
						?>
					</div>
				<?php 
				}
			}
			else{
				echo '<h2>You are not authorized to add occurrence records</h2>';
			}
		}
		?>
	</div>
<?php 	
include($serverRoot.'/footer.php');
?>

</body>
</html>