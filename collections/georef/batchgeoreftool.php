<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceGeorefTools.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../collections/georef/batchgeoreftool.php?'.$_SERVER['QUERY_STRING']);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$submitAction = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$qCountry = array_key_exists('qcountry',$_POST)?$_POST['qcountry']:'';
$qState = array_key_exists('qstate',$_POST)?$_POST['qstate']:'';
$qCounty = array_key_exists('qcounty',$_POST)?$_POST['qcounty']:'';
$qMunicipality = array_key_exists('qmunicipality',$_POST)?$_POST['qmunicipality']:'';
$qLocality = array_key_exists('qlocality',$_POST)?$_POST['qlocality']:'';
$qDisplayAll = array_key_exists('qdisplayall',$_POST)?$_POST['qdisplayall']:0;
$qVStatus = array_key_exists('qvstatus',$_POST)?$_POST['qvstatus']:'';
$qSciname = array_key_exists('qsciname',$_POST)?$_POST['qsciname']:'';
$qProcessingStatus = array_key_exists('qprocessingstatus',$_POST)?$_POST['qprocessingstatus']:'';

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
$footprintWKT = array_key_exists('footprintwkt',$_POST)?$_POST['footprintwkt']:'';
$georeferenceVerificationStatus = array_key_exists('georeferenceverificationstatus',$_POST)?$_POST['georeferenceverificationstatus']:'';
$minimumElevationInMeters = array_key_exists('minimumelevationinmeters',$_POST)?$_POST['minimumelevationinmeters']:'';
$maximumElevationInMeters = array_key_exists('maximumelevationinmeters',$_POST)?$_POST['maximumelevationinmeters']:'';
$minimumElevationInFeet = array_key_exists('minimumelevationinfeet',$_POST)?$_POST['minimumelevationinfeet']:'';
$maximumElevationInFeet = array_key_exists('maximumelevationinfeet',$_POST)?$_POST['maximumelevationinfeet']:'';

if(!$georeferenceSources) $georeferenceSources = 'georef batch tool '.date('Y-m-d');
if(!$georeferenceVerificationStatus) $georeferenceVerificationStatus = 'reviewed - high confidence';

$geoManager = new OccurrenceGeorefTools();
if($SOLR_MODE) $solrManager = new SOLRManager();
$geoManager->setCollId($collId);

$editor = false;
if($IS_ADMIN
	|| (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))
	|| (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"]))){
 	$editor = true;
}

$statusStr = '';
$localArr;
if($editor && $submitAction){
	if($qCountry) $geoManager->setQueryVariables('qcountry',$qCountry);
	if($qState) $geoManager->setQueryVariables('qstate',$qState);
	if($qCounty) $geoManager->setQueryVariables('qcounty',$qCounty);
	if($qMunicipality) $geoManager->setQueryVariables('qmunicipality',$qMunicipality);
	if($qSciname) $geoManager->setQueryVariables('qsciname',$qSciname);
	if($qDisplayAll) $geoManager->setQueryVariables('qdisplayall',$qDisplayAll);
	if($qVStatus) $geoManager->setQueryVariables('qvstatus',$qVStatus);
	if($qLocality) $geoManager->setQueryVariables('qlocality',$qLocality);
	if($qProcessingStatus) $geoManager->setQueryVariables('qprocessingstatus',$qProcessingStatus);
	if($submitAction == 'Update Coordinates'){
		$statusStr = $geoManager->updateCoordinates($_POST);
        if($SOLR_MODE) $solrManager->updateSOLR();
	}
	$localArr = $geoManager->getLocalityArr();
}

header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
	<head>
		<title>Georeferencing Tools</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link type="text/css" href="<?php echo $clientRoot; ?>/css/jquery-ui.css" rel="Stylesheet" />
		<script type="text/javascript" src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js"></script>
		<script type="text/javascript" src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js"></script>
		<script type="text/javascript" src="<?php echo $CLIENT_ROOT; ?>/js/symb/collections.georef.batchgeoreftool.js?ver=161212"></script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div  id='innertext'>
			<div style="float:left;">
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
				<?php
				if($statusStr){
					?>
					<div style='margin:20px;font-weight:bold;color:red;'>
						<?php echo $statusStr; ?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
			if($collId){
				if($editor){
					?>
					<div style="float:right;">
						<form name="queryform" method="post" action="batchgeoreftool.php" onsubmit="return verifyQueryForm(this)">
							<fieldset style="padding:5px;width:600px;background-color:lightyellow;">
								<legend><b>Query Form</b></legend>
								<div style="height:20px;">
									<div style="clear:both;">
										<div style="float:left;margin-right:10px;">
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
											<select name="qstate" style="width:150px;">
												<option value=''>All States</option>
												<option value=''>--------------------</option>
												<?php
												$sArr = $geoManager->getStateArr($qCountry);
												foreach($sArr as $s){
													echo '<option '.($qState==$s?'SELECTED':'').'>'.$s.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;margin-right:10px;">
											<select name="qcounty" style="width:180px;">
												<option value=''>All Counties</option>
												<option value=''>--------------------</option>
												<?php
												$coArr = $geoManager->getCountyArr($qCountry,$qState);
												foreach($coArr as $c){
													echo '<option '.($qCounty==$c?'SELECTED':'').'>'.$c.'</option>';
												}
												?>
											</select>
										</div>
									</div>
									<div style="clear:both;margin-top:5px;">
										<div style="float:left;margin-right:10px;">
											<select name="qmunicipality" style="width:180px;">
												<option value=''>All Municipalities</option>
												<option value=''>--------------------</option>
												<?php
												$muArr = $geoManager->getMunicipalityArr($qCountry,$qState);
												foreach($muArr as $m){
													echo '<option '.($qMunicipality==$m?'SELECTED':'').'>'.$m.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;margin-right:10px;">
											<select name="qprocessingstatus">
												<option value="">All Processing Status</option>
												<option value="">-----------------------</option>
												<?php 
												$processingStatus = $geoManager->getProcessingStatus();
												foreach($processingStatus as $pStatus){
													echo '<option '.($qProcessingStatus==$pStatus?'SELECTED':'').'>'.$pStatus.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;">
											<img src="../../images/add.png" onclick="toggle('advfilterdiv')" title="Advanced Options" />
										</div>
									</div>
								</div>
								<div id="advfilterdiv" style="clear:both;margin-top:5px;display:<?php echo ($qSciname || $qVStatus || $qDisplayAll?'block':'none'); ?>;">
									<div style="float:left;margin-right:15px;">
										<b>Verification status:</b>
										<input id="qvstatus" name="qvstatus" type="text" value="<?php echo $qVStatus; ?>" style="width:175px;" />
									</div>
									<div style="float:left;">
										<b>Family/Genus:</b>
										<input name="qsciname" type="text" value="<?php echo $qSciname; ?>" style="width:150px;" />
									</div>
									<div style="clear:both;margin-top:5px;">
										<input name="qdisplayall" type="checkbox" value="1" <?php echo ($qDisplayAll?'checked':''); ?> />
										Including previously georeferenced records
									</div>
								</div>
								<div style="margin-top:5px;clear:both;">
									<div style="float:right;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="submitaction" type="submit" value="Generate List" />
										<span id="qworkingspan" style="display:none;">
											<img src="../../images/workingcircle.gif" />
										</span>
									</div>
									<div style="float:left">
										<b>Locality Term:</b>
										<input name="qlocality" type="text" value="<?php echo $qLocality; ?>" style="width:250px;" />
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div style="clear:both;">
						<form name="georefform" method="post" action="batchgeoreftool.php" onsubmit="return verifyGeorefForm(this)">
							<div style="float:right;">
								<span>
									<a href="#" onclick="geoCloneTool();"><img src="../../images/list.png" title="Search for clones previously georeferenced" style="width:15px;" /></a>
								</span>
								<span style="margin-left:10px;">
									<a href="#" onclick="geoLocateLocality();"><img src="../../images/geolocate.png" title="GeoLocate locality" style="width:15px;" /></a>
								</span>
								<span style="margin-left:10px;">
									<a href="#" onclick="analyseLocalityStr();"><img src="../../images/find.png" title="Analyse Locality string for embedded Lat/Long or UTM" style="width:15px;" /></a>
								</span>
								<span style="margin-left:10px;">
									<a href="#" onclick="openFirstRecSet();"><img src="../../images/edit.png" title="Edit first set of records" style="width:15px;" /></a>
								</span>
							</div>
							<div style="font-weight:bold;">
								<?php
								$localCnt = '---';
								if(isset($localArr)){
									$localCnt = count($localArr);
								}
								if($localCnt == 1000){
									$localCnt = '1000 or more';
								}
								echo 'Return Count: '.$localCnt;
								?>
							</div>
							<div style="clear:both;">
								<select id="locallist" name="locallist[]" size="15" multiple="multiple" style="width:100%">
									<?php
									if(isset($localArr)){
										if($localArr){
											foreach($localArr as $k => $v){
												$locStr = '';
												if(!$qCountry && $v['country']) $locStr = $v['country'].'; ';
												if(!$qState && $v['stateprovince']) $locStr .= $v['stateprovince'].'; ';
												if(!$qCounty && $v['county']) $locStr .= $v['county'].'; ';
												if(!$qMunicipality && $v['municipality']) $locStr .= $v['municipality'].'; ';
												if($v['locality']) $locStr .= str_replace(';',',',$v['locality']);
												if($v['verbatimcoordinates']) $locStr .= ', '.$v['verbatimcoordinates'];
												if(array_key_exists('decimallatitude',$v) && $v['decimallatitude']){
													$locStr .= ' ('.$v['decimallatitude'].', '.$v['decimallongitude'].') ';
												}
												echo '<option value="'.$v['occid'].'">'.trim($locStr,' ,').' ['.$v['cnt'].']</option>'."\n";
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
										echo '<div>Total: '.$statArr['total'].'</div>';
										echo '<div>Percentage: '.$statArr['percent'].'%</div>';
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
										<td><input name="latdeg" type="text" value="" onchange="updateLatDec(this.form)" style="width:30px;" /></td>
										<td><input name="latmin" type="text" value="" onchange="updateLatDec(this.form)" style="width:50px;" /></td>
										<td><input name="latsec" type="text" value="" onchange="updateLatDec(this.form)" style="width:50px;" /></td>
										<td>
											<select name="latns" onchange="updateLatDec(this.form)">
												<option>N</option>
												<option >S</option>
											</select>
										</td>
										<td> = </td>
										<td>
											<input id="decimallatitude" name="decimallatitude" type="text" value="" style="width:80px;" />
											<span style="cursor:pointer;padding:3px;" onclick="openMappingAid();">
												<img src="../../images/world.png" style="border:0px;width:13px;" />
											</span>
										</td>
									</tr>
									<tr>
										<td style="vertical-align:middle"><b>Longitude:</b> </td>
										<td><input name="lngdeg" type="text" value="" onchange="updateLngDec(this.form)" style="width:30px;" /></td>
										<td><input name="lngmin" type="text" value="" onchange="updateLngDec(this.form)" style="width:50px;" /></td>
										<td><input name="lngsec" type="text" value="" onchange="updateLngDec(this.form)" style="width:50px;" /></td>
										<td style="width:20px;">
											<select name="lngew" onchange="updateLngDec(this.form)">
												<option>E</option>
												<option SELECTED>W</option>
											</select>
										</td>
										<td> = </td>
										<td><input id="decimallongitude" name="decimallongitude" type="text" value="" style="width:80px;" /></td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Error (in meters):</b>
										</td>
										<td colspan="2" style="vertical-align:middle">
											<input id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" type="text" value="" style="width:50px;" onchange="verifyCoordUncertainty(this)" />
										</td>
										<td colspan="2" style="vertical-align:middle">
											<span style="margin-left:20px;font-weight:bold;">Datum:</span>
											<input id="geodeticdatum" name="geodeticdatum" type="text" value="" style="width:75px;" />
											<span style="cursor:pointer;margin-left:3px;" onclick="toggle('utmdiv');">
												<img src="../../images/editplus.png" style="border:0px;width:14px;" />
											</span>
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Footprint WKT:</b>
										</td>
										<td colspan="4" style="vertical-align:middle">
											<input id="footprintwkt" name="footprintwkt" type="text" value="" style="width:500px;" onchange="verifyFootprintWKT(this)" />
										</td>
									</tr>
									<tr>
										<td colspan="7">
											<div id="utmdiv" style="display:none;padding:15px 10px;background-color:lightyellow;border:1px solid yellow;width:400px;height:75px;margin-bottom:10px;">
												<div>
													<div style="margin:3px;float:left;">
														East: <input name="utmeast" type="text" style="width:100px;" />
													</div>
													<div style="margin:3px;float:left;">
														North: <input name="utmnorth" type="text" style="width:100px;" />
													</div>
													<div style="margin:3px;float:left;">
														Zone: <input name="utmzone" style="width:40px;" />
													</div>
												</div>
												<div style="clear:both;margin:3px;">
													<div style="float:left;">
														Hemisphere:
														<select name="hemisphere" title="Use hemisphere designator (e.g. 12N) rather than grid zone ">
															<option value="Northern">North</option>
															<option value="Southern">South</option>
														</select>
													</div>
													<div style="margin:5px 0px 0px 15px;float:left;">
														<input type="button" value="Convert UTM values to lat/long " onclick="insertUtm(this.form)" />
													</div>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Sources:</b>
										</td>
										<td colspan="4">
											<input id="georeferencesources" name="georeferencesources" type="text" value="<?php echo $georeferenceSources; ?>" style="width:500px;" />
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Remarks:</b>
										</td>
										<td colspan="4">
											<input name="georeferenceremarks" type="text" value="" style="width:500px;" />
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Verification Status:</b>
										</td>
										<td colspan="4">
											<input id="georeferenceverificationstatus" name="georeferenceverificationstatus" type="text" value="<?php echo $georeferenceVerificationStatus; ?>" style="width:400px;" />
										</td>
									</tr>
									<tr>
										<td colspan="3" style="vertical-align:middle">
											<b>Elevation:</b>
										</td>
										<td colspan="4">
											<input name="minimumelevationinmeters" type="text" value="" style="width:50px;" /> to
											<input name="maximumelevationinmeters" type="text" value="" style="width:50px;" /> meters
											<span style="margin-left:80px;">
												<input type="text" value="" style="width:50px;" onchange="updateMinElev(this.value)" /> to
												<input type="text" value="" style="width:50px;" onchange="updateMaxElev(this.value)" /> feet
											</span>
										</td>
									</tr>
									<tr>
										<td colspan="6">
											<input name="submitaction" type="submit" value="Update Coordinates" />
											<span id="workingspan" style="display:none;">
												<img src="../../images/workingcircle.gif" />
											</span>
											<input name="qcountry" type="hidden" value="<?php echo $qCountry; ?>" />
											<input name="qstate" type="hidden" value="<?php echo $qState; ?>" />
											<input name="qcounty" type="hidden" value="<?php echo $qCounty; ?>" />
											<input name="qmunicipality" type="hidden" value="<?php echo $qMunicipality; ?>" />
											<input name="qlocality" type="hidden" value="<?php echo $qLocality; ?>" />
											<input name="qsciname" type="hidden" value="<?php echo $qSciname; ?>" />
											<input name="qvstatus" type="hidden" value="<?php echo $qVStatus; ?>" />
											<input name="qprocessingstatus" type="hidden" value="<?php echo $qProcessingStatus; ?>" />
											<input name="qdisplayall" type="hidden" value="<?php echo $qDisplayAll; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										</td>
										<td align="right">
											<div style="margin:10px;">
												Georeferenced by:
												<input name="georeferencedby" type="text" value="<?php echo $paramsArr['un']; ?>" readonly />
											</div>
										</td>
									</tr>
								</table>
								<div>Note: Existing data within following georeference fields will be replaced with incoming data. 
								However, elevation data will only be added when the target fields are null. 
								No incoming data will replace existing elevational data. 
								Georeference fields that will be replaced: decimalLatitude, decimalLongitude, coordinateUncertaintyInMeters, geodeticdatum, 
								footprintwkt, georeferencedby, georeferenceRemarks, georeferenceSources, georeferenceVerificationStatus </div>
							</div>
						</form>
					</div>
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
			?>
		</div>
	</body>
</html>
