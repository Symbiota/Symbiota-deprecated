<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
 include_once('../config/symbini.php');
 header("Content-Type: text/html; charset=".$charset);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $defaultTitle; ?> - Coordinate Mapper</title>
  </head> 
  <body onload="initialize()"  onunload="GUnload()">
    <script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $googleMapKey; ?>" type="text/javascript"></script>
    <script type="text/javascript">
      //<![CDATA[
      
      	var zoomLevel = 5;
      	
        function initialize(){

            var map = new GMap2(document.getElementById("map"));
            map.setCenter(new GLatLng(36.97, -109.05), zoomLevel);
            map.setUIToDefault();
            
            GEvent.addListener(map, 'dblclick', function(overlay, point) {
				map.clearOverlays();
				map.zoomIn(point,true);
            });

            GEvent.addListener(map, 'click', function(overlay, point) {
				if(point) {
					map.clearOverlays();
                    var marker = new GMarker(point);
                    map.addOverlay(marker);
                    
					// Add Coords by clicking the map
    		        var latValue = point.y;
    		        var lonValue = point.x;
    		        latValue = latValue.toFixed(5);;
    		        lonValue = lonValue.toFixed(5);
    				document.getElementById("latbox").value = latValue;
                    document.getElementById("lonbox").value = lonValue;
             	}
            });
        }

        function updateParentForm() {
            opener.document.getElementById("pointlat").value = document.getElementById("latbox").value;
            opener.document.getElementById("pointlong").value = document.getElementById("lonbox").value;
            if(opener.document.getElementById("radiustemp").value == ""){
            	opener.document.getElementById("radiustemp").value = 30;
            	opener.document.getElementById("radius").value = 30;
            } 
            self.close();
            return false;
        }

        //]]>
    </script>
    <div>Pan and zoom by double clicking on map. Click once to capture coordinates.  
    Click on the Submit Coordinate button to transfer Coordinates. </div>
    <div id='map' style='width: 500px; height: 400px'></div>
	<form id="mapForm" onsubmit="return updateParentForm();">
		<div>
			Latitude: <input type="text" id="latbox" size="13" name="lat" value="" /> 
		</div>
		<div>
			Longitude: <input type="text" id="lonbox" size="13" name="lon" value="" /> 
			<input type="submit" name="addcoords" value="Submit Coordinates" onclick="addCoordinates()" />	
		</div>
	</form>
  </body>
</html>
