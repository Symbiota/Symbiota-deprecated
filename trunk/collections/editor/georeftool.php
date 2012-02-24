<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/GeoreferencingTools.php');

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('querysubmit',$_REQUEST)?$_REQUEST['querysubmit']:'';

$country = array_key_exists('country',$_REQUEST)?$_REQUEST['country']:'';
$state = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:'';
$county = array_key_exists('county',$_REQUEST)?$_REQUEST['county']:'';
$locality = array_key_exists('locality',$_REQUEST)?$_REQUEST['locality']:'';

$latDeg = array_key_exists('latdeg',$_REQUEST)?$_REQUEST['latdeg']:'';
$latMin = array_key_exists('latmin',$_REQUEST)?$_REQUEST['latmin']:'';
$latSec = array_key_exists('latsec',$_REQUEST)?$_REQUEST['latsec']:'';
$latDec = array_key_exists('latdec',$_REQUEST)?$_REQUEST['latdec']:'';
$latNS = array_key_exists('latns',$_REQUEST)?$_REQUEST['latns']:'';

$lngDeg = array_key_exists('lngdeg',$_REQUEST)?$_REQUEST['lngdeg']:'';
$lngMin = array_key_exists('lngmin',$_REQUEST)?$_REQUEST['lngmin']:'';
$lngSec = array_key_exists('lngsec',$_REQUEST)?$_REQUEST['lngsec']:'';
$lngDec = array_key_exists('lngdec',$_REQUEST)?$_REQUEST['lngdec']:'';
$lngEW = array_key_exists('lngew',$_REQUEST)?$_REQUEST['lngew']:'';

$status = array_key_exists('status',$_REQUEST)?$_REQUEST['status']:'';

$geoManager = new GeoreferencingTools();
$geoManager->setCollId($collId);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$localArr = array();
if($editable){
	if($action == 'Generate List'){
		if($country) $geoManager->setQueryVariables('country',$country);
		if($state) $geoManager->setQueryVariables('stateprovince',$state);
		if($county) $geoManager->setQueryVariables('county',$county);
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
					?>
					<div style="font-weight:bold;font-size:130%;">
						<?php echo $geoManager->getCollName(); ?>
					</div>
					<form name="queryform" method="post" action="georeftool.php" onsubmit="return verifyQueryForm(this)">
						<fieldset style="padding:10px;">
							<legend><b>Query Form</b></legend>
							<div style="margin-bottom:3px;">
								<b>Country:</b> 
								<select name="country">
									<option value=''>All Countries</option>
									<?php 
									$cArr = $geoManager->getCountryArr();
									foreach($cArr as $c){
										echo '<option '.($country==$c?'SELECTED':'').'>'.$c.'</option>';
									}
									?>
								</select>
								<span style="margin-left:20px;">
									<b>State: </b>
									<select name="state">
										<option value=''>All States</option>
										<?php 
										$sArr = $geoManager->getStateArr();
										foreach($sArr as $s){
											echo '<option '.($state==$s?'SELECTED':'').'>'.$s.'</option>';
										}
										?>
									</select>
								</span>
							</div>
							<div>
								<b>County:</b> 
								<select name="county">
									<option value=''>All Counties</option>
									<?php 
									$coArr = $geoManager->getCountyArr();
									foreach($coArr as $c){
										echo '<option '.($county==$c?'SELECTED':'').'>'.$c.'</option>';
									}
									?>
								</select>
							</div>
							<div>
								<b>Locality Term:</b> <input name="locality" type="text" value="<?php echo $locality; ?>" />
								<span style="margin-left:50px;">
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
									<input name="querysubmit" type="submit" value="Generate List" />
								</span>
							</div>
						</fieldset> 
					</form>
					<form name="georefform" method="post" action="georeftool.php" onsubmit="return verifyGeorefForm(this)">
						<div style="font-weight:bold;">
							<?php 
							echo 'Return Count: '.$localArr['rowcnt'];
							unset($localArr['rowcnt']);
							?>
						</div>
						<div style="">
							<select name="locallist" size="10" multiple="multiple">
								<?php 
								foreach($localArr as $k => $v){
									$locStr = $v['locality'];
									if($v['extra']) $locStr .= '; '.$v['extra'];
									echo '<option value="'.$v['locality'].'">'.$locStr.'; '.$v['cnt'].'</option>'."\n";
								}
								?>
							</select>
						</div>
						<div style="clear:both;margin:15px;">
							<table>
								<tr>
									<td></td>
									<td><b>Deg.</b></td>
									<td><b>Min.</b></td>
									<td><b>Sec.</b></td>
									<td></td>
									<td></td>
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
									<td>
										<select name="lngew" onchange="updateLngDec(this.form)">
											<option>E</option>
											<option <?php echo (!$lngEW || $lngEW=='W'?'SELECTED':''); ?>>W</option>
										</select>
									</td>
									<td> = </td>
									<td><input name="lngdec" type="text" value="<?php echo $latDec; ?>" style="width:80px;" /></td>
								</tr>
								<tr>
									<td colspan="2">Error (in meters): </td>
									<td colspan="5"><input name="coordinateuncertaintyinmeters" type="text" value="<?php echo $coordUncertainty; ?>" style="width:50px;" /> meters</td>
								</tr>
								<tr>
									<td colspan="2">Sources: </td>
									<td colspan="5"><input name="georeferencesources" type="text" value="<?php echo $georeferenceSources; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td colspan="2">Remarks: </td>
									<td colspan="5"><input name="georeferenceRemarks" type="text" value="<?php echo $georeferenceRemarks; ?>" style="" /></td>
								</tr>
								<tr>
									<td colspan="2">Verification Status: </td>
									<td colspan="5"><input name="georeferenceverificationstatus" type="text" value="<?php echo $georeferenceVerificationStatus; ?>" style="" /></td>
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
					<div style='font-weight:bold;'>
						ERROR: Collection identifier is null
					</div>
					<?php 
				}
			}
			else{
				?>
				<div style='font-weight:bold;'>
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
