<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpatialModuleManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$mapCenter = '[-110.90713, 32.21976]';
if(isset($SPATIAL_INITIAL_CENTER)) $mapCenter = $SPATIAL_INITIAL_CENTER;
$mapZoom = 7;
if(isset($SPATIAL_INITIAL_ZOOM)) $mapZoom = $SPATIAL_INITIAL_ZOOM;

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
    <link href="<?php echo $CLIENT_ROOT; ?>/css/spatialbase.css?ver=15" type="text/css" rel="stylesheet" />
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.mobile-1.4.0.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-1.9.1.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui-1.10.4.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.popupoverlay.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/ol-symbiota-ext.js?ver=29" type="text/javascript"></script>
    <script src="https://npmcdn.com/@turf/turf@5.0.4/turf.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jszip.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jscolor/jscolor.js?ver=2" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/stream.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/shapefile.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/dbf.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/FileSaver.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/html2canvas.min.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/symb/spatial.module.js?ver=248" type="text/javascript"></script>
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
            $('#vectortoolstab').tabs({
                beforeLoad: function( event, ui ) {
                    $(ui.panel).html("<p>Loading...</p>");
                }
            });
            $('#addLayers').popup({
                transition: 'all 0.3s',
                scrolllock: true
            });
            $('#csvoptions').popup({
                transition: 'all 0.3s',
                scrolllock: true
            });
            $('#mapsettings').popup({
                transition: 'all 0.3s',
                scrolllock: true
            });
            $('#maptools').popup({
                transition: 'all 0.3s',
                scrolllock: true
            });
            $('#reclassifytool').popup({
                transition: 'all 0.3s',
                scrolllock: true,
                blur: false
            });
            $('#rastercalctool').popup({
                transition: 'all 0.3s',
                scrolllock: true,
                blur: false
            });
            $('#vectorizeoverlaytool').popup({
                transition: 'all 0.3s',
                scrolllock: true,
                blur: false
            });
            $('#loadingOverlay').popup({
                transition: 'all 0.3s',
                scrolllock: true,
                opacity:0.6,
                color:'white',
                blur: false
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
    <div data-role="panel" data-dismissible=false class="overflow:hidden;" id="defaultpanel" data-swipe-close=false data-position="left" data-display="overlay" >
        <div class="panel-content">
            <div id="spatialpanel">
                <div id="accordion">
                    <h3 class="tabtitle">Search Criteria</h3>
                    <div id="criteriatab">
                        <ul>
                            <li><a class="tabtitle" href="#searchcriteria">Criteria</a></li>
                            <li><a class="tabtitle" href="#searchcollections">Collections</a></li>
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
                                <div style="margin-top:5px;">
                                    <input data-role="none" type='checkbox' name='hasgenetic' id='hasgenetic' value='1' onchange="buildQueryStrings();"> Limit to Specimens with Genetic Data Only
                                </div>
                                <div><hr></div>
                            </div>
                        </div>
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
                            <div style="margin-bottom:15px;">
                                <div style="float:left;margin-top:20px;">
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
                                        <button data-role="none" id="symbolizeReset1" onclick='resetSymbology();' >Reset Symbology</button>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <button data-role="none" id="randomColorColl" onclick='autoColorColl();' >Auto Color</button>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <button data-role="none" id="saveCollKeyImage" onclick='saveKeyImage();' >Save Image</button>
                                    </div>
                                </div>
                            </div>
                            <div style="margin:5 0 5 0;clear:both;"><hr /></div>
                            <div id="collSymbologyKey" style="background-color:white;">
                                <div style="margin-top:8px;">
                                    <div style="display:table;">
                                        <div id="symbologykeysbox"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="queryrecordsdiv" style="">
                            <div style="margin-top:-10px;margin-right:10px;">
                                <fieldset style="border:1px solid black;height:50px;width:360px;margin-left:-10px;padding-top:3px;">
                                    <legend><b>Download</b></legend>
                                    <div style="height:25px;width:330px;margin-left:auto;margin-right:auto;">
                                        <div style="float:left;">
                                            <select data-role="none" id="querydownloadselect">
                                                <option value="">Download Type</option>
                                                <option value="csv">CSV</option>
                                                <option value="kml">KML</option>
                                                <option value="geojson">GeoJSON</option>
                                                <option value="gpx">GPX</option>
                                                <option value="png">Map PNG Image</option>
                                            </select>
                                        </div>
                                        <div style="float:right;">
                                            <button data-role="none" type="button" onclick='processDownloadRequest(false);' >Download</button>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div id="queryrecords" style=""></div>
                        </div>
                        <div id="maptaxalist" >
                            <div style="margin-bottom:15px;">
                                <div style="float:left;margin-top:20px;">
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
                                        <button data-role="none" id="symbolizeReset2" onclick='resetSymbology();' >Reset Symbology</button>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <button data-role="none" id="randomColorTaxa" onclick='autoColorTaxa();' >Auto Color</button>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <button data-role="none" id="saveTaxaKeyImage" onclick='saveKeyImage();' >Save Image</button>
                                    </div>
                                </div>
                            </div>
                            <div style="margin:5 0 5 0;clear:both;"><hr /></div>
                            <div style="margin-bottom:30px;">
                                <div style='font-weight:bold;float:left;margin-bottom:5px;'>Taxa Count: <span id="taxaCountNum">0</span></div>
                                <div style="float:right;margin-bottom:5px;">
                                    <button data-role="none" id="taxacsvdownload" onclick="exportTaxaCSV();" >Download CSV</button>
                                </div>
                            </div>
                            <div style="margin:5 0 5 0;clear:both;"><hr /></div>
                            <div id="taxasymbologykeysbox" style="background-color:white;"></div>
                        </div>

                        <div id="selectionslist" style="">
                            <div>
                                <div style="margin-top:-10px;margin-right:10px;">
                                    <fieldset style="border:1px solid black;height:50px;width:360px;margin-left:-10px;padding-top:3px;">
                                        <legend><b>Download</b></legend>
                                        <div style="height:25px;width:330px;margin-left:auto;margin-right:auto;">
                                            <div style="float:left;">
                                                <select data-role="none" id="selectdownloadselect">
                                                    <option value="">Download Type</option>
                                                    <option value="csv">CSV</option>
                                                    <option value="kml">KML</option>
                                                    <option value="geojson">GeoJSON</option>
                                                    <option value="gpx">GPX</option>
                                                </select>
                                            </div>
                                            <div style="float:right;">
                                                <button data-role="none" name="submitaction" type="button" onclick='processDownloadRequest(true);' >Download</button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div style="margin-top:10px;">
                                    <div style="float:left;">
                                        <div>
                                            <button data-role="none" id="clearselectionsbut" onclick='clearSelections();' >Clear Selections</button>
                                        </div>
                                    </div>
                                    <div id="" style='margin-right:15px;float:right;' >
                                        <div>
                                            <button data-role="none" id="zoomtoselectionsbut" onclick='zoomToSelections();' >Zoom to Selections</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="clear:both;height:10px;"></div>
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

                    <h3 class="tabtitle">Vector Tools</h3>
                    <div id="vectortoolstab" style="width:379px;padding:0px;">
                        <ul>
                            <li><a class="tabtitle" href="#polycalculatortab">Shapes</a></li>
                            <li><a class="tabtitle" href="#pointscalculatortab">Points</a></li>
                        </ul>
                        <div id="polycalculatortab" style="width:379px;padding:0px;">
                            <div style="padding:10px">
                                <div style="height:45px;">
                                    <div style="float:right;">
                                        Total area of selected shapes (sq/km)
                                    </div>
                                    <div style="float:right;margin-top:5px;">
                                        <input data-role="none" type="text" id="polyarea" style="width:250px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="0" disabled />
                                    </div>
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div style="margin-top:10px;">
                                    <b>Download Shapes</b> <select data-role="none" id="shapesdownloadselect">
                                        <option value="">Download Type</option>
                                        <option value="kml">KML</option>
                                        <option value="geojson">GeoJSON</option>
                                    </select>
                                    <button data-role="none" style="margin-left:5px;" type="button" onclick='downloadShapesLayer();' >Download</button>
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div style="margin-top:10px;">
                                    <button data-role="none" onclick="createBuffers();" >Buffer</button> Creates buffer polygon of <input data-role="none" type="text" id="bufferSize" style="width:50px;" value="" /> km around selected features.
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div style="margin-top:10px;">
                                    <button data-role="none" onclick="createPolyDifference();" >Difference</button> Returns a new polygon with the area of the polygon or circle selected first, exluding the area of the polygon or circle selected second.
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div style="margin-top:10px;">
                                    <button data-role="none" onclick="createPolyIntersect();" >Intersect</button> Returns a new polygon with the area overlapping of both selected polygons or circles.
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div style="margin-top:10px;">
                                    <button data-role="none" onclick="createPolyUnion();" >Union</button> Returns a new polygon with the combined area of two or more selected polygons or circles. *Note new polygon will replace all selected shapes.
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                            </div>
                        </div>

                        <div id="pointscalculatortab" style="width:379px;padding:0px;">
                            <div id="pointToolsNoneDiv" style="padding:10px;margin-top:10px;display:block;">
                                There are no points loaded on the map.
                            </div>
                            <div id="pointToolsDiv" style="padding:10px;display:none;">
                                <div style="">
                                    <button data-role="none" onclick="createConcavePoly();" >Concave Hull Polygon</button> Creates a concave hull polygon or multipolygon for
                                    <select data-role="none" id="concavepolysource" style="margin-top:3px;" onchange="checkPointToolSource('concavepolysource');">
                                        <option value="all">all</option>
                                        <option value="selected">selected</option>
                                    </select> points with a maximum edge length of <input data-role="none" type="text" id="concaveMaxEdgeSize" style="width:75px;margin-top:3px;" value="" /> kilometers.
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                                <div style="margin-top:10px;">
                                    <button data-role="none" onclick="createConvexPoly();" >Convex Hull Polygon</button> Creates a convex hull polygon for
                                    <select data-role="none" id="convexpolysource" style="margin-top:3px;" onchange="checkPointToolSource('convexpolysource');">
                                        <option value="all">all</option>
                                        <option value="selected">selected</option>
                                    </select> points.
                                </div>
                                <div style="margin:5 0 5 0;"><hr /></div>
                            </div>
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
            <span class="maptext">Draw</span>
            <select id="drawselect">
                <option value="None">None</option>
                <option value="Polygon">Polygon</option>
                <option value="Circle">Circle</option>
                <option value="LineString">Line</option>
                <option value="Point">Point</option>
            </select>
        </div>
        <div id="basecontrol">
            <span class="maptext">Base Layer</span>
            <select data-role="none" id="base-map" onchange="changeBaseMap();">
                <option value="worldtopo">ESRI World Topo</option>
                <option value="openstreet">OpenStreetMap</option>
                <option value="blackwhite">Stamen Design Black &amp; White</option>
                <option value="worldimagery">ESRI World Imagery</option>
                <option value="ocean">ESRI Ocean</option>
                <option value="ngstopo">National Geographic Topo</option>
                <option value="natgeoworld">National Geographic World</option>
                <option value="esristreet">ESRI StreetMap</option>
            </select>
        </div>
        <div style="clear:both;"></div>
        <div id="selectcontrol">
            <span class="maptext">Active Layer</span>
            <select id="selectlayerselect" onchange="setActiveLayer();">
                <option id="lsel-none" value="none">None</option>
            </select>
        </div>
        <div style="clear:both;"></div>
        <div id="settingsLink" style="margin-left:22px;float:left;">
            <span class="maptext"><a class="mapsettings_open" href="#mapsettings"><b>Settings</b></a></span>
        </div>
        <div id="toolsLink" style="margin-left:22px;float:left;">
            <span class="maptext"><a class="maptools_open" href="#maptools"><b>Tools</b></a></span>
        </div>
        <div id="layerControllerLink" style="margin-left:22px;float:left;">
            <span class="maptext"><a class="addLayers_open" href="#addLayers"><b>Layers</b></a></span>
        </div>
        <div id="deleteSelections" style="margin-left:60px;float:left;">
            <button data-role="none" type="button" onclick='deleteSelections();' >Delete Shapes</button>
        </div>
        <div style="clear:both;"></div>
        <div id="dateslidercontrol" style="margin-top:5px;display:none;">
            <div style="margin:5 0 5 0;color:white;"><hr /></div>
            <div id="setdatediv" style="">
                <span class="maptext">Earliest</span>
                <input data-role="none" type="text" id="datesliderearlydate" style="width:100px;margin-right:5px;" value="" onchange="checkDSLowDate();" />
                <span class="maptext">Latest</span>
                <input data-role="none" type="text" id="datesliderlatedate" style="width:100px;margin-right:25px;" value="" onchange="checkDSHighDate();" />
                <button data-role="none" type="button" onclick="setDSValues();" >Set</button>
            </div>
            <div style="margin:5 0 5 0;color:white;"><hr /></div>
            <div id="animatediv" style="">
                <div>
                    <span class="maptext">Interval Duration (years)</span>
                    <input data-role="none" type="text" id="datesliderinterduration" style="width:40px;margin-right:5px;" value="" onchange="checkDSAnimDuration();" />
                    <span class="maptext">Interval Time (seconds)</span>
                    <input data-role="none" type="text" id="datesliderintertime" style="width:40px;margin-right:10px;" value="" onchange="checkDSAnimTime();" />
                </div>
                <div style="clear:both;"></div>
                <div style="margin-top:3px;">
                    <div style="float:left;">
                        <span style="margin-right:5px;">
                            <span class="maptext">Save Images</span>
                            <input data-role="none" type='checkbox' id='dateslideranimimagesave' onchange="checkDSSaveImage();" value='1'>
                        </span>
                        <span style="margin-right:5px;">
                            <span class="maptext">Reverse</span>
                            <input data-role="none" type='checkbox' id='dateslideranimreverse' value='1'>
                        </span>
                        <span>
                            <span class="maptext">Dual</span>
                            <input data-role="none" type='checkbox' id='dateslideranimdual' value='1'>
                        </span>
                    </div>
                    <div style="float:right;">
                        <button data-role="none" type="button" onclick="setDSAnimation();" >Start</button>
                        <button data-role="none" type="button" onclick="stopDSAnimation();" >Stop</button>
                    </div>
                </div>
                <div style="clear:both;"></div>
            </div>
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
    var clustersource;
    var taxaArr = [];
    var taxontype = '';
    var thes = false;
    var loadVectorPoints = true;
    var loadPointsEvent = false;
    var taxaCnt = 0;
    var lazyLoadCnt = 20000;
    var clusterDistance = 50;
    var clusterPoints = true;
    var showHeatMap = false;
    var heatMapRadius = 5;
    var heatMapBlur = 15;
    var mapSymbology = 'coll';
    var clusterKey = 'CollectionName';
    var maxFeatureCount;
    var currentResolution;
    var activeLayer = 'none';
    var shapeActive = false;
    var pointActive = false;
    var spiderCluster;
    var spiderFeature;
    var hiddenClusters = [];
    var clickedFeatures = [];
    var dragDrop1 = false;
    var dragDrop2 = false;
    var dragDrop3 = false;
    var dragDropTarget = '';
    var droppedShapefile = '';
    var droppedDBF = '';
    var dsOldestDate = '';
    var dsNewestDate = '';
    var tsOldestDate = '';
    var tsNewestDate = '';
    var dateSliderActive = false;
    var sliderdiv = '';
    var rasterLayers = [];
    var overlayLayers = [];
    var vectorizeLayers = [];
    var loadingTimer = 0;
    var loadingComplete = true;
    var returnClusters = false;
    var dsAnimDuration = '';
    var dsAnimTime = '';
    var dsAnimImageSave = false;
    var dsAnimReverse = false;
    var dsAnimDual = false;
    var dsAnimLow = '';
    var dsAnimHigh = '';
    var dsAnimStop = true;
    var dsAnimation = '';
    var zipFile = '';
    var zipFolder = '';
    var SOLRFields = 'occid,collid,catalogNumber,otherCatalogNumbers,family,sciname,tidinterpreted,scientificNameAuthorship,identifiedBy,' +
        'dateIdentified,typeStatus,recordedBy,recordNumber,eventDate,displayDate,coll_year,coll_month,coll_day,habitat,associatedTaxa,' +
        'cultivationStatus,country,StateProvince,county,municipality,locality,localitySecurity,localitySecurityReason,geo,minimumElevationInMeters,' +
        'maximumElevationInMeters,labelProject,InstitutionCode,CollectionCode,CollectionName,CollType,thumbnailurl,accFamily';
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

    var popupcontainer = document.getElementById('popup');
    var popupcontent = document.getElementById('popup-content');
    var popupcloser = document.getElementById('popup-closer');
    var finderpopupcontainer = document.getElementById('finderpopup');
    var finderpopupcontent = document.getElementById('finderpopup-content');
    var finderpopupcloser = document.getElementById('finderpopup-closer');
    var typeSelect = document.getElementById('drawselect');

    var popupoverlay = new ol.Overlay({
        element: popupcontainer,
        autoPan: true,
        autoPanAnimation: {
            duration: 250
        }
    });

    popupcloser.onclick = function() {
        popupoverlay.setPosition(undefined);
        popupcloser.blur();
        return false;
    };

    var finderpopupoverlay = new ol.Overlay({
        element: finderpopupcontainer,
        autoPan: true,
        autoPanAnimation: {
            duration: 250
        }
    });

    finderpopupcloser.onclick = function(){
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

    var atlasManager = new ol.style.AtlasManager();

    var baselayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
            crossOrigin: 'anonymous'
        })
    });

    var selectsource = new ol.source.Vector({wrapX: false});
    var selectlayer = new ol.layer.Vector({
        source: selectsource,
        style: new ol.style.Style({
            fill: new ol.style.Fill({
                color: 'rgba(255,255,255,0.4)'
            }),
            stroke: new ol.style.Stroke({
                color: '#3399CC',
                width: 2
            }),
            image: new ol.style.Circle({
                radius: 7,
                stroke: new ol.style.Stroke({
                    color: '#3399CC',
                    width: 2
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255,255,255,0.4)'
                })
            })
        })
    });

    var pointvectorsource = new ol.source.Vector({wrapX: false});
    var pointvectorlayer = new ol.layer.Vector({
        source: pointvectorsource
    });

    var heatmaplayer = new ol.layer.Heatmap({
        source: pointvectorsource,
        weight: function(feature){
            var showPoint = true;
            if(dateSliderActive) showPoint = validateFeatureDate(feature);
            if(showPoint){
                return 1;
            }
            else{
                return 0;
            }
        },
        gradient: ['#00f','#0ff','#0f0','#ff0','#f00'],
        blur: parseInt(heatMapBlur, 10),
        radius: parseInt(heatMapRadius, 10),
        visible: false
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
    layersArr['heat'] = heatmaplayer;
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
            return (evt.type == 'click' && activeLayer == 'select' && !evt.originalEvent.altKey);
        },
        style: new ol.style.Style({
            fill: new ol.style.Fill({
                color: 'rgba(255,255,255,0.5)'
            }),
            stroke: new ol.style.Stroke({
                color: 'rgba(0,153,255,1)',
                width: 5
            }),
            image: new ol.style.Circle({
                radius: 7,
                stroke: new ol.style.Stroke({
                    color: 'rgba(0,153,255,1)',
                    width: 2
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(0,153,255,1)'
                })
            })
        }),
        toggleCondition: ol.events.condition.click
    });

    var pointInteraction = new ol.interaction.Select({
        layers: [layersArr['pointv'],layersArr['spider']],
        condition: function(evt) {
            if(evt.type == 'click' && activeLayer == 'pointv'){
                if(!evt.originalEvent.altKey){
                    if(spiderCluster){
                        var spiderclick = map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
                            spiderFeature = feature;
                            if(feature && layer === layersArr['spider']){
                                return feature;
                            }
                        });
                        if(!spiderclick){
                            var blankSource = new ol.source.Vector({
                                features: new ol.Collection(),
                                useSpatialIndex: true
                            });
                            layersArr['spider'].setSource(blankSource);
                            for(i in hiddenClusters){
                                showFeature(hiddenClusters[i]);
                            }
                            hiddenClusters = [];
                            spiderCluster = false;
                            spiderFeature = '';
                            layersArr['pointv'].getSource().changed();
                        }
                    }
                    return true;
                }
                else if(evt.originalEvent.altKey){
                    map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
                        if(feature){
                            if(spiderCluster && layer === layersArr['spider']){
                                clickedFeatures.push(feature);
                                return feature;
                            }
                            else if(layer === layersArr['pointv']){
                                clickedFeatures.push(feature);
                                return feature;
                            }
                        }
                    });
                    return false;
                }
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

    function editVectorLayers(c,title){
        var layer = c.value;
        if(c.checked == true){
            var layerName = '<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+layer;
            var layerSourceName = layer+'Source';
            layersArr[layerSourceName] = new ol.source.ImageWMS({
                url: 'rpc/GeoServerConnector.php',
                params: {'LAYERS':layerName, 'datatype':'vector'},
                serverType: 'geoserver',
                crossOrigin: 'anonymous',
                imageLoadFunction: function(image, src) {
                    imagePostFunction(image, src);
                }
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

    function vectorizeRaster(){
        showWorking();
        var overlay = document.getElementById("vectorizesourcelayer").value;
        var overlaySource = overlayLayers[overlay]['source'];
        var overlayName = '<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+overlay;
        var features = selectInteraction.getFeatures().getArray();
        var boundsFeature = features[0].clone();
        var geoJSONFormat = new ol.format.GeoJSON();
        var geometry = boundsFeature.getGeometry();
        var fixedgeometry = geometry.transform(mapProjection,wgs84Projection);
        var geojsonStr = geoJSONFormat.writeGeometry(fixedgeometry);
        var xmlContent = generateWPSPolyExtractXML(overlayLayers[overlay]['values'],overlaySource,geojsonStr);
        var http = new XMLHttpRequest();
        var url = "rpc/GeoServerConnector.php";
        var params = 'REQUEST=wps&xmlrequest='+xmlContent;
        //console.log(url+'?'+params);
        http.open("POST", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function() {
            if(http.readyState == 4 && http.status == 200) {
                //console.log(http.responseText);
                var features = geoJSONFormat.readFeatures(http.responseText, {
                    featureProjection: 'EPSG:3857'
                });
                selectsource.addFeatures(features);
                document.getElementById("selectlayerselect").value = 'select';
                setActiveLayer();
            }
            hideWorking();
        };
        http.send(params);
    }

    function checkRasterCalcForm(){
        var outputName = document.getElementById("rastercalcOutputName").value;
        var layer1 = document.getElementById("rastcalcoverlay1").value;
        var operator = document.getElementById("rastcalcoperator").value;
        var layer2 = document.getElementById("rastcalcoverlay2").value;
        var colorVal = document.getElementById("rastcalccolor").value;
        if(layer1 == "" || layer2 == "") alert("Please select overlay layers to calculate.");
        else if(outputName == "") alert("Please enter a name for the output overlay.");
        else if(layersArr[outputName]) alert("The name for the output you entered is already being used by another layer. Please enter a different name.");
        else if(operator == "") alert("Please select operator for calculation.");
        else if(colorVal == "FFFFFF") alert("Please select a color other than white for this overlay.");
        else{
            $("#rastercalctool").popup("hide");
            calculateRasters();
        }
    }

    function calculateRasters(){
        var layer1 = document.getElementById("rastcalcoverlay1").value;
        var layer2 = document.getElementById("rastcalcoverlay2").value;
        var operator = document.getElementById("rastcalcoperator").value;
        var hexColor = document.getElementById("rastcalccolor").value;
        var rgbColorArr = hexToRgb('#'+hexColor);
        var outputName = document.getElementById("rastercalcOutputName").value;
        outputName = outputName.replace(" ","_");
        overlayLayers[outputName] = [];
        overlayLayers[outputName]['id'] = outputName;
        overlayLayers[outputName]['title'] = outputName;
        overlayLayers[outputName]['source'] = '';

        var layerRasterSourceName = outputName+'RasterSource';
        layersArr[layerRasterSourceName] = new ol.source.Raster({
            sources: [layersArr[layer1].getSource(), layersArr[layer2].getSource()],
            operationType: 'pixel',
            operation: function (pixels, data) {
                var operator = data.operator;
                var rgbarr = data.rgbarr;
                var value1 = pixels[0][4];
                var value2 = pixels[1][4];
                if(operator == '+') var result = value1+value2;
                else if(operator == '-') var result = value1-value2;
                else if(operator == '*') var result = value1*value2;
                else if(operator == '/') var result = value1/value2;
                if(result > 0){
                    inputPixel[0] = 123; //rgbarr['r'];
                    inputPixel[1] = 203; //rgbarr['g'];
                    inputPixel[2] = 122; //rgbarr['b'];
                    inputPixel[3] = 255;
                    inputPixel[4] = result;
                    return inputPixel;
                }
                return [0, 0, 0, 0, 0];
            },
            beforeoperations: function(event) {
                event.data['operator'] = operator;
                event.data['rgbarr'] = rgbColorArr;
            }
        });
        layersArr[outputName] = new ol.layer.Image({
            source: layersArr[layerRasterSourceName]
        });

        layersArr[outputName].setOpacity(0.4);
        map.addLayer(layersArr[outputName]);
        refreshLayerOrder();
        var infoArr = [];
        infoArr['Name'] = outputName;
        infoArr['layerType'] = 'raster';
        infoArr['Title'] = outputName;
        infoArr['Abstract'] = '';
        infoArr['DefaultCRS'] = '';
        buildLayerTableRow(infoArr,true);
    }

    function clearRasterCalcForm() {
        document.getElementById("rastcalcoverlay1").selectedIndex = 0;
        document.getElementById("rastcalcoperator").selectedIndex = 0;
        document.getElementById("rastcalcoverlay2").selectedIndex = 0;
        document.getElementById("rastcalccolor").value = "FFFFFF";
    }

    function reclassifyRaster(){
        var rasterLayer = document.getElementById("reclassifysourcelayer").value;
        var outputName = document.getElementById("reclassifyOutputName").value;
        outputName = outputName.replace(" ","_");
        overlayLayers[outputName] = [];
        overlayLayers[outputName]['id'] = outputName;
        overlayLayers[outputName]['title'] = outputName;
        overlayLayers[outputName]['source'] = rasterLayer;
        overlayLayers[outputName]['values'] = [];
        overlayLayers[outputName]['values']['rasmin'] = document.getElementById('reclassifyRasterMin').value;
        overlayLayers[outputName]['values']['rasmax'] = document.getElementById('reclassifyRasterMax').value;
        overlayLayers[outputName]['values']['color'] = document.getElementById('reclassifyColorVal').value;

        var layerName = '<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+rasterLayer;
        var layerTileSourceName = outputName+'Source';
        var layerRasterSourceName = outputName+'RasterSource';
        var sldContent = generateReclassifySLD(overlayLayers[outputName]['values'],layerName);
        layersArr[layerTileSourceName] = new ol.source.TileWMS({
            url: 'rpc/GeoServerConnector.php',
            params: {'LAYERS':layerName, 'STYLES':'reclassify_style', 'SLD_BODY':sldContent, 'datatype':'raster'},
            serverType: 'geoserver',
            crossOrigin: 'anonymous',
            imageLoadFunction: function(image, src) {
                imagePostFunction(image, src);
            }
        });
        layersArr[layerRasterSourceName] = new ol.source.Raster({
            sources: [layersArr[layerTileSourceName]],
            operationType: 'pixel',
            operation: function (pixels, data) {
                var inputPixel = pixels[0];
                if((inputPixel[0] && inputPixel[1] && inputPixel[2])){
                    var pixr = inputPixel[0];
                    var pixg = inputPixel[1];
                    var pixb = inputPixel[2];
                    if(pixr == 255 && pixg == 255 && pixb == 255){
                        return [0, 0, 0, 0];
                    }
                    else if(pixr == 0 && pixg == 0 && pixb == 0){
                        return [0, 0, 0, 0];
                    }
                    else{
                        return inputPixel;
                    }
                }
                return [0, 0, 0, 0];
            }
        });
        layersArr[outputName] = new ol.layer.Image({
            source: layersArr[layerRasterSourceName]
        });

        layersArr[outputName].setOpacity(0.4);
        map.addLayer(layersArr[outputName]);
        refreshLayerOrder();
        var infoArr = [];
        infoArr['Name'] = outputName;
        infoArr['raster'] = 'vector';
        infoArr['Title'] = outputName;
        infoArr['Abstract'] = '';
        infoArr['DefaultCRS'] = '';
        buildLayerTableRow(infoArr,true);
        vectorizeLayers[outputName] = outputName;
    }

    function editRasterLayers(c,title){
        var layer = c.value;
        if(c.checked == true){
            var layerName = '<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+layer;
            var layerTileSourceName = layer+'Source';
            var layerRasterSourceName = layer+'RasterSource';
            layersArr[layerTileSourceName] = new ol.source.TileWMS({
                url: 'rpc/GeoServerConnector.php',
                params: {'LAYERS':layerName, 'datatype':'raster'},
                serverType: 'geoserver',
                crossOrigin: 'anonymous',
                imageLoadFunction: function(image, src) {
                    imagePostFunction(image, src);
                }
            });
            layersArr[layerRasterSourceName] = new ol.source.Raster({
                sources: [layersArr[layerTileSourceName]],
                operationType: 'pixel',
                operation: function (pixels, data) {
                    return pixels[0];
                }
            });
            layersArr[layer] = new ol.layer.Image({
                source: layersArr[layerRasterSourceName]
            });

            layersArr[layer].setOpacity(0.4);
            map.addLayer(layersArr[layer]);
            refreshLayerOrder();
            addLayerToSelList(layer,title);
        }
        else{
            map.removeLayer(layersArr[layer]);
            removeLayerToSelList(layer);
        }
    }

    var mapView = new ol.View({
        zoom: <?php echo $mapZoom; ?>,
        projection: 'EPSG:3857',
        minZoom: 2.5,
        maxZoom: 19,
        center: ol.proj.transform(<?php echo $mapCenter; ?>, 'EPSG:4326', 'EPSG:3857'),
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
            layersArr['pointv'],
            layersArr['heat'],
            layersArr['spider']
        ],
        overlays: [popupoverlay,finderpopupoverlay],
        renderer: 'canvas'
    });

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
            var source = layersArr['spider'].getSource();
            source.clear();
            var blankSource = new ol.source.Vector({
                features: new ol.Collection(),
                useSpatialIndex: true
            });
            layersArr['spider'].setSource(blankSource);
            for(i in hiddenClusters){
                showFeature(hiddenClusters[i]);
            }
            hiddenClusters = [];
            spiderCluster = '';
            layersArr['pointv'].getSource().changed();
        }
    });

    dragAndDropInteraction.on('addfeatures', function(event) {
        var filename = event.file.name.split('.');
        var fileType = filename.pop();
        filename = filename.join("");
        if(fileType == 'geojson' || fileType == 'kml' || fileType == 'shp' || fileType == 'dbf'){
            if(fileType == 'geojson' || fileType == 'kml'){
                if(setDragDropTarget()){
                    var infoArr = [];
                    infoArr['Name'] = dragDropTarget;
                    infoArr['layerType'] = 'vector';
                    infoArr['Title'] = filename;
                    infoArr['Abstract'] = '';
                    infoArr['DefaultCRS'] = '';
                    var sourceIndex = dragDropTarget+'Source';
                    var features = event.features;
                    if(fileType == 'kml'){
                        var geoJSONFormat = new ol.format.GeoJSON();
                        features = geoJSONFormat.readFeatures(geoJSONFormat.writeFeatures(features));
                    }
                    layersArr[sourceIndex] = new ol.source.Vector({
                        features: features
                    });
                    layersArr[dragDropTarget].setStyle(getDragDropStyle);
                    layersArr[dragDropTarget].setSource(layersArr[sourceIndex]);
                    buildLayerTableRow(infoArr,true);
                    map.getView().fit(layersArr[sourceIndex].getExtent());
                    toggleLayerTable();
                }
            }
            else if(fileType == 'shp' || fileType == 'dbf'){
                var dbfURL = '';
                if(fileType == 'shp'){
                    droppedShapefile = window.URL.createObjectURL(event.file);
                }
                if(fileType == 'dbf'){
                    droppedDBF = window.URL.createObjectURL(event.file);
                }
                if(fileType == 'shp'){
                    if(setDragDropTarget()){
                        setTimeout(function() {
                            shapefile = new Shapefile({
                                shp: droppedShapefile,
                                dbf: droppedDBF
                            },function (data){
                                var infoArr = [];
                                infoArr['Name'] = dragDropTarget;
                                infoArr['layerType'] = 'vector';
                                infoArr['Title'] = filename;
                                infoArr['Abstract'] = '';
                                infoArr['DefaultCRS'] = '';
                                var sourceIndex = dragDropTarget+'Source';
                                var format = new ol.format.GeoJSON();
                                var res = map.getView().getResolution();
                                var features = format.readFeatures(data.geojson, {
                                    featureProjection: 'EPSG:3857'
                                });
                                layersArr[sourceIndex] = new ol.source.Vector({
                                    features: features
                                });
                                layersArr[dragDropTarget].setStyle(getDragDropStyle);
                                layersArr[dragDropTarget].setSource(layersArr[sourceIndex]);
                                buildLayerTableRow(infoArr,true);
                                map.getView().fit(layersArr[sourceIndex].getExtent());
                                toggleLayerTable();
                                droppedShapefile = '';
                                droppedDBF = '';
                            });
                        },500);
                    }
                }
            }
        }
        else{
            alert('The drag and drop file loading only supports GeoJSON, kml, and shp file formats.');
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
                        if(nfeature.get('features')){
                            var cFeatures = nfeature.get('features');
                            for (f in cFeatures) {
                                ol.extent.extend(extent, cFeatures[f].getGeometry().getExtent());
                            }
                        }
                        else{
                            ol.extent.extend(extent, nfeature.getGeometry().getExtent());
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
                if (newfeatures.length > 1 && !spiderFeature) {
                    pointInteraction.getFeatures().clear();
                    if(!spiderCluster){
                        spiderifyPoints(newfeatures);
                    }
                }
                else {
                    if(spiderFeature){
                        var newfeature = spiderFeature;
                        spiderFeature = '';
                    }
                    else{
                        var newfeature = newfeatures[0];
                    }
                    pointInteraction.getFeatures().clear();
                    if (newfeature.get('features')) {
                        var clusterCnt = newfeatures[0].get('features').length;
                        if (clusterCnt > 1 && !spiderCluster) {
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
                    infoArr['layerType'] = 'vector';
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
                        if(loadPointsEvent){
                            var pointextent = pointvectorsource.getExtent();
                            map.getView().fit(pointextent,map.getSize());
                        }
                    });
                    processed = processed + lazyLoadCnt;
                    index++;
                }
                while(processed < solrRecCnt);
            }
        });

        clustersource = new ol.source.PropertyCluster({
            distance: clusterDistance,
            source: pointvectorsource,
            clusterkey: clusterKey,
            indexkey: 'occid',
            geometryFunction: function(feature){
                if(dateSliderActive){
                    if(validateFeatureDate(feature)){
                        return feature.getGeometry();
                    }
                    else{
                        return null;
                    }
                }
                else{
                    return feature.getGeometry();
                }
            }
        });

        layersArr['pointv'].setStyle(getPointStyle);
        if(clusterPoints){
            layersArr['pointv'].setSource(clustersource);
        }
        else{
            layersArr['pointv'].setSource(pointvectorsource);
        }
        layersArr['heat'].setSource(pointvectorsource);
        if(showHeatMap){
            layersArr['heat'].setVisible(true);
        }
    }

    map.on('singleclick', function(evt) {
        if(evt.originalEvent.altKey){
            var layerIndex = activeLayer+"Source";
            var viewResolution = /** @type {number} */ (mapView.getResolution());
            if(activeLayer != 'none' && activeLayer != 'select' && activeLayer != 'pointv' && activeLayer != 'dragdrop1' && activeLayer != 'dragdrop2' && activeLayer != 'dragdrop3'){
                var url = layersArr[layerIndex].getGetFeatureInfoUrl(evt.coordinate,viewResolution,'EPSG:3857',{'INFO_FORMAT':'application/json'});
                if (url) {
                    $.ajax({
                        type: "GET",
                        url: url,
                        async: true
                    }).done(function(msg) {
                        if(msg){
                            var infoHTML = '';
                            var infoArr = JSON.parse(msg);
                            var propArr = infoArr['features'][0]['properties'];
                            if(overlayLayers[activeLayer]){
                                var sourceVal = propArr['GRAY_INDEX'];
                                var lowCalVal = overlayLayers[activeLayer]['values']['rasmin'];
                                var highCalVal = overlayLayers[activeLayer]['values']['rasmax'];
                                var calcVal = overlayLayers[activeLayer]['values']['newval'];
                                if(sourceVal >= lowCalVal && sourceVal <= highCalVal){
                                    infoHTML += '<b>Value:</b> '+calcVal+'<br />';
                                }
                                else{
                                    infoHTML += '<b>Value:</b> 0<br />';
                                }
                            }
                            else{
                                //infoHTML += '<b>id:</b> '+infoArr['id']+'<br />';
                                //infoHTML += '<b>geometry:</b> '+infoArr['geometry']+'<br />';
                                for(var key in propArr){
                                    var valTag = '';
                                    if(key == 'GRAY_INDEX') valTag = 'Value';
                                    else valTag = key;
                                    infoHTML += '<b>'+valTag+':</b> '+propArr[key]+'<br />';
                                }
                            }
                            popupcontent.innerHTML = infoHTML;
                            popupoverlay.setPosition(evt.coordinate);
                        }
                    });
                }
            }
            else if(activeLayer == 'dragdrop1' || activeLayer == 'dragdrop2' || activeLayer == 'dragdrop3' || activeLayer == 'select'){
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
                    if(infoHTML){
                        popupcontent.innerHTML = infoHTML;
                        popupoverlay.setPosition(evt.coordinate);
                    }
                }
            }
            else if(activeLayer == 'pointv'){
                var infoHTML = '';
                var targetFeature = '';
                var iFeature = '';
                if(clickedFeatures.length == 1){
                    targetFeature = clickedFeatures[0];
                }
                if(targetFeature){
                    if(clusterPoints && targetFeature.get('features').length == 1){
                        iFeature = targetFeature.get('features')[0];
                    }
                    else if(!clusterPoints){
                        iFeature = targetFeature;
                    }
                }
                else{
                    return;
                }
                if(iFeature){
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
                else{
                    alert('You clicked on multiple points. The info window can only display data for a single point.');
                }
                clickedFeatures = [];
            }
        }
        else{
            var layerIndex = activeLayer+"Source";
            if(activeLayer != 'none' && activeLayer != 'select' && activeLayer != 'pointv'){
                if(activeLayer == 'dragdrop1' || activeLayer == 'dragdrop2' || activeLayer == 'dragdrop3'){
                    map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
                        if(layer === layersArr[activeLayer]){
                            try{
                                selectsource.addFeature(feature);
                                document.getElementById("selectlayerselect").value = 'select';
                                setActiveLayer();
                            }
                            catch(e){
                                alert('Feature has already been added to Shapes layer.');
                            }
                        }
                    });
                }
                else{
                    var viewResolution = /** @type {number} */ (mapView.getResolution());
                    var url = layersArr[layerIndex].getGetFeatureInfoUrl(evt.coordinate, viewResolution, 'EPSG:3857', {'INFO_FORMAT': 'application/json'});
                    selectObjectFromID(url, activeLayer);
                }
            }
        }
    });

    function selectObjectFromID(url,selectLayer){
        $.ajax({
            type: "GET",
            url: url,
            async: true
        }).done(function(msg) {
            if(msg){
                var infoArr = JSON.parse(msg);
                var objID = infoArr['features'][0]['id'];
                var url = 'rpc/GeoServerConnector.php?SERVICE=WFS&VERSION=1.1.0&REQUEST=GetFeature&typename=<?php echo $GEOSERVER_LAYER_WORKSPACE; ?>:'+selectLayer+'&featureid='+objID+'&outputFormat=application/json&srsname=EPSG:3857';
                $.get(url, function(data){
                    var features = new ol.format.GeoJSON().readFeatures(data);
                    if(features){
                        selectsource.addFeatures(features);
                        document.getElementById("selectlayerselect").value = 'select';
                        setActiveLayer();
                    }
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

<?php include_once('includes/mapsettings.php'); ?>

<?php include_once('includes/maptools.php'); ?>

<?php include_once('includes/layercontroller.php'); ?>

<?php include_once('includes/csvoptions.php'); ?>

<?php include_once('includes/reclassifytool.php'); ?>

<?php include_once('includes/rastercalculator.php'); ?>

<?php include_once('includes/vectorizeoverlay.php'); ?>

<!-- Data Download Form -->
<div style="display:none;">
    <form name="datadownloadform" id="datadownloadform" action="rpc/datadownloader.php" method="post">
        <input id="dh-q" name="dh-q"  type="hidden" value="" />
        <input id="dh-fq" name="dh-fq" type="hidden" value="" />
        <input id="dh-fl" name="dh-fl" type="hidden" value="" />
        <input id="dh-rows" name="dh-rows" type="hidden" value="" />
        <input id="dh-type" name="dh-type" type="hidden" value="" />
        <input id="dh-filename" name="dh-filename" type="hidden" value="" />
        <input id="dh-contentType" name="dh-contentType" type="hidden" value="" />
        <input id="dh-selections" name="dh-selections" type="hidden" value="" />
        <input id="schemacsv" name="schemacsv" type="hidden" value="" />
        <input id="identificationscsv" name="identificationscsv" type="hidden" value="" />
        <input id="imagescsv" name="imagescsv" type="hidden" value="" />
        <input id="formatcsv" name="formatcsv" type="hidden" value="" />
        <input id="zipcsv" name="zipcsv" type="hidden" value="" />
        <input id="csetcsv" name="csetcsv" type="hidden" value="" />
    </form>
</div>

<div id="loadingOverlay" data-role="popup" style="width:100%;position:relative;">
    <div id="loader"></div>
</div>
</body>
</html>