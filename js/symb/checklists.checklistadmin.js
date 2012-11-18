$(document).ready(function() {
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

function openMappingAid() {
	mapWindow=open("../tools/mappointaid.php?formname=editclmatadata&latname=ecllatcentroid&longname=ecllongcentroid","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if(mapWindow.opener == null) mapWindow.opener = self;
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
	return false;
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

