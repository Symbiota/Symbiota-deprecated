<!DOCTYPE html>
<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistAdmin.php');
header("Content-Type: text/html; charset=".$charset);
$formName = array_key_exists("formname",$_REQUEST)?$_REQUEST["formname"]:"";
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$latName = array_key_exists("latname",$_REQUEST)?$_REQUEST["latname"]:""; 
$longName = array_key_exists("longname",$_REQUEST)?$_REQUEST["longname"]:""; 
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:0; 
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:0; 
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;

$clManager = new ChecklistAdmin();
if($clid){
	$clManager->setClid($clid);
	$clArray = $clManager->getMetaData();
	if($clArray["footprintWKT"]){
		$footprintPoly = $clManager->createFootprint($clArray["footprintWKT"]);
	}
}
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
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=drawing"></script>
		</script>
	    <script type="text/javascript">
		    var map;
		    var currentMarker;
			var drawingManager = null;
			var selectedShape = null;
	      	
			function initialize(){
				var latCenter = <?php echo $lat; ?>;
				var lngCenter = <?php echo $lng; ?>;
				var latValue = opener.document.<?php echo $formName.'.'.$latName; ?>.value;
				var lngValue = opener.document.<?php echo $formName.'.'.$longName; ?>.value;
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
					editable: true,
					draggable: true
				};
				
				var drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: null,
					drawingControl: true,
					drawingControlOptions: {
						position: google.maps.ControlPosition.TOP_CENTER,
						drawingModes: [
							google.maps.drawing.OverlayType.POLYGON
						]
					},
					markerOptions: {
						draggable: true
					},
					polygonOptions: polyOptions
				});
		
				drawingManager.setMap(map);
				
				google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
					if (e.type != google.maps.drawing.OverlayType.MARKER) {
						// Switch back to non-drawing mode after drawing a shape.
						drawingManager.setDrawingMode(null);
						
						var newShapeType = '';
						newShapeType = e.type;
						// Add an event listener that selects the newly-drawn shape when the user
						// mouses down on it.
						var newShape = e.overlay;
						newShape.type = e.type;
						google.maps.event.addListener(newShape, 'click', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape, 'dragend', function() {
							setSelection(newShape);
						});
						if (newShapeType == 'polygon'){
							getPolygonCoords(newShape);
							google.maps.event.addListener(newShape.getPath(), 'insert_at', function() {
								setSelection(newShape);
							});
							google.maps.event.addListener(newShape.getPath(), 'remove_at', function() {
								setSelection(newShape);
							});
							google.maps.event.addListener(newShape.getPath(), 'set_at', function() {
								setSelection(newShape);
							});
						}
						setSelection(newShape);
					}	
				});
				
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
					clearSelection();
		            setTimeout("placeMarker()", 500);
		        });
				
				// Clear the current selection when the drawing mode is changed, or when the
				// map is clicked.
				google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
				//google.maps.event.addListener(map, 'click', clearSelection);
				google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteSelectedShape);
				
				<?php echo ($footprintPoly?$footprintPoly:''); ?>
				
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
			
			function clearSelection() {
				if (selectedShape) {
					selectedShape.setEditable(false);
					selectedShape = null;
				}
				document.getElementById("poly_array").value = '';
				document.getElementById("polycoord_message").style.display = "none";
			}

			function setSelection(shape) {
				clearSelection();
				var selectedShapeType = shape.type;
				selectedShape = shape;
				selectedShape.setEditable(true);
				//selectColor(shape.get('fillColor') || shape.get('strokeColor'));
				if (selectedShapeType == 'polygon') {
					getPolygonCoords(shape);
				}
			}

			function deleteSelectedShape() {
				if (selectedShape){
					selectedShape.setMap(null);
					clearSelection();
				}
			}

			function getPolygonCoords(polygon) {
				var coordinates = [];
				coordinates = (polygon.getPath().getArray());
				var json_coords = JSON.stringify(coordinates);
				document.getElementById("poly_array").value = json_coords;
				document.getElementById("polycoord_message").style.display = "block";
			}

	        function updateParentForm() {
				try{
		            opener.document.<?php echo $formName.'.'.$latName; ?>.value = document.getElementById("latbox").value;
		            opener.document.<?php echo $formName.'.'.$longName; ?>.value = document.getElementById("lngbox").value;
					opener.document.getElementById("footprintWKT").value = document.getElementById("poly_array").value;
					opener.document.getElementById("polybox").style.display = "block";
				}
				catch(myErr){
					alert("Unable to transfer data. Please let an administrator know.");
				}
	            self.close();
	            return false;
	        }
	    </script>

	</head> 
	<body onload="initialize()">
		<div style="width:770px;height:650px;">
			<div>
				Click once to capture point coordinates, or use the polygon tool to capture polygon coordinates.  
				Submit Coordinate button will transfer to form. 
			</div>
			<div style="margin-right:30px;">
				<b>Latitude:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="<?php echo $latDef; ?>" />&nbsp;&nbsp;&nbsp; 
				<b>Longitude:</b> <input type="text" id="lngbox" size="13" name="lon" value="<?php echo $lngDef; ?>" /> 
				<input type="hidden" id="poly_array" name="poly_array" value='' />
				<input type="submit" name="addcoords" value="Submit Coordinates" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
				<button id="delete-button">Delete Selected Shape</button>
			</div>
			<div id="polycoord_message" style="display:none;">
				Polygon coordinates ready to submit.  
			</div>
			<div id='map_canvas' style='width:95%; height:90%; clear:both;'></div>
		</div>
	</body>
</html>
