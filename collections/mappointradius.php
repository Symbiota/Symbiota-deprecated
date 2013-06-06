<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);

$boundaryArr = explode(";",$mappingBoundaries);
$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
?>
<!DOCTYPE html >
<html>
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
            map.setCenter(new GLatLng(<?php echo $latCenter.','.$lngCenter; ?>), zoomLevel);
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
    		        latValue = latValue.toFixed(5);
    		        lonValue = lonValue.toFixed(5);
    				document.getElementById("latbox").value = latValue;
                    document.getElementById("lonbox").value = lonValue;
             	}
            });
        }

        function mapClickRectangle(overlay,point){
        	if(firstClick){   // First click
            	map.clearOverlays();
                r1 = point;
                myrectangle = null;
                firstClick = false;
                eventOnMove = GEvent.addListener(map, 'mousemove',mapDragRectangle);
				document.getElementById("nlat").value = "";
				document.getElementById("slat").value = "";
				document.getElementById("elon").value = "";
				document.getElementById("wlon").value = "";
            }
            else{   // Second click
				GEvent.removeListener(eventOnMove);
				if(point){
	            	map.clearOverlays();
	                firstClick = true;
				}
				else{
	                //Add Coords by clicking the map
					var bounds = myrectangle.getBounds();
	                var sw = bounds.getSouthWest();
	                var ne = bounds.getNorthEast();
					document.getElementById("nlat").value = ne.lat().toFixed(5);
					document.getElementById("slat").value = sw.lat().toFixed(5);
					document.getElementById("elon").value = ne.lng().toFixed(5);
					document.getElementById("wlon").value = sw.lng().toFixed(5);
				}
            }
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
    <div>Click once to capture coordinates.  
    Click on the Submit Coordinate button to transfer Coordinates. </div>
    <div id='map' style='width: 100%; height: 520px'></div>
	<form id="mapForm" onsubmit="return updateParentForm();">
		<div>
			Latitude: <input type="text" id="latbox" size="13" name="lat" value="" />&nbsp;&nbsp;&nbsp; 
			Longitude: <input type="text" id="lonbox" size="13" name="lon" value="" /> &nbsp;&nbsp;&nbsp;
			<input type="submit" name="addcoords" value="Submit Coordinates" />	
		</div>
	</form>
  </body>
</html>
