<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formName = array_key_exists("formname",$_REQUEST)?$_REQUEST["formname"]:"";
$latName = array_key_exists("latname",$_REQUEST)?$_REQUEST["latname"]:""; 
$longName = array_key_exists("longname",$_REQUEST)?$_REQUEST["longname"]:""; 
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
elseif($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$lat = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lng = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
else{
	$lat = 42.877742;
	$lng = -97.380979;
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	    <script type="text/javascript">
		    var map;
		    var currentMarker;
			var drawingManager = null;
			var selectedShape = null;
			
			function initialize(){
				var latCenter = <?php echo $lat; ?>;
				var lngCenter = <?php echo $lng; ?>;
				var footPolyArr = [];
				var footPolyPaths = [];
				var validWKT = true;
				var polyBounds = new google.maps.LatLngBounds();
				if(opener.document.getElementById("footprintWKT").value != ''){
					var jsonTest = true;
					try{
						JSON.parse(opener.document.getElementById("footprintWKT").value);
					}catch(e){
						jsonTest = false;
					}
					if(jsonTest){
						footPolyArr = JSON.parse(opener.document.getElementById("footprintWKT").value);
					}
					else if(!isNaN(opener.document.getElementById("footprintWKT").value.substr(0,opener.document.getElementById("footprintWKT").value.indexOf(',')))){
						var numWktArr = opener.document.getElementById("footprintWKT").value.split(",");
						if(numWktArr.length % 2 === 0){
							for(i=0;i<numWktArr.length;i+=2){
								var b = i+1;
								footPolyArr.push({"A":numWktArr[i],"D":numWktArr[b]});
							}
							footPolyArr.push({"A":numWktArr[0],"D":numWktArr[1]});
						}
						else{
							alert("The footprint is not in the proper format. Please recreate it using the map tools.");
						}
					}
					else if((opener.document.getElementById("footprintWKT").value.substring(0,10) == "POLYGON ((") && (opener.document.getElementById("footprintWKT").value.lastIndexOf('))') == opener.document.getElementById("footprintWKT").value.length - '))'.length)){
						var cleanedStr = opener.document.getElementById("footprintWKT").value.replace("POLYGON ((","");
						cleanedStr = cleanedStr.replace("))","");
						var cleanedStrArr = cleanedStr.split(",");
						var validForm = true;
						for(i=0;i<cleanedStrArr.length;i++){
							var partsArr = cleanedStrArr[i].trim().split(" ");
							if(i===0){
								var firstParts = partsArr;
							}
							if(partsArr.length % 2 === 0){
								footPolyArr.push({"A":partsArr[1],"D":partsArr[0]});
							}
							else{
								alert("The footprint is not in the proper format. Please recreate it using the map tools.");
								validForm = false;
								footPolyArr = [];
								break;
							}
						}
						if(validForm){
							footPolyArr.push({"A":firstParts[1],"D":firstParts[0]});
						}
					}
					else{
						alert("The footprint is not in the proper format. Please recreate it using the map tools.");
					}
				}
				if(footPolyArr.length > 0){
					for(i in footPolyArr){
						var keys = Object.keys(footPolyArr[i]);
						if(!isNaN(footPolyArr[i][keys[0]]) && !isNaN(footPolyArr[i][keys[1]])){
							footPolyPaths.push(new google.maps.LatLng(footPolyArr[i][keys[0]],footPolyArr[i][keys[1]]));
							polyBounds.extend(new google.maps.LatLng(footPolyArr[i][keys[0]],footPolyArr[i][keys[1]]));
						}
						else{
							alert("The footprint is not in the proper format. Please recreate it using the map tools.");
							validWKT = false;
							break;
						}
					}
					if(validWKT){
						footPolyPaths.push(new google.maps.LatLng(footPolyArr[0][keys[0]],footPolyArr[0][keys[1]]));
						polyBounds.extend(new google.maps.LatLng(footPolyArr[0][keys[0]],footPolyArr[0][keys[1]]));
					}
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
				
				// Clear the current selection when the drawing mode is changed, or when the
				// map is clicked.
				google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
				google.maps.event.addListener(map, 'click', clearSelection);
				google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteSelectedShape);
				
				if(footPolyPaths.length > 0){
					var footPoly = new google.maps.Polygon({
						paths: footPolyPaths,
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: true,
						map: map
					});
					footPoly.type = 'polygon';google.maps.event.addListener(footPoly, 'click', function() {
						setSelection(footPoly);
					});
					google.maps.event.addListener(footPoly, 'dragend', function() {setSelection(footPoly);});
					google.maps.event.addListener(footPoly.getPath(), 'insert_at', function() {
						setSelection(footPoly);
					});
					google.maps.event.addListener(footPoly.getPath(), 'remove_at', function() {
						setSelection(footPoly);
					});
					google.maps.event.addListener(footPoly.getPath(), 'set_at', function() {
						setSelection(footPoly);
					});
					setSelection(footPoly);
					map.fitBounds(polyBounds);
					map.panToBounds(polyBounds);
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
				if (selectedShapeType == 'polygon') {
					getPolygonCoords(shape);
				}
			}

			function deleteSelectedShape() {
				if (selectedShape){
					selectedShape.setMap(null);
					clearSelection();
					updateParentForm();
				}
			}

			function getPolygonCoords(polygon) {
				var coordinates = [];
				var coordinatesMVC = (polygon.getPath().getArray());
				for(i=0;i<coordinatesMVC.length;i++){
					var mvcString = coordinatesMVC[i].toString();
					mvcString = mvcString.replace("(","");
					mvcString = mvcString.replace(")","");
					var latlngArr = mvcString.split(", ");
					coordinates.push({"A":latlngArr[0],"D":latlngArr[1]});
					if(i==0){
						var firstSet = latlngArr;
					}
				}
				coordinates.push({"A":firstSet[0],"D":firstSet[1]});
				var json_coords = JSON.stringify(coordinates);
				document.getElementById("poly_array").value = json_coords;
				document.getElementById("polycoord_message").style.display = "block";
			}

	        function updateParentForm() {
				if(opener.document.getElementById("footprintWKT")){
					opener.document.getElementById("footprintWKT").value = document.getElementById("poly_array").value;
					try{
						opener.document.getElementById("footprintWKT").onchange();
					}
					catch(myErr){}
				}
				if(opener.document.getElementById("polysavebox")){
					opener.document.getElementById("polysavebox").style.display = "block";
				}
				if(opener.document.getElementById("polyexistsbox")){
					opener.document.getElementById("polyexistsbox").style.display = "none";
				}
				if(opener.document.getElementById("polycreatebox")){
					opener.document.getElementById("polycreatebox").style.display = "none";
				}
				self.close();
	            return false;
	        }
	    </script>

	</head> 
	<body style="background-color:#ffffff;" onload="initialize()">
		<div style="width:770px;height:650px;">
			<div>
				Use the polygon tool to capture polygon coordinates.  
				Submit Polygon button will transfer polygon to form. 
			</div>
			<div style="margin-top:8px;">
				<div id="polycoord_message" style="float:left;display:none;">
					<b>Polygon coordinates ready to submit.</b>  
				</div>
				<div style="float:right;margin-right:30px;">
					<input type="hidden" id="poly_array" name="poly_array" value='' />
					<button type="submit" name="addcoords" value="Submit Polygon" onclick="updateParentForm();">Submit</button>&nbsp;&nbsp;&nbsp;
					<button id="delete-button">Delete Shape</button>
				</div>
			</div>
			<div id='map_canvas' style='width:95%; height:90%; clear:both;'></div>
		</div>
	</body>
</html>