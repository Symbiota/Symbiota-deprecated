<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:0; 
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:0; 
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;
if(!$latDef && !$lngDef){
	$latDef = '';
	$lngDef = '';
} 
?>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $defaultTitle; ?> - Coordinate Aid</title>
  </head> 
  <body onload="initialize()"  onunload="GUnload()">
  	<?php
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
    <script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $googleMapKey; ?>" type="text/javascript"></script>
    <script type="text/javascript">
      //<![CDATA[
      
		var zoomLevel = <?php echo $zoom; ?>;
      	
		function initialize(){

			var map = new GMap2(document.getElementById("map"));

				<?php
				if(is_numeric($latDef) && is_numeric($lngDef)){
					echo "map.addOverlay(new GMarker(new GLatLng(".$lat.",".$lng.")));\n";
				}
				?>
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
	            opener.document.fullform.decimallatitude.value = document.getElementById("latbox").value;
	            opener.document.fullform.decimallongitude.value = document.getElementById("lngbox").value;
	            opener.document.fullform.geodeticdatum.value = "WGS84";
	            opener.document.fullform.decimallatitude.onchange();
	            opener.document.fullform.decimallongitude.onchange();
			}
			catch(myErr){
			}
			finally{
	            self.close();
    	        return false;
			}
        }

        //]]>
    </script>
		<div style="width:770px;">
			<div>
				Use navigation controls to pan and zoom. Click once to capture coordinates.  
				Submit Coordinate button will transfer to form. 
			</div>
			<div style="margin-right:30px;">
				<b>Latitude:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="<?php echo $latDef; ?>" />&nbsp;&nbsp;&nbsp; 
				<b>Longitude:</b> <input type="text" id="lngbox" size="13" name="lon" value="<?php echo $lngDef; ?>" /> 
				<input type="submit" name="addcoords" value="Submit Coordinates" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
			</div>
			<div style="clear:both;">
				<div id='map' style='width:750px;height:650px;'></div>
			</div>
		</div>
	</body>
</html>
