<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formName = array_key_exists("formname",$_REQUEST)?$_REQUEST["formname"]:"";
$latName = array_key_exists("latname",$_REQUEST)?$_REQUEST["latname"]:"";
$longName = array_key_exists("longname",$_REQUEST)?$_REQUEST["longname"]:"";
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:'';
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:'';
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;

if($latDef == 0 && $lngDef == 0){
	$latDef = '';
	$lngDef = '';
}

$latCenter = 0; $lngCenter = 0;
if(is_numeric($latDef) && is_numeric($lngDef)){
	$latCenter = $latDef;
	$lngCenter = $lngDef;
	$zoom = 12;
}
elseif($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
else{
	$latCenter = 42.877742;
	$lngCenter = -97.380979;
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	    <script type="text/javascript">
		    var map;
			var selectedShape = null;

			function initialize(){
				if(opener.document.getElementById("footprintWKT").value != ''){
					document.getElementById('poly_array').value = opener.document.getElementById("footprintWKT").value;
				}
				var dmLatLng = new google.maps.LatLng(<?php echo $latCenter.','.$lngCenter; ?>);
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

						var newShapeType = e.type;
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
							setPolygonStr(newShape);
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
				setPolygon();
			}

			function setPolygon(){
				var pointArr = [];
				var polyBounds = new google.maps.LatLngBounds();
				if(document.getElementById('poly_array').value != ''){
					var footprintWKT = document.getElementById("poly_array").value;
					if(footprintWKT.substring(0,10) == "POLYGON (("){
						//Reduce only points of WKT format
						footprintWKT = footprintWKT.slice(10,-2);
					}
					if(footprintWKT.substring(0,2) == "[{"){
						//Translate old json format to wkt
						try{
							var footPolyArr = JSON.parse(footprintWKT);
							for(i in footPolyArr){
								var keys = Object.keys(footPolyArr[i]);
								if(!isNaN(footPolyArr[i][keys[0]]) && !isNaN(footPolyArr[i][keys[1]])){
									var pt = new google.maps.LatLng(footPolyArr[i][keys[0]],footPolyArr[i][keys[1]]);
									pointArr.push(pt);
									polyBounds.extend(pt);
								}
								else{
									alert("The footprint is not in the proper format. Please recreate it using the map tools.");
									break;
								}
							}
							if(footPolyArr.length > 0){
								var pt = new google.maps.LatLng(footPolyArr[0][keys[0]],footPolyArr[0][keys[1]]);
								pointArr.push(pt);
								polyBounds.extend(pt);
							}
						}
						catch(e){
							alert("The footprint is not in the proper format. Please recreate it using the map tools.");
						}
					}
					else{
						var strArr = footprintWKT.split(",");
						for(var i=0; i < strArr.length; i++){
							var xy = strArr[i].split(" ");
							var pt = new google.maps.LatLng(xy[1],xy[0]);
							pointArr.push(pt);
							polyBounds.extend(pt);
					    }
					}
				}

				if(pointArr.length > 0){
					var footPoly = new google.maps.Polygon({
						paths: pointArr,
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: true,
						map: map
					});
					footPoly.type = 'polygon';
					google.maps.event.addListener(footPoly, 'click', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly, 'dragend', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly.getPath(), 'insert_at', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly.getPath(), 'remove_at', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly.getPath(), 'set_at', function() { setSelection(footPoly); });
					setSelection(footPoly);
					map.fitBounds(polyBounds);
					map.panToBounds(polyBounds);
				}
			}

			function resetPolygon(){
				if(selectedShape) selectedShape.setMap(null);
				setPolygon();
			}

			function setSelection(shape) {
				selectedShape = shape;
				selectedShape.setEditable(true);
				if (shape.type == 'polygon') {
					setPolygonStr(shape);
				}
			}

			function clearSelection() {
				if(selectedShape){
					selectedShape.setEditable(false);
					selectedShape = null;
				}
				document.getElementById("polycoord_message").style.display = "none";
			}

			function deleteSelectedShape() {
				if(selectedShape){
					selectedShape.setMap(null);
					clearSelection();
				}
				document.getElementById("poly_array").value = "";
				opener.window.deletePolygon();
			}

			function setPolygonStr(polygon) {
				var coordinates = [];
				var coordinatesMVC = (polygon.getPath().getArray());
				for(i=0;i<coordinatesMVC.length;i++){
					var mvcString = coordinatesMVC[i].toString();
					mvcString = mvcString.slice(1, -1);
					var latlngArr = mvcString.split(", ");
					coordinates.push(parseFloat(latlngArr[1]).toFixed(6)+" "+parseFloat(latlngArr[0]).toFixed(6));
					//if(i==0) var firstSet = parseFloat(latlngArr[1]).toFixed(6)+" "+parseFloat(latlngArr[0]).toFixed(6);
				}
				//coordinates.push(firstSet);
				document.getElementById("poly_array").value = coordinates.toString();
				document.getElementById("polycoord_message").style.display = "block";
			}

	        function updateParentForm() {
				if(opener.document.getElementById("footprintWKT")){
					var polyValue = document.getElementById("poly_array").value;
					if(polyValue && polyValue.substring(7) != "POLYGON") polyValue = "POLYGON (("+polyValue+"))";
					opener.document.getElementById("footprintWKT").value = polyValue;
					try{
						opener.document.getElementById("footprintWKT").onchange();
					}
					catch(myErr){}
				}
				if(opener.document.getElementById("polySaveDiv")){
					opener.document.getElementById("polySaveDiv").style.display = "block";
				}
				if(opener.document.getElementById("delpolygon")){
					opener.document.getElementById("delpolygon").style.display = "block";
				}
				if(opener.document.getElementById("polyDefDiv")){
					opener.document.getElementById("polyDefDiv").style.display = "none";
				}
				if(opener.document.getElementById("polyNotDefDiv")){
					opener.document.getElementById("polyNotDefDiv").style.display = "none";
				}
				self.close();
	            return false;
	        }
	    </script>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<div style="width:700px;">
			<div style="margin-top:8px;">
				<div id="polycoord_message" style="float:left;display:none;">
					<b>Polygon coordinates ready to submit.</b>
				</div>
				<div style="float:right;margin-right:30px;">
					<button type="submit" name="addcoords" value="Submit Polygon" onclick="updateParentForm();">Submit</button>&nbsp;&nbsp;&nbsp;
					<button id="delete-button">Delete Shape</button>
					<a href="#" onclick="toggle('helptext')"><img alt="Display Help Text" src="../images/qmark_big.png" style="width:15px;" /></a>
				</div>
				<div id="helptext" style="clear:both;display:none;">
					Click on polygon tool to draw a polygon representing search area.
					Submit Polygon button will transfer polygon to form.
				</div>
			</div>
		</div>
		<div id='map_canvas' style='width:100%;height:700px;'></div>
		<div>
			<textarea id="poly_array" name="poly_array" style="width:650px;height:75px;"></textarea>
			<button onclick="resetPolygon()">Redraw Polygon</button>
		</div>
	</body>
</html>