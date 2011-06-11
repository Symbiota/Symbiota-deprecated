var pauseSubmit = false;
var imgAssocCleared = false;
var voucherAssocCleared = false;
var surveyAssocCleared = false;


$(document).ready(function() {
	$("#occedittabs").tabs({
		select: function(event, ui) {
			statusObj = document.getElementById("statusdiv");
			if(statusObj){
				statusObj.style.display = "none";
			}
			return true;
		}
	});

	$("#ffsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		change: function(event, ui) {
			pauseSubmit = true;
			fieldChanged('sciname');
			verifyFullformSciName();
		}
	},
	{ minLength: 3, autoFocus: true });

	//Determination add form
	$("#dafsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php",
		change: function(event, ui) { 
			pauseSubmit = true;
			verifyDetSciName(document.detaddform);
		}
	},
	{ minLength: 3, autoFocus: true });
	
	//Determination edit form
	$("#defsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php",
		change: function(event, ui) { 
			pauseSubmit = true;
			verifyDetSciName(document.deteditform);
		}
	},
	{ minLength: 3, autoFocus: true });
	
	//Misc pulldown fields
	$("#ffcountry").autocomplete( { source: countryArr },{ minLength: 1, autoFocus: true, matchContains: false } );

	$("#ffstate").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/statesuggest.php", { term: request.term, "country": document.fullform.country.value }, response );
		}
	},{ minLength: 1, autoFocus: true, matchContains: false }
	);

	$("#ffcounty").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/countysuggest.php", { term: request.term, "state": document.fullform.stateprovince.value }, response );
		}
	},{ minLength: 1, autoFocus: true, matchContains: false }
	);
});

function verifyFullformSciName(){
	var f = document.fullform;
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
				alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus.");
				f.sciname.focus();
			}
			fieldChanged('scientificnameauthorship');
			fieldChanged('family');
			pauseSubmit = false;
		}
	};
	snXmlHttp.open("POST",url,true);
	snXmlHttp.send(null);
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

function toogleLocSecReason(f){
	var lsrObj = document.getElementById("locsecreason");
	if(f.localitysecurity.checked){
		lsrObj.style.display = "inline";
	}
	else{
		lsrObj.style.display = "none";
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

function openMappingAid(latDef,lngDef,zoom) {
    mapWindow=open("mappointaid.php?latdef="+latDef+"&lngdef="+lngDef+"&zoom="+zoom,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function dwcDoc(dcTag){
    dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=900,height=300,left=20,top=20,scrollbars=1");
    if(dwcWindow.opener == null) dwcWindow.opener = self;
    return false;
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

function catalogNumberChanged(cnValue){
	fieldChanged('catalognumber');

	if(cnValue){
		cnXmlHttp = GetXmlHttpObject();
		if(cnXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var url = "rpc/querycatalognumber.php?cn=" + cnValue + "&collid=" + collId;
		cnXmlHttp.onreadystatechange=function(){
			if(cnXmlHttp.readyState==4 && cnXmlHttp.status==200){
				var resObj = eval('(' + cnXmlHttp.responseText + ')')
				if(resObj.length > 0){
					alert("Record(s) of same catalog number already exists: " + resObj);
				}
			}
		};
		cnXmlHttp.open("POST",url,true);
		cnXmlHttp.send(null);
	}
}

function occurrenceIdChanged(oiValue){
	fieldChanged('occurrenceid');

	if(oiValue){
		oiXmlHttp = GetXmlHttpObject();
		if(oiXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var url = "rpc/queryoccurrenceid.php?oi=" + oiValue;
		oiXmlHttp.onreadystatechange=function(){
			if(oiXmlHttp.readyState==4 && oiXmlHttp.status==200){
				var resObj = eval('(' + oiXmlHttp.responseText + ')')
				if(resObj.length > 0){
					alert("Record(s) of same catalog number already exists: " + resObj);
				}
			}
		};
		oiXmlHttp.open("POST",url,true);
		oiXmlHttp.send(null);
	}
}

function lookForDups(f){
	var collName = f.recordedby.value;
	var collNum = f.recordnumber.value;
	var collDate = f.eventdate.value;
	if(!collName || !collNum){
		alert("Collector name and number must have a value to search for duplicates");
		return;
	}
	document.getElementById("dupdisplayspan").style.display = "none";
	document.getElementById("dupnonespan").style.display = "none";
	document.getElementById("dupsearchspan").style.display = "block";
	document.getElementById("dupspan").style.display = "block";

	//Parse last name of collector
	var lastName = "";
	var lastNameArr = collName.split(",");
	lastNameArr = lastNameArr[0].split(";");
	lastNameArr = lastNameArr[0].split("&");
	lastNameArr = lastNameArr[0].match(/[A-Z]{1}[A-Za-z]{2,}/g);
	if(lastNameArr.length == 1){
		lastName = lastNameArr[0];
	}
	else if(lastNameArr.length > 1){
		lastName = lastNameArr[1];
	}
	
	//Check for matching records
	dupXmlHttp = GetXmlHttpObject();
	if(dupXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url = "rpc/querydups.php?cname=" + lastName + "&cnum=" + collNum;
	if(collDate) url = url + "&cdate=" + collDate;
	dupXmlHttp.onreadystatechange=function(){
		if(dupXmlHttp.readyState==4 && dupXmlHttp.status==200){
			var resObj = eval('(' + dupXmlHttp.responseText + ')')
			if(resObj.length > 0){
				document.getElementById("dupsearchspan").style.display = "none";
				document.getElementById("dupdisplayspan").style.display = "block";
				//alert("Duplicate records have been found: " + resObj);
				dupWindow=open("dupsearch.php?occids="+resObj+"&collid="+f.collid.value,"dupaid","resizable=1,scrollbars=1,width=700,height=700,left=20,top=20");
				if(dupWindow.opener == null) dupWindow.opener = self;
				if(window.focus) {dupWindow.focus()}
				document.getElementById("dupspan").style.display = "none";
			}
			else{
				document.getElementById("dupsearchspan").style.display = "none";
				document.getElementById("dupnonespan").style.display = "block";
			}
		}
	};
	dupXmlHttp.open("POST",url,true);
	dupXmlHttp.send(null);
}

function fieldChanged(fieldName){
	document.fullform.editedfields.value = document.fullform.editedfields.value + fieldName + ";"; 
}

//Form verification code
function verifyFullForm(f){
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
	//If sciname was changed and submit was clicked immediately afterward, wait 5 seconds so that name can be verified 
	if(pauseSubmit){
		var date = new Date();
		var curDate = null;
		do{ 
			curDate = new Date(); 
		}while(curDate - date < 5000 && pauseSubmit);
	}
	return true;
}

function verifyGotoNew(f){
	if(f.editedfields.value){
		return confirm("Edits not saved. If you go to a new record you will loss your edits. Are you sure you want to continue?");
	}
	return true;
}

function verifyDeletion(f){
	var occId = f.occid.value;
	//Restriction when images are linked
	document.getElementById("delverimgspan").style.display = "block";
	verifyAssocImages(occId);
	
	//Restriction when vouchers are linked
	document.getElementById("delvervouspan").style.display = "block";
	verifyAssocVouchers(occId);
	
	//Restriction when surveys are linked
	document.getElementById("delversurspan").style.display = "block";
	verifyAssocSurveys(occId);
}

function verifyAssocImages(occid){
	var iXmlHttp = GetXmlHttpObject();
	if(iXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url = "rpc/getassocimgcnt.php?occid=" + occid;
	iXmlHttp.onreadystatechange=function(){
		if(iXmlHttp.readyState==4 && iXmlHttp.status==200){
			var imgCnt = iXmlHttp.responseText;
			document.getElementById("delverimgspan").style.display = "none";
			if(imgCnt > 0){
				document.getElementById("delimgfailspan").style.display = "block";
			}
			else{
				document.getElementById("delimgappdiv").style.display = "block";
			}
			imgAssocCleared = true;
			displayDeleteSubmit();
		}
	};
	iXmlHttp.open("POST",url,true);
	iXmlHttp.send(null);
}

function verifyAssocVouchers(occid){
	var vXmlHttp = GetXmlHttpObject();
	if(vXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url = "rpc/getassocvouchers.php?occid=" + occid;
	vXmlHttp.onreadystatechange=function(){
		if(vXmlHttp.readyState==4 && vXmlHttp.status==200){
			var vList = eval("("+vXmlHttp.responseText+")");;
			document.getElementById("delvervouspan").style.display = "none";
			if(vList != ''){
				document.getElementById("delvoulistdiv").style.display = "block";
				var strOut = "";
				for(var key in vList){
					strOut = strOut + "<li><a href='../../checklists/checklist.php?cl="+key+"' target='_blank'>"+vList[key]+"</a></li>";
				}
				document.getElementById("voucherlist").innerHTML = strOut;
			}
			else{
				document.getElementById("delvouappdiv").style.display = "block";
			}
			voucherAssocCleared = true;
			displayDeleteSubmit();
		}
	};
	vXmlHttp.open("POST",url,true);
	vXmlHttp.send(null);
}

function verifyAssocSurveys(occid){
	var sXmlHttp = GetXmlHttpObject();
	if(sXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url = "rpc/getassocsurveys.php?occid=" + occid;
	sXmlHttp.onreadystatechange=function(){
		if(sXmlHttp.readyState==4 && sXmlHttp.status==200){
			var sList = eval("("+sXmlHttp.responseText+")");;
			document.getElementById("delversurspan").style.display = "none";
			if(sList != ''){
				document.getElementById("delsurlistdiv").style.display = "block";
				var strOut = "";
				for(var key in sList){
					strOut = strOut + "<li><a href='../../checklists/survey.php?surveyid="+key+"' target='_blank'>"+sList[key]+"</a></li>";
				}
				document.getElementById("surveylist").innerHTML = strOut;
			}
			else{
				document.getElementById("delsurappdiv").style.display = "block";
			}
			surveyAssocCleared = true;
			displayDeleteSubmit();
		}
	};
	sXmlHttp.open("POST",url,true);
	sXmlHttp.send(null);
}

function displayDeleteSubmit(){
	if(imgAssocCleared && voucherAssocCleared && surveyAssocCleared){
		var elem = document.getElementById("delapprovediv");
		elem.style.display = "block";
	}
}

//Occurrence field checks
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

//Determination form methods 
function verifyDetSciName(f){
	var sciNameStr = f.sciname.value;
	snXmlHttp = GetXmlHttpObject();
	if(snXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url = "rpc/verifysciname.php?sciname=" + sciNameStr;
	snXmlHttp.onreadystatechange=function(){
		if(snXmlHttp.readyState==4 && snXmlHttp.status==200){
			if(snXmlHttp.responseText){
				var retObj = eval("("+snXmlHttp.responseText+")");
				f.scientificnameauthorship.value = retObj.author;
				f.tidtoadd.value = retObj.tid;
			}
			else{
				f.scientificnameauthorship.value = "";
				alert("WARNING: Taxon not found, perhaps misspelled or not in the taxonomic thesaurus? This is only a problem if this is the current determination or images need to be remapped to this name.");
				f.sciname.focus();
			}
			pauseSubmit = false;
		}
	};
	snXmlHttp.open("POST",url,true);
	snXmlHttp.send(null);
} 

function detDateChanged(f){
	var isNew = false;
	var newDateStr = f.dateidentified.value;
	if(newDateStr){
		dateIdentified = document.fullform.dateidentified.value;
		if(dateIdentified){
			var yearPattern = /[1,2]{1}\d{3}/;
			var newYear = newDateStr.match(yearPattern);
			var curYear = dateIdentified.match(yearPattern);
			if(newYear[0] > curYear[0]){
				isNew = true;
			}
		}
		else{
			isNew = true;
		}
	}
	f.makecurrent.checked = isNew;
	f.remapimages.checked = isNew;
}

function verifyDetAddForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value");
		return false;
	}
	if(f.identifiedby.value == ""){
		alert("Determiner field must have a value");
		return false;
	}
	if(f.dateidentified.value == ""){
		alert("Determination Date field must have a value");
		return false;
	}
	if(!isNumeric(f.sortsequence.value)){
		alert("Sort Sequence field must be a numeric value only");
		return false;
	}
	//If sciname was changed and submit was clicked immediately afterward, wait 5 seconds so that name can be verified 
	if(pauseSubmit){
		var date = new Date();
		var curDate = null;
		do{ 
			curDate = new Date(); 
		}while(curDate - date < 5000 && pauseSubmit);
	}
	return true;
}

function verifyDetEditForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value");
		return false;
	}
	if(f.identifiedby.value == ""){
		alert("Determiner field must have a value");
		return false;
	}
	if(f.dateidentified.value == ""){
		alert("Determination Date field must have a value");
		return false;
	}
	if(!isNumeric(f.sortsequence.value)){
		alert("Sort Sequence field must be a numeric value only");
		return false;
	}
	//If sciname was changed and submit was clicked immediately afterward, wait 5 seconds so that name can be verified 
	if(pauseSubmit){
		var date = new Date();
		var curDate = null;
		do{ 
			curDate = new Date(); 
		}while(curDate - date < 5000 && pauseSubmit);
	}
	return true;
}

//Image form methods 
function verifyImgAddForm(f){
    if(f.elements["imgfile"].value.replace(/\s/g, "") == "" ){
        if(f.elements["imgurl"].value.replace(/\s/g, "") == ""){
        	window.alert("Select an image file or enter a URL to an existing image");
			return false;
        }
    }
    return true;
}

function verifyImgEditForm(f){
	if(f.url.value == ""){
		alert("Web URL field must have a value");
		return false;
	}
	return true;
}

function verifyImgDelForm(f){
	if(confirm('Are you sure you want to delete this image? Note that the physical image will be deleted from the server if checkbox is selected.')){
		return true;
	}
	return false;
}

function openOccurrenceSearch(target) {
	collId = document.fullform.collid.value;
	occWindow=open("imgremapaid.php?targetid="+target+"&collid="+collId,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=750,height=600,left=20,top=20");
	if (occWindow.opener == null) occWindow.opener = self;
}

//Misc
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
