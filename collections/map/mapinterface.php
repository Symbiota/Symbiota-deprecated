<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonProfileMap.php');
include_once($SERVER_ROOT.'/classes/MapInterfaceManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
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
$solrManager = new SOLRManager();
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
$recordCnt = 0;
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
    //$stArrJson = str_replace( "'", '"',$stArrJson);
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
    if(array_key_exists('gridSizeSetting',$previousCriteria)) $gridSize = $previousCriteria['gridSizeSetting'];
    if(array_key_exists('minClusterSetting',$previousCriteria)) $minClusterSize = $previousCriteria['minClusterSetting'];
    if(array_key_exists('clusterSwitch',$previousCriteria)) $clusterOff = $previousCriteria['clusterSwitch'];
    if(array_key_exists('recordlimit',$previousCriteria)) $recLimit = (($previousCriteria['recordlimit']&&is_numeric($previousCriteria['recordlimit']))?$previousCriteria['recordlimit']:5000);
}

$dbArr = Array();
if(array_key_exists('db',$_REQUEST)){
    if(!is_array($previousCriteria["db"])){
        $dbArr[] = 'all';
    }
    else{
        $dbArr = $previousCriteria["db"];
    }
}
elseif(array_key_exists('db',$previousCriteria)){
    $dbArr = explode(';',$previousCriteria["db"]);
}

if((array_key_exists("upperlat",$previousCriteria)) || (array_key_exists("pointlat",$previousCriteria)) || (array_key_exists("poly_array",$previousCriteria))){
    $queryShape = $mapManager->createShape($previousCriteria);
}

if(!array_key_exists("poly_array",$previousCriteria)) $previousCriteria["poly_array"] = '';
if(!array_key_exists("upperlat",$previousCriteria)) $previousCriteria["upperlat"] = '';
if(!array_key_exists("pointlat",$previousCriteria)) $previousCriteria["pointlat"] = '';

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
elseif($stArr || ($mapType && $mapType == 'occquery') || $clid){
    if($stArr){
        $mapManager->setSearchTermsArr($stArr);
    }
    $mapWhere = $mapManager->getSqlWhere();
    if(!$stArr){
        $stArr = $mapManager->getSearchTermsArr();
    }
    if(!$SOLR_MODE){
        $mapManager->setRecordCnt($mapWhere);
        $recordCnt = $mapManager->getRecordCnt();
    }

    $jsonStArr = json_encode($stArr);
}
?>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $defaultTitle; ?> - Map Interface</title>
    <link type="text/css" href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" rel="stylesheet" />
    <link type="text/css" href="../../css/main.css?ver=<?php echo $CSS_VERSION_LOCAL; ?>" rel="stylesheet" />
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
    <script type="text/javascript" src="../../js/jquery.popupoverlay.js"></script>
    <script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
    <script type="text/javascript" src="../../js/jscolor/jscolor.js?ver=4"></script>
    <script type="text/javascript">
        $(function() {
            var winHeight = $(window).height();
            winHeight = winHeight + "px";
            document.getElementById('mapinterface').style.height = winHeight;
            document.getElementById('loadingOverlay').style.height = winHeight;

            $("#accordion").accordion({
                collapsible: true,
                heightStyle: "fill"
            });
            $('#loadingOverlay').popup({
                transition: 'all 0.3s',
                scrolllock: true,
                opacity:0.5,
                color:'white'
            });

            //showWorking();
        });
    </script>
    <script type="text/javascript" src="../../js/symb/collections.mapinterface.js?20170403"></script>
    <script type="text/javascript" src="../../js/symb/markerclusterer.js?20170403"></script>
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
        var gotCoords = <?php echo ($GEOLOCATION?'true':'false'); ?>;
        var mapSymbol = 'coll';
        var selected = false;
        var deselected = false;
        var positionFound = false;
        var recordsFound = false;
        var jsonRecReturn = '';
        var starr = '<?php echo $jsonStArr; ?>';
        var clid = '<?php echo $clid; ?>';
        var clusterOff = '<?php echo $clusterOff; ?>';
        var genObs = JSON.parse('<?php echo json_encode($genObs); ?>');
        var finderArr = [];
        var grpArr = [];
        var markerArr = [];
        var tidArr = [];
        var taxaArr = [];
        var taxaCnt = 0;
        var collKeyArr = [];
        var collNameArr = [];
        var familyNameArr = [];
        var keyTidArr = [];
        var taxaKeyArr = [];
        var clusterCollArr = [];
        var clusterTaxArr = [];
        var optionsCollArr = [];
        var optionsTaxArr = [];
        var grpCnt = 1;
        var keyHTML = '';
        var ibLabel = '';
        var mouseoverTimeout = '';
        var mouseoutTimeout = '';
        var zoomToPoint = <?php echo ($queryShape?'false':'true'); ?>;
        var pointBounds = new google.maps.LatLngBounds();
        var occArr = [];

        function showWorking(){
            $('#loadingOverlay').popup('show');
        }

        function hideWorking(){
            $('#loadingOverlay').popup('hide');
        }

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
            if(isset($MAPPING_BOUNDARIES)){
                $coorArr = explode(";",$MAPPING_BOUNDARIES);
                if($coorArr && count($coorArr) == 4){
                    $latCen = ($coorArr[0] + $coorArr[2])/2;
                    $longCen = ($coorArr[1] + $coorArr[3])/2;
                }
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
                zoom: <?php echo (isset($GOOGLE_MAP_ZOOM) && $GOOGLE_MAP_ZOOM?$GOOGLE_MAP_ZOOM:'6'); ?>,
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

            // Clear the current selection when the drawing mode is changed, or when the
            // map is clicked.
            //google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
            //google.maps.event.addListener(map, 'click', clearSelection);
            google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteSelectedShape);

            <?php
            echo ($queryShape?$queryShape:'');

            if(!$SOLR_MODE){
                ?>
                if(starr){
                    if(<?php echo $recordCnt; ?> > 0){
                        checkHighResult(<?php echo $recordCnt; ?>);
                    }
                    else{
                        alert('There were no records matching your query.');
                    }
                }
                else{
                    //hideWorking();
                }
                <?php
            }
            else{
                echo 'if(starr) callLazyLoader();';
            }
            ?>
        }

        function callLazyLoader(){
            setTimeout(function() {
                lazyLoadPoints();
            }, 100);
        }

        function setPanels(show){
            if(show){
                document.getElementById("recordstaxaheader").style.display = "block";
                document.getElementById("tabs2").style.display = "block";
            }
            else{
                document.getElementById("recordstaxaheader").style.display = "none";
                document.getElementById("tabs2").style.display = "none";
            }
        }

        function lazyLoadPoints(){
            var returnArr = [];
            var recArr = [];
            var recTot = 0;
            var processed = 0;
            var index = 0;
            var verifyTotal = true;

            do{
                getPointArr(index);
                if(recordsFound){
                    returnArr = JSON.parse(jsonRecReturn);
                    recTot = returnArr['rectot'];
                    if(recTot > 0){
                        <?php
                        if($SOLR_MODE){
                            ?>
                            if(verifyTotal && recTot > 10000){
                                if(confirm("Your search produced "+recTot+" results. Loading this many points can cause significant delays in loading the map. Are you sure you would like to continue?")){
                                    verifyTotal = false;
                                }
                                else{
                                    zoomToPoint = false;
                                    break;
                                }
                            }
                            <?php
                        }
                        ?>
                        recArr = returnArr['recarr'];
                        processPoints(recArr);
                        jsonRecReturn = '';
                    }
                    else{
                        alert('There were no records matching your query.');
                        recordsFound = false;
                        break;
                    }
                }
                else{
                    alert('There was an error retrieving records.');
                    break;
                }
                processed = processed + 1000;
                index++;
            }
            while(processed < recTot);
            if(recordsFound){
                setTimeout(function() {
                    afterEffects();
                }, 500);
            }
        }

        function afterEffects(){
            setPanels(true);
            $("#accordion").accordion("option",{active: 1});
            buildCollKey();
            buildTaxaKey();
            jscolor.init();
            if(zoomToPoint){
                map.fitBounds(pointBounds);
                map.panToBounds(pointBounds);
            }
            setTimeout(function() {
                //hideWorking();
            }, 500);
        }

        function getPointArr(index){
            //console.log('rpc/maplazyloader.php?starr='+starr+'&index='+index+'&reccnt=<?php echo $recordCnt; ?>&maptype=<?php echo $mapType; ?>');
            recordsFound = false;
            $.ajax({
                type: "POST",
                url: "rpc/maplazyloader.php",
                async: false,
                data: {
                    starr: starr,
                    reccnt: <?php echo $recordCnt; ?>,
                    maptype: '<?php echo $mapType; ?>',
                    index: index
                }
            }).done(function(msg) {
                if(msg){
                    jsonRecReturn = msg;
                    recordsFound = true;
                }
                else{
                    return;
                }
            });
        }

        function processPoints(pArr){
            var fndGrps = [];
            for(var key in pArr) {
                var iconColor = pArr[key]['color'];
                var tempGcntArr = [];
                var fndGrpCnt = 0;
                if(!finderArr[key]){
                    finderArr[key] = grpCnt;
                    fndGrpCnt = grpCnt;
                    buildCollKeyPiece(key,iconColor);
                }
                else{
                    fndGrpCnt = finderArr[key];
                }
                fndGrps.push(fndGrpCnt);
                delete pArr[key]['color'];
                for(var occ in pArr[key]) {
                    if(occArr.indexOf(occ) < 0){
                        var family = '';
                        var tidinterpreted = pArr[key][occ]['tidinterpreted'];
                        var sciname = pArr[key][occ]['sciname'];
                        var scinameStr = pArr[key][occ]['namestring'];
                        var tempArr = [];
                        var tempArr = [];
                        if (tidArr[scinameStr]) {
                            if (tidArr[scinameStr].indexOf(grpCnt) > -1) {
                                tempArr = tidArr[scinameStr];
                            }
                        }
                        tempArr.push(grpCnt);
                        tidArr[scinameStr] = tempArr;
                        if (pArr[key][occ]['sciname']) {
                            sciname = pArr[key][occ]['sciname'];
                        }
                        if (sciname) {
                            var tempFamArr = [];
                            var tempScinameArr = [];
                            family = pArr[key][occ]['family'];
                            if ((familyNameArr.indexOf(family) < 0) && (family != 'undefined')) {
                                familyNameArr.push(family);
                            }
                            if (taxaArr[family]) {
                                tempFamArr = taxaArr[family];
                                tempScinameArr = taxaArr[family]['sciname_arr'];
                            }
                            if (keyTidArr.indexOf(scinameStr) < 0) {
                                tempScinameArr.push(sciname);
                                keyTidArr.push(scinameStr);
                                taxaCnt++;
                            }
                            tempFamArr[sciname] = scinameStr;
                            taxaArr[family] = tempFamArr;
                            taxaArr[family]['sciname_arr'] = tempScinameArr;
                            buildTaxaKeyPiece(scinameStr, tidinterpreted, sciname);
                            var llArr = pArr[key][occ]['latLngStr'].split(',');
                            var spStr = '';
                            var titleStr = pArr[key][occ]['latLngStr'];
                            var type = '';
                            var displayStr = pArr[key][occ]['identifier'];
                            var iconColorStr = '#' + iconColor;
                            if (genObs.indexOf(pArr[key][occ]['collid']) > -1) {
                                type = 'obs';
                                var markerIcon = {
                                    path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
                                    fillColor: iconColorStr,
                                    fillOpacity: 1,
                                    scale: 1,
                                    strokeColor: "#000000",
                                    strokeWeight: 1
                                };
                            }
                            else {
                                type = 'spec';
                                var markerIcon = {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    fillColor: iconColorStr,
                                    fillOpacity: 1,
                                    scale: 7,
                                    strokeColor: "#000000",
                                    strokeWeight: 1
                                };
                            }
                            markerArr[occ] = getMarker(llArr[0], llArr[1], displayStr, iconColor, markerIcon, type, scinameStr, occ, 0);
                            addMarkerListners(markerArr[occ]);
                            oms.addMarker(markerArr[occ]);
                            var markerPos = markerArr[occ].getPosition();
                            pointBounds.extend(markerPos);
                            if (grpArr[fndGrpCnt]) {
                                var tempArr = grpArr[fndGrpCnt];
                            }
                            else {
                                var tempArr = [];
                            }
                            tempArr.push(markerArr[occ]);
                            grpArr[fndGrpCnt] = tempArr;
                        }
                        occArr.push(occ);
                    }
                }

                for(var gc in fndGrps) {
                    var markers = grpArr[fndGrps[gc]];

                    optionsCollArr[fndGrps[gc]] = {
                        styles: [{
                            color: iconColor
                        }],
                        maxZoom: 13,
                        gridSize: <?php echo $gridSize; ?>,
                        minimumClusterSize: <?php echo $minClusterSize; ?>
                    };

                    if(clusterOff=="n"){
                        if(clusterCollArr[fndGrps[gc]]){
                            clusterCollArr[fndGrps[gc]].clearMarkers();
                            clusterCollArr[fndGrps[gc]].setMap(null);
                        }
                        clusterCollArr[fndGrps[gc]] = new MarkerClusterer(map, markers, optionsCollArr[fndGrps[gc]]);
                    }
                }

                grpCnt++;
            }
        }

        function buildCollKeyPiece(key,iconColor){
            keyHTML = '';
            keyHTML += '<div style="display:table-row;">';
            keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="keyColor'+grpCnt+'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="'+iconColor+'" onchange="changeCollColor(this.value,'+grpCnt+');" /></div>';
            keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
            keyHTML += '<div style="display:table-cell;width:250px;vertical-align:middle;padding-left:8px;">'+key+'</div>';
            keyHTML += '</div>';
            keyHTML += '<div style="display:table-row;height:8px;"></div>';
            collKeyArr[key] = keyHTML;
            collNameArr.push(key);
        }

        function buildCollKey(){
            keyHTML = '';
            collNameArr.sort();
            for(var i=0,l=collNameArr.length;i<l;i++){
                keyHTML += collKeyArr[collNameArr[i]];
            }
            document.getElementById("symbologykeysbox").innerHTML = keyHTML;
        }

        function buildTaxaKeyPiece(key,tidinterpreted,sciname){
            keyHTML = '';
            keyLabel = "'"+key+"'";
            keyHTML += '<div id="'+key+'keyrow">';
            keyHTML += '<div style="display:table-row;">';
            keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'+key+'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="e69e67" onchange="changeTaxaColor(this.value,'+keyLabel+');" /></div>';
            keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
            if(!tidinterpreted){
                keyHTML += "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i>"+sciname+"</i></div>";
            }
            else{
                keyHTML += "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../../taxa/index.php?taxon="+sciname+"'>"+sciname+"</a></i></div>";
            }
            keyHTML += '</div></div>';
            taxaKeyArr[key] = keyHTML;
        }

        function buildTaxaKey(){
            document.getElementById("taxaCountNum").innerHTML = taxaCnt;
            keyHTML = '';
            familyNameArr.sort();
            for(var i=0,l=familyNameArr.length;i<l;i++){
                tempArr = taxaArr[familyNameArr[i]]['sciname_arr'];
                if(tempArr.length > 0){
                    tempArr.sort();
                    keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>" + familyNameArr[i] + "</h3></div>";
                    keyHTML += "<div style='display:table;'>";
                    for(var s = 0, w = tempArr.length; s < w; s++){
                        var tidCode = taxaArr[familyNameArr[i]][tempArr[s]];
                        if (taxaKeyArr[tidCode]) keyHTML += taxaKeyArr[tidCode];
                    }
                    keyHTML += "</div>";
                }
            }
            if(taxaArr['undefined']){
                tempArr = taxaArr['undefined']['sciname_arr'];
                if(tempArr.length > 0){
                    tempArr.sort();
                    keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>Family Not Defined</h3></div>";
                    keyHTML += "<div style='display:table;'>";
                    for(var s = 0, w = tempArr.length; s < w; s++){
                        var tidCode = taxaArr[familyNameArr[i]][tempArr[s]];
                        keyHTML += taxaKeyArr[tidCode];
                    }
                    keyHTML += "</div>";
                }
            }
            document.getElementById("taxasymbologykeysbox").innerHTML = keyHTML;
        }

        function changeMainKey(gCnt,newColor){
            if(mapSymbol == 'taxa'){
                clearTaxaSymbology();
            }
            changeKeyColor(newColor,grpArr[gCnt]);
            if(clusterOff=="n"){
                if(clusterCollArr[gCnt]){
                    clusterCollArr[gCnt].clearMarkers();
                }
                optionsCollArr[gCnt] = {
                    styles: [{
                        color: newColor
                    }],
                    maxZoom: 13,
                    gridSize: <?php echo $gridSize; ?>,
                    minimumClusterSize: <?php echo $minClusterSize; ?>
                };
                clusterCollArr[gCnt] = new MarkerClusterer(map, grpArr[gCnt], optionsCollArr[gCnt]);
            }
        }

        function findSelection(gCnt,id,dir){
            if (grpArr[gCnt]) {
                for (i in grpArr[gCnt]) {
                    if(grpArr[gCnt][i].occid==id){
                        if(grpArr[gCnt][i].customInfo=='obs'){
                            var markerColor = '#'+grpArr[gCnt][i].color;
                            if(dir == 'select'){
                                var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
                            }
                            else if(dir == 'deselect'){
                                var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
                            }
                            grpArr[gCnt][i].setIcon(markerIcon);
                        }
                        if(grpArr[gCnt][i].customInfo=='spec'){
                            var markerColor = '#'+grpArr[gCnt][i].color;
                            if(dir == 'select'){
                                var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
                            }
                            else if(dir == 'deselect'){
                                var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
                            }
                            grpArr[gCnt][i].setIcon(markerIcon);
                        }
                        if(dir == 'select'){
                            grpArr[gCnt][i].selected = true;
                            selected = true;
                        }
                        else if(dir == 'deselect'){
                            grpArr[gCnt][i].selected = false;
                            deselected = true;
                        }
                        return;
                    }
                }
            }
        }

        function findGrpClusterSelection(gCnt,id){
            if(clusterCollArr[gCnt]){
                var clusters = clusterCollArr[gCnt].getClusters();
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

        function findTaxClusterSelection(id){
            for(var tid in tidArr) {
                if(clusterTaxArr[tid]){
                    var clusters = clusterTaxArr[tid].getClusters();
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
        }

        function clearTaxaSymbology(){
            for(var tid in tidArr) {
                if(clusterTaxArr[tid]){
                    clusterTaxArr[tid].clearMarkers();
                }
                var keyName = 'taxaColor'+tid;
                if(document.getElementById(keyName)){
                    document.getElementById(keyName).color.fromString("E69E67");
                }
            }
        }

        function resetMainSymbology(){
            for(var gcnt in grpArr) {
                changeMainKey(gcnt,"E69E67");
                var keyName = 'keyColor'+gcnt;
                document.getElementById(keyName).color.fromString("E69E67");
            }
        }

        function changeCollColor(color,gcnt){
            changeMainKey(gcnt,color);
            mapSymbol = 'coll';
        }

        function changeTaxaColor(color,tidcode){
            if(mapSymbol == 'coll'){
                resetMainSymbology();
            }
            changeTaxaKey(tidcode,color);
            mapSymbol = 'taxa';
        }

        function addMarkerListners(marker){
            marker.addListener('mouseover', function() {
                occid = marker.occid;
                clid = marker.clid;
                markerLabel = marker.text;
                boxPosition = marker.getPosition();
                boxText = '<div>'+markerLabel+'<br /><a href="#" onclick="closeAllInfoWins();openIndPU('+occid+','+clid+');return false;"><span style="color:blue;">See Details</span></a></div>';
                var myOptions = {
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

                if(mouseoutTimeout){
                    if(ibLabel) {
                        ibLabel.close();
                    }
                    clearTimeout(mouseoutTimeout);
                    mouseoutTimeout = null;
                }

                mouseoverTimeout = setTimeout(
                    function(){
                        ibLabel = new InfoBox(myOptions);
                        ibLabel.open(map);
                    },1000
                );
            });

            marker.addListener('mouseout', function() {
                if(mouseoverTimeout){
                    clearTimeout(mouseoverTimeout);
                    mouseoverTimeout = null;
                }
                mouseoutTimeout = setTimeout(
                    function(){
                        if(ibLabel){
                            ibLabel.close();
                        }
                    },3000
                );
            });
        }

        function changeTaxaKey(tid,newColor){
            if(clusterTaxArr[tid]){
                clusterTaxArr[tid].clearMarkers();
            }
            var tempArr = [];
            for(var gcnt in grpArr) {
                if(grpArr[gcnt]) {
                    var newMarkerColor = '#'+newColor;
                    for (i in grpArr[gcnt]) {
                        if(grpArr[gcnt][i].taxatid == tid){
                            if(grpArr[gcnt][i].customInfo=='obs'){
                                if(grpArr[gcnt][i].selected==true){
                                    var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
                                }
                                else{
                                    var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
                                }
                                grpArr[gcnt][i].color = newColor;
                                grpArr[gcnt][i].setIcon(markerIcon);
                            }
                            if(grpArr[gcnt][i].customInfo=='spec'){
                                if(grpArr[gcnt][i].selected==true){
                                    var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
                                }
                                else{
                                    var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
                                }
                                grpArr[gcnt][i].color = newColor;
                                grpArr[gcnt][i].setIcon(markerIcon);
                            }
                            tempArr.push(grpArr[gcnt][i]);
                            if(clusterOff=="n"){
                                if(clusterCollArr[gcnt]){
                                    clusterCollArr[gcnt].removeMarker(grpArr[gcnt][i]);
                                }
                            }
                        }
                    }
                }
            }

            if(clusterOff=="n"){
                optionsTaxArr[tid] = {
                    styles: [{
                        color: newColor
                    }],
                    maxZoom: 13,
                    gridSize: <?php echo $gridSize; ?>,
                    minimumClusterSize: <?php echo $minClusterSize; ?>
                };

                clusterTaxArr[tid] = new MarkerClusterer(map, tempArr, optionsTaxArr[tid]);
            }
        }

        function autoColorColl(){
            document.getElementById("randomColorColl").disabled = true;
            if(mapSymbol == 'taxa'){
                clearTaxaSymbology();
            }
            var usedColors = [];
            for(var gcnt in grpArr) {
                var randColor = generateRandColor();
                while (usedColors.indexOf(randColor) > -1) {
                    randColor = generateRandColor();
                }
                usedColors.push(randColor);
                changeMainKey(gcnt,randColor);
                var keyName = 'keyColor'+gcnt;
                document.getElementById(keyName).color.fromString(randColor);
            }
            mapSymbol = 'coll';
            document.getElementById("randomColorColl").disabled = false;
        }

        function autoColorTaxa(){
            document.getElementById("randomColorTaxa").disabled = true;
            resetMainSymbology();
            var usedColors = [];
            for(var tid in tidArr) {
                var randColor = generateRandColor();
                while (usedColors.indexOf(randColor) > -1) {
                    randColor = generateRandColor();
                }
                usedColors.push(randColor);
                changeTaxaKey(tid,randColor);
                var keyName = 'taxaColor'+tid;
                if(document.getElementById(keyName)){
                    document.getElementById(keyName).color.fromString(randColor);
                }
            }
            mapSymbol = 'taxa';
            document.getElementById("randomColorTaxa").disabled = false;
        }

        function selectPoints(){
            var selectedpoints = document.getElementById("selectedpoints");
            selected = false;
            var selectedpoint = Number(selectedpoints.value);
            while (selected == false) {
                for(var gcnt in grpArr) {
                    findSelection(gcnt,selectedpoint,'select');
                    if(clusterOff=="n"){
                        findGrpClusterSelection(gcnt,selectedpoint);
                    }
                }
                if(clusterOff=="n"){
                    findTaxClusterSelection(selectedpoint);
                }
            }
            if(selections.indexOf(selectedpoint) < 0){
                selections.push(selectedpoint);
            }
            adjustSelectionsTab();
        }

        function selectDSPoints(){
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

        function deselectPoints(){
            deselected = false;
            var deselectedpoint = Number(deselectedpoints.value);
            while (deselected == false) {
                for(var gcnt in grpArr) {
                    findSelection(gcnt,deselectedpoint,'deselect');
                    if(clusterOff=="n"){
                        findGrpClusterSelection(gcnt,deselectedpoint);
                    }
                }
                if(clusterOff=="n"){
                    findTaxClusterSelection(deselectedpoint);
                }
            }
            var index = selections.indexOf(deselectedpoint);
            selections.splice(index, 1);
            adjustSelectionsTab();
        }

        function deselectDSPoints(){
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

        function clearSelections(){
            for (var i = 0; i < selections.length; i++) {
                occid = Number(selections[i]);
                for(var gcnt in grpArr) {
                    findSelection(gcnt,occid,'deselect');
                    if(clusterOff=="n"){
                        findGrpClusterSelection(gcnt,occid);
                    }
                }
                if(clusterOff=="n"){
                    findTaxClusterSelection(occid);
                }
            }
            selections.length = 0;
            adjustSelectionsTab();
            document.getElementById("selectiontbody").innerHTML = '';
        }

        function zoomToSelections(){
            var selectZoomBounds = new google.maps.LatLngBounds();
            for(var gcnt in grpArr) {
                for (var i=0; i < selections.length; i++) {
                    occid = Number(selections[i]);
                    if (grpArr[gcnt]) {
                        for (j in grpArr[gcnt]) {
                            if(grpArr[gcnt][j].occid==occid){
                                var markerPos = grpArr[gcnt][j].getPosition();
                                selectZoomBounds.extend(markerPos);
                            }
                        }
                    }
                }
            }
            map.fitBounds(selectZoomBounds);
            map.panToBounds(selectZoomBounds);
        }

        function resetSymbology(){
            document.getElementById("symbolizeReset1").disabled = true;
            document.getElementById("symbolizeReset2").disabled = true;
            clearTaxaSymbology();
            resetMainSymbology();
            mapSymbol = 'coll';
            document.getElementById("symbolizeReset1").disabled = false;
            document.getElementById("symbolizeReset2").disabled = false;
        }

        function checkHighResult(result) {
            if (result <= 15000) {
                if(result <= 5000){
                    callLazyLoader();
                }
                else if(confirm("Your search produced "+result+" results. Loading this many points can cause significant delays in loading the map. Are you sure you would like to continue?")){
                    //document.getElementById("recordlimit").value = result;
                    //refreshClustering();
                    callLazyLoader();
                }
                else{
                    //hideWorking();
                    return;
                }
            }
            else{
                alert("Your search produced "+result+" results which exceeds the maximum of 15000, please refine your search more.");
                //hideWorking();
            }
        }

        function changeRecordPage(starr,page){
            document.getElementById("queryrecords").innerHTML = "<p>Loading...</p>";
            getRecords(starr,page);
        }

        function getRecords(starr,page){
            //alert("rpc/changemaprecordpage.php?starr="+starr+"&selected="+JSON.stringify(selections)+"&page="+page);

            setTimeout(function(){
                $.ajax({
                    type: "POST",
                    url: "rpc/changemaprecordpage.php",
                    async: false,
                    data: {
                        starr: starr,
                        selected: JSON.stringify(selections),
                        page: page
                    }
                }).done(function(msg) {
                    if(msg){
                        var newMapRecordList = JSON.parse(msg);
                        document.getElementById("queryrecords").innerHTML = newMapRecordList;
                    }
                    else{
                        return;
                    }
                });
            },5)
        }

        <?php echo ($GEOLOCATION?"google.maps.event.addDomListener(window, 'load', getCoords);":""); ?>
    </script>
</head>
<body style='width:100%;' <?php echo (!$GEOLOCATION?'onload="initialize();"':''); ?>>
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
                                <li id="mapinterfaceCollectionsTab"><a href="#searchcollections"><span>Collections</span></a></li>
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
                                    <!-- <div style="float:left;<?php echo ($SOLR_MODE?'display:none;':''); ?>">
                                        Record Limit:
                                        <input data-role="none" type="text" id="recordlimit" style="width:75px;" name="recordlimit" value="<?php echo ($recLimit?$recLimit:""); ?>" title="Maximum record amount returned from search." onchange="return checkRecordLimit(this.form);" />
                                    </div> -->
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
                                        <input type="hidden" id="clid" name="clid" value='<?php echo (array_key_exists("clid",$previousCriteria)?$previousCriteria["clid"]:""); ?>' />
                                        <button data-role="none" type=button id="resetform" name="resetform" onclick='window.open("mapinterface.php", "_self");' >Reset</button>
                                        <button data-role="none" id="display2" name="display2" onclick='submitMapForm(this.form);' >Search</button>
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div>
                                    <span style=""><input data-role="none" type='checkbox' name='thes' value='1' <?php echo (array_key_exists("thes",$previousCriteria) && !$previousCriteria["thes"]?'':'CHECKED'); ?> >Include Synonyms</span>
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
                                <div style="margin-top:5px;">
                                    Other Catalog Number:
                                    <input data-role="none" type="text" id="othercatnum" style="width:150px;" name="othercatnum" value="<?php if(array_key_exists("othercatnum",$previousCriteria)) echo $previousCriteria["othercatnum"]; ?>" title="" />
                                </div>
                                <div style="margin-top:5px;">
                                    <input data-role="none" type='checkbox' name='typestatus' value='1' <?php if(array_key_exists("typestatus",$previousCriteria) && $previousCriteria["typestatus"]) echo "CHECKED"; ?> > Limit to Type Specimens Only
                                </div>
                                <div style="margin-top:5px;">
                                    <input data-role="none" type='checkbox' name='hasimages' value='1' <?php if(array_key_exists("hasimages",$previousCriteria) && $previousCriteria["hasimages"]) echo "CHECKED"; ?> > Limit to Specimens with Images Only
                                </div>
                                <div style="margin-top:5px;">
                                    <input data-role="none" type='checkbox' name='hasgenetic' value='1' <?php if(array_key_exists("hasgenetic",$previousCriteria) && $previousCriteria["hasgenetic"]) echo "CHECKED"; ?> > Limit to Specimens with Genetic Data Only
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
                            if($stArr){
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
                    if($stArr){
                        ?>
                        <h3 id="recordstaxaheader" style="display:none;">Records and Taxa</h3>
                        <div id="tabs2" style="display:none;width:379px;padding:0px;">
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
                                            <button data-role="none" id="symbolizeReset1" name="symbolizeReset1" onclick='resetSymbology();' >Reset Symbology</button>
                                        </div>
                                        <div style="margin-top:5px;">
                                            <button data-role="none" id="randomColorColl" name="randomColorColl" onclick='autoColorColl();' >Auto Color</button>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;clear:both;"><hr /></div>
                                <div style="" >
                                    <div style="margin-top:8px;">
                                        <div style="display:table;">
                                            <div id="symbologykeysbox"></div>
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
                                            <button data-role="none" id="symbolizeReset2" name="symbolizeReset2" onclick='resetSymbology();' >Reset Symbology</button>
                                        </div>
                                        <div style="margin-top:5px;">
                                            <button data-role="none" id="randomColorTaxa" name="randomColorTaxa" onclick='autoColorTaxa();' >Auto Color</button>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;clear:both;"><hr /></div>
                                <div style='font-weight:bold;'>Taxa Count: <span id="taxaCountNum">0</span></div>
                                <div id="taxasymbologykeysbox"></div>
                            </div>
                            <div id="selectionslist" style="">
                                <div style="height:65px;margin-bottom:15px;">
                                    <div style="float:left;">
                                        <div>
                                            <button data-role="none" id="clearselectionsbut" onclick='clearSelections();' >Clear Selections</button>
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
                                            <button data-role="none" id="zoomtoselectionsbut" onclick='zoomToSelections();' >Zoom to Selections</button>
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
                                    <table class="styledtable" style="font-family:Arial;font-size:12px;margin-left:-15px;">
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
                                    <table class="styledtable" style="font-family:Arial;font-size:12px;margin-left:-15px;">
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
<div id="loadingOverlay" data-role="popup" style="width:100%;position:relative;">
    <div id="loadingImage" style="width:100px;height:100px;position:absolute;top:50%;left:50%;margin-top:-50px;margin-left:-50px;">
        <img style="border:0px;width:100px;height:100px;" src="../../images/ajax-loader.gif" />
    </div>
</div>
</body>
</html>