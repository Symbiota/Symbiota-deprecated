<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
include_once($SERVER_ROOT.'/classes/GardenSearchManager.php');
header("Content-Type: text/html; charset=".$charset);

$gsManager = new GardenSearchManager();

$lifeCycleArr = array();
$evergreenArr = array();
$flowerColorArr = array();
$bloomMonthArr = array();
$landscapeUseArr = array();
$cultivationPrefArr = array();
$propagationArr = array();
$growthHabitArr = array();
$nativeHabitatArr = array();
$availabilityArr = array();

$lifeCycleArr = $gsManager->getCharacterStateArr(136,true);
$evergreenArr = $gsManager->getCharacterStateArr(100,true);
$flowerColorArr = $gsManager->getCharacterStateArr(612,true);
$bloomMonthArr = $gsManager->getCharacterStateArr(165,true);
$landscapeUseArr = $gsManager->getCharacterStateArr(679,true);
$cultivationPrefArr = $gsManager->getCharacterStateArr(767,true);
$propagationArr = $gsManager->getCharacterStateArr(740,true);
$plantBehaviorArr = $gsManager->getCharacterStateArr(688,true);
$availabilityArr = $gsManager->getCharacterStateArr(209,true);
?>
<html>
<head>
	<title><?php echo $defaultTitle?> Gardening with Natives</title>
    <link href="../js/jquery-ui-1.12.1/jquery-ui.min.css" type="text/css" rel="stylesheet" />
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet" />
    <script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
<?php
include($serverRoot."/header.php");
?>
<link rel="stylesheet" href="../css/jquery.bxslider.css">
<script src="../js/jquery.bxslider.js"></script>
<script src="../js/jquery.manifest.js" type="text/javascript"></script>
<script>
    var searchCriteriaArr = [];
    var searchArr = [];
    var display = '';

    $(document).ready(function () {
        function split( val ) {
            return val.split( /,\s*/ );
        }
        function extractLast( term ) {
            return split( term ).pop();
        }

        $( "#garden-sciname-search-input" )
            .bind( "keydown", function( event ) {
                if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function( request, response ) {
                    var source = '../webservices/autofillsciname.php';
                    //console.log('term: '+request.term+'rlow: '+rankLow+'rhigh: '+rankHigh+'rlimit: '+rankLimit);
                    $.getJSON( source, {
                        term: extractLast( request.term ),
                        hideauth: true,
                        limit: 20
                    }, response );
                },
                search: function() {
                    var term = extractLast( this.value );
                    if ( term.length < 4 ) {
                        return false;
                    }
                },
                focus: function() {
                    return false;
                },
                select: function( event, ui ) {
                    var terms = split( this.value );
                    terms.pop();
                    terms.push( ui.item.value );
                    this.value = terms.join( ", " );
                    return false;
                }
            },{}
        );
        $( "#garden-common-search-input" )
            .bind( "keydown", function( event ) {
                if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                    source: function( request, response ) {
                        var source = '../webservices/autofillvernacular.php';
                        //console.log('term: '+request.term+'rlow: '+rankLow+'rhigh: '+rankHigh+'rlimit: '+rankLimit);
                        $.getJSON( source, {
                            term: extractLast( request.term ),
                            hideauth: true,
                            limit: 20
                        }, response );
                    },
                    search: function() {
                        var term = extractLast( this.value );
                        if ( term.length < 4 ) {
                            return false;
                        }
                    },
                    focus: function() {
                        return false;
                    },
                    select: function( event, ui ) {
                        var terms = split( this.value );
                        terms.pop();
                        terms.push( ui.item.value );
                        this.value = terms.join( ", " );
                        return false;
                    }
                },{}
            );

        $("#heightSlider").slider({
            range: true,
            min: 0,
            max: 51,
            create: function() {
                var textbox = $("#height-label");
                textbox.text("Any");
            },
            step: 1,
            slide: function(event, ui) {
                var textbox = $("#height-label");
                var max;
                if (ui.values[1] == 51) {max = "50+"}else{ max = ui.values[1]}
                textbox.text(ui.values[0]+ ' - '+ max);
            },
            stop: function( event, ui ) {
                if(ui.values[1] != 0){
                    var max;
                    if (ui.values[1] == 51) {max = "50+"}else{ max = ui.values[1]}
                    var valLabel = 'Max height '+ui.values[0]+' - '+max+' ft';
                    var valCode = '140--'+ui.values[0]+','+ui.values[1];
                    var optionObj = {name:valLabel, nameCode:valCode};
                    //remove any items from searchCriteriaArray that have nameCode
                    //starting with 140--
                    for (var j = 0; j < searchCriteriaArr.length; j++){
                        var myNameCode = searchCriteriaArr[j].nameCode;
                        if ( myNameCode.indexOf("140--") !== -1) {
                            searchCriteriaArr.splice(j, 1);
                        }
                    }
                    //push new option to search array
                    searchCriteriaArr.push(optionObj);
                    //console.log(searchCriteriaArr);
                    //remove old manifest item, and add new one.
                    $('#searchCriteriaManifest').manifest('remove', '.mf_item:contains("Max height")');
                    $('#searchCriteriaManifest').manifest('add',valLabel);
                    getSearchResults();
                }
            }
        });

        $("#widthSlider").slider({
            range: true,
            min: 0,
            max: 15,
            create: function() {
                var textbox = $("#width-label");
                textbox.text("Any");
            },
            step: 1,
            slide: function(event, ui) {
                var textbox = $("#width-label");
                textbox.text(ui.values[0]+ ' - '+ ui.values[1]);
            },
            stop: function( event, ui ) {
                if(ui.values[1] != 0){
                    var valLabel = 'Max width '+ui.values[0]+' - '+ui.values[1]+' ft';
                    var valCode = '738--'+ui.values[0]+','+ui.values[1];
                    var optionObj = {name:valLabel, nameCode:valCode};
                    //remove any items from searchCriteriaArray that have nameCode
                    //starting with 738--
                    for (var j = 0; j < searchCriteriaArr.length; j++){
                        var myNameCode = searchCriteriaArr[j].nameCode;
                        if ( myNameCode.indexOf("738--") !== -1) {
                            searchCriteriaArr.splice(j, 1);
                        }
                    }
                    //push new option to search array
                    searchCriteriaArr.push(optionObj);
                    //remove old manifest item, and add new one.
                    $('#searchCriteriaManifest').manifest('remove', '.mf_item:contains("Max width")');
                    $('#searchCriteriaManifest').manifest('add',valLabel);
                    getSearchResults();
                }
            }
        });

        $("#characteristicsPane").accordion({
            collapsible: true,
            active:false,
            heightStyle: "content"
        });
        $("#morePane").accordion({
            collapsible: true,
            active:false,
            heightStyle: "content"
        });
        $("#availabilityPane").accordion({
            collapsible: true,
            active:false,
            heightStyle: "content"
        });
        $('#searchCriteriaManifest').manifest({
            onRemove: function(event) {
                for(i in searchCriteriaArr){
                    if(searchCriteriaArr[i].name == event){
                        var index = searchCriteriaArr.indexOf(searchCriteriaArr[i]);
                        var id = searchCriteriaArr[i].nameCode;
                        var divId = id+'Div';
                        if(document.getElementById(divId)){
                            document.getElementById(divId).classList.remove("selectedOption");
                            document.getElementById(id).checked = false;
                        }
                        searchCriteriaArr.splice(index, 1);
                    }
                }
                getSearchResults();
            }
        });
    });

    function processOption(id){
        var divId = id+'Div';
        var labelId = id+'Label';
        var labelText = document.getElementById(labelId).innerHTML;
        if(document.getElementById(id).checked){
            document.getElementById(divId).classList.add("selectedOption");
            var optionObj = {name:labelText, nameCode:id};
            searchCriteriaArr.push(optionObj);
            $('#searchCriteriaManifest').manifest('add',labelText);
        }
        else{
            for(i in searchCriteriaArr){
                if(searchCriteriaArr[i].name == labelText){
                    var index = searchCriteriaArr.indexOf(searchCriteriaArr[i]);
                    searchCriteriaArr.splice(index, 1);
                }
            }
            var vals = $('#searchCriteriaManifest').manifest('values');
            var index = Number(vals.indexOf(labelText));
            var removeLine = ':eq('+index+')'
            $('#searchCriteriaManifest').manifest('remove',removeLine);
            document.getElementById(divId).classList.remove("selectedOption");
        }
        getSearchResults();
    }

    function processNameSearch(type){
        var name = "";
        var nameCode = "";
        if(type == "sciname"){
            name = document.getElementById('garden-sciname-search-input').value;
            nameCode = 'sciname--'+name;
        }
        if(type == "common"){
            name = document.getElementById('garden-common-search-input').value;
            nameCode = 'common--'+name;
        }
        var optionObj = {name:name, nameCode:nameCode};
        searchCriteriaArr.push(optionObj);
        $('#searchCriteriaManifest').manifest('add',name);
        getSearchResults();
    }

    function getSearchResults(){
        display = "";
        if(document.getElementById('listdisplayselector').checked) display = "list";
        if(document.getElementById('griddisplayselector').checked) display = "grid";
        prepSearchArr();
        loadResultData();
    }

    function prepSearchArr(){
        searchArr = [];
        for(i in searchCriteriaArr){
            searchArr.push(searchCriteriaArr[i].nameCode);
        }
    }

    function loadResultData(){
        var http = new XMLHttpRequest();
        var searchStr = JSON.stringify(searchArr);
        var url = "rpc/gettaxadata.php";
        var params = 'searchJson='+searchStr+'&display='+display;
        //console.log(url+'?'+params);
        //console.log('loading');
        $('body').addClass('with-overlay');
        http.open("POST", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function() {
            if(http.readyState == 4 && http.status == 200) {
                processResults(http.responseText);
            }
        };
        http.send(params);
    }

    function processResults(res){
        //console.log('processing');
        var resultArr = [];
        var reccnt = 0;
        //alert(resultArr);
        if(res != 'empty'){
            resultArr = JSON.parse(res);
            reccnt = getResultCnt(resultArr);
        }
        if(reccnt > 0){
            var resulthtml = buildResultHtml(resultArr);
            document.getElementById("reultsDiv").innerHTML = resulthtml;
            document.getElementById("results-wrapper").style.display = 'block';
            document.getElementById("reultsDiv").style.display = 'block';
        }
        else{
            document.getElementById("reultsDiv").innerHTML = '';
            //document.getElementById("results-wrapper").style.display = 'none';
        }
        //console.log('loaded');
        document.getElementById("resultCount").innerHTML = reccnt;
        $('body').removeClass('with-overlay');
    }

    function getResultCnt(res){
        var cnt = 0;
        for(i in res){
            if(res[i]) cnt++;
        }
        return cnt;
    }

    function buildResultHtml(res){
        var html = '';
        if(display == "list") {
            html += '<div class="searchResultTable list-results">';
        }else{
            html += '<div class="searchResultTable">';
        }
        for(i in res){
            console.log(res[i]);
            var sciname = res[i].sciname;
            if(display == "grid"){
                html += '<div class="searchresultgridcell">';
                html += '<a href="../taxa/garden.php?taxon='+i+'" target="_blank">';
                if(res[i].url) html += '<img class="searchresultgridimage" src="'+res[i].url+'" title="'+sciname+'" alt="'+sciname+' image" />';
                html += '<div class="searchresultgridsciname">'+sciname+'</div>';
                html += '</a>';
                html += '</div>';
            }
            if(display == "list"){
                html += '<div class="searchResultListMainRow">';
                html += '<div class="searchResultListSciname">';
                html += '<a href="../taxa/garden.php?taxon='+i+'" target="_blank">'+sciname+'</a>';
                html += '</div>';
                html += '<div class="searchResultListCommon">'+(res[i].common?res[i].common:"")+'</div>';
                html += '<div class="searchResultListToggle">';
                html += '<div id="listShow'+i+'" class="searchResultListToggleShow" onclick="toggleListItem('+i+',1);">summary</div>';
                html += '<div id="listHide'+i+'" class="searchResultListToggleHide" onclick="toggleListItem('+i+',0);">(hide summary)</div>';
                html += '</div>';
                html += '</div>';
                html += '<div id="hiddenListDiv'+i+'" class="searchResultListHiddenRow">';
                if(res[i].url) html += '<img class="searchResultListImage" src="'+res[i].url+'" title="'+sciname+'" alt="'+sciname+' image" />';
                html += '<div class="list-icon-type">'+(res[i].type?res[i].type:"")+'</div>';
                html += '<div class="list-icon-light">'+(res[i].sunlight?res[i].sunlight:"")+'</div>';
                html += '<div class="list-icon-moisture">'+(res[i].moisture?res[i].moisture:"")+'</div>';
                html += '<div class="list-max-height">'+(res[i].height_string)+'</div>';
                html += '<div class="list-max-width">'+(res[i].width_string)+'</div>';
                html += '<div class="list-ease">'+(res[i].ease?res[i].ease:"")+'</div>';
                html += '</div>';
            }
        }
        html += '</div>';
        html += '</div>';
        return html;
    }

    function toggleListItem(id,show){
        var hiddenDivId = 'hiddenListDiv'+id;
        var showDivId = 'listShow'+id;
        var hideDivId = 'listHide'+id;
        if(show){
            document.getElementById(showDivId).style.display = 'none';
            document.getElementById(hiddenDivId).style.display = 'block';
            document.getElementById(hideDivId).style.display = 'block';
        }
        else{
            document.getElementById(showDivId).style.display = 'block';
            document.getElementById(hiddenDivId).style.display = 'none';
            document.getElementById(hideDivId).style.display = 'none';
        }
    }

    function clearSearchParams(){
        $('#searchCriteriaManifest').manifest('destroy');
        for(i in searchCriteriaArr){
            var id = searchCriteriaArr[i].nameCode;
            var divId = id+'Div';
            if(document.getElementById(divId)){
                document.getElementById(divId).classList.remove("selectedOption");
                document.getElementById(id).checked = false;
            }
        }
        searchCriteriaArr = [];
        //replace content of searchManifestWrapper with the original hidden field
        $(".searchManifestWrapper").html('<input id="searchCriteriaManifest" type="text" value="" readonly/>');
        //reinitialize manifest
        $('#searchCriteriaManifest').manifest({
            onRemove: function(event) {
                for(i in searchCriteriaArr){
                    if(searchCriteriaArr[i].name == event){
                        var index = searchCriteriaArr.indexOf(searchCriteriaArr[i]);
                        var id = searchCriteriaArr[i].nameCode;
                        var divId = id+'Div';
                        if(document.getElementById(divId)){
                            document.getElementById(divId).classList.remove("selectedOption");
                            document.getElementById(id).checked = false;
                        }
                        searchCriteriaArr.splice(index, 1);
                    }
                }
            }
        });
        $("#widthSlider").slider("values", 0, 0);
        $("#widthSlider").slider("values", 1, 0);
        $("#heightSlider").slider("values", 0, 0);
        $("#heightSlider").slider("values", 1, 0);
        $("#garden-sciname-search-input").val('');
        $("#garden-common-search-input").val('');
        $("#height-label").text("Any");
        $("#width-label").text("Any");
        document.getElementById("resultCount").innerHTML = 0;
        document.getElementById("reultsDiv").style.display = 'none';
    }
</script>
<div class="native-banner-wrapper">
    <div class="inner-content">
        <h2>Gardening with Natives</h2>
    </div>
</div>
<div class="garden-content">
    <div class="garden-name-search-wrapper">
        <h2>Garden Native Plant Search <i class="fa fa-question-circle green"></i></h2>
        <span class="garden-feature-search-text">Select any combination of features within one or more categories. Results are immediately displayed below.</span>
        <h2>Search by plant name</h2>
        <div class="garden-name-search-box">
            <div class="input-label">Scientific name</div>
            <div class="input-wrapper">
                <input type="text" id="garden-sciname-search-input" title="Enter scientific name here." autocomplete="off" />
                <button id="garden-sciname-search-but" type="button" onclick="processNameSearch('sciname');"><i class="fa fa-search"></i></button>
            </div>
        </div>
        <div class="garden-name-search-box">
            <div class="input-label">Common name</div>
            <div class="input-wrapper">
                <input type="text" id="garden-common-search-input" title="Enter common name here." autocomplete="off" />
                <button id="garden-common-search-but" type="button" onclick="processNameSearch('common');"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </div>
</div>
<div class="garden-content">
    <div class="basic-feature-search-wrapper">
        <div class="divTable gardenSearchTable">
            <div class="divTableHeading">
                <div class="divTableRow">
                    <div class="divTableHead">Plant Type</div>
                    <div class="divTableHead">Sunlight</div>
                    <div class="divTableHead">Moisture</div>
                    <div class="divTableHead">Mature Size</div>
                    <div class="divTableHead">Ease of Growth</div>
                </div>
            </div>
            <div class="divTableBody">
                <div class="divTableRow">
                    <div class="divTableCell">
                        <div class="divTable plantTypeCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('137--3');" id="137--3">
                                        <div id="137--3Div" class="featureCheckBoxDiv">
                                            <label id="137--3Label" class="featureCheckBoxLabel planttype1" for="137--3">Tree</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('137--2');" id="137--2">
                                        <div id="137--2Div" class="featureCheckBoxDiv">
                                            <label id="137--2Label" class="featureCheckBoxLabel planttype2" for="137--2">Shrub</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('137--6');" id="137--6">
                                        <div id="137--6Div" class="featureCheckBoxDiv">
                                            <label id="137--6Label" class="featureCheckBoxLabel planttype3" for="137--6">Vine</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('137--1');" id="137--1">
                                        <div id="137--1Div" class="featureCheckBoxDiv">
                                            <label id="137--1Label" class="featureCheckBoxLabel planttype4" for="137--1">Herb</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('137--4');" id="137--4">
                                        <div id="137--4Div" class="featureCheckBoxDiv">
                                            <label id="137--4Label" class="featureCheckBoxLabel planttype5" for="137--4">Grass or grass-like</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('137--5');" id="137--5">
                                        <div id="137--5Div" class="featureCheckBoxDiv">
                                            <label id="137--5Label" class="featureCheckBoxLabel planttype6" for="137--5">Fern or fern ally</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable sunlightCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('680--1');" id="680--1">
                                        <div id="680--1Div" class="featureCheckBoxDiv">
                                            <label id="680--1Label" class="featureCheckBoxLabel sunlight1" for="680--1">Sun</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('680--2');" id="680--2">
                                        <div id="680--2Div" class="featureCheckBoxDiv">
                                            <label id="680--2Label" class="featureCheckBoxLabel sunlight3" for="680--2">Part shade</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('680--3');" id="680--3">
                                        <div id="680--3Div" class="featureCheckBoxDiv">
                                            <label id="680--3Label" class="featureCheckBoxLabel sunlight4" for="680--3">Shade</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable moistureCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('683--1');" id="683--1">
                                        <div id="683--1Div" class="featureCheckBoxDiv">
                                            <label id="683--1Label" class="featureCheckBoxLabel moisture1" for="683--1">Dry</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('683--2');" id="683--2">
                                        <div id="683--2Div" class="featureCheckBoxDiv">
                                            <label id="683--2Label" class="featureCheckBoxLabel moisture3" for="683--2">Moist</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" onchange="processOption('683--3');" id="683--3">
                                        <div id="683--3Div" class="featureCheckBoxDiv">
                                            <label id="683--3Label" class="featureCheckBoxLabel moisture4" for="683--3">Wet</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable sizeCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell unselectable">
                                        <div class="feature-slider-range">
                                            <div class="feature-slider-low-value">Any</div>
                                            <div class="feature-slider-high-value">50+</div>
                                        </div>
                                        <div class="feature-slider-wrapper unselectable">
                                            <div id="heightSlider">
                                                <div id="height-handle" class="ui-slider-handle">
                                                    <div class="custom-label-bar"></div>
                                                </div>
                                                <div id="height-handle" class="ui-slider-handle">
                                                    <div class="custom-label-bar"></div>
                                                </div>
                                            </div>
                                            <div id="height-label"></div>
                                            <div class="feature-slider-label">
                                                Height (ft)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell unselectable">
                                        <div class="feature-slider-range">
                                            <div class="feature-slider-low-value">Any</div>
                                            <div class="feature-slider-high-value">15</div>
                                        </div>
                                        <div class="feature-slider-wrapper">
                                            <div id="widthSlider">
                                                <div id="width-handle" class="ui-slider-handle">
                                                    <div class="custom-label-bar"></div>
                                                </div>
                                                <div id="width-handle" class="ui-slider-handle">
                                                    <div class="custom-label-bar"></div>
                                                </div>
                                            </div>
                                            <div id="width-label" ></div>
                                            <div class="feature-slider-label">
                                                Width (ft)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable growthCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureTextCheckBox" type="checkbox" onchange="processOption('684--1');" id="684--1">
                                        <div id="684--1Div" class="featureTextCheckBoxDiv unselectable">
                                            <label id="684--1Label" class="featureTextCheckBoxLabel" for="684--1">Easy</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureTextCheckBox" type="checkbox" onchange="processOption('684--2');" id="684--2">
                                        <div id="684--2Div" class="featureTextCheckBoxDiv unselectable">
                                            <label id="684--2Label" class="featureTextCheckBoxLabel" for="684--2">Moderate</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureTextCheckBox" type="checkbox" onchange="processOption('684--3');" id="684--3">
                                        <div id="684--3Div" class="featureTextCheckBoxDiv unselectable">
                                            <label id="684--3Label" class="featureTextCheckBoxLabel" for="684--3">Difficult</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="garden-content">
</div>
<div id="advSearchWrapper" class="garden-content">
    <?php
    if($lifeCycleArr || $evergreenArr || $flowerColorArr || $bloomMonthArr){
        ?>
        <div id="characteristicsPane" class="advSearchPane">
            <h3>Characteristics</h3>
            <div>
                <?php
                if($lifeCycleArr){
                    ?>
                    <div class="advSearchOptionWrapper">
                        <?php
                        foreach($lifeCycleArr as $opt => $optArr){
                            $cId = $optArr['cid'];
                            $csId = $optArr['cs'];
                            $optId = $cId.'--'.$csId;
                            $onchange = "processOption('".$optId."');";
                            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
                            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
                            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                if($evergreenArr){
                    ?>
                    <div class="advSearchOptionWrapper">
                        <?php
                        foreach($evergreenArr as $opt => $optArr){
                            $cId = $optArr['cid'];
                            $csId = $optArr['cs'];
                            $optId = $cId.'--'.$csId;
                            $onchange = "processOption('".$optId."');";
                            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
                            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
                            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                if($flowerColorArr){
                    ?>
                    <div class="advSearchOptionWrapper">
                        <div class="advSearchOptionHeader">
                            Flower color
                            <hr />
                        </div>
                        <?php
                        foreach($flowerColorArr as $opt => $optArr){
                            $cId = $optArr['cid'];
                            $csId = $optArr['cs'];
                            $optId = $cId.'--'.$csId;
                            $onchange = "processOption('".$optId."');";
                            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
                            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
                            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                if($bloomMonthArr){
                    ?>
                    <div class="advSearchOptionWrapper">
                        <div class="advSearchOptionHeader">
                            Bloom months
                            <hr />
                        </div>
                        <?php
                        foreach($bloomMonthArr as $opt => $optArr){
                            $cId = $optArr['cid'];
                            $csId = $optArr['cs'];
                            $optId = $cId.'--'.$csId;
                            $onchange = "processOption('".$optId."');";
                            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
                            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
                            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
                <div class="advSearchOptionWrapper">
                    <div class="advSearchOptionHeader">
                        Wildlife Support
                        <hr />
                    </div>
                    <input class="advSearchOptCheckBox" type="checkbox" onchange="processOption('685--2');" id="685--2">
                    <div id="685--2Div" class="advSearchOptCheckBoxDiv unselectable with-icons">
                        <div class="wildlifeOptIconDiv wildlife2"></div>
                        <label id="685--2Label" class="advSearchOptCheckBoxLabel" for="685--2">Pollinators</label>
                    </div>
                    <input class="advSearchOptCheckBox" type="checkbox" onchange="processOption('685--1');" id="685--1">
                    <div id="685--1Div" class="advSearchOptCheckBoxDiv unselectable with-icons">
                        <div class="wildlifeOptIconDiv wildlife4"></div>
                        <label id="685--1Label" class="advSearchOptCheckBoxLabel" for="685--1">Butterfly nectar source</label>
                    </div>
                    <input class="advSearchOptCheckBox" type="checkbox" onchange="processOption('685--6');" id="685--6">
                    <div id="685--6Div" class="advSearchOptCheckBoxDiv unselectable with-icons">
                        <div class="wildlifeOptIconDiv wildlife1"></div>
                        <label id="685--6Label" class="advSearchOptCheckBoxLabel" for="685--6">Hummingbirds</label>
                    </div>
                    <input class="advSearchOptCheckBox" type="checkbox" onchange="processOption('685--3');" id="685--3">
                    <div id="685--3Div" class="advSearchOptCheckBoxDiv unselectable with-icons">
                        <div class="wildlifeOptIconDiv wildlife5"></div>
                        <label id="685--3Label" class="advSearchOptCheckBoxLabel" for="685--3">Pest-eating insects</label>
                    </div>
                    <input class="advSearchOptCheckBox" type="checkbox" onchange="processOption('685--5');" id="685--5">
                    <div id="685--5Div" class="advSearchOptCheckBoxDiv unselectable with-icons">
                        <div class="wildlifeOptIconDiv wildlife3"></div>
                        <label id="685--5Label" class="advSearchOptCheckBoxLabel" for="685--5">Butterfly larval host</label>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <?php
    if($propagationArr || $plantBehaviorArr || $landscapeUseArr || $cultivationPrefArr){
        ?>
        <div id="morePane" class="advSearchPane">
            <h3>Growth & Maintenance</h3>
            <div>
		            <?php
		            if($landscapeUseArr){
			            ?>
                        <div class="advSearchOptionWrapper">
                            <div class="advSearchOptionHeader">
                                Landscape uses
                                <hr />
                            </div>
				            <?php
				            foreach($landscapeUseArr as $opt => $optArr){
					            $cId = $optArr['cid'];
					            $csId = $optArr['cs'];
					            $optId = $cId.'--'.$csId;
					            $onchange = "processOption('".$optId."');";
					            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
					            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
					            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
					            echo '</div>';
				            }
				            ?>
                        </div>
			            <?php
		            }
		            if($cultivationPrefArr){
			            ?>
                        <div class="advSearchOptionWrapper">
                            <div class="advSearchOptionHeader">
                                Other cultivation preferences
                                <hr />
                            </div>
				            <?php
				            foreach($cultivationPrefArr as $opt => $optArr){
					            $cId = $optArr['cid'];
					            $csId = $optArr['cs'];
					            $optId = $cId.'--'.$csId;
					            $onchange = "processOption('".$optId."');";
					            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
					            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
					            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
					            echo '</div>';
				            }
				            ?>
                        </div>
			            <?php
		            }
		            ?>
	            <?php
	            if($plantBehaviorArr){
		            ?>
                    <div class="advSearchOptionWrapper">
                        <div class="advSearchOptionHeader">
                            Plant Behavior
                            <hr />
                        </div>
			            <?php
			            foreach($plantBehaviorArr as $opt => $optArr){
				            $cId = $optArr['cid'];
				            $csId = $optArr['cs'];
				            $optId = $cId.'--'.$csId;
				            $onchange = "processOption('".$optId."');";
				            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
				            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
				            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
				            echo '</div>';
			            }
			            ?>
                    </div>
		            <?php
	            }
	            ?>
                <?php
                if($propagationArr){
                    ?>
                    <div class="advSearchOptionWrapper">
                        <div class="advSearchOptionHeader">
                            Propagation
                            <hr />
                        </div>
                        <?php
                        foreach($propagationArr as $opt => $optArr){
                            $cId = $optArr['cid'];
                            $csId = $optArr['cs'];
                            $optId = $cId.'--'.$csId;
                            $onchange = "processOption('".$optId."');";
                            echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
                            echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
                            echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>

            </div>
        </div>
        <?php
    }

    //removed dynamic availability, but left here in case it needs to be restored.
    //replaced with static panel
    if($availabilityArr && 1==2 ){
        ?>
        <div id="availabilityPane" class="advSearchPane">
            <h3>Commercial availability</h3>
            <div>
                <div class="advSearchOptionWrapper">
                    <?php
                    foreach($availabilityArr as $opt => $optArr){
                        $cId = $optArr['cid'];
                        $csId = $optArr['cs'];
                        $optId = $cId.'--'.$csId;
                        //$onchange = "processOption('".$optId."');";
                        $onchange = "";
                        echo '<input class="advSearchOptCheckBox" type="checkbox" onchange="'.$onchange.'" id="'.$optId.'">';
                        echo '<div id="'.$optId.'Div" class="advSearchOptCheckBoxDiv unselectable">';
                        echo '<label id="'.$optId.'Label" class="advSearchOptCheckBoxLabel" for="'.$optId.'">'.$opt.'</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <div id="availabilityPane" class="advSearchPane">
        <h3>Commercial availability</h3>
        <div>
            <div class="advSearchOptionWrapper">
                <p>We are looking forward to presenting details about businesses in the regions below and the native species they sell. Contact us if you&rsquo;re interested in helping to develop this resource! <span style="float: right;color: #502e91 !important;">(<a
                                href="<?php echo $clientRoot; ?>/pages/pdfs/Nurserylist_20190116.pdf" style="color: #502e91 !important;" target="_blank">Business contact information</a>)</span></p>
                <div class="advSearchOptionHeader">Portland Metro<hr></div>
                <ul class="inline">
                    <li>Aurora Nursery</li>
                    <li>BeaverLake Nursery</li>
                    <li>Bosky Dell Nursery</li>
                    <li>Echo Valley Natives</li>
                    <li>Humble Roots Farm &amp; Nursery</li>
                    <li>Livingscape Nursery</li>
                    <li>Portland Nursery</li>
                    <li>Scholls Valley Native Nursery</li>
                    <li>Valley Growers Nursery &amp; Landscape</li>
                </ul>
                <div class="advSearchOptionHeader">Willamette Valley<hr></div>
                <ul class="inline">
                    <li>Balance Restoration Nursery</li>
                    <li>Brooks Tree Farm</li>
                    <li>Champoeg Nursery</li>
                    <li>Doak Creek Native Plant Nursery</li>
                    <li>Native Grounds Nursery</li>
                    <li>Oak Point Nursery</li>
                    <li>Sevenoaks Native Nursery</li>
                    <li>Shonnard&rsquo;s Nursery</li>
                    <li>Trillium Gardens</li>
                    <li>Willamette Gardens</li>
                    <li>Willamette Wildlings</li>
                </ul>
                <div class="advSearchOptionHeader">Eastern Oregon<hr></div>
                <ul class="inline">
                    <li>Clearwater Native Nursery</li>
                    <li>Western Native Plants/Rock Bottom Ranch</li>
                    <li>Wintercreek Restoration &amp; Nursery</li>
                </ul>
                <div class="advSearchOptionHeader">Southern Oregon<hr></div>
                <ul class="inline">
                    <li>Althouse Nursery</li>
                    <li>Forestfarm Nursery</li>
                    <li>Klamath-Siskiyou Native Seeds</li>
                    <li>Plant Oregon : Wagner Creek</li>
                    <li>Shooting Star Nursery</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="results-wrapper" id="results-wrapper">
    <div class="results-content">
        <div class="topResultsBar">
            <div class="resultsDiv">
                Results: <span id="resultCount"></span>
            </div>
            <div class="resetButtonDiv">
                <button id="resetButton" type="button" onclick="clearSearchParams();">Clear all filters</button>
            </div>
            <div class="displaySettingDiv">
                <div class="gridDisplayDiv">
                    <input name="display" id="griddisplayselector" type="radio" value="grid" onchange="getSearchResults();" checked/> Grid
                </div>
                <div class="listDisplayDiv">
                    <input name="display" id="listdisplayselector" type="radio" value="list" onchange="getSearchResults();" /> List
                </div>
            </div>
        </div>
        <div class="searchManifestWrapper">
            <input id="searchCriteriaManifest" type="text" value="" readonly/>
        </div>
        <div class="reultsBoxWrapper" id="reultsDiv"></div>
    </div>
</div>
<div class="garden-content">
    <h2>Browse Plant Collections</h2>
    <div class="home-boxes">
        <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14796&pid=3" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Meadowscape_sm.jpg" alt="Meadowscape">
            <h3>Meadowscape</h3>
            <div class="box-overlay">
                <div class="centered">A sun-loving mix of flowering herbs, perennials, and grasses</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14797" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Pollinator_garden_sm.jpg" alt="Pollinator Garden">
            <h3>Pollinator Garden</h3>
            <div class="box-overlay">
                <div class="centered">A focus on flowers that provide nectar or pollen to native bees and other insect pollinators</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14798&pid=3" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Rock_garden_sm.jpg" alt="Rock Garden">
            <h3>Rock Garden</h3>
            <div class="box-overlay">
                <div class="centered">Plants that bring color and texture to rocky, well-drained landscapes</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14799&pid=3" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Water_features_garden_sm.jpg" alt="Rain Garden">
            <h3>Rain & Water Garden</h3>
            <div class="box-overlay">
                <div class="centered">Plants suited to the edge of a pond or stream, or moist soils nearby</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14800&pid=3" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Woodland_garden_sm.jpg" alt="Woodland Garden">
            <h3>Woodland Garden</h3>
            <div class="box-overlay">
                <div class="centered">Understory ferns, herbs, and shrubs to complete a wooded planting</div>
            </div>
        </a>
    </div>
</div>
<div class="garden-header-wrapper clearfix">
    <div class="col1">
        <div class="col-content">
            <h2>What is a native?</h2>
            <p>Oregon native plants are those which occur or historically occurred naturally in our state, and established in the
                landscape independently of direct or indirect human intervention.</p>
            <h2>Why plant natives?</h2>
            <p>Native plants are wise gardening choices. If planted in a habitat comparable to their natural one, they will:</p>
            <ul class="square-bullets white-bullets">
                <li>Use less water, fertilizer, and pesticides when established.</li>
                <li>Capture the unique character of a region by preserving its biological heritage and maintaining genetic diversity.</li>
                <li>Provide food and habitat for native pollinators, birds, and other animals.</li>
                <li>Serve as biodiversity corridors, connecting distant natural areas with critical strands of native habitat
                    through urban areas.</li>
            </ul>
        </div>
    </div>
    <div class="col2">&nbsp;</div>
</div>
<div class="metro-wrapper">
    <div class="inner-content">
        <hr />
        <div class="metro-col1"> </div>
        <div class="metro-col2">
            <div class="col-content">
                <p>Metro is a primary contributor to OregonFlora's Gardening with Native Plants and supports efforts to protect clean
                    air, water and habitat in greater Portland.</p>
            </div>
        </div>
    </div>
</div>
<?php
include($serverRoot."/footer.php");
?>

</body>
</html>