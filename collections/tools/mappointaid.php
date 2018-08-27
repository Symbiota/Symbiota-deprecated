<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');

$errMode = array_key_exists("errmode",$_REQUEST)?$_REQUEST["errmode"]:1;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Point-Radius Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
		<script type="text/javascript">
			var map;
			var startLocation;
			var currentMarker;
			var errCircle;

			function initialize(){
				var latCenter = opener.document.getElementById("decimallatitude").value;
				var lngCenter = opener.document.getElementById("decimallongitude").value;
				if(latCenter){
					document.getElementById("latbox").value = latCenter;
					document.getElementById("lngbox").value = lngCenter;
				}
				var errRadius = 0;
				if(opener.document.getElementById("coordinateuncertaintyinmeters") && opener.document.getElementById("coordinateuncertaintyinmeters").value){
					errRadius = opener.document.getElementById("coordinateuncertaintyinmeters").value;
					document.getElementById("errRadius").value = opener.document.getElementById("coordinateuncertaintyinmeters").value;
				}

				var dmOptions = {
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};

				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

				var polyOptions = {
					strokeWeight: 0,
					fillOpacity: 0.45,
					editable: false,
					draggable: false
				};

				var drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: null,
					drawingControl: false,
					circleOptions: polyOptions
				});

				drawingManager.setMap(map);

				if(latCenter && lngCenter){
					startLocation = new google.maps.LatLng(latCenter,lngCenter);
					setMarker();
				}

				google.maps.event.addListener(map, 'click', function(event) {
					mapZoom = map.getZoom();
					startLocation = event.latLng;
					setTimeout("placeMarker()", 500);
				});
				setErrorRadius(latCenter, lngCenter, errRadius);

				if(!errCircle){
					if(latCenter){
						map.setCenter(new google.maps.LatLng(latCenter,lngCenter));
						map.setZoom(16);
					}
					else{
						var bounds = new google.maps.LatLngBounds();
						<?php
						$boundArr = array();
						if($MAPPING_BOUNDARIES){
							$boundArr = explode(";",$MAPPING_BOUNDARIES);
						}
						if(!$boundArr || count($boundArr) != 4){
							$boundArr[0] = 51;
							$boundArr[1] = -65;
							$boundArr[2] = 25;
							$boundArr[3] = -125;
						}
						echo 'bounds.extend(new google.maps.LatLng('.$boundArr[2].','.$boundArr[3].'));'."\n";
						echo 'bounds.extend(new google.maps.LatLng('.$boundArr[0].','.$boundArr[1].'));'."\n";
						?>
						map.fitBounds(bounds);
						map.panToBounds(bounds);
					}
				}
			}

			function placeMarker() {
				if(currentMarker) currentMarker.setMap();
				if(mapZoom == map.getZoom()){
					setMarker();
				}
			}

			function setMarker(){
				currentMarker = new google.maps.Marker({
					position: startLocation,
					draggable: true,
					map: map
				});
				currentMarker.addListener('position_changed', function(){
					var latLng = (currentMarker.getPosition());
					document.getElementById("latbox").value = latLng.lat().toFixed(6);
					document.getElementById("lngbox").value = latLng.lng().toFixed(6);
				});
				if(errCircle) errCircle.bindTo('center', currentMarker, 'position');

				document.getElementById("latbox").value = startLocation.lat().toFixed(6);
				document.getElementById("lngbox").value = startLocation.lng().toFixed(6);

				map.setCenter(currentMarker.getPosition());
				if(map.getZoom() < 8) map.setZoom(8);
			}

			function setErrorRadius(latValue, lngValue, errRadius){
				errRadius = parseInt(errRadius);
				if(latValue && lngValue && isNumeric(errRadius) && errRadius > 0){
					errCircle = new google.maps.Circle({
						center: new google.maps.LatLng(latValue, lngValue),
						radius: errRadius,
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: true,
						map: map
					});
					google.maps.event.addListener(errCircle, 'radius_changed', function(){
						var radius = (errCircle.getRadius());
						document.getElementById("errRadius").value = radius.toFixed(0);
					});
					errCircle.bindTo('center', currentMarker, 'position');
					var bounds = errCircle.getBounds();
					map.fitBounds(bounds);
					map.panToBounds(bounds);
				}
			}

			function coordinatesChanged(){
				var latValue = document.getElementById("latbox").value;
				var lngValue = document.getElementById("lngbox").value;
				if(validateCoordinates(latValue,lngValue)){
					currentMarker.setPosition(new google.maps.LatLng(latValue,lngValue));
					map.setCenter(currentMarker.getPosition());
					if(map.getZoom() < 8) map.setZoom(8);
				}
			}

			function errRadiusChanged(inputObj){
				var radius = parseInt(inputObj.value,10);
				if(errCircle){
					if(radius){
						errCircle.setRadius(radius);
						var bounds = errCircle.getBounds();
						map.fitBounds(bounds);
						map.panToBounds(bounds);
					}
					else{
						errCircle.setMap(null);
						errCircle = null;
					}
				}
				else{
					if(currentMarker){
						setErrorRadius(currentMarker.getPosition().lat(), currentMarker.getPosition().lng(), radius);
					}
				}
			}

			function updateParentForm(f){
				opener.document.getElementById("decimallatitude").value = f.latbox.value;
				opener.document.getElementById("decimallongitude").value = f.lngbox.value;
				try{
					if(opener.document.getElementById("coordinateuncertaintyinmeters")){
						opener.document.getElementById("coordinateuncertaintyinmeters").value = f.errRadius.value;
						opener.document.getElementById("coordinateuncertaintyinmeters").onchange();
					}
					if(opener.document.getElementById("geodeticdatum")){
						opener.document.getElementById("geodeticdatum").value = "WGS84";
						opener.document.getElementById("geodeticdatum").onchange();
					}
					opener.document.getElementById("decimallatitude").onchange();
					opener.document.getElementById("decimallongitude").onchange();
				}
				catch(myErr){
					//alert("Unable to trigger onchange");
				}
				finally{
					self.close();
					return false;
				}
			}

			function validateCoordinates(latValue,lngValue){
				if(!isNumeric(latValue)){
					alert("Latitude must be a numeric value only");
					return false;
				}
				else if(!isNumeric(lngValue)){
					alert("Longitude must be a numeric value only");
					return false;
				}
				else if(parseInt(Math.abs(latValue)) > 90){
					alert("Latitude coordinates are out-of-range (e.g. > 90 or < -90)");
					return false;
				}
				else if(parseInt(Math.abs(lngValue)) > 180){
					alert("Longitude coordinates are out-of-range (e.g. > 180 or < -180)");
					return false;
				}
				return true;
			}

			function isNumeric(n) {
				return !isNaN(parseFloat(n)) && isFinite(n);
			}
		</script>

	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<div style="">
			<form name="coordform" action="" method="post" onsubmit="return false">
				<div style="float:right;margin:5px 20px">
					<button name="addcoords" type="button" onclick="updateParentForm(this.form);">Submit Coordinates</button><br/>
					<button name="refreshbutton" type="button" onclick="return false">Refresh Map</button>
				</div>
				<div style="margin:3px 20px 3px 0px;">
					Click on the map to capture coordinates, or drag marker.
					<?php if($errMode) echo 'Enter uncertainty to create an error radius circle around the marker. '; ?>
					The Submit Coordinates button will transfer the information to form.
				</div>
				<div style="margin-right:10px;">
					<b>Latitude:</b> <input type="text" id="latbox" name="lat" onchange="coordinatesChanged()" style="width:100px" />
					<b>Longitude:</b> <input type="text" id="lngbox" name="lon" onchange="coordinatesChanged()" style="width:100px" />
				<?php
				if($errMode){
					?>
					<b>Uncertainty in Meters:</b> <input type="text" id="errRadius" name="errRadius" size="13" onchange="errRadiusChanged(this)" />
					<?php
				}
				?>
				</div>
			</form>
			<div id='map_canvas' style='width:100%; height:88%; clear:both;'></div>
		</div>
	</body>
</html>
