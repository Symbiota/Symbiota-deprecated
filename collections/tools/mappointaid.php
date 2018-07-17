<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');

$errMode = array_key_exists("errmode",$_REQUEST)?$_REQUEST["errmode"]:1;
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;

$lat = 42.877742;
$lng = -97.380979;
if($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$lat = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lng = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
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
				var latCenter = <?php echo $lat; ?>;
				var lngCenter = <?php echo $lng; ?>;
				var latValue = opener.document.getElementById("decimallatitude").value;
				var lngValue = opener.document.getElementById("decimallongitude").value;
				var errRadius = 0;
				if(opener.document.getElementById("coordinateuncertaintyinmeters")) errRadius = opener.document.getElementById("coordinateuncertaintyinmeters").value;
				if(latValue){
					latCenter = latValue;
					lngCenter = lngValue;
					document.getElementById("latbox").value = latValue;
					document.getElementById("lngbox").value = lngValue;
				}
		    	var dmLatLng = new google.maps.LatLng(latCenter,lngCenter);
		    	var dmOptions = {
					zoom: <?php echo $zoom; ?>,
					center: dmLatLng,
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

				if(latValue && lngValue){
					var mLatLng = new google.maps.LatLng(latValue,lngValue);
					var marker = new google.maps.Marker({
						position: mLatLng,
						map: map
					});
					currentMarker = marker;
				}

				google.maps.event.addListener(map, 'click', function(event) {
		            mapZoom = map.getZoom();
		            startLocation = event.latLng;
		            setTimeout("placeMarker()", 500);
		        });
				setErrorRadius(latValue, lngValue, errRadius);
			}

			function coordinatesChanged(){
				var latValue = document.getElementById("latbox").value;
				var lngValue = document.getElementById("lngbox").value;
				if(validateCoordinates(latValue,lngValue)){
					currentMarker.setPosition(new google.maps.LatLng(latValue,lngValue));
				}
			}

			function errRadiusChanged(inputObj){
				var radius = parseInt(inputObj.value,10);
				if(errCircle){
					if(radius){
						errCircle.set('radius',radius);
					}
					else{
						errCircle.setMap(null);
						errCircle = null;
					}
				}
				else{
					setErrorRadius(latValue, lngValue, radius);
				}
	        }

			function setErrorRadius(latValue, lngValue, errRadius){
				if(latValue && lngValue && errRadius > 0){
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
					google.maps.event.addListener(errCircle, 'center_changed', function(){
						var latLng = (errCircle.getCenter());
						document.getElementById("latbox").value = latLng.lat().toFixed(6);
						document.getElementById("lngbox").value = latLng.lng().toFixed(6);
					});
					errCircle.bindTo('center', currentMarker, 'position');
				}
			}

	        function placeMarker() {
	    		if(currentMarker) currentMarker.setMap();
	            if(mapZoom == map.getZoom()){
	            	currentMarker = new google.maps.Marker({
	                    position: startLocation,
	                    map: map
	                });

	    	        var latValue = startLocation.lat();
	    	        var lngValue = startLocation.lng();
					if(errCircle){
						errCircle.setCenter(new google.maps.LatLng(latValue,lngValue));
						errCircle.bindTo('center', currentMarker, 'position');
					}
					document.getElementById("latbox").value = latValue.toFixed(5);
					document.getElementById("lngbox").value = lngValue.toFixed(5);
	    		}
	        }

	        function updateParentForm(f){
					var latObj = opener.document.getElementById("decimallatitude");
					var lngObj = opener.document.getElementById("decimallongitude");
					latObj.value = f.latbox.value;
					lngObj.value = f.lngbox.value;
				try{
					lngObj.onchange();
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
				<div style="float:right;margin:5px 20px"><button name="addcoords" type="button" onclick="updateParentForm(this.form);">Submit Coordinates</button></div>
				<div style="margin:3px;">
					Click on the map to capture coordinates, or move the marker.<br/>
					<?php if($errMode) echo 'Enter uncertainty to create an error radius circle around the marker.<br/>'; ?>
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
