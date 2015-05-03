<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:0; 
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:0; 
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
else{
	$boundaryArr = explode(";",$mappingBoundaries);
	$lat = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lng = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false">
		</script>
	    <script type="text/javascript">
		    var map;
		    var currentMarker;
		    
			function initialize(){
		    	var dmLatLng = new google.maps.LatLng(<?php echo $lat.",".$lng; ?>);
		    	var dmOptions = {
					zoom: <?php echo $zoom; ?>,
					center: dmLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};
		    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
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
		            opener.document.getElementById("geodeticdatum").value = "WGS84";
		            opener.document.getElementById("decimallatitude").onchange();
		            opener.document.getElementById("decimallongitude").onchange();
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
		<div style="width:770px;height:650px;">
			<div style="margin:3px;">
				Click once to capture coordinates.  
				Submit Coordinate button will transfer to form. 
			</div>
			<div style="margin-right:30px;">
				<b>Latitude:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="<?php echo $latDef; ?>" />&nbsp;&nbsp;&nbsp; 
				<b>Longitude:</b> <input type="text" id="lngbox" size="13" name="lon" value="<?php echo $lngDef; ?>" /> 
				<input type="submit" name="addcoords" value="Submit Coordinates" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
			</div>
			<div id='map_canvas' style='width:95%; height:90%; clear:both;'></div>
		</div>
	</body>
</html>
