<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonProfileMap.php');
include_once($SERVER_ROOT.'/classes/SpatialModuleManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$spatialManager = new SpatialModuleManager();

$collList = $spatialManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

$dbArr = Array();
?>
<html>
<head>
    <title><?php echo $DEFAULT_TITLE; ?> Spatial Module</title>
    <link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/bootstrap.css" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/jquery.mobile-1.4.0.min.css" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/jquery.symbiota.css" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui_accordian.css" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/ol.css" type="text/css" rel="stylesheet" />
    <link href="<?php echo $CLIENT_ROOT; ?>/css/spatialbase.css?ver=2" type="text/css" rel="stylesheet" />
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.mobile-1.4.0.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-1.9.1.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui-1.10.4.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.popupoverlay.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/ol-symbiota-ext.js?ver=2" type="text/javascript"></script>
    <script src="https://npmcdn.com/@turf/turf/turf.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jscolor/jscolor.js?ver=5" type="text/javascript"></script>
    <!-- <script src="<?php echo $CLIENT_ROOT; ?>/js/stream.js" type="text/javascript"></script> -->
    <!-- <script src="<?php echo $CLIENT_ROOT; ?>/js/shapefile.js?ver=2" type="text/javascript"></script> -->
    <!-- <script src="<?php echo $CLIENT_ROOT; ?>/js/dbf.js" type="text/javascript"></script> -->
    <script src="<?php echo $CLIENT_ROOT; ?>/js/symb/spatial.module.js?ver=125" type="text/javascript"></script>
    <script type="text/javascript">
        $(function() {
            var winHeight = $(window).height();
            winHeight = winHeight + "px";
            document.getElementById('spatialpanel').style.height = winHeight;

            $("#accordion").accordion({
                collapsible: true,
                heightStyle: "fill"
            });
        });

        $(document).ready(function() {
            $('#criteriatab').tabs({
                beforeLoad: function( event, ui ) {
                    $(ui.panel).html("<p>Loading...</p>");
                }
            });
            $('#recordstab').tabs({
                beforeLoad: function( event, ui ) {
                    $(ui.panel).html("<p>Loading...</p>");
                }
            });
            $('#addLayers').popup({
                transition: 'all 0.3s',
                scrolllock: true
            });
        });
    </script>
</head>
<body class="mapbody">
<div data-role="page" id="page1">
    <div role="main" class="ui-content">
        <a href="#defaultpanel" id="panelopenbutton" data-role="button" data-inline="true" data-icon="bars">Open</a>
    </div>
    <!-- defaultpanel -->
    <div data-role="panel" data-dismissible="false" class="overflow:hidden;" id="defaultpanel" data-position="left" data-display="overlay" >
        <div class="panel-content">
            <div id="spatialpanel">
                <div id="accordion">
                    <h3 class="tabtitle">Search Criteria</h3>
                    <div id="criteriatab">
                        <ul>
                            <li><a class="tabtitle" href="#searchcriteria">Criteria</a></li>
                            <li><a class="tabtitle" href="#searchcollections">Collections</a></li>
                            <!-- <li><a class="tabtitle" href="#maptools">Map Tools</a></li> -->
                        </ul>
                        <div id="searchcollections">
                            <div class="mapinterface">
                                <form name="spatialcollsearchform" id="spatialcollsearchform" data-ajax="false" action="index.php" method="get" onsubmit="">
                                    <div>
                                        <h1 style="margin:0px 0px 8px 0px;font-size:15px;">Collections to be Searched</h1>
                                    </div>
                                    <?php
                                    if($specArr || $obsArr){
                                        ?>
                                        <div id="specobsdiv">
                                            <div style="margin:0px 0px 10px 20px;">
                                                <input id="dballcb" data-role="none" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" checked />
                                                Select/Deselect All
                                            </div>
                                            <?php
                                            if($specArr){
                                                $spatialManager->outputFullMapCollArr($specArr);
                                            }
                                            if($specArr && $obsArr) echo '<hr style="clear:both;height:2px;background-color:black;"/>';
                                            if($obsArr){
                                                $spatialManager->outputFullMapCollArr($obsArr);
                                            }
                                            ?>
                                            <div style="clear:both;"></div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </form>
                            </div>
                        </div>
                        <div id="searchcriteria">
                            <div name="spatialcriteriasearchform" id="spatialcriteriasearchform">


                                <div style="height:25px;">
                                    <div style="float:right;">
                                        <button data-role="none" type=button id="resetform" name="resetform" onclick='window.open("index.php", "_self");' >Reset</button>
                                        <button data-role="none" id="display2" name="display2" onclick='loadPoints();' >Load Records</button>
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div>
                                    <span style=""><input data-role="none" type='checkbox' name='thes' id='thes' onchange="buildQueryStrings();" value='1'>Include Synonyms</span>
                                </div>
                                <div id="taxonSearch0">
                                    <div id="taxa_autocomplete" >
                                        <div style="margin-top:5px;">
                                            <select data-role="none" id="taxontype" name="type" onchange="buildQueryStrings();">
                                                <option id='familysciname' value='1'>Family or Scientific Name</option>
                                                <option id='family' value='2'>Family only</option>
                                                <option id='sciname' value='3'>Scientific Name only</option>
                                                <option id='highertaxon' value='4'>Higher Taxonomy</option>
                                                <option id='commonname' value='5'>Common Name</option>
                                            </select>
                                        </div>
                                        <div style="margin-top:5px;">
                                            Taxa: <input data-role="none" id="taxa" type="text" style="width:275px;" name="taxa" value="" onchange="buildQueryStrings();" title="Separate multiple taxa w/ commas" />
                                        </div>
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <!-- <div id="checklistSearch0">
                                    <div id="checklist_autocomplete" >
                                        Checklist:
                                        <input data-role="none" type="text" id="checklistname" style="width:275px;" name="checklistname" value="" title="" />
                                        <input id="clid" name="clid" type="hidden"  value="" />
                                    </div>
                                </div>
                                <div><hr></div> -->
                                <div>
                                    Country: <input data-role="none" type="text" id="country" style="width:225px;" name="country" value="" onchange="buildQueryStrings();" title="Separate multiple terms w/ commas" />
                                </div>
                                <div style="margin-top:5px;">
                                    State/Province: <input data-role="none" type="text" id="state" style="width:150px;" name="state" value="" onchange="buildQueryStrings();" title="Separate multiple terms w/ commas" />
                                </div>
                                <div style="margin-top:5px;">
                                    County: <input data-role="none" type="text" id="county" style="width:225px;"  name="county" value="" onchange="buildQueryStrings();" title="Separate multiple terms w/ commas" />
                                </div>
                                <div style="margin-top:5px;">
                                    Locality: <input data-role="none" type="text" id="locality" style="width:225px;" name="local" onchange="buildQueryStrings();" value="" />
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div id="shapecriteriabox">
                                    <div id="noshapecriteria">
                                        No shapes are selected on the map.
                                    </div>
                                    <div id="shapecriteria" style="display:none;">
                                        Within selected shapes.
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div>
                                    Collector's Last Name:
                                    <input data-role="none" type="text" id="collector" style="width:125px;" name="collector" value="" onchange="buildQueryStrings();" title="" />
                                </div>
                                <div style="margin-top:5px;">
                                    Collector's Number:
                                    <input data-role="none" type="text" id="collnum" style="width:125px;" name="collnum" value="" onchange="buildQueryStrings();" title="Separate multiple terms by commas and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750" />
                                </div>
                                <div style="margin-top:5px;">
                                    Collection Date:
                                    <input data-role="none" type="text" id="eventdate1" style="width:100px;" name="eventdate1" value="" onchange="buildQueryStrings();" title="Single date or start date of range" /> -
                                    <input data-role="none" type="text" id="eventdate2" style="width:100px;" name="eventdate2" value="" onchange="buildQueryStrings();" title="End date of range; leave blank if searching for single date" />
                                </div>
                                <div style="margin:10 0 10 0;"><hr></div>
                                <div>
                                    Catalog Number:
                                    <input data-role="none" type="text" id="catnum" style="width:150px;" name="catnum" value="" onchange="buildQueryStrings();" title="" />
                                </div>
                                <div style="margin-top:5px;">
                                    Other Catalog Number:
                                    <input data-role="none" type="text" id="othercatnum" style="width:150px;" name="othercatnum" value="" onchange="buildQueryStrings();" title="" />
                                </div>
                                <div style="margin-top:5px;">
                                    <input data-role="none" type='checkbox' name='typestatus' id='typestatus' value='1' onchange="buildQueryStrings();"> Limit to Type Specimens Only
                                </div>
                                <div style="margin-top:5px;">
                                    <input data-role="none" type='checkbox' name='hasimages' id='hasimages' value='1' onchange="buildQueryStrings();"> Limit to Specimens with Images Only
                                </div>
                                <div><hr></div>
                            </div>
                        </div>
                        <!-- <div id="maptools">
                            <div id="toollist">
                                <ul>
                                    <li><a href='#' onclick='return false;'>tool1</a></li>
                                    <li><a href='#' onclick='return false;'>tool2</a></li>
                                    <li><a href='#' onclick='return false;'>tool3</a></li>
                                </ul>
                            </div>
                        </div> -->
                    </div>

                    <h3 id="recordsHeader" class="tabtitle" style="display:none;">Records and Taxa</h3>
                    <div id="recordstab" style="display:none;width:379px;padding:0px;">
                        <ul>
                            <li><a href='#symbology' onclick='buildCollKey();'>Collections</a></li>
                            <li><a href='#queryrecordsdiv' onclick='changeRecordPage(1);'>Records</a></li>
                            <li><a href='#maptaxalist' onclick='buildTaxaKey();'>Taxa</a></li>
                            <li style="display:none;" id="selectionstab" ><a href='#selectionslist'>Selections</a></li>
                        </ul>
                        <div id="symbology" style="">
                            <div style="height:40px;margin-bottom:15px;">
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
                                            <input data-role="none" name="kmlreclimit" id="kmlreclimit" type="hidden" value="<?php //echo $recLimit; ?>" />
                                            <button data-role="none" name="submitaction" type="button" onclick='prepSelectionKml(this.form);' >Download KML file</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div id="queryrecords" style=""></div>
                        </div>
                        <div id="maptaxalist" >
                            <div style="height:40px;margin-bottom:15px;">
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
                                    <div style="margin-top:5px;margin-bottom:5px;">
                                        <button data-role="none" id="selectioncsvbut" onclick='openCsvOptions("selection");' >Download CSV file</button>
                                    </div>
                                </div>
                                <div id="" style='margin-right:15px;float:right;' >
                                    <div>
                                        <button data-role="none" id="zoomtoselectionsbut" onclick='zoomToSelections();' >Zoom to Selections</button>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <button data-role="none" name="submitaction" type="button" onclick='prepSelectionKml(this.form);' >Download KML file</button>
                                    </div>
                                </div>
                            </div>
                            <div style="margin:5 0 5 0;clear:both;"><hr /></div>
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
                        </div>

                    </div>

                </div>
            </div>
            <a href="#" id="panelclosebutton" data-rel="close" data-role="button" data-theme="a" data-icon="delete" data-inline="true"></a>
        </div><!-- /content wrapper for padding -->
    </div><!-- /defaultpanel -->
</div>

<div id="map" class="map"></div>

<div id="popup" class="ol-popup">
    <a href="#" id="popup-closer" class="ol-popup-closer"></a>
    <div id="popup-content"></div>
</div>

<div id="finderpopup" class="ol-popup ol-popup-finder" style="padding:5px;">
    <a href="#" id="finderpopup-closer" style="display:none;"></a>
    <div id="finderpopup-content"></div>
</div>

<div id="mapinfo">
    <div id="mapcoords"></div>
    <div id="mapscale_us"></div>
    <div id="mapscale_metric"></div>
</div>

<div id="maptoolcontainer">
    <div id="maptoolbox">
        <div id="drawcontrol">
            <span class="maptext">Draw Tool</span>
            <select id="drawselect">
                <option value="None">None</option>
                <option value="Polygon">Polygon</option>
                <option value="Circle">Circle</option>
            </select>
        </div>
        <div id="selectcontrol">
            <span class="maptext">Active Layer</span>
            <select id="selectlayerselect" onchange="setActiveLayer();">
                <option id="lsel-none" value="none">None</option>
            </select>
        </div>
        <div style="clear:both;"></div>
        <div id="basecontrol">
            <span class="maptext">Base Layer</span>
            <select data-role="none" id="base-map" onchange="changeBaseMap();">
                <option value="worldtopo">World Topo</option>
                <option value="openstreet">OpenStreetMap</option>
                <option value="blackwhite">Black &amp; White</option>
                <option value="worldimagery">World Imagery</option>
                <option value="ocean">Ocean</option>
                <option value="ngstopo">NGS Topo</option>
                <option value="natgeoworld">NGS World</option>
                <option value="esristreet">ESRI StreetMap World</option>
            </select>
        </div>
        <div id="layerControllerLink" style="display:none;">
            <span class="maptext"><a class="addLayers_open" href="#addLayers">Edit Layers</a></span>
        </div>
        <div style="clear:both;"></div>
        <div id="clustercontrol">
            <span class="maptext"><input data-role="none" type='checkbox' name='clusterswitch' id='clusterswitch' onchange="changeClusterSetting();" value='1' checked>Cluster Points</span>
        </div>
    </div>
</div>

<script type="text/javascript">
    var layersArr = [];
    var mouseCoords = [];
    var tempcqlArr = [];
    var cqlArr = [];
    var solrqArr = [];
    var solrgeoqArr = [];
    var selections = [];
    var collSymbology = [];
    var taxaSymbology = [];
    var collKeyArr = [];
    var taxaKeyArr = [];
    var cqlString = '';
    var newcqlString = '';
    var solrqString = '';
    var newsolrqString = '';
    var solroccqString = '';
    var geoCallOut = false;
    var solrRecCnt = 0;
    var draw;
    var taxaArr = [];
    var taxontype = '';
    var thes = false;
    var loadVectorPoints = true;
    var taxaCnt = 0;
    var lazyLoadCnt = 5000;
    var clusterPoints = true;
    var mapSymbology = 'coll';
    var clusterKey = 'CollectionName';
    var maxFeatureCount;
    var currentResolution;
    var activeLayer = 'none';
    var shapeActive = false;
    var pointActive = false;
    var spiderCluster;
    var hiddenClusters = [];
    var layersExist = false;
    var dragDrop1 = false;
    var dragDrop2 = false;
    var dragDrop3 = false;
    var dragDropTarget = '';

    var popupcontainer = document.getElementById('popup');
    var popupcontent = document.getElementById('popup-content');
    var popupcloser = document.getElementById('popup-closer');

    var popupoverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
        element: popupcontainer,
        autoPan: true,
        autoPanAnimation: {
            duration: 250
        }
    }));

    popupcloser.onclick = function() {
        popupoverlay.setPosition(undefined);
        popupcloser.blur();
        return false;
    };

    var finderpopupcontainer = document.getElementById('finderpopup');
    var finderpopupcontent = document.getElementById('finderpopup-content');
    var finderpopupcloser = document.getElementById('finderpopup-closer');

    var finderpopupoverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
        element: finderpopupcontainer,
        autoPan: true,
        autoPanAnimation: {
            duration: 250
        }
    }));

    finderpopupcloser.onclick = function() {
        finderpopupoverlay.setPosition(undefined);
        finderpopupcloser.blur();
        return false;
    };

    var mapProjection = new ol.proj.Projection({
        code: 'EPSG:3857'
    });

    var wgs84Projection = new ol.proj.Projection({
        code: 'EPSG:4326',
        units: 'degrees'
    });

    var projection = ol.proj.get('EPSG:4326');
    var projectionExtent = projection.getExtent();
    var tileSize = 512;
    var maxResolution = ol.extent.getWidth(projectionExtent) / (tileSize * 2);
    var resolutions = new Array(16);
    for (var z = 0; z < 16; ++z) {
        resolutions[z] = maxResolution / Math.pow(2, z);
    }

    var baselayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}'
        })
    });

    var selectsource = new ol.source.Vector({wrapX: false});
    var selectlayer = new ol.layer.Vector({
        source: selectsource
    });

    var pointvectorsource = new ol.source.Vector({wrapX: false});
    var pointvectorlayer = new ol.layer.Vector({
        source: pointvectorsource
    });

    var blankdragdropsource = new ol.source.Vector({wrapX: false});
    var dragdroplayer1 = new ol.layer.Vector({
        source: blankdragdropsource
    });
    var dragdroplayer2 = new ol.layer.Vector({
        source: blankdragdropsource
    });
    var dragdroplayer3 = new ol.layer.Vector({
        source: blankdragdropsource
    });

    /*var pointimagesource = new ol.source.ImageWMS({
        url: '<?php echo $GEOSERVER_URL; ?>/<?php echo $GEOSERVER_OCC_WORKSPACE; ?>/wms',
        params: {
            'LAYERS':'<?php echo $GEOSERVER_OCC_WORKSPACE; ?>:<?php echo $GEOSERVER_OCC_LAYER; ?>',
            'CRS':'EPSG:4326',
            'CQL_FILTER':cqlString
        },
        serverType: 'geoserver',
        imageLoadFunction: function(image, src) {
            imagePostFunction(image, src);
        }
    });
    var pointimagelayer = new ol.layer.Image({
        source: pointimagesource
    });*/

    var spiderLayer = new ol.layer.Vector({
        source: new ol.source.Vector({
            features: new ol.Collection(),
            useSpatialIndex: true
        })
    });

    layersArr['base'] = baselayer;
    layersArr['dragdrop1'] = dragdroplayer1;
    layersArr['dragdrop2'] = dragdroplayer2;
    layersArr['dragdrop3'] = dragdroplayer3;
    layersArr['select'] = selectlayer;
    layersArr['pointv'] = pointvectorlayer;
    //layersArr['pointi'] = pointimagelayer;
    layersArr['spider'] = spiderLayer;

    var zoomslider = new ol.control.ZoomSlider();
    var scaleLineControl_us = new ol.control.ScaleLine({target: document.getElementById('mapscale_us'),units: 'us'});
    var scaleLineControl_metric = new ol.control.ScaleLine({target: document.getElementById('mapscale_metric'),units: 'metric'});
    var dragAndDropInteraction = new ol.interaction.DragAndDrop({
        formatConstructors: [
            ol.format.GPX,
            ol.format.GeoJSON,
            ol.format.IGC,
            ol.format.KML,
            ol.format.TopoJSON
        ]
    });
    var selectInteraction = new ol.interaction.Select({
        layers: [layersArr['select']],
        condition: function(evt) {
            if(evt.type == 'click' && activeLayer == 'select'){
                return true;
            }
            else{
                return false;
            }
        },
        toggleCondition: ol.events.condition.click
    });
    var pointInteraction = new ol.interaction.Select({
        layers: [layersArr['pointv'],layersArr['spider']],
        condition: function(evt) {
            if(evt.type == 'click' && activeLayer == 'Points' && !evt.originalEvent.altKey){
                if(spiderCluster){
                    var spiderclick = map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
                        if(feature && layer === layersArr['spider']){
                            return feature;
                        }
                    });
                    if(!spiderclick){
                        layersArr['spider'].getSource().clear();
                        for(i in hiddenClusters){
                            showCluster(hiddenClusters[i]);
                        }
                        hiddenClusters = [];
                        spiderCluster = '';
                        layersArr['pointv'].getSource().changed();
                    }
                }
                return true;
            }
            else{
                return false;
            }
        },
        toggleCondition: ol.events.condition.click,
        multi: true,
        hitTolerance: 2,
        style: getPointStyle
    });

    var dragDropStyle = {
        'Point': new ol.style.Style({
            image: new ol.style.Circle({
                fill: new ol.style.Fill({
                    color: 'rgba(255,255,0,0.5)'
                }),
                radius: 5,
                stroke: new ol.style.Stroke({
                    color: '#ff0',
                    width: 1
                })
            })
        }),
        'LineString': new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#f00',
                width: 3
            })
        }),
        'Polygon': new ol.style.Style({
            fill: new ol.style.Fill({
                color: 'rgba(170,170,170,0.3)'
            }),
            stroke: new ol.style.Stroke({
                color: '#000000',
                width: 1
            })
        }),
        'MultiPoint': new ol.style.Style({
            image: new ol.style.Circle({
                fill: new ol.style.Fill({
                    color: 'rgba(255,0,255,0.5)'
                }),
                radius: 5,
                stroke: new ol.style.Stroke({
                    color: '#f0f',
                    width: 1
                })
            })
        }),
        'MultiLineString': new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#0f0',
                width: 3
            })
        }),
        'MultiPolygon': new ol.style.Style({
            fill: new ol.style.Fill({
                color: 'rgba(170,170,170,0.3)'
            }),
            stroke: new ol.style.Stroke({
                color: '#000000',
                width: 1
            })
        })
    };

    function openCsvOptions(type){
        /*document.getElementById("typecsv").value = type;
        if(type=='dsselectionquery'){
            if(dsselections.length!=0){
                var jsonSelections = JSON.stringify(dsselections);
            }
            else{
                alert("Please select records from the dataset to create CSV file.");
                return;
            }
        }
        else{
            var jsonSelections = JSON.stringify(selections);
        }
        document.getElementById("selectionscsv").value = jsonSelections;
        document.getElementById("starrcsv").value = starr;
        var urlStr = 'csvoptions.php?dltype=specimen';
        newWindow = window.open(urlStr,'popup','scrollbars=0,toolbar=1,resizable=1,width=650,height=650');
        if (newWindow.opener == null) newWindow.opener = self;
        return false;*/
    }

    function editLayers(c,title){
        var layer = c.value;
        if(c.checked == true){
            var layerName = '<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+layer;
            var layerSourceName = layer+'Source';
            layersArr[layerSourceName] = new ol.source.ImageWMS({
                url: '<?php echo $GEOSERVER_URL; ?>/<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>/wms',
                params: {'LAYERS':layerName},
                serverType: 'geoserver'
            });
            layersArr[layer] = new ol.layer.Image({
                source: layersArr[layerSourceName]
            });
            layersArr[layer].setOpacity(0.3);
            map.addLayer(layersArr[layer]);
            refreshLayerOrder();
            addLayerToSelList(layer,title);
        }
        else{
            map.removeLayer(layersArr[layer]);
            removeLayerToSelList(layer);
        }
    }

    var typeSelect = document.getElementById('drawselect');

    var mapView = new ol.View({
        zoom: 7,
        projection: 'EPSG:3857',
        minZoom: 3,
        maxZoom: 19,
        center: ol.proj.transform([-111.64808, 35.19397], 'EPSG:4326', 'EPSG:3857'),
    });

    var map = new ol.Map({
        view: mapView,
        target: 'map',
        controls: ol.control.defaults().extend([
            new ol.control.FullScreen()
        ]),
        layers: [
            layersArr['base'],
            layersArr['dragdrop1'],
            layersArr['dragdrop2'],
            layersArr['dragdrop3'],
            layersArr['select'],
            //layersArr['pointi'],
            layersArr['pointv'],
            layersArr['spider']
        ],
        overlays: [popupoverlay,finderpopupoverlay]
    });

    var coordFormat = function(){
        return(function(coord1){
            mouseCoords = coord1;
            if(coord1[0] < -180){coord1[0] = coord1[0] + 360};
            if(coord1[0] > 180){coord1[0] = coord1[0] - 360};
            var template = 'Lat: {y} Lon: {x}';
            var coord2 = [coord1[1], coord1[0]];
            return ol.coordinate.format(coord1,template,5);
        });
    };

    var mousePositionControl = new ol.control.MousePosition({
        coordinateFormat: coordFormat(),
        projection: 'EPSG:4326',
        className: 'custom-mouse-position',
        target: document.getElementById('mapcoords'),
        undefinedHTML: '&nbsp;'
    });

    map.addControl(zoomslider);
    map.addControl(scaleLineControl_us);
    map.addControl(scaleLineControl_metric);
    map.addControl(mousePositionControl);
    map.addInteraction(selectInteraction);
    map.addInteraction(pointInteraction);
    map.addInteraction(dragAndDropInteraction);

    var selectedFeatures = selectInteraction.getFeatures();
    var selectedPointFeatures = pointInteraction.getFeatures();

    selectedPointFeatures.on('add', function(event) {
        setSpatialParamBox();
        buildQueryStrings();
    });

    selectedPointFeatures.on('remove', function(event) {
        setSpatialParamBox();
        buildQueryStrings();
    });

    map.getView().on('change:resolution', function(event) {
        if(spiderCluster){
            layersArr['spider'].getSource().clear();
            hiddenClusters = [];
            spiderCluster = '';
        }
    });

    dragAndDropInteraction.on('addfeatures', function(event) {
        var filename = event.file.name.split('.');
        var fileType = filename.pop();
        filename = filename.join("");
        if(fileType == 'geojson' || fileType == 'kml'){
            if(setDragDropTarget()){
                var infoArr = [];
                infoArr['Name'] = dragDropTarget;
                infoArr['Title'] = filename;
                infoArr['Abstract'] = '';
                infoArr['DefaultCRS'] = '';
                var sourceIndex = dragDropTarget+'Source';
                layersArr[sourceIndex] = new ol.source.Vector({
                    features: event.features
                });
                layersArr[dragDropTarget].setStyle(getDragDropStyle);
                layersArr[dragDropTarget].setSource(layersArr[sourceIndex]);
                buildLayerTableRow(infoArr,true);
                map.getView().fit(layersArr[sourceIndex].getExtent());
                toggleLayerController();
            }
        }
        else{
            alert('The drag and drop file loading only supports GeoJSON and kml file formats.');
        }
    });

    pointInteraction.on('select', function(event) {
        var newfeatures = event.selected;
        var zoomLevel = map.getView().getZoom();
        if (newfeatures.length > 0) {
            if (zoomLevel < 17) {
                var extent = ol.extent.createEmpty();
                if (newfeatures.length > 1) {
                    for (n in newfeatures) {
                        var nfeature = newfeatures[n];
                        pointInteraction.getFeatures().remove(nfeature);
                        var cFeatures = nfeature.get('features');
                        for (f in cFeatures) {
                            ol.extent.extend(extent, cFeatures[f].getGeometry().getExtent());
                        }
                    }
                    map.getView().fit(extent, map.getSize());
                }
                else {
                    var newfeature = newfeatures[0];
                    pointInteraction.getFeatures().remove(newfeature);
                    if (newfeature.get('features')) {
                        var clusterCnt = newfeature.get('features').length;
                        if (clusterCnt > 1) {
                            var cFeatures = newfeature.get('features');
                            for (f in cFeatures) {
                                ol.extent.extend(extent, cFeatures[f].getGeometry().getExtent());
                            }
                            map.getView().fit(extent, map.getSize());
                        }
                        else {
                            processPointSelection(newfeature);
                        }
                    }
                    else {
                        processPointSelection(newfeature);
                    }
                }
            }
            else {
                if (newfeatures.length > 1) {
                    for (n in newfeatures) {
                        var nfeature = newfeatures[n];
                        pointInteraction.getFeatures().remove(nfeature);
                    }
                    spiderifyPoints(newfeatures);
                }
                else {
                    var newfeature = newfeatures[0];
                    pointInteraction.getFeatures().remove(newfeature);
                    if (newfeature.get('features')) {
                        var clusterCnt = newfeatures[0].get('features').length;
                        if (clusterCnt > 1) {
                            spiderifyPoints(newfeatures);
                        }
                        else {
                            processPointSelection(newfeature);
                        }
                    }
                    else {
                        processPointSelection(newfeature);
                    }
                }
            }
        }
    });

    selectedFeatures.on('add', function(event) {
        setSpatialParamBox();
        buildQueryStrings();
    });

    selectedFeatures.on('remove', function(event) {
        setSpatialParamBox();
        buildQueryStrings();
    });

    selectsource.on('change', function(event) {
        if(!draw){
            var featureCnt = selectsource.getFeatures().length;
            if(featureCnt > 0){
                if(!shapeActive){
                    var infoArr = [];
                    infoArr['Name'] = 'select';
                    infoArr['Title'] = 'Shapes';
                    infoArr['Abstract'] = '';
                    infoArr['DefaultCRS'] = '';
                    buildLayerTableRow(infoArr,true);
                    shapeActive = true;
                }
            }
            else{
                if(shapeActive){
                    removeLayerToSelList('select');
                    shapeActive = false;
                }
            }
        }
    });

    function loadPointWMSLayer(){
        //console.log(cqlString);
        layersArr['pointi'].getSource().updateParams({
            'LAYERS':'<?php echo $GEOSERVER_OCC_WORKSPACE; ?>:<?php echo $GEOSERVER_OCC_LAYER; ?>',
            'CRS':'EPSG:4326',
            'CQL_FILTER':cqlString
        });
    }

    function loadPointWFSLayer(index){
        pointvectorsource = new ol.source.Vector({
            loader: function(extent, resolution, projection) {
                var processed = 0;
                do{
                    lazyLoadPoints(index,function(res){
                        var format = new ol.format.GeoJSON();
                        var features = format.readFeatures(res, {
                            featureProjection: 'EPSG:3857'
                        });
                        primeSymbologyData(features);
                        pointvectorsource.addFeatures(features);
                        var pointextent = pointvectorsource.getExtent();
                        map.getView().fit(pointextent,map.getSize());
                    });
                    processed = processed + lazyLoadCnt;
                    index++;
                }
                while(processed < solrRecCnt);
            }
        });

        var clustersource = new ol.source.PropertyCluster({
            distance: 50,
            source: pointvectorsource,
            clusterkey: clusterKey,
            indexkey: 'occid',
            geometryFunction: function(feature){
                return feature.getGeometry();
            }
        });

        layersArr['pointv'].setStyle(getPointStyle);
        if(clusterPoints){
            layersArr['pointv'].setSource(clustersource);
        }
        else{
            layersArr['pointv'].setSource(pointvectorsource);
        }
    }

    map.on('singleclick', function(evt) {
        if(evt.originalEvent.altKey){
            var layerIndex = activeLayer+"Source";
            var viewResolution = /** @type {number} */ (mapView.getResolution());
            if(activeLayer != 'none' && activeLayer != 'select' && activeLayer != 'Points' && activeLayer != 'dragdrop1' && activeLayer != 'dragdrop2' && activeLayer != 'dragdrop3'){
                var url = layersArr[layerIndex].getGetFeatureInfoUrl(evt.coordinate,viewResolution,'EPSG:3857',{'INFO_FORMAT':'application/json'});
                if (url) {
                    $.ajax({
                        type: "GET",
                        url: url,
                        async: true
                    }).done(function(msg) {
                        if(msg){
                            var infoHTML = '';
                            var infoArr = msg['features'][0];
                            var propArr = infoArr['properties'];
                            //infoHTML += '<b>id:</b> '+infoArr['id']+'<br />';
                            //infoHTML += '<b>geometry:</b> '+infoArr['geometry']+'<br />';
                            for(var key in propArr){
                                infoHTML += '<b>'+key+':</b> '+propArr[key]+'<br />';
                            }
                            popupcontent.innerHTML = infoHTML;
                            popupoverlay.setPosition(evt.coordinate);
                        }
                    });
                }
            }
            else if(activeLayer == 'dragdrop1' || activeLayer == 'dragdrop2' || activeLayer == 'dragdrop3'){
                var infoHTML = '';
                var feature = map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
                    if(layer === layersArr[activeLayer]){
                        return feature;
                    }
                });
                if(feature){
                    var properties = feature.getKeys();
                    for(i in properties){
                        if(String(properties[i]) != 'geometry'){
                            infoHTML += '<b>'+properties[i]+':</b> '+feature.get(properties[i])+'<br />';
                        }
                    }
                    popupcontent.innerHTML = infoHTML;
                    popupoverlay.setPosition(evt.coordinate);
                }
            }
            else if(activeLayer == 'Points'){
                var infoHTML = '';
                var clickedFeatures = [];
                map.forEachFeatureAtPixel(evt.pixel, function(feature, layer){
                    if(layer === layersArr['spider'] || layer === layersArr['pointv']){
                        if(feature.get('features') || feature.get('occid')){
                            clickedFeatures.push(feature);
                        }
                    }
                });
                if(clickedFeatures.length == 1 && clickedFeatures[0].get('features').length == 1){
                    var iFeature = (clusterPoints?clickedFeatures[0].get('features')[0]:clickedFeatures[0]);
                    infoHTML += '<b>occid:</b> '+iFeature.get('occid')+'<br />';
                    infoHTML += '<b>CollectionName:</b> '+(iFeature.get('CollectionName')?iFeature.get('CollectionName'):'')+'<br />';
                    infoHTML += '<b>catalogNumber:</b> '+(iFeature.get('catalogNumber')?iFeature.get('catalogNumber'):'')+'<br />';
                    infoHTML += '<b>otherCatalogNumbers:</b> '+(iFeature.get('otherCatalogNumbers')?iFeature.get('otherCatalogNumbers'):'')+'<br />';
                    infoHTML += '<b>family:</b> '+(iFeature.get('family')?iFeature.get('family'):'')+'<br />';
                    infoHTML += '<b>sciname:</b> '+(iFeature.get('sciname')?iFeature.get('sciname'):'')+'<br />';
                    infoHTML += '<b>recordedBy:</b> '+(iFeature.get('recordedBy')?iFeature.get('recordedBy'):'')+'<br />';
                    infoHTML += '<b>recordNumber:</b> '+(iFeature.get('recordNumber')?iFeature.get('recordNumber'):'')+'<br />';
                    infoHTML += '<b>eventDate:</b> '+(iFeature.get('displayDate')?iFeature.get('displayDate'):'')+'<br />';
                    infoHTML += '<b>habitat:</b> '+(iFeature.get('habitat')?iFeature.get('habitat'):'')+'<br />';
                    infoHTML += '<b>associatedTaxa:</b> '+(iFeature.get('associatedTaxa')?iFeature.get('associatedTaxa'):'')+'<br />';
                    infoHTML += '<b>country:</b> '+(iFeature.get('country')?iFeature.get('country'):'')+'<br />';
                    infoHTML += '<b>StateProvince:</b> '+(iFeature.get('StateProvince')?iFeature.get('StateProvince'):'')+'<br />';
                    infoHTML += '<b>county:</b> '+(iFeature.get('county')?iFeature.get('county'):'')+'<br />';
                    infoHTML += '<b>locality:</b> '+(iFeature.get('locality')?iFeature.get('locality'):'')+'<br />';
                    if(iFeature.get('thumbnailurl')){
                        var thumburl = iFeature.get('thumbnailurl');
                        infoHTML += '<img src="'+thumburl+'" style="height:150px" />';
                    }
                    popupcontent.innerHTML = infoHTML;
                    popupoverlay.setPosition(evt.coordinate);
                }
                else if(clickedFeatures.length > 1 || clickedFeatures[0].get('features').length > 1){
                    alert('You clicked on multiple points. Info window can only display data for a single point.')
                }
            }
        }
    });

    map.on('dblclick', function(evt) {
        var layerIndex = activeLayer+"Source";
        if(activeLayer != 'none' && activeLayer != 'select' && activeLayer != 'Points'){
            if(activeLayer == 'dragdrop1' || activeLayer == 'dragdrop2' || activeLayer == 'dragdrop3'){
                map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
                    selectsource.addFeature(feature);
                }, null, function(layer) {
                    return (layer === layersArr[activeLayer]);
                });
            }
            else{
                var viewResolution = /** @type {number} */ (mapView.getResolution());
                var url = layersArr[layerIndex].getGetFeatureInfoUrl(evt.coordinate, viewResolution, 'EPSG:3857', {'INFO_FORMAT': 'application/json'});
                selectObjectFromID(url, activeLayer);
            }
        }
        return false;
    });

    function selectObjectFromID(url,selectLayer){
        $.ajax({
            type: "GET",
            url: url,
            async: true
        }).done(function(msg) {
            if(msg){
                var infoArr = msg['features'][0];
                var objID = infoArr['id'];
                var url = '<?php echo $GEOSERVER_URL; ?>/<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>/wfs?service=WFS&version=1.1.0&request=GetFeature&typename=<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+selectLayer+'&featureid='+objID+'&outputFormat=application/json&srsname=EPSG:3857';
                $.get( url, function( data ) {
                    var features = new ol.format.GeoJSON().readFeatures(data);
                    selectsource.addFeatures(features);
                });
            }
        });
    }

    typeSelect.onchange = function() {
        map.removeInteraction(draw);
        changeDraw();
    };

    changeDraw();
</script>

<!-- Add Layers -->
<div id="addLayers" data-role="popup" class="well" style="width:600px;height:90%;">
    <a class="boxclose addLayers_close" id="boxclose"></a>
    <div style="height:100%;overflow-y: scroll;padding:30px;">
        <table id='layercontroltable' class="styledtable" style="font-family:Arial;font-size:12px;margin-left:-15px;width:530px;">
            <tbody id="layerselecttbody"></tbody>
        </table>
    </div>
</div>
</body>
</html>