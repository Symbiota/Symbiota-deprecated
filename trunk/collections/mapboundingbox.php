<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);

$boundaryArr = explode(";",$mappingBoundaries);
$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
?>

<html>
	<head>
		<title><?php echo $defaultTitle; ?> - Coordinate Mapper</title>
	</head> 
	<body style="background-color:#ffffff;">
	<script src="http://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>
	<script type="text/javascript">
      	var map;
      	var rectangle;
		var latCenter = <?php echo $latCenter; ?>;
		var lngCenter = <?php echo $lngCenter; ?>;

        function initialize(){
			var dmOptions = {
				zoom: 5,
				center: new google.maps.LatLng(latCenter, lngCenter),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};
	
	    	map = new google.maps.Map(document.getElementById("map"), dmOptions);

			//placeRectangle(latCenter, lngCenter);

			google.maps.event.addListener(map, 'click', function(event) {
				if(rectangle) rectangle.setMap(null);
				placeRectangle(event.latLng.lat(),event.latLng.lng());
			});
        }

		function placeRectangle(lat, lng){
			var boxWidth;
			if(map.getBounds()){
				var mapBounds = map.getBounds();
				boxWidth = (mapBounds.getNorthEast().lat() - mapBounds.getSouthWest().lat())/8;
			}
			else{
				boxWidth = 1;
			}
			var newBounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(lat - boxWidth, lng - boxWidth),
				new google.maps.LatLng(lat + boxWidth, lng + boxWidth)
			);

			// Define a rectangle and set its editable property to true.
			rectangle = new google.maps.Rectangle({
				bounds: newBounds,
				editable: true,
				draggable: true
			});

			google.maps.event.addListener(rectangle, 'bounds_changed', function(event) {
				recordRectBounds(rectangle.getBounds());
			});
			
			rectangle.setMap(map);

			recordRectBounds(newBounds);
		}

		function recordRectBounds(bounds){
			var ne = bounds.getNorthEast();
			var sw = bounds.getSouthWest();
			document.getElementById("nlat").value = ne.lat().toFixed(5);
			document.getElementById("slat").value = sw.lat().toFixed(5);
			document.getElementById("wlon").value = sw.lng().toFixed(5);
			document.getElementById("elon").value = ne.lng().toFixed(5);
		}

		function updateParentForm() {
			opener.document.getElementById("upperlat").value = document.getElementById("nlat").value;
			opener.document.getElementById("bottomlat").value = document.getElementById("slat").value;
			opener.document.getElementById("leftlong").value = document.getElementById("wlon").value;
			opener.document.getElementById("rightlong").value = document.getElementById("elon").value;
			self.close();
			return false;
		}

		google.maps.event.addDomListener(window, 'load', initialize);

    </script>
    <div style="width:500px;">Click once to start drawing and again to finish rectangle. 
    Click on the Submit button to transfer Coordinates.</div>
    <div id='map' style='width:100%; height: 520px'></div>
	<form id="mapForm" onsubmit="return updateParentForm();">
		<table>
			<tr><td>
				Northern Lat: <input type="text" id="nlat" size="13" name="nlat" value="" />
			</td><td>
				Eastern Long: <input type="text" id="elon" size="13" name="elon" value="" />
				<input type="submit" name="addcoords" value="Submit" />	
			</td></tr>
			<tr><td>
				Southern Lat: <input type="text" id="slat" size="13" name="slat" value="" />
			</td><td>
				Western Long: <input type="text" id="wlon" size="13" name="wlon" value="" />
			</td></tr>
		</table>
	</form>
  </body>
</html>
