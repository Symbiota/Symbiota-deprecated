<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/TaxonProfileMap.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

$taxonValue = array_key_exists('taxon',$_REQUEST)?$_REQUEST['taxon']:0;
$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$mapType = array_key_exists('maptype',$_REQUEST)?$_REQUEST['maptype']:0;
$taxonAuthorityId = array_key_exists('taxonfilter',$_REQUEST)?$_REQUEST['taxonfilter']:1;
$gridSize = array_key_exists('gridSizeSetting',$_REQUEST)?$_REQUEST['gridSizeSetting']:10;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)?$_REQUEST['minClusterSetting']:50;
$clusterOff = array_key_exists('clusterSwitch',$_REQUEST)?$_REQUEST['clusterSwitch']:0;
$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
//$mapSymbology = array_key_exists("mapsymbology",$_REQUEST)?$_REQUEST["mapsymbology"]:"coll";
$recLimit = array_key_exists("recordlimit",$_REQUEST)?$_REQUEST["recordlimit"]:"5000";
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:1; 

$mapManager = new MapInterfaceManager();
$queryShape = '';
$showTaxaBut = 1;

/*if($_REQUEST && array_key_exists("taxa",$_REQUEST)){
	if(!is_numeric($_REQUEST["taxa"])){
		$taxaStr = str_replace(",",";",$_REQUEST["taxa"]);
		$taxaArr = explode(";",$taxaStr);
		if(count($taxaArr) == 1){
			$mapSymbology = 'coll';
			$showTaxaBut = 0;
		}
	}
}*/

if((array_key_exists("upperlat",$_REQUEST)) || (array_key_exists("pointlat",$_REQUEST)) || (array_key_exists("poly_array",$_REQUEST))){
	$queryShape = $mapManager->createShape();
}

$collList = $mapManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);
$mapManager->setFieldArr(0);
$mapWhere = '';
$genObs = $mapManager->getGenObsInfo();
$tArr = Array();
$coordExist = false;
$spatial = false;
$iconKeys = Array(); 
$coordArr = Array();
$stArr = Array();
$groupCnt = 1;
$grpCntArr = Array();
$mysqlVersion = $mapManager->getMysqlVersion();
if($mysqlVersion){
	$mysqlVerNums = explode(".", $mysqlVersion);
	if($mysqlVerNums[0] > 5){
		$spatial = true;
	}
	elseif($mysqlVerNums[0] == 5){
		if($mysqlVerNums[1] > 6){
			$spatial = true;
		}
		elseif($mysqlVerNums[1] == 6){
			if($mysqlVerNums[2] >= 1){
				$spatial = true;
			}
		}
	}
}

$dbArr = Array();
if(array_key_exists('db',$_REQUEST)){
	$dbArr = $_REQUEST["db"];
}

if($mapType && $mapType == 'taxa'){
	$taxaMapManager = new TaxonProfileMap();
	//Finds tidaccepted of $taxonValue, then finds first 5 tidaccepted of taxa with the parenttid of $taxonValue
	//creates array of tids, scinames ($this->taxArr), sciname of all taxa, seeds $this->synMap with accepted names, adds synonyms to $this->synMap
	$taxaMapManager->setTaxon($taxonValue);
	//returns $this->synMap
	$synMap = $taxaMapManager->getSynMap();
	//Creates an empty array in $this->taxaArr for each key in $this->taxArr, then returns $this->taxaMap ..?
	$taxaMapManager->getTaxaMap();
	//Creates sql where statement based on tids in $this->synMap
	$mapWhere = $taxaMapManager->getTaxaSqlWhere();
	//returns $this->taxaArr
	$tArr = $taxaMapManager->getTaxaArr();
}
elseif($mapType && $mapType == 'occquery'){
	$mapWhere = $mapManager->getSqlWhere();
	$stArr = $mapManager->getSearchTermsArr();
	/*if($mapSymbology == 'taxa'){
		//$fullTaxaList = $mapManager->getFullTaxaArr($mapWhere,$taxonAuthorityId,$stArr);
		$coordArr = $mapManager->getTaxaGeoCoords(0,false,$mapWhere,$recLimit);
	}*/
	//elseif($mapSymbology == 'coll'){
		$fullCollList = $mapManager->getFullCollArr($stArr);
		$coordArr = $mapManager->getCollGeoCoords(0,false,$mapWhere,$recLimit);
	//}
	$jsonStArr = json_encode($stArr);
}

if($coordArr && !is_numeric($coordArr)){
	foreach($coordArr as $sciName => $valueArr){
		if(count($valueArr)>1){
			$coordExist = true;
		}
	}
}
?>
<!DOCTYPE html >
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $defaultTitle; ?> - Map Interface</title>
	<link href="css/base.css" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery.mobile-1.4.0.min.css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery.symbiota.css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui_accordian.css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<style type="text/css">
		#tabs1 a,#tabs2 a{
			outline-color: transparent;
		}
	</style>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.mobile-1.4.0.min.js"></script>
	<script type="text/javascript" src="../js/jquery-1.9.1.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.10.4.js"></script>
	<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=drawing"></script>
	<script type="text/javascript" src="../js/jscolor/jscolor.js"></script>
	<script type="text/javascript">
	$(function() {
		$( "#accordion" ).accordion({
			collapsible: true,
			heightStyle: "fill"
			<?php
			if($coordExist){
				echo ",active: 1";
			}
			?>
		});
	});
	</script>
	<script type="text/javascript" src="../js/symb/collections.mapinterface.js?var=1303"></script>
	<script type="text/javascript" src="../js/symb/markerclusterer.js?ver=260913"></script>
	<script type="text/javascript" src="../js/symb/oms.min.js"></script>
	<script type="text/javascript" src="../js/symb/keydragzoom.js"></script>
	<script type="text/javascript" src="../js/symb/infobox.js"></script>
    <script type="text/javascript">
		var map;
		var useLLDecimal = true;
	    var infoWins = [];
	    var puWin;
        var minLng = 180;
        var minLat = 90;
        var maxLng = -180;
        var maxLat = -90;
		var markers = [];
		var marker;
		var drawingManager = null;
		var oms = null;
		var selectedShape = null;
		var gotCoords = true;
		var mapSymbol = 'coll';
		
		function getCoords(){
			if (navigator.geolocation) {
				var timeoutVal = 5000;
				navigator.geolocation.getCurrentPosition(
					initialize, 
					function (error) { 
						if (error.code){
							gotCoords = false;
							initialize();
						}
					}
				);
			}
			else{
				gotCoords = false;
				initialize();
			}
		}
		
		function getMarker(newLat, newLng, newTitle, newIcon, type, tid, occid, clid){
			var m = new google.maps.Marker({
				position: new google.maps.LatLng(newLat, newLng),
				title: newTitle,
				<?php
				if($clusterOff==1){
					?>
					map: map,
					<?php
				}
				?>
				icon: newIcon,
				customInfo: type,
				taxatid: tid,
				occid: occid,
				clid: clid
			});
			//markers<?php echo $groupCnt; ?>.push(m);
			return m;
		}
		
		function initialize(position){
			<?php
			$latCen = 41.0;
			$longCen = -95.0;
			$coorArr = explode(";",$mappingBoundaries);
			if($coorArr && count($coorArr) == 4){
				$latCen = ($coorArr[0] + $coorArr[2])/2;
				$longCen = ($coorArr[1] + $coorArr[3])/2;
			}
			?>
			var pos = '';
			if(gotCoords==true){
				pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
				posLat = position.coords.latitude;
				posLong = position.coords.longitude;
				document.getElementById("geocriteria").style.display = "none";
				document.getElementById("distancegeocriteria").style.display = "block";
			}
			else{
				pos = new google.maps.LatLng(<?php echo $latCen.', '.$longCen; ?>);
			}
			
			var dmOptions = {
				zoom: <?php echo ($coordExist==true?4:6);?>,
				center: pos,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				mapTypeControl: true,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
					position: google.maps.ControlPosition.TOP_RIGHT
				},
				panControl: true,
				panControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle.LARGE,
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				scaleControl: true,
				scaleControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				streetViewControl: false
			};

	    	map = new google.maps.Map(document.getElementById("map"), dmOptions);
			
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
						google.maps.drawing.OverlayType.RECTANGLE,
						google.maps.drawing.OverlayType.CIRCLE
						<?php
						if($spatial){
							?>
							,google.maps.drawing.OverlayType.POLYGON
							<?php
						}
						?>
					]
				},
				markerOptions: {
					draggable: true
				},
				polylineOptions: {
					editable: true
				},
				rectangleOptions: polyOptions,
				circleOptions: polyOptions,
				polygonOptions: polyOptions
			});
	
			drawingManager.setMap(map);
			
			google.maps.event.addDomListener(
				document.getElementById("distFromMe"), 
				'change', 
				function(){ 
					var distance = document.getElementById("distFromMe").value;
					if(distance){
						document.getElementById("pointlat").value = posLat;
						document.getElementById("pointlong").value = posLong;
						document.getElementById("radius").value = distance;
					}
				}
			);
			
			google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
				if (e.type != google.maps.drawing.OverlayType.MARKER) {
					// Switch back to non-drawing mode after drawing a shape.
					deleteSelectedShape();
					drawingManager.setDrawingMode(null);
					
					var newShapeType = '';
					newShapeType = e.type;
					// Add an event listener that selects the newly-drawn shape when the user
					// mouses down on it.
					var newShape = e.overlay;
					newShape.type = e.type;
					/*google.maps.event.addListener(newShape, 'click', function() {
						setSelection(newShape);
					});*/
					google.maps.event.addListener(newShape, 'dragend', function() {
						setSelection(newShape);
					});
					if (newShapeType == 'circle'){
						getCircleCoords(newShape);
						google.maps.event.addListener(newShape, 'radius_changed', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape, 'center_changed', function() {
							setSelection(newShape);
						});
					}
					if (newShapeType == 'rectangle'){
						getRectangleCoords(newShape);
						google.maps.event.addListener(newShape, 'bounds_changed', function() {
							setSelection(newShape);
						});
					}
					if (newShapeType == 'polygon'){
						getPolygonCoords(newShape);
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
			if($coordExist==true){
				$markerCnt = 0;
				$minLng = 180;
				$minLat = 90;
				$maxLng = -180;
				$maxLat = -90;
				$tIdArr = array();
				foreach($coordArr as $sciName => $valueArr){
					?>
					markers<?php echo $groupCnt; ?> = [];
					<?php
					$iconColor = $valueArr["color"];
					if($iconColor && (count($valueArr)>1)) {
						$grpCntArr[] = $groupCnt;
						/*if($mapSymbology == 'taxa'){
							$keyLabel = '<i>'.$sciName.'</i>';
						}*/
						//if($mapSymbology == 'coll'){
							$keyLabel = $sciName;
						//}
						$iconKey = '<div style="display:table-row;">';
						$iconKey .= '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="keyColor'.$groupCnt.'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="'.$iconColor.'" onchange="" /></div>';
						$iconKey .= '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
						$iconKey .= '<div style="display:table-cell;width:250px;vertical-align:middle;padding-left:8px;">'.$keyLabel.'</div>';
						$iconKey .= '</div>';
						$iconKey .= '<div style="display:table-row;height:8px;"></div>';
						$iconKeys[] = $iconKey;
						?>
						function changeMainKey<?php echo $groupCnt; ?>(newColor){ 
							if(mapSymbol == 'taxa'){
								clearTaxaSymbology();
							}
							changeKeyColor(newColor,markers<?php echo $groupCnt; ?>);
							<?php
							if($clusterOff==0){
								?>
								if(markerCluster<?php echo $groupCnt; ?>){
									markerCluster<?php echo $groupCnt; ?>.clearMarkers();
								}
								newMcOptions<?php echo $groupCnt; ?> = {
									styles: [{
										color: newColor
									}],
									maxZoom: 13,
									gridSize: <?php echo $gridSize; ?>,
									minimumClusterSize: <?php echo $minClusterSize; ?>
								}
								markerCluster<?php echo $groupCnt; ?> = new MarkerClusterer(map, markers<?php echo $groupCnt; ?>, newMcOptions<?php echo $groupCnt; ?>);
								<?php
							}
							?>
						}
						var key<?php echo $groupCnt; ?> = document.getElementById("keyColor<?php echo $groupCnt; ?>");
						google.maps.event.addDomListener(
							key<?php echo $groupCnt; ?>, 
							'change', 
							function(){ 
								var newColor = document.getElementById("keyColor<?php echo $groupCnt; ?>").value;
								changeMainKey<?php echo $groupCnt; ?>(newColor);
								mapSymbol = 'coll';
							}
						);
						<?php
					}
					unset($valueArr["color"]);
					foreach($valueArr as $occId => $spArr){
						if($spArr['tidinterpreted']){
							if(array_key_exists($spArr['tidinterpreted'],$tIdArr)){
								if(!in_array($groupCnt, $tIdArr[$spArr['tidinterpreted']])){
									$tIdArr[$spArr['tidinterpreted']][] = $groupCnt;
								}
							}
							else{
								$tIdArr[$spArr['tidinterpreted']][] = $groupCnt;
								?>
								var markerCluster<?php echo $spArr['tidinterpreted']; ?> = null;
								<?php
							}
						}
						//Find max/min point values
						$llArr = explode(',',$spArr['latLngStr']);
						if($llArr[0] < $minLat) $minLat = $llArr[0];
						if($llArr[0] > $maxLat) $maxLat = $llArr[0];
						if($llArr[1] < $minLng) $minLng = $llArr[1];
						if($llArr[1] > $maxLng) $maxLng = $llArr[1];
						//Create marker
						$spStr = '';
						$titleStr = $spArr['latLngStr'];
						$type = '';
						$displayStr = '';      
						/*if(is_numeric($spArr['catalognumber'])){
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
						}*/
						$displayStr = $spArr['identifier'];
						if($spArr['collid'] == $genObs){
							//$displayStr = "General Observation";
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
						echo 'markers'.$groupCnt.'.push(m'.$markerCnt.');',"\n";
						$markerCnt++;
					}
					?>
					mcOptions<?php echo $groupCnt; ?> = {
						styles: [{
							color: "<?php echo $iconColor; ?>"
						}],
						maxZoom: 13,
						gridSize: <?php echo $gridSize; ?>,
						minimumClusterSize: <?php echo $minClusterSize; ?>
					}
					
					//Initialize clusterer with options
					<?php
					if($clusterOff==0){
						?>
						markerCluster<?php echo $groupCnt; ?> = new MarkerClusterer(map, markers<?php echo $groupCnt; ?>, mcOptions<?php echo $groupCnt; ?>);
						<?php
					}
					$groupCnt++;
				}
				?>
				
				<?php
				foreach($tIdArr as $tId => $gcArr){
					?>
					function changeTaxaKey<?php echo $tId; ?>(newColor){ 
						if(markerCluster<?php echo $tId; ?>){
							markerCluster<?php echo $tId; ?>.clearMarkers();
						}
						var newMarkers<?php echo $tId; ?> = [];
						<?php
						foreach($grpCntArr as $gCnt){
							?>
							if (markers<?php echo $gCnt; ?>) {
								for (i in markers<?php echo $gCnt; ?>) {
									if(markers<?php echo $gCnt; ?>[i].taxatid == <?php echo $tId; ?>){
										<?php
										if($clusterOff==0){
											?>
											markerCluster<?php echo $gCnt; ?>.removeMarker(markers<?php echo $gCnt; ?>[i]);
											<?php
										}
										?>
										if(markers<?php echo $gCnt; ?>[i].customInfo=='obs'){
											var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
											markers<?php echo $gCnt; ?>[i].setIcon(markerIcon);
										}
										if(markers<?php echo $gCnt; ?>[i].customInfo=='spec'){
											var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
											markers<?php echo $gCnt; ?>[i].setIcon(markerIcon);
										}
										newMarkers<?php echo $tId; ?>.push(markers<?php echo $gCnt; ?>[i]);
										//markers<?php echo $gCnt; ?>[i].setMap(null);
									}
								}
							}
							<?php
						}
						if($clusterOff==0){
							?>
							newMcOptions<?php echo $tId; ?> = {
								styles: [{
									color: newColor
								}],
								maxZoom: 13,
								gridSize: <?php echo $gridSize; ?>,
								minimumClusterSize: <?php echo $minClusterSize; ?>
							}
							markerCluster<?php echo $tId; ?> = new MarkerClusterer(map, newMarkers<?php echo $tId; ?>, newMcOptions<?php echo $tId; ?>);
							<?php
						}
						?>
					}
					<?php
				}
				?>
				
				<?php
				foreach($tIdArr as $tId => $gcArr){
					?>
					var taxaNewColor<?php echo $tId; ?> = document.getElementById("taxaColor<?php echo $tId; ?>");
					google.maps.event.addDomListener(
						taxaNewColor<?php echo $tId; ?>, 
						'change', 
						function(){ 
							var newtaxaColor = taxaNewColor<?php echo $tId; ?>.value;
							if(mapSymbol == 'coll'){
								resetMainSymbology();
								document.getElementById("taxaColor<?php echo $tId; ?>").color.fromString(newtaxaColor);
							}
							changeTaxaKey<?php echo $tId; ?>(newtaxaColor);
							mapSymbol = 'taxa';
						}
					);
					<?php
				}
				?>
				
				function clearTaxaSymbology(){ 
					<?php
					foreach($tIdArr as $tId => $gcArr){
						?>
						if(markerCluster<?php echo $tId; ?>){
							markerCluster<?php echo $tId; ?>.clearMarkers();
						}
						document.getElementById("taxaColor<?php echo $tId; ?>").color.fromString("FFFFFF");
						<?php
					}
					?>
				}
				
				function resetMainSymbology(){ 
					<?php
					foreach($grpCntArr as $gcnt){
						?>
						changeMainKey<?php echo $gcnt; ?>("e69e67");
						document.getElementById("keyColor<?php echo $gcnt; ?>").color.fromString("E69E67");
						<?php
					}
					?>
				}
				
				var resetButton1 = document.getElementById("symbolizeReset1");
				var resetButton2 = document.getElementById("symbolizeReset2");
				google.maps.event.addDomListener(
					resetButton1, 
					'click', 
					function(){ 
						document.getElementById("symbolizeReset1").disabled = true;
						clearTaxaSymbology();
						resetMainSymbology();
						mapSymbol = 'coll';
						document.getElementById("symbolizeReset1").disabled = false;
					}
				);
				
				google.maps.event.addDomListener(
					resetButton2, 
					'click', 
					function(){ 
						document.getElementById("symbolizeReset2").disabled = true;
						clearTaxaSymbology();
						resetMainSymbology();
						mapSymbol = 'coll';
						document.getElementById("symbolizeReset2").disabled = false;
					}
				);
				
				var randomColorColl = document.getElementById("randomColorColl");
				var randomColorTaxa = document.getElementById("randomColorTaxa");
				google.maps.event.addDomListener(
					randomColorColl, 
					'click', 
					function(){ 
						document.getElementById("randomColorColl").disabled = true;
						if(mapSymbol == 'taxa'){
							clearTaxaSymbology();
						}
						var usedColors = [];
						<?php
						foreach($grpCntArr as $gcnt){
							?>
							var randColor = generateRandColor();
							while (usedColors.contains(randColor)) {
								randColor = generateRandColor();
							}
							usedColors.push(randColor);
							changeMainKey<?php echo $gcnt; ?>(randColor);
							document.getElementById("keyColor<?php echo $gcnt; ?>").color.fromString(randColor);
							<?php
						}
						?>
						mapSymbol = 'coll';
						document.getElementById("randomColorColl").disabled = false;
					}
				);
				
				google.maps.event.addDomListener(
					randomColorTaxa, 
					'click', 
					function(){ 
						document.getElementById("randomColorTaxa").disabled = true;
						if(mapSymbol == 'coll'){
							resetMainSymbology();
						}
						var usedColors = [];
						<?php
						foreach($tIdArr as $tId => $gcArr){
							?>
							var randColor = generateRandColor();
							while (usedColors.contains(randColor)) {
								randColor = generateRandColor();
							}
							usedColors.push(randColor);
							changeTaxaKey<?php echo $tId; ?>(randColor);
							document.getElementById("taxaColor<?php echo $tId; ?>").color.fromString(randColor);
							<?php
						}
						?>
						mapSymbol = 'taxa';
						document.getElementById("randomColorTaxa").disabled = false;
					}
				);
				
				var swLatLng = new google.maps.LatLng(<?php echo $minLat.','.$minLng; ?>);
				var neLatLng = new google.maps.LatLng(<?php echo $maxLat.','.$maxLng; ?>);
				var llBounds = new google.maps.LatLngBounds(swLatLng, neLatLng);
				map.fitBounds(llBounds);
				<?php
			}
			?>
		
			// Clear the current selection when the drawing mode is changed, or when the
			// map is clicked.
			//google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
			//google.maps.event.addListener(map, 'click', clearSelection);
			google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteSelectedShape);

			<?php echo ($queryShape?$queryShape:''); ?>
			
			<?php
			if($stArr && $coordExist==false && !is_numeric($coordArr)){
				?>
				alert("No occurrences matched your search criteria.");
				<?php
			}
			
			if($stArr && $coordExist==false && is_numeric($coordArr)){
				?>
				alert("Your search produced <?php echo $coordArr; ?> results, which exceeds the record limit. Please try refining your search, or increase the record limit. Please note though, that increasing the record limit can cause delays in the page loading, or your browser to crash and can be no higher than 50000.");
				<?php
			}
			?>
		}
		
		google.maps.event.addDomListener(window, 'load', getCoords);
	</script>
</head> 
<body style='width:100%;height:100%;'>
	<div data-role="page" id="page1">
		<div role="main" class="ui-content">
			<a href="#defaultpanel" style="position:absolute;top:0;left:0;margin-top:0px;z-index:10;padding-top:3px;padding-bottom:3px;text-decoration:none;" data-role="button" data-inline="true" data-icon="bars">Open</a>
		</div>
		<!-- defaultpanel -->
		<div data-role="panel" data-dismissible="false" class="overflow: hidden;" style="width:376px;" id="defaultpanel" data-position="left" data-display="overlay" >
			<div class="panel-content">
				<div id="mapinterface">
					<div id="accordion" style="height:680px;" >
						<?php //echo "MySQL Version: ".$mysqlVersion; ?>
						<?php //echo $spatial?"yes":"no"; ?>
						<?php //echo "Request: ".json_encode($_REQUEST); ?>
						<?php //echo "starr: ".json_encode($stArr); ?>
						<?php //echo "mapWhere: ".$mapWhere; ?>
						<?php //echo "coordArr: ".json_encode($coordArr); ?>
						<?php //echo "clusteringOff: ".$clusterOff; ?>
						<?php //echo "coordArr: ".$coordArr; ?>
						<?php //echo "tIdArr: ".json_encode($tIdArr); ?>
						<h3>Search Criteria</h3>
						<div id="tabs1" style="width:375px;padding:0px;">
							<form name="mapsearchform" id="mapsearchform" data-ajax="false" action="mapinterface.php" method="get" onsubmit="return verifyCollForm(this);return checkForm();">
								<ul>
									<li><a href="#searchcriteria"><span>Criteria</span></a></li>
									<li><a href="#searchcollections"><span>Collections</span></a></li>
								</ul>
								<div id="searchcollections" style="">
									<div class="mapinterface">
										<div>
											<h1 style="margin:0px 0px 8px 0px;font-size:15px;">Collections to be Searched</h1>
										</div>
										<?php 
										if($specArr && $obsArr){
											?>
											<div id="specobsdiv">
												<div style="margin:0px 0px 10px 20px;">
													<input id="dballcb" data-role="none" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?php echo (((array_key_exists("db",$_REQUEST)&&in_array("all",$dbArr))||!$dbArr)?'checked':'') ?> />
													Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
												</div>
												<?php 
												$mapManager->outputFullMapCollArr($dbArr,$specArr); 
												if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>'; 
												$mapManager->outputFullMapCollArr($dbArr,$obsArr);
												?>
												<div style="clear:both;">&nbsp;</div>
											</div>
											<?php 
										}
										?>
									</div>	
								</div>
								<div id="searchcriteria" style="">
									<div style="height:25px;">
										<div style="float:left;">
											Record Limit:
											<input data-role="none" type="text" id="recordlimit" style="width:75px;" name="recordlimit" value="<?php echo ($recLimit?$recLimit:""); ?>" title="Maximum record amount returned from search." onchange="checkRecordLimit(this.form);" />
										</div>
										<div style="float:right;">
											<input type="hidden" name="maptype" value="occquery" />
											<input type="hidden" id="gridSizeSetting" name="gridSizeSetting" value="<?php echo (array_key_exists("gridSizeSetting",$_REQUEST)?$_REQUEST["gridSizeSetting"]:"60"); ?>" />
											<input type="hidden" id="minClusterSetting" name="minClusterSetting" value="<?php echo (array_key_exists("minClusterSetting",$_REQUEST)?$_REQUEST["minClusterSetting"]:"10"); ?>" />
											<input type="hidden" id="clusterSwitch" name="clusterSwitch" value="<?php echo (array_key_exists("clusterSwitch",$_REQUEST)?$_REQUEST["clusterSwitch"]:"0"); ?>" />
											<!--<input type="hidden" id="mapsymbology" name="mapsymbology" value='<?php //echo ($mapSymbology?$mapSymbology:"coll"); ?>' />-->
											<input type="hidden" id="pointlat" name="pointlat" value='<?php echo (array_key_exists("pointlat",$_REQUEST)?$_REQUEST["pointlat"]:""); ?>' />
											<input type="hidden" id="pointlong" name="pointlong" value='<?php echo (array_key_exists("pointlong",$_REQUEST)?$_REQUEST["pointlong"]:""); ?>' />
											<input type="hidden" id="radius" name="radius" value='<?php echo (array_key_exists("radius",$_REQUEST)?$_REQUEST["radius"]:""); ?>' />
											<input type="hidden" id="upperlat" name="upperlat" value='<?php echo (array_key_exists("upperlat",$_REQUEST)?$_REQUEST["upperlat"]:""); ?>' />
											<input type="hidden" id="rightlong" name="rightlong" value='<?php echo (array_key_exists("rightlong",$_REQUEST)?$_REQUEST["rightlong"]:""); ?>' />
											<input type="hidden" id="bottomlat" name="bottomlat" value='<?php echo (array_key_exists("bottomlat",$_REQUEST)?$_REQUEST["bottomlat"]:""); ?>' />
											<input type="hidden" id="leftlong" name="leftlong" value='<?php echo (array_key_exists("leftlong",$_REQUEST)?$_REQUEST["leftlong"]:""); ?>' />
											<input type="hidden" id="poly_array" name="poly_array" value='<?php echo (array_key_exists("poly_array",$_REQUEST)?$_REQUEST["poly_array"]:""); ?>' />
											<button data-role="none" type=button id="resetform" name="resetform" onclick='window.open("mapinterface.php", "_self");' >Reset</button>
											<button data-role="none" id="display2" name="display2" onclick='submitMapForm(this.form);' >Search</button>
										</div>
									</div>
									<div style="margin:10 0 10 0;"><hr></div>
									<div>
										<span style=""><input data-role="none" type='checkbox' name='thes' value='1' <?php if(array_key_exists("thes",$_REQUEST) && $_REQUEST["thes"]) echo "CHECKED"; ?> >Include Synonyms</span>
									</div>
									<div id="taxonSearch0">
										<div id="taxa_autocomplete" >
											<select data-role="none" id="taxontype" name="type">
												<option id='familysciname' value='1' <?php if(array_key_exists("type",$_REQUEST) && $_REQUEST["type"] == "1") echo "SELECTED"; ?> >Family or Scientific Name</option>
												<option id='family' value='2' <?php if(array_key_exists("type",$_REQUEST) && $_REQUEST["type"] == "2") echo "SELECTED"; ?> >Family only</option>
												<option id='sciname' value='3' <?php if(array_key_exists("type",$_REQUEST) && $_REQUEST["type"] == "3") echo "SELECTED"; ?> >Scientific Name only</option>
												<option id='classorder' value='4' <?php if(array_key_exists("type",$_REQUEST) && $_REQUEST["type"] == "4") echo "SELECTED"; ?> >Class / Order</option>
												<option id='commonname' value='5' <?php if(array_key_exists("type",$_REQUEST) && $_REQUEST["type"] == "5") echo "SELECTED"; ?> >Common Name</option>
											</select><br />
											Taxa: <input data-role="none" id="taxa" type="text" style="width:275px;" name="taxa" value="<?php if(array_key_exists("taxa",$_REQUEST)) echo $_REQUEST["taxa"]; ?>" title="Separate multiple taxa w/ commas" />
										</div>
									</div>
									<div style="margin:10 0 10 0;"><hr></div>
									<div>
										Country: <input data-role="none" type="text" id="country" style="width:225px;" name="country" value="<?php if(array_key_exists("country",$_REQUEST)) echo $_REQUEST["country"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div>
										State/Province: <input data-role="none" type="text" id="state" style="width:150px;" name="state" value="<?php if(array_key_exists("state",$_REQUEST)) echo $_REQUEST["state"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div>
										County: <input data-role="none" type="text" id="county" style="width:225px;"  name="county" value="<?php if(array_key_exists("county",$_REQUEST)) echo $_REQUEST["county"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div>
										Locality: <input data-role="none" type="text" id="locality" style="width:225px;" name="local" value="<?php if(array_key_exists("local",$_REQUEST)) echo $_REQUEST["local"]; ?>" />
									</div>
									<div style="margin:10 0 10 0;"><hr></div>
									<div id="shapecriteria">
										<div id="noshapecriteria" style="display:<?php echo ((!$_REQUEST || ((!$_REQUEST['poly_array']) && (!$_REQUEST['upperlat'])))?'block':'none'); ?>;">
											<div id="geocriteria" style="display:<?php echo ((!$_REQUEST || ((!$_REQUEST['poly_array']) && (!$_REQUEST['distFromMe']) && (!$_REQUEST['pointlat']) && (!$_REQUEST['upperlat'])))?'block':'none'); ?>;">
												<div>
													Use the shape tools on the map to select occurrences within a given shape.
												</div>
											</div>
											<div id="distancegeocriteria" style="display:<?php echo ((!$_REQUEST || ($_REQUEST && $_REQUEST['distFromMe']))?'block':'none'); ?>;">
												<div>
													Within <input data-role="none" type="text" id="distFromMe" style="width:40px;" name="distFromMe" value="<?php if(array_key_exists('distFromMe',$_REQUEST)) echo $_REQUEST['distFromMe']; ?>" /> miles from me, or
													use the shape tools on the map to select occurrences within a given shape.
												</div>
											</div>
										</div>
										<div id="polygeocriteria" style="display:<?php echo (($_REQUEST && $_REQUEST['poly_array'])?'block':'none'); ?>;">
											<div>
												Within the selected polygon.
											</div>
										</div>
										<div id="circlegeocriteria" style="display:<?php echo (($_REQUEST && $_REQUEST['pointlat'] && !$_REQUEST['distFromMe'])?'block':'none'); ?>;">
											<div>
												Within the selected circle.
											</div>
										</div>
										<div id="rectgeocriteria" style="display:<?php echo (($_REQUEST && $_REQUEST['upperlat'])?'block':'none'); ?>;">
											<div>
												Within the selected rectangle.
											</div>
										</div>
										<div id="deleteshapediv" style="margin-top:5px;display:<?php echo (($_REQUEST && ($_REQUEST['pointlat'] || $_REQUEST['upperlat'] || $_REQUEST['poly_array']))?'block':'none'); ?>;">
											<button data-role="none" type=button id="delete-button">Delete Selected Shape</button>
										</div>
										<div><hr></div>
									</div>
									<div>
										Collector's Last Name: 
										<input data-role="none" type="text" id="collector" style="width:125px;" name="collector" value="<?php if(array_key_exists("collector",$_REQUEST)) echo $_REQUEST["collector"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div>
										Collector's Number: 
										<input data-role="none" type="text" id="collnum" style="width:125px;" name="collnum" value="<?php if(array_key_exists("collnum",$_REQUEST)) echo $_REQUEST["collnum"]; ?>" title="Separate multiple terms by commas and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750" />
									</div>
									<div>
										Collection Date: 
										<input data-role="none" type="text" id="eventdate1" style="width:80px;" name="eventdate1" style="width:100px;" value="<?php if(array_key_exists("eventdate1",$_REQUEST)) echo $_REQUEST["eventdate1"]; ?>" title="Single date or start date of range" /> - 
										<input data-role="none" type="text" id="eventdate2" style="width:80px;" name="eventdate2" style="width:100px;" value="<?php if(array_key_exists("eventdate2",$_REQUEST)) echo $_REQUEST["eventdate2"]; ?>" title="End date of range; leave blank if searching for single date" />
									</div>
									<div style="margin:10 0 10 0;"><hr></div>
									<div>
										Catalog Number:
										<input data-role="none" type="text" id="catnum" style="width:150px;" name="catnum" value="<?php if(array_key_exists("catnum",$_REQUEST)) echo $_REQUEST["catnum"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<!-- 
									<div>
										Type Status:
										<input type="text" size="32" id="typestatus" name="typestatus" value="<?php //if(array_key_exists("typestatus",$_REQUEST)) echo $_REQUEST["typestatus"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									 -->
									<div><hr></div>
									<div>
										<!--  <a href="javascript:var popupReference=window.open('support/help.html','technical','toolbar=1,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=800,height=700,left=20,top=20');" class="bodylink">
											Click Here</a> for more information on how this query page works... -->
									</div>
									<input type="hidden" name="reset" value="1" />
								</div>
							</form>
						</div>
						<?php
						if($coordExist==true){
							?>
							<h3>Records and Taxa</h3>
							<div id="tabs2" style="width:375px;padding:0px;">
								<ul>
									<li><a href='#symbology'><span>Collections</span></a></li>
									<li><a href='maprecordlist.php?starr=<?php echo $jsonStArr; ?>'><span>Records</span></a></li>
									<li><a href='#maptaxalist'><span>Taxa List</span></a></li>
								</ul>
								<div id="symbology" style="">
									<div style="height:40px;margin-bottom:15px;">
										<?php
										if($genObs){
											?>
											<div style="float:left;">
												<div>
													<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
														<g>
															<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
														</g>
													</svg> = Collection
												</div>
												<div style="margin-top:5px;" >
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
										<div id="symbolizeResetButt" style='margin-right:15px;float:right;' >
											<div>
												<button data-role="none" id="symbolizeReset1" name="symbolizeReset1" onclick='' >Reset Symbology</button>
											</div>
											<div>
												<button data-role="none" id="randomColorColl" name="randomColorColl" onclick='' >Auto Color</button>
											</div>
										</div>
									</div>
									<hr />
									<div style="" >
										<div style="margin-top:8px;">
											<div style="display:table;">
												<div id="symbologykeysbox">
													<?php 
														foreach($iconKeys as $iconValue){
															echo $iconValue;
														}
														?>
												</div>
											</div>
										</div>
									</div>
									<!--<div id="symbolizeTaxaButt" style='margin-top:8px;margin-right:15px;float:right;display:<?php //echo ((($mapSymbology=='coll') && ($showTaxaBut == 1))?'block':'none'); ?>;' >
										<button data-role="none" id="symbolizeTaxa" name="symbolizeTaxa" onclick='reSymbolizeMap("taxa");' >Symbolize by Taxa</button>
									</div>
									<div id="symbolizeCollButt" style='margin-top:8px;margin-right:15px;float:right;display:<?php //echo (($mapSymbology=='taxa')?'block':'none'); ?>;' >
										<button data-role="none" id="symbolizeColl" name="symbolizeColl" onclick='reSymbolizeMap("coll");' >Symbolize by Collection</button>
									</div>-->
								</div>
								<div id="maptaxalist" >
									<?php
										$checklistArr = $mapManager->getChecklist($stArr,$mapWhere);
										?>
										<div style="height:40px;margin-bottom:15px;">
											<?php
											if($genObs){
												?>
												<div style="float:left;">
													<div>
														<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
															<g>
																<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
															</g>
														</svg> = Collection
													</div>
													<div style="margin-top:5px;" >
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
											<div id="symbolizeResetButt" style='margin-right:15px;float:right;' >
												<div>
													<button data-role="none" id="symbolizeReset2" name="symbolizeReset2" onclick='' >Reset Symbology</button>
												</div>
												<div>
													<button data-role="none" id="randomColorTaxa" name="randomColorTaxa" onclick='' >Auto Color</button>
												</div>
											</div>
										</div>
										<hr />
										<?php
										//echo json_encode($checklistArr);
										echo "<div style='font-weight:bold;font-size:125%;'>Taxa Count: ".$mapManager->getChecklistTaxaCnt()."</div>";
										$undFamilyArray = Array();
										if(array_key_exists("undefined",$checklistArr)) $undFamilyArray = $checklistArr["undefined"]; 
										foreach($checklistArr as $family => $sciNameArr){
											echo "<div style='margin-left:5;margin-top:5;'><h3>".$family."</h3></div>";
											echo "<div style='display:table;'>";
											foreach($sciNameArr as $sciName => $fsciArr){
												if(array_key_exists($fsciArr['tid'],$tIdArr)){
													echo '<div id="'.$fsciArr['tid'].'keyrow">';
													echo '<div style="display:table-row;">';
													echo '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'.$fsciArr['tid'].'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="ffffff" onclick="" /></div>';
													echo '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
													echo "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../taxa/index.php?taxon=".$fsciArr['sciname']."'>".$fsciArr['sciname']."</a></i></div>";
													echo "</div>";
													echo '<div style="display:table-row;height:8px;"></div>';
													echo '</div>';
												}
											}
											echo "</div>";
										}
										if($undFamilyArray){
											echo "<div style='margin-left:5;margin-top:5;'><h3>Family Not Defined</h3></div>";
											echo "<div style='display:table;'>";
											foreach($undFamilyArray as $sciName => $usciArr){
												if(array_key_exists($usciArr['tid'],$tIdArr)){
													echo '<div id="'.$usciArr['tid'].'keyrow">';
													echo '<div style="display:table-row;">';
													echo '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'.$usciArr['tid'].'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="ffffff" onclick="" /></div>';
													echo '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
													echo "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../taxa/index.php?taxon=".$usciArr['sciname']."'>".$usciArr['sciname']."</a></i></div>";
													echo "</div>";
													echo '<div style="display:table-row;height:8px;"></div>';
													echo '</div>';
												}
											}
											echo "</div>";
										}
									?>
								</div>
							</div>
							<h3>Map Options</h3>
							<div>
								<?php
								if($coordExist==true){
									?>
									<div style="border:1px black solid;margin-top:10px;padding:5px;" >
										<b>Clustering</b>
										<div style="margin-top:8px;">
											<div style="float:left;">
												Grid Size: <input name="gridsize" id="gridsize" data-role="none" type="text" value="<?php echo $gridSize; ?>" style="width:50px;" />
											</div>
											<div style="padding-left:8px;float:left;">
												Min. Cluster Size: <input name="minclustersize" id="minclustersize" data-role="none" type="text" value="<?php echo $minClusterSize; ?>" style="width:50px;" />
											</div>
										</div>
										<div style="clear:both;margin-top:8px;">
											Turn Off Clustering: <input data-role="none" type="checkbox" id="clusteroff" name="clusteroff" value='1' <?php echo ($clusterOff==1?'checked':'') ?>/>
										</div>
									</div>
									<div style="clear:both;">
										<div style="float:right;margin-top:10px;">
											<button data-role="none" id="refreshCluster" name="refreshCluster" onclick="refreshClustering();" >Refresh Map</button>
										</div>
									</div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<a href="#demo-links" style="position:absolute;bottom:0;right:0;margin-right:0px;margin-bottom:0px;padding-top:3px;padding-bottom:3px;z-index:10;" data-rel="close" data-role="button" data-theme="a" data-icon="delete" data-inline="true">Close</a>
			</div><!-- /content wrapper for padding -->
		</div><!-- /defaultpanel -->
	</div>
	<div id='map' style='width:100%;height:100%;'></div>
</body>
</html>