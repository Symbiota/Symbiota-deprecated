$(document).ready(function() {
	//Filter autocomplete
	$("#taxonfilter").autocomplete({ source: taxonArr }, { delay: 0, minLength: 2 });

	//Species add form
	$("#speciestoadd").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/speciessuggest.php", { term: request.term, cl: clid }, response );
		}
	},{ minLength: 4, delay: 400, autoFocus: true }
	);

	$('#tabs').tabs(
		{ selected: tabIndex }
	);

});

function toggle(target){
	var objDiv = document.getElementById(target);
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
	return false;
}

function openMappingAid(targetForm,targetLat,targetLong) {
    mapWindow=open("../tools/mappointaid.php?formname="+targetForm+"&latname="+targetLat+"&longname="+targetLong,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function openPopup(urlStr,windowName){
	var wWidth = 900;
	if(document.getElementById('maintable').offsetWidth){
		wWidth = document.getElementById('maintable').offsetWidth*1.05;
	}
	else if(document.body.offsetWidth){
		wWidth = document.body.offsetWidth*0.9;
	}
	newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
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

function validateSqlFragForm(f){
	if(!isNumeric(f.latnorth.value) || !isNumeric(f.latsouth.value) || !isNumeric(f.lngwest.value) || !isNumeric(f.lngeast.value)){
		alert("Latitude and longitudes values muct be numeric values only");
		return false;
	}
	if(confirm("If an SQL fragment already exists, you will replace it with the new one. Are you sure you want to continue?")){
		return true;
	}
	return false;
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
