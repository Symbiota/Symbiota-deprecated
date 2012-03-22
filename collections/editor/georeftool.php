<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/GeoreferencingTools.php');

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('querysubmit',$_POST)?$_POST['querysubmit']:'';

$country = array_key_exists('country',$_REQUEST)?$_REQUEST['country']:'';
$state = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:'';
$county = array_key_exists('county',$_REQUEST)?$_REQUEST['county']:'';
$locality = array_key_exists('locality',$_REQUEST)?$_REQUEST['locality']:'';
$vStatus = array_key_exists('vstatus',$_REQUEST)?$_REQUEST['vstatus']:'';
$vStatusStr = array_key_exists('vstatusstr',$_REQUEST)?$_REQUEST['vstatusstr']:'';

$latDeg = array_key_exists('latdeg',$_POST)?$_POST['latdeg']:'';
$latMin = array_key_exists('latmin',$_POST)?$_POST['latmin']:'';
$latSec = array_key_exists('latsec',$_POST)?$_POST['latsec']:'';
$latDec = array_key_exists('latdec',$_POST)?$_POST['latdec']:'';
$latNS = array_key_exists('latns',$_POST)?$_POST['latns']:'';

$lngDeg = array_key_exists('lngdeg',$_POST)?$_POST['lngdeg']:'';
$lngMin = array_key_exists('lngmin',$_POST)?$_POST['lngmin']:'';
$lngSec = array_key_exists('lngsec',$_POST)?$_POST['lngsec']:'';
$lngDec = array_key_exists('lngdec',$_POST)?$_POST['lngdec']:'';
$lngEW = array_key_exists('lngew',$_POST)?$_POST['lngew']:'';

$coordUncertainty = array_key_exists('coorduncertainty',$_POST)?$_POST['coorduncertainty']:'';
$georeferenceSources = array_key_exists('georeferencesources',$_POST)?$_POST['georeferencesources']:'';
$georeferenceRemarks = array_key_exists('georeferenceremarks',$_POST)?$_POST['georeferenceremarks']:'';
$georeferenceVerificationStatus = array_key_exists('georeferenceverificationstatus',$_POST)?$_POST['georeferenceverificationstatus']:'';


$geoManager = new GeoreferencingTools();
$geoManager->setCollId($collId);

$editor = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editor = true;
}

$localArr;
if($editor){
	if($action == 'Generate List'){
		if($country) $geoManager->setQueryVariables('country',$country);
		if($state) $geoManager->setQueryVariables('stateprovince',$state);
		if($county) $geoManager->setQueryVariables('county',$county);
		if($vStatus) $geoManager->setQueryVariables('vstatus',$vStatus);
		if($locality) $geoManager->setQueryVariables('locality',$locality);
		$localArr = $geoManager->getLocalityArr();
	}
}

header("Content-Type: text/html; charset=".$charset);
?>
<html>
	<head>
		<title>Georeferencing Tools</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<script language="javascript">
			function verifyQueryForm(f){
				if(f.locality.value == ""){
					alert("Please enter a locality term");
					return false;
				}
				return true;
			}

			function verifyGeorefForm(f){
				if(f.latdec.value == "" || f.lngdec.value == ""){
					alert("Please enter coordinates into lat/long decimal fields");
					return false;
				}
				if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
					alert("Coordinate Uncertainity can only contain numeric values");
					return false;
				}
				return true;
			}

			function updateLatDec(f){
				var latDec = parseInt(f.latdeg.value);
				var latMin = parseFloat(f.latmin.value);
				var latSec = parseFloat(f.latsec.value);
				var latNS = f.latns.value;
				if(!isNumeric(latDec) || !isNumeric(latMin) || !isNumeric(latSec)){
					alert('Degree, minute, and second values must be numeric only');
					return false;
				}
				if(latDec > 90){
					alert("Latitude degrees cannot be greater than 90");
					return false;
				}
				if(latMin > 60){
					alert("The Minutes value cannot be greater than 60");
					return false;
				}
				if(latSec > 60){
					alert("The Seconds value cannot be greater than 60");
					return false;
				}
				if(latMin) latDec = latDec + (f.latmin.value / 60);
				if(latSec) latDec = latDec + (f.latsec.value / 3600);
				if(latNS == "S"){
					if(latDec > 0) latDec = -1*latDec;
				}
				else{
					if(latDec < 0) latDec = -1*latDec;
				}
				f.latdec.value = Math.round(latDec*1000000)/1000000;
			}

			function updateLngDec(f){
				var lngDec = parseInt(f.lngdeg.value);
				var lngMin = parseFloat(f.lngmin.value);
				var lngSec = parseFloat(f.lngsec.value);
				var lngEW = f.lngew.value;
				if(!isNumeric(lngDec) || !isNumeric(lngMin) || !isNumeric(lngSec)){
					alert("Degree, minute, and second values must be numeric only");
					return false;
				}
				if(lngDec > 180){
					alert("Longitude degrees cannot be greater than 180");
					return false;
				}
				if(lngMin > 60){
					alert("The Minutes value cannot be greater than 60");
					return false;
				}
				if(lngSec > 60){
					alert("The Seconds value cannot be greater than 60");
					return false;
				}
				if(lngMin) lngDec = lngDec + (lngMin / 60);
				if(lngSec) lngDec = lngDec + (lngSec / 3600);
				if(lngEW == "W"){
					if(lngDec > 0) lngDec = -1*lngDec;
				}
				else{
					if(lngDec < 0) lngDec = -1*lngDec;
				}
				f.lngdec.value = Math.round(lngDec*1000000)/1000000;
			}

			function verifyCoordUncertainty(inputObj){
				if(!isNumeric(inputObj.value)){
					alert("Coordinate Uncertainity can only contain numeric values");
				}
			}

			function toggle(target){
				var objDiv = document.getElementById(target);
				if(objDiv){
					if(objDiv.style.display=="none"){
						objDiv.style.display = "block";
					}
					else{
						objDiv.style.display = "none";
					}
				}
				else{
				  	var divs = document.getElementsByTagName("div");
				  	for (var h = 0; h < divs.length; h++) {
				  	var divObj = divs[h];
						if(divObj.className == target){
							if(divObj.style.display=="none"){
								divObj.style.display="block";
							}
						 	else {
						 		divObj.style.display="none";
						 	}
						}
					}
				}
			}

			function isNumeric(sText){
			   	var validChars = "0123456789-.";
			   	var isNumber = true;
			   	var charVar;

			   	for(var i = 0; i < sText.length && isNumber == true; i++){ 
			   		charVar = sText.charAt(i); 
					if(validChars.indexOf(charVar) == -1){
						isNumber = false;
						break;
			      	}
			   	}
				return isNumber;
			}
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = 0;
		include($serverRoot.'/header.php');
		if(isset($collections_editor_georeftoolsCrumbs)){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_editor_georeftoolsCrumbs;
			echo "<b>Georeferencing Tools</b>";
			echo "</div>";
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($status){ 
				?>
				<div style='margin:20px;font-weight:bold;color:red;'>
					<?php echo $status; ?>
				</div>
				<?php 
			}
			if($symbUid){
				if($collId){
					if($editor){
						?>
						<div style="font-weight:bold;font-size:130%;">
							<?php echo $geoManager->getCollName(); ?>
						</div>
						<form name="queryform" method="post" action="georeftool.php" onsubmit="return verifyQueryForm(this)">
							<fieldset style="padding:10px;">
								<legend><b>Query Form</b></legend>
								<div style="margin-bottom:3px;">
									<div style="float:left;margin-right:10px;">
										<b>Country:</b> 
										<select name="country" style="width:150px;">
											<option value=''>All Countries</option>
											<option value=''>--------------------</option>
											<?php 
											$cArr = $geoManager->getCountryArr();
											foreach($cArr as $c){
												echo '<option '.($country==$c?'SELECTED':'').'>'.$c.'</option>';
											}
											?>
										</select>
									</div>
									<div style="float:left;margin-right:10px;">
										<b>State: </b>
										<select name="state" style="width:150px;">
											<option value=''>All States</option>
											<option value=''>--------------------</option>
											<?php 
											$sArr = $geoManager->getStateArr($country);
											foreach($sArr as $s){
												echo '<option '.($state==$s?'SELECTED':'').'>'.$s.'</option>';
											}
											?>
										</select>
									</div>
									<div style="float:left;">
										<b>County:</b> 
										<select name="county" style="width:180px;">
											<option value=''>All Counties</option>
											<option value=''>--------------------</option>
											<?php 
											$coArr = $geoManager->getCountyArr($country,$state);
											foreach($coArr as $c){
												echo '<option '.($county==$c?'SELECTED':'').'>'.$c.'</option>';
											}
											?>
										</select>
										<img src="../../images/add.png" onclick="toggle('advfilterdiv')" title="Advanced Options" />
									</div>
								</div>
								<div id="advfilterdiv" style="clear:both;display:<?php echo ($vStatus?'block':'none'); ?>;">
									<b>Lat/Long contains coordinate values and verification status equals:</b>
									<input name="vstatus" type="text" value="<?php echo $vStatus; ?>" style="width:" />
								</div>
								<div style="clear:both;">
									<b>Locality Term:</b> 
									<input name="locality" type="text" value="<?php echo $locality; ?>" style="width:250px;" />
									<span style="margin-left:175px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="querysubmit" type="submit" value="Generate List" />
									</span>
								</div>
							</fieldset> 
						</form>
						<form name="georefform" method="post" action="georeftool.php" onsubmit="return verifyGeorefForm(this)">
							<div style="font-weight:bold;">
								<?php 
								echo 'Return Count: '.(isset($localArr)?count($localArr):'---');
								?>
							</div>
							<div style="">
								<select name="locallist" size="10" multiple="multiple" style="width:95%">
									<?php 
									if(isset($localArr)){
										if($localArr){
											foreach($localArr as $k => $v){
												$locStr = $v['locality'];
												if($v['extra']) $locStr .= '; '.$v['extra'];
												echo '<option value="'.$v['locality'].'">'.$locStr.'; '.$v['cnt'].'</option>'."\n";
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
							<div style="clear:both;margin:15px;">
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
										<td><b>Latitude:</b> </td>
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
										<td><input name="latdec" type="text" value="<?php echo $latDec; ?>" style="width:80px;" /></td>
									</tr>
									<tr>
										<td><b>Longitude:</b> </td>
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
										<td><input name="lngdec" type="text" value="<?php echo $latDec; ?>" style="width:80px;" /></td>
									</tr>
									<tr>
										<td colspan="2"><b>Error (in meters):</b> </td>
										<td colspan="5">
											<input name="coordinateuncertaintyinmeters" type="text" value="<?php echo $coordUncertainty; ?>" style="width:50px;" onchange="verifyCoordUncertainty(this)" /> 
											meters
										</td>
									</tr>
									<tr>
										<td colspan="2"><b>Sources:</b> </td>
										<td colspan="5">
											<input name="georeferencesources" type="text" value="<?php echo $georeferenceSources; ?>" style="width:500px;" />
										</td>
									</tr>
									<tr>
										<td colspan="2"><b>Remarks:</b> </td>
										<td colspan="5">
											<input name="georeferenceRemarks" type="text" value="<?php echo $georeferenceRemarks; ?>" style="width:500px;" />
										</td>
									</tr>
									<tr>
										<td colspan="2"><b>Verification Status:</b> </td>
										<td colspan="5">
											<input name="georeferenceverificationstatus" type="text" value="<?php echo $georeferenceVerificationStatus; ?>" style="width:400px;" />
										</td>
									</tr>
									<tr>
										<td colspan="7">
											<input name="querysubmit" type="submit" value="Update Coordinates" />
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
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
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/editor/georeftool.php?collid=<?php echo $collId; ?>'>login</a>!
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
