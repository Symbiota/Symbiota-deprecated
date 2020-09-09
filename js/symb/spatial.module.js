$(window).resize(function(){
    var winHeight = $(window).height();
    winHeight = winHeight + "px";
    document.getElementById('spatialpanel').style.height = winHeight;
    $("#accordion").accordion("refresh");
});

$(document).on("pageloadfailed", function(event, data){
    event.preventDefault();
});

$(document).ready(function() {
    setLayersTable();

    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

		$( ".query-trigger-field" )
				.bind( "change", function( event ) {
					buildQueryStrings();
				});
		$( "#taxa_autocomplete").on("click",'.ui-autocomplete li a', function(event) {//for Safari, Firefox PC, etc.
				event.preventDefault();
				buildQueryStrings();
		});
    $( "#taxa" )
				// don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
            		$( this ).data( "autocomplete" ) !== undefined &&
            		$( this ).data( "autocomplete" ).menu !== undefined &&
                $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function( request, response ) {
                var t = document.getElementById("taxontype").value;
                var source = '';
                var rankLow = '';
                var rankHigh = '';
                var rankLimit = '';
                if(t == 5){
                    source = '../webservices/autofillvernacular.php';
                }
                else{
                    source = '../webservices/autofillsciname.php';
                }
                if(t == 4){
                    rankLow = 21;
                    rankHigh = 139;
                }
                else if(t == 2){
                    rankLimit = 140;
                }
                else if(t == 3){
                    rankLow = 141;
                }
                else{
                    rankLow = 140;
                }
                //console.log('term: '+request.term+'rlow: '+rankLow+'rhigh: '+rankHigh+'rlimit: '+rankLimit);
                $.getJSON( source, {
                    term: extractLast( request.term ),
                    rlow: rankLow,
                    rhigh: rankHigh,
                    rlimit: rankLimit,
                    hideauth: true,
                    limit: 20
                }, response );
            },
            appendTo: "#taxa_autocomplete",
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
                
								buildQueryStrings();
                return false;
            }
        },{});
});

function addLayerToSelList(layer,title){
    var origValue = document.getElementById("selectlayerselect").value;
    var selectionList = document.getElementById("selectlayerselect").innerHTML;
    var optionId = "lsel-"+layer;
    var newOption = '<option id="lsel-'+optionId+'" value="'+layer+'">'+title+'</option>';
    selectionList += newOption;
    document.getElementById("selectlayerselect").innerHTML = selectionList;
    if(layer != 'select'){
        document.getElementById("selectlayerselect").value = layer;
        setActiveLayer();
    }
    else{
        document.getElementById("selectlayerselect").value = origValue;
    }
}

function adjustSelectionsTab(){
    if(selections.length > 0){
        document.getElementById("selectionstab").style.display = "block";
    }
    else{
        document.getElementById("selectionstab").style.display = "none";
        var activeTab = $('#recordstab').tabs("option","active");
        if(activeTab==3){
            buildCollKey();
            $('#recordstab').tabs({active:0});
        }
    }
}

function animateDS(){
    if(!dsAnimStop){
        var lowDate = document.getElementById("datesliderearlydate").value;
        var highDate = document.getElementById("datesliderlatedate").value;
        var lowDateVal = new Date(lowDate);
        lowDateVal = new Date(lowDateVal.setTime(lowDateVal.getTime()+86400000));
        var highDateVal = new Date(highDate);
        highDateVal = new Date(highDateVal.setTime(highDateVal.getTime()+86400000));
        if(dsAnimReverse){
            if(dsAnimDual){
                if(lowDateVal.getTime() !== highDateVal.getTime()) highDateVal = new Date(highDateVal.setDate(highDateVal.getDate() - dsAnimDuration));
                var calcLowDate = new Date(lowDateVal.setDate(lowDateVal.getDate() - dsAnimDuration));
                if(calcLowDate.getTime() > dsAnimLow.getTime()){
                    lowDateVal = calcLowDate;
                }
                else{
                    lowDateVal = dsAnimLow;
                    dsAnimStop = true;
                }
            }
            else{
                var calcHighDate = new Date(highDateVal.setDate(highDateVal.getDate() - dsAnimDuration));
                if(calcHighDate.getTime() > dsAnimLow.getTime()){
                    highDateVal = calcHighDate;
                }
                else{
                    dsAnimStop = true;
                }
            }
        }
        else{
            if(dsAnimDual && (lowDateVal.getTime() !== highDateVal.getTime())) lowDateVal = new Date(lowDateVal.setDate(lowDateVal.getDate() + dsAnimDuration));
            var calcHighDate = new Date(highDateVal.setDate(highDateVal.getDate() + dsAnimDuration));
            if(calcHighDate.getTime() < dsAnimHigh.getTime()){
                highDateVal = calcHighDate;
            }
            else{
                highDateVal = dsAnimHigh;
                dsAnimStop = true;
            }
        }
        tsOldestDate = lowDateVal;
        tsNewestDate = highDateVal;
        var lowDateValStr = getISOStrFromDateObj(lowDateVal);
        var highDateValStr = getISOStrFromDateObj(highDateVal);
        $("#sliderdiv").slider('values',0,tsOldestDate.getTime());
        $("#sliderdiv").slider('values',1,tsNewestDate.getTime());
        $("#custom-label-min").text(lowDateValStr);
        $("#custom-label-max").text(highDateValStr);
        document.getElementById("datesliderearlydate").value = lowDateValStr;
        document.getElementById("datesliderlatedate").value = highDateValStr;
        layersArr['pointv'].getSource().changed();
        if(dsAnimImageSave){
            var filename = lowDateValStr+'-to-'+highDateValStr+'.png';
            exportMapPNG(filename,true);
        }
        if(!dsAnimStop){
            dsAnimation = setTimeout(animateDS,dsAnimTime);
        }
        else{
            tsOldestDate = dsAnimLow;
            tsNewestDate = dsAnimHigh;
            var lowDateValStr = getISOStrFromDateObj(dsAnimLow);
            var highDateValStr = getISOStrFromDateObj(dsAnimHigh);
            $("#sliderdiv").slider('values',0,tsOldestDate.getTime());
            $("#sliderdiv").slider('values',1,tsNewestDate.getTime());
            $("#custom-label-min").text(lowDateValStr);
            $("#custom-label-max").text(highDateValStr);
            document.getElementById("datesliderearlydate").value = lowDateValStr;
            document.getElementById("datesliderlatedate").value = highDateValStr;
            layersArr['pointv'].getSource().changed();
            dsAnimation = '';
        }
    }
}

function arrayIndexSort(obj){
    var keys = [];
    for(var key in obj){
        if(obj.hasOwnProperty(key)){
            keys.push(key);
        }
    }
    return keys;
}

function autoColorColl(){
    document.getElementById("randomColorColl").disabled = true;
    changeMapSymbology('coll');
    var usedColors = [];
    for(i in collSymbology){
        var randColor = generateRandColor();
        while (usedColors.indexOf(randColor) > -1) {
            randColor = generateRandColor();
        }
        usedColors.push(randColor);
        changeCollColor(randColor,i);
        var keyName = 'keyColor'+i;
        document.getElementById(keyName).color.fromString(randColor);
    }
    document.getElementById("randomColorColl").disabled = false;
}

function autoColorTaxa(){
    document.getElementById("randomColorTaxa").disabled = true;
    changeMapSymbology('taxa');
    var usedColors = [];
    for(i in taxaSymbology){
        var randColor = generateRandColor();
        while (usedColors.indexOf(randColor) > -1) {
            randColor = generateRandColor();
        }
        usedColors.push(randColor);
        changeTaxaColor(randColor,i);
        var keyName = 'taxaColor'+i;
        if(document.getElementById(keyName)){
            document.getElementById(keyName).color.fromString(randColor);
        }
    }
    document.getElementById("randomColorTaxa").disabled = false;
}

function buildCollKey(){
    for(i in collSymbology){
        buildCollKeyPiece(i);
    }
    keyHTML = '';
    var sortedKeys = arrayIndexSort(collKeyArr).sort();
    for(i in sortedKeys) {
        keyHTML += collKeyArr[sortedKeys[i]];
    }
    document.getElementById("symbologykeysbox").innerHTML = keyHTML;
    jscolor.init();
}

function buildCollKeyPiece(key){
    keyHTML = '';
    keyLabel = "'"+key+"'";
    var color = collSymbology[key]['color'];
    keyHTML += '<div style="display:table-row;">';
    keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="keyColor'+key+'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="'+color+'" onchange="changeCollColor(this.value,'+keyLabel+');" /></div>';
    keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
    keyHTML += '<div style="display:table-cell;width:250px;vertical-align:middle;padding-left:8px;">'+key+'</div>';
    keyHTML += '</div>';
    keyHTML += '<div style="display:table-row;height:8px;"></div>';
    collKeyArr[key] = keyHTML;
}

function buildCQLString(){
    newcqlString = '';
    for(i in cqlArr){
        newcqlString += ' AND '+cqlArr[i];
    }
    if(loadVectorPoints){
        newcqlString = encodeURIComponent(newcqlString.substr(5,newcqlString.length));
    }
    else{
        newcqlString = newcqlString.substr(5,newcqlString.length);
    }
    //console.log(cqlString);
}

function buildLayerTableRow(lArr,removable){
    var layerList = '';
    var trfragment = '';
    var layerID = lArr['Name'];
    var layerType = lArr['layerType'];
    if(layerType != 'vector'){
        var infoArr = [];
        infoArr['id'] = layerID;
        infoArr['title'] = lArr['Title'];
        rasterLayers.push(infoArr);
    }
    var addLayerFunction = (layerType == 'vector'?'editVectorLayers':'editRasterLayers');
    var divid = "lay-"+layerID;
    if(!document.getElementById(divid)){
        trfragment += '<td style="width:30px;">';
        var onchange = (removable?"toggleUploadLayer(this,'"+lArr['Title']+"');":addLayerFunction+"(this,'"+lArr['Title']+"');");
        trfragment += '<input type="checkbox" value="'+layerID+'" onchange="'+onchange+'" '+(removable?'checked ':'')+'/>';
        trfragment += '</td>';
        trfragment += '<td style="width:170px;">';
        trfragment += '<b>'+lArr['Title']+'</b>';
        trfragment += '</td>';
        trfragment += '<td style="width:330px;">';
        trfragment += lArr['Abstract'];
        trfragment += '</td>';
        trfragment += '<td style="width:50px;background-color:black">';
        trfragment += '<img src="../images/'+(layerType == 'vector'?'button_wfs.png':'button_wms.png')+'" style="width:20px;margin-left:8px;">';
        trfragment += '</td>';
        trfragment += '<td style="width:50px;">';
        if(removable){
            var onclick = "removeUserLayer('"+layerID+"');";
            trfragment += '<input type="image" style="margin-left:5px;" src="../images/del.png" onclick="'+onclick+'" title="Remove layer">';
        }
        trfragment += '</td>';
        var layerTable = document.getElementById("layercontroltable");
        var newLayerRow = (removable?layerTable.insertRow(0):layerTable.insertRow());
        newLayerRow.id = 'lay-'+layerID;
        newLayerRow.innerHTML = trfragment;
        if(removable) addLayerToSelList(layerID,lArr['Title']);
    }
    else{
        document.getElementById("selectlayerselect").value = layerID;
        setActiveLayer();
    }
    toggleLayerTable();
}

function buildQueryStrings(){
console.log(document.getElementById("taxa").value.trim());
    cqlArr = [];
    solrqArr = [];
    solrgeoqArr = [];
    newcqlString = '';
    newsolrqString = '';
    getCollectionParams();
    prepareTaxaParams(function(res){
        getTextParams();
        getGeographyParams(loadVectorPoints);
        if(cqlArr.length > 0){
            buildCQLString();
        }
        if(solrqArr.length > 0 || solrgeoqArr.length > 0){
            buildSOLRQString();
        }
    });
}

function buildRasterCalcDropDown(){
    var selectionList = '<option value="">Select Layer</option>';
    overlayLayers.sort();
    for(l in overlayLayers){
        var newOption = '<option value="'+overlayLayers[l]['id']+'">'+overlayLayers[l]['id']+'</option>';
        selectionList += newOption;
    }
    document.getElementById("rastcalcoverlay1").innerHTML = selectionList;
    document.getElementById("rastcalcoverlay2").innerHTML = selectionList;
}

function buildReclassifyDropDown(){
    var selectionList = '<option value="">Select Layer</option>';
    for(i in rasterLayers){
        var newOption = '<option value="'+rasterLayers[i]['id']+'">'+rasterLayers[i]['title']+'</option>';
        selectionList += newOption;
    }
    if(overlayLayers.length > 0){
        for(l in overlayLayers){
            var newOption = '<option value="'+overlayLayers[l]['id']+'">'+overlayLayers[l]['id']+'</option>';
            selectionList += newOption;
        }
    }
    document.getElementById("reclassifysourcelayer").innerHTML = selectionList;
}

function buildSOLRQString(){
    newsolrqString = 'q=';
    var tempqStr = '';
    var tempfqStr = '';
    if(solrqArr.length > 0){
        for(i in solrqArr){
            tempqStr += ' AND '+solrqArr[i];
        }
        tempqStr = tempqStr.substr(5,tempqStr.length);
        tempqStr += ' AND (decimalLatitude:[* TO *] AND decimalLongitude:[* TO *] AND sciname:[* TO *])';
        document.getElementById("dh-q").value = tempqStr;
        newsolrqString += tempqStr;
    }
    else{
        document.getElementById("dh-q").value = '(sciname:[* TO *])';
        newsolrqString += '(sciname:[* TO *])';
    }

    if(solrgeoqArr.length > 0){
        for(i in solrgeoqArr){
            tempfqStr += ' OR geo:'+solrgeoqArr[i];
        }
        tempfqStr = tempfqStr.substr(4,tempfqStr.length);
        document.getElementById("dh-fq").value = tempfqStr;
        newsolrqString += '&fq='+tempfqStr;
    }
}

function buildTaxaKey(){
    document.getElementById("taxaCountNum").innerHTML = taxaCnt;
    for(i in taxaSymbology){
        var family = taxaSymbology[i]['family'];
        var tidinterpreted = taxaSymbology[i]['tidinterpreted'];
        var sciname = taxaSymbology[i]['sciname'];
        buildTaxaKeyPiece(i,family,tidinterpreted,sciname);
    }
    keyHTML = '';
    var famUndefinedArr = [];
    if(taxaKeyArr['undefined']){
        famUndefinedArr = taxaKeyArr['undefined'];
        var undIndex = taxaKeyArr.indexOf('undefined');
        taxaKeyArr.splice(undIndex,1);
    }
    var fsortedKeys = arrayIndexSort(taxaKeyArr).sort();
    for(f in fsortedKeys){
        var scinameArr = [];
        scinameArr = taxaKeyArr[fsortedKeys[f]];
        var ssortedKeys = arrayIndexSort(scinameArr).sort();
        keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>"+fsortedKeys[f]+"</h3></div>";
        keyHTML += "<div style='display:table;'>";
        for(s in ssortedKeys){
            keyHTML += taxaKeyArr[fsortedKeys[f]][ssortedKeys[s]];
        }
        keyHTML += "</div>";
    }
    if(famUndefinedArr.length > 0){
        var usortedKeys = arrayIndexSort(famUndefinedArr).sort();
        keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>Family Not Defined</h3></div>";
        keyHTML += "<div style='display:table;'>";
        for(u in usortedKeys){
            keyHTML += taxaKeyArr[usortedKeys[u]];
        }
    }
    document.getElementById("taxasymbologykeysbox").innerHTML = keyHTML;
    jscolor.init();
}

function buildTaxaKeyPiece(key,family,tidinterpreted,sciname){
    keyHTML = '';
    keyLabel = "'"+key+"'";
    var color = taxaSymbology[key]['color'];
    keyHTML += '<div id="'+key+'keyrow">';
    keyHTML += '<div style="display:table-row;">';
    keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'+key+'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="'+color+'" onchange="changeTaxaColor(this.value,'+keyLabel+');" /></div>';
    keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
    if(!tidinterpreted){
        keyHTML += "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i>"+sciname+"</i></div>";
    }
    else{
        //keyHTML += "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../taxa/index.php?taxon="+sciname+"'>"+sciname+"</a></i></div>";
        keyHTML += "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../taxa/index.php?taxon="+tidinterpreted+"'>"+sciname+"</a></i></div>";
    }
    keyHTML += '</div></div>';
    if(!taxaKeyArr[family]){
        taxaKeyArr[family] = [];
    }
    taxaKeyArr[family][key] = keyHTML;
}

function buildVectorizeDropDown(){
    var selectionList = '<option value="">Select Layer</option>';
    vectorizeLayers.sort();
    for(l in vectorizeLayers){
        var newOption = '<option value="'+vectorizeLayers[l]+'">'+vectorizeLayers[l]+'</option>';
        selectionList += newOption;
    }
    document.getElementById("vectorizesourcelayer").innerHTML = selectionList;
}

function changeBaseMap(){
    var blsource;
    var selection = document.getElementById('base-map').value;
    var baseLayer = map.getLayers().getArray()[0];
    if(selection == 'worldtopo'){
        blsource = new ol.source.XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
            crossOrigin: 'anonymous'
        });
    }
    if(selection == 'openstreet'){blsource = new ol.source.OSM();}
    if(selection == 'blackwhite'){blsource = new ol.source.Stamen({layer: 'toner'});}
    if(selection == 'worldimagery'){
        blsource = new ol.source.XYZ({
            url: 'http://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            crossOrigin: 'anonymous'
        });
    }
    if(selection == 'ocean'){
        blsource = new ol.source.XYZ({
            url: 'http://services.arcgisonline.com/arcgis/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}',
            crossOrigin: 'anonymous'
        });
    }
    if(selection == 'ngstopo'){
        blsource = setBaseLayerSource('http://services.arcgisonline.com/arcgis/rest/services/NGS_Topo_US_2D/MapServer/tile/{z}/{y}/{x}');
    }
    if(selection == 'natgeoworld'){
        blsource = new ol.source.XYZ({
            url: 'http://services.arcgisonline.com/arcgis/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}',
            crossOrigin: 'anonymous'
        });
    }
    if(selection == 'esristreet'){
        blsource = setBaseLayerSource('http://services.arcgisonline.com/arcgis/rest/services/ESRI_StreetMap_World_2D/MapServer/tile/{z}/{y}/{x}');
    }
    baseLayer.setSource(blsource);
}

function changeClusterDistance(){
    clusterDistance = document.getElementById("setclusterdistance").value;
    clustersource.setDistance(clusterDistance);
}

function changeClusterSetting(){
    if(document.getElementById("sliderdiv")){
        document.getElementById("clusterswitch").checked = clusterPoints;
        alert('You cannot change the cluster setting while the Date Slider is active.');
    }
    else{
        clusterPoints = document.getElementById("clusterswitch").checked;
        if(clusterPoints){
            removeDateSlider();
            loadPointWFSLayer(0);
        }
        else{
            layersArr['pointv'].setSource(pointvectorsource);
        }
    }
}

function changeCollColor(color,key){
    changeMapSymbology('coll');
    collSymbology[key]['color'] = color;
    layersArr['pointv'].getSource().changed();
    if(spiderCluster){
        var spiderFeatures = layersArr['spider'].getSource().getFeatures();
        for(f in spiderFeatures){
            var style = (spiderFeatures[f].get('features')?setClusterSymbol(spiderFeatures[f]):setSymbol(spiderFeatures[f]));
            spiderFeatures[f].setStyle(style);
        }
    }
}

function changeDraw() {
    var value = typeSelect.value;
    if (value !== 'None') {
        draw = new ol.interaction.Draw({
            source: selectsource,
            type: (value)
        });

        draw.on('drawend', function(evt){
            typeSelect.value = 'None';
            map.removeInteraction(draw);
            if(!shapeActive){
                var infoArr = [];
                infoArr['Name'] = 'select';
                infoArr['Title'] = 'Shapes';
                infoArr['layerType'] = 'vector';
                infoArr['Abstract'] = '';
                infoArr['DefaultCRS'] = '';
                buildLayerTableRow(infoArr,true);
                shapeActive = true;
                document.getElementById("selectlayerselect").value = 'select';
                setActiveLayer();
            }
            else{
                document.getElementById("selectlayerselect").value = 'select';
                setActiveLayer();
            }
            draw = '';
        });

        map.addInteraction(draw);
    }
    else{
        draw = '';
    }
}

function changeHeatMapBlur(){
    heatMapBlur = document.getElementById("heatmapblur").value;
    layersArr['heat'].setBlur(parseInt(heatMapBlur, 10));
}

function changeHeatMapRadius(){
    heatMapRadius = document.getElementById("heatmapradius").value;
    layersArr['heat'].setRadius(parseInt(heatMapRadius, 10));
}

function changeMapSymbology(symbology){
    if(symbology != mapSymbology){
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
    }
    if(symbology == 'coll'){
        if(mapSymbology == 'taxa'){
            clearTaxaSymbology();
            clusterKey = 'CollectionName';
            mapSymbology = 'coll';
            if(clusterPoints){
                loadPointWFSLayer(0);
            }
        }
    }
    if(symbology == 'taxa'){
        if(mapSymbology == 'coll'){
            resetMainSymbology();
            clusterKey = 'namestring';
            mapSymbology = 'taxa';
            if(clusterPoints){
                loadPointWFSLayer(0);
            }
        }
    }
}

function changeRecordPage(page){
    document.getElementById("queryrecords").innerHTML = "<p>Loading...</p>";
    var selJson = JSON.stringify(selections);
    var http = new XMLHttpRequest();
    var url = "rpc/changemaprecordpage.php";
    var params = solrqString+'&rows='+solrRecCnt+'&page='+page+'&selected='+selJson;
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            var newMapRecordList = JSON.parse(http.responseText);
            document.getElementById("queryrecords").innerHTML = newMapRecordList;
        }
    };
    http.send(params);
}

function changeTaxaColor(color,tidcode){
    changeMapSymbology('taxa');
    taxaSymbology[tidcode]['color'] = color;
    layersArr['pointv'].getSource().changed();
}

function checkDateSliderType(){
    if(dateSliderActive){
        document.body.removeChild(sliderdiv);
        sliderdiv = '';
        var dual = document.getElementById("dsdualtype").checked;
        createDateSlider(dual);
    }
}

function checkDSAnimDuration(){
    var duration = document.getElementById("datesliderinterduration").value;
    var imageSave = document.getElementById("dateslideranimimagesave").checked;
    if(duration){
        if(!isNaN(duration) && duration > 0){
            var lowDate = document.getElementById("datesliderearlydate").value;
            var hLowDate = new Date(lowDate);
            hLowDate = new Date(hLowDate.setTime(hLowDate.getTime()+86400000));
            var highDate = document.getElementById("datesliderlatedate").value;
            var hHighDate = new Date(highDate);
            hHighDate = new Date(hHighDate.setTime(hHighDate.getTime()+86400000));
            var difference = (hHighDate-hLowDate)/1000;
            difference /= (60*60*24);
            var diffYears = Math.abs(difference/365.25);
            if(duration >= diffYears){
                alert("Interval duration must less than the difference between the earliest and latest dates in years: "+diffYears.toFixed(4));
                document.getElementById("datesliderinterduration").value = '';
            }
            else if(imageSave){
                var lowDate = document.getElementById("datesliderearlydate").value;
                var hLowDate = new Date(lowDate);
                hLowDate = new Date(hLowDate.setTime(hLowDate.getTime()+86400000));
                var highDate = document.getElementById("datesliderlatedate").value;
                var hHighDate = new Date(highDate);
                hHighDate = new Date(hHighDate.setTime(hHighDate.getTime()+86400000));
                var difference = (hHighDate - hLowDate)/1000;
                difference /= (60*60*24);
                var diffYears = difference/365.25;
                var imageCount = Math.ceil(diffYears/duration);
                if(!confirm("You have Save Images checked. With the current interval duration and date settings, this will produce "+imageCount+" images. Click OK to continue.")){
                    document.getElementById("dateslideranimimagesave").checked = false;
                }
            }
        }
        else{
            alert("Interval duration must be a number greater than zero.");
            document.getElementById("datesliderinterduration").value = '';
        }
    }
}

function checkDSAnimTime(){
    var animtime = Number(document.getElementById("datesliderintertime").value);
    if(animtime){
        if(isNaN(animtime) || animtime < 0.1 || animtime > 5){
            alert("Interval time must be a number greater than or equal to .1, and less than or equal to 5.");
            document.getElementById("datesliderintertime").value = '';
        }
    }
}

function checkDSHighDate(){
    var maxDate = dsNewestDate.getTime();
    var hMaxDate = new Date(maxDate);
    var hMaxDateStr = getISOStrFromDateObj(hMaxDate);
    var currentHighSetting = new Date($("#sliderdiv").slider("values",1));
    var currentHighSettingStr = getISOStrFromDateObj(currentHighSetting);
    var highDate = document.getElementById("datesliderlatedate").value;
    if(highDate){
        if(formatCheckDate(highDate)){
            var currentLowSetting = new Date($("#sliderdiv").slider("values",0));
            var currentLowSettingStr = getISOStrFromDateObj(currentLowSetting);
            var hHighDate = new Date(highDate);
            if(hHighDate < hMaxDate){
                if(hHighDate < currentLowSetting){
                    alert("Date cannot be earlier than the currently set earliest date: "+currentLowSettingStr+'.');
                    document.getElementById("datesliderlatedate").value = currentHighSettingStr;
                }
            }
            else{
                alert("Date cannot be later than the latest date on slider: "+hMaxDateStr+'.');
                document.getElementById("datesliderlatedate").value = currentHighSettingStr;
            }
        }
    }
    else{
        document.getElementById("datesliderlatedate").value = currentHighSettingStr;
    }
}

function checkDSLowDate(){
    var minDate = dsOldestDate.getTime();;
    var hMinDate = new Date(minDate);
    var hMinDateStr = getISOStrFromDateObj(hMinDate);
    var currentLowSetting = new Date($("#sliderdiv").slider("values",0));
    var currentLowSettingStr = getISOStrFromDateObj(currentLowSetting);
    var lowDate = document.getElementById("datesliderearlydate").value;
    if(lowDate){
        if(formatCheckDate(lowDate)){
            var currentHighSetting = new Date($("#sliderdiv").slider("values",1));
            var currentHighSettingStr = getISOStrFromDateObj(currentHighSetting);
            var hLowDate = new Date(lowDate);
            if(hLowDate > hMinDate){
                if(hLowDate > currentHighSetting){
                    alert("Date cannot be after the currently set latest date: "+currentHighSettingStr+'.');
                    document.getElementById("datesliderearlydate").value = currentLowSettingStr;
                }
            }
            else{
                alert("Date cannot be earlier than the earliest date on slider: "+hMinDateStr+'.');
                document.getElementById("datesliderearlydate").value = currentLowSettingStr;
            }
        }
    }
    else{
        document.getElementById("datesliderearlydate").value = currentLowSettingStr;
    }
}

function checkDSSaveImage(){
    var imageSave = document.getElementById("dateslideranimimagesave").checked;
    var duration = document.getElementById("datesliderinterduration").value;
    if(imageSave){
        if(duration){
            var lowDate = document.getElementById("datesliderearlydate").value;
            var hLowDate = new Date(lowDate);
            hLowDate = new Date(hLowDate.setTime(hLowDate.getTime()+86400000));
            var highDate = document.getElementById("datesliderlatedate").value;
            var hHighDate = new Date(highDate);
            hHighDate = new Date(hHighDate.setTime(hHighDate.getTime()+86400000));
            var difference = (hHighDate - hLowDate)/1000;
            difference /= (60*60*24);
            var diffYears = difference/365.25;
            var imageCount = Math.ceil(diffYears/duration);
            if(!confirm("With the current interval duration and date settings, this will produce "+imageCount+" images. Click OK to continue.")){
                document.getElementById("dateslideranimimagesave").checked = false;
            }
        }
        else{
            alert("Please enter an interval duration before selecting to save images.");
            document.getElementById("dateslideranimimagesave").checked = false;
        }
    }
}

function checkLoading(){
    if(!loadingComplete){
        loadingComplete = true;
        loadPointsEvent = false;
        hideWorking();
    }
}

function checkObjectNotEmpty(obj){
    for(var i in obj){
        if(obj[i]) return true;
    }
    return false;
}

function checkPointToolSource(selector){
    if(!(selections.length >= 3)){
        document.getElementById(selector).value = 'all';
        alert('There must be at least 3 selected points on the map.');
    }
}

function checkReclassifyForm(){
    var rasterLayer = document.getElementById("reclassifysourcelayer").value;
    var outputName = document.getElementById("reclassifyOutputName").value;
    var rasterMinVal = document.getElementById("reclassifyRasterMin").value;
    var rasterMaxVal = document.getElementById("reclassifyRasterMax").value;
    var colorVal = document.getElementById("reclassifyColorVal").value;
    if(rasterLayer == "") alert("Please select a raster layer to reclassify.");
    else if(outputName == "") alert("Please enter a name for the output overlay.");
    else if(layersArr[outputName]) alert("The name for the output you entered is already being used by another layer. Please enter a different name.");
    else if(colorVal == "FFFFFF") alert("Please select a color other than white for this overlay.");
    else if(rasterMinVal == "" || rasterMaxVal == "") alert("Please enter a min and max value for the raster to reclassify.");
    else if(isNaN(rasterMinVal) || isNaN(rasterMaxVal)) alert("Please enter only numbers for the min and max values.");
    else{
        $("#reclassifytool").popup("hide");
        reclassifyRaster();
    }
}

function checkReclassifyToolOpen(){
    if(rasterLayers.length > 0){
        document.getElementById("reclassifyOutputName").value = "";
        buildReclassifyDropDown();
        document.getElementById("reclassifysourcelayer").selectedIndex = 0;
        setReclassifyTable();
        $("#maptools").popup("hide");
        $("#reclassifytool").popup("show");
    }
    else{
        alert('There are no raster layers available.')
    }
}

function checkVectorizeForm(){
    var rasterLayer = document.getElementById("vectorizesourcelayer").value;
    if(rasterLayer == "") alert("Please select an overlay layer to vectorize.");
    else{
        $("#vectorizeoverlaytool").popup("hide");
        vectorizeRaster();
    }
}

function checkVectorizeOverlayToolOpen(){
    if(checkObjectNotEmpty(vectorizeLayers) && selectInteraction.getFeatures().getArray().length == 1){
        buildVectorizeDropDown();
        document.getElementById("vectorizesourcelayer").selectedIndex = 0;
        $("#maptools").popup("hide");
        $("#vectorizeoverlaytool").popup("show");
    }
    else{
        alert('To use this tool, you must first use the Reclassify Tool to create at least one reclassified raster layer and have one, and only one, polygon selected from your Shapes layer to define the vectorize boundaries.')
    }
}

function cleanSelectionsLayer(){
    var selLayerFeatures = layersArr['select'].getSource().getFeatures();
    var currentlySelected = selectInteraction.getFeatures().getArray();
    for(i in selLayerFeatures){
        if(currentlySelected.indexOf(selLayerFeatures[i]) === -1){
            layersArr['select'].getSource().removeFeature(selLayerFeatures[i]);
        }
    }
}

function clearReclassifyForm() {
    document.getElementById("reclassifysourcelayer").selectedIndex = 0;
    document.getElementById("reclassifyOutputName").value = "";
    var tableDiv = document.getElementById("reclassifyTableDiv");
    tableDiv.removeChild(tableDiv.childNodes[0]);
    setReclassifyTable();
}

function clearSelections(){
    var selpoints = selections;
    selections = [];
    for(i in selpoints){
        if(!clusterPoints){
            var point = findOccPoint(selpoints[i]);
            var style = setSymbol(point);
            point.setStyle(style);
        }
    }
    layersArr['pointv'].getSource().changed();
    adjustSelectionsTab();
    document.getElementById("selectiontbody").innerHTML = '';
}

function clearTaxaSymbology(){
    for(i in taxaSymbology){
        taxaSymbology[i]['color'] = "E69E67";
        var keyName = 'taxaColor'+i;
        if(document.getElementById(keyName)){
            document.getElementById(keyName).color.fromString("E69E67");
        }
    }
}

function closeOccidInfoBox(){
    finderpopupcloser.onclick();
}

function coordFormat(){
    return(function(coord1){
        mouseCoords = coord1;
        if(coord1[0] < -180){coord1[0] = coord1[0] + 360};
        if(coord1[0] > 180){coord1[0] = coord1[0] - 360};
        var template = 'Lat: {y} Lon: {x}';
        var coord2 = [coord1[1], coord1[0]];
        return ol.coordinate.format(coord1,template,5);
    });
}

function createBuffers(){
    var bufferSize = document.getElementById("bufferSize").value;
    if(bufferSize == '' || isNaN(bufferSize)) alert("Please enter a number for the buffer size.");
    else if(selectInteraction.getFeatures().getArray().length >= 1){
        selectInteraction.getFeatures().forEach(function(feature){
            if(feature){
                var selectedClone = feature.clone();
                var geoType = selectedClone.getGeometry().getType();
                var geoJSONFormat = new ol.format.GeoJSON();
                var selectiongeometry = selectedClone.getGeometry();
                var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
                var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
                var featCoords = JSON.parse(geojsonStr).coordinates;
                if(geoType == 'Point'){
                    var turfFeature = turf.point(featCoords);
                }
                else if(geoType == 'LineString'){
                    var turfFeature = turf.lineString(featCoords);
                }
                else if(geoType == 'Polygon'){
                    var turfFeature = turf.polygon(featCoords);
                }
                else if(geoType == 'MultiPolygon'){
                    var turfFeature = turf.multiPolygon(featCoords);
                }
                else if(geoType == 'Circle'){
                    var center = fixedselectgeometry.getCenter();
                    var radius = fixedselectgeometry.getRadius();
                    var turfFeature = getWGS84CirclePoly(center,radius);
                }
                var buffered = turf.buffer(turfFeature,bufferSize,{units:'kilometers'});
                var buffpoly = geoJSONFormat.readFeature(buffered);
                buffpoly.getGeometry().transform(wgs84Projection,mapProjection);
                selectsource.addFeature(buffpoly);
            }
        });
        document.getElementById("bufferSize").value = '';
    }
    else{
        alert('You must have at least one shape selected in your Shapes layer to create a buffer polygon.');
    }
}

function createConcavePoly(){
    var source = document.getElementById('concavepolysource').value;
    var maxEdge = document.getElementById('concaveMaxEdgeSize').value;
    var features = [];
    var geoJSONFormat = new ol.format.GeoJSON();
    if(maxEdge != '' && !isNaN(maxEdge) && maxEdge > 0){
        if(source == 'all'){
            features = getTurfPointFeaturesetAll();
        }
        else if(source == 'selected'){
            if(selections.length >= 3){
                features = getTurfPointFeaturesetSelected();
            }
            else{
                document.getElementById('concavepolysource').value = 'all';
                alert('There must be at least 3 selected points on the map. Please either select more points or re-run this tool for all points.');
                return;
            }
        }
        if(features){
            var concavepoly = '';
            try{
                var options = {units: 'kilometers', maxEdge: Number(maxEdge)};
                concavepoly = turf.concave(features,options);
            }
            catch(e){
                alert('Concave polygon was not able to be calculated. Perhaps try using a larger value for the maximum edge length.');
            }
            if(concavepoly){
                var cnvepoly = geoJSONFormat.readFeature(concavepoly);
                cnvepoly.getGeometry().transform(wgs84Projection,mapProjection);
                selectsource.addFeature(cnvepoly);
            }
        }
        else{
            alert('There must be at least 3 points on the map to calculate polygon.');
        }
        document.getElementById('concavepolysource').value = 'all';
        document.getElementById('concaveMaxEdgeSize').value = '';
    }
    else{
        alert('Please enter a number for the maximum edge size.');
    }
}

function createConvexPoly(){
    var source = document.getElementById('convexpolysource').value;
    var features = [];
    var geoJSONFormat = new ol.format.GeoJSON();
    if(source == 'all'){
        features = getTurfPointFeaturesetAll();
    }
    else if(source == 'selected'){
        if(selections.length >= 3){
            features = getTurfPointFeaturesetSelected();
        }
        else{
            document.getElementById('convexpolysource').value = 'all';
            alert('There must be at least 3 selected points on the map. Please either select more points or re-run this tool for all points.');
            return;
        }
    }
    if(features){
        var convexpoly = turf.convex(features);
        if(convexpoly){
            var cnvxpoly = geoJSONFormat.readFeature(convexpoly);
            cnvxpoly.getGeometry().transform(wgs84Projection,mapProjection);
            selectsource.addFeature(cnvxpoly);
        }
    }
    else{
        alert('There must be at least 3 points on the map to calculate polygon.');
    }
    document.getElementById('convexpolysource').value = 'all';
}

function createDateSlider(dual){
    if(dsOldestDate && dsNewestDate){
        sliderdiv = document.createElement('div');
        sliderdiv.setAttribute("id","sliderdiv");
        sliderdiv.setAttribute("style","width:calc(95% - 250px);height:30px;bottom:10px;left:50px;display:block;position:absolute;z-index:3;");
        var minhandlediv = document.createElement('div');
        minhandlediv.setAttribute("id","custom-handle-min");
        minhandlediv.setAttribute("class","ui-slider-handle");
        var minlabeldiv = document.createElement('div');
        minlabeldiv.setAttribute("id","custom-label-min");
        minlabeldiv.setAttribute("class","custom-label");
        var minlabelArrowdiv = document.createElement('div');
        minlabelArrowdiv.setAttribute("id","custom-label-min-arrow");
        minlabelArrowdiv.setAttribute("class","label-arrow");
        minhandlediv.appendChild(minlabeldiv);
        minhandlediv.appendChild(minlabelArrowdiv);
        sliderdiv.appendChild(minhandlediv);
        var maxhandlediv = document.createElement('div');
        maxhandlediv.setAttribute("id","custom-handle-max");
        maxhandlediv.setAttribute("class","ui-slider-handle");
        var maxlabeldiv = document.createElement('div');
        maxlabeldiv.setAttribute("id","custom-label-max");
        maxlabeldiv.setAttribute("class","custom-label");
        maxhandlediv.appendChild(maxlabeldiv);
        var maxlabelArrowdiv = document.createElement('div');
        maxlabelArrowdiv.setAttribute("class","label-arrow");
        maxhandlediv.appendChild(maxlabeldiv);
        maxhandlediv.appendChild(maxlabelArrowdiv);
        sliderdiv.appendChild(maxhandlediv);
        document.body.appendChild(sliderdiv);

        var minDate = dsOldestDate.getTime();
        var maxDate = dsNewestDate.getTime();
        tsOldestDate = dsOldestDate;
        tsNewestDate = dsNewestDate;
        var hMinDate = new Date(minDate);
        var minDateStr = getISOStrFromDateObj(hMinDate);
        var hMaxDate = new Date(maxDate);
        var maxDateStr = getISOStrFromDateObj(hMaxDate);

        var minhandle = $("#custom-handle-min");
        var maxhandle = $("#custom-handle-max");
        $("#sliderdiv").slider({
            range: true,
            min: minDate,
            max: maxDate,
            values: [minDate,maxDate],
            create: function() {
                if(dual){
                    var mintextbox = $("#custom-label-min");
                    mintextbox.text(minDateStr);
                }
                var maxtextbox = $("#custom-label-max");
                maxtextbox.text(maxDateStr);
            },
            //step: 7 * 24 * 60 * 60 * 1000,
            step: 1000 * 60 * 60 * 24,
            slide: function(event, ui) {
                if(dual){
                    var mintextbox = $("#custom-label-min");
                    tsOldestDate = new Date(ui.values[0]);
                    var newMinDateStr = getISOStrFromDateObj(tsOldestDate);
                    mintextbox.text(newMinDateStr);
                    document.getElementById("datesliderearlydate").value = newMinDateStr;
                }
                var maxtextbox = $("#custom-label-max");
                tsNewestDate = new Date(ui.values[1]);
                var newMaxDateStr = getISOStrFromDateObj(tsNewestDate);
                maxtextbox.text(newMaxDateStr);
                document.getElementById("datesliderlatedate").value = newMaxDateStr;
                layersArr['pointv'].getSource().changed();
            }
        });
        if(!dual){
            document.getElementById("custom-handle-min").style.display = 'none';
            document.getElementById("custom-handle-min").style.position = 'absolute';
            document.getElementById("custom-handle-min").style.left = '-9999px';
        }
        document.getElementById("datesliderearlydate").value = minDateStr;
        document.getElementById("datesliderlatedate").value = maxDateStr;
        document.getElementById("dateslidercontrol").style.display = 'block';
        document.getElementById("maptoolcontainer").style.top = 'initial';
        document.getElementById("maptoolcontainer").style.left = 'initial';
        document.getElementById("maptoolcontainer").style.bottom = '100px';
        document.getElementById("maptoolcontainer").style.right = '-190px';
    }
}

function createPolyDifference(){
    var shapeCount = 0;
    selectInteraction.getFeatures().forEach(function(feature){
        var selectedClone = feature.clone();
        var geoType = selectedClone.getGeometry().getType();
        if(geoType == 'Polygon' || geoType == 'MultiPolygon' || geoType == 'Circle'){
            shapeCount++;
        }
    });
    if(shapeCount == 2){
        var features = [];
        var geoJSONFormat = new ol.format.GeoJSON();
        selectInteraction.getFeatures().forEach(function(feature){
            if(feature){
                var selectedClone = feature.clone();
                var geoType = selectedClone.getGeometry().getType();
                var selectiongeometry = selectedClone.getGeometry();
                var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
                var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
                var featCoords = JSON.parse(geojsonStr).coordinates;
                if(geoType == 'Polygon'){
                    features.push(turf.polygon(featCoords));
                }
                else if(geoType == 'MultiPolygon'){
                    features.push(turf.multiPolygon(featCoords));
                }
                else if(geoType == 'Circle'){
                    var center = fixedselectgeometry.getCenter();
                    var radius = fixedselectgeometry.getRadius();
                    features.push(getWGS84CirclePoly(center,radius));
                }
            }
        });
        var difference = turf.difference(features[0],features[1]);
        if(difference){
            var diffpoly = geoJSONFormat.readFeature(difference);
            diffpoly.getGeometry().transform(wgs84Projection,mapProjection);
            selectsource.addFeature(diffpoly);
        }
    }
    else{
        alert('You must have two polygons or circles, and only two polygons or circles, selected in your Shapes layer to find the difference.');
    }
}

function createPolyIntersect(){
    var shapeCount = 0;
    selectInteraction.getFeatures().forEach(function(feature){
        var selectedClone = feature.clone();
        var geoType = selectedClone.getGeometry().getType();
        if(geoType == 'Polygon' || geoType == 'MultiPolygon' || geoType == 'Circle'){
            shapeCount++;
        }
    });
    if(shapeCount == 2){
        var features = [];
        var geoJSONFormat = new ol.format.GeoJSON();
        selectInteraction.getFeatures().forEach(function(feature){
            if(feature){
                var selectedClone = feature.clone();
                var geoType = selectedClone.getGeometry().getType();
                var selectiongeometry = selectedClone.getGeometry();
                var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
                var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
                var featCoords = JSON.parse(geojsonStr).coordinates;
                if(geoType == 'Polygon'){
                    features.push(turf.polygon(featCoords));
                }
                else if(geoType == 'MultiPolygon'){
                    features.push(turf.multiPolygon(featCoords));
                }
                else if(geoType == 'Circle'){
                    var center = fixedselectgeometry.getCenter();
                    var radius = fixedselectgeometry.getRadius();
                    features.push(getWGS84CirclePoly(center,radius));
                }
            }
        });
        var intersection = turf.intersect(features[0],features[1]);
        if(intersection){
            var interpoly = geoJSONFormat.readFeature(intersection);
            interpoly.getGeometry().transform(wgs84Projection,mapProjection);
            selectsource.addFeature(interpoly);
        }
        else{
            alert('The two selected shapes do not intersect.');
        }
    }
    else{
        alert('You must have two polygons or circles, and only two polygons or circles, selected in your Shapes layer to find the intersect.');
    }
}

function createPolyUnion(){
    var shapeCount = 0;
    selectInteraction.getFeatures().forEach(function(feature){
        var selectedClone = feature.clone();
        var geoType = selectedClone.getGeometry().getType();
        if(geoType == 'Polygon' || geoType == 'MultiPolygon' || geoType == 'Circle'){
            shapeCount++;
        }
    });
    if(shapeCount > 1){
        var features = [];
        var geoJSONFormat = new ol.format.GeoJSON();
        selectInteraction.getFeatures().forEach(function(feature){
            if(feature){
                var selectedClone = feature.clone();
                var geoType = selectedClone.getGeometry().getType();
                var selectiongeometry = selectedClone.getGeometry();
                var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
                var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
                var featCoords = JSON.parse(geojsonStr).coordinates;
                if(geoType == 'Polygon'){
                    features.push(turf.polygon(featCoords));
                }
                else if(geoType == 'MultiPolygon'){
                    features.push(turf.multiPolygon(featCoords));
                }
                else if(geoType == 'Circle'){
                    var center = fixedselectgeometry.getCenter();
                    var radius = fixedselectgeometry.getRadius();
                    features.push(getWGS84CirclePoly(center,radius));
                }
            }
        });
        var union = turf.union(features[0],features[1]);
        for (f in features){
            if(f > 1){
                union = turf.union(union,features[f]);
            }
        }
        if(union){
            deleteSelections();
            var unionpoly = geoJSONFormat.readFeature(union);
            unionpoly.getGeometry().transform(wgs84Projection,mapProjection);
            selectsource.addFeature(unionpoly);
            document.getElementById("selectlayerselect").value = 'select';
            setActiveLayer();
        }
    }
    else{
        alert('You must have at least two polygons or circles selected in your Shapes layer to find the union.');
    }
}

function deleteSelections(){
    selectInteraction.getFeatures().forEach(function(feature){
        layersArr['select'].getSource().removeFeature(feature);
    });
    selectInteraction.getFeatures().clear();
    if(layersArr['select'].getSource().getFeatures().length < 1){
        removeUserLayer('select');
    }
}

function downloadShapesLayer(){
    var dlType = document.getElementById("shapesdownloadselect").value;
    var format = '';
    if(dlType == ''){
        alert('Please select a download format.');
        return;
    }
    else if(dlType == 'kml'){
        var format = new ol.format.KML();
        var filetype = 'application/vnd.google-earth.kml+xml';
    }
    else if(dlType == 'geojson'){
        var format = new ol.format.GeoJSON();
        var filetype = 'application/vnd.geo+json';
    }
    var features = layersArr['select'].getSource().getFeatures();
    var fixedFeatures = setDownloadFeatures(features);
    var exportStr = format.writeFeatures(fixedFeatures,{'dataProjection': wgs84Projection, 'featureProjection': mapProjection});
    if(dlType == 'kml'){
        exportStr = exportStr.replace(/<kml xmlns="http:\/\/www.opengis.net\/kml\/2.2" xmlns:gx="http:\/\/www.google.com\/kml\/ext\/2.2" xmlns:xsi="http:\/\/www.w3.org\/2001\/XMLSchema-instance" xsi:schemaLocation="http:\/\/www.opengis.net\/kml\/2.2 https:\/\/developers.google.com\/kml\/schema\/kml22gx.xsd">/g,'<kml xmlns="http://www.opengis.net/kml/2.2"><Document id="root_doc"><Folder><name>shapes_export</name>');
        exportStr = exportStr.replace(/<Placemark>/g,'<Placemark><Style><LineStyle><color>ff000000</color><width>1</width></LineStyle><PolyStyle><color>4DAAAAAA</color><fill>1</fill></PolyStyle></Style>');
        exportStr = exportStr.replace(/<Polygon>/g,'<Polygon><altitudeMode>clampToGround</altitudeMode>');
        exportStr = exportStr.replace(/<\/kml>/g,'</Folder></Document></kml>');
    }
    var filename = 'shapes_'+getDateTimeString()+'.'+dlType;
    var blob = new Blob([exportStr], {type: filetype});
    if(window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveBlob(blob, filename);
    }
    else{
        var elem = window.document.createElement('a');
        elem.href = window.URL.createObjectURL(blob);
        elem.download = filename;
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }
}

function exportMapPNG(filename,zip){
    map.once('postcompose', function(event) {
        var canvas = document.getElementsByTagName('canvas').item(0);
        if(zip){
            var image = canvas.toDataURL('image/png', 1.0);
            zipFolder.file(filename, image.substr(image.indexOf(',')+1), {base64: true});
            if(dsAnimImageSave && dsAnimStop){
                zipFile.generateAsync({type:"blob"}).then(function(content) {
                    var zipfilename = 'dateanimationimages_'+getDateTimeString()+'.zip';
                    saveAs(content,zipfilename);
                });
            }
        }
        else if(navigator.msSaveBlob) {
            navigator.msSaveBlob(canvas.msToBlob(),filename);
        }
        else{
            canvas.toBlob(function(blob) {
                saveAs(blob,filename);
            });
        }
    });
    map.renderSync();
}

function exportTaxaCSV(){
    var csvContent = '';
    csvContent = '"ScientificName","Family","RecordCount"'+"\n";
    var sortedTaxa = arrayIndexSort(taxaSymbology).sort();
    for(i in sortedTaxa){
        var family = taxaSymbology[sortedTaxa[i]]['family'].toLowerCase();
        family = family.charAt(0).toUpperCase()+family.slice(1);
        var row = taxaSymbology[sortedTaxa[i]]['sciname']+','+family+','+taxaSymbology[sortedTaxa[i]]['count']+"\n";
        csvContent += row;
    }
    var filename = 'taxa_list.csv';
    var filetype = 'text/csv; charset=utf-8';
    var blob = new Blob([csvContent], {type: filetype});
    if(window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveBlob(blob,filename);
    }
    else{
        var elem = window.document.createElement('a');
        elem.href = window.URL.createObjectURL(blob);
        elem.download = filename;
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }
}

function extensionSelected(obj){
    if(obj.checked == true){
        document.getElementById('csvzip').checked = true;
    }
}

function findOccCluster(occid){
    var clusters = layersArr['pointv'].getSource().getFeatures();
    for(c in clusters){
        var clusterindex = clusters[c].get('identifiers');
        if(clusterindex.indexOf(Number(occid)) !== -1){
            return clusters[c];
        }
    }
}

function findOccClusterPosition(occid){
    if(spiderCluster){
        var spiderPoints = layersArr['spider'].getSource().getFeatures();
        for(p in spiderPoints){
            if(spiderPoints[p].get('features')[0].get('occid') == Number(occid)){
                return spiderPoints[p].getGeometry().getCoordinates();
            }
        }
    }
    else if(clusterPoints){
        var clusters = layersArr['pointv'].getSource().getFeatures();
        for(c in clusters){
            var clusterindex = clusters[c].get('identifiers');
            if(clusterindex.indexOf(Number(occid)) !== -1){
                return clusters[c].getGeometry().getCoordinates();
            }
        }
    }
    else{
        var features = layersArr['pointv'].getSource().getFeatures();
        for(f in features){
            if(Number(features[f].get('occid')) == occid){
                return features[f].getGeometry().getCoordinates();
            }
        }
    }
}

function findOccPoint(occid){
    var features = layersArr['pointv'].getSource().getFeatures();
    for(f in features){
        if(Number(features[f].get('occid')) == occid){
            return features[f];
        }
    }
}

function findOccPointInCluster(cluster,occid){
    var cFeatures = cluster.get('features');
    for (f in cFeatures) {
        if(Number(cFeatures[f].get('occid')) == occid){
            return cFeatures[f];
        }
    }
}

function finishGetGeographyParams(){
    if(!geoCallOut){
        if(tempcqlArr.length > 0){
            var temcqlfrag = '';
            for(i in tempcqlArr){
                temcqlfrag += ' OR '+tempcqlArr[i];
            }
            temcqlfrag = '('+temcqlfrag.substr(4,temcqlfrag.length)+')';
            cqlArr.push(temcqlfrag);
            buildCQLString();
            buildSOLRQString();
        }
    }
}

function formatCheckDate(dateStr){
    if(dateStr != ""){
        var dateArr = parseDate(dateStr);
        if(dateArr['y'] == 0){
            alert("Please use the following date formats: yyyy-mm-dd, mm/dd/yyyy, or dd mmm yyyy");
            return false;
        }
        else{
            //Invalid format is month > 12
            if(dateArr['m'] > 12){
                alert("Month cannot be greater than 12. Note that the format should be YYYY-MM-DD");
                return false;
            }

            //Check to see if day is valid
            if(dateArr['d'] > 28){
                if(dateArr['d'] > 31
                    || (dateArr['d'] == 30 && dateArr['m'] == 2)
                    || (dateArr['d'] == 31 && (dateArr['m'] == 4 || dateArr['m'] == 6 || dateArr['m'] == 9 || dateArr['m'] == 11))){
                    alert("The Day (" + dateArr['d'] + ") is invalid for that month");
                    return false;
                }
            }

            //Enter date into date fields
            var mStr = dateArr['m'];
            if(mStr.length == 1){
                mStr = "0" + mStr;
            }
            var dStr = dateArr['d'];
            if(dStr.length == 1){
                dStr = "0" + dStr;
            }
            return dateArr['y'] + "-" + mStr + "-" + dStr;
        }
    }
}

function generateRandColor(){
    var hexColor = '';
    var x = Math.round(0xffffff * Math.random()).toString(16);
    var y = (6-x.length);
    var z = '000000';
    var z1 = z.substring(0,y);
    hexColor = z1 + x;
    return hexColor;
}

function generateReclassifySLD(valueArr,layername){
    var sldContent = '';
    sldContent += '<?xml version="1.0" encoding="UTF-8"?>';
    sldContent += '<StyledLayerDescriptor version="1.0.0" ';
    sldContent += 'xsi:schemaLocation="http://www.opengis.net/sld StyledLayerDescriptor.xsd" ';
    sldContent += 'xmlns="http://www.opengis.net/sld" ';
    sldContent += 'xmlns:ogc="http://www.opengis.net/ogc" ';
    sldContent += 'xmlns:xlink="http://www.w3.org/1999/xlink" ';
    sldContent += 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
    sldContent += '<NamedLayer>';
    sldContent += '<Name>'+layername+'</Name>';
    sldContent += '<UserStyle>';
    sldContent += '<FeatureTypeStyle>';
    sldContent += '<Rule>';
    sldContent += '<Name>reclassify_style</Name>';
    sldContent += '<RasterSymbolizer>';
    sldContent += '<Opacity>1.0</Opacity>';
    sldContent += '<ColorMap type="intervals">';
    sldContent += '<ColorMapEntry color="#FFFFFF" quantity="'+valueArr['rasmin']+'"/>';
    sldContent += '<ColorMapEntry color="#'+valueArr['color']+'" quantity="'+valueArr['rasmax']+'"/>';
    sldContent += '</ColorMap>';
    sldContent += '</RasterSymbolizer>';
    sldContent += '</Rule>';
    sldContent += '</FeatureTypeStyle>';
    sldContent += '</UserStyle>';
    sldContent += '</NamedLayer>';
    sldContent += '</StyledLayerDescriptor>';
    return sldContent;
}

function generateWPSPolyExtractXML(valueArr,layername,geojsonstr){
    var xmlContent = '';
    xmlContent += '<?xml version="1.0" encoding="UTF-8"?><wps:Execute version="1.0.0" service="WPS" ';
    xmlContent += 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.opengis.net/wps/1.0.0" ';
    xmlContent += 'xmlns:wfs="http://www.opengis.net/wfs" xmlns:wps="http://www.opengis.net/wps/1.0.0" ';
    xmlContent += 'xmlns:ows="http://www.opengis.net/ows/1.1" xmlns:gml="http://www.opengis.net/gml" ';
    xmlContent += 'xmlns:ogc="http://www.opengis.net/ogc" xmlns:wcs="http://www.opengis.net/wcs/1.1.1" ';
    xmlContent += 'xmlns:xlink="http://www.w3.org/1999/xlink" ';
    xmlContent += 'xsi:schemaLocation="http://www.opengis.net/wps/1.0.0 http://schemas.opengis.net/wps/1.0.0/wpsAll.xsd">';
    xmlContent += '<ows:Identifier>ras:PolygonExtraction</ows:Identifier>';
    xmlContent += '<wps:DataInputs>';
    xmlContent += '<wps:Input>';
    xmlContent += '<ows:Identifier>data</ows:Identifier>';
    xmlContent += '<wps:Reference mimeType="image/tiff" xlink:href="http://geoserver/wcs" method="POST">';
    xmlContent += '<wps:Body>';
    xmlContent += '<wcs:GetCoverage service="WCS" version="1.1.1">';
    xmlContent += '<ows:Identifier>'+layername+'</ows:Identifier>';
    xmlContent += '<wcs:DomainSubset>';
    xmlContent += '<ows:BoundingBox crs="http://www.opengis.net/gml/srs/epsg.xml#4326">';
    xmlContent += '<ows:LowerCorner>-180.0 -90.0</ows:LowerCorner>';
    xmlContent += '<ows:UpperCorner>180.0 90.0</ows:UpperCorner>';
    xmlContent += '</ows:BoundingBox>';
    xmlContent += '</wcs:DomainSubset>';
    xmlContent += '<wcs:Output format="image/tiff"/>';
    xmlContent += '</wcs:GetCoverage>';
    xmlContent += '</wps:Body>';
    xmlContent += '</wps:Reference>';
    xmlContent += '</wps:Input>';
    xmlContent += '<wps:Input>';
    xmlContent += '<ows:Identifier>band</ows:Identifier>';
    xmlContent += '<wps:Data>';
    xmlContent += '<wps:LiteralData>0</wps:LiteralData>';
    xmlContent += '</wps:Data>';
    xmlContent += '</wps:Input>';
    xmlContent += '<wps:Input>';
    xmlContent += '<ows:Identifier>insideEdges</ows:Identifier>';
    xmlContent += '<wps:Data>';
    xmlContent += '<wps:LiteralData>0</wps:LiteralData>';
    xmlContent += '</wps:Data>';
    xmlContent += '</wps:Input>';
    xmlContent += '<wps:Input>';
    xmlContent += '<ows:Identifier>roi</ows:Identifier>';
    xmlContent += '<wps:Data>';
    xmlContent += '<wps:ComplexData mimeType="application/json"><![CDATA['+geojsonstr+']]></wps:ComplexData>';
    xmlContent += '</wps:Data>';
    xmlContent += '</wps:Input>';
    xmlContent += '<wps:Input>';
    xmlContent += '<ows:Identifier>nodata</ows:Identifier>';
    xmlContent += '<wps:Data>';
    xmlContent += '<wps:LiteralData>0</wps:LiteralData>';
    xmlContent += '</wps:Data>';
    xmlContent += '</wps:Input>';
    xmlContent += '<wps:Input>';
    xmlContent += '<ows:Identifier>ranges</ows:Identifier>';
    xmlContent += '<wps:Data>';
    xmlContent += '<wps:LiteralData>('+valueArr['rasmin']+';'+valueArr['rasmax']+')</wps:LiteralData>';
    xmlContent += '</wps:Data>';
    xmlContent += '</wps:Input>';
    xmlContent += '</wps:DataInputs>';
    xmlContent += '<wps:ResponseForm>';
    xmlContent += '<wps:RawDataOutput mimeType="application/json">';
    xmlContent += '<ows:Identifier>result</ows:Identifier>';
    xmlContent += '</wps:RawDataOutput>';
    xmlContent += '</wps:ResponseForm>';
    xmlContent += '</wps:Execute>';
    return xmlContent;
}

function getCollectionParams(){
    var dbElements = document.getElementsByName("db[]");
    var c = false;
    var all = true;
    var collid = '';
    var cqlfrag = '';
    var solrqfrag = '';
    for(i = 0; i < dbElements.length; i++){
        var dbElement = dbElements[i];
        if(dbElement.checked && !isNaN(dbElement.value)){
            if(c == true) collid = collid+" ";
            collid = collid + dbElement.value;
            c = true;
        }
        if(!dbElement.checked && !isNaN(dbElement.value)){
            all = false;
        }
    }
    if(all == false && c == true){
        if(collid.substr(collid.length-1,collid.length)==','){
            collid = collid.substr(0,collid.length-1);
        }
        cqlfrag = '(collid IN('+collid+'))';
        cqlArr.push(cqlfrag);
        solrqfrag = '(collid:('+collid+'))';
        solrqArr.push(solrqfrag);
        return true;
    }
    else if(all == false && c == false){
        alert("Please choose at least one collection");
        return false;
    }
    else{
        return true;
    }
}

function getDateTimeString(){
    var now = new Date();
    var dateTimeString = now.getFullYear().toString();
    dateTimeString += (((now.getMonth()+1) < 10)?'0':'')+(now.getMonth()+1).toString();
    dateTimeString += ((now.getDate() < 10)?'0':'')+now.getDate().toString();
    dateTimeString += ((now.getHours() < 10)?'0':'')+now.getHours().toString();
    dateTimeString += ((now.getMinutes() < 10)?'0':'')+now.getMinutes().toString();
    dateTimeString += ((now.getSeconds() < 10)?'0':'')+now.getSeconds().toString();
    return dateTimeString;
}

function getDragDropStyle(feature, resolution) {
    var featureStyleFunction = feature.getStyleFunction();
    if(featureStyleFunction) {
        return featureStyleFunction.call(feature, resolution);
    }
    else{
        return dragDropStyle[feature.getGeometry().getType()];
    }
}

function getGeographyParams(vector){
    tempcqlArr = [];
    var totalArea = 0;
    selectInteraction.getFeatures().forEach(function(feature){
        var cqlfrag = '';
        var solrqfrag = '';
        var geoCqlString = '';
        var geoSolrqString = '';
        if(feature){
            var selectedClone = feature.clone();
            var geoType = selectedClone.getGeometry().getType();
            var wktFormat = new ol.format.WKT();
            var geoJSONFormat = new ol.format.GeoJSON();
            if(geoType == 'MultiPolygon' || geoType == 'Polygon') {
                var selectiongeometry = selectedClone.getGeometry();
                var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
                var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
                var polyCoords = JSON.parse(geojsonStr).coordinates;
                if (geoType == 'MultiPolygon') {
                    var areaFeat = turf.multiPolygon(polyCoords);
                    var area = turf.area(areaFeat);
                    var area_km = area/1000/1000;
                    totalArea = totalArea + area_km;
                    for (e in polyCoords) {
                        var singlePoly = turf.polygon(polyCoords[e]);
                        //console.log('start multipolygon length: '+singlePoly.geometry.coordinates.length);
                        if(singlePoly.geometry.coordinates.length > 10){
                            var options = {tolerance: 0.001, highQuality: true};
                            singlePoly = turf.simplify(singlePoly,options);
                        }
                        //console.log('end multipolygon length: '+singlePoly.geometry.coordinates.length);
                        polyCoords[e] = singlePoly.geometry.coordinates;
                    }
                    var turfSimple = turf.multiPolygon(polyCoords);
                }
                if (geoType == 'Polygon') {
                    var areaFeat = turf.polygon(polyCoords);
                    var area = turf.area(areaFeat);
                    var area_km = area/1000/1000;
                    totalArea = totalArea + area_km;
                    //console.log('start multipolygon length: '+areaFeat.geometry.coordinates.length);
                    if(areaFeat.geometry.coordinates.length > 10){
                        var options = {tolerance: 0.001, highQuality: true};
                        areaFeat = turf.simplify(areaFeat,options);
                    }
                    //console.log('end multipolygon length: '+areaFeat.geometry.coordinates.length);
                    polyCoords = areaFeat.geometry.coordinates;
                    var turfSimple = turf.polygon(polyCoords);
                }
                var polySimple = geoJSONFormat.readFeature(turfSimple,{featureProjection:'EPSG:3857'});
                var simplegeometry = polySimple.getGeometry();
                var fixedgeometry = simplegeometry.transform(mapProjection,wgs84Projection);
                var wmswktString = wktFormat.writeGeometry(fixedgeometry);
                var geocoords = fixedgeometry.getCoordinates();
                var wfswktString = writeWfsWktString(geoType,geocoords);
                if(vector){
                    geoCqlString += "((WITHIN(geo,"+wfswktString+")))";
                }
                else{
                    geoCqlString += "((WITHIN(geo,"+wmswktString+")))";
                }
                geoSolrqString = '"Intersects('+wmswktString+')"';
                cqlfrag = '('+geoCqlString+')';
                tempcqlArr.push(cqlfrag);
                solrqfrag = geoSolrqString;
                solrgeoqArr.push(solrqfrag);
            }
            if(geoType == 'Circle'){
                var center = selectedClone.getGeometry().getCenter();
                var radius = selectedClone.getGeometry().getRadius();
                var edgeCoordinate = [center[0] + radius, center[1]];
                var wgs84Sphere = new ol.Sphere(6378137);
                var groundRadius = wgs84Sphere.haversineDistance(
                    ol.proj.transform(center, 'EPSG:3857', 'EPSG:4326'),
                    ol.proj.transform(edgeCoordinate, 'EPSG:3857', 'EPSG:4326')
                );
                groundRadius = groundRadius/1000;
                var circleArea = Math.PI*groundRadius*groundRadius;
                totalArea = totalArea + circleArea;
                var fixedcenter = ol.proj.transform(center,'EPSG:3857','EPSG:4326');
                geoSolrqString = '{!geofilt sfield=geo pt='+fixedcenter[1]+','+fixedcenter[0]+' d='+groundRadius+'}';
                solrqfrag = geoSolrqString;
                solrgeoqArr.push(solrqfrag);
                buildSOLRQString();
                geoCallOut = true;
                solroccqString = 'q=*:*&fq='+geoSolrqString;
                getSOLROccArr(function(res){
                    geoCallOut = false;
                    if(res){
                        var occStr = res.join();
                        geoCqlString = '(occid IN('+occStr+'))';
                        cqlfrag = '('+geoCqlString+')';
                        tempcqlArr.push(cqlfrag);
                        finishGetGeographyParams();
                    }
                });
            }
        }
    });
    if(totalArea === 0){
        document.getElementById("polyarea").value = totalArea;
    }
    else{
        document.getElementById("polyarea").value = totalArea.toFixed(2);
    }
    finishGetGeographyParams();
}

function getISOStrFromDateObj(dObj){
    var dYear = dObj.getFullYear();
    var dMonth = ((dObj.getMonth() + 1) > 9?(dObj.getMonth() + 1):'0'+(dObj.getMonth() + 1));
    var dDay = (dObj.getDate() > 9?dObj.getDate():'0'+dObj.getDate());

    return dYear+'-'+dMonth+'-'+dDay;
}

function getPointInfoArr(cluster){
    var feature = (cluster.get('features')?cluster.get('features')[0]:cluster);
    var infoArr = [];
    infoArr['occid'] = Number(feature.get('occid'));
    infoArr['institutioncode'] = (feature.get('InstitutionCode')?feature.get('InstitutionCode'):'');
    infoArr['catalognumber'] = (feature.get('catalogNumber')?feature.get('catalogNumber'):'');
    var recordedby = (feature.get('recordedBy')?feature.get('recordedBy'):'');
    var recordnumber = (feature.get('recordNumber')?feature.get('recordNumber'):'');
    infoArr['collector'] = (recordedby?recordedby:'')+(recordedby&&recordnumber?' ':'')+(recordnumber?recordnumber:'');
    infoArr['eventdate'] = (feature.get('displayDate')?feature.get('displayDate'):'');
    infoArr['sciname'] = (feature.get('sciname')?feature.get('sciname'):'');
    //var country = (feature.get('country')?feature.get('country'):'');
    //var stateProvince = (feature.get('StateProvince')?feature.get('StateProvince'):'');
    //var county = (feature.get('county')?feature.get('county'):'');
    //infoArr['locality'] = (country?country:'')+(country&&stateProvince?'; ':'')+(stateProvince?stateProvince:'')+(country||stateProvince?'; ':'')+(county?county:'');

    return infoArr;
}

function getPointStyle(feature) {
    var style = '';
    if(clusterPoints){
        style = setClusterSymbol(feature);
    }
    else{
        style = setSymbol(feature);
    }
    return style;
}

function getSOLROccArr(callback){
    getSOLRRecCnt(true,function(res) {
        if(solrRecCnt){
            var occArr = [];
            var http = new XMLHttpRequest();
            var url = "rpc/SOLRConnector.php";
            var params = solroccqString+'&rows='+solrRecCnt+'&start=0&fl=occid&wt=json';
            //console.log(url+'?'+params);
            http.open("POST", url, true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function() {
                if(http.readyState == 4 && http.status == 200) {
                    var resArr = JSON.parse(http.responseText);
                    var recArr = resArr['response']['docs'];
                    for(i in recArr){
                        occArr.push(recArr[i]['occid']);
                    }
                    callback(occArr);
                }
            }
            http.send(params);
        }
    });
}

function getSOLRRecCnt(occ,callback){
    solrRecCnt = 0;
    var qStr = '';
    if(occ){
        qStr = solroccqString;
    }
    else{
        qStr = solrqString;
    }
    var http = new XMLHttpRequest();
    var url = "rpc/SOLRConnector.php";
    var params = qStr+'&rows=0&start=0&wt=json';
    //console.log(url + "?" +  params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            var resArr = JSON.parse(http.responseText);
            solrRecCnt = resArr['response']['numFound'];
            document.getElementById("dh-rows").value = solrRecCnt;
            callback(1);
        }
    };
    http.send(params);
}

function getTextParams(){
    var cqlfrag = '';
    var solrqfrag = '';
    var countryval = document.getElementById("country").value.trim();
    var stateval = document.getElementById("state").value.trim();
    var countyval = document.getElementById("county").value.trim();
    var localityval = document.getElementById("locality").value.trim();
    var collectorval = document.getElementById("collector").value.trim();
    var collnumval = document.getElementById("collnum").value.trim();
    var colldate1 = document.getElementById("eventdate1").value.trim();
    var colldate2 = document.getElementById("eventdate2").value.trim();
    var catnumval = document.getElementById("catnum").value.trim();
    var othercatnumval = document.getElementById("othercatnum").value.trim();
    var typestatus = document.getElementById("typestatus").checked;
    var hasimages = document.getElementById("hasimages").checked;
    var hasgenetic = document.getElementById("hasgenetic").checked;

    if(countryval){
        var countryvals = countryval.split(',');
        var countryCqlString = '';
        var countrySolrqString = '';
        for(i = 0; i < countryvals.length; i++){
            if(countryCqlString) countryCqlString += " OR ";
            if(countrySolrqString) countrySolrqString += " OR ";
            countryCqlString += "(country = '"+countryvals[i]+"')";
            countrySolrqString += '(country:"'+countryvals[i]+'")';
        }
        cqlfrag = '('+countryCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+countrySolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(stateval){
        var statevals = stateval.split(',');
        var stateCqlString = '';
        var stateSolrqString = '';
        for(i = 0; i < statevals.length; i++){
            if(stateCqlString) stateCqlString += " OR ";
            if(stateSolrqString) stateSolrqString += " OR ";
            stateCqlString += "(StateProvince = '"+statevals[i]+"')";
            stateSolrqString += '(StateProvince:"'+statevals[i]+'")';
        }
        cqlfrag = '('+stateCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+stateSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(countyval){
        var countyvals = countyval.split(',');
        var countyCqlString = '';
        var countySolrqString = '';
        for(i = 0; i < countyvals.length; i++){
            if(countyCqlString) countyCqlString += " OR ";
            if(countySolrqString) countySolrqString += " OR ";
            countyCqlString += "(county LIKE '"+countyvals[i]+"%')";
            countySolrqString += "(county:"+countyvals[i].replace(" ","\\ ")+"*)";
        }
        cqlfrag = '('+countyCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+countySolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(localityval){
        var localityvals = localityval.split(',');
        var localityCqlString = '';
        var localitySolrqString = '';
        for(i = 0; i < localityvals.length; i++){
            if(localityCqlString) localityCqlString += " OR ";
            if(localitySolrqString) localitySolrqString += " OR ";
            localityCqlString += "(";
            localitySolrqString += "(";
            if(localityvals[i].indexOf(" ") !== -1){
                var templocalityCqlString = '';
                var templocalitySolrqString = '';
                var vals = localityvals[i].split(" ");
                for(i = 0; i < vals.length; i++){
                    if(templocalityCqlString) templocalityCqlString += " AND ";
                    if(templocalitySolrqString) templocalitySolrqString += " AND ";
                    templocalityCqlString += "locality LIKE '%"+vals[i]+"%'";
                    templocalitySolrqString += '((municipality:'+vals[i]+'*) OR (locality:*'+vals[i]+'*))';
                }
                localityCqlString += templocalityCqlString;
                localitySolrqString += templocalitySolrqString;
            }
            else{
                localityCqlString += "locality LIKE '%"+localityvals[i]+"%'";
                localitySolrqString += '(locality:*'+localityvals[i]+'*)';
            }
            localityCqlString += ")";
            localitySolrqString += ")";
        }
        cqlfrag = '('+localityCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+localitySolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(collectorval){
        var collectorvals = collectorval.split(',');
        var collectorCqlString = '';
        var collectorSolrqString = '';
        if(collectorvals.length == 1){
            collectorCqlString = "(recordedBy LIKE '%"+collectorvals[0]+"%')";
            collectorSolrqString = '(recordedBy:*'+collectorvals[0].replace(" ","\\ ")+'*)';
        }
        else if(collectorvals.length > 1){
            for (i in collectorvals){
                collectorCqlString += " OR (recordedBy LIKE '%"+collectorvals[i]+"%')";
                collectorSolrqString += ' OR (recordedBy:*'+collectorvals[i].replace(" ","\\ ")+'*)';
            }
            collectorCqlString = collectorCqlString.substr(4,collectorCqlString.length);
            collectorSolrqString = collectorSolrqString.substr(4,collectorSolrqString.length);
        }
        cqlfrag = '('+collectorCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+collectorSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(collnumval){
        var collnumvals = collnumval.split(',');
        var collnumCqlString = '';
        var collnumSolrqString = '';
        for (i in collnumvals){
            if(collnumvals[i].indexOf(" - ") !== -1){
                var pos = collnumvals[i].indexOf(" - ");
                var t1 = collnumvals[i].substr(0,pos).trim();
                var t2 = collnumvals[i].substr(pos+3,collnumvals[i].length).trim();
                if(!isNaN(t1) && !isNaN(t2)){
                    collnumCqlString += " OR (recordNumber BETWEEN "+t1+" AND "+t2+")";
                    collnumSolrqString += ' OR (recordNumber:['+t1+' TO '+t2+'])';
                }
                else{
                    collnumCqlString += " OR (recordNumber BETWEEN '"+t1+"' AND '"+t2+"')";
                    collnumSolrqString += " OR (recordNumber:['"+t1+"' TO '"+t2+"'])";
                }
            }
            else{
                collnumCqlString += " OR (recordNumber = '"+collnumvals[i]+"')";
                collnumSolrqString += ' OR (recordNumber:"'+collnumvals[i]+'")';
            }
        }
        collnumCqlString = collnumCqlString.substr(4,collnumCqlString.length);
        collnumSolrqString = collnumSolrqString.substr(4,collnumSolrqString.length);
        cqlfrag = '('+collnumCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+collnumSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(colldate1 || colldate2){
        var colldateCqlString = '';
        var colldateSolrqString = '';
        if(!colldate1 && colldate2){
            colldate1 = colldate2;
            colldate2 = '';
        }
        colldate1 = formatCheckDate(colldate1);
        if(colldate2){
            colldate2 = formatCheckDate(colldate2);
        }
        if(colldate2){
            colldateCqlString += "(eventDate BETWEEN '"+colldate1+"' AND '"+colldate2+"')";
            colldateSolrqString += '(eventDate:['+colldate1+'T00:00:00Z TO '+colldate2+'T23:59:59.999Z])';
        }
        else{
            if(colldate1.substr(colldate1.length-5,colldate1.length) == '00-00'){
                colldateCqlString += "(coll_year = "+colldate1.substr(0,4)+")";
                colldateSolrqString += '(coll_year:'+colldate1.substr(0,4)+')';
            }
            else if(colldate1.substr(colldate1.length-2,colldate1.length) == '00'){
                colldateCqlString += "((coll_year = "+colldate1.substr(0,4)+") AND (coll_month = "+colldate1.substr(5,7)+"))";
                colldateSolrqString += '((coll_year:'+colldate1.substr(0,4)+') AND (coll_month:'+colldate1.substr(5,7)+'))';
            }
            else{
                colldateCqlString += "(eventDate = '"+colldate1+"')";
                colldateSolrqString += '(eventDate:['+colldate1+'T00:00:00Z TO '+colldate1+'T23:59:59.999Z])';
            }
        }
        cqlfrag = colldateCqlString;
        cqlArr.push(cqlfrag);
        solrqfrag = '('+colldateSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(catnumval){
        var catnumvals = catnumval.split(',');
        var catnumCqlString = '';
        var catnumSolrqString = '';
        for(i = 0; i < catnumvals.length; i++){
            if(catnumCqlString) catnumCqlString += " OR ";
            if(catnumSolrqString) catnumSolrqString += " OR ";
            catnumCqlString += "(catalogNumber = '"+catnumvals[i]+"')";
            catnumSolrqString += '(catalogNumber:"'+catnumvals[i]+'")';
        }
        cqlfrag = '('+catnumCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+catnumSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(othercatnumval){
        var othercatnumvals = othercatnumval.split(',');
        var othercatnumCqlString = '';
        var othercatnumSolrqString = '';
        for(i = 0; i < othercatnumvals.length; i++){
            if(othercatnumCqlString) othercatnumCqlString += " OR ";
            if(othercatnumSolrqString) othercatnumSolrqString += " OR ";
            othercatnumCqlString += "(otherCatalogNumbers = '"+othercatnumvals[i]+"')";
            othercatnumSolrqString += '(otherCatalogNumbers:"'+othercatnumvals[i]+'")';
        }
        cqlfrag = '('+othercatnumCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+othercatnumSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(typestatus){
        var typestatusCqlString = "typeStatus LIKE '_%'";
        var typestatusSolrqString = "(typeStatus:[* TO *])";
        cqlfrag = '('+typestatusCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+typestatusSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(hasimages){
        var hasimagesCqlString = "imgid LIKE '_%'";
        var hasimagesSolrqString = "(imgid:[* TO *])";
        cqlfrag = '('+hasimagesCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+hasimagesSolrqString+')';
        solrqArr.push(solrqfrag);
    }
    if(hasgenetic){
        var hasgeneticCqlString = "resourcename LIKE '_%'";
        var hasgeneticSolrqString = "(resourcename:[* TO *])";
        cqlfrag = '('+hasgeneticCqlString+')';
        cqlArr.push(cqlfrag);
        solrqfrag = '('+hasgeneticSolrqString+')';
        solrqArr.push(solrqfrag);
    }
}

function getTurfPointFeaturesetAll(){
    var turfFeatureArr = [];
    var geoJSONFormat = new ol.format.GeoJSON();
    if(clusterPoints){
        var clusters = layersArr['pointv'].getSource().getFeatures();
        for(c in clusters){
            var cFeatures = clusters[c].get('features');
            for (f in cFeatures) {
                var selectedClone = cFeatures[f].clone();
                var selectiongeometry = selectedClone.getGeometry();
                var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
                var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
                var pntCoords = JSON.parse(geojsonStr).coordinates;
                turfFeatureArr.push(turf.point(pntCoords));
            }
        }
    }
    else{
        var features = layersArr['pointv'].getSource().getFeatures();
        for(f in features){
            var selectedClone = features[f].clone();
            var selectiongeometry = selectedClone.getGeometry();
            var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
            var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
            var pntCoords = JSON.parse(geojsonStr).coordinates;
            turfFeatureArr.push(turf.point(pntCoords));
        }
    }
    if(turfFeatureArr.length >= 3){
        var turfCollection = turf.featureCollection(turfFeatureArr);
        return turfCollection;
    }
    else{
        return false;
    }
}

function getTurfPointFeaturesetSelected(){
    var turfFeatureArr = [];
    var geoJSONFormat = new ol.format.GeoJSON();
    for(i in selections){
        var point = '';
        if(clusterPoints){
            var cluster = findOccCluster(selections[i]);
            point = findOccPointInCluster(cluster,selections[i]);
        }
        else{
            point = findOccPoint(selections[i]);
        }
        if(point){
            var selectedClone = point.clone();
            var selectiongeometry = selectedClone.getGeometry();
            var fixedselectgeometry = selectiongeometry.transform(mapProjection,wgs84Projection);
            var geojsonStr = geoJSONFormat.writeGeometry(fixedselectgeometry);
            var pntCoords = JSON.parse(geojsonStr).coordinates;
            turfFeatureArr.push(turf.point(pntCoords));
        }
    }
    if(turfFeatureArr.length >= 3){
        var turfCollection = turf.featureCollection(turfFeatureArr);
        return turfCollection;
    }
    else{
        return false;
    }
}

function getWGS84CirclePoly(center,radius){
    var turfFeature = '';
    var edgeCoordinate = [center[0] + radius, center[1]];
    var wgs84Sphere = new ol.Sphere(6378137);
    var groundRadius = wgs84Sphere.haversineDistance(center,edgeCoordinate);
    groundRadius = groundRadius/1000;
    var ciroptions = {steps:200, units:'kilometers'};
    turfFeature = turf.circle(center,groundRadius,ciroptions);
    return turfFeature;
}

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1],16),
        g: parseInt(result[2],16),
        b: parseInt(result[3],16)
    } : null;
}

function hideFeature(feature){
    var invisibleStyle = new ol.style.Style({
        image: new ol.style.Circle({
            radius: feature.get('radius'),
            fill: new ol.style.Fill({
                color: 'rgba(255, 255, 255, 0.01)'
            })
        })
    });
    feature.setStyle(invisibleStyle);
}

function hideWorking(){
    $('#loadingOverlay').popup('hide');
}

function imagePostFunction(image, src) {
    var img = image.getImage();
    if(typeof window.btoa === 'function'){
        var xhr = new XMLHttpRequest();
        var dataEntries = src.split("&");
        var url;
        var params = "";
        for (var i = 0 ; i< dataEntries.length ; i++){
            if (i===0){
                url = dataEntries[i];
            }
            else{
                params = params + "&"+dataEntries[i];
            }
        }
        xhr.open('POST', url, true);
        xhr.responseType = 'arraybuffer';
        xhr.onload = function(e) {
            if (this.status === 200) {
                var uInt8Array = new Uint8Array(this.response);
                var i = uInt8Array.length;
                var binaryString = new Array(i);
                while (i--) {
                    binaryString[i] = String.fromCharCode(uInt8Array[i]);
                }
                var data = binaryString.join('');
                var type = xhr.getResponseHeader('content-type');
                if (type.indexOf('image') === 0) {
                    img.src = 'data:' + type + ';base64,' + window.btoa(data);
                }
            }
        };
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send(params);
    }
    else {
        img.src = src;
    }
}

function lazyLoadPoints(index,callback){
    var startindex = 0;
    loadingComplete = true;
    if(index > 1) startindex = (index - 1)*lazyLoadCnt;
    var http = new XMLHttpRequest();
    var url = "rpc/SOLRConnector.php";
    var params = solrqString+'&rows='+lazyLoadCnt+'&start='+startindex+'&fl='+SOLRFields+'&wt=geojson';
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            loadingComplete = false;
            setTimeout(checkLoading,loadingTimer);
            callback(http.responseText);
        }
    };
    http.send(params);
}

function loadPoints(){
	//console.log("loadPoints");
    cqlString = '';
    solrqString = '';
    taxaCnt = 0;
    collSymbology = [];
    taxaSymbology = [];
    selections = [];
    dsOldestDate = '';
    dsNewestDate = '';
    removeDateSlider();
    cqlString = newcqlString;
    solrqString = newsolrqString;
    if(newsolrqString){
    
        showWorking();
        pointvectorsource = new ol.source.Vector({wrapX: false});
        layersArr['pointv'].setSource(pointvectorsource);
        getSOLRRecCnt(false,function(res) {
        	//console.log(solrRecCnt);
            if(solrRecCnt){
                loadPointsEvent = true;
                setLoadingTimer();
                if(loadVectorPoints){
                    loadPointWFSLayer(0);
                }
                else{
                    loadPointWMSLayer();
                }
                //cleanSelectionsLayer();
                setRecordsTab();
                changeRecordPage(1);
                $('#recordstab').tabs({active: 1});
                $("#accordion").accordion("option","active",1);
                selectInteraction.getFeatures().clear();
                if(!pointActive){
                    var infoArr = [];
                    infoArr['Name'] = 'pointv';
                    infoArr['layerType'] = 'vector';
                    infoArr['Title'] = 'Points';
                    infoArr['Abstract'] = '';
                    infoArr['DefaultCRS'] = '';
                    buildLayerTableRow(infoArr,true);
                    pointActive = true;
                }
            }
            else{
                setRecordsTab();
                if(pointActive){
                    removeLayerToSelList('pointv');
                    pointActive = false;
                }
                loadPointsEvent = false;
                hideWorking();                
                //ORIG alert('There were no records matching your query.');
                alert('There were no records matching your query.');
            }
        });
    }
    else{
        alert('Please add criteria for points.');
    }
}

function openIndPopup(occid){
    openPopup('../collections/individual/index.php?occid=' + occid);
}

function openOccidInfoBox(occid,label){
    var occpos = findOccClusterPosition(occid);
    finderpopupcontent.innerHTML = label;
    finderpopupoverlay.setPosition(occpos);
}

function openPopup(urlStr){
    wWidth = document.body.offsetWidth*0.90;
    newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
    if (newWindow.opener == null) newWindow.opener = self;
    return false;
}

function parseDate(dateStr){
    var y = 0;
    var m = 0;
    var d = 0;
    try{
        var validformat1 = /^\d{4}-\d{1,2}-\d{1,2}$/; //Format: yyyy-mm-dd
        var validformat2 = /^\d{1,2}\/\d{1,2}\/\d{2,4}$/; //Format: mm/dd/yyyy
        var validformat3 = /^\d{1,2} \D+ \d{2,4}$/; //Format: dd mmm yyyy
        if(validformat1.test(dateStr)){
            var dateTokens = dateStr.split("-");
            y = dateTokens[0];
            m = dateTokens[1];
            d = dateTokens[2];
        }
        else if(validformat2.test(dateStr)){
            var dateTokens = dateStr.split("/");
            m = dateTokens[0];
            d = dateTokens[1];
            y = dateTokens[2];
            if(y.length == 2){
                if(y < 20){
                    y = "20" + y;
                }
                else{
                    y = "19" + y;
                }
            }
        }
        else if(validformat3.test(dateStr)){
            var dateTokens = dateStr.split(" ");
            d = dateTokens[0];
            mText = dateTokens[1];
            y = dateTokens[2];
            if(y.length == 2){
                if(y < 15){
                    y = "20" + y;
                }
                else{
                    y = "19" + y;
                }
            }
            mText = mText.substring(0,3);
            mText = mText.toLowerCase();
            var mNames = new Array("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");
            m = mNames.indexOf(mText)+1;
        }
        else if(dateObj instanceof Date && dateObj != "Invalid Date"){
            var dateObj = new Date(dateStr);
            y = dateObj.getFullYear();
            m = dateObj.getMonth() + 1;
            d = dateObj.getDate();
        }
    }
    catch(ex){
    }
    var retArr = new Array();
    retArr["y"] = y.toString();
    retArr["m"] = m.toString();
    retArr["d"] = d.toString();
    return retArr;
}

function prepareTaxaData(callback){
    var http = new XMLHttpRequest();
    var url = "rpc/gettaxalinks.php";
    var taxaArrStr = JSON.stringify(taxaArr);
    var params = 'taxajson='+taxaArrStr+'&type='+taxontype+'&thes='+thes;
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            taxaArr = JSON.parse(http.responseText);
            callback(1);
        }
    };
    http.send(params);
}

function prepareTaxaParams(callback){
    var taxaval = document.getElementById("taxa").value.trim();
    if(taxaval){
        var taxavals = taxaval.split(',');
        var taxaCqlString = '';
        var taxaSolrqString = '';
        taxaArr = [];
        taxontype = document.getElementById("taxontype").value;
        thes = false;
        if(document.getElementById("thes").checked) thes = true;
        for (i in taxavals){
            var name = taxavals[i].trim();
            taxaArr.push(name);
        }
        prepareTaxaData(function(res){
            if(taxaArr){
                var taxaCqlString = '';
                var taxaSolrqString = '';
                for (i in taxaArr){
                    if(taxontype == 4){
                        taxaCqlString = " OR parenttid = "+i;
                        taxaSolrqString = " OR (parenttid:"+i+")";
                    }
                    else{
                        if(taxontype == 5){
                            var famArr = [];
                            var scinameArr = [];
                            if(taxaArr[i]["families"]){
                                famArr = taxaArr[i]["families"];
                            }
                            if(famArr.length > 0){
                                taxaSolrqString += " OR (family:("+famArr.join()+"))";
                                for (f in famArr){
                                    taxaCqlString += " OR family = '"+famArr[f]+"'";
                                }
                            }
                            if(taxaArr[i]["scinames"]){
                                scinameArr = taxaArr[i]["scinames"];
                                if(scinameArr.length > 0){
                                    for (s in scinameArr){
                                        taxaSolrqString += " OR ((sciname:"+scinameArr[s].replace(/ /g,"\\ ")+") OR (sciname:"+scinameArr[s].replace(/ /g,"\\ ")+"\\ *))";
                                        taxaCqlString += " OR sciname LIKE '"+scinameArr[s]+"%'";
                                    }
                                }
                            }
                        }
                        else{
                            if((taxontype == 2 || taxontype == 1) && ((i.substr(i.length - 5) == "aceae") || (i.substr(i.length - 4) == "idae"))){
                                taxaSolrqString += " OR (family:"+i+")";
                                taxaCqlString += " OR family = '"+i+"'";
                            }
                            if((taxontype == 3 || taxontype == 1) && ((i.substr(i.length - 5) != "aceae") || (i.substr(i.length - 4) != "idae"))){
                                taxaSolrqString += " OR ((sciname:"+i.replace(/ /g,"\\ ")+") OR (sciname:"+i.replace(/ /g,"\\ ")+"\\ *))";
                                taxaCqlString += " OR sciname LIKE '"+i+"%'";
                            }
                        }
                        if(taxaArr[i]["synonyms"]){
                            var synArr = [];
                            synArr = taxaArr[i]["synonyms"];
                            var tidArr = [];
                            if(taxontype == 1 || taxontype == 2 || taxontype == 5){
                                for (syn in synArr){
                                    if(synArr[syn].indexOf('aceae') !== -1 || synArr[syn].indexOf('idae') !== -1){
                                        taxaSolrqString += " OR (family:"+synArr[syn]+")";
                                        taxaCqlString += " OR family = '"+synArr[syn]+"'";
                                    }
                                }
                            }
                            for (syn in synArr){
                                tidArr.push(syn);
                            }
                            taxaSolrqString += " OR (tidinterpreted:("+tidArr.join(' ')+"))";
                            taxaCqlString += " OR tidinterpreted IN("+tidArr.join(' ')+")";
                        }
                    }
                }
                taxaCqlString = taxaCqlString.substr(4,taxaCqlString.length);
                taxaSolrqString = taxaSolrqString.substr(4,taxaSolrqString.length);
                cqlfrag = '(('+taxaCqlString+'))';
                cqlArr.push(cqlfrag);
                solrqfrag = '('+taxaSolrqString+')';
                solrqArr.push(solrqfrag);
            }
            callback(1);
        });
    }
    else{
        callback(1);
    }
}

function prepCsvControlForm(){
    if (document.getElementById('csvschemasymb').checked) var schema = document.getElementById('csvschemasymb').value;
    if (document.getElementById('csvschemadwc').checked) var schema = document.getElementById('csvschemadwc').value;
    if (document.getElementById('csvformatcsv').checked) var format = document.getElementById('csvformatcsv').value;
    if (document.getElementById('csvformattab').checked) var format = document.getElementById('csvformattab').value;
    if (document.getElementById('csvcsetiso').checked) var cset = document.getElementById('csvcsetiso').value;
    if (document.getElementById('csvcsetutf').checked) var cset = document.getElementById('csvcsetutf').value;
    document.getElementById("schemacsv").value = schema;
    document.getElementById("dh-filename").value = document.getElementById("dh-filename").value+'.'+schema;
    if(document.getElementById("csvidentifications").checked==true){
        document.getElementById("identificationscsv").value = 1;
    }
    if(document.getElementById("csvimages").checked==true){
        document.getElementById("imagescsv").value = 1;
    }
    document.getElementById("formatcsv").value = format;
    document.getElementById("csetcsv").value = cset;
    if(document.getElementById("csvzip").checked==true){
        document.getElementById("zipcsv").value = 1;
        document.getElementById("dh-type").value = 'zip';
        document.getElementById("dh-contentType").value = 'application/zip';
    }
    else{
        document.getElementById("zipcsv").value = false;
        document.getElementById("dh-type").value = 'csv';
        document.getElementById("dh-contentType").value = 'text/csv; charset='+cset;
    }
    $("#csvoptions").popup("hide");
    document.getElementById("datadownloadform").submit();
}

function primeSymbologyData(features){
    var currentDate = new Date();
    for(f in features) {
        if(features[f].get('coll_year')){
            var fyear = Number(features[f].get('coll_year'));
            if(fyear.toString().length == 4 && fyear > 1500){
                var fmonth = (features[f].get('coll_month')?Number(features[f].get('coll_month')):1);
                var fday = (features[f].get('coll_day')?Number(features[f].get('coll_day')):1);
                var fDate = new Date();
                fDate.setFullYear(fyear, fmonth - 1, fday);
                if(currentDate > fDate){
                    if(!dsOldestDate || (fDate < dsOldestDate)){
                        dsOldestDate = fDate;
                    }
                    if(!dsNewestDate || (fDate > dsNewestDate)){
                        dsNewestDate = fDate;
                    }
                }
            }
        }
        var color = 'e69e67';
        var collName = features[f].get('CollectionName');
        var collid = features[f].get('collid');
        var tidinterpreted = features[f].get('tidinterpreted');
        var sciname = features[f].get('sciname');
        var family = (features[f].get('accFamily')?features[f].get('accFamily'):features[f].get('family'));
        if(family){
            family = family.toUpperCase();
        }
        else{
            family = 'undefined';
        }
        //var namestring = (sciname?sciname:'')+(tidinterpreted?tidinterpreted:'');
        var namestring = (sciname?sciname:'');
        namestring = namestring.replace(" ","");
        namestring = namestring.toLowerCase();
        namestring = namestring.replace(/[^A-Za-z0-9 ]/g,'');
        if(!collSymbology[collName]){
            collSymbology[collName] = [];
            collSymbology[collName]['collid'] = collid;
            collSymbology[collName]['color'] = color;
        }
        if(!taxaSymbology[namestring]){
            taxaCnt++;
            taxaSymbology[namestring] = [];
            taxaSymbology[namestring]['sciname'] = sciname;
            taxaSymbology[namestring]['tidinterpreted'] = tidinterpreted;
            taxaSymbology[namestring]['family'] = family;
            taxaSymbology[namestring]['color'] = color;
            taxaSymbology[namestring]['count'] = 1;
        }
        else{
            taxaSymbology[namestring]['count'] = taxaSymbology[namestring]['count'] + 1;
        }
        features[f].set('namestring',namestring,true);
    }
}

function processCheckSelection(c){
    if(c.checked == true){
        var activeTab = $('#recordstab').tabs("option","active");
        if(activeTab==1){
            if($('.occcheck:checked').length==$('.occcheck').length){
                document.getElementById("selectallcheck").checked = true;
            }
        }
        selections.push(Number(c.value));
        layersArr['pointv'].getSource().changed();
        updateSelections(Number(c.value),false);
    }
    else if(c.checked == false){
        var activeTab = $('#recordstab').tabs("option","active");
        if(activeTab==1){
            document.getElementById("selectallcheck").checked = false;
        }
        var index = selections.indexOf(Number(c.value));
        selections.splice(index, 1);
        layersArr['pointv'].getSource().changed();
        removeSelectionRecord(Number(c.value));
    }
    adjustSelectionsTab();
}

function processDownloadRequest(selection){
    document.getElementById("dh-fl").value = '';
    document.getElementById("dh-type").value = '';
    document.getElementById("dh-filename").value = '';
    document.getElementById("dh-contentType").value = '';
    document.getElementById("dh-selections").value = '';
    var dlType = (selection?document.getElementById("selectdownloadselect").value:document.getElementById("querydownloadselect").value);
    if(dlType){
        var filename = 'spatialdata_'+getDateTimeString();
        var contentType = '';
        if(dlType == 'kml') contentType = 'application/vnd.google-earth.kml+xml';
        else if(dlType == 'geojson') contentType = 'application/vnd.geo+json';
        else if(dlType == 'gpx') contentType = 'application/gpx+xml';
        document.getElementById("dh-type").value = dlType;
        document.getElementById("dh-filename").value = filename;
        document.getElementById("dh-contentType").value = contentType;
        if(selection) document.getElementById("dh-selections").value = selections.join();
        if(!selection && dlType == 'csv'){
            document.getElementById("dh-fl").value = 'occid';
        }
        else{
            document.getElementById("dh-fl").value = SOLRFields;
        }
        if(dlType == 'csv'){
            $("#csvoptions").popup("show");
        }
        else if(dlType == 'kml' || dlType == 'geojson' || dlType == 'gpx'){
            document.getElementById("datadownloadform").submit();
        }
        else if(dlType == 'png'){
            var imagefilename = 'map_'+getDateTimeString()+'.png';
            exportMapPNG(imagefilename,false);
        }
    }
    else{
        alert('Please select a download type.')
    }
}

function processPointSelection(sFeature){
    var feature = (sFeature.get('features')?sFeature.get('features')[0]:sFeature);
    var occid = Number(feature.get('occid'));
    if(selections.indexOf(occid) < 0){
        selections.push(occid);
        var infoArr = getPointInfoArr(sFeature);
        updateSelections(occid,infoArr);
    }
    else{
        var index = selections.indexOf(occid);
        selections.splice(index, 1);
        removeSelectionRecord(occid);
    }
    var style = (sFeature.get('features')?setClusterSymbol(sFeature):setSymbol(sFeature));
    sFeature.setStyle(style);
    adjustSelectionsTab();
}

function refreshLayerOrder(){
    var layerCount = map.getLayers().getArray().length;
    layersArr['dragdrop1'].setZIndex(layerCount-6);
    layersArr['dragdrop2'].setZIndex(layerCount-5);
    layersArr['dragdrop3'].setZIndex(layerCount-4);
    layersArr['select'].setZIndex(layerCount-3);
    layersArr['pointv'].setZIndex(layerCount-2);
    layersArr['heat'].setZIndex(layerCount-1);
    layersArr['spider'].setZIndex(layerCount);
}

function removeDateSlider(){
    if(document.getElementById("sliderdiv")){
        document.body.removeChild(sliderdiv);
        sliderdiv = '';
        document.getElementById("datesliderswitch").checked = false;
        dateSliderActive = false;
    }
    if(returnClusters && !showHeatMap){
        returnClusters = false;
        document.getElementById("clusterswitch").checked = true;
        changeClusterSetting();
    }
    tsOldestDate = '';
    tsNewestDate = '';
    document.getElementById("dateslidercontrol").style.display = 'none';
    document.getElementById("maptoolcontainer").style.top = '10px';
    document.getElementById("maptoolcontainer").style.left = '50%';
    document.getElementById("maptoolcontainer").style.bottom = 'initial';
    document.getElementById("maptoolcontainer").style.right = 'initial';
    document.getElementById("datesliderearlydate").value = '';
    document.getElementById("datesliderlatedate").value = '';
    dsAnimDuration = document.getElementById("datesliderinterduration").value = '';
    dsAnimTime = document.getElementById("datesliderintertime").value = '';
    dsAnimImageSave = document.getElementById("dateslideranimimagesave").checked = false;
    dsAnimReverse = document.getElementById("dateslideranimreverse").checked = false;
    dsAnimDual = document.getElementById("dateslideranimdual").checked = false;
    layersArr['pointv'].getSource().changed();
}

function removeLayerToSelList(layer){
    var selectobject = document.getElementById("selectlayerselect");
    for (var i=0; i<selectobject.length; i++){
        if(selectobject.options[i].value == layer) selectobject.remove(i);
    }
    setActiveLayer();
}

function removeSelection(c){
    if(c.checked == false){
        var occid = c.value;
        var chbox = 'ch'+occid;
        removeSelectionRecord(occid);
        if(document.getElementById(chbox)){
            document.getElementById(chbox).checked = false;
        }
        var index = selections.indexOf(Number(c.value));
        selections.splice(index, 1);
        layersArr['pointv'].getSource().changed();
        if(spiderCluster){
            var spiderFeatures = layersArr['spider'].getSource().getFeatures();
            for(f in spiderFeatures){
                if(spiderFeatures[f].get('features')[0].get('occid') == Number(c.value)){
                    var style = (spiderFeatures[f].get('features')?setClusterSymbol(spiderFeatures[f]):setSymbol(spiderFeatures[f]));
                    spiderFeatures[f].setStyle(style);
                }
            }
        }
        adjustSelectionsTab();
    }
}

function removeSelectionRecord(sel){
    var selDivId = "sel"+sel;
    if(document.getElementById(selDivId)){
        var selDiv = document.getElementById(selDivId);
        selDiv.parentNode.removeChild(selDiv);
    }
}

function removeUserLayer(layerID){
    var layerDivId = "lay-"+layerID;
    if(document.getElementById(layerDivId)){
        var layerDiv = document.getElementById(layerDivId);
        layerDiv.parentNode.removeChild(layerDiv);
    }
    if(layerID == 'select'){
        selectInteraction.getFeatures().clear();
        layersArr[layerID].getSource().clear(true);
        shapeActive = false;
    }
    else if(layerID == 'pointv'){
        clearSelections();
        adjustSelectionsTab();
        removeDateSlider();
        pointvectorsource = new ol.source.Vector({wrapX: false});
        layersArr['pointv'].setSource(pointvectorsource);
        layersArr['heat'].setSource(pointvectorsource);
        layersArr['heat'].setVisible(false);
        clustersource = '';
        $('#criteriatab').tabs({active: 0});
        $("#accordion").accordion("option","active",0);
        pointActive = false;
    }
    else if(overlayLayers[layerID]){
        var layerTileSourceName = layerID+'Source';
        var layerRasterSourceName = layerID+'RasterSource';
        layersArr[layerTileSourceName] = '';
        layersArr[layerRasterSourceName] = '';
        layersArr[layerID].setVisible(false);
        var index = overlayLayers.indexOf(layerID);
        overlayLayers.splice(index, 1);
        if(vectorizeLayers[layerID]){
            var vecindex = vectorizeLayers.indexOf(layerID);
            vectorizeLayers.splice(vecindex, 1);
        }
    }
    else{
        layersArr[layerID].setSource(blankdragdropsource);
        if(layerID == 'dragdrop1') dragDrop1 = false;
        else if(layerID == 'dragdrop2') dragDrop2 = false;
        else if(layerID == 'dragdrop3') dragDrop3 = false;
    }
    document.getElementById("selectlayerselect").value = 'none';
    removeLayerToSelList(layerID);
    setActiveLayer();
    toggleLayerTable();
}

function resetMainSymbology(){
    for(i in collSymbology){
        collSymbology[i]['color'] = "E69E67";
        var keyName = 'keyColor'+i;
        if(document.getElementById(keyName)){
            document.getElementById(keyName).color.fromString("E69E67");
        }
    }
}

function resetSymbology(){
    document.getElementById("symbolizeReset1").disabled = true;
    document.getElementById("symbolizeReset2").disabled = true;
    changeMapSymbology('coll');
    resetMainSymbology();
    for(i in collSymbology){
        buildCollKeyPiece(i);
    }
    layersArr['pointv'].getSource().changed();
    if(spiderCluster){
        var spiderFeatures = layersArr['spider'].getSource().getFeatures();
        for(f in spiderFeatures){
            var style = (spiderFeatures[f].get('features')?setClusterSymbol(spiderFeatures[f]):setSymbol(spiderFeatures[f]));
            spiderFeatures[f].setStyle(style);
        }
    }
    document.getElementById("symbolizeReset1").disabled = false;
    document.getElementById("symbolizeReset2").disabled = false;
}

function saveKeyImage(){
    var keyElement = (mapSymbology == 'coll'?document.getElementById("collSymbologyKey"):document.getElementById("taxasymbologykeysbox"));
    var keyClone = keyElement.cloneNode(true);
    document.body.appendChild(keyClone);
    html2canvas(keyClone).then(function(canvas) {
        if (navigator.msSaveBlob) {
            navigator.msSaveBlob(canvas.msToBlob(),'mapkey.png');
        }
        else {
            canvas.toBlob(function(blob) {
                saveAs(blob,'mapkey.png');
            });
        }
        document.body.removeChild(keyClone);
        keyClone = '';
    });
}

function selectAll(cb){
    var boxesChecked = true;
    if(!cb.checked){
        boxesChecked = false;
    }
    var f = cb.form;
    for(var i=0;i<f.length;i++){
        if(f.elements[i].name == "db[]" || f.elements[i].name == "cat[]" || f.elements[i].name == "occid[]"){
            f.elements[i].checked = boxesChecked;
        }
        if(f.elements[i].name == "occid[]"){
            f.elements[i].onchange();
        }
    }
}

function selectAllCat(cb,target){
    var boxesChecked = true;
    if(!cb.checked){
        boxesChecked = false;
    }
    var inputObjs = document.getElementsByTagName("input");
    for (i = 0; i < inputObjs.length; i++) {
        var inputObj = inputObjs[i];
        if(inputObj.getAttribute("class") == target || inputObj.getAttribute("className") == target){
            inputObj.checked = boxesChecked;
        }
    }
}

function setActiveLayer(){
    var selectDropDown = document.getElementById("selectlayerselect");
    activeLayer = selectDropDown.options[selectDropDown.selectedIndex].value;
}

function setBaseLayerSource(urlTemplate){
    return new ol.source.TileImage({
        tileUrlFunction: function(tileCoord, pixelRatio, projection) {
            var z = tileCoord[0];
            var x = tileCoord[1];
            var y = -tileCoord[2] - 1;
            var n = Math.pow(2, z + 1); // 2 tiles at z=0
            x = x % n;
            if (x * n < 0) {
                x = x + n;
            }
            return urlTemplate.replace('{z}', z.toString())
                .replace('{y}', y.toString())
                .replace('{x}', x.toString());
        },
        projection: 'EPSG:4326',
        tileGrid: new ol.tilegrid.TileGrid({
            origin: ol.extent.getTopLeft(projectionExtent),
            resolutions: resolutions,
            tileSize: 512
        }),
        crossOrigin: 'anonymous'
    });
}

function setClusterSymbol(feature) {
    var style = '';
    var stroke = '';
    var selected = false;
    if(feature.get('features')){
        var size = feature.get('features').length;
        if(size > 1){
            var features = feature.get('features');
            if(selections.length > 0){
                var clusterindex = feature.get('identifiers');
                for(i in selections){
                    if(clusterindex.indexOf(selections[i]) !== -1) selected = true;
                }
            }
            var clusterindex = feature.get('identifiers');
            var cKey = feature.get('clusterkey');
            if(mapSymbology == 'coll'){
                var hexcolor = '#'+collSymbology[cKey]['color'];
            }
            else if(mapSymbology == 'taxa'){
                var hexcolor = '#'+taxaSymbology[cKey]['color'];
            }
            var colorArr = hexToRgb(hexcolor);
            if(size < 10) var radius = 10;
            else if(size < 100) var radius = 15;
            else if(size < 1000) var radius = 20;
            else if(size < 10000) var radius = 25;
            else if(size < 100000) var radius = 30;
            else var radius = 35;

            if(selected) stroke = new ol.style.Stroke({color: '#10D8E6', width: 2});

            style = new ol.style.Style({
                image: new ol.style.Circle({
                    opacity: 1,
                    scale: 1,
                    radius: radius,
                    stroke: stroke,
                    fill: new ol.style.Fill({
                        color: [colorArr['r'],colorArr['g'],colorArr['b'],0.8]
                    }),
                    atlasManager: atlasManager
                }),
                text: new ol.style.Text({
                    scale: 1,
                    text: size.toString(),
                    fill: new ol.style.Fill({
                        color: '#fff'
                    }),
                    stroke: new ol.style.Stroke({
                        color: 'rgba(0, 0, 0, 0.6)',
                        width: 3
                    })
                })
            });
        }
        else{
            var originalFeature = feature.get('features')[0];
            style = setSymbol(originalFeature);
        }
    }
    return style;
}

function setDownloadFeatures(features){
    var fixedFeatures = [];
    for(i in features){
        var clone = features[i].clone();
        var geoType = clone.getGeometry().getType();
        if(geoType == 'Circle'){
            var geoJSONFormat = new ol.format.GeoJSON();
            var geometry = clone.getGeometry();
            var fixedgeometry = geometry.transform(mapProjection,wgs84Projection);
            var center = fixedgeometry.getCenter();
            var radius = fixedgeometry.getRadius();
            var turfCircle = getWGS84CirclePoly(center,radius);
            var circpoly = geoJSONFormat.readFeature(turfCircle);
            circpoly.getGeometry().transform(wgs84Projection,mapProjection);
            fixedFeatures.push(circpoly);
        }
        else{
            fixedFeatures.push(clone);
        }
    }
    return fixedFeatures;
}

function setDragDropTarget(){
    dragDropTarget = '';
    if(!dragDrop1){
        dragDrop1 = true;
        dragDropTarget = 'dragdrop1';
        return true;
    }
    else if(!dragDrop2){
        dragDrop2 = true;
        dragDropTarget = 'dragdrop2';
        return true;
    }
    else if(!dragDrop3){
        dragDrop3 = true;
        dragDropTarget = 'dragdrop3';
        return true;
    }
    else{
        alert('You may only have 3 uploaded layers at a time. Please remove one of the currently uploaded layers to upload more.');
        return false;
    }
}

function setDSAnimation(){
    dsAnimDuration = document.getElementById("datesliderinterduration").value;
    dsAnimTime = document.getElementById("datesliderintertime").value;
    if(dsAnimDuration && dsAnimTime){
        dsAnimStop = false;
        dsAnimDuration = dsAnimDuration*365.25;
        dsAnimTime = dsAnimTime*1000;
        dsAnimImageSave = document.getElementById("dateslideranimimagesave").checked;
        dsAnimReverse = document.getElementById("dateslideranimreverse").checked;
        dsAnimDual = document.getElementById("dateslideranimdual").checked;
        var lowDate = document.getElementById("datesliderearlydate").value;
        var highDate = document.getElementById("datesliderlatedate").value;
        dsAnimLow = new Date(lowDate);
        dsAnimLow = new Date(dsAnimLow.setTime(dsAnimLow.getTime()+86400000));
        dsAnimHigh = new Date(highDate);
        dsAnimHigh = new Date(dsAnimHigh.setTime(dsAnimHigh.getTime()+86400000));
        var lowDateVal = dsAnimLow;
        var highDateVal = dsAnimHigh;
        if(dsAnimReverse){
            if(dsAnimDual) lowDateVal = highDateVal;
        }
        else{
            highDateVal = lowDateVal;
        }
        tsOldestDate = lowDateVal;
        tsNewestDate = highDateVal;
        var lowDateValStr = getISOStrFromDateObj(lowDateVal);
        var highDateValStr = getISOStrFromDateObj(highDateVal);
        $("#sliderdiv").slider('values',0,tsOldestDate.getTime());
        $("#sliderdiv").slider('values',1,tsNewestDate.getTime());
        $("#custom-label-min").text(lowDateValStr);
        $("#custom-label-max").text(highDateValStr);
        document.getElementById("datesliderearlydate").value = lowDateValStr;
        document.getElementById("datesliderlatedate").value = highDateValStr;
        layersArr['pointv'].getSource().changed();
        if(dsAnimImageSave){
            zipFile = new JSZip();
            zipFolder = zipFile.folder("images");
        }
        animateDS();
    }
    else{
        dsAnimDuration = '';
        dsAnimTime = '';
        alert("Please enter an interval duration and interval time.");
    }
}

function setDSValues(){
    var lowDate = document.getElementById("datesliderearlydate").value;
    tsOldestDate = new Date(lowDate);
    tsOldestDate = new Date(tsOldestDate.setTime(tsOldestDate.getTime()+86400000));
    var hLowDateStr = getISOStrFromDateObj(tsOldestDate);
    var highDate = document.getElementById("datesliderlatedate").value;
    tsNewestDate = new Date(highDate);
    tsNewestDate = new Date(tsNewestDate.setTime(tsNewestDate.getTime()+86400000));
    var hHighDateStr = getISOStrFromDateObj(tsNewestDate);
    $("#sliderdiv").slider('values',0,tsOldestDate.getTime());
    $("#sliderdiv").slider('values',1,tsNewestDate.getTime());
    $("#custom-label-min").text(hLowDateStr);
    $("#custom-label-max").text(hHighDateStr);
    layersArr['pointv'].getSource().changed();
}

function setLayersTable(){
    var http = new XMLHttpRequest();
    var url = "rpc/getlayersarr.php";
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            var layerArr;
            var jsonReturn = false;
            try{
                layerArr = JSON.parse(http.responseText);
            }catch(e){
                return false;
            }
            for(i in layerArr){
                if(String(layerArr[i])){
                    jsonReturn = true;
                    break;
                }
            }
            if(jsonReturn){
                for(i in layerArr){
                    buildLayerTableRow(layerArr[i],false);
                }
            }
        }
        toggleLayerTable();
    };
    http.send();
}

function setLoadingTimer(){
    loadingTimer = 20000;
    if(solrRecCnt < 200000) loadingTimer = 13000;
    if(solrRecCnt < 150000) loadingTimer = 10000;
    if(solrRecCnt < 100000) loadingTimer = 7000;
    if(solrRecCnt < 50000) loadingTimer = 5000;
    if(solrRecCnt < 10000) loadingTimer = 3000;
    if(solrRecCnt < 5000) loadingTimer = 1000;
}

function setReclassifyTable(){
    if(document.getElementById("reclassifytable")){
        var currentTable = document.getElementById("reclassifytable");
        currentTable.parentNode.removeChild(currentTable);
    }
    var newTable = document.createElement('table');
    newTable.setAttribute("id","reclassifytable");
    newTable.setAttribute("class","styledtable");
    newTable.setAttribute("style","font-family:Arial;font-size:12px;margin-top:15px;margin-left:auto;margin-right:auto;width:330px;");
    var newTHead = document.createElement('thead');
    var newTHeadRow = document.createElement('tr');
    var newTHeadHead1 = document.createElement('th');
    newTHeadHead1.setAttribute("style","text-align:center;");
    newTHeadHead1.innerHTML = "Raster Min Value";
    newTHeadRow.appendChild(newTHeadHead1);
    var newTHeadHead1 = document.createElement('th');
    newTHeadHead1.setAttribute("style","text-align:center;");
    newTHeadHead1.innerHTML = "Raster Max Value";
    newTHeadRow.appendChild(newTHeadHead1);
    var newTHeadHead2 = document.createElement('th');
    newTHeadHead2.setAttribute("style","text-align:center;");
    newTHeadHead2.innerHTML = "Color";
    newTHeadRow.appendChild(newTHeadHead2);
    newTHead.appendChild(newTHeadRow);
    newTable.appendChild(newTHead);
    var newTBody = document.createElement('tbody');
    newTBody.setAttribute("id","reclassifyTBody");
    var newRow = document.createElement('tr');
    var newRastValCell = document.createElement('td');
    newRastValCell.setAttribute("style","width:150px;");
    var newRastValInput = document.createElement('input');
    newRastValInput.setAttribute("data-role","none");
    newRastValInput.setAttribute("type","text");
    newRastValInput.setAttribute("id","reclassifyRasterMin");
    newRastValInput.setAttribute("style","width:150px;margin-left:10px;");
    newRastValInput.setAttribute("value","");
    newRastValCell.appendChild(newRastValInput);
    newRow.appendChild(newRastValCell);
    var newRastValCell = document.createElement('td');
    newRastValCell.setAttribute("style","width:150px;");
    var newRastValInput = document.createElement('input');
    newRastValInput.setAttribute("data-role","none");
    newRastValInput.setAttribute("type","text");
    newRastValInput.setAttribute("id","reclassifyRasterMax");
    newRastValInput.setAttribute("style","width:150px;margin-left:10px;");
    newRastValInput.setAttribute("value","");
    newRastValCell.appendChild(newRastValInput);
    newRow.appendChild(newRastValCell);
    var newColorValCell = document.createElement('td');
    newColorValCell.setAttribute("style","width:30px;");
    var newColorValInput = document.createElement('input');
    newColorValInput.setAttribute("data-role","none");
    newColorValInput.setAttribute("id","reclassifyColorVal");
    newColorValInput.setAttribute("class","color");
    newColorValInput.setAttribute("style","cursor:pointer;border:1px black solid;height:20px;width:20px;margin-left:5px;margin-bottom:-2px;font-size:0px;");
    newColorValInput.setAttribute("value","FFFFFF");
    newColorValCell.appendChild(newColorValInput);
    newRow.appendChild(newColorValCell);
    newTBody.appendChild(newRow);
    newTable.appendChild(newTBody);
    document.getElementById("reclassifyTableDiv").appendChild(newTable);
    jscolor.init();
}

function setRecordsTab(){
    if(solrRecCnt > 0){
        document.getElementById("recordsHeader").style.display = "block";
        document.getElementById("recordstab").style.display = "block";
        document.getElementById("pointToolsNoneDiv").style.display = "none";
        document.getElementById("pointToolsDiv").style.display = "block";
    }
    else{
        document.getElementById("recordsHeader").style.display = "none";
        document.getElementById("recordstab").style.display = "none";
        document.getElementById("pointToolsNoneDiv").style.display = "block";
        document.getElementById("pointToolsDiv").style.display = "none";
    }
}

function setSpatialParamBox(){
    var selectionCnt = selectInteraction.getFeatures().getArray().length;
    if(selectionCnt > 0){
        document.getElementById("noshapecriteria").style.display = "none";
        document.getElementById("shapecriteria").style.display = "block";
    }
    else{
        document.getElementById("noshapecriteria").style.display = "block";
        document.getElementById("shapecriteria").style.display = "none";
    }
}

function setSymbol(feature){
    var showPoint = true;
    if(dateSliderActive){
        showPoint = validateFeatureDate(feature);
    }
    var style = '';
    var stroke = '';
    var selected = false;
    var cKey = feature.get(clusterKey);
    var recType = feature.get('CollType');
    if(!recType) recType = 'observation';
    if(selections.length > 0){
        var occid = feature.get('occid');
        if(selections.indexOf(occid) !== -1) selected = true;
    }
    if(mapSymbology == 'coll'){
        var color = '#'+collSymbology[cKey]['color'];
    }
    else if(mapSymbology == 'taxa'){
        var color = '#'+taxaSymbology[cKey]['color'];
    }

    if(showPoint){
        if(selected) stroke = new ol.style.Stroke({color: '#10D8E6', width: 2});
        else stroke = new ol.style.Stroke({color: 'black', width: 1});
        var fill = new ol.style.Fill({color: color});
    }
    else{
        stroke = new ol.style.Stroke({color: 'rgba(255, 255, 255, 0.01)', width: 0});
        var fill = new ol.style.Fill({color: 'rgba(255, 255, 255, 0.01)'});
    }

    if(recType.toLowerCase().indexOf('observation') !== -1){
        style = new ol.style.Style({
            image: new ol.style.RegularShape({
                opacity: 1,
                scale: 1,
                fill: fill,
                stroke: stroke,
                points: 3,
                radius: 7,
                atlasManager: atlasManager
            })
        });
    }
    else{
        style = new ol.style.Style({
            image: new ol.style.Circle({
                opacity: 1,
                scale: 1,
                radius: 7,
                fill: fill,
                stroke: stroke,
                atlasManager: atlasManager
            })
        });
    }

    return style;
}

function showFeature(feature){
    if(feature.get('features')){
        var featureStyle = setClusterSymbol(feature);
    }
    else{
        var featureStyle = setSymbol(feature);
    }
    feature.setStyle(featureStyle);
}

function showWorking(){
    $('#loadingOverlay').popup('show');
}

function spiderifyPoints(features){
    spiderCluster = 1;
    spiderFeature = '';
    var spiderFeatures = [];
    for(f in features){
        var feature = features[f];
        hideFeature(feature);
        hiddenClusters.push(feature);
        if(feature.get('features')){
            var addFeatures = feature.get('features');
            for(f in addFeatures){
                spiderFeatures.push(addFeatures[f]);
            }
        }
        else{
            spiderFeatures.push(feature);
        }
    }

    var source = layersArr['spider'].getSource();
    source.clear();

    var center = features[0].getGeometry().getCoordinates();
    var pix = map.getView().getResolution();
    var r = pix * 12 * (0.5 + spiderFeatures.length / 4);
    if (spiderFeatures.length <= 10){
        var max = Math.min(spiderFeatures.length, 10);
        for(i in spiderFeatures){
            var a = 2*Math.PI*i/max;
            if (max==2 || max == 4) a += Math.PI/4;
            var p = [center[0]+r*Math.sin(a), center[1]+r*Math.cos(a)];
            var cf = new ol.Feature({
                'features':[spiderFeatures[i]],
                geometry: new ol.geom.Point(p)
            });
            var style = setClusterSymbol(cf);
            cf.setStyle(style);
            source.addFeature(cf);
        };
    }
    else{
        var a = 0;
        var r;
        var d = 30;
        var features = new Array();
        var links = new Array();
        var max = Math.min (60, spiderFeatures.length);
        for(i in spiderFeatures){
            r = d/2 + d*a/(2*Math.PI);
            a = a + (d+0.1)/r;
            var dx = pix*r*Math.sin(a)
            var dy = pix*r*Math.cos(a)
            var p = [center[0]+dx, center[1]+dy];
            var cf = new ol.Feature({
                'features':[spiderFeatures[i]],
                geometry: new ol.geom.Point(p)
            });
            var style = setClusterSymbol(cf);
            cf.setStyle(style);
            source.addFeature(cf);
        }
    }
}

function stopDSAnimation(){
    dsAnimStop = true;
    /*tsOldestDate = dsAnimLow;
    tsNewestDate = dsAnimHigh;
    var lowDateValStr = getISOStrFromDateObj(dsAnimLow);
    var highDateValStr = getISOStrFromDateObj(dsAnimHigh);
    $("#sliderdiv").slider('values',0,tsOldestDate.getTime());
    $("#sliderdiv").slider('values',1,tsNewestDate.getTime());
    $("#custom-label-min").text(lowDateValStr);
    $("#custom-label-max").text(highDateValStr);
    document.getElementById("datesliderearlydate").value = lowDateValStr;
    document.getElementById("datesliderlatedate").value = highDateValStr;
    layersArr['pointv'].getSource().changed();*/
    dsAnimDuration = '';
    dsAnimTime = '';
    dsAnimImageSave = false;
    dsAnimReverse = false;
    dsAnimDual = false;
    dsAnimLow = '';
    dsAnimHigh = '';
    dsAnimation = '';
}

function toggle(target){
    var ele = document.getElementById(target);
    if(ele){
        if(ele.style.display=="none"){
            ele.style.display="block";
        }
        else {
            ele.style.display="none";
        }
    }
    else{
        var divObjs = document.getElementsByTagName("div");
        for (i = 0; i < divObjs.length; i++) {
            var divObj = divObjs[i];
            if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
                if(divObj.style.display=="none"){
                    divObj.style.display="block";
                }
                else {
                    divObj.style.display="none";
                }
            }
        }
    }
}

function toggleCat(catid){
    toggle("minus-"+catid);
    toggle("plus-"+catid);
    toggle("cat-"+catid);
}

function toggleDateSlider(){
    dateSliderActive = document.getElementById("datesliderswitch").checked;
    if(dateSliderActive){
        if(dsOldestDate && dsNewestDate){
            if(dsOldestDate != dsNewestDate){
                if(!clusterPoints){
                    //var dual = document.getElementById("dsdualtype").checked;
                    createDateSlider(true);
                }
                else{
                    returnClusters = true;
                    document.getElementById("clusterswitch").checked = false;
                    changeClusterSetting();
                    createDateSlider(true);
                }
            }
            else{
                alert('The current records on the map do not have a range of dates for the Date Slider to populate.');
            }
        }
        else{
            document.getElementById("datesliderswitch").checked = false;
            dateSliderActive = false;
            alert('Points must be loaded onto the map to use the Date Slider.');
        }
    }
    else{
        removeDateSlider();
    }
}

function toggleHeatMap(){
    showHeatMap = document.getElementById("heatmapswitch").checked;
    if(showHeatMap){
        layersArr['pointv'].setVisible(false);
        layersArr['heat'].setVisible(true);
    }
    else{
        if(returnClusters && !dateSliderActive){
            returnClusters = false;
            document.getElementById("clusterswitch").checked = true;
            changeClusterSetting();
        }
        layersArr['heat'].setVisible(false);
        layersArr['pointv'].setVisible(true);
    }
}

function toggleLayerTable(layerID){
    //hiding these per Linda
    //var tableRows = document.getElementById("layercontroltable").rows.length;
    //if(tableRows > 0){
    //    document.getElementById("nolayermessage").style.display = "none";
    //    document.getElementById("layercontroltable").style.display = "block";
    //}
    //else{
        $('#addLayers').popup('hide');
        document.getElementById("nolayermessage").style.display = "block";
        document.getElementById("layercontroltable").style.display = "none";
    //}
}

function toggleUploadLayer(c,title){
    var layer = c.value;
    if(layer == 'pointv' && showHeatMap) layer = 'heat';
    if(c.checked == true){
        layersArr[layer].setVisible(true);
        addLayerToSelList(c.value,title);
    }
    else{
        layersArr[layer].setVisible(false);
        removeLayerToSelList(c.value);
    }
}

function uncheckAll(f){
    document.getElementById('dballcb').checked = false;
}

function unselectCat(catTarget){
    var catObj = document.getElementById(catTarget);
    catObj.checked = false;
    uncheckAll();
}

function updateSelections(seloccid,infoArr){
    var selectionList = '';
    var trfragment = '';
    var selcat = '';
    var sellabel = '';
    var sele = '';
    var sels = '';
    selectionList += document.getElementById("selectiontbody").innerHTML;
    var divid = "sel"+seloccid;
    var trid = "tr"+seloccid;
    if(infoArr){
        selcat = infoArr['catalognumber'];
        var mouseOverLabel = "openOccidInfoBox("+seloccid+",'"+infoArr['collector']+"');";
        var labelHTML = '<a href="#" onmouseover="'+mouseOverLabel+'" onmouseout="closeOccidInfoBox();" onclick="openIndPopup('+seloccid+'); return false;">';
        labelHTML += infoArr['collector'];
        labelHTML += '</a>';
        sellabel = labelHTML;
        sele = infoArr['eventdate'];
        sels = infoArr['sciname'];
    }
    else if(document.getElementById(trid)){
        var catid = "cat"+seloccid;
        var labelid = "label"+seloccid;
        var eid = "e"+seloccid;
        var sid = "s"+seloccid;
        selcat = document.getElementById(catid).innerHTML;
        sellabel = document.getElementById(labelid).innerHTML;
        sele = document.getElementById(eid).innerHTML;
        sels = document.getElementById(sid).innerHTML;
    }
    if(!document.getElementById(divid)){
        trfragment = '';
        trfragment += '<tr id="sel'+seloccid+'" >';
        trfragment += '<td>';
        trfragment += '<input type="checkbox" id="selch'+seloccid+'" name="occid[]" value="'+seloccid+'" onchange="removeSelection(this);" checked />';
        trfragment += '</td>';
        trfragment += '<td id="selcat'+seloccid+'"  style="width:200px;" >'+selcat+'</td>';
        trfragment += '<td id="sellabel'+seloccid+'"  style="width:200px;" >';
        trfragment += sellabel;
        trfragment += '</td>';
        trfragment += '<td id="sele'+seloccid+'"  style="width:200px;" >'+sele+'</td>';
        trfragment += '<td id="sels'+seloccid+'"  style="width:200px;" >'+sels+'</td>';
        trfragment += '</tr>';
        selectionList += trfragment;
    }
    document.getElementById("selectiontbody").innerHTML = selectionList;
}

function validateFeatureDate(feature){
    var valid = false;
    if(feature.get('coll_year')){
        var fyear = Number(feature.get('coll_year'));
        if(fyear.toString().length == 4 && fyear > 1500){
            var fmonth = (feature.get('coll_month')?Number(feature.get('coll_month')):1);
            var fday = (feature.get('coll_day')?Number(feature.get('coll_day')):1);
            var fDate = new Date();
            fDate.setFullYear(fyear, fmonth - 1, fday);
            if(fDate > tsOldestDate && fDate < tsNewestDate){
                valid = true;
            }
        }
    }
    return valid;
}

function verifyCollForm(){
    var f = document.getElementById("spatialcollsearchform");
    var formVerified = false;
    for(var h=0;h<f.length;h++){
        if(f.elements[h].name == "db[]" && f.elements[h].checked){
            formVerified = true;
            break;
        }
        if(f.elements[h].name == "cat[]" && f.elements[h].checked){
            formVerified = true;
            break;
        }
    }
    if(formVerified){
        for(var i=0;i<f.length;i++){
            if(f.elements[i].name == "cat[]" && f.elements[i].checked){
                //Uncheck all db input elements within cat div
                var childrenEle = document.getElementById('cat-'+f.elements[i].value).children;
                for(var j=0;j<childrenEle.length;j++){
                    if(childrenEle[j].tagName == "DIV"){
                        var divChildren = childrenEle[j].children;
                        for(var k=0;k<divChildren.length;k++){
                            var divChildren2 = divChildren[k].children;
                            for(var l=0;l<divChildren2.length;l++){
                                if(divChildren2[l].tagName == "INPUT"){
                                    divChildren2[l].checked = false;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return formVerified;
}

function writeWfsWktString(type,geocoords) {
    var wktStr = '';
    var coordStr = '';
    var coordRingStr = '';
    if(type == 'Polygon'){
        for(i in geocoords){
            for(c in geocoords[i]) {
                var lat = geocoords[i][c][1];
                var long = geocoords[i][c][0];
                coordStr += lat+' '+long+',';
            }
        }
        coordStr = coordStr.substring(0,coordStr.length-1);
        wktStr = 'POLYGON(('+coordStr+'))';
    }
    else if(type == 'MultiPolygon'){
        for(i in geocoords){
            for(r in geocoords[i]){
                coordRingStr = '';
                for(c in geocoords[i][r]) {
                    var lat = geocoords[i][r][c][1];
                    var long = geocoords[i][r][c][0];
                    coordRingStr += lat+' '+long+',';
                }
                coordRingStr = coordRingStr.substring(0,coordRingStr.length-1);
                coordStr += '('+coordRingStr+'),';
            }
        }
        coordStr = coordStr.substring(0,coordStr.length-1);
        wktStr = 'MULTIPOLYGON(('+coordStr+'))';
    }

    return wktStr;
}

function zipSelected(obj){
    if(obj.checked == false){
        document.getElementById("csvimages").checked = false;
        document.getElementById("csvidentifications").checked = false;
    }
}

function zoomToSelections(){
    var extent = ol.extent.createEmpty();
    for(i in selections){
        var point = '';
        if(clusterPoints){
            var cluster = findOccCluster(selections[i]);
            point = findOccPointInCluster(cluster,selections[i]);
        }
        else{
            point = findOccPoint(selections[i]);
        }
        if(point){
            ol.extent.extend(extent, point.getGeometry().getExtent());
        }
    }
    map.getView().fit(extent, map.getSize());
}