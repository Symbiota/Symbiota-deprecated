<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceGeorefTools.php');

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$submitAction = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$qCountry = array_key_exists('qcountry',$_REQUEST)?$_REQUEST['qcountry']:'';
$qState = array_key_exists('qstate',$_REQUEST)?$_REQUEST['qstate']:'';
$qCounty = array_key_exists('qcounty',$_REQUEST)?$_REQUEST['qcounty']:'';
$qLocality = array_key_exists('qlocality',$_REQUEST)?$_REQUEST['qlocality']:'';
$qVStatus = array_key_exists('qvstatus',$_REQUEST)?$_REQUEST['qvstatus']:'';

$latDeg = array_key_exists('latdeg',$_POST)?$_POST['latdeg']:'';
$latMin = array_key_exists('latmin',$_POST)?$_POST['latmin']:'';
$latSec = array_key_exists('latsec',$_POST)?$_POST['latsec']:'';
$decimalLatitude = array_key_exists('decimallatitude',$_POST)?$_POST['decimallatitude']:'';
$latNS = array_key_exists('latns',$_POST)?$_POST['latns']:'';

$lngDeg = array_key_exists('lngdeg',$_POST)?$_POST['lngdeg']:'';
$lngMin = array_key_exists('lngmin',$_POST)?$_POST['lngmin']:'';
$lngSec = array_key_exists('lngsec',$_POST)?$_POST['lngsec']:'';
$decimalLongitude = array_key_exists('decimallongitude',$_POST)?$_POST['decimallongitude']:'';
$lngEW = array_key_exists('lngew',$_POST)?$_POST['lngew']:'';

$coordinateUncertaintyInMeters = array_key_exists('coordinateuncertaintyinmeters',$_POST)?$_POST['coordinateuncertaintyinmeters']:'';
$geodeticDatum = array_key_exists('geodeticdatum',$_POST)?$_POST['geodeticdatum']:'';
$georeferenceSources = array_key_exists('georeferencesources',$_POST)?$_POST['georeferencesources']:'';
$georeferenceRemarks = array_key_exists('georeferenceremarks',$_POST)?$_POST['georeferenceremarks']:'';
$georeferenceVerificationStatus = array_key_exists('georeferenceverificationstatus',$_POST)?$_POST['georeferenceverificationstatus']:'';
$minimumElevationInMeters = array_key_exists('minimumelevationinmeters',$_POST)?$_POST['minimumelevationinmeters']:'';
$maximumElevationInMeters = array_key_exists('maximumelevationinmeters',$_POST)?$_POST['maximumelevationinmeters']:'';

$geoManager = new OccurrenceGeorefTools();
$geoManager->setCollId($collId);

$editor = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editor = true;
}

$statusStr = '';
$localArr;
if($editor && $submitAction && $qLocality){
	if($qCountry) $geoManager->setQueryVariables('qcountry',$qCountry);
	if($qState) $geoManager->setQueryVariables('qstate',$qState);
	if($qCounty) $geoManager->setQueryVariables('qcounty',$qCounty);
	if($qVStatus) $geoManager->setQueryVariables('qvstatus',$qVStatus);
	if($qLocality) $geoManager->setQueryVariables('qlocality',$qLocality);
	if($submitAction == 'Update Coordinates'){
		$statusStr = $geoManager->updateCoordinates($_POST);
	}
	$localArr = $geoManager->getLocalityArr();
}

header("Content-Type: text/html; charset=".$charset);
?>
<html>
	<head>
		<title>Georeferencing Tools</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<script type="text/javascript" src="../../js/symb/collections.georef.batchgeoreftool.js"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = 0;
		//include($serverRoot.'/header.php');
		?>
		<div style="font-weight:bold;font-size:150%;margin-top:6px;">
			<?php echo $geoManager->getCollName(); ?>
		</div>
		
		<div class='navpath' style="margin:10px;">
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<?php 
			if(isset($collections_editor_georeftoolsCrumbs)){
				echo $collections_editor_georeftoolsCrumbs." &gt;&gt;"; 
			}
			else{
				?>
				<a href='../misc/collprofiles.php?emode=1&collid=<?php echo $collId; ?>'>Control Menu</a> &gt;&gt;
				<?php  
			}
			?>
			<b>Batch Georeferencing Tools</b>
		</div>
		<!-- This is inner text! -->
		<div style="width:98%">
			<?php 
			if($statusStr){ 
				?>
				<div style='margin:20px;font-weight:bold;color:red;'>
					<?php echo $statusStr; ?>
				</div>
				<?php 
			}
			if($symbUid){
				if($collId){
					if($editor){
						?>
						<form name="queryform" method="post" action="batchgeoreftool.php" onsubmit="return verifyQueryForm(this)">
							<fieldset style="padding:10px;width:700px;">
								<legend><b>Query Form</b></legend>
								<div style="margin-bottom:3px;">
									<div style="float:left;margin-right:10px;">
										<b>Country:</b> 
										<select name="qcountry" style="width:150px;">
											<option value=''>All Countries</option>
											<option value=''>--------------------</option>
											<?php 
											$cArr = $geoManager->getCountryArr();
											foreach($cArr as $c){
												echo '<option '.($qCountry==$c?'SELECTED':'').'>'.$c.'</option>';
											}
											?>
										</select>
									</div>
									<div style="float:left;margin-right:10px;">
										<b>State: </b>
										<select name="qstate" style="width:150px;">
											<option value=''>All States</option>
											<option value=''>--------------------</option>
											<?php 
											$sArr = $geoManager->getStateArr($country);
											foreach($sArr as $s){
												echo '<option '.($qState==$s?'SELECTED':'').'>'.$s.'</option>';
											}
											?>
										</select>
									</div>
									<div style="float:left;">
										<b>County:</b> 
										<select name="qcounty" style="width:180px;">
											<option value=''>All Counties</option>
											<option value=''>--------------------</option>
											<?php 
											$coArr = $geoManager->getCountyArr($country,$state);
											foreach($coArr as $c){
												echo '<option '.($qCounty==$c?'SELECTED':'').'>'.$c.'</option>';
											}
											?>
										</select>
										<img src="../../images/add.png" onclick="toggle('advfilterdiv')" title="Advanced Options" />
									</div>
								</div>
								<div id="advfilterdiv" style="clear:both;display:<?php echo ($qVStatus?'block':'none'); ?>;">
									<b>Lat/Long contains coordinate values and verification status equals:</b>
									<input name="qvstatus" type="text" value="<?php echo $qVStatus; ?>" style="" />
								</div>
								<div style="clear:both;">
									<b>Locality Term:</b> 
									<input name="qlocality" type="text" value="<?php echo $qLocality; ?>" style="width:250px;" />
									<span style="margin-left:175px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="submitaction" type="submit" value="Generate List" />
									</span>
								</div>
							</fieldset> 
						</form>
						<form name="georefform" method="post" action="batchgeoreftool.php" onsubmit="return verifyGeorefForm(this)">
							<div style="float:right;">
								<a href="#" onclick="openFirstRecSet();">
									<img src="../../images/edit.png" title="Edit first set of records" style="width:13px;" />
								</a>
							</div>
							<div style="font-weight:bold;">
								<?php 
								echo 'Return Count: '.(isset($localArr)?count($localArr):'---');
								?>
							</div>
							<div style="clear:both;">
								<select name="locallist[]" size="10" multiple="multiple" style="width:100%">
									<?php 
									if(isset($localArr)){
										if($localArr){
											foreach($localArr as $k => $v){
												$locStr = $v['locality'];
												if($v['extra']) $locStr .= '; '.$v['extra'];
												echo '<option value="'.$v['occid'].'">'.$locStr.'; '.$v['cnt'].'</option>'."\n";
											}
										}
										else{
											echo '<option value="">No localities returned matching search term</option>';
										}
									}
									else{
										echo '<option value="">Use query form above to build locality list</option>';
									}
									?>
								</select>
							</div>
							<div style="float:right;">
								<fieldset>
									<legend><b>Statistics</b></legend>
									<div style="">
										Records to be Georeferenced 
									</div>
									<div style="margin:5px;">
										<?php 
										$statArr = $geoManager->getCoordStatistics();
										foreach($statArr as $k => $v){
											echo '<div>';
											echo $k.': '.$v;
											if($k == 'Total Percentage') echo '%';
											echo '</div>';
										}
										?>
									</div>
								</fieldset>
							</div>
							<div style="margin:15px;">
								<table>
									<tr>
										<td></td>
										<td><b>Deg.</b></td>
										<td style="width:55px;"><b>Min.</b></td>
										<td style="width:55px;"><b>Sec.</b></td>
										<td style="width:20px;">&nbsp;</td>
										<td style="width:15px;">&nbsp;</td>
										<td><b>Decimal</b></td>
									</tr>
									<tr>
										<td style="vertical-align:middle"><b>Latitude:</b> </td>
										<td><input name="latdeg" type="text" value="<?php echo $latDeg; ?>" onchange="updateLatDec(this.form)" style="width:30px;" /></td>
										<td><input name="latmin" type="text" value="<?php echo $latMin; ?>" onchange="updateLatDec(this.form)" style="width:50px;" /></td>
										<td><input name="latsec" type="text" value="<?php echo $latSec; ?>" onchange="updateLatDec(this.form)" style="width:50px;" /></td>
										<td>
											<select name="latns" onchange="updateLatDec(this.form)">
												<option>N</option>
												<option <?php echo ($latNS=='S'?'SELECTED':''); ?>>S</option>
											</select>
										</td>
										<td> = </td>
										<td>
											<input id="decimallatitude" name="decimallatitude" type="text" value="<?php echo $decimalLatitude; ?>" style="width:80px;" />
											<span style="cursor:pointer;padding:3px;" onclick="openMappingAid();">
												<img src="../../images/world40.gif" style="border:0px;width:13px;" />
											</span>
										</td>
									</tr>
									<tr>
										<td style="vertical-align:middle"><b>Longitude:</b> </td>
										<td><input name="lngdeg" type="text" value="<?php echo $lngDeg; ?>" onchange="updateLngDec(this.form)" style="width:30px;" /></td>
										<td><input name="lngmin" type="text" value="<?php echo $lngMin; ?>" onchange="updateLngDec(this.form)" style="width:50px;" /></td>
										<td><input name="lngsec" type="text" value="<?php echo $lngSec; ?>" onchange="updateLngDec(this.form)" style="width:50px;" /></td>
										<td style="width:20px;">
											<select name="lngew" onchange="updateLngDec(this.form)">
												<option>E</option>
												<option <?php echo (!$lngEW || $lngEW=='W'?'SELECTED':''); ?>>W</option>
											</select>
										</td>
										<td> = </td>
										<td><input id="decimallongitude" name="decimallongitude" type="text" value="<?php echo $decimalLongitude; ?>" style="width:80px;" /></td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Error (in meters):</b> 
										</td>
										<td colspan="2" style="vertical-align:middle">
											<input name="coordinateuncertaintyinmeters" type="text" value="<?php echo $coordinateUncertaintyInMeters; ?>" style="width:50px;" onchange="verifyCoordUncertainty(this)" /> 
											meters
										</td>
										<td colspan="2" style="vertical-align:middle">
											<span style="margin-left:20px;font-weight:bold;">Datum:</span> 
											<input id="geodeticdatum" name="geodeticdatum" type="text" value="<?php echo $geodeticDatum; ?>" style="width:75px;" />
											<span style="cursor:pointer;margin-left:3px;" onclick="toggle('utmdiv');">
												<img src="../../images/showedit.png" style="border:0px;width:14px;" />
											</span>
										</td>
									</tr>
									<tr>
										<td colspan="7">
											<div id="utmdiv" style="display:none;padding:15px 10px;background-color:lightyellow;border:1px solid yellow;width:400px;margin-bottom:10px;">
												<div style="margin:2px;">
													Zone: <input name="utmzone" style="width:40px;" />
												</div>
												<div style="margin:2px;">
													East: <input name="utmeast" type="text" style="width:100px;" />
												</div>
												<div style="margin:2px;">
													North: <input name="utmnorth" type="text" style="width:100px;" />
												</div>
												<div style="margin:2px;">
													Hemisphere: 
													<select name="hemisphere" title="Use hemisphere designator (e.g. 12N) rather than grid zone ">
														<option value="Northern">North</option>
														<option value="Southern">South</option>
													</select>
												</div>
												<div style="margin-top:5px;">
													<input type="button" value="Convert UTM values to decimal lat/long " onclick="insertUtm(this.form)" />
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Sources:</b> 
										</td>
										<td colspan="4">
											<input name="georeferencesources" type="text" value="<?php echo $georeferenceSources; ?>" style="width:500px;" />
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Remarks:</b> 
										</td>
										<td colspan="4">
											<input name="georeferenceRemarks" type="text" value="<?php echo $georeferenceRemarks; ?>" style="width:500px;" />
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Verification Status:</b> 
										</td>
										<td colspan="4">
											<input name="georeferenceverificationstatus" type="text" value="<?php echo $georeferenceVerificationStatus; ?>" style="width:400px;" />
										</td>
									</tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Elevation:</b> 
										</td>
										<td colspan="4">
											<input name="minimumelevationinmeters" type="text" value="<?php echo $minimumElevationInMeters; ?>" /> to 
											<input name="maximumelevationinmeters" type="text" value="<?php echo $maximumElevationInMeters; ?>" /> meters
										</td>
									</tr>
									<tr>
										<td colspan="7">
											<input name="submitaction" type="submit" value="Update Coordinates" />
											<input name="qcountry" type="hidden" value="<?php echo $qCountry; ?>" />
											<input name="qstate" type="hidden" value="<?php echo $qState; ?>" />
											<input name="qcounty" type="hidden" value="<?php echo $qCounty; ?>" />
											<input name="qlocality" type="hidden" value="<?php echo $qLocality; ?>" />
											<input name="qvstatus" type="hidden" value="<?php echo $qVStatus; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
											<input name="georefby" type="hidden" value="<?php echo $userDisplayName; ?>" />
										</td>
									</tr>
								</table>
							</div>
						</form>
						
						<?php
					}
					else{
						?>
						<div style='font-weight:bold;font-size:120%;'>
							ERROR: You do not have permission to edit this collection
						</div>
						<?php 
					}
				}
				else{
					?>
					<div style='font-weight:bold;font-size:120%;'>
						ERROR: Collection identifier is null
					</div>
					<?php 
				}
			}
			else{
				?>
				<div style='font-weight:bold;font-size:120%;'>
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/georef/batchgeoreftool.php?collid=<?php echo $collId; ?>'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
		<?php 	
		//include($serverRoot.'/footer.php');
		?>
	</body>
</html>
