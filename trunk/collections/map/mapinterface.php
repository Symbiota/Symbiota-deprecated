<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonProfileMap.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$taxonValue = array_key_exists('taxon',$_REQUEST)?$_REQUEST['taxon']:0;
$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$mapType = array_key_exists('maptype',$_REQUEST)?$_REQUEST['maptype']:0;
$taxonAuthorityId = array_key_exists('taxonfilter',$_REQUEST)?$_REQUEST['taxonfilter']:1;
$stArrJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';
$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:1; 

$mapManager = new MapInterfaceManager();
$queryShape = '';
$showTaxaBut = 1;

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
$previousCriteria = Array();
$groupCnt = 1;
$grpCntArr = Array();
$jsonStArr = '';
$gridSize = 60;
$minClusterSize = 10;
$clusterOff = "n";
$recLimit = "5000";
$mysqlVersion = $mapManager->getMysqlVersion();
if($mysqlVersion){
	if($mysqlVersion["db"] == 'MariaDB'){
		$spatial = true;
	}
	elseif($mysqlVersion["db"] == 'mysql'){
		$mysqlVerNums = explode(".", $mysqlVersion["ver"]);
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
}

if($stArrJson){
	$stArrJson = str_replace( "'", '"',$stArrJson);
	$stArr = json_decode($stArrJson, true);
}

if($_REQUEST || $stArr){
	if($_REQUEST){
		$previousCriteria = $_REQUEST;
	}
	if($stArr){
		$previousCriteria = $stArr;
	}
}

if($previousCriteria){
	$gridSize = $previousCriteria['gridSizeSetting'];
	$minClusterSize = $previousCriteria['minClusterSetting'];
	$clusterOff = $previousCriteria['clusterSwitch'];
	$recLimit = (($previousCriteria['recordlimit']&&is_numeric($previousCriteria['recordlimit']))?$previousCriteria['recordlimit']:5000);
}

$dbArr = Array();
if(array_key_exists('db',$_REQUEST)){
	$dbArr = $previousCriteria["db"];
}
elseif(array_key_exists('db',$previousCriteria)){
	$dbArr = explode(';',$previousCriteria["db"]);
}

if((array_key_exists("upperlat",$previousCriteria)) || (array_key_exists("pointlat",$previousCriteria)) || (array_key_exists("poly_array",$previousCriteria))){
	$queryShape = $mapManager->createShape($previousCriteria);
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
elseif($stArr || ($mapType && $mapType == 'occquery')){
	if($stArr){
		$mapManager->setSearchTermsArr($stArr);
	}
	$mapWhere = $mapManager->getSqlWhere();
	if(!$stArr){
		$stArr = $mapManager->getSearchTermsArr();
	}
	$fullCollList = $mapManager->getFullCollArr($stArr);
	$coordArr = $mapManager->getCollGeoCoords(0,false,$mapWhere,$recLimit);
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
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $defaultTitle; ?> - Map Interface</title>
	<link type="text/css" href="../../css/base.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" />
	<link type="text/css" href="../../css/main.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery.mobile-1.4.0.min.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery.symbiota.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui_accordian.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<style type="text/css">
		#tabs1 a,#tabs2 a,#tabs3 a{
			outline-color: transparent;
			font-size: 12px;
			font-weight: normal;
		}
	</style>
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.mobile-1.4.0.min.js"></script>
	<script type="text/javascript" src="../../js/jquery-1.9.1.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui-1.10.4.js"></script>
	<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=drawing"></script>
	<script type="text/javascript" src="../../js/jscolor/jscolor.js"></script>
	<script type="text/javascript">
		$(function() {
			var winHeight = $(window).height();
			winHeight = winHeight + "px";
			document.getElementById('mapinterface').style.height = winHeight;
			
			$("#accordion").accordion({
				collapsible: true,
				heightStyle: "fill"
				<?php
				if($coordExist){
					echo ",active: 1";
				}
				?>
			});
		});
		
		var starr = JSON.stringify(<?php echo $jsonStArr; ?>);
		
		<?php
		if($coordExist){
			echo "var coords = true;";
		}
		else{
			echo "var coords = false;";
		}
		?>
	</script>
	<script type="text/javascript" src="../../js/symb/collections.mapinterface.js"></script>
	<script type="text/javascript" src="../../js/symb/markerclusterer.js?ver=260913"></script>
	<script type="text/javascript" src="../../js/symb/oms.min.js"></script>
	<script type="text/javascript" src="../../js/symb/keydragzoom.js"></script>
	<script type="text/javascript" src="../../js/symb/infobox.js"></script>
    <script type="text/javascript">
		<?php
		if($symbUid){
			?>
			$(window).bind("load", function() {
			   loadRecordsetList('<?php echo $symbUid; ?>',"loadlist");
			});
			
			var uid = <?php echo $symbUid; ?>;
			<?php
		}
		?>
		
		var map;
		var useLLDecimal = true;
	    var infoWins = [];
	    var puWin;
        var minLng = 180;
        var minLat = 90;
        var maxLng = -180;
        var maxLat = -90;
		var markers = [];
		var dsmarkers = [];
		var dsoccids = [];
		var selections = [];
		var dsselections = [];
		var selectedds = '';
		var selecteddsrole = '';
		var marker;
		var drawingManager = null;
		var oms;
		var dsoms;
		var selectedShape = null;
		var gotCoords = true;
		var mapSymbol = 'coll';
		var selected = false;
		var deselected = false;
		var positionFound = false;
		
		function getCoords(){
			if (navigator.geolocation){
				var options = {
					timeout: 4000,
					maximumAge: 0
				};
				setTimeout(function(){
					if(!positionFound){
						gotCoords = false;
						initialize();
					}
				},4005)
				navigator.geolocation.getCurrentPosition(
					initialize, 
					function (error) { 
						if (error.code){
							gotCoords = false;
							initialize();
						}
					},
					options
				);
			}
			else{
				gotCoords = false;
				initialize();
			}
		}
		
		function getMarker(newLat, newLng, newTitle, color, newIcon, type, tid, occid, clid){
			var m = new google.maps.Marker({
				position: new google.maps.LatLng(newLat, newLng),
				text: newTitle,
				<?php
				if($clusterOff=="y"){
					?>
					map: map,
					<?php
				}
				?>
				icon: newIcon,
				selected: false,
				color: color,
				customInfo: type,
				taxatid: tid,
				occid: occid,
				clid: clid
			});
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
				positionFound = true;
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
			
			dsoms = new OverlappingMarkerSpiderfier(map);
			
			map.enableKeyDragZoom({
				visualEnabled: true,
				visualPosition: google.maps.ControlPosition.LEFT,
				visualPositionOffset: new google.maps.Size(35, 0),
				visualPositionIndex: null,
				visualSprite: "../../images/dragzoom_btn.png",
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
				occid = marker.occid;
				chbox = 'ch'+occid;
				if(selections.indexOf(occid) > -1){
					var index = selections.indexOf(occid);
					if (index > -1) {
						selections.splice(index, 1);
					}
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = false;
						document.getElementById("selectallcheck").checked = false;
					}
					removeSelectionRecord(occid);
					adjustSelectionsTab();
					deselectMarker(marker);
				}
				else{
					selections.push(occid);
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = true;
						var f = document.getElementById("selectform");
						var boxesChecked = true;
						for(var i=0;i<f.length;i++){
							if(f.elements[i].name == "occid[]"){ 
								if(f.elements[i].checked == false){
									boxesChecked = false;
								}
							}
						}
						if(boxesChecked == true){
							document.getElementById("selectallcheck").checked = true;
						}
					}
					adjustSelectionsTab();
					selectMarker(marker);
				}
			});
			
			dsoms.addListener('click', function(marker, event) {
				occid = marker.occid;
				chbox = 'dsch'+occid;
				if(dsselections.indexOf(occid) > -1){
					var index = dsselections.indexOf(occid);
					if (index > -1) {
						dsselections.splice(index, 1);
					}
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = false;
						document.getElementById("dsselectallcheck").checked = false;
					}
					deselectDsMarker(marker);
				}
				else{
					dsselections.push(occid);
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = true;
						var f = document.getElementById("dsselectform");
						var boxesChecked = true;
						for(var i=0;i<f.length;i++){
							if(f.elements[i].name == "occid[]"){ 
								if(f.elements[i].checked == false){
									boxesChecked = false;
								}
							}
						}
						if(boxesChecked == true){
							document.getElementById("dsselectallcheck").checked = true;
						}
					}
					selectDsMarker(marker);
				}
			});
			
			oms.addListener('spiderfy', function(markers) {
				closeAllInfoWins();
			});
			
			dsoms.addListener('spiderfy', function(markers) {
				closeAllInfoWins();
			});
			
			var deselecteddspoints = document.getElementById("deselecteddspoints");
			google.maps.event.addDomListener(
				deselecteddspoints, 
				'change', 
				function(){ 
					deselected = false;
					var deselectedpoint = Number(deselecteddspoints.value);
					while (deselected == false) {
						if (dsmarkers) {
							for (i in dsmarkers) {
								if(dsmarkers[i].occid==deselectedpoint){
									var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#000000",strokeWeight:2};
									dsmarkers[i].setIcon(markerIcon);
									dsmarkers[i].selected = false;
									deselected = true;
								}
							}
						}
					}
					var index = dsselections.indexOf(deselectedpoint);
					dsselections.splice(index, 1);
				}
			);
			
			var selecteddspoints = document.getElementById("selecteddspoints");
			google.maps.event.addDomListener(
				selecteddspoints, 
				'change', 
				function(){ 
					selected = false;
					var selectedpoint = Number(selecteddspoints.value);
					while (selected == false) {
						if (dsmarkers) {
							for (i in dsmarkers) {
								if(dsmarkers[i].occid==selectedpoint){
									var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#10D8E6",strokeWeight:2};
									dsmarkers[i].setIcon(markerIcon);
									dsmarkers[i].selected = true;
									selected = true;
								}
							}
						}
					}
					if(dsselections.indexOf(selectedpoint) < 0){
						dsselections.push(selectedpoint);
					}
				}
			);
			
			<?php
			if($coordExist==true){
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
						$keyLabel = $sciName;
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
							if($clusterOff=="n"){
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
						
						function findSelection<?php echo $groupCnt; ?>(id,dir){ 
							if (markers<?php echo $groupCnt; ?>) {
								for (i in markers<?php echo $groupCnt; ?>) {
									if(markers<?php echo $groupCnt; ?>[i].occid==id){
										if(markers<?php echo $groupCnt; ?>[i].customInfo=='obs'){
											var markerColor = '#'+markers<?php echo $groupCnt; ?>[i].color;
											if(dir == 'select'){
												var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
											}
											else if(dir == 'deselect'){
												var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
											}
											markers<?php echo $groupCnt; ?>[i].setIcon(markerIcon);
										}
										if(markers<?php echo $groupCnt; ?>[i].customInfo=='spec'){
											var markerColor = '#'+markers<?php echo $groupCnt; ?>[i].color;
											if(dir == 'select'){
												var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
											}
											else if(dir == 'deselect'){
												var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
											}
											markers<?php echo $groupCnt; ?>[i].setIcon(markerIcon);
										}
										if(dir == 'select'){
											markers<?php echo $groupCnt; ?>[i].selected = true;
											selected = true;
										}
										else if(dir == 'deselect'){
											markers<?php echo $groupCnt; ?>[i].selected = false;
											deselected = true;
										}
										return;
									}
								}
							}
						}
						
						<?php
						if($clusterOff=="n"){
							?>
							function findGrpClusterSelection<?php echo $groupCnt; ?>(id){
								if(markerCluster<?php echo $groupCnt; ?>){
									var clusters = markerCluster<?php echo $groupCnt; ?>.getClusters();
									for( var i=0, l=clusters.length; i<l; i++ ){
										var selCluster = false;
										var oldHtml = clusters[i].clusterIcon_.div_.innerHTML;
										for( var j=0, le=clusters[i].markers_.length; j<le; j++ ){
											if(clusters[i].markers_[j].selected==true){
												selCluster = true;
											}
										}
										if(selCluster == true){
											var newHtml = oldHtml.replace('></circle>', ' stroke="#10D8E6" stroke-width="3px"></circle>');
										}
										if(selCluster == false){
											var newHtml = oldHtml.replace(' stroke="#10D8E6" stroke-width="3px"></circle>', '></circle>');
										}
										clusters[i].clusterIcon_.div_.innerHTML = newHtml;
									}
								}
							}
							<?php
						}
						?>
						
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
						$displayStr = $spArr['identifier'];
						if(in_array($spArr['collid'],$genObs)){
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
						echo 'var m'.$occId.' = getMarker('.$spArr['latLngStr'].',"'.$displayStr.'","'.$iconColor.'",markerIcon,"'.$type.'","'.($spArr['tidinterpreted']?$spArr['tidinterpreted']:0).'",'.$occId.','.($clid?$clid:'0').');',"\n";
						?>
						google.maps.event.addListener(
							m<?php echo $occId; ?>, 
							'mouseover', 
							function(){ 
								occid = m<?php echo $occId; ?>.occid;
								clid = m<?php echo $occId; ?>.clid;
								markerLabel = m<?php echo $occId; ?>.text;
								boxPosition = m<?php echo $occId; ?>.getPosition();
								boxText = '<div>'+markerLabel+'<br /><a href="#" onclick="closeAllInfoWins();openIndPU('+occid+','+clid+');return false;"><span style="color:blue;">See Details</span></a></div>';
								var myOptions<?php echo $occId; ?> = {
									content: boxText,
									boxStyle: {
										border: "1px solid black",
										background: "#ffffff",
										textAlign: "center",
										padding: "2px",
										fontSize: "12px"
									},
									disableAutoPan: true,
									pixelOffset: new google.maps.Size(0,10),
									position: boxPosition,
									isHidden: false,
									closeBoxURL: "",
									pane: "floatPane",
									enableEventPropagation: false
								};
								
								mouseoverTimeout<?php echo $occId; ?> = setTimeout(
									function(){
										ibLabel<?php echo $occId; ?> = new InfoBox(myOptions<?php echo $occId; ?>);
										ibLabel<?php echo $occId; ?>.open(map); 
									},1000
								);
							}
						);
						
						google.maps.event.addListener(
							m<?php echo $occId; ?>, 
							'mouseout', 
							function(){ 
								if(mouseoverTimeout<?php echo $occId; ?>){   
									clearTimeout(mouseoverTimeout<?php echo $occId; ?>); 
									mouseoverTimeout<?php echo $occId; ?> = null; 
								}
								mouseoutTimeout<?php echo $occId; ?> = setTimeout(
									function(){
										if(ibLabel<?php echo $occId; ?>){
											ibLabel<?php echo $occId; ?>.close();
										}
									},3000
								);
							}
						);
						<?php
						echo 'oms.addMarker(m'.$occId.');',"\n";
						echo 'markers'.$groupCnt.'.push(m'.$occId.');',"\n";
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
					if($clusterOff=="n"){
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
								var newMarkerColor = '#'+newColor;
								for (i in markers<?php echo $gCnt; ?>) {
									if(markers<?php echo $gCnt; ?>[i].taxatid == '<?php echo $tId; ?>'){
										if(markers<?php echo $gCnt; ?>[i].customInfo=='obs'){
											if(markers<?php echo $gCnt; ?>[i].selected==true){
												var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
											}
											else{
												var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
											}
											markers<?php echo $gCnt; ?>[i].color = newColor;
											markers<?php echo $gCnt; ?>[i].setIcon(markerIcon);
										}
										if(markers<?php echo $gCnt; ?>[i].customInfo=='spec'){
											if(markers<?php echo $gCnt; ?>[i].selected==true){
												var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
											}
											else{
												var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
											}
											markers<?php echo $gCnt; ?>[i].color = newColor;
											markers<?php echo $gCnt; ?>[i].setIcon(markerIcon);
										}
										newMarkers<?php echo $tId; ?>.push(markers<?php echo $gCnt; ?>[i]);
										<?php
										if($clusterOff=="n"){
											?>
											markerCluster<?php echo $gCnt; ?>.removeMarker(markers<?php echo $gCnt; ?>[i]);
											<?php
										}
										?>
									}
								}
							}
							<?php
						}
						if($clusterOff=="n"){
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
				
				if($clusterOff=="n"){
					?>
					function findTaxClusterSelection(id){
						<?php
						foreach($tIdArr as $tId => $gcArr){
							?>
							if(markerCluster<?php echo $tId; ?>){
								var clusters = markerCluster<?php echo $tId; ?>.getClusters();
								for( var i=0, l=clusters.length; i<l; i++ ){
									var selCluster = false;
									var oldHtml = clusters[i].clusterIcon_.div_.innerHTML;
									for( var j=0, le=clusters[i].markers_.length; j<le; j++ ){
										if(clusters[i].markers_[j].selected==true){
											selCluster = true;
										}
									}
									if(selCluster == true){
										var newHtml = oldHtml.replace('></circle>', ' stroke="#10D8E6" stroke-width="3px"></circle>');
									}
									if(selCluster == false){
										var newHtml = oldHtml.replace(' stroke="#10D8E6" stroke-width="3px"></circle>', '></circle>');
									}
									clusters[i].clusterIcon_.div_.innerHTML = newHtml;
								}
							}
							<?php
						}
						?>
					}
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
						document.getElementById("taxaColor<?php echo $tId; ?>").color.fromString("E69E67");
						<?php
					}
					?>
				}
				
				function resetMainSymbology(){ 
					<?php
					foreach($grpCntArr as $gcnt){
						?>
						changeMainKey<?php echo $gcnt; ?>("E69E67");
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
						resetMainSymbology();
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
				
				var selectedpoints = document.getElementById("selectedpoints");
				google.maps.event.addDomListener(
					selectedpoints, 
					'change', 
					function(){ 
						selected = false;
						var selectedpoint = Number(selectedpoints.value);
						while (selected == false) {
							<?php
							foreach($grpCntArr as $gcnt){
								?>
								findSelection<?php echo $gcnt; ?>(selectedpoint,'select');
								<?php
								if($clusterOff=="n"){
									?>
									findGrpClusterSelection<?php echo $gcnt; ?>(selectedpoint);
									<?php
								}
							}
							if($clusterOff=="n"){
								?>
								findTaxClusterSelection(selectedpoint);
								<?php
							}
							?>
						}
						if(selections.indexOf(selectedpoint) < 0){
							selections.push(selectedpoint);
						}
						adjustSelectionsTab();
					}
				);
				
				var deselectedpoints = document.getElementById("deselectedpoints");
				google.maps.event.addDomListener(
					deselectedpoints, 
					'change', 
					function(){ 
						deselected = false;
						var deselectedpoint = Number(deselectedpoints.value);
						while (deselected == false) {
							<?php
							foreach($grpCntArr as $gcnt){
								?>
								findSelection<?php echo $gcnt; ?>(deselectedpoint,'deselect');
								<?php
								if($clusterOff=="n"){
									?>
									findGrpClusterSelection<?php echo $gcnt; ?>(deselectedpoint);
									<?php
								}
							}
							if($clusterOff=="n"){
								?>
								findTaxClusterSelection(deselectedpoint);
								<?php
							}
							?>
						}
						var index = selections.indexOf(deselectedpoint);
						selections.splice(index, 1);
						adjustSelectionsTab();
					}
				);
				
				var clearselections = document.getElementById("clearselectionsbut");
				google.maps.event.addDomListener(
					clearselections, 
					'click', 
					function(){ 
						for (var i = 0; i < selections.length; i++) {
							occid = Number(selections[i]);
							<?php
							foreach($grpCntArr as $gcnt){
								?>
								findSelection<?php echo $gcnt; ?>(occid,'deselect');
								<?php
								if($clusterOff=="n"){
									?>
									findGrpClusterSelection<?php echo $gcnt; ?>(occid);
									<?php
								}
							}
							if($clusterOff=="n"){
								?>
								findTaxClusterSelection(occid);
								<?php
							}
							?>
						}
						selections.length = 0;
						adjustSelectionsTab();
						document.getElementById("selectiontbody").innerHTML = '';
					}
				);
				
				var zoomtoselections = document.getElementById("zoomtoselectionsbut");
				google.maps.event.addDomListener(
					zoomtoselections, 
					'click', 
					function(){ 
						var selectZoomBounds = new google.maps.LatLngBounds();
						<?php
						foreach($grpCntArr as $gcnt){
							?>
							for (var i=0; i < selections.length; i++) {
								occid = Number(selections[i]);
								if (markers<?php echo $gcnt; ?>) {
									for (j in markers<?php echo $gcnt; ?>) {
										if(markers<?php echo $gcnt; ?>[j].occid==occid){
											var markerPos = markers<?php echo $gcnt; ?>[j].getPosition();
											selectZoomBounds.extend(markerPos);
										}
									}
								}
							}
							<?php
						}
						?>
						map.fitBounds(selectZoomBounds);
						map.panToBounds(selectZoomBounds);
					}
				);
				<?php
				$latDiff = $maxLat - $minLat;
				if($latDiff >= 100){
					$minLat = -60;
					$maxLat = 60;
				}
				?>
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
				checkHighResult(<?php echo $coordArr; ?>);
				<?php
			}
			?>
		}
		
		google.maps.event.addDomListener(window, 'load', getCoords);
	</script>
</head> 
<body style='width:100%;'>
	<div data-role="page" id="page1">
		<div role="main" class="ui-content" style="height:400px;">
			<a href="#defaultpanel" style="position:absolute;top:0;left:0;margin-top:0px;z-index:10;padding-top:3px;padding-bottom:3px;text-decoration:none;" data-role="button" data-inline="true" data-icon="bars">Open</a>
		</div>
		<!-- defaultpanel -->
		<div data-role="panel" data-dismissible="false" class="overflow: hidden;" style="width:380px;" id="defaultpanel" data-position="left" data-display="overlay" >
			<div class="panel-content">
				<div id="mapinterface">
					<div id="accordion" style="" >
						<?php //echo "MySQL Version: ".$mysqlVersion; ?>
						<?php //echo $spatial?"yes":"no"; ?>
						<?php //echo "Request: ".json_encode($_REQUEST); ?>
						<?php //echo "starr: ".json_encode($stArr); ?>
						<?php //echo "mapWhere: ".$mapWhere; ?>
						<?php //echo "coordArr: ".json_encode($coordArr); ?>
						<?php //echo "clusteringOff: ".$clusterOff; ?>
						<?php //echo "coordArr: ".$coordArr; ?>
						<?php //echo "tIdArr: ".json_encode($tIdArr); ?>
						<?php //echo "minLat:".$minLat."maxLat:".$maxLat."minLng:".$minLng."maxLng:".$maxLng; ?>
						<h3>Search Criteria and Options</h3>
						<div id="tabs1" style="width:379px;padding:0px;">
							<form name="mapsearchform" id="mapsearchform" data-ajax="false" action="mapinterface.php" method="get" onsubmit="return verifyCollForm(this);return checkForm();">
								<ul>
									<li><a href="#searchcriteria"><span>Criteria</span></a></li>
									<li><a href="#searchcollections"><span>Collections</span></a></li>
									<li><a href="#mapoptions"><span>Map Options</span></a></li>
								</ul>
								<div id="searchcollections" style="">
									<div class="mapinterface">
										<div>
											<h1 style="margin:0px 0px 8px 0px;font-size:15px;">Collections to be Searched</h1>
										</div>
										<?php 
										if($specArr || $obsArr){
											?>
											<div id="specobsdiv">
												<div style="margin:0px 0px 10px 20px;">
													<input id="dballcb" data-role="none" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?php echo (((array_key_exists("db",$previousCriteria)&&in_array("all",$dbArr))||!$dbArr)?'checked':'') ?> />
													Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
												</div>
												<?php 
												if($specArr){
													$mapManager->outputFullMapCollArr($dbArr,$specArr);
												}
												if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>'; 
												if($obsArr){
													$mapManager->outputFullMapCollArr($dbArr,$obsArr);
												}
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
											<input data-role="none" type="text" id="recordlimit" style="width:75px;" name="recordlimit" value="<?php echo ($recLimit?$recLimit:""); ?>" title="Maximum record amount returned from search." onchange="return checkRecordLimit(this.form);" />
										</div>
										<div style="float:right;">
											<input type="hidden" name="maptype" value="occquery" />
											<input type="hidden" id="selectedpoints" value="" />
											<input type="hidden" id="deselectedpoints" value="" />
											<input type="hidden" id="selecteddspoints" value="" />
											<input type="hidden" id="deselecteddspoints" value="" />
											<input type="hidden" id="clearselections" value="0" />
											<input type="hidden" id="gridSizeSetting" name="gridSizeSetting" value="<?php echo (array_key_exists("gridSizeSetting",$previousCriteria)?$previousCriteria["gridSizeSetting"]:"60"); ?>" />
											<input type="hidden" id="minClusterSetting" name="minClusterSetting" value="<?php echo (array_key_exists("minClusterSetting",$previousCriteria)?$previousCriteria["minClusterSetting"]:"10"); ?>" />
											<input type="hidden" id="clusterSwitch" name="clusterSwitch" value="<?php echo (array_key_exists("clusterSwitch",$previousCriteria)?$previousCriteria["clusterSwitch"]:"n"); ?>" />
											<input type="hidden" id="pointlat" name="pointlat" value='<?php echo (array_key_exists("pointlat",$previousCriteria)?$previousCriteria["pointlat"]:""); ?>' />
											<input type="hidden" id="pointlong" name="pointlong" value='<?php echo (array_key_exists("pointlong",$previousCriteria)?$previousCriteria["pointlong"]:""); ?>' />
											<input type="hidden" id="radius" name="radius" value='<?php echo (array_key_exists("radius",$previousCriteria)?$previousCriteria["radius"]:""); ?>' />
											<input type="hidden" id="upperlat" name="upperlat" value='<?php echo (array_key_exists("upperlat",$previousCriteria)?$previousCriteria["upperlat"]:""); ?>' />
											<input type="hidden" id="rightlong" name="rightlong" value='<?php echo (array_key_exists("rightlong",$previousCriteria)?$previousCriteria["rightlong"]:""); ?>' />
											<input type="hidden" id="bottomlat" name="bottomlat" value='<?php echo (array_key_exists("bottomlat",$previousCriteria)?$previousCriteria["bottomlat"]:""); ?>' />
											<input type="hidden" id="leftlong" name="leftlong" value='<?php echo (array_key_exists("leftlong",$previousCriteria)?$previousCriteria["leftlong"]:""); ?>' />
											<input type="hidden" id="poly_array" name="poly_array" value='<?php echo (array_key_exists("poly_array",$previousCriteria)?$previousCriteria["poly_array"]:""); ?>' />
											<button data-role="none" type=button id="resetform" name="resetform" onclick='window.open("mapinterface.php", "_self");' >Reset</button>
											<button data-role="none" id="display2" name="display2" onclick='submitMapForm(this.form);' >Search</button>
										</div>
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<div>
										<span style=""><input data-role="none" type='checkbox' name='thes' value='1' <?php if(array_key_exists("thes",$previousCriteria) && $previousCriteria["thes"]) echo "CHECKED"; ?> >Include Synonyms</span>
									</div>
									<div id="taxonSearch0">
										<div id="taxa_autocomplete" >
											<div style="margin-top:5px;">
												<select data-role="none" id="taxontype" name="type">
													<option id='familysciname' value='1' <?php if(array_key_exists("type",$previousCriteria) && $previousCriteria["type"] == "1") echo "SELECTED"; ?> >Family or Scientific Name</option>
													<option id='family' value='2' <?php if(array_key_exists("type",$previousCriteria) && $previousCriteria["type"] == "2") echo "SELECTED"; ?> >Family only</option>
													<option id='sciname' value='3' <?php if(array_key_exists("type",$previousCriteria) && $previousCriteria["type"] == "3") echo "SELECTED"; ?> >Scientific Name only</option>
													<option id='highertaxon' value='4' <?php if(array_key_exists("type",$previousCriteria) && $previousCriteria["type"] == "4") echo "SELECTED"; ?> >Higher Taxonomy</option>
													<option id='commonname' value='5' <?php if(array_key_exists("type",$previousCriteria) && $previousCriteria["type"] == "5") echo "SELECTED"; ?> >Common Name</option>
												</select>
											</div>
											<div style="margin-top:5px;">
												Taxa: <input data-role="none" id="taxa" type="text" style="width:275px;" name="taxa" value="<?php if(array_key_exists("taxa",$previousCriteria)) echo $previousCriteria["taxa"]; ?>" title="Separate multiple taxa w/ commas" />
											</div>
										</div>
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<!-- <div id="checklistSearch0">
										<div id="checklist_autocomplete" >
											Checklist:
											<input data-role="none" type="text" id="checklistname" style="width:275px;" name="checklistname" value="<?php //if(array_key_exists("checklistname",$previousCriteria)) echo $previousCriteria["checklistname"]; ?>" title="" />
											<input id="clid" name="clid" type="hidden"  value="<?php //if(array_key_exists("clid",$previousCriteria)) echo $previousCriteria["clid"]; ?>" />
										</div>
									</div>
									<div><hr></div> -->
									<div>
										Country: <input data-role="none" type="text" id="country" style="width:225px;" name="country" value="<?php if(array_key_exists("country",$previousCriteria)) echo $previousCriteria["country"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div style="margin-top:5px;">
										State/Province: <input data-role="none" type="text" id="state" style="width:150px;" name="state" value="<?php if(array_key_exists("state",$previousCriteria)) echo $previousCriteria["state"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div style="margin-top:5px;">
										County: <input data-role="none" type="text" id="county" style="width:225px;"  name="county" value="<?php if(array_key_exists("county",$previousCriteria)) echo $previousCriteria["county"]; ?>" title="Separate multiple terms w/ commas" />
									</div>
									<div style="margin-top:5px;">
										Locality: <input data-role="none" type="text" id="locality" style="width:225px;" name="local" value="<?php if(array_key_exists("local",$previousCriteria)) echo $previousCriteria["local"]; ?>" />
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<div id="shapecriteria">
										<div id="noshapecriteria" style="display:<?php echo ((!$previousCriteria || ((!$previousCriteria['poly_array']) && (!$previousCriteria['upperlat'])))?'block':'none'); ?>;">
											<div id="geocriteria" style="display:<?php echo ((!$previousCriteria || ((!$previousCriteria['poly_array']) && (!$previousCriteria['distFromMe']) && (!$previousCriteria['pointlat']) && (!$previousCriteria['upperlat'])))?'block':'none'); ?>;">
												<div>
													Use the shape tools on the map to select occurrences within a given shape.
												</div>
											</div>
											<div id="distancegeocriteria" style="display:<?php echo ((!$previousCriteria || ($previousCriteria && array_key_exists('distFromMe',$previousCriteria) && $previousCriteria['distFromMe']))?'block':'none'); ?>;">
												<div>
													Within <input data-role="none" type="text" id="distFromMe" style="width:40px;" name="distFromMe" value="<?php if(array_key_exists('distFromMe',$previousCriteria)) echo $previousCriteria['distFromMe']; ?>" /> miles from me, or
													use the shape tools on the map to select occurrences within a given shape.
												</div>
											</div>
										</div>
										<div id="polygeocriteria" style="display:<?php echo (($previousCriteria && $previousCriteria['poly_array'])?'block':'none'); ?>;">
											<div>
												Within the selected polygon.
											</div>
										</div>
										<div id="circlegeocriteria" style="display:<?php echo (($previousCriteria && $previousCriteria['pointlat'] && !$previousCriteria['distFromMe'])?'block':'none'); ?>;">
											<div>
												Within the selected circle.
											</div>
										</div>
										<div id="rectgeocriteria" style="display:<?php echo (($previousCriteria && $previousCriteria['upperlat'])?'block':'none'); ?>;">
											<div>
												Within the selected rectangle.
											</div>
										</div>
										<div id="deleteshapediv" style="margin-top:5px;display:<?php echo (($previousCriteria && ($previousCriteria['pointlat'] || $previousCriteria['upperlat'] || $previousCriteria['poly_array']))?'block':'none'); ?>;">
											<button data-role="none" type=button id="delete-button">Delete Selected Shape</button>
										</div>
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<div>
										Collector's Last Name: 
										<input data-role="none" type="text" id="collector" style="width:125px;" name="collector" value="<?php if(array_key_exists("collector",$previousCriteria)) echo $previousCriteria["collector"]; ?>" title="" />
									</div>
									<div style="margin-top:5px;">
										Collector's Number: 
										<input data-role="none" type="text" id="collnum" style="width:125px;" name="collnum" value="<?php if(array_key_exists("collnum",$previousCriteria)) echo $previousCriteria["collnum"]; ?>" title="Separate multiple terms by commas and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750" />
									</div>
									<div style="margin-top:5px;">
										Collection Date: 
										<input data-role="none" type="text" id="eventdate1" style="width:80px;" name="eventdate1" style="width:100px;" value="<?php if(array_key_exists("eventdate1",$previousCriteria)) echo $previousCriteria["eventdate1"]; ?>" title="Single date or start date of range" /> - 
										<input data-role="none" type="text" id="eventdate2" style="width:80px;" name="eventdate2" style="width:100px;" value="<?php if(array_key_exists("eventdate2",$previousCriteria)) echo $previousCriteria["eventdate2"]; ?>" title="End date of range; leave blank if searching for single date" />
									</div>
									<div style="margin:10 0 10 0;"><hr></div>
									<div>
										Catalog Number:
										<input data-role="none" type="text" id="catnum" style="width:150px;" name="catnum" value="<?php if(array_key_exists("catnum",$previousCriteria)) echo $previousCriteria["catnum"]; ?>" title="" />
									</div>
									<div><hr></div>
									<input type="hidden" name="reset" value="1" />
								</div>
							</form>
							<div id="mapoptions" style="">
								<div style="border:1px black solid;margin-top:10px;padding:5px;" >
									<b>Clustering</b>
									<div style="margin-top:8px;">
										<div style="float:left;">
											Grid Size: <input name="gridsize" id="gridsize" data-role="none" type="text" value="<?php echo $gridSize; ?>" style="width:50px;" onchange="setClustering();" />
										</div>
										<div style="padding-left:8px;float:left;">
											Min. Cluster Size: <input name="minclustersize" id="minclustersize" data-role="none" type="text" value="<?php echo $minClusterSize; ?>" style="width:50px;" onchange="setClustering();" />
										</div>
									</div>
									<div style="clear:both;margin-top:8px;">
										Turn Off Clustering: <input data-role="none" type="checkbox" id="clusteroff" name="clusteroff" value='1' <?php echo ($clusterOff=="y"?'checked':'') ?> onchange="setClustering();"/>
									</div>
								</div>
								<?php
								if($coordExist==true){
									?>
									<div style="clear:both;">
										<div style="float:right;margin-top:10px;">
											<button data-role="none" id="refreshCluster" name="refreshCluster" onclick="refreshClustering();" >Refresh Map</button>
										</div>
									</div>
									<?php
								}
								?>
							</div>
							<form style="display:none;" name="csvcontrolform" id="csvcontrolform" action="csvdownloadhandler.php" method="post" onsubmit="">
								<input data-role="none" name="selectionscsv" id="selectionscsv" type="hidden" value="" />
								<input data-role="none" name="starrcsv" id="starrcsv" type="hidden" value="" />
								<input data-role="none" name="typecsv" id="typecsv" type="hidden" value="" />
								<input data-role="none" name="schema" id="schemacsv" type="hidden" value="" />
								<input data-role="none" name="identifications" id="identificationscsv" type="hidden" value="" />
								<input data-role="none" name="images" id="imagescsv" type="hidden" value="" />
								<input data-role="none" name="format" id="formatcsv" type="hidden" value="" />
								<input data-role="none" name="cset" id="csetcsv" type="hidden" value="" />
								<input data-role="none" name="zip" id="zipcsv" type="hidden" value="" />
								<input data-role="none" name="csvreclimit" id="csvreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
							</form>
						</div>
						<?php
						if($coordExist==true){
							?>
							<h3>Records and Taxa</h3>
							<div id="tabs2" style="width:379px;padding:0px;">
								<ul>
									<li><a href='#symbology'><span>Collections</span></a></li>
									<li><a href='#queryrecordsdiv' onclick='changeRecordPage(starr,1);'><span>Records</span></a></li>
									<li><a href='#maptaxalist'><span>Taxa List</span></a></li>
									<li style="display:none;" id="selectionstab" ><a href='#selectionslist'><span>Selections</span></a></li>
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
										<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;' >
											<div>
												<button data-role="none" id="symbolizeReset1" name="symbolizeReset1" onclick='' >Reset Symbology</button>
											</div>
											<div style="margin-top:5px;">
												<button data-role="none" id="randomColorColl" name="randomColorColl" onclick='' >Auto Color</button>
											</div>
										</div>
									</div>
									<div style="margin:5 0 5 0;clear:both;"><hr /></div>
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
								</div>
								<div id="queryrecordsdiv" style="">
									<div style="height:25px;margin-top:-5px;">
										<div>
											<div style="float:left;">
												<button data-role="none" id="fullquerycsvbut" onclick='openCsvOptions("fullquery");' >Download CSV file</button>
											</div>
											<div style="float:right;">
												<form name="fullquerykmlform" id="fullquerykmlform" action="kmlmanager.php" method="post" style="margin-bottom:0px;" onsubmit="">
													<input data-role="none" name="selectionskml" id="selectionskml" type="hidden" value="" />
													<input data-role="none" name="starrkml" id="starrselectionkml" type="hidden" value="" />
													<input data-role="none" name="kmltype" id="kmltype" type="hidden" value="fullquery" />
													<input data-role="none" name="kmlreclimit" id="kmlreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
													<button data-role="none" name="submitaction" type="button" onclick='prepSelectionKml(this.form);' >Download KML file</button>
												</form>
											</div>
										</div>
									</div>
									<div id="queryrecords" style=""></div>
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
											<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;' >
												<div>
													<button data-role="none" id="symbolizeReset2" name="symbolizeReset2" onclick='' >Reset Symbology</button>
												</div>
												<div style="margin-top:5px;">
													<button data-role="none" id="randomColorTaxa" name="randomColorTaxa" onclick='' >Auto Color</button>
												</div>
											</div>
										</div>
										<div style="margin:5 0 5 0;clear:both;"><hr /></div>
										<?php
										echo "<div style='font-weight:bold;'>Taxa Count: ".$mapManager->getChecklistTaxaCnt()."</div>";
										$undFamilyArray = Array();
										if(array_key_exists("undefined",$checklistArr)){ 
											$undFamilyArray = $checklistArr["undefined"];
											unset($checklistArr["undefined"]);
										}
										foreach($checklistArr as $family => $sciNameArr){
											$show = false;
											foreach($sciNameArr as $sciName => $cntArr){
												if(array_key_exists($cntArr['tid'],$tIdArr)){
													$show = true;
												}
											}
											if($show == true){
												echo "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>".$family."</h3></div>";
												echo "<div style='display:table;'>";
												foreach($sciNameArr as $sciName => $fsciArr){
													if(array_key_exists($fsciArr['tid'],$tIdArr)){
														echo '<div id="'.$fsciArr['tid'].'keyrow">';
														echo '<div style="display:table-row;">';
														echo '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'.$fsciArr['tid'].'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="e69e67" onclick="" /></div>';
														echo '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
														if(is_numeric($fsciArr['tid'])){
															echo "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../../taxa/index.php?taxon=".$fsciArr['sciname']."'>".$fsciArr['sciname']."</a></i></div>";
														}
														else{
															echo "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i>".$fsciArr['sciname']."</i></div>";
														}
														echo "</div>";
														echo '</div>';
													}
												}
												echo "</div>";
											}
										}
										if($undFamilyArray){
											echo "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>Family Not Defined</h3></div>";
											echo "<div style='display:table;'>";
											foreach($undFamilyArray as $sciName => $usciArr){
												if(array_key_exists($usciArr['tid'],$tIdArr)){
													echo '<div id="'.$usciArr['tid'].'keyrow">';
													echo '<div style="display:table-row;">';
													echo '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'.$usciArr['tid'].'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="e69e67" onclick="" /></div>';
													echo '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
													if(is_numeric($usciArr['tid'])){
														echo "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../../taxa/index.php?taxon=".$usciArr['sciname']."'>".$usciArr['sciname']."</a></i></div>";
													}
													else{
														echo "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i>".$usciArr['sciname']."</i></div>";
													}
													echo "</div>";
													echo '</div>';
												}
											}
											echo "</div>";
										}
									?>
								</div>
								<div id="selectionslist" style="">
									<div style="height:65px;margin-bottom:15px;">
										<div style="float:left;">
											<div>
												<button data-role="none" id="clearselectionsbut" onclick='' >Clear Selections</button>
											</div>
											<div style="margin-top:5px;">
												<button data-role="none" id="sendtogpsbut" onclick='openGarminDownloader("query");' >Send to GPS</button>
											</div>
											<div style="margin-top:5px;margin-bottom:5px;">
												<button data-role="none" id="selectioncsvbut" onclick='openCsvOptions("selection");' >Download CSV file</button>
											</div>
										</div>
										<div id="" style='margin-right:15px;float:right;' >
											<div>
												<button data-role="none" id="zoomtoselectionsbut" onclick='' >Zoom to Selections</button>
											</div>
											<div style="margin-top:5px;">
												<form name="selectionkmlform" id="selectionkmlform" action="kmlmanager.php" method="post" style="margin-bottom:0px;" onsubmit="">
													<input data-role="none" name="selectionskml" id="selectionskml" type="hidden" value="" />
													<input data-role="none" name="starrkml" id="starrselectionkml" type="hidden" value="" />
													<input data-role="none" name="kmltype" id="kmltype" type="hidden" value="selection" />
													<input data-role="none" name="kmlreclimit" id="kmlreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
													<button data-role="none" name="submitaction" type="button" onclick='prepSelectionKml(this.form);' >Download KML file</button>
												</form>
											</div>
											<div id="dsaddrecbut" style="display:none;margin-top:5px;">
												<button data-role="none" onclick='addSelectedToDs();' >Add to Dataset</button>
											</div>
										</div>
									</div>
									<div style="margin:5 0 5 0;clear:both;"><hr /></div>
									<form name="selectform" action="" method="post" onsubmit="" target="_blank">
										<table class="styledtable" style="margin-left:-15px;font-size:12px;">
											<thead>
												<tr>
													<th style="width:15px;"></th>
													<th>Catalog #</th>
													<th>Collector</th>
													<th style="width:40px;">Date</th>
													<th>Scientific Name</th>
												</tr>
											</thead>
											<tbody id="selectiontbody"></tbody>
										</table>
									</form>
								</div>
							</div>
							<?php
						}
						?>
						<h3>Datasets</h3>
						<div id="tabs3" style="width:379px;padding:0px;">
							<?php
							if($symbUid){
								?>
								<ul>
									<li><a href='#recordsetselect'><span>Dataset</span></a></li>
									<li style="display:none;" id="recordsetlisttab" ><a href='#recordslist'><span>Records</span></a></li>
								</ul>
								<div id="recordsetselect" style=""></div>
								<div id="recordslist" style="">
									<div style="height:40px;margin-bottom:15px;">
										<div style="float:left;">
											<div>
												<form name="dsselectionkmlform" id="dsselectionkmlform" action="kmlmanager.php" method="post" style="margin-bottom:0px;" onsubmit="">
													<input data-role="none" name="selectionskml" id="selectionskml" type="hidden" value="" />
													<input data-role="none" name="starrkml" id="starrselectionkml" type="hidden" value="" />
													<input data-role="none" name="kmltype" id="kmltype" type="hidden" value="dsselectionquery" />
													<input data-role="none" name="kmlreclimit" id="kmlreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
													<button data-role="none" name="submitaction" type="button" onclick='prepSelectionKml(this.form);' >Download KML file</button>
												</form>
											</div>
											<div style="margin-top:5px;">
												<button data-role="none" id="" onclick='openGarminDownloader("dataset");' >Send to GPS</button>
											</div>
										</div>
										<div id="" style='margin-right:15px;float:right;' >
											<div>
												<button data-role="none" id="" onclick='zoomToDsSelections();' >Zoom to Selections</button>
											</div>
											<div style="margin-top:5px;">
												<button data-role="none" id="dsselectionquerycsvbut" onclick='openCsvOptions("dsselectionquery");' >Download CSV file</button>
											</div>
										</div>
									</div>
									<div id="dsdeleterecbut" style="height:25px;display:none;">
										<div style="float:left;width:100%;">
											<button data-role="none" id="" onclick='deleteSelectedFromDs();' >Delete Selections</button>
										</div>
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<form name="dsselectform" id="dsselectform" action="" method="post" onsubmit="">
										<div id="recordsetcntdiv" style="float:right;padding-top:3px;"></div>
										<div style="float:left;margin-bottom:5px;margin-left:-15px;">
											<input data-role="none" name="" id="dsselectallcheck" value="" type="checkbox" onclick="selectAll(this);" />
											Select/Deselect all Records
										</div>
										<table class="styledtable" style="margin-left:-15px;font-size:12px;">
											<thead>
												<tr>
													<th style="width:15px;"></th>
													<th>Catalog #</th>
													<th>Collector</th>
													<th>Date</th>
													<th>Scientific Name</th>
												</tr>
											</thead>
											<tbody id="recordstbody"></tbody>
										</table>
									</form>
								</div>
								<?php
							}
							else{
								?>
								<div style="border:1px black solid;margin:15px;padding:5px;width:300px;" >
									Please <a href="#" onclick='openLogin();'>login</a> to access dataset tools.
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div><!-- <a href="../../index.php" style="position:absolute;top:0;right:0;margin-right:38px;margin-bottom:0px;margin-top:1px;padding-top:3px;padding-bottom:3px;z-index:10;" data-role="button" data-inline="true" >Home</a> -->
				<a href="#" style="position:absolute;top:2;right:0;margin-right:0px;margin-bottom:0px;margin-top:1px;padding-top:3px;padding-bottom:3px;padding-left:20px;z-index:10;height:20px;" data-rel="close" data-role="button" data-theme="a" data-icon="delete" data-inline="true"></a>
			</div><!-- /content wrapper for padding -->
		</div><!-- /defaultpanel -->
	</div>
	<div id='map' style='width:100%;height:100%;'></div>
</body>
</html>