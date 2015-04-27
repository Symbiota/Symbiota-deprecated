<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceMapManager.php');
include_once($serverRoot.'/classes/MappingShared.php');
include_once($serverRoot.'/classes/TaxonProfileMap.php');
header("Content-Type: text/html; charset=".$charset);

$taxonValue = array_key_exists('taxon',$_REQUEST)?$_REQUEST['taxon']:0;
$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$mapType = array_key_exists('maptype',$_REQUEST)?$_REQUEST['maptype']:0;
$gridSize = array_key_exists('gridSizeSetting',$_REQUEST)?$_REQUEST['gridSizeSetting']:10;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)?$_REQUEST['minClusterSetting']:50;

$sharedMapManager = new MappingShared();

$sharedMapManager->setFieldArr(0);

$mapWhere = '';
$genObs = $sharedMapManager->getGenObsInfo();

if($mapType == 'taxa'){
	$taxaMapManager = new TaxonProfileMap();
	$taxaMapManager->setTaxon($taxonValue);
	$synMap = $taxaMapManager->getSynMap();
	$taxaMapManager->getTaxaMap();
	$mapWhere = $taxaMapManager->getTaxaSqlWhere();
	$tArr = $taxaMapManager->getTaxaArr();
	$sharedMapManager->setTaxaArr($tArr);
}
elseif($mapType == 'occquery'){
	$occurMapManager = new OccurrenceMapManager();
	$mapWhere = $occurMapManager->getOccurSqlWhere();
	$tArr = $occurMapManager->getTaxaArr();
	$stArr = $occurMapManager->getSearchTermsArr();
	$sharedMapManager->setSearchTermsArr($stArr);
}

$sharedMapManager->setTaxaArr($tArr);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $defaultTitle; ?> - Google Map</title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script src="http://www.google.com/jsapi"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="../js/symb/markerclusterer.js?ver=260913"></script>
	<script type="text/javascript" src="../js/symb/oms.min.js"></script>
	<script type="text/javascript" src="../js/symb/keydragzoom.js"></script>
	<script type="text/javascript">
		var map = null;
		var markerClusterer = null;
		var useLLDecimal = true;
	    var infoWins = new Array();
	    var puWin;
        var minLng = 180;
        var minLat = 90;
        var maxLng = -180;
        var maxLat = -90;
		var markers = [];
		
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
			
			oms = new OverlappingMarkerSpiderfier(map);
			
			map.enableKeyDragZoom({
				visualEnabled: true,
				visualPosition: google.maps.ControlPosition.LEFT,
				visualPositionOffset: new google.maps.Size(35, 0),
				visualPositionIndex: null,
				visualSprite: "../images/dragzoom_btn.png",
				visualSize: new google.maps.Size(20, 20),
				visualTips: {
					off: "Turn on",
					on: "Turn off"
				}
			});
			
			oms.addListener('click', function(marker, event) {
				closeAllInfoWins();
				occid = marker.occid;
				clid = marker.clid;
				openIndPU(occid,clid);
			});
			
			oms.addListener('spiderfy', function(markers) {
				closeAllInfoWins();
			});
			
           <?php 
			$coordExist = false;
			$iconKeys = Array(); 
			$coordArr = $sharedMapManager->getGeoCoords(0,false,$mapWhere);
			$markerCnt = 0;
			$spCnt = 1;
			$minLng = 180;
        	$minLat = 90;
        	$maxLng = -180;
        	$maxLat = -90;
			foreach($coordArr as $sciName => $valueArr){
				?>
				markers = [];
				<?php
				$iconColor = $valueArr["color"];
				if($iconColor) {
					$iconKey = '<div><svg xmlns="http://www.w3.org/2000/svg" style="height:12px;width:12px;margin-bottom:-2px;"><g><rect x="1" y="1" width="11" height="10" fill="#'.$iconColor.'" stroke="#000000" stroke-width="1px" /></g></svg>';
					$iconKey .= ' = <i>'.$sciName.'</i></div>';
					$iconKeys[] = $iconKey;
				}
				unset($valueArr["color"]);
				foreach($valueArr as $occId => $spArr){
					$coordExist = true;
					//Find max/min point values
					$llArr = explode(',',$spArr['latLngStr']);
					if($llArr[0] < $minLat) $minLat = $llArr[0];
					if($llArr[0] > $maxLat) $maxLat = $llArr[0];
					if($llArr[1] < $minLng) $minLng = $llArr[1];
					if($llArr[1] > $maxLng) $maxLng = $llArr[1];
					//Create marker
					$spStr = '';
					$functionStr = '';
					$titleStr = $spArr['latLngStr'];
					$type = '';
					$displayStr = '';      
					if(is_numeric($spArr['catalognumber'])){
						$displayStr = $spArr['institutioncode'].'-'.($spArr['collectioncode']?$spArr['collectioncode'].'-':'').$spArr['catalognumber'];
					}
					elseif((!$spArr['catalognumber']) && ($spArr['othercatalognumbers'])){
						$displayStr = $spArr['institutioncode'].'-'.($spArr['collectioncode']?$spArr['collectioncode'].'-':'').$spArr['othercatalognumbers'];
					}
					elseif((!$spArr['catalognumber']) && (!$spArr['othercatalognumbers'])){
						$displayStr = $spArr['institutioncode'].($spArr['collectioncode']?'-'.$spArr['collectioncode']:'').($spArr['identifier']?'-'.$spArr['identifier']:'');
					}
					else{
						$displayStr = $spArr['catalognumber'];
					}
					if($spArr['collid'] == $genObs){
						$displayStr = "General Observation";
						$type = 'obs';
						?>
						var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:"#<?php echo $iconColor; ?>",fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
						<?php
					}
					else{
						$type = 'spec';
						?>
						var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#<?php echo $iconColor; ?>",fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
						<?php
					}
					echo 'var m'.$markerCnt.' = getMarker('.$spArr['latLngStr'].',"'.$displayStr.'",markerIcon,"'.$type.'","'.($spArr['tidinterpreted']?$spArr['tidinterpreted']:0).'",'.$occId.','.($clid?$clid:'0').');',"\n";
					echo 'oms.addMarker(m'.$markerCnt.');',"\n";
					$markerCnt++;
				}
				?>
				//set style options for marker clusters (these are the default styles)
				mcOptions<?php echo $spCnt; ?> = {
					styles: [{
						color: "<?php echo $iconColor; ?>"
					}],
					maxZoom: 13,
					gridSize: <?php echo $gridSize; ?>,
					minimumClusterSize: <?php echo $minClusterSize; ?>
				}
				
				//Initialize clusterer with options
				var markerCluster<?php echo $spCnt; ?> = new MarkerClusterer(map, markers, mcOptions<?php echo $spCnt; ?>);
				
				<?php
				$spCnt++;
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
			newWindow = window.open('../collections/individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			setTimeout(function () { newWindow.focus(); }, 0.5);
		}

		function closeAllInfoWins(){
			for( var w = 0; w < infoWins.length; w++ ) {
				var win = infoWins[w];
				win.close();
			}
		}

		function getMarker(newLat, newLng, newTitle, newIcon, type, tid, occid, clid){
            var m = new google.maps.Marker({
				position: new google.maps.LatLng(newLat, newLng),
				title: newTitle,
				icon: newIcon,
				customInfo: type,
				taxatid: tid,
				occid: occid,
				clid: clid
			});
			markers.push(m);
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
<body style="background-color:#ffffff;" onload="initialize()">
	<?php
	//echo json_encode($coordArr);
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
    <table title='Add Point of Reference' style="width:100%;" >
    	<tr>
    		<td style="width:330px" valign='top'>
			    <fieldset>
					<legend>Legend</legend>
					<div style="float:left;">
						<?php 
						//echo $coordArr;
						foreach($iconKeys as $iconValue){
							echo $iconValue;
						}
						?>
					</div>
					<?php
					if($genObs){
						?>
						<div style="float:right;">
							<div>
								<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
									<g>
										<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
									</g>
								</svg> = Collection
							</div>
							<div>
								<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
									<g>
										<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
									</g>
								</svg> = General Observation
							</div>
						</div>
						<?php
					}
					?>
				</fieldset>
			</td>
			<td style="width:375px;" valign='top'>
				<div>
					<fieldset>
						<legend>Add Point of Reference</legend>
						<div style='float:left;width:275px;'>
							<div class='latlongdiv' style='display:block;'>
								<div>
									Latitude decimal: <input name='lat' id='lat' size='10' type='text' /> eg: 34.57
								</div>
								<div style="margin-top:5px;">
									Longitude decimal: <input name='lng' id='lng' size='10' type='text' /> eg: -112.38
								</div>
								<div style='font-size:80%;margin-top:5px;'>
									<a href='#' onclick='javascript: toggleLatLongDivs();'>Enter in D:M:S format</a>
								</div>
							</div>
							<div class='latlongdiv' style='display:none;'>
								<div>
									Latitude: 
									<input name='latdeg' id='latdeg' size='2' type='text' />&deg;
									<input name='latmin' id='latmin' size='5' type='text' />&prime;
									<input name='latsec' id='latsec' size='5' type='text' />&Prime;
									<select name='latns' id='latns'>
										<option value='N'>N</option>
										<option value='S'>S</option>
									</select>
								</div>
								<div style="margin-top:5px;">
									Longitude: 
									<input name='longdeg' id='longdeg' size='2' type='text' />&deg;
									<input name='longmin' id='longmin' size='5' type='text' />&prime;
									<input name='longsec' id='longsec' size='5' type='text' />&Prime;
									<select name='longew' id='longew'>
										<option value='E'>E</option>
										<option value='W' selected>W</option>
									</select>
								</div>
								<div style='font-size:80%;margin-top:5px;'>
									<a href='#' onclick='toggleLatLongDivs();'>Enter in Decimal format</a>
								</div>
							</div>
						</div>
						<div style="float:right;width:100px;">
							<div style="float:right;">
								Marker Name: <input name='title' id='title' size='20' type='text' />
							</div><br />
							<div style="float:right;margin-top:10px;">
								<input type='submit' value='Add Marker' onclick='addRefPoint();' />
							</div>
						</div>
					</fieldset>
				</div>
			</td>
		</tr>
	</table>
</body>
</html>
