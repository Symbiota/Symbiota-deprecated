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
      	var map;
        var r1=null;
        var myrectangle;
        var firstClick = true;
        var eventOnMove;
        var eventOnClick;
      	
        function initialize(){
            if (GBrowserIsCompatible()) {
	            map = new GMap2(document.getElementById("map"));
	            map.setCenter(new GLatLng(36.97, -109.05), zoomLevel);
                //map.addControl(new GScaleControl());
                map.enableScrollWheelZoom();
	            map.setUIToDefault();
	            eventOnClick = GEvent.addListener(map, 'click', mapClickRectangle);
	            GEvent.addListener(map, 'dblclick', function(overlay, point) {
					document.getElementById("nlat").value = "";
					document.getElementById("slat").value = "";
					document.getElementById("elon").value = "";
					document.getElementById("wlon").value = "";
	            	map.clearOverlays();
	                firstClick = true;
					GEvent.removeListener(eventOnMove);
	            });
            }            
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

        function mapDragRectangle(point){
        	if(!firstClick){
                drawRectangle(r1,point);
            }
        }

        function drawRectangle(a,b){
			if(a && b){
	            if (myrectangle != null) {
	                map.removeOverlay(myrectangle);
	            }
	            myrectangle = new GPolygon(new Array(a,new GPoint(a.x,b.y),b,new GPoint(b.x,a.y),a),'#fd942d',1,1,'#96bdfe',.5);
	            map.addOverlay(myrectangle);
			}
        }

        function updateParentForm() {
            opener.document.getElementById("upperlat").value = document.getElementById("nlat").value;
            opener.document.getElementById("bottomlat").value = document.getElementById("slat").value;
            opener.document.getElementById("leftlong").value = document.getElementById("wlon").value;
            opener.document.getElementById("rightlong").value = document.getElementById("elon").value;
            self.close();
            return false;
        }

        //]]>
    </script>
    <div style="width:500px;">Double click to pan and zoom, click once to start drawing and again to finish rectangle, and 
    click on the Submit button to transfer Coordinates.</div>
    <div id='map' style='width:100%; height: 520px'></div>
	<form id="mapForm" onsubmit="return updateParentForm();">
		<table>
			<tr><td>
				Northern Lat: <input type="text" id="nlat" size="13" name="nlat" value="" />
			</td><td>
				Eastern Long: <input type="text" id="elon" size="13" name="elon" value="" />
			</td></tr>
			<tr><td>
				Southern Lat: <input type="text" id="slat" size="13" name="slat" value="" />
			</td><td>
				Western Long: <input type="text" id="wlon" size="13" name="wlon" value="" />
				<input type="submit" name="addcoords" value="Submit" onclick="addCoordinates()" />	
			</td></tr>
		</table>
	</form>
  </body>
</html>
