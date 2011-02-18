function initTabs(tabObjId){
	var dTabs=new ddtabcontent(tabObjId); 
	dTabs.setpersist(true);
	dTabs.setselectedClassTarget("link"); 
	dTabs.init();
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

function toggleCoordDiv(){
	coordObj = document.getElementById("coordaiddiv");
	if(coordObj.style.display == "none"){
		document.getElementById("locextradiv1").style.display = "block";
		document.getElementById("locextradiv2").style.display = "block";
		coordObj.style.display = "block";
	}
	else{
		coordObj.style.display = "none";
	}
}

function toggleIdDetails(){
	toggle("idrefdiv");
	toggle("idremdiv");
}

function openMappingAid(targetForm,targetLat,targetLong,latDef,lngDef,zoom) {
    mapWindow=open("../../tools/mappointaid.php?formname="+targetForm+"&latname="+targetLat+"&longname="+targetLong+"&latdef="+latDef+"&lngdef="+lngDef+"&zoom="+zoom,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function insertUtm(f) {
	var zValue = document.getElementById("utmzone").value.replace(/^\s+|\s+$/g,"");
	var eValue = document.getElementById("utmeast").value.replace(/^\s+|\s+$/g,"");
	var nValue = document.getElementById("utmnorth").value.replace(/^\s+|\s+$/g,"");
	if(zValue && eValue && nValue){
		if(isNumeric(zValue) && isNumeric(eValue) && isNumeric(nValue)){
			//Remove prior UTM references from verbatimCoordinates field
			var vcStr = f.verbatimcoordinates.value;
			vcStr = vcStr.replace(/\(UTM: \d+ \d+E \d+N\)[;\s]*/g, "");
			vcStr = vcStr.replace(/^\s+|\s+$/g, "");
			vcStr = vcStr.replace(/^;|;$/g, "");
			//put UTM into verbatimCoordinate field
			if(vcStr != ""){
				vcStr = vcStr + "; ";
			}
			var utmStr = "(UTM: " + zValue + " " + eValue + "E " + nValue + "N)";
			f.verbatimcoordinates.value = vcStr + utmStr;
			//Convert to Lat/Lng values
			var latLngStr = utm2LatLng(zValue,eValue,nValue);
			var llArr = latLngStr.split(',');
			if(llArr){
				f.decimallatitude.value = Math.round(llArr[0]*1000000)/1000000;
				f.decimallongitude.value = Math.round(llArr[1]*1000000)/1000000;
			}
		}
		else{
			alert("UTM fields must contain numeric values only");
		}
	}
	else{
		alert("Zone, Easting, and Northing fields must not be empty");
	}
}

function utm2LatLng(zValue, eValue, nValue){
	var d = 0.99960000000000004; // scale along long0
	var d1 = 6378137; // Polar Radius
	var d2 = 0.0066943799999999998;

	var d4 = (1 - Math.sqrt(1 - d2)) / (1 + Math.sqrt(1 - d2));
	var d15 = eValue - 500000;
	var d16 = nValue;
	var d11 = ((zValue - 1) * 6 - 180) + 3;
	var d3 = d2 / (1 - d2);
	var d10 = d16 / d;
	var d12 = d10 / (d1 * (1 - d2 / 4 - (3 * d2 * d2) / 64 - (5 * Math.pow(d2,3) ) / 256));
	var d14 = d12 + ((3 * d4) / 2 - (27 * Math.pow(d4,3) ) / 32) * Math.sin(2 * d12) + ((21 * d4 * d4) / 16 - (55 * Math.pow(d4,4) ) / 32) * Math.sin(4 * d12) + ((151 * Math.pow(d4,3) ) / 96) * Math.sin(6 * d12);
	var d13 = (d14 / Math.PI) * 180;
	var d5 = d1 / Math.sqrt(1 - d2 * Math.sin(d14) * Math.sin(d14));
	var d6 = Math.tan(d14) * Math.tan(d14);
	var d7 = d3 * Math.cos(d14) * Math.cos(d14);
	var d8 = (d1 * (1 - d2)) / Math.pow(1 - d2 * Math.sin(d14) * Math.sin(d14), 1.5);
	var d9 = d15 / (d5 * d);
	var d17 = d14 - ((d5 * Math.tan(d14)) / d8) * (((d9 * d9) / 2 - (((5 + 3 * d6 + 10 * d7) - 4 * d7 * d7 - 9 * d3) * Math.pow(d9,4) ) / 24) + (((61 + 90 * d6 + 298 * d7 + 45 * d6 * d6) - 252 * d3 - 3 * d7 * d7) * Math.pow(d9,6) ) / 720);
	var latValue = (d17 / Math.PI) * 180; // Breddegrad (N)
	var d18 = ((d9 - ((1 + 2 * d6 + d7) * Math.pow(d9,3) ) / 6) + (((((5 - 2 * d7) + 28 * d6) - 3 * d7 * d7) + 8 * d3 + 24 * d6 * d6) * Math.pow(d9,5) ) / 120) / Math.cos(d14);
	var lngValue = d11 + ((d18 / Math.PI) * 180); // Længdegrad (Ø)
	return latValue + "," + lngValue;

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
				var vcStr = f.verbatimcoordinates.value;
				vcStr = vcStr.replace(/\(Lat: [-dmsNSLong:;EW\d\.\s]+\)/g, "");
				vcStr = vcStr.replace(/^\s+|\s+$/g, "");
				vcStr = vcStr.replace(/^;|;$/g, "");
				if(vcStr != ""){
					vcStr = vcStr + "; ";
				}
				var dmsStr = "(Lat: " + latDeg + "d " + latMin + "m ";
				if(latSec > 0) dmsStr += latSec + "s ";
				dmsStr += latNS + "; Long: " + lngDeg + "d " + lngMin + "m ";
				if(lngSec) dmsStr += lngSec + "s ";
				dmsStr += lngEW + ")";
				f.verbatimcoordinates.value = vcStr + dmsStr;
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
	var elevMin = document.getElementById("elevminft").value;
	var elevMax = document.getElementById("elevmaxft").value;
	f.minimumelevationinmeters.value = Math.round(elevMin*.03048)*10;
	f.maximumelevationinmeters.value = Math.round(elevMax*.03048)*10;
	verbStr = elevMin;
	if(elevMax) verbStr += " - " + elevMax;
	verbStr += "ft";
	f.verbatimelevation.value = verbStr;
}

function fieldChanged(fieldName){
	document.fullform.editedfields.value = document.fullform.editedfields.value + fieldName + ";"; 
}

//Form verification code
function submitFullForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value. Enter closest know identification, even if it's only to family, order, or above. ");
		return false;
	}
	if(f.recordedby.value == ""){
		alert("Collector field must have a value. Enter 'unknown' if needed.");
		return false;
	}
	if(!verifyDate(f.eventdate)){
		return false;
	}
	if(f.country.value == ""){
		alert("Country field must have a value");
		return false;
	}
	if(f.stateprovince.value == ""){
		alert("State field must have a value");
		return false;
	}
	if(f.locality.value == ""){
		alert("Locality field must have a value");
		return false;
	}

	return true;
}

function submitDetEditForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value");
		return false;
	}
	if(f.identifiedby.value == ""){
		alert("Determiner field must have a value");
		return false;
	}
	if(f.dateidentified.value == ""){
		alert("Determination Data field must have a value");
		return false;
	}
	if(!isNumeric(f.sortsequence.value)){
		alert("Sort Sequence field must be a numeric value only");
		return false;
	}
	return true;
}

function submitImgAddForm(f){
    if(f.elements["imgfile"].value.replace(/\s/g, "") == "" ){
        if(f.elements["imgurl"].value.replace(/\s/g, "") == ""){
        	window.alert("Select an image file or enter a URL to an existing image");
			return false;
        }
    }
    return true;
}

function submitImgEditForm(f){
	if(f.url.value == ""){
		alert("Web URL field must have a value");
		return false;
	}
	return true;
}

function submitImgDelForm(f){
	if(confirm('Are you sure you want to delete this image? Note that the physical image will be deleted from the server if checkbox is selected.')){
		return true;
	}
	return false;
}

//Occurrence field checks
function verifySciName(f){
	if(f.scientificnameauthorship.value == ""){
		var sciNameStr = f.sciname.value;
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
					f.scientificnameauthorship.value = retObj.author;
					f.family.value = retObj.family;
				}
				else{
					f.scientificnameauthorship.value = "";
					f.family.value = "";
					f.identifiedby.focus();
					alert("Taxon not found. Maybe misspelled or needs to be added to taxonomic thesaurus.");
				}
				fieldChanged('scientificnameauthorship');
				fieldChanged('family');
			}
		};
		snXmlHttp.open("POST",url,true);
		snXmlHttp.send(null);
	}
} 

function scinameChanged(){
	document.fullform.scientificnameauthorship.value = "";
	document.fullform.family.value = "";
	fieldChanged('sciname');
	fieldChanged('scientificnameauthorship');
	fieldChanged('family');
}

function verifyDate(eventDateInput){
	var dateStr = eventDateInput.value;
	if(dateStr == "") return true;
	//test date and return mysqlformat
	var validformat=/^\d{4}-\d{2}-\d{2}$/ //Format: yyyy-mm-dd
	if(!validformat.test(dateStr)){
		alert("Invalid Date Format. Please correct to follow this format: yyyy-mm-dd");
		return false;
	}
	return true;
}

function inputIsNumeric(inputObj, titleStr){
	if(!isNumeric(inputObj.value)){
		alert("Input value for " + titleStr + " must be a number value only! " );
	}
}

function isNumeric(sText){
   	var validChars = "0123456789-.";
   	var isNumber = true;
   	var charVar;

   	for(var i = 0; i < sText.length && isNumber == true; i++){ 
   		charVar = sText.charAt(i); 
		if(validChars.indexOf(charVar) == -1){
			isNumber = false;
			break;
      	}
   	}
	return isNumber;
}


//Misc
function initTaxonList(input){
	$(input).autocomplete({ ajax_get:getTaxonSuggs, minchars:3 });
}

function initDetTaxonList(input){
	$(input).autocomplete({ ajax_get:getTaxonSuggs, minchars:3 });
}

function getTaxonSuggs(key,cont){ 
   	var script_name = 'rpc/getspecies.php';
   	var params = { 'q':key }
   	$.get(script_name,params,
		function(obj){
			// obj is just array of strings
			var res = [];
			for(var i=0;i<obj.length;i++){
				res.push({ id:i , value:obj[i]});
			}
			// will build suggestions list
			cont(res);
		},
	'json');
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
