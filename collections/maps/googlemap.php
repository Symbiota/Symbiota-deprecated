<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$charset);

 $mapManager = new OccurrenceMapManager(); 
 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo $defaultTitle; ?> - Google Map</title>
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo $googleAnalyticsKey; ?>']);
		_gaq.push(['_trackPageview']);
	
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
</head> 
<body onload="load()" onunload="GUnload()">
    <script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $googleMapKey; ?>" type="text/javascript"></script>
    <script type="text/javascript">
      //<![CDATA[
        var map;
        var points;
        var useLLDecimal = true;
      
        function load(){
            if(GBrowserIsCompatible()) {
                map = new GMap2(document.getElementById("map"));
                points = new Array();
                map.addControl(new GLargeMapControl()); // pan, zoom
                map.addControl(new GMapTypeControl()); // map, satellite, hybrid
                map.addControl(new GOverviewMapControl()); // small overview in corner
                <?php
                	$latCen = 41.0;
                	$longCen = -95.0;
                	$coorArr = explode(";",$mappingBoundaries);
                	if($coorArr && count($coorArr) == 4){
                		$latCen = ($coorArr[0] + $coorArr[2])/2;
                		$longCen = ($coorArr[1] + $coorArr[3])/2;
                	}
                ?>
                map.setCenter(new GLatLng( <?php echo $latCen.",".$longCen; ?> ), 3);

                var tinyIcon = new GIcon(G_DEFAULT_ICON);
                tinyIcon.shadow = "../../images/google/shadow.png";
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
	                		$collId = $spArr["collid"];
							$dbpk = $spArr["dbpk"];
							if(count($dataArr) == 1){
	                        	$functionStr = "window.location.href = \"javascript:var indpopup=window.open('../individual/index.php?pk=".$dbpk."&collid=".$collId.(array_key_exists("clid",$_REQUEST)?"&clid=".$_REQUEST["clid"]:"")."','indspec','toolbar=1,scrollbars=1,width=870,height=600,left=20,top=20');\";";
							}
							else{
								$gui = $spArr["gui"];
								if(!$gui) $gui = "occurrence #".$occId;
								$spStr .= "<div style='color:blue;cursor:pointer;' onclick=\\\"javascript:var indpopup=window.open(\\'../individual/index.php?pk=".$dbpk."&collid=".$collId.(array_key_exists("clid",$_REQUEST)?"&clid=".$_REQUEST["clid"]:"")."\\',\\'indspec\\',\\'toolbar=0,scrollbars=1,width=650,height=600,left=20,top=20\\');\\\">".$gui."</div>";
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
        
      //]]>
    </script>
<?php

    if(!$coordExist){ //no results
        echo "<div style='font-size:120%;font-weight:bold;'>Your query apparently does not contain any records with coordinates that can be mapped.</div>";
        echo "&nbsp;&nbsp;&nbsp;Either the records in the query are not georeferenced (no lat/long)<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-or-<br>";
        echo "&nbsp;&nbsp;&nbsp;Rare/threatened status requires the locality coordinates be hidden.";
    }
    echo "<div id='map' style='width: 800px; height: 600px'></div>";
    echo "<table title='Add Point of Reference'><tr><td width='200px' valign='top'>";
    foreach($iconKeys as $iconValue){
        echo $iconValue;
    }
    echo "</td><td width='340' valign='top'>";
    echo "<div class='latlongdiv' style='display:block'>";
    echo "Latitude decimal:&nbsp;&nbsp;&nbsp;<input name='lat' id='lat' size='10' type='text'> eg: 34.57<br/>";
    echo "Longitude decimal: <input name='lng' id='lng' size='10' type='text'> eg: -112.38";
    echo "<div style='font-size:80%;margin-left:10px;'><a href='#' onclick='javascript: toggleLatLongDivs();'>Enter in D:M:S format</a></div>";
    echo "</div>";

    echo "<div class='latlongdiv' style='display:none'>";
    echo "Latitude:&nbsp;&nbsp;&nbsp;<input name='latdeg' id='latdeg' size='2' type='text'>&deg;&nbsp;";
    echo "<input name='latmin' id='latmin' size='5' type='text'>&prime;&nbsp;";
    echo "<input name='latsec' id='latsec' size='5' type='text'>&Prime;&nbsp;";
    echo "<select name='latns' id='latns'><option value='N' selected>N</option><option value='S'>S</option></select>";
    echo "Longitude: <input name='longdeg' id='longdeg' size='2' type='text'>&deg;&nbsp;";
    echo "<input name='longmin' id='longmin' size='5' type='text'>&prime;&nbsp;";
    echo "<input name='longsec' id='longsec' size='5' type='text'>&Prime;&nbsp;";
    echo "<select name='longew' id='longew'><option value='E'>E</option><option value='W' selected>W</option></select>";
    echo "<div style='font-size:80%;margin-left:10px;'><a href='#' onclick='javascript: toggleLatLongDivs();'>Enter in Decimal format</a></div>";
    echo "</div>";

    echo "</td><td valign='top'>";
    echo "Marker Name: <input name='title' id='title' size='20' type='text'><br>";
    echo "<input type='submit' value='Add Marker' onclick='javascript: addRefPoint();'>";
    echo "</td></tr></table>";
?>
</body>
</html>
