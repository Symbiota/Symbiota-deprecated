<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$charset);

$clid = (array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0);
$mapManager = new OccurrenceMapManager(); 
 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $defaultTitle; ?> - Google Map</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
		var map;
		var useLLDecimal = true;
	    var infoWins = new Array();
	    var puWin;
        var minLng = 180;
        var minLat = 90;
        var maxLng = -180;
        var maxLat = -90;

		function initialize(){
            <?php
			$latCen = 41.0;
			$longCen = -95.0;
			$coorArr = explode(";",$mappingBoundaries);
			if($coorArr && count($coorArr) == 4){
				$latCen = ($coorArr[0] + $coorArr[2])/2;
				$longCen = ($coorArr[1] + $coorArr[3])/2;
			}
			?>
	    	var dmOptions = {
				zoom: 3,
				center: new google.maps.LatLng(<?php echo $latCen.','.$longCen; ?>),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

	    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

           <?php 
			$coordExist = false;
			$iconKeys = Array(); 
			$coordArr = $mapManager->getGeoCoords();
			$markerCnt = 0;
			$minLng = 180;
        	$minLat = 90;
        	$maxLng = -180;
        	$maxLat = -90;
			foreach($coordArr as $sciName => $valueArr){
				$iconUrl = $valueArr["icon"];
				if($iconUrl) $iconKeys[] = "<div><img width='12px' src='".$iconUrl."'/> = <i>".$sciName."</i></div>";
				unset($valueArr["icon"]);
				foreach($valueArr as $latLng => $dataArr){
					$coordExist = true;
					//Find max/min point values
					$llArr = explode(',',$latLng);
					if($llArr[0] < $minLat) $minLat = $llArr[0];
					if($llArr[0] > $maxLat) $maxLat = $llArr[0];
					if($llArr[1] < $minLng) $minLng = $llArr[1];
					if($llArr[1] > $maxLng) $maxLng = $llArr[1];
					//Create marker
					$spStr = '';
					$functionStr = '';
					$titleStr = $latLng;
					foreach($dataArr as $occId => $spArr){
						if(count($dataArr) == 1){
							$functionStr = $occId.",".$clid;
						}
						else{
							$id = $occId;
							$spStr .= "<a href='#' onclick='openIndPU(".$occId.",".(array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"0").")'>".$id."</a><br/>";
						}
					}
					echo 'var m'.$markerCnt.' = getMarker('.$latLng.',"'.$titleStr.'","'.$iconUrl.'");',"\n";
					if($functionStr){
						?>
						google.maps.event.addListener(
							m<?php echo $markerCnt; ?>, 
							'click', 
							function(){ 
								closeAllInfoWins();
								openIndPU(<?php echo $functionStr; ?>); 
							}
						);
						<?php
					}
					elseif($spStr){
						?>
						google.maps.event.addListener(m<?php echo $markerCnt; ?>, 'click', function(){
							closeAllInfoWins();
							var iWin = new google.maps.InfoWindow({ content: "<div><b>Specimens at this point:</b></div><?php echo $spStr; ?>" });
							infoWins.push( iWin );
							iWin.open(map,m<?php echo $markerCnt; ?>);
						});
						<?php
					}
					$markerCnt++;
				}
			}
			?>
			var swLatLng = new google.maps.LatLng(<?php echo $minLat.','.$minLng; ?>);
			var neLatLng = new google.maps.LatLng(<?php echo $maxLat.','.$maxLng; ?>);
			var llBounds = new google.maps.LatLngBounds(swLatLng, neLatLng);
			map.fitBounds(llBounds);
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
			setTimeout(function () { newWindow.focus(); }, 0.5);
		}

		function closeAllInfoWins(){
			for( var w = 0; w < infoWins.length; w++ ) {
				var win = infoWins[w];
				win.close();
			}
		}

		function getMarker(newLat, newLng, newTitle, newIcon){
            var iconImg = new google.maps.MarkerImage( newIcon );
            
            var m = new google.maps.Marker({
                position: new google.maps.LatLng(newLat, newLng),
                map: map,
                title: newTitle,
                icon: iconImg
            });
			return m;
        }

        function addRefPoint(){
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

                    var iconImg = new google.maps.MarkerImage( '../images/google/arrow.png' );

                    var m = new google.maps.Marker({
                        position: new google.maps.LatLng(lat,lng),
                        map: map,
                        title: title,
                        icon: iconImg,
                        zIndex: google.maps.Marker.MAX_ZINDEX
                    });
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
	</script>
</head>
<body onload="initialize()">
	<?php
	if(!$coordExist){ //no results
		?>
			<div style="font-size:120%;font-weight:bold;">
				Your query apparently does not contain any records with coordinates that can be mapped.
			</div>
			<div style="margin-left:20px;">
				Either the records in the query are not georeferenced (no lat/long)<br/>
			</div>
			<div style="margin-left:100px;">
				-or-
			</div>
			<div style="margin-left:20px;">
				Rare/threatened status requires the locality coordinates be hidden.
			</div>
        <?php 
    }
    ?>
    <div id='map_canvas' style='width: 100%; height: 600px'></div>
    <table title='Add Point of Reference'>
    	<tr>
    		<td style="width:200px" valign='top'>
			    <?php 
			    foreach($iconKeys as $iconValue){
			        echo $iconValue;
			    }
				?>
			</td>
			<td style="width:340px" valign='top'>
				<div class='latlongdiv' style='display:block'>
					Latitude decimal: 
					<input name='lat' id='lat' size='10' type='text' /> 
					eg: 34.57<br/>
					Longitude decimal: 
					<input name='lng' id='lng' size='10' type='text' /> 
					eg: -112.38
					<div style='font-size:80%;margin-left:10px;'>
						<a href='#' onclick='javascript: toggleLatLongDivs();'>Enter in D:M:S format</a>
					</div>
				</div>
				<div class='latlongdiv' style='display:none'>
					Latitude: 
					<input name='latdeg' id='latdeg' size='2' type='text' />&deg;
					<input name='latmin' id='latmin' size='5' type='text' />&prime;
					<input name='latsec' id='latsec' size='5' type='text' />&Prime;
					<select name='latns' id='latns'>
						<option value='N'>N</option>
						<option value='S'>S</option>
					</select>
					Longitude: 
					<input name='longdeg' id='longdeg' size='2' type='text' />&deg;
					<input name='longmin' id='longmin' size='5' type='text' />&prime;
					<input name='longsec' id='longsec' size='5' type='text' />&Prime;
					<select name='longew' id='longew'>
						<option value='E'>E</option>
						<option value='W' selected>W</option>
					</select>
					<div style='font-size:80%;margin-left:10px;'>
						<a href='#' onclick='toggleLatLongDivs();'>Enter in Decimal format</a>
					</div>
				</div>
			</td>
			<td valign='top'>
				Marker Name: <input name='title' id='title' size='20' type='text' /><br/>
				<input type='submit' value='Add Marker' onclick='addRefPoint();' />
			</td>
		</tr>
	</table>
</body>
</html>
