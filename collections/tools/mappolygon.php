<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/shared/mapaids.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$formSubmit = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:0;
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:'';
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:'';
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

if($formSubmit){
	if($formSubmit == 'save'){
		$clManager->savePolygon($_POST['footprintwkt']);
		$formSubmit = "exit";
	}
}

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
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/wktpolygontools.js" type="text/javascript"></script>
		<script type="text/javascript">
			var map;
			var selectedShape = null;
			<?php
			if($formSubmit && $formSubmit == 'exit'){
				echo 'window.close();';
			}
			?>

			function initialize(){
				var dmOptions = {
					zoom: <?php echo $zoom; ?>,
					center: new google.maps.LatLng(<?php echo $latCenter.','.$lngCenter; ?>),
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};
				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

				var drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: null,
					drawingControl: true,
					drawingControlOptions: {
						position: google.maps.ControlPosition.TOP_CENTER,
						drawingModes: [
							google.maps.drawing.OverlayType.POLYGON,
							google.maps.drawing.OverlayType.RECTANGLE,
							google.maps.drawing.OverlayType.CIRCLE
						]
					},
					markerOptions: {
						draggable: true
					},
					polygonOptions: {
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: true
					}
				});

				drawingManager.setMap(map);

				google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
					if (e.type != google.maps.drawing.OverlayType.MARKER) {
						// Switch back to non-drawing mode after drawing a shape.
						drawingManager.setDrawingMode(null);

						var shapeType = e.type;
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
						if(shapeType == 'circle'){
							getCircleCoords(newShape);
							google.maps.event.addListener(newShape, 'radius_changed', function() {
								setSelection(newShape);
							});
							google.maps.event.addListener(newShape, 'center_changed', function() {
								setSelection(newShape);
							});
						}
						else if(shapeType == 'rectangle'){
							getRectangleCoords(newShape);
							google.maps.event.addListener(newShape, 'bounds_changed', function() {
								setSelection(newShape);
							});
						}
						else if(shapeType == 'polygon'){
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

				// Clear the current selection when the drawing mode is changed or when the map is clicked
				google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
				google.maps.event.addListener(map, 'click', clearSelection);
				setPolygon();
			}

			function setPolygon(){
				var pointArr = [];
				var polyBounds = new google.maps.LatLngBounds();
				if(opener.document.getElementById("footprintwkt").value != ''){
					var origFootprintWkt = opener.document.getElementById("footprintwkt").value;
					var footprintWKT = validatePolygon(origFootprintWkt);
					if(footprintWKT != origFootprintWkt){
						opener.document.getElementById("footprintwkt").value = footprintWKT;
					}
					footprintWKT = trimPolygon(footprintWKT);
					var strArr = footprintWKT.split(",");
					for(var i=0; i < strArr.length; i++){
						var xy = strArr[i].trim().split(" ");
						var lat = xy[0];
						var lng = xy[1];
						if(!isNumeric(lat) || !isNumeric(lng)){
							alert("One or more coordinates are illegal (lat: "+lat+"   long: "+lng+")");
							opener.document.getElementById("footprintwkt").value = origFootprintWkt;
							return false;
						}
						else if(parseInt(Math.abs(lat)) > 90 || parseInt(Math.abs(lng)) > 180){
							alert("One or more coordinates are out-of-range or ordered incorrectly (lat: "+lat+"   long: "+lng+")");
							opener.document.getElementById("footprintwkt").value = origFootprintWkt;
							return false;
						}
						var pt = new google.maps.LatLng(lat,lng);
						pointArr.push(pt);
						polyBounds.extend(pt);
					}
				}

				if(pointArr.length > 0){
					var footPoly = new google.maps.Polygon({
						paths: pointArr,
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: false,
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

			function isNumeric(n) {
				return !isNaN(parseFloat(n)) && isFinite(n);
			}

			function resetPolygon(){
				if(selectedShape) selectedShape.setMap(null);
				setPolygon();
			}

			function setSelection(shape) {
				selectedShape = shape;
				selectedShape.setEditable(true);
				if(shape.type == 'polygon'){
					setPolygonStr(shape);
				}
			}

			function clearSelection() {
				if(selectedShape){
					selectedShape.setEditable(false);
					selectedShape = null;
				}
			}

			function setCircleStr(shape){
				var rad = (circle.getRadius());
				var radius = (rad/1000)*0.6214;
				opener.document.getElementById("radius").value = radius;
				opener.document.getElementById("pointlat").value = (circle.getCenter().lat());
				opener.document.getElementById("pointlong").value = (circle.getCenter().lng());
			}

			function setRectangleStr(shape){
				var latUpperValue = rectangle.getBounds().getNorthEast().lat();
				opener.document.getElementById("upperlat").value = latUpperValue;
				if(latUpperValue > 0) opener.document.getElementById("upperlat_NS").value = 'N';
				else if(latUpperValue < 0) opener.document.getElementById("upperlat_NS").value = 'S';

				var latBottomValue = rectangle.getBounds().getSouthWest().lat();
				opener.document.getElementById("bottomlat").value = latBottomValue;
				if(latBottomValue > 0) opener.document.getElementById("bottomlat_NS").value = 'N';
				else if(latBottomValue < 0) opener.document.getElementById("bottomlat_NS").value = 'S';

				var lngLeftValue = rectangle.getBounds().getSouthWest().lng();
				opener.document.getElementById("leftlong").value = lngLeftValue;
				if(lngLeftValue > 0) opener.document.getElementById("leftlong_EW").value = 'E';
				else if(lngLeftValue < 0) opener.document.getElementById("leftlong_EW").value = 'W';

				var lngRightValue = rectangle.getBounds().getNorthEast().lng();
				opener.document.getElementById("rightlong").value = lngRightValue;
				if(lngRightValue > 0) opener.document.getElementById("rightlong_EW").value = 'E';
				else if(lngRightValue < 0) opener.document.getElementById("rightlong_EW").value = 'W';
			}

			function setPolygonStr(polygon) {
				var coordinates = [];
				var coordinatesMVC = (polygon.getPath().getArray());
				for(var i=0;i<coordinatesMVC.length;i++){
					var mvcString = coordinatesMVC[i].toString();
					mvcString = mvcString.slice(1, -1);
					var latlngArr = mvcString.split(",");
					coordinates.push(parseFloat(latlngArr[0]).toFixed(6)+" "+parseFloat(latlngArr[1]).toFixed(6));
				}
				if(coordinates[0] != coordinates[i]) coordinates.push(coordinates[0]);
				var coordStr = coordinates.toString();
				if(coordStr && coordStr != "" && coordStr != undefined){
					opener.document.getElementById("footprintwkt").value = "POLYGON (("+coordStr+"))";
				}
			}

			function deleteSelectedShape(){
				if(selectedShape){
					selectedShape.setMap(null);
					clearSelection();
				}
			}

			function submitPolygonForm(f){
				opener.document.getElementById("footprintwkt").value = f.footprintwkt.value;
			}
		</script>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<div id="helptext" style="clear:both;">
			Click on polygon symbol to activate polygon tool and create a shape representing research area. Submit shape to add to search form.
		</div>
		<div id='map_canvas' style='width:100%;height:600px;'></div>
		<div>
			<form name="polygonSubmitForm" method="post" action="mappolyaid.php" onsubmit="return submitPolygonForm(this)">
				<div style="float:left">
					<button name="formsubmit" type="submit" value="submitshape">Submit Shape</button>
					<button name="formsubmit" type="submit" onclick="deleteSelectedShape()">Delete Selected Shape</button>
				</div>
			</form>
		</div>
	</body>
</html>