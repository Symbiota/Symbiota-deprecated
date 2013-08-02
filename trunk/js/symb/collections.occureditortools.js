function openAssocSppAid(){
	var assocWindow = open("assocsppaid.php","assocaid","resizable=0,width=550,height=200,left=20,top=20");
	if(assocWindow != null){
		if (assocWindow.opener == null) assocWindow.opener = self;
		fieldChanged("associatedtaxa");
	}
	else{
		alert("Unable to open associated species tool, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
}

function toggleCoordDiv(){
	coordObj = document.getElementById("coordAidDiv");
	if(coordObj.style.display == "block"){
		coordObj.style.display = "none";
	}
	else{
		document.getElementById("locExtraDiv").style.display = "block";
		coordObj.style.display = "block";
	}
}

function toggleCsMode(modeId){
	if(modeId == 1){
		document.getElementById("editorCssLink").href = "includes/config/occureditorcrowdsource.css";
		document.getElementById("longtagspan").style.display = "block";
		document.getElementById("shorttagspan").style.display = "none";
	}
	else{
		document.getElementById("editorCssLink").href = "../../css/occureditor.css";
		document.getElementById("longtagspan").style.display = "none";
		document.getElementById("shorttagspan").style.display = "block";
	}
}

function openMappingAid() {
	var f = document.fullform;
	var latDef = f.decimallatitude.value;
	var lngDef = f.decimallongitude.value;
	var zoom = 5;
	if(latDef && lngDef) zoom = 11;
	var mapWindow=open("mappointaid.php?latdef="+latDef+"&lngdef="+lngDef+"&zoom="+zoom,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
	if(mapWindow != null){
		if (mapWindow.opener == null) mapWindow.opener = self;
	}
	else{
		alert("Unable to open map, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
}

function geoLocateLocality(){
	var f = document.fullform;
	var country = f.country.value;
	var state = f.stateprovince.value;
	if(!state) state = "unknown";
	var county = f.county.value;
	if(!county) county = "unknown";
	var locality = f.locality.value;
	if(f.verbatimcoordinates.value) locality = locality + "; " + f.verbatimcoordinates.value;
	
	if(!country){
		alert("Country is blank and it is a required field for GeoLocate");
	}
	else if(!locality){
		alert("Locality is blank and it is a required field for GeoLocate");
	}
	else{
		geolocWindow=open("../georef/geolocate.php?country="+country+"&state="+state+"&county="+county+"&locality="+locality,"geoloctool","resizable=1,scrollbars=1,toolbar=1,width=1050,height=700,left=20,top=20");
		if(geolocWindow.opener == null) geolocWindow.opener = self;
	}
}

function geoLocateUpdateCoord(latValue,lngValue,coordErrValue){
	document.getElementById("locExtraDiv").style.display = "block";

	var f = document.fullform;
	f.decimallatitude.value = latValue;
	f.decimallatitude.onchange();
	f.decimallongitude.value = lngValue;
	f.decimallongitude.onchange();
	f.coordinateuncertaintyinmeters.value = coordErrValue;
	f.coordinateuncertaintyinmeters.onchange();
	f.georeferencesources.value = "GeoLocate";
	f.georeferencesources.onchange();
	f.geodeticdatum.value = "WGS84";
	f.geodeticdatum.onchange();
	//f.georeferenceverificationstatus.value = "reviewed - high confidence";
	//f.georeferenceverificationstatus.onchange();
}

function insertUtm(f) {
	var zValue = document.getElementById("utmzone").value.replace(/^\s+|\s+$/g,"");
	var hValue = document.getElementById("hemisphere").value;
	var eValue = document.getElementById("utmeast").value.replace(/^\s+|\s+$/g,"");
	var nValue = document.getElementById("utmnorth").value.replace(/^\s+|\s+$/g,"");
	if(zValue && eValue && nValue){
		if(isNumeric(eValue) && isNumeric(nValue)){
			//Remove prior UTM references from verbatimCoordinates field
			var vcStr = f.verbatimcoordinates.value;
			vcStr = vcStr.replace(/\d{2}.*\d+E\s+\d+N[;\s]*/g, "");
			vcStr = vcStr.replace(/(Northern)|(Southern)/g, "");
			vcStr = vcStr.replace(/^\s+|\s+$/g, "");
			vcStr = vcStr.replace(/^;|;$/g, "");
			//put UTM into verbatimCoordinate field
			if(vcStr != ""){
				vcStr = vcStr + "; ";
			}
			var utmStr = zValue + " " + eValue + "E " + nValue + "N ";
			f.verbatimcoordinates.value = vcStr + utmStr;
			//Convert to Lat/Lng values
			var zNum = parseInt(zValue);
			if(isNumeric(zNum)){
				var latLngStr = utm2LatLng(zNum,eValue,nValue,f.geodeticdatum.value);
				var llArr = latLngStr.split(',');
				if(llArr){
					var latFact = 1;
					if(hValue == "Southern") latFact = -1;
					f.decimallatitude.value = latFact*Math.round(llArr[0]*1000000)/1000000;
					f.decimallongitude.value = Math.round(llArr[1]*1000000)/1000000;
				}
			}
			fieldChanged("decimallatitude");
			fieldChanged("decimallongitude");
			fieldChanged("verbatimcoordinates");
		}
		else{
			alert("Easting and northing fields must contain numeric values only");
		}
	}
	else{
		alert("Zone, Easting, and Northing fields must not be empty");
	}
}

function utm2LatLng(zValue, eValue, nValue, datum){
	//Datum assumed to be  or WGS84
	var d = 0.99960000000000004; // scale along long0
	var d1 = 6378137; // Polar Radius
	var d2 = 0.00669438;
	if(datum.match(/nad\s?27/i)){
		//datum is NAD27
		d1 = 6378206; 
		d2 = 0.006768658;
	}
	else if(datum.match(/nad\s?83/i)){
		//datum is NAD83
		d1 = 6378137;
		d2 = 0.00669438;
	}
	
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
	var lngValue = d11 + ((d18 / Math.PI) * 180); // LÃ¦ngdegrad (Ã˜)
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
				vcStr = vcStr.replace(/-*\d{2}°+[NS\d\.\s\'\"-°]+[EW;]+/g, "");
				vcStr = vcStr.replace(/^\s+|\s+$/g, "");
				vcStr = vcStr.replace(/^;|;$/g, "");
				if(vcStr != ""){
					vcStr = vcStr + "; ";
				}
				var dmsStr = latDeg + "&deg; " + latMin + "' ";
				if(latSec > 0) dmsStr += latSec + "'' ";
				dmsStr += latNS + "  " + lngDeg + "&deg; " + lngMin + "' ";
				if(lngSec) dmsStr += lngSec + "'' ";
				dmsStr += lngEW;
				f.verbatimcoordinates.value = vcStr + dmsStr;
				var latDec = parseInt(latDeg) + (parseFloat(latMin)/60) + (parseFloat(latSec)/3600);
				var lngDec = parseInt(lngDeg) + (parseFloat(lngMin)/60) + (parseFloat(lngSec)/3600);
				if(latNS == "S") latDec = latDec * -1; 
				if(lngEW == "W") lngDec = lngDec * -1; 
				f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
				f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
	
				fieldChanged("decimallatitude");
				fieldChanged("decimallongitude");
				fieldChanged("verbatimcoordinates");
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

function insertTRS(f) {
	var township = document.getElementById("township").value.replace(/^\s+|\s+$/g,"");
	var townshipNS = document.getElementById("townshipNS").value.replace(/^\s+|\s+$/g,"");
	var range = document.getElementById("range").value.replace(/^\s+|\s+$/g,"");
	var rangeEW = document.getElementById("rangeEW").value.replace(/^\s+|\s+$/g,"");
	var section = document.getElementById("section").value.replace(/^\s+|\s+$/g,"");
	var secdetails = document.getElementById("secdetails").value.replace(/^\s+|\s+$/g,"");
	var meridian = document.getElementById("meridian").value.replace(/^\s+|\s+$/g,"");
	
	if(!township || !range){
		alert("Township and Range fields must have values");
		return false;
	}
	else if(!isNumeric(township)){
		alert("Numeric value expected for Township field. If non-standardize format is used, enter directly into the Verbatim Coordinate Field");
		return false;
	}
	else if(!isNumeric(range)){
		alert("Numeric value expected for Range field. If non-standardize format is used, enter directly into the Verbatim Coordinate Field");
		return false;
	}
	else if(!isNumeric(section)){
		alert("Numeric value expected for Section field. If non-standardize format is used, enter directly into the Verbatim Coordinate Field");
		return false;
	}
	else if(section > 36){
		alert("Section field must contain a numeric value between 1-36");
		return false;
	}
	else{
		//Insert into verbatimCoordinate field
		vCoord = f.verbatimcoordinates;
		if(vCoord.value) vCoord.value = vCoord.value + "; "; 
		vCoord.value = vCoord.value + "TRS: T"+township+townshipNS+" R"+range+rangeEW+" sec "+section+" "+secdetails+" "+meridian;
		fieldChanged("verbatimcoordinates");
	}
}

//Dupe searches
function searchDupesCatalogNumber(f){
	var cnValue = f.catalognumber.value;
	if(cnValue){
		cnXmlHttp = GetXmlHttpObject();
		if(cnXmlHttp==null){
			alert ("Your browser does not support AJAX!");
		return;
	}
	var occid = f.occid.value;
	var url = "rpc/querycatalognumber.php?cn=" + cnValue + "&collid=" + collId + "&occid=" + occid;
	
	document.getElementById("dupeMsgDiv").style.display = "block";
	document.getElementById("dupesearch").style.display = "block";
	document.getElementById("dupenone").style.display = "none";

	cnXmlHttp.onreadystatechange=function(){
		if(cnXmlHttp.readyState==4 && cnXmlHttp.status==200){
			var resObj = eval('(' + cnXmlHttp.responseText + ')')
			if(resObj.length > 0){
				catalogNumberIsDupe = true;
				if(confirm("Record(s) of same catalog number already exists. Do you want to view this record?")){
					var occWindow=open("dupesearch.php?occidquery="+resObj+"&collid="+collId+"&curoccid="+occid,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
					if(occWindow != null){
						if (occWindow.opener == null) occWindow.opener = self;
					}
					else{
						alert("Unable to display record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
					}
				}						
				document.getElementById("dupesearch").style.display = "none";
				document.getElementById("dupeMsgDiv").style.display = "none";
			}
			else{
				document.getElementById("dupesearch").style.display = "none";
				document.getElementById("dupenone").style.display = "block";
				setTimeout(function () { 
					document.getElementById("dupenone").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
					}, 3000);
				catalogNumberIsDupe = false;
			}
		}
	};
	cnXmlHttp.open("POST",url,true);
		cnXmlHttp.send(null);
	}
}

function verifyDupeCatalogNumber(f){
	var cnValue = f.catalognumber.value;
	if(!cnValue || !catalogNumberIsDupe) return true;
	xmlHttp = GetXmlHttpObject();
	if(xmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	var url = "rpc/querycatalognumber.php?cn=" + cnValue + "&collid=" + f.collid.value + "&occid=" + f.occid.value;
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var resObj = eval('(' + xmlHttp.responseText + ')')
			if(resObj.length > 0){
				if(confirm("Another record exists with the same catalog number, which is not allowed. Do you want to view the other record(s)?")){
					var occWindow=open("dupesearch.php?occidquery="+resObj+"&collid="+f.collid.value+"&curoccid="+f.occid.value,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
					if(occWindow != null){
						if (occWindow.opener == null) occWindow.opener = self;
					}
					else{
						alert("Unable to display record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
					}
				}
				return false;
			}
			else{
				return true;
			}
		}
	};
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
}

function searchDupesOtherCatalogNumbers(f){
	var inValue = f.othercatalognumbers.value; 
	if(inValue){
		xmlHttp = GetXmlHttpObject();
		if(xmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var occid = f.occid.value;
	var url = "rpc/queryothercatalognumbers.php?invalue=" + inValue + "&collid=" + collId + "&occid=" + occid;

	document.getElementById("dupeMsgDiv").style.display = "block";
	document.getElementById("dupesearch").style.display = "block";
	document.getElementById("dupenone").style.display = "none";

	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var resObj = eval('(' + xmlHttp.responseText + ')');
			if(resObj.length > 0){
				if(confirm("Record(s) using the same identifier already exists. Do you want to view this record?")){
					var occWindow=open("dupesearch.php?occidquery="+resObj+"&collid="+collId+"&curoccid="+occid,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
					if(occWindow != null){
						if (occWindow.opener == null) occWindow.opener = self;
					}
					else{
						alert("Unable to show record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
					}
				}						
				document.getElementById("dupesearch").style.display = "none";
				document.getElementById("dupeMsgDiv").style.display = "none";
			}
			else{
				document.getElementById("dupesearch").style.display = "none";
				document.getElementById("dupenone").style.display = "block";
				setTimeout(function () { 
					document.getElementById("dupenone").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
					}, 3000);
			}
		}
	};
	xmlHttp.open("POST",url,true);
		xmlHttp.send(null);
	}
}

function searchDupesCollector(f,silent){
	var cName = f.recordedby.value;
	var cNum = f.recordnumber.value;
	var cDate = f.eventdate.value;
	var occid = f.occid.value;

	if(!cName || (!cNum && !cDate)){
		if(!silent) alert("Collector name and number or date must have a value to search for duplicates");
		return false;
	}
	
	xmlHttp = GetXmlHttpObject();
	if(xmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url = "rpc/querydupescollector.php?cname=" + cName + "&cnum=" + cNum + "&cdate=" + cDate + "&curoccid=" + occid;
	document.getElementById("dupeMsgDiv").style.display = "block";
	document.getElementById("dupesearch").style.display = "block";
	document.getElementById("dupenone").style.display = "none";
	
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var resStr = xmlHttp.responseText;
			if(resStr){
				var dupOccWindow = open("dupesearch.php?occidquery="+resStr+"&collid="+collId+"&curoccid="+occid,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
				if(dupOccWindow != null){
					if(dupOccWindow.opener == null) dupOccWindow.opener = self;
					if(window.focus) {dupOccWindow.focus()}
					document.getElementById("dupesearch").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
				}
				else{
					alert("Duplicate found but unable to display. This is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
				}
			}
			else{
				searchDupesCollectorEvent(f,silent);
			}
		}
	};
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
}

function searchDupesCollectorEvent(f,silent){
	var cName = f.recordedby.value;
	var cNum = f.recordnumber.value;
	var cDate = f.eventdate.value;
	var occid = f.occid.value;

	if(!cName || (!cNum && !cDate)){
		if(!silent) alert("Collector name and number or date must have a value to search for duplicates");
		return false;
	}
	
	xmlHttp = GetXmlHttpObject();
	if(xmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url = "rpc/querydupescollectorevent.php?cname=" + cName + "&cnum=" + cNum + "&cdate=" + cDate + "&curoccid=" + occid;
	document.getElementById("dupeMsgDiv").style.display = "block";
	document.getElementById("dupesearch").style.display = "block";
	document.getElementById("dupenone").style.display = "none";
	
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var resStr = xmlHttp.responseText;
			if(resStr){
				var dupOccWindow=open("dupesearch.php?occidquery="+resStr+"&collid="+collId+"&curoccid="+occid+"&exact=0","occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
				if(dupOccWindow != null){
					if(dupOccWindow.opener == null) dupOccWindow.opener = self;
					if(window.focus) {dupOccWindow.focus()}
					document.getElementById("dupesearch").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
				}
				else{
					alert("Duplicate found but unable to display. This is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
				}
			}
			else{
				document.getElementById("dupesearch").style.display = "none";
				document.getElementById("dupenone").style.display = "block";
				setTimeout(function () { 
					document.getElementById("dupenone").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
					}, 5000);
			}
		}
	};
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
}

function autoDupeSearch(){
	var f = document.fullform;
	if(f.autodupe && f.autodupe.checked == true){
		searchDupesCollector(f,true);
	}
}

function lookForExsDupes(f){
	var exsTitle = f.exsiccatititle.value;
	var exsNumber = f.exsiccatinumber.value;
		
	if(!exsTitle || !exsNumber){
		alert("Exsiccati title and number must have a value to search for duplicates");
		return;
	}

	dupExsWindow=open("dupesearch.php?exstitle="+exsTitle+"&exsnumber="+exsNumber,"exsdupaid","resizable=1,scrollbars=1,toolbar=1,width=900,height=700,left=20,top=20");
	if(dupExsWindow.opener == null) dupExsWindow.opener = self;
	if(window.focus) {
		dupExsWindow.focus()
	}
}
