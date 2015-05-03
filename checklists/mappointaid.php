<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistAdmin.php');
header("Content-Type: text/html; charset=".$charset);

$latCenter = array_key_exists("latcenter",$_REQUEST)?$_REQUEST["latcenter"]:0; 
$lngCenter = array_key_exists("lngcenter",$_REQUEST)?$_REQUEST["lngcenter"]:0; 
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0; 

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

if(!is_numeric($latCenter) || !is_numeric($lngCenter) || (!$latCenter && !$lngCenter)){
	$boundaryArr = explode(";",$mappingBoundaries);
	$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false">
		</script>
	    <script type="text/javascript">
		    var map;
		    var currentMarker;
		    var newPoint;
		    var mapZoom;
		    var newIcon;
		    var pIcon;
	      	
			function initialize(){
				var latCenter = <?php echo $latCenter; ?>;
				var lngCenter = <?php echo $lngCenter; ?>;
		    	var dmOptions = {
					zoom: 14,
					center: new google.maps.LatLng(latCenter,lngCenter),
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};
		    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
				newIcon = new google.maps.MarkerImage("../images/google/smpin_red.png");
				pIcon = new google.maps.MarkerImage("../images/google/smpin_blue.png");

				//Add all existing points for given taxon
				<?php 
				$pointArr = $clManager->getPoints($tid);
				foreach($pointArr as $k => $pArr){
					echo 'placeMarker('.$pArr['lat'].','.$pArr['lng'].',"'.$pArr['notes'].'");'."\n";
				}
				?>
		    	
				//Add listener for clicking on map
				google.maps.event.addListener(map, 'click', function(event){
					newPoint = event.latLng;
					mapZoom = map.getZoom();
		            setTimeout("placeNewMarker()", 500);
		        });
	        }
	
	        function placeNewMarker() {
		        //Remove previous marker
	    		if(currentMarker) currentMarker.setMap();
	            if(mapZoom == map.getZoom()){
	                var marker = new google.maps.Marker({
	                    position: newPoint,
	                    map: map,
	                    icon: newIcon
	                });
					currentMarker = marker;

					//Add coordinate values to text boxes
	    	        var latValue = newPoint.lat();
	    	        var lonValue = newPoint.lng();
					document.getElementById("latbox").value = latValue.toFixed(5);
					document.getElementById("lngbox").value = lonValue.toFixed(5);
	    		}
	        }

	        function placeMarker(lat,lng,notesStr) {
	        	var mLatLng = new google.maps.LatLng(lat,lng);
                var marker = new google.maps.Marker({
                    position: mLatLng,
                    map: map,
                    title: notesStr,
                    icon: pIcon
                });
				//google.maps.event.addListener(marker,"click",function(){  });
	        }

	        function updateParentForm() {
				try{
		            opener.document.pointaddform.latdec.value = document.getElementById("latbox").value;
		            opener.document.pointaddform.lngdec.value = document.getElementById("lngbox").value;
				}
				catch(myErr){
					alert("Unable to transfer data. Please let an administrator know.");
				}
	            self.close();
	            return false;
	        }
	    </script>

	</head> 
	<body style="background-color:#ffffff;" onload="initialize()">
		<div style="width:770px;height:650px;">
			<div>
				Click once to capture coordinates.  
				Submit Coordinate button will transfer to form. 
			</div>
			<div style="margin-right:30px;">
				<b>Latitude:</b> <input type="text" id="latbox" size="13" name="lat" value="" /> 
				<b>Longitude:</b> <input type="text" id="lngbox" size="13" name="lon" value="" /> 
				<input type="submit" name="addcoords" value="Submit Coordinates" onclick="updateParentForm();" />
			</div>
			<div id='map_canvas' style='width:95%; height:90%; clear:both;'></div>
		</div>
	</body>
</html>
