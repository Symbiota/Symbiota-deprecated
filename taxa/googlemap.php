<!DOCTYPE html>
<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/TaxonProfileMap.php');
header("Content-Type: text/html; charset=".$charset);

$taxonValue = $_REQUEST["taxon"]; 
$clid = array_key_exists('clid',$_REQUEST)&&$_REQUEST['clid']?$_REQUEST['clid']:0;

$mapManager = new TaxonProfileMap();
$mapManager->setTaxon($taxonValue);
$synMap = $mapManager->getSynMap(); 

$coordArr = $mapManager->getGeoCoords();
$taxaMap = $mapManager->getTaxaMap(); 
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
		var useLLDecimal = true;
	    var infoWins = new Array();
	    var puWin;

	    function initialize(){
	    	var dmOptions = {
				zoom: 3,
				center: new google.maps.LatLng(41,-95),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

	    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
			var markers = [];
            <?php
			if($coordArr){
				$latMin = $coordArr['latmin'];
				unset($coordArr['latmin']);
				$latMax = $coordArr['latmax']; 
				unset($coordArr['latmax']);
				$lngMin = $coordArr['lngmin']; 
				unset($coordArr['lngmin']);
				$lngMax = $coordArr['lngmax']; 
				unset($coordArr['lngmax']);
				foreach($coordArr as $llStr => $occidArr){
					$iArr = array();
					$tidAcc = 0;
					$titleStr = '';
					$mId = 0;
					$iconUrl = $clientRoot."/images/google/smpin_white.png";
					$htmlStr = '<div style="width:250px;height:100px;"><b>Specimens at this point:</b><br/>';
					foreach($occidArr as $occId => $pointArr){
						$mId = $occId;
						$tidAcc = $synMap[$pointArr['tid']];
						$iArr[$tidAcc][] = $occId;
						$titleStr = str_replace(array('"',"'"),'',$pointArr['d']);
						$htmlStr .= '<a href="#" onclick="openIndPU('.$occId.','.($clid?$clid:'0').')">';
						$htmlStr .= $titleStr.' ';
						$htmlStr .= '<img src="'.$taxaMap[$tidAcc]['icon'].'" style="width:12px;" /> ';
						$htmlStr .= '</a><br/>';
					}
					$htmlStr .= '</div>';
					if(count($iArr) == 1){
						$iconUrl = $taxaMap[$tidAcc]['icon'];
					}
					if(count($occidArr) > 1){
						$titleStr = count($occidArr).' specimens';
					}
					?>
					var m<?php echo $mId; ?> = getMarker(<?php echo $llStr.',"'.$titleStr.'","'.$iconUrl.'"'; ?> );
					//markers.push(m<?php echo $mId; ?>);
					<?php
					if(count($occidArr) == 1){
						?>
						google.maps.event.addListener(
							m<?php echo $mId; ?>, 
							'click', 
							function(){ openIndPU(<?php echo array_shift($iArr[$tidAcc]).','.($clid?$clid:'0'); ?>); }
						);
						<?php
					}
					else if(count($occidArr) > 1){
						?>
						google.maps.event.addListener(m<?php echo $mId; ?>, 'click', function(){
							closeAllInfoWins();
							var iWin = new google.maps.InfoWindow({ content: '<?php echo $htmlStr; ?>' });
							infoWins.push( iWin );
							iWin.open(map,m<?php echo $mId; ?>);
						});
						<?php
					}
				}
				?>
				var swLatLng = new google.maps.LatLng(<?php echo $latMin.','.$lngMin; ?>);
				var neLatLng = new google.maps.LatLng(<?php echo $latMax.','.$lngMax; ?>);
				var llBounds = new google.maps.LatLngBounds(swLatLng, neLatLng);
				map.fitBounds(llBounds);
	            <?php
            }
            ?>
			//var mc = new MarkerClusterer(map, markers);
        }

		function openIndPU(occId,clid){
			closeAllInfoWins(); 
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
			if(puWin != null) puWin.close();
			puWin = window.open('../collections/individual/index.php?occid='+occId+'&clid='+clid,'indspec','scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if(puWin.opener == null) puWin.opener = self;
			setTimeout(function () { puWin.focus(); }, 0.5);
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
<body onload="initialize();">
<?php
    if(!$coordArr){ //no results
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
	<div id='map_canvas' style='width:95%; height:600px; clear:both;'></div>
    <table title='Add Point of Reference'>
    	<tr>
    		<td width='330px' valign='top'>
			    <?php 
			    $iconArr = array();
			    foreach($taxaMap as $k => $tArr){
			        $iconArr[$tArr['sciname']] = $tArr['icon'];
			    }
			    ksort($iconArr);
			    foreach($iconArr as $sn => $i){
					echo '<div><img width="12px" src="'.$i.'" /> = <i>'.$sn.'</i></div>'."\n";
			    }
			    ?>
			</td>
			<td width='275px' valign='top'>
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
			<td width='160px' valign='top'>
				Marker Name: 
				<input name='title' id='title' size='20' type='text'><br>
				<input type='submit' value='Add Marker' onclick='addRefPoint();'>
			</td>
		</tr>
	</table>
</body>
</html>
