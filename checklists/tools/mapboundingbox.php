<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$latCenter = 0;
$lngCenter = 0;
if(isset($MAPPING_BOUNDARIES) && $MAPPING_BOUNDARIES){
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
		<title>Bounding Box Tool</title>
	</head>
	<body style="background-color:#ffffff;">
	<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>
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

		function updateParentForm(f) {
			var nlat = "";
			var slat = "";
			var wlon = "";
			var elon = "";

			if(isNumeric(f.nlat.value)) nlat = parseFloat(f.nlat.value);
			if(isNumeric(f.slat.value)) slat = parseFloat(f.slat.value);
			if(isNumeric(f.wlon.value)) wlon = parseFloat(f.wlon.value);
			if(isNumeric(f.elon.value)) elon = parseFloat(f.elon.value);

			opener.document.getElementById("upperlat").value = nlat;
			opener.document.getElementById("bottomlat").value = slat;
			opener.document.getElementById("leftlong").value = wlon;
			opener.document.getElementById("rightlong").value = elon;
			self.close();
			return false;
		}

		function isNumeric(sText){
			if(sText == "") return false;
			var ValidChars = "0123456789-.";
			var IsNumber = true;
			var Char;

			for (var i = 0; i < sText.length && IsNumber == true; i++){
				Char = sText.charAt(i);
				if (ValidChars.indexOf(Char) == -1){
					IsNumber = false;
					break;
				}
		   	}
			return IsNumber;
		}

		google.maps.event.addDomListener(window, 'load', initialize);

	</script>
	<div style="width:500px;"><?php echo $LANG['MBB_INSTRUCTIONS']; ?></div>
	<div id='map' style='width:100%; height: 520px'></div>
	<form id="mapForm" onsubmit="return false">
		<table>
			<tr><td>
				<?php echo $LANG['MBB_NORTHERN']; ?>: <input type="text" id="nlat" size="13" name="nlat" value="" />
			</td><td>
				<?php echo $LANG['MBB_EASTERN']; ?>: <input type="text" id="elon" size="13" name="elon" value="" />
				<button onclick="updateParentForm(this.form);"><?php echo $LANG['SUBMIT']; ?></button>
			</td></tr>
			<tr><td>
				<?php echo $LANG['MBB_SOUTHERN']; ?>: <input type="text" id="slat" size="13" name="slat" value="" />
			</td><td>
				<?php echo $LANG['MBB_WESTERN']; ?>: <input type="text" id="wlon" size="13" name="wlon" value="" />
			</td></tr>
		</table>
	</form>
  </body>
</html>