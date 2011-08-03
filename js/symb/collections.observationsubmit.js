$(document).ready(function() {
	$("#sciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php",
		change: function(event, ui) { 
			verifySciName();
		}
	},{ minLength: 3, autoFocus: true });
});

function verifySciName(){
	var sciNameStr = document.obsform.sciname.value;
	if(sciNameStr){
		snXmlHttp = GetXmlHttpObject();
		if(snXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var url = "rpc/verifysciname.php";
		url=url + "?sciname=" + sciNameStr;
		snXmlHttp.onreadystatechange=function(){
			if(snXmlHttp.readyState==4 && snXmlHttp.status==200){
				if(snXmlHttp.responseText){
					var retObj = eval("("+snXmlHttp.responseText+")");
					document.obsform.scientificnameauthorship.value = retObj.author;
					document.obsform.family.value = retObj.family;
				}
				else{
					document.obsform.scientificnameauthorship.value = "";
					document.obsform.family.value = "";
					alert("Taxon not found. Maybe misspelled or needs to be added to taxonomic thesaurus.");
					snXmlHttp = null;
				}
			}
		};
		snXmlHttp.open("POST",url,true);
		snXmlHttp.send(null);
	}
	else{
		document.obsform.scientificnameauthorship.value = "";
		document.obsform.family.value = "";
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

function insertLatLng(f) {
	var latDeg = document.getElementById("latdeg").value.replace(/^\s+|\s+$/g,"");
	var latMin = document.getElementById("latmin").value.replace(/^\s+|\s+$/g,"");
	var latSec = document.getElementById("latsec").value.replace(/^\s+|\s+$/g,"");
	var latNS = document.getElementById("latns").value;
	var lngDeg = document.getElementById("lngdeg").value.replace(/^\s+|\s+$/g,"");
	var lngMin = document.getElementById("lngmin").value.replace(/^\s+|\s+$/g,"");
	var lngSec = document.getElementById("lngsec").value.replace(/^\s+|\s+$/g,"");
	var lngEW = document.getElementById("lngew").value;
	if(latDeg && latMin && lngDeg && lngMin){
		if(latMin == "") latMin = 0;
		if(latSec == "") latSec = 0;
		if(lngMin == "") lngMin = 0;
		if(lngSec == "") lngSec = 0;
		if(isNumeric(latDeg) && isNumeric(latMin) && isNumeric(latSec) && isNumeric(lngDeg) && isNumeric(lngMin) && isNumeric(lngSec)){
			if(latDeg < 0 || latDeg > 90){
				alert("Latitude degree must be between 0 and 90 degrees");
			}
			else if(lngDeg < 0 || lngDeg > 180){
				alert("Longitude degree must be between 0 and 180 degrees");
			}
			else if(latMin < 0 || latMin > 60 || lngMin < 0 || lngMin > 60 || latSec < 0 || latSec > 60 || lngSec < 0 || lngSec > 60){
				alert("Minute and second values can only be between 0 and 60");
			}
			else{
				var latDec = parseInt(latDeg) + (parseFloat(latMin)/60) + (parseFloat(latSec)/3600);
				var lngDec = parseInt(lngDeg) + (parseFloat(lngMin)/60) + (parseFloat(lngSec)/3600);
				if(latNS == "S") latDec = latDec * -1; 
				if(lngEW == "W") lngDec = lngDec * -1; 
				f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
				f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
			}
		}
		else{
			alert("Field values must be numeric only");
		}
	}
	else{
		alert("DMS fields must contain a value");
	}
}

function insertElevFt(f){
	var elev = document.getElementById("elevft").value;
	f.minimumelevationinmeters.value = Math.round(elev*.03048)*10;
}

function submitObsForm(f){
    if(f.sciname.value == ""){
		window.alert("Observation must have an identification (scientific name) assigned to it, even if it is only to family rank.");
		return false;
    }
    if(f.recordedby.value == ""){
		window.alert("Observer field must have a value.");
		return false;
    }
    if(f.eventdate.value == ""){
		window.alert("Observation date must have a value.");
		return false;
    }
    if(f.locality.value == ""){
		window.alert("Locality must have a value to submit an observation.");
		return false;
    }
    if(f.decimallatitude.value == "" || f.decimallongitude.value == ""){
		window.alert("Latitude and Longitude must have a value to submit an observation. Note that one can submit an image without a locality definition through the Taxon Profile page.");
		return false;
    }
    if(isNumeric(f.decimallatitude.value) == false){
		window.alert("Latitude must be in the decimal format with numeric characters only (34.5335). ");
		return false;
    }
    if(isNumeric(f.decimallongitude.value) == false){
		window.alert("Longitude must be in the decimal format with numeric characters only. Note that the western hemisphere is represented as a negitive number (-110.5335). ");
		return false;
    }
    if(parseInt(f.decimallongitude.value ) > 0 && (f.stateprovince == 'USA' || f.stateprovince == 'Canada' || f.stateprovince == 'Mexico')){
		window.alert("For North America, the decimal format of longitude should be negitive value. ");
		return false;
    }
    if(isNumeric(f.coordinateuncertaintyinmeters.value) == false){
		window.alert("Coordinate Uncertainty must be a numeric value only. ");
		return false;
    }
    if(isNumeric(f.minimumelevationinmeters.value) == false){
		window.alert("Elevation must be a numeric value only. ");
		return false;
    }
    if(f.imgfile1.value == ""){
   		window.alert("An observation submitted through this interface must be documented with an image.");
		return false;
    }
    return true;
}

function verifyDate(eventDateInput){
	var dateStr = eventDateInput.value;
	//test date and return mysqlformat

	
}

function openMappingAid(targetForm,targetLat,targetLong) {
    mapWindow=open("../../tools/mappointaid.php?formname="+targetForm+"&latname="+targetLat+"&longname="+targetLong,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
    if(document.obsform.geodeticdatum.value == "") document.obsform.geodeticdatum.value = "WGS84"; 
}

function verifyLatValue(inputObj){
	inputIsNumeric(inputObj, 'Decimal Latitude');
	if(inputObj.value > 90 || inputObj.value < -90){
		alert('Decimal latitude value should be between -90 and 90 degrees');
	}
}

function verifyLngValue(inputObj){
	inputIsNumeric(inputObj, 'Decimal Longitude');
	if(inputObj.value > 180 || inputObj.value < -180){
		alert('Decimal longitude value should be between -180 and 180 degrees');
	}
}

function verifyElevValue(inputObj){
	inputIsNumeric(inputObj, 'Coordinate Uncertainty');
	if(inputObj.value > 4000){
		alert('Are you sure your elevation value in meters. ' + inputObj.value + ' meters is a very high elevation.');
	}
}

function inputIsNumeric(inputObj, titleStr){
	if(!isNumeric(inputObj.value)){
		alert("Input value for " + titleStr + " must be a number value only! " );
	}
}

function isNumeric(sText){
   	var ValidChars = "0123456789-.";
   	var IsNumber = true;
   	var Char;
 
   	for(var i = 0; i < sText.length && IsNumber == true; i++){ 
	   Char = sText.charAt(i); 
		if(ValidChars.indexOf(Char) == -1){
			IsNumber = false;
			break;
      	}
   	}
	return IsNumber;
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
