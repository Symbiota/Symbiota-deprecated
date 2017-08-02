$(window).resize(function(){
	var winHeight = $(window).height();
	winHeight = winHeight + "px";
	document.getElementById('mapinterface').style.height = winHeight;
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
	$('#tabs3').tabs({
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
				var t = document.mapsearchform.taxontype.value;
				var source = '';
                var rankLow = '';
                var rankHigh = '';
                var rankLimit = '';
				if(t == 5){
                    source = '../../webservices/autofillvernacular.php';
				}
				else{
                    source = '../../webservices/autofillsciname.php';
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
		
	/*$( "#checklistname" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/checklistlist.php", {
					term: extractLast( request.term )
				}, response );
			},
			appendTo: "#checklist_autocomplete",
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
				terms.push( ui.item.label );
				document.getElementById('clid').value = ui.item.value;
				this.value = terms;
				return false;
			},
			change: function (event, ui) {
				if (!ui.item) {
					this.value = '';
					document.getElementById('clid').value = '';
				}
			}
		},{});*/
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
	if(!isNaN(recordLimit) && recordLimit > 0){
		if (recordLimit > 50000) {
			alert("Record limit cannot exceed 50000.");
			document.getElementById("recordlimit").value = 5000;
			return;
		}
		if (recordLimit <= 50000) {
			if(recordLimit > 5000){
				if(confirm('Increasing the record limit can cause delays in loading the map, or for your browser to crash.')){
					return true;
				}
				else{
					document.getElementById("recordlimit").value = 5000;
				}
			}
		}
	}
	else{
		document.getElementById("recordlimit").value = 5000;
		alert("Record Limit must be set to a whole number greater than zero.");
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
	var coordinatesMVC = (polygon.getPath().getArray());
	for(i=0;i<coordinatesMVC.length;i++){
		var mvcString = coordinatesMVC[i].toString();
		mvcString = mvcString.replace("(","");
		mvcString = mvcString.replace(")","");
		var latlngArr = mvcString.split(", ");
		coordinates.push({"A":latlngArr[0],"D":latlngArr[1]});
		if(i==0){
			var firstSet = latlngArr;
		}
	}
	coordinates.push({"A":firstSet[0],"D":firstSet[1]});
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
	newWindow = window.open('../individual/index.php?occid='+occId+'&clid='+clid+',indspec' + occId+',scrollbars=1,toolbar=0,resizable=1,width='+wWidth+',height=600,left=20,top=20');
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
			padding: "2px",
			fontSize: "12px"
		},
		disableAutoPan: true,
		pixelOffset: new google.maps.Size(-25, 0),
		position: new google.maps.LatLng(lat, lon),
		isHidden: false,
		closeBoxURL: "",
		pane: "floatPane",
		enableEventPropagation: false
	};

	ibLabel = new InfoBox(myOptions);
	ibLabel.open(map);
}

function closeOccidInfoBox(){
	if(ibLabel){
        ibLabel.close();
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

function changeKeyColor(v,markers){
	var newMarkerColor = '#'+v;
	if (markers) {
		for (i in markers) {
			if(markers[i].customInfo=='obs'){
				if(markers[i].selected==true){
					var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
				}
				else{
					var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
				}
				markers[i].color = v;
				markers[i].setIcon(markerIcon);
			}
			if(markers[i].customInfo=='spec'){
				if(markers[i].selected==true){
					var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
				}
				else{
					var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
				}
				markers[i].color = v;
				markers[i].setIcon(markerIcon);
			}
		}
	}
}

function selectMarker(marker){
	var markerColor = '#'+marker.color;
	if(marker.customInfo=='obs'){
		var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
		marker.setIcon(markerIcon);
	}
	if(marker.customInfo=='spec'){
		var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
		marker.setIcon(markerIcon);
	}
	marker.selected = true;
}

function selectDsMarker(marker){
	var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#10D8E6",strokeWeight:2};
	marker.setIcon(markerIcon);
	marker.selected = true;
}

function deselectMarker(marker){
	var markerColor = '#'+marker.color;
	if(marker.customInfo=='obs'){
		var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
		marker.setIcon(markerIcon);
	}
	if(marker.customInfo=='spec'){
		var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
		marker.setIcon(markerIcon);
	}
	marker.selected = false;
}

function deselectDsMarker(marker){
	var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#000000",strokeWeight:2};
	marker.setIcon(markerIcon);
	marker.selected = false;
}

function findSelections(c){
	if(c.checked == true){
		var activeTab = $('#tabs2').tabs("option","active");
		if(activeTab==1){
			if($('.occcheck:checked').length==$('.occcheck').length){
				document.getElementById("selectallcheck").checked = true;
			}
		}
		var selectedbox = document.getElementById("selectedpoints");
		selectedbox.value = c.value;
		selectPoints();
	}
	else if(c.checked == false){
		var activeTab = $('#tabs2').tabs("option","active");
		if(activeTab==1){
			document.getElementById("selectallcheck").checked = false;
		}
		removeSelectionRecord(c.value);
		var deselectedbox = document.getElementById("deselectedpoints");
		deselectedbox.value = c.value;
		deselectPoints();
	}
}

function findDsSelections(c){
	if(c.checked == true){
		var activeTab = $('#tabs3').tabs("option","active");
		if(activeTab==1){
			if($('.dsocccheck:checked').length==$('.dsocccheck').length){
				document.getElementById("dsselectallcheck").checked = true;
			}
		}
		var selectedbox = document.getElementById("selecteddspoints");
		selectedbox.value = c.value;
		selectDSPoints();
	}
	else if(c.checked == false){
		var activeTab = $('#tabs3').tabs("option","active");
		if(activeTab==1){
			document.getElementById("dsselectallcheck").checked = false;
		}
		var deselectedbox = document.getElementById("deselecteddspoints");
		deselectedbox.value = c.value;
		deselectDSPoints();
	}
}

function adjustSelectionsTab(){
	if(selections.length > 0){
		document.getElementById("selectionstab").style.display = "block";
		updateSelections();
	}
	else{
		document.getElementById("selectionstab").style.display = "none";
		var activeTab = $('#tabs2').tabs("option","active");
		if(activeTab==3){
			$('#tabs2').tabs({active:0});
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

			var iconImg = new google.maps.MarkerImage( '../../images/google/arrow.png' );

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
		if(f.elements[i].name == "db[]" || f.elements[i].name == "cat[]" || f.elements[i].name == "occid[]"){ 
			f.elements[i].checked = boxesChecked;
		}
		if(f.elements[i].name == "occid[]"){
			f.elements[i].onchange();
		}
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
	document.getElementById("symbologykeysbox").innerHTML = currentkeys+newKeyToAdd;
}

function openIndPopup(occid){
	openPopup('../individual/index.php?occid=' + occid);
}

function openGarminDownloader(type){
	if(type=="query"){
		var jsonSelections = JSON.stringify(selections);
	}
	if(type=="dataset"){
		if(dsselections.length!=0){
			var jsonSelections = JSON.stringify(dsselections);
		}
		else{
			alert("Please select records from the dataset to send to the GPS unit.");
			return;
		}
	}
	var url = 'garmin.php?selections='+jsonSelections;
	newWindow = window.open(url,'popup','scrollbars=1,toolbar=0,resizable=1,width=450,height=350,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function openEditorPopup(occid){
	openPopup('editor/occurrenceeditor.php?occid=' + occid);
}

function openLogin(){
	if(starr){
		loginstarr = starr.replace(/"/g, "'");
		window.location='../../profile/index.php?refurl=../collections/map/mapinterface.php?starr='+loginstarr;
	}
	else{
		window.location='../../profile/index.php?refurl=../collections/map/mapinterface.php';
	}
}

function prepSelectionKml(f){
	if(f.kmltype.value=='dsselectionquery'){
		if(dsselections.length!=0){
			var jsonSelections = JSON.stringify(dsselections);
		}
		else{
			alert("Please select records from the dataset to create KML file.");
			return;
		}
	}
	else{
		var jsonSelections = JSON.stringify(selections);
	}
	f.selectionskml.value = jsonSelections;
	f.starrkml.value = starr;
	f.submit();
}

function openPopup(urlStr){
	wWidth = document.body.offsetWidth*0.90;
	newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function setClustering(){
	var gridSizeSett = document.getElementById("gridsize").value;
	var minClusterSett = document.getElementById("minclustersize").value;
	if(document.getElementById("clusteroff").checked==true){
		var clstrSwitch = "y";
	}
	else{
		var clstrSwitch = "n";
	}
	document.getElementById("gridSizeSetting").value = gridSizeSett;
	document.getElementById("minClusterSetting").value = minClusterSett;
	document.getElementById("clusterSwitch").value = clstrSwitch;
}

function refreshClustering(){
	var searchForm = document.getElementById("mapsearchform");
	submitMapForm(searchForm);
}

function changeTaxonomy(starr,f){
	var taxonFilter = document.getElementById("taxonfilter").value;
	document.getElementById("maptaxalist").innerHTML = "<p>Loading...</p>";
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

/*function changeRecordPage(starr,page){
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
}*/

function removeSelectionRecord(sel){
	var selDivId = "sel"+sel;
	if(document.getElementById(selDivId)){
		var selDiv = document.getElementById(selDivId);
		selDiv.parentNode.removeChild(selDiv);
	}
}

function findUncheckedSelections(c){
	if(c.checked == false){
		var occid = c.value;
		var chbox = 'ch'+occid;
		removeSelectionRecord(occid);
		if(document.getElementById(chbox)){
			document.getElementById(chbox).checked = false;
		}
		var deselectedbox = document.getElementById("deselectedpoints");
		deselectedbox.value = occid;
		deselectPoints();
		adjustSelectionsTab();
	}
}

function updateSelections(){
	var selectionList = '';
	var trfragment = '';
	selectionList += document.getElementById("selectiontbody").innerHTML;
	for (i = 0; i < selections.length; i++) {
		var seloccid = selections[i];
		var divid = "sel"+seloccid;
		var trid = "tr"+seloccid;
		if(!document.getElementById(divid)){
			if(document.getElementById(trid)){
				var catid = "cat"+seloccid;
				var labelid = "label"+seloccid;
				var eid = "e"+seloccid;
				var sid = "s"+seloccid;
				var selcat = document.getElementById(catid).innerHTML;
				var sellabel = document.getElementById(labelid).innerHTML;
				var sele = document.getElementById(eid).innerHTML;
				var sels = document.getElementById(sid).innerHTML;
				trfragment = '';
				trfragment += '<tr id="sel'+seloccid+'" >';
				trfragment += '<td>';
				trfragment += '<input type="checkbox" id="selch'+seloccid+'" name="occid[]" value="'+seloccid+'" onchange="findUncheckedSelections(this);" checked />';
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
			else{
				var sutXmlHttp=GetXmlHttpObject();
				if (sutXmlHttp==null){
					alert ("Your browser does not support AJAX!");
					return;
				}
				var url="rpc/updateselections.php?selected="+seloccid;
				
				sutXmlHttp.onreadystatechange=function(){
					if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
						selectionList += sutXmlHttp.responseText;
					}
				};
				sutXmlHttp.open("POST",url,false);
				sutXmlHttp.send(null);
			}
		}
	}
	document.getElementById("selectiontbody").innerHTML = selectionList;
}

function loadRecordsetList(uid,selset){
	var recordsetlisthtml = '';
	var recordsets = '';
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/maprecordsetmanager.php?uid="+uid+"&action=loadlist&selset="+selset;
	
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			recordsets = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	
	if(recordsets){
		recordsetlisthtml += '<div id="adddiv" style="border:1px black solid;margin-top:10px;padding:5px;display:none;">';
	}
	else{
		recordsetlisthtml += '<div id="adddiv" style="border:1px black solid;margin-top:10px;padding:5px;display:block;">';
	}
	recordsetlisthtml += '<legend><b>Create New Dataset</b></legend><br />';
	recordsetlisthtml += '<form name="datasetadminform" action="#" method="post">';
	recordsetlisthtml += '<div>Name<br /><input data-role="none" name="name" id="newdsname" type="text" style="width:250px" /></div>';
	recordsetlisthtml += '<div style="margin-top:5px;">Notes<br /><input data-role="none" name="notes" id="newdsnotes" type="text" style="width:90%;" value="" /></div>';
	var onclickhandler = "createDataset("+uid+");"
	recordsetlisthtml += '<div style="margin-top:5px;"><input data-role="none" name="submitaction" type="button" onclick="'+onclickhandler+'" value="Create New Dataset" /></div></form></div>';
	var toggle = "toggle('adddiv')";
	if(recordsets){
		recordsetlisthtml += '<div style="width:100%;"><div style="float:right;margin:10px;" title="Create New Dataset" onclick="'+toggle+'">';
		recordsetlisthtml += '<img src="../../images/add.png" style="width:14px;" /></div></div>';
	}
	if(recordsets){
		recordsetlisthtml += '<div id="nodsdiv" style="display:none;margin-top:5px;">There are no datasets linked to your profile, please create one in the box above to continue.</div>';
	}
	else{
		recordsetlisthtml += '<div id="nodsdiv" style="display:block;margin-top:5px;">There are no datasets linked to your profile, please create one in the box above to continue.</div>';
	}
	if(recordsets){
		recordsetlisthtml += '<div id="dsdiv" style="display:block;margin-top:5px;">Select a dataset from the list below, or click on the green plus sign to create a new one.</div>';
	}
	else{
		recordsetlisthtml += '<div id="dsdiv" style="display:none;margin-top:5px;">Select a dataset from the list below, or click on the green plus sign to create a new one.</div>';
	}
	if(recordsets){
		recordsetlisthtml += '<div id="dsmanagementdiv" style="display:none;"><hr />';
		recordsetlisthtml += '<div style="height:25px;">';
		recordsetlisthtml += '<div style="float:left;"><button data-role="none" id="" onclick="clearDataset();" >Remove Dataset</button></div>';
		recordsetlisthtml += '<div id="dsdeletediv" style="float:right;display:none;"><button data-role="none" onclick="deleteDataset();" >Delete Dataset</button></div>';
		recordsetlisthtml += '</div>';
		//recordsetlisthtml += '<div style="float:right;"><button data-role="none" id="" onclick="cloneDataset();" >Duplicate Dataset</button></div>';
		recordsetlisthtml += '</div>';
		recordsetlisthtml += '<div><hr></div><div id="datasetlist" style="margin-top:8px;">'+recordsets+'</div>';
	}
	document.getElementById("recordsetselect").innerHTML = recordsetlisthtml;
}

function loadRecordset(dsid,role){
	clearDatasetPts();
	selectedds = dsid;
	selecteddsrole = role;
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/maprecordsetmanager.php?dsid="+dsid+"&action=loadrecords";
	
	var records = '';
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			records = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	
	document.getElementById("dsmanagementdiv").style.display = "block";
	if(role=="DatasetAdmin"){
		document.getElementById("dsdeletediv").style.display = "block";
	}
	else{
		document.getElementById("dsdeletediv").style.display = "none";
	}
	if(role=="DatasetAdmin" || role=="DatasetEditor"){
		if(document.getElementById("dsaddrecbut")){
			document.getElementById("dsaddrecbut").style.display = "block";
		}
		document.getElementById("dsdeleterecbut").style.display = "block";
	}
	else{
		if(document.getElementById("dsaddrecbut")){
			document.getElementById("dsaddrecbut").style.display = "none";
		}
		document.getElementById("dsdeleterecbut").style.display = "none";
	}
	if(records!='null'){
		var recordsArr = JSON.parse(records)
		var recAmt = recordsArr.length;
		dsmarkers = [];
		dsoccids = [];
		var tbody = ''
		var recCnt = 0;
		if(!coords){
			var selectZoomBounds = new google.maps.LatLngBounds();
		}
		for(var i in recordsArr){
			var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#000000",strokeWeight:2};
			var mar = getMarker(recordsArr[i]['lat'],recordsArr[i]['long'],"","",markerIcon,"","",recordsArr[i]['occid'],0);
			dsoms.addMarker(mar);
			mar.setMap(map);
			dsmarkers.push(mar);
			dsoccids.push(recordsArr[i]['occid']);
			if(!coords){
				selectZoomBounds.extend(new google.maps.LatLng(recordsArr[i]['lat'],recordsArr[i]['long']));
			}
			var trfragment = '';
			trfragment += '<tr id="ds'+recordsArr[i]['occid']+'" >';
			trfragment += '<td><input data-role="none" type="checkbox" class="dsocccheck" id="dsch'+recordsArr[i]['occid']+'" name="occid[]" value="'+recordsArr[i]['occid']+'" onchange="findDsSelections(this);" /></td>';
			if(recordsArr[i]['catnum']){
				trfragment += '<td>'+recordsArr[i]['catnum']+'</td>';
			}
			else{
				trfragment += '<td></td>';
			}
			trfragment += '<td><a href="#" onclick="openIndPopup('+recordsArr[i]['occid']+'); return false;">';
			trfragment += recordsArr[i]['coll']+'</a></td>';
			if(recordsArr[i]['eventdate']){
				trfragment += '<td>'+recordsArr[i]['eventdate']+'</td>';
			}
			else{
				trfragment += '<td></td>';
			}
			trfragment += '<td>'+recordsArr[i]['sciname']+'</td>';
			trfragment += '</tr>';
			tbody += trfragment;
			recCnt++;
		}
		var rccntdiv = '<b>Count: '+recCnt+' records</b>';
		document.getElementById("recordsetcntdiv").innerHTML = rccntdiv;
		document.getElementById("recordstbody").innerHTML = tbody;
		document.getElementById("recordsetlisttab").style.display = "block";
		if(!coords){
			map.fitBounds(selectZoomBounds);
			map.panToBounds(selectZoomBounds);
		}
	}
	else{
		var activeTab = $('#tabs3').tabs("option","active");
		if(activeTab==1){
			$('#tabs3').tabs({active:0});
		}
		alert("There are currently no records in the dataset you selected.");
	}
}

function clearDataset(){
	clearDatasetPts();
	selectedds = '';
	selecteddsrole = '';
	if(document.getElementById("dsmanagementdiv")){
		document.getElementById("dsmanagementdiv").style.display = "none";
	}
	if(document.getElementById("dsdeletediv")){
		document.getElementById("dsdeletediv").style.display = "none";
	}
	if(document.getElementById("dsaddrecbut")){
		document.getElementById("dsaddrecbut").style.display = "none";
	}
	if(document.getElementById("dsdeleterecbut")){
		document.getElementById("dsdeleterecbut").style.display = "none";
	}
	if(document.getElementsByName("dsid")){
		var ele = document.getElementsByName("dsid");
		for(var i=0;i<ele.length;i++){
			ele[i].checked = false;
		}
	}
}

function clearDatasetPts(){
	if(dsmarkers.length!=0){
		for(var i = 0; i < dsmarkers.length; i++){
			dsmarkers[i].setMap(null);
		}
	}
	document.getElementById("recordsetcntdiv").innerHTML = '';
	document.getElementById("recordstbody").innerHTML = '';
	document.getElementById("recordsetlisttab").style.display = "none";
}

function addSelectedToDs(){
	var selectionstoadd = [];
	for (var i=0; i < selections.length; i++) {
		//alert(selections[i]);
		if(dsoccids.indexOf(selections[i]) < 0){
			selectionstoadd.push(selections[i]);
		}
	}
	var jsonSelections = JSON.stringify(selectionstoadd);
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/maprecordsetmanager.php?dsid="+selectedds+"&action=addrecords&selections="+jsonSelections;
	
	var records = '';
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			records = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	loadRecordset(selectedds,selecteddsrole);
}

function cloneDataset(){
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/maprecordsetmanager.php?dsid="+selectedds+"&uid="+uid+"&action=clonedataset";
	
	var newDsid = '';
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			newDsid = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	clearDataset();
	loadRecordsetList(uid,newDsid);
	loadRecordset(newDsid,"DatasetAdmin");
}

function deleteDataset(){
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/maprecordsetmanager.php?dsid="+selectedds+"&action=deletedataset";
	
	var records = '';
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			records = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	clearDataset();
	selectedds = '';
	selecteddsrole = '';
	loadRecordsetList(uid,"");
}

function deleteSelectedFromDs(){
	if(dsselections.length!=0){
		var jsonSelections = JSON.stringify(dsselections);
		var sutXmlHttp=GetXmlHttpObject();
		if (sutXmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return;
		}
		
		var url="rpc/maprecordsetmanager.php?dsid="+selectedds+"&action=deleterecords&selections="+jsonSelections;
		
		var records = '';
		sutXmlHttp.onreadystatechange=function(){
			if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
				records = sutXmlHttp.responseText;
			}
		};
		sutXmlHttp.open("POST",url,false);
		sutXmlHttp.send(null);
		dsselections.length = 0;
		if(document.getElementById("dsselectallcheck").checked==true){
			document.getElementById("dsselectallcheck").checked = false;
			clearDatasetPts();
			var activeTab = $('#tabs3').tabs("option","active");
			if(activeTab==1){
				$('#tabs3').tabs({active:0});
			}
		}
		else{
			loadRecordset(selectedds,selecteddsrole);
		}
	}
	else{
		alert("Please select the records from the dataset which you would like to delete.");
		return;
	}
}

function zoomToDsSelections(){
	if(dsselections.length!=0){
		var selectZoomBounds = new google.maps.LatLngBounds();
		for (var i=0; i < dsselections.length; i++) {
			occid = Number(dsselections[i]);
			if (dsmarkers) {
				for (j in dsmarkers) {
					if(dsmarkers[j].occid==occid){
						var markerPos = dsmarkers[j].getPosition();
						selectZoomBounds.extend(markerPos);
					}
				}
			}
		}
		map.fitBounds(selectZoomBounds);
		map.panToBounds(selectZoomBounds);
	}
	else{
		alert("Please select records from the dataset in order to zoom.");
		return;
	}
}

function createDataset(uid){
	clearDataset();
	var newname = document.getElementById("newdsname").value;
	var newnotes = document.getElementById("newdsnotes").value;
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	var url="rpc/maprecordsetmanager.php?uid="+uid+"&action=createset&newname="+newname+"&newnotes"+newnotes;
	
	var newDsid = '';
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			newDsid = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	loadRecordsetList(uid,newDsid);
	loadRecordset(newDsid,"DatasetAdmin");
}

function openCsvOptions(type){
	document.getElementById("typecsv").value = type;
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
	newWindow = window.open(urlStr,'popup','scrollbars=0,toolbar=0,resizable=1,width=650,height=650');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function prepCsvControlForm(f){
	opener.document.getElementById("schemacsv").value = f.schema.value;
	if(f.identifications.checked==true){
		opener.document.getElementById("identificationscsv").value = 1;
	}
	if(f.images.checked==true){
		opener.document.getElementById("imagescsv").value = 1;
	}
	opener.document.getElementById("formatcsv").value = f.format.value;
	opener.document.getElementById("csetcsv").value = f.cset.value;
	if(f.zip.checked==true){
		opener.document.getElementById("zipcsv").value = 1;
	}
	opener.document.getElementById("csvcontrolform").submit();
	self.close();
	return false;
}