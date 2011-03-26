$(document).ready(function() {
	//Filter autocomplete
	$("#taxonfilter").autocomplete({ source: taxonArr }, { delay: 0, minLength: 2 });

	//Species add form
	$("#speciestoadd").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/speciessuggest.php", { term: request.term, cl: clid }, response );
		}
	},{ minLength: 3, autoFocus: true }
	);

	$('#tabs').tabs();

});

function toggle(target){
	var objDiv = document.getElementById("gamediv");
	if(objDiv){
		if(objDiv.style.display=="none"){
			objDiv.style.display = "block";
		}
		else{
			objDiv.style.display = "none";
		}
	}
	else{
	  	var divs = document.getElementsByTagName("div");
	  	for (var h = 0; h < divs.length; h++) {
	  	var divObj = divs[h];
			if(divObj.className == target){
				if(divObj.style.display=="none"){
					divObj.style.display="block";
				}
			 	else {
			 		divObj.style.display="none";
			 	}
			}
		}
	
	  	var spans = document.getElementsByTagName("span");
	  	for (var i = 0; i < spans.length; i++) {
	  	var spanObj = spans[i];
			if(spanObj.className == target){
				if(spanObj.style.display=="none"){
					spanObj.style.display="inline";
				}
			 	else {
			 		spanObj.style.display="none";
			 	}
			}
		}
	}
}

function openMappingAid(targetForm,targetLat,targetLong) {
    mapWindow=open("../tools/mappointaid.php?formname="+targetForm+"&latname="+targetLat+"&longname="+targetLong,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function openPopup(urlStr,windowName){
	newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=1,resizable=1,width=950,height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
}

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

function showImagesChecked(cbObj){
	if(cbObj.checked){
		document.getElementById("showvouchers").checked = false;
		document.getElementById("showvouchersdiv").style.display = "none"; 
	}
	else{
		document.getElementById("showvouchersdiv").style.display = "block"; 
	}
}

function validateMetadataForm(f){ 
	if(f.ecllatcentroid.value == "" && f.ecllongcentroid.value == ""){
		return true;
	}
	if(f.ecllatcentroid.value == ""){
		alert("If longitude has a value, latitude must also have a value");
		return false;
	} 
	if(f.ecllongcentroid.value == ""){
		alert("If latitude has a value, longitude must also have a value");
		return false;
	} 
	if(!isNumeric(f.ecllatcentroid.value)){
		alert("Latitude must be strictly numeric (decimal format: e.g. 34.2343)");
		return false;
	}
	if(Math.abs(f.ecllatcentroid.value) > 90){
		alert("Latitude values can not be greater than 90 or less than -90.");
		return false;
	} 
	if(!isNumeric(f.ecllongcentroid.value)){
		alert("Longitude must be strictly numeric (decimal format: e.g. -112.2343)");
		return false;
	}
	if(Math.abs(f.ecllongcentroid.value) > 180){
		alert("Longitude values can not be greater than 180 or less than -180.");
		return false;
	}
	if(f.ecllongcentroid.value > 1){
		alert("Is this checklist in the western hemisphere?\nIf so, decimal longitude should be a negative value (e.g. -112.2343)");
	} 
	if(!isNumeric(f.eclpointradiusmeters.value)){
		alert("Point radius must be a numeric value only");
		return false;
	}
	return true;
}

function isNumeric(sText){
   	var ValidChars = "0123456789-.";
   	var IsNumber = true;
   	var Char;
 
   	for (var i = 0; i < sText.length && IsNumber == true; i++){ 
	   Char = sText.charAt(i); 
		if (ValidChars.indexOf(Char) == -1){
			IsNumber = false;
			break;
      	}
   	}
	return IsNumber;
}

function validateAddSpecies(f){ 
	var sciName = f.speciestoadd.value;
	if(sciName == ""){
		alert("Enter the scientific name of species you wish to add");
		return false;
	}
	else{
		cseXmlHttp=GetXmlHttpObject();
		if (cseXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return false;
	  	}
		var url="rpc/gettid.php";
		url=url+"?sciname="+sciName;
		url=url+"&sid="+Math.random();
		cseXmlHttp.onreadystatechange=function(){
			if(cseXmlHttp.readyState==4 && cseXmlHttp.status==200){
				testTid = cseXmlHttp.responseText;
				if(testTid == ""){
					alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, contact your data adminitrator to add this species to the Taxonomic Thesaurus.");
				}
				else{
					f.tidtoadd.value = testTid;
					f.submit();
				}
			}
		};
		cseXmlHttp.open("POST",url,true);
		cseXmlHttp.send(null);
		return false;
	}
}

function updateSql(){
	country = document.getElementById("countryinput").value;
	state = document.getElementById("stateinput").value;
	county = document.getElementById("countyinput").value;
	locality = document.getElementById("localityinput").value;
	latNorth = document.getElementById("latnorthinput").value;
	lngWest = document.getElementById("lngwestinput").value;
	lngEast = document.getElementById("lngeastinput").value;
	latSouth = document.getElementById("latsouthinput").value;
	sqlFragStr = "";
	if(country){
		sqlFragStr = "AND (o.country = \"" + country + "\") ";
	}
	if(state){
		sqlFragStr = sqlFragStr + "AND (o.stateprovince = \"" + state + "\") ";
	}
	if(county){
		sqlFragStr = sqlFragStr + "AND (o.county LIKE \"%" + county + "%\") ";
	}
	if(locality){
		sqlFragStr = sqlFragStr + "AND (o.locality LIKE \"%" + locality + "%\"') ";
	}
	if(latNorth && latSouth){
		sqlFragStr = sqlFragStr + "AND (o.decimallatitude BETWEEN " + latSouth + " AND " + latNorth + ") ";
	}
	if(lngWest && lngEast){
		sqlFragStr = sqlFragStr + "AND (o.decimallongitude BETWEEN " + lngWest + " AND " + lngEast + ") ";
	}
	document.getElementById("sqlfrag").value = sqlFragStr.substring(4);
}

function buildSql(){
	updateSql();
	return false;
}

function testSql(){
	tsXmlHttp=GetXmlHttpObject();
	if (tsXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	sqlValue = document.getElementById("sqlfrag").value;
	var url="rpc/testsql.php?clid=" + clid + "&sql="+sqlValue;
	tsXmlHttp.onreadystatechange=function(){
		if(tsXmlHttp.readyState==4 && tsXmlHttp.status==200){
			if(tsXmlHttp.responseText == "1"){
				alert("SUCCESS: SQL frament good to go");
			}
			else{
				alert("ERROR: SQL fragment failed");
			}
		}
	};
	tsXmlHttp.open("POST",url,true);
	tsXmlHttp.send(null);
}

Array.prototype.unique = function() {
	var a = [];
	var l = this.length;
    for(var i=0; i<l; i++) {
		for(var j=i+1; j<l; j++) {
		if (this[i] === this[j]) j = ++i;
	}
	a.push(this[i]);
	}
	return a;
};

//Game menu 
var timeout	= 500;
var closetimer	= 0;
var ddmenuitem	= 0;

// open hidden layer
function mopen(id)
{	
	// cancel close timer
	mcancelclosetime();

	// close old layer
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';

	// get new layer and show it
	ddmenuitem = document.getElementById(id);
	ddmenuitem.style.visibility = 'visible';

}
// close showed layer
function mclose()
{
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
}

// go close timer
function mclosetime()
{
	closetimer = window.setTimeout(mclose, timeout);
}

// cancel close timer
function mcancelclosetime()
{
	if(closetimer)
	{
		window.clearTimeout(closetimer);
		closetimer = null;
	}
}

// close layer when click-out
document.onclick = mclose; 
