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

    $( "#taxa" )
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
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
                return false;
            }
        },{});
});

function addLayerToSelList(layer){
    var selectionList = document.getElementById("selectlayerselect").innerHTML;
    var optionId = "lsel-"+layer;
    var newOption = '<option id="lsel-'+optionId+'" value="'+layer+'">'+layer+'</option>';
    selectionList += newOption;
    document.getElementById("selectlayerselect").innerHTML = selectionList;
    document.getElementById("selectlayerselect").value = layer;
    setActiveLayer();
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

function buildLayerTableRow(lArr,upload){
    var layerList = '';
    var trfragment = '';
    var layerID = (upload?dragDropTarget:lArr['Name']);
    var divid = "lay-"+layerID;
    if(!document.getElementById(divid)){
        trfragment += '<td style="width:30px;">';
        trfragment += '<input type="checkbox" value="'+layerID+'" onchange="'+(upload?'toggleUploadLayer(this);':'editLayers(this);')+'" '+(upload?'checked ':'')+'/>';
        trfragment += '</td>';
        trfragment += '<td style="width:150px;">';
        trfragment += '<b>'+lArr['Title']+'</b>';
        trfragment += '</td>';
        trfragment += '<td style="width:250px;">';
        trfragment += lArr['Abstract'];
        trfragment += '</td>';
        trfragment += '<td style="width:50px;">';
        trfragment += '';
        trfragment += '</td>';
        trfragment += '<td style="width:50px;">';
        if(upload){
            var onclick = "removeUploadLayer('"+dragDropTarget+"');";
            trfragment += '<input type="image" style="margin-left:5px;" src="../images/del.png" onclick="'+onclick+'" title="Remove layer">';
        }
        trfragment += '</td>';
    }
    var layerTable = document.getElementById("layercontroltable");
    var newLayerRow = layerTable.insertRow();
    newLayerRow.id = 'lay-'+layerID;
    newLayerRow.innerHTML = trfragment;
    if(upload) addLayerToSelList(dragDropTarget);
}

function buildQueryStrings(){
    cqlArr = [];
    solrqArr = [];
    solrgeoqArr = [];
    newcqlString = '';
    newsolrqString = '';
    if(getCollectionParams()){
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
        newsolrqString += tempqStr;
    }
    else{
        newsolrqString += '*:*';
    }
    if(solrgeoqArr.length > 0){
        for(i in solrgeoqArr){
            tempfqStr += ' OR geo:'+solrgeoqArr[i];
        }
        tempfqStr = tempfqStr.substr(4,tempfqStr.length);
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
        keyHTML += "<div style='display:table-cell;vertical-align:middle;padding-left:8px;'><i><a target='_blank' href='../taxa/index.php?taxon="+sciname+"'>"+sciname+"</a></i></div>";
    }
    keyHTML += '</div></div>';
    if(!taxaKeyArr[family]){
        taxaKeyArr[family] = [];
    }
    taxaKeyArr[family][key] = keyHTML;
}

function changeBaseMap(){
    var blsource;
    var selection = document.getElementById('base-map').value;
    var baseLayer = map.getLayers().getArray()[0];
    if(selection == 'worldtopo'){
        blsource = new ol.source.XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}'
        });
    }
    if(selection == 'openstreet'){blsource = new ol.source.OSM();}
    if(selection == 'blackwhite'){blsource = new ol.source.Stamen({layer: 'toner'});}
    if(selection == 'worldimagery'){
        blsource = new ol.source.XYZ({
            url: 'http://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
        });
    }
    if(selection == 'ocean'){
        blsource = new ol.source.XYZ({
            url: 'http://services.arcgisonline.com/arcgis/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}'
        });
    }
    if(selection == 'ngstopo'){
        blsource = setBaseLayerSource('http://services.arcgisonline.com/arcgis/rest/services/NGS_Topo_US_2D/MapServer/tile/{z}/{y}/{x}');
    }
    if(selection == 'natgeoworld'){
        blsource = new ol.source.XYZ({
            url: 'http://services.arcgisonline.com/arcgis/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}'
        });
    }
    if(selection == 'esristreet'){
        blsource = setBaseLayerSource('http://services.arcgisonline.com/arcgis/rest/services/ESRI_StreetMap_World_2D/MapServer/tile/{z}/{y}/{x}');
    }
    baseLayer.setSource(blsource);
}

function changeClusterSetting(){
    clusterPoints = document.getElementById("clusterswitch").checked;
}

function changeCollColor(color,key){
    changeMapSymbology('coll');
    collSymbology[key]['color'] = color;
    layersArr['pointv'].getSource().changed();
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
                addLayerToSelList('Shapes');
                shapeActive = true;
            }
        });

        map.addInteraction(draw);
    }
}

function changeMapSymbology(symbology){
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

function cleanSelectionsLayer(){
    var selLayerFeatures = layersArr['select'].getSource().getFeatures();
    var currentlySelected = selectInteraction.getFeatures().getArray();
    for(i in selLayerFeatures){
        if(currentlySelected.indexOf(selLayerFeatures[i]) === -1){
            layersArr['select'].getSource().removeFeature(selLayerFeatures[i]);
        }
    }
}

function clearSelections(){
    for(i in selections){
        removeSelectionRecord(Number(selections[i]));
    }
    selections = [];
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
    if(clusterPoints){
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
            if(c == true) collid = collid+",";
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
                    for (e in polyCoords) {
                        for (i in polyCoords[e]) {
                            var ring = turf.lineString(polyCoords[e][i]);
                            //alert(ring.geometry.coordinates.length);
                            //ring = turf.simplify(ring, 0.000001, true);
                            ring = turf.simplify(ring, 0.001, true);
                            //alert(ring.geometry.coordinates.length);
                            polyCoords[e][i] = ring.geometry.coordinates;
                        }
                    }
                    var turfSimple = turf.multiPolygon(polyCoords);
                }
                if (geoType == 'Polygon') {
                    for (i in polyCoords) {
                        var ring = turf.lineString(polyCoords[i]);
                        //alert(ring.geometry.coordinates.length);
                        //ring = turf.simplify(ring, 0.000001, true);
                        ring = turf.simplify(ring, 0.001, true);
                        //alert(ring.geometry.coordinates.length);
                        polyCoords[i] = ring.geometry.coordinates;
                    }
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
    finishGetGeographyParams();
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
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            var resArr = JSON.parse(http.responseText);
            solrRecCnt = resArr['response']['numFound'];
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

    if(countryval){
        var countryvals = countryval.split(',');
        var countryCqlString = '';
        var countrySolrqString = '';
        for(i = 0; i < countryvals.length; i++){
            if(countryCqlString) countryCqlString += " OR ";
            if(countrySolrqString) countrySolrqString += " OR ";
            countryCqlString += "(country = '"+countryvals[i]+"')";
            countrySolrqString += "(country:'"+countryvals[i]+"')";
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
            stateSolrqString += "(StateProvince:"+statevals[i]+")";
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
            countySolrqString += "(county:"+countyvals[i].replace(" ","\ ")+"*)";
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
                    templocalitySolrqString += '((municipality:'+vals[i].replace(" ","\ ")+'*) OR (locality:*'+vals[i].replace(" ","\ ")+'*))';
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
            collectorSolrqString = '(recordedBy:*'+collectorvals[0].replace(" ","\ ")+'*)';
        }
        else if(collectorvals.length > 1){
            for (i in collectorvals){
                collectorCqlString += " OR (recordedBy LIKE '%"+collectorvals[i]+"%')";
                collectorSolrqString += ' OR (recordedBy:*'+collectorvals[i].replace(" ","\ ")+'*)';
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
}

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1],16),
        g: parseInt(result[2],16),
        b: parseInt(result[3],16)
    } : null;
}

function hideCluster(cluster){
    var invisibleStyle = new ol.style.Style({
        image: new ol.style.Circle({
            radius: cluster.get('radius'),
            fill: new ol.style.Fill({
                color: 'rgba(255, 255, 255, 0.01)'
            })
        })
    });
    cluster.setStyle(invisibleStyle);
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
    if(index > 1) startindex = (index - 1)*lazyLoadCnt;
    var http = new XMLHttpRequest();
    var url = "rpc/SOLRConnector.php";
    var params = solrqString+'&rows='+lazyLoadCnt+'&start='+startindex+'&wt=geojson';
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            callback(http.responseText);
        }
    };
    http.send(params);
}

function loadPoints(){
    cqlString = '';
    solrqString = '';
    taxaCnt = 0;
    collSymbology = [];
    taxaSymbology = [];
    selections = [];
    cqlString = newcqlString;
    solrqString = newsolrqString;
    if(newsolrqString){
        getSOLRRecCnt(false,function(res) {
            if(solrRecCnt){
                setRecordsTab();
                if(loadVectorPoints){
                    loadPointWFSLayer(0);
                }
                else{
                    loadPointWMSLayer();
                }
                cleanSelectionsLayer();
                changeRecordPage(1);
                $('#recordstab').tabs({active: 1});
                $("#accordion").accordion("option","active",1);
                selectInteraction.getFeatures().clear();
                if(!pointActive){
                    addLayerToSelList('Points');
                    pointActive = true;
                }
            }
            else{
                setRecordsTab();
                if(pointActive){
                    removeLayerToSelList('Points');
                    pointActive = false;
                }
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
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            taxaArr = JSON.parse(http.responseText);
            callback(1);
        }
    }
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
                                        taxaSolrqString += " OR (sciname:"+scinameArr[s].replace(" ","\ ")+"*)";
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
                                taxaSolrqString += " OR (sciname:"+i.replace(" ","\ ")+"*)";
                                taxaCqlString += " OR sciname LIKE '"+i+"%'";
                            }
                        }
                        if(taxaArr[i]["synonyms"]){
                            var synArr = [];
                            synArr = taxaArr[i]["synonyms"];
                            if(synArr.length > 0){
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
                                taxaSolrqString += " OR (tidinterpreted:("+tidArr.join()+"))";
                                taxaCqlString += " OR tidinterpreted IN("+tidArr.join()+")";
                            }
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

function primeSymbologyData(features){
    for(f in features) {
        var color = 'e69e67';
        var collName = features[f].get('CollectionName');
        var collid = features[f].get('collid');
        var tidinterpreted = features[f].get('tidinterpreted');
        var sciname = features[f].get('sciname');
        var family = features[f].get('family');
        if(family){
            family = family.toUpperCase();
        }
        else{
            family = 'undefined';
        }
        var namestring = (sciname?sciname:'')+(tidinterpreted?tidinterpreted:'');
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

function processPointSelection(cluster){
    var feature = (cluster.get('features')?cluster.get('features')[0]:cluster);
    var occid = Number(feature.get('occid'));
    if(selections.indexOf(occid) < 0){
        selections.push(occid);
        var infoArr = getPointInfoArr(cluster);
        updateSelections(occid,infoArr);
    }
    else{
        var index = selections.indexOf(occid);
        selections.splice(index, 1);
        removeSelectionRecord(occid);
    }
    var style = (clusterPoints?setClusterSymbol(cluster):setSymbol(cluster));
    cluster.setStyle(style);
    adjustSelectionsTab();
}

function refreshLayerOrder(){
    var layerCount = map.getLayers().getArray().length;
    layersArr['dragdrop1'].setZIndex(layerCount-5);
    layersArr['dragdrop2'].setZIndex(layerCount-4);
    layersArr['dragdrop3'].setZIndex(layerCount-3);
    layersArr['select'].setZIndex(layerCount-2);
    //layersArr['pointi'].setZIndex(layerCount-2);
    layersArr['pointv'].setZIndex(layerCount-1);
    layersArr['spider'].setZIndex(layerCount);
}

function removeLayerToSelList(layer){
    var selectobject = document.getElementById("selectlayerselect");
    for (var i=0; i<selectobject.length; i++){
        if(selectobject.options[i].value == layer)
            selectobject.remove(i);
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

function removeUploadLayer(layerID){
    var layerDivId = "lay-"+layerID;
    if(document.getElementById(layerDivId)){
        var layerDiv = document.getElementById(layerDivId);
        layerDiv.parentNode.removeChild(layerDiv);
    }
    layersArr[layerID].setSource(blankdragdropsource);
    if(layerID == 'dragdrop1') dragDrop1 = false;
    else if(layerID == 'dragdrop2') dragDrop2 = false;
    else if(layerID == 'dragdrop3') dragDrop3 = false;
    removeLayerToSelList(layerID);
    toggleLayerController();
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
    document.getElementById("symbolizeReset1").disabled = false;
    document.getElementById("symbolizeReset2").disabled = false;
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
        })
    });
}

function setClusterSymbol(feature) {
    var style = '';
    var stroke = '';
    var selected = false;
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
                radius: radius,
                stroke: stroke,
                fill: new ol.style.Fill({
                    color: [colorArr['r'],colorArr['g'],colorArr['b'],0.8]
                })
            }),
            text: new ol.style.Text({
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
    return style;
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

function setLayersTable(){
    var http = new XMLHttpRequest();
    var url = "rpc/getlayersarr.php";
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            var layerArr = [];
            var jsonReturn = false;
            try{
                layerArr = JSON.parse(http.responseText);
            }catch(e){
                return false;
            }
            if(layerArr){
                layersExist = true;
                for(i in layerArr){
                    buildLayerTableRow(layerArr[i],false);
                }
                toggleLayerController();
            }
        }
    };
    http.send();
}

function setRecordsTab(){
    if(solrRecCnt > 0){
        document.getElementById("recordsHeader").style.display = "block";
        document.getElementById("recordstab").style.display = "block";
    }
    else{
        document.getElementById("recordsHeader").style.display = "none";
        document.getElementById("recordstab").style.display = "none";
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
    var style = '';
    var stroke = '';
    var selected = false;
    var cKey = feature.get(clusterKey);
    var recType = feature.get('CollType');
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

    if(selected) stroke = new ol.style.Stroke({color: '#10D8E6', width: 2});
    else stroke = new ol.style.Stroke({color: 'black', width: 1});

    var fill = new ol.style.Fill({color: color});

    if(recType.toLowerCase().indexOf('observation') !== -1){
        style = new ol.style.Style({
            image: new ol.style.RegularShape({
                fill: fill,
                stroke: stroke,
                points: 3,
                radius: 7
            })
        });
    }
    else{
        style = new ol.style.Style({
            image: new ol.style.Circle({
                radius: 7,
                fill: fill,
                stroke: stroke
            })
        });
    }

    return style;
}

function showCluster(cluster){
    var clusterStyle = setClusterSymbol(cluster);
    cluster.setStyle(clusterStyle);
}

function spiderifyPoints(features){
    spiderCluster = 1;
    var spiderFeatures = [];
    for(f in features){
        var feature = features[f];
        hideCluster(feature);
        hiddenClusters.push(feature);
        var addFeatures = feature.get('features');
        for(f in addFeatures){
            spiderFeatures.push(addFeatures[f]);
        }
    }

    var source = layersArr['spider'].getSource();
    source.clear();

    var center = features[0].getGeometry().getCoordinates();
    var pix = map.getView().getResolution();
    var r = pix * 12 * (0.5 + spiderFeatures.length / 4);
    if (spiderFeatures.length <= 10){
        var max = Math.min(spiderFeatures.length, 10);
        for(i=0; i<max; i++){
            var a = 2*Math.PI*i/max;
            if (max==2 || max == 4) a += Math.PI/4;
            var p = [ center[0]+r*Math.sin(a), center[1]+r*Math.cos(a) ];
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
        for(i=0; i<max; i++){
            r = d/2 + d*a/(2*Math.PI);
            a = a + (d+0.1)/r;
            var dx = pix*r*Math.sin(a)
            var dy = pix*r*Math.cos(a)
            var p = [ center[0]+dx, center[1]+dy ];
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

function toggleLayerController(layerID){
    if(!layersExist && !dragDrop1 && !dragDrop2 && !dragDrop3){
        $('#addLayers').popup('hide');
        document.getElementById("layerControllerLink").style.display = "none";
    }
    else{
        document.getElementById("layerControllerLink").style.display = "block";
    }
}

function toggleUploadLayer(c){
    var layer = c.value;
    if(c.checked == true){
        layersArr[layer].setVisible(true);
        addLayerToSelList(c.value);
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
        selcat = wordwrap(infoArr['catalognumber'], 7, '<br />\n');
        var mouseOverLabel = "openOccidInfoBox("+seloccid+",'"+infoArr['collector']+"');";
        var labelHTML = '<a href="#" onmouseover="'+mouseOverLabel+'" onmouseout="closeOccidInfoBox();" onclick="openIndPopup('+seloccid+'); return false;">';
        labelHTML += wordwrap(infoArr['collector'], 12, '<br />\n');
        labelHTML += '</a>';
        sellabel = labelHTML;
        sele = wordwrap(infoArr['eventdate'], 10, '<br />\n');
        sels = wordwrap(infoArr['sciname'], 12, '<br />\n');
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

function wordwrap(str,width,brk,cut){
    brk = brk || 'n';
    width = width || 75;
    cut = cut || false;
    if(!str) return str;
    var regex = '.{1,'+width+'}(\s|$)'+(cut?'|.{'+width+'}|.+$':'|\S+?(\s|$)');
    return str.match(RegExp(regex,'g')).join(brk);
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