<!DOCTYPE html>
<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$charset);

$mapManager = new OccurrenceMapManager(); 
 
$latCen = 41.0;
$lngCen = -95.0;
$coorArr;
if(isset($mappingBoundaries)){
	$coorArr = explode(";",$mappingBoundaries);
	if($coorArr && count($coorArr) == 4){
		$latCen = ($coorArr[0] + $coorArr[2])/2;
		$longCen = ($coorArr[1] + $coorArr[3])/2;
	}
}
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> - Google Map</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false">
	</script>
	<script type="text/javascript">
		var map;
		var points;
		var useLLDecimal = true;

	    function initialize(){
	    	var dmLatLng = new google.maps.LatLng(<?php echo $latCen.",".$lngCen; ?>);
	    	var dmOptions = {
				zoom: 3,
				center: dmLatLng,
				mapTypeId: google.maps.MapTypeId.TERRAIN
			};

	    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
            points = new Array();

            var mImg = new google.maps.MarkerImage();

            
			var tinyIcon = new GIcon(G_DEFAULT_ICON);
			tinyIcon.shadow = "../images/google/shadow.png";
			tinyIcon.iconSize = new GSize(25, 25);
			tinyIcon.infoWindowAnchor = new GPoint(12,0);
			tinyIcon.shadowSize = new GSize(45, 25);
			tinyIcon.iconAnchor = new GPoint(12, 25);
           <?php 
                $coordExist = false;
                $iconKeys = Array(); 
                $coordArr = $mapManager->getGeoCoords();
                $markerCnt = 0;
                foreach($coordArr as $sciName => $valueArr){
                	$iconUrl = $valueArr["icon"];
					if($iconUrl) $iconKeys[] = "<div><img width='12px' src='".$iconUrl."'/> = <i>".$sciName."</i></div>";
                	unset($valueArr["icon"]);
					echo "tinyIcon.image = '".$iconUrl."';\n";
                	foreach($valueArr as $latLng => $dataArr){
						$coordExist = true;
                        echo "var point = new GLatLng(".$latLng.");\n";
                        echo "points.push( point );\n";
                        echo "var marker".$markerCnt." = new GMarker(point, tinyIcon);\n";
                        $spStr = "";
                        $functionStr = "";
                        foreach($dataArr as $occId => $spArr){
							if(count($dataArr) == 1){
	                        	$functionStr = "openIndPU(".$occId.",".(array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"0").")";
							}
							else{
								$gui = $spArr["gui"];
								if(!$gui) $gui = "occurrence #".$occId;
								$spStr .= "<a href='#' onclick='openIndPU(".$occId.",".(array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"0").")'>".$gui."</a><br/>";
							}
                        }
                        if($spStr){
		                  	$functionStr = "marker".$markerCnt.".openInfoWindowHtml(\"<div><b>Specimens at this point:</b></div>".$spStr."\");";
                        }
	                    echo "GEvent.addListener(marker".$markerCnt.", 'click', function() {".$functionStr."});\n";
                        echo "map.addOverlay(marker".$markerCnt.");\n";
                        $markerCnt++;
                	}
                }
                echo "resizeMap(map, points);";
            ?>
        }

        function resizeMap( map, points ) {
            var lngLowerBound = -180;
            var latLowerBound = -90;
            var lngUpperBound = 180;
            var latUpperBound = 90;

            <?php
                if(!$mapManager->getUseCookies() && isset($mappingBoundaries)){
	    	 		$latlonArr = explode(";",$mappingBoundaries);
                
	    	        echo "lngLowerBound = ".$latlonArr[3].";";
	        	    echo "latLowerBound = ".$latlonArr[2].";";
	            	echo "lngUpperBound = ".$latlonArr[1].";";
	            	echo "latUpperBound = ".$latlonArr[0].";";
                }
            ?>
            var minLng = 180;       //Pixels
            var minLat = 90;
            var maxLng = -180;
            var maxLat = -90;
            var averLat = 0;
            var averLng = 0;
            var panBounds;
            var neBounds;
            var swBounds;
            var optimalBounds;
            var zoomLevel = 3;

            // Find the max/min points
            for ( var i = 0; i < points.length; i++ ) {
                var p = points[i];
                if ( p.lat() < minLat && p.lat() > latLowerBound) minLat = p.lat();
                if ( p.lat() > maxLat && p.lat() < latUpperBound ) maxLat = p.lat();
                if ( p.lng() < minLng && p.lng() > lngLowerBound ) minLng = p.lng();
                if ( p.lng() > maxLng && p.lng() < lngUpperBound ) maxLng = p.lng();
            }
            averLat =  (minLat + maxLat) / 2;
            averLng = (minLng + maxLng) / 2;
            panBounds = new GLatLng(averLat,averLng);

            // Find the optimal Width Zoom
            swBounds = new GLatLng(minLat,minLng);
            neBounds = new GLatLng(maxLat,maxLng);
            optimalBounds = new GLatLngBounds(swBounds,neBounds);
            zoomLevel = map.getBoundsZoomLevel(optimalBounds);
            if(zoomLevel > 8) zoomLevel = 8;

            // Reposition
            window.setTimeout(function() {map.setCenter(panBounds, zoomLevel);}, 500);
        }
        
        function addRefPoint(formObj){
            var lat = document.getElementById("lat").value;
            var lng = document.getElementById("lng").value;
            var title = document.getElementById("title").value;
            if(!useLLDecimal){
                var latdeg = document.getElementById("latdeg").value;
                var latmin = document.getElementById("latmin").value;
                var latsec = document.getElementById("latsec").value;
                var latns = document.getElementById("latns").value;
                var longdeg = document.getElementById("longdeg").value;
                var longmin = document.getElementById("longmin").value;
                var longsec = document.getElementById("longsec").value;
                var longew = document.getElementById("longew").value;
                if(latdeg != null && longdeg != null){
                    if(latmin == null) latmin = 0;
                    if(latsec == null) latsec = 0;
                    if(longmin == null) longmin = 0;
                    if(longsec == null) longsec = 0;
                    lat = latdeg*1 + latmin/60 + latsec/3600;
                    lng = longdeg*1 + longmin/60 + longsec/3600;
                    if(latns == "S") lat = lat * -1;
                    if(longew == "W") lng = lng * -1;
                }
            }
            if(lat != null && lng != null){
                if(lat < -180 || lat > 180 || lng < -180 || lng > 180){
                    window.alert("Latitude and Longitude must be of values between -180 and 180 (" + lat + ";" + lng + ")");
                }
                else{
                    var addPoint = true;
                    if(lng > 0) addPoint = window.confirm("Longitude is positive, which will put the marker in the eastern hemisphere (e.g. Asia).\nIs this what you want?");
                    if(!addPoint) lng = -1*lng;

                    var rfIcon = new GIcon();
                    rfIcon.iconSize = new GSize(20, 26);
                    rfIcon.iconAnchor = new GPoint(10, 13);
                    rfIcon.infoWindowAnchor = new GPoint(10, 13);

                    rfIcon.image = '../../images/x.gif';
                    var rfPoint = new GLatLng(lat,lng);
                    points.push(rfPoint);
                    markerOptions = {icon:rfIcon}; 
                    var rfMarker = new GMarker(rfPoint, markerOptions);
                    
                    GEvent.addListener(rfMarker, "click", function() {rfMarker.openInfoWindowHtml("<b>" + title + "</b><br/>(" + lat + "; " + lng + ")");});
                    map.addOverlay(rfMarker);
                }
            }
            else{
                window.alert("Enter values in the latitude and longitude fields");
            }
        }
        
		function toggleLatLongDivs(){
			var divs = document.getElementsByTagName("div");
			for (i = 0; i < divs.length; i++) {
				var obj = divs[i];
				if(obj.getAttribute("class") == "latlongdiv" || obj.getAttribute("className") == "latlongdiv"){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
					else{
						obj.style.display="none";
					}
				}
			}
			if(useLLDecimal){
				useLLDecimal = false;
			}
			else{
				useLLDecimal = true;
			}
		}
	
		function openIndPU(occId,clid){
			var wWidth = 900;
			try{
				if(opener.document.getElementById('maintable').offsetWidth){
					wWidth = opener.document.getElementById('maintable').offsetWidth*1.05;
				}
				else if(opener.document.body.offsetWidth){
					wWidth = opener.document.body.offsetWidth*0.9;
				}
			}
			catch(err){
			}
			newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}
    </script>
</head> 
<body onload="initialize();">
<?php
    if(!$coordExist){ //no results
    	?>
		<div style='font-size:120%;font-weight:bold;'>
			Your query apparently does not contain any records with coordinates that can be mapped.
		</div>
		<div style="margin:15px;">
			Either the records in the query are not georeferenced (no lat/long) or
		 	Rare/threatened status requires the locality coordinates be hidden.
		</div>
        <?php 
    }
    ?>
	<div id='map_canvas' style='width:95%; height:650px; clear:both;'></div>
    <table title='Add Point of Reference'>
    	<tr>
    		<td width='200px' valign='top'>
			    <?php 
			    foreach($iconKeys as $iconValue){
			        echo $iconValue;
			    }
			    ?>
			</td>
			<td width='340' valign='top'>
				<div class='latlongdiv' style='display:block'>
					Latitude decimal:
					<input name='lat' id='lat' size='10' type='text'> eg: 34.57<br/>
					Longitude decimal: 
					<input name='lng' id='lng' size='10' type='text'> eg: -112.38
					<div style='font-size:80%;margin-left:10px;'>
						<a href='#' onclick='toggleLatLongDivs();return false;'>Enter in D:M:S format</a>
					</div>
				</div>
				<div class='latlongdiv' style='display:none'>
					Latitude:
					<input name='latdeg' id='latdeg' size='2' type='text'>&deg;
					<input name='latmin' id='latmin' size='5' type='text'>&prime;
					<input name='latsec' id='latsec' size='5' type='text'>&Prime;
					<select name='latns' id='latns'>
						<option value='N' selected>N</option>
						<option value='S'>S</option>
					</select>
					Longitude: 
					<input name='longdeg' id='longdeg' size='2' type='text'>&deg;
					<input name='longmin' id='longmin' size='5' type='text'>&prime;
					<input name='longsec' id='longsec' size='5' type='text'>&Prime;
					<select name='longew' id='longew'>
						<option value='E'>E</option>
						<option value='W' selected>W</option>
					</select>
					<div style='font-size:80%;margin-left:10px;'>
						<a href='#' onclick='toggleLatLongDivs();return false;'>Enter in Decimal format</a>
					</div>
				</div>
			</td>
			<td valign='top'>
				Marker Name: 
				<input name='title' id='title' size='20' type='text'><br>
				<input type='submit' value='Add Marker' onclick='addRefPoint();'>
			</td>
		</tr>
	</table>
</body>
</html>
