<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$projValue = $_REQUEST['proj'];
$target = array_key_exists('target',$_REQUEST)?$_REQUEST['target']:'checklists';

$clManager = new ChecklistManager();
if(!$projValue && isset($DEFAULT_PROJ_ID)) $projValue = $DEFAULT_PROJ_ID;
$clManager->setProj($projValue);

?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Species Checklists</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?sensor=false">
		</script>
		<script type="text/javascript">
		    var map;
		    var points = new Array();
		    var infoWins = new Array();
		  	
		    function initialize(){
		    	var dmLatLng = new google.maps.LatLng(41.0, -95.0);
		    	var dmOptions = {
					zoom: 3,
					center: dmLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};

				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
                <?php $clManager->echoResearchPoints($target); ?>
                resizeMap();
	        }

			function resizeMap() {
				var minLng = 180;       //Pixels
				var minLat = 180;
				var maxLng = -180;
				var maxLat = -180;
				var averLat = 0;
				var averLng = 0;
				var panBounds;
	            
				var neBounds;
				var swBounds;
				var optimalBounds;
				var zoomLevel = 3;
	
				// Find the max/min points
				for( var i = 0; i < points.length; i++ ) {
					var p = points[i];
					if ( p.lat() < minLat ) minLat = p.lat();
					if ( p.lat() > maxLat ) maxLat = p.lat();
					if ( p.lng() < minLng ) minLng = p.lng();
					if ( p.lng() > maxLng ) maxLng = p.lng();
				}
				var swLatLng = new google.maps.LatLng(minLat, minLng);
				var neLatLng = new google.maps.LatLng(maxLat, maxLng);
				var llBounds = new google.maps.LatLngBounds(swLatLng, neLatLng);
				map.fitBounds(llBounds);
	    	}

	    	function closeAllInfoWins(){
				for( var w = 0; w < infoWins.length; w++ ) {
					var win = infoWins[w];
					win.close();
				}
	    	}
		</script>
	    <style>
			html, body, #map_canvas {
				width: 100%;
				height: 100%;
				margin: 0;
				padding: 0;
			}
		</style>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
    	<div id="map_canvas"></div>
	</body>
</html>