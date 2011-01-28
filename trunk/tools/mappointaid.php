<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
$formName = array_key_exists("formname",$_REQUEST)?$_REQUEST["formname"]:""; 
$latName = array_key_exists("latname",$_REQUEST)?$_REQUEST["latname"]:""; 
$longName = array_key_exists("longname",$_REQUEST)?$_REQUEST["longname"]:""; 
?>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $defaultTitle; ?> - Coordinate Aid</title>
  </head> 
  <body onload="initialize()"  onunload="GUnload()">
  	<?php 
  		$boundaryArr = explode(";",$mappingBoundaries);
  		$lat = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
  		$lng = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
  	?>
    <script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $googleMapKey; ?>" type="text/javascript"></script>
    <script type="text/javascript">
      //<![CDATA[
      
      	var zoomLevel = 5;
      	
        function initialize(){

            var map = new GMap2(document.getElementById("map"));

            map.setCenter(new GLatLng(<?php echo $lat.",".$lng;?>), zoomLevel);
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
                    document.getElementById("lngbox").value = lonValue;
             	}
            });
        }

        function updateParentForm() {
			try{
	            opener.document.<?php echo $formName.'.'.$latName; ?>.value = document.getElementById("latbox").value;
	            opener.document.<?php echo $formName.'.'.$longName; ?>.value = document.getElementById("lngbox").value;
			}
			catch(myErr){
				alert("Unable to transfer data. Please let an administrator know.");
			}
			finally{
	            self.close();
    	        return false;
			}
        }

        //]]>
    </script>
    <div>Use navigation controls to pan and zoom. Click once to capture coordinates.  
    	Click on the Submit Coordinate button to transfer Coordinates. 
    </div>
	<div style="margin-top:18px;">
		Latitude:&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="" />&nbsp;&nbsp;&nbsp; 
		Longitude: <input type="text" id="lngbox" size="13" name="lon" value="" /> 
		<input type="submit" name="addcoords" value="Submit Coordinates" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
		 
	</div>
    <div style="clear:both;">
	    <div id='map' style='width: 750px; height: 650px'></div>
	</div>
  </body>
</html>
