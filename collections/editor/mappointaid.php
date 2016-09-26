<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:0; 
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:0; 
$errRad = array_key_exists("errrad",$_REQUEST)?$_REQUEST["errrad"]:0;
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;
if($latDef == 0 && $lngDef == 0){
	$latDef = '';
	$lngDef = '';
} 

$lat = 0; $lng = 0; 
if(is_numeric($latDef) && is_numeric($lngDef)){
	$lat = $latDef; 
	$lng = $lngDef; 
}
elseif($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$lat = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lng = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
$err = 0;
if(is_numeric($errRad)){
	$err = $errRad; 
}
?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	    <script type="text/javascript">
		    var map;
		    var currentMarker;
			var errCircle;
		    
			function initialize(){
		    	var dmLatLng = new google.maps.LatLng(<?php echo $lat.",".$lng; ?>);
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
			
				<?php
				if(is_numeric($latDef) && is_numeric($lngDef)){
					?>
					var mLatLng = new google.maps.LatLng(<?php echo $latDef.",".$lngDef; ?>);
					var marker = new google.maps.Marker({
						position: mLatLng,
						map: map
					});
					currentMarker = marker;
					<?php 
				}
				?>

				google.maps.event.addListener(map, 'click', function(event) {
		            mapZoom = map.getZoom();
		            startLocation = event.latLng;
		            setTimeout("placeMarker()", 500);
		        });
				
				<?php
				if($err && $lat && $lng){
					?>
					errCircle = new google.maps.Circle({
						center: new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $lng; ?>),
						radius: <?php echo $err; ?>,
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: false,
						map: map
					});
					google.maps.event.addListener(errCircle, 'radius_changed', function(){
						var radius = (errCircle.getRadius());
						document.getElementById("uncbox").value = radius.toFixed(0);
					});
					google.maps.event.addListener(errCircle, 'center_changed', function(){
						var latLng = (errCircle.getCenter());
						document.getElementById("latbox").value = latLng.lat().toFixed(6);
						document.getElementById("lngbox").value = latLng.lng().toFixed(6);
					});
					errCircle.bindTo('center', marker, 'position');
					<?php
				}
				?>
				
				var uncbox = document.getElementById("uncbox");
				google.maps.event.addDomListener(
					uncbox, 
					'keyup', 
					function(){ 
						var radius = parseInt(document.getElementById("uncbox").value,10);
						if(errCircle){
							if(radius){
								errCircle.set('radius',radius);
							}
							else{
								errCircle.setMap(null);
								errCircle = '';
							}
						}
						else{
							var errlat = document.getElementById("latbox").value;
							var errlon = document.getElementById("lngbox").value;
							errCircle = new google.maps.Circle({
								center: new google.maps.LatLng(errlat,errlon),
								radius: radius,
								strokeWeight: 0,
								fillOpacity: 0.45,
								editable: true,
								draggable: false,
								map: map
							});
							google.maps.event.addListener(errCircle, 'radius_changed', function(){
								var radius = (errCircle.getRadius());
								document.getElementById("uncbox").value = radius.toFixed(0);
							});
							google.maps.event.addListener(errCircle, 'center_changed', function(){
								var latLng = (errCircle.getCenter());
								document.getElementById("latbox").value = latLng.lat().toFixed(6);
								document.getElementById("lngbox").value = latLng.lng().toFixed(6);
							});
							errCircle.bindTo('center', marker, 'position');
						}
					}
				);
			}
	
	        function placeMarker() {
	    		if(currentMarker) currentMarker.setMap();
	            if(mapZoom == map.getZoom()){
	                var marker = new google.maps.Marker({
	                    position: startLocation,
	                    map: map
	                });
	    			currentMarker = marker;
	
	    	        var latValue = startLocation.lat();
	    	        var lonValue = startLocation.lng();
					if(errCircle){
						errCircle.setCenter(new google.maps.LatLng(latValue,lonValue));
						errCircle.bindTo('center', marker, 'position');
					}
					latValue = latValue.toFixed(5);;
					lonValue = lonValue.toFixed(5);
					document.getElementById("latbox").value = latValue;
					document.getElementById("lngbox").value = lonValue;
	    		}
	        }
			
			function updateParentForm() {
				try{
		            opener.document.getElementById("decimallatitude").value = document.getElementById("latbox").value;
		            opener.document.getElementById("decimallongitude").value = document.getElementById("lngbox").value;
					opener.document.getElementById("coordinateuncertaintyinmeters").value = document.getElementById("uncbox").value;
		            opener.document.getElementById("geodeticdatum").value = "WGS84";
		            opener.document.getElementById("decimallatitude").onchange();
		            opener.document.getElementById("decimallongitude").onchange();
					opener.document.getElementById("coordinateuncertaintyinmeters").onchange();
		            opener.document.getElementById("geodeticdatum").onchange();
				}
				catch(myErr){
				}
				finally{
		            self.close();
	    	        return false;
				}
	        }
	    </script>
	</head> 
	<body style="background-color:#ffffff;" onload="initialize()">
		<div>
			<div style="margin:3px;">
				Click on the map to capture coordinates, or move the marker. Enter a number into the Uncertainty in Meters box to create an error radius circle around the marker.
				You can edit the error radius circle by clicking on any of its sides and dragging to the size you want.
				The Submit Coordinates button will transfer the information to the form. 
			</div>
			<div style="margin-right:30px;">
				<b>Latitude:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="<?php echo $latDef; ?>" />&nbsp;&nbsp;&nbsp; 
				<b>Longitude:</b> <input type="text" id="lngbox" size="13" name="lon" value="<?php echo $lngDef; ?>" /> 
			</div>
			<div style="margin-right:30px;margin-top:3px;margin-bottom:3px;">
				<b>Uncertainty in Meters:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="uncbox" size="13" name="unc" value="<?php echo $errRad; ?>" />&nbsp;&nbsp;&nbsp; 
				<input type="submit" name="addcoords" value="Submit Coordinates" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
			</div>
			<div id='map_canvas' style='width:98%; height:83%; clear:both;'></div>
		</div>
	</body>
</html>
