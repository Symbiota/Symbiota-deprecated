/**
* key: input for LOOK(1)
* cont: function(res) for return of suggest results
*/ 

/*$(function() {
	$( "#accordion" ).accordion({
		collapsible: true,
		heightStyle: "fill",
		active: 1
	});
});*/

$(window).resize(function(){
	$("#accordion").accordion("refresh");
});

$(document).on("pageloadfailed", function(event, data){
    event.preventDefault();
});

$(document).ready(function() {
	$('#tabs1').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});
	$('#tabs2').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});
	
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	$( "#taxa" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/taxalist.php", {
					term: extractLast( request.term ), t: function() { return document.mapsearchform.taxontype.value; }
				}, response );
			},
			appendTo: "#taxa_autocomplete",
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 4 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				this.value = terms.join( ", " );
				return false;
			}
		},{});
});

function GetXmlHttpObject(){
	var xmlHttp=null;
	try{
		// Firefox, Opera 8.0+, Safari, IE 7.x
		xmlHttp=new XMLHttpRequest();
	}
	catch (e){
		// Internet Explorer
		try{
			xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e){
			xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xmlHttp;
}

function checkUpperLat(){
	if(document.mapsearchform.upperlat.value != ""){
		if(document.mapsearchform.upperlat_NS.value=='N'){
			document.mapsearchform.upperlat.value = Math.abs(parseFloat(document.mapsearchform.upperlat.value));
		}
		else{
			document.mapsearchform.upperlat.value = -1*Math.abs(parseFloat(document.mapsearchform.upperlat.value));
		}
	}
}
		
function checkBottomLat(){
	if(document.mapsearchform.bottomlat.value != ""){
		if(document.mapsearchform.bottomlat_NS.value == 'N'){
			document.mapsearchform.bottomlat.value = Math.abs(parseFloat(document.mapsearchform.bottomlat.value));
		}
		else{
			document.mapsearchform.bottomlat.value = -1*Math.abs(parseFloat(document.mapsearchform.bottomlat.value));
		}
	}
}

function checkRightLong(){
	if(document.mapsearchform.rightlong.value != ""){
		if(document.mapsearchform.rightlong_EW.value=='E'){
			document.mapsearchform.rightlong.value = Math.abs(parseFloat(document.mapsearchform.rightlong.value));
		}
		else{
			document.mapsearchform.rightlong.value = -1*Math.abs(parseFloat(document.mapsearchform.rightlong.value));
		}
	}
}

function checkLeftLong(){
	if(document.mapsearchform.leftlong.value != ""){
		if(document.mapsearchform.leftlong_EW.value=='E'){
			document.mapsearchform.leftlong.value = Math.abs(parseFloat(document.mapsearchform.leftlong.value));
		}
		else{
			document.mapsearchform.leftlong.value = -1*Math.abs(parseFloat(document.mapsearchform.leftlong.value));
		}
	}
}

function checkPointLat(){
	if(document.mapsearchform.pointlat.value != ""){
		if(document.mapsearchform.pointlat_NS.value=='N'){
			document.mapsearchform.pointlat.value = Math.abs(parseFloat(document.mapsearchform.pointlat.value));
		}
		else{
			document.mapsearchform.pointlat.value = -1*Math.abs(parseFloat(document.mapsearchform.pointlat.value));
		}
	}
}

function checkPointLong(){
	if(document.mapsearchform.pointlong.value != ""){
		if(document.mapsearchform.pointlong_EW.value=='E'){
			document.mapsearchform.pointlong.value = Math.abs(parseFloat(document.mapsearchform.pointlong.value));
		}
		else{
			document.mapsearchform.pointlong.value = -1*Math.abs(parseFloat(document.mapsearchform.pointlong.value));
		}
	}
}

function checkForm(){
	var frm = document.mapsearchform;

	//make sure they have filled out at least one field.
	if((frm.taxa.value == '') && (frm.country.value == '') && (frm.state.value == '') && (frm.county.value == '') && 
		(frm.locality.value == '') && (frm.upperlat.value == '') && (frm.pointlat.value == '') && 
		(frm.collector.value == '') && (frm.collnum.value == '') && (frm.eventdate.value == '')){
        alert("Please fill in at least one search parameter!");
        return false;
    }
 
    if(frm.upperlat.value != '' || frm.bottomlat.value != '' || frm.leftlong.value != '' || frm.rightlong.value != ''){
        // if Lat/Long field is filled in, they all should have a value!
        if(frm.upperlat.value == '' || frm.bottomlat.value == '' || frm.leftlong.value == '' || frm.rightlong.value == ''){
			alert("Error: Please make all Lat/Long bounding box values contain a value or all are empty");
			return false;
        }

		// Check to make sure lat/longs are valid.
		if(Math.abs(frm.upperlat.value) > 90 || Math.abs(frm.bottomlat.value) > 90 || Math.abs(frm.pointlat.value) > 90){
			alert("Latitude values can not be greater than 90 or less than -90.");
			return false;
		} 
		if(Math.abs(frm.leftlong.value) > 180 || Math.abs(frm.rightlong.value) > 180 || Math.abs(frm.pointlong.value) > 180){
			alert("Longitude values can not be greater than 180 or less than -180.");
			return false;
		} 
		if(parseFloat(frm.upperlat.value) < parseFloat(frm.bottomlat.value)){
			alert("Your northern latitude value is less then your southern latitude value. Please correct this.");
			return false;
		}
		if(parseFloat(frm.leftlong.value) > parseFloat(frm.rightlong.value)){
			alert("Your western longitude value is greater then your eastern longitude value. Please correct this. Note that western hemisphere longitudes in the decimal format are negitive.");
			return false;
		}
    }

	//Same with point radius fields
    if(frm.pointlat.value != '' || frm.pointlong.value != '' || frm.radius.value != ''){
    	if(frm.pointlat.value == '' || frm.pointlong.value == '' || frm.radius.value == ''){
    		alert("Error: Please make all Lat/Long point-radius values contain a value or all are empty");
			return false;
		}
	}

    return true;
}

function reSymbolizeMap(type) {
	var searchForm = document.getElementById("mapsearchform");
	if (type == 'taxa') {
		document.getElementById("mapsymbology").value = 'taxa';
	}
	if (type == 'coll') {
		document.getElementById("mapsymbology").value = 'coll';
	}
	submitMapForm(searchForm);
}

function checkRecordLimit(f) {
	var recordLimit = document.getElementById("recordlimit").value;
	if (recordLimit > 50000) {
		alert("Record limit cannot exceed 50000.");
		document.getElementById("recordlimit").value = 5000;
		return;
	}
	if (recordLimit <= 50000) {
		return confirm('Increasing the record limit can cause delays in loading the map, or for your browser to crash.');
	}
}

function updateRadius(){
	var radiusUnits = document.getElementById("radiusunits").value;
	var radiusInMiles = document.getElementById("radiustemp").value;
	if(radiusUnits == "km"){
		radiusInMiles = radiusInMiles*0.6214; 
	}
	document.getElementById("radius").value = radiusInMiles;
}

function clearSelection() {
	if (selectedShape) {
		selectedShape.setEditable(false);
		selectedShape = null;
	}
	document.getElementById("pointlat").value = '';
	document.getElementById("pointlong").value = '';
	document.getElementById("radius").value = '';
	document.getElementById("upperlat").value = '';
	document.getElementById("leftlong").value = '';
	document.getElementById("bottomlat").value = '';
	document.getElementById("rightlong").value = '';
	document.getElementById("poly_array").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "block";
	document.getElementById("polygeocriteria").style.display = "none";
	document.getElementById("circlegeocriteria").style.display = "none";
	document.getElementById("rectgeocriteria").style.display = "none";
	document.getElementById("deleteshapediv").style.display = "none";
}

function setSelection(shape) {
	clearSelection();
	var selectedShapeType = shape.type;
	selectedShape = shape;
	selectedShape.setEditable(true);
	//selectColor(shape.get('fillColor') || shape.get('strokeColor'));
	if (selectedShapeType == 'circle') {
		getCircleCoords(shape);
	}
	if (selectedShapeType == 'rectangle') {
		getRectangleCoords(shape);
	}
	if (selectedShapeType == 'polygon') {
		getPolygonCoords(shape);
	}
}

function deleteSelectedShape() {
	if (selectedShape){
		selectedShape.setMap(null);
		clearSelection();
	}
}

function getCircleCoords(circle) {
	var rad = (circle.getRadius());
	var radius = (rad/1000)*0.6214;
	document.getElementById("pointlat").value = (circle.getCenter().lat());
	document.getElementById("pointlong").value = (circle.getCenter().lng());
	document.getElementById("radius").value = radius;
	document.getElementById("upperlat").value = '';
	document.getElementById("leftlong").value = '';
	document.getElementById("bottomlat").value = '';
	document.getElementById("rightlong").value = '';
	document.getElementById("poly_array").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "none";
	document.getElementById("polygeocriteria").style.display = "none";
	document.getElementById("circlegeocriteria").style.display = "block";
	document.getElementById("rectgeocriteria").style.display = "none";
	document.getElementById("deleteshapediv").style.display = "block";
}
  
function getRectangleCoords(rectangle) {
	document.getElementById("upperlat").value = (rectangle.getBounds().getNorthEast().lat());
	document.getElementById("rightlong").value = (rectangle.getBounds().getNorthEast().lng());
	document.getElementById("bottomlat").value = (rectangle.getBounds().getSouthWest().lat());
	document.getElementById("leftlong").value = (rectangle.getBounds().getSouthWest().lng());
	document.getElementById("pointlat").value = '';
	document.getElementById("pointlong").value = '';
	document.getElementById("radius").value = '';
	document.getElementById("poly_array").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "none";
	document.getElementById("polygeocriteria").style.display = "none";
	document.getElementById("circlegeocriteria").style.display = "none";
	document.getElementById("rectgeocriteria").style.display = "block";
	document.getElementById("deleteshapediv").style.display = "block";
}
  
function getPolygonCoords(polygon) {
	var coordinates = [];
	coordinates = (polygon.getPath().getArray());
	var json_coords = JSON.stringify(coordinates);
	document.getElementById("poly_array").value = json_coords;
	document.getElementById("pointlat").value = '';
	document.getElementById("pointlong").value = '';
	document.getElementById("radius").value = '';
	document.getElementById("upperlat").value = '';
	document.getElementById("leftlong").value = '';
	document.getElementById("bottomlat").value = '';
	document.getElementById("rightlong").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "none";
	document.getElementById("polygeocriteria").style.display = "block";
	document.getElementById("circlegeocriteria").style.display = "none";
	document.getElementById("rectgeocriteria").style.display = "none";
	document.getElementById("deleteshapediv").style.display = "block";
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

function openOccidInfoBox(label,lat,lon){
	var myOptions = {
		content: label,
		boxStyle: {
			border: "1px solid black",
			background: "#ffffff",
			textAlign: "center",
			fontSize: "8pt"
		},
		disableAutoPan: true,
		pixelOffset: new google.maps.Size(-25, 0),
		position: new google.maps.LatLng(lat, lon),
		isHidden: false,
		closeBoxURL: "",
		pane: "floatPane",
		enableEventPropagation: true
	};

	ibLabel = new InfoBox(myOptions);
	ibLabel.open(map);
}

function closeOccidInfoBox(){
	ibLabel.close();
}

function generateRandColor(){
	var hexColor = '';
	//hexColor = Math.floor((Math.abs(Math.random()*16777215))%16777215).toString(16);
	var x = Math.round(0xffffff * Math.random()).toString(16);
	var y = (6-x.length);
	var z = '000000';
	var z1 = z.substring(0,y);
	hexColor = z1 + x;
	return hexColor;
}

Array.prototype.contains = function ( needle ) {
   for (i in this) {
       if (this[i] == needle) return true;
   }
   return false;
}

function changeKeyColor(v,markers){
	var newMarkerColor = '#'+v;
	if (markers) {
		for (i in markers) {
			if(markers[i].customInfo=='obs'){
				var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
				markers[i].setIcon(markerIcon);
			}
			if(markers[i].customInfo=='spec'){
				var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
				markers[i].setIcon(markerIcon);
			}
		}
	}
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

function selectAll(cb){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var f = cb.form;
	for(var i=0;i<f.length;i++){
		if(f.elements[i].name == "db[]" || f.elements[i].name == "cat[]" || f.elements[i].name == "occid[]") f.elements[i].checked = boxesChecked;
	}
}

function uncheckAll(f){
	document.getElementById('dballcb').checked = false;
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

function unselectCat(catTarget){
	var catObj = document.getElementById(catTarget);
	catObj.checked = false;
	uncheckAll();
}

function verifyCollForm(f){
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
	if(!formVerified){
		alert("Please choose at least one collection!");
		return false;
	}
	else{
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

function verifyOtherCatForm(f){
	var dbElements = document.getElementsByName("surveyid[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		if(dbElement.checked) return true;
	}
	alert("Please choose at least one checkbox!");
	return false;
}

function submitMapForm(f){
	f.submit();
}

function checkKey(e){
	var key;
	if(window.event){
		key = window.event.keyCode;
	}else{
		key = e.keyCode;
	}
	if(key == 13){
		document.collections.submit();
	}
}

function shiftKeyBox(tid){
	var currentkeys = document.getElementById("symbologykeysbox").innerHTML;
	var keyDivName = tid+"keyrow";
	var colorBoxName = "taxaColor"+tid;
	var newKeyToAdd = document.getElementById(keyDivName).innerHTML;
	document.getElementById(colorBoxName).color.hidePicker();
	//document.getElementById(keyDivName).innerHTML = '';
	document.getElementById("symbologykeysbox").innerHTML = currentkeys+newKeyToAdd;
}

function openIndPopup(occid){
	openPopup('individual/index.php?occid=' + occid);
}

function openEditorPopup(occid){
	openPopup('editor/occurrenceeditor.php?occid=' + occid);
}

function openPopup(urlStr){
	var wWidth = 900;
	newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function refreshClustering(){
	var gridSizeSett = document.getElementById("gridsize").value;
	var minClusterSett = document.getElementById("minclustersize").value;
	if(document.getElementById("clusteroff").checked){
		var clstrSwitch = 1;
	}
	else{
		var clstrSwitch = 0;
	}
	var searchForm = document.getElementById("mapsearchform");
	document.getElementById("gridSizeSetting").value = gridSizeSett;
	document.getElementById("minClusterSetting").value = minClusterSett;
	document.getElementById("clusterSwitch").value = clstrSwitch;
	submitMapForm(searchForm);
}

function changeTaxonomy(starr,f){
	var taxonFilter = document.getElementById("taxonfilter").value;
	document.getElementById("maptaxalist").innerHTML = "<p>Loading...</p>";
	//alert(taxonFilter);
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	var url='rpc/changemaptaxonomy.php?starr='+starr+'&taxonfilter='+taxonFilter;
	
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			var newMapTaxaList = JSON.parse(sutXmlHttp.responseText);
			document.getElementById("maptaxalist").innerHTML = newMapTaxaList;
		}
	};
	sutXmlHttp.open("POST",url,true);
	sutXmlHttp.send(null);
}

function changeRecordPage(starr,page){
	document.getElementById("queryrecords").innerHTML = "<p>Loading...</p>";
	
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	var url="rpc/changemaprecordpage.php?starr="+starr+"&page="+page;
	
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			var newMapRecordList = JSON.parse(sutXmlHttp.responseText);
			document.getElementById("queryrecords").innerHTML = newMapRecordList;
			var _img = document.getElementById('edit_img');
			var newImg = new Image;
			newImg.src = '../images/edit.png';
			newImg.onload = function() {
				_img.src = this.src;
			}
		}
	};
	sutXmlHttp.open("POST",url,true);
	sutXmlHttp.send(null);
}