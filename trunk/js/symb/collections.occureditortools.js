
function openAssocSppAid(){
	assocWindow = open("assocsppaid.php","assocaid","resizable=0,width=550,height=200,left=20,top=20");
	if (assocWindow.opener == null) assocWindow.opener = self;
}

function toggleCoordDiv(){
	coordObj = document.getElementById("coordaiddiv");
	if(coordObj.style.display == "none"){
		document.getElementById("elevaiddiv").style.display = "none";
		document.getElementById("locextradiv1").style.display = "block";
		document.getElementById("locextradiv2").style.display = "block";
		coordObj.style.display = "block";
	}
	else{
		coordObj.style.display = "none";
	}
}

function toggleElevDiv(){
	elevObj = document.getElementById("elevaiddiv");
	if(elevObj.style.display == "none"){
		document.getElementById("coordaiddiv").style.display = "none";
		elevObj.style.display = "block";
	}
	else{
		elevObj.style.display = "none";
	}
}

function openMappingAid() {
	var f = document.fullform;
	var latDef = f.decimallatitude.value;
	var lngDef = f.decimallongitude.value;
	var zoom = 5;
	if(latDef && lngDef) zoom = 9;
	mapWindow=open("mappointaid.php?latdef="+latDef+"&lngdef="+lngDef+"&zoom="+zoom,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
	if (mapWindow.opener == null) mapWindow.opener = self;
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
			vcStr = vcStr.replace(/\d{2}.*\d+E\s+\d+N\s{1}(Northern)|(Southern){1}[;\s]*/g, "");
			vcStr = vcStr.replace(/^\s+|\s+$/g, "");
			vcStr = vcStr.replace(/^;|;$/g, "");
			//put UTM into verbatimCoordinate field
			if(vcStr != ""){
				vcStr = vcStr + "; ";
			}
			var utmStr = zValue + " " + eValue + "E " + nValue + "N " + hValue;
			f.verbatimcoordinates.value = vcStr + utmStr;
			//Convert to Lat/Lng values
			var zNum = parseInt(zValue);
			if(isNumeric(zNum)){
				var latLngStr = utm2LatLng(zNum,eValue,nValue,f.geodeticdatum.value);
				var llArr = latLngStr.split(',');
				if(llArr){
					f.decimallatitude.value = Math.round(llArr[0]*1000000)/1000000;
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
	var d = 0.99960000000000004; // scale along long0
	var d1 = 6378137; // Polar Radius
	var d2 = 0.00669438;
	if(datum.match(/nad\s?27/i)){
		//datum is NAD27, else assumed to be NAD83 or WGS84
		d1 = 6378206; 
		d2 = 0.006768658;
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
				vcStr = vcStr.replace(/-*\d{2}°+[NS\d\.\s\'\"]+°+[-\d\.\s\'\"]+[EW;]+/g, "");
				vcStr = vcStr.replace(/^\s+|\s+$/g, "");
				vcStr = vcStr.replace(/^;|;$/g, "");
				if(vcStr != ""){
					vcStr = vcStr + "; ";
				}
				var dmsStr = latDeg + "° " + latMin + "' ";
				if(latSec > 0) dmsStr += latSec + '" ';
				dmsStr += latNS + "  " + lngDeg + "° " + lngMin + "' ";
				if(lngSec) dmsStr += lngSec + '" ';
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

function insertElevFt(f){
	var elevMin = document.getElementById("elevminft").value;
	var elevMax = document.getElementById("elevmaxft").value;
	if(elevMin){
		if(isNumeric(elevMin)){
			f.minimumelevationinmeters.value = Math.round(elevMin*.03048)*10;
			fieldChanged("minimumelevationinmeters");
			verbStr = elevMin;
			if(elevMax){
				if(isNumeric(elevMax)){
					f.maximumelevationinmeters.value = Math.round(elevMax*.03048)*10;
					fieldChanged("maximumelevationinmeters");
					if(elevMax) verbStr += " - " + elevMax;
				}
				else{
					alert("Elevation fields must be numeric values only (no text)!");
				}
			}
			verbStr += "ft";
			f.verbatimelevation.value = verbStr;
			fieldChanged("verbatimelevation");
		}
		else{
			alert("Elevation fields must be numeric values only (no text)!");
		}
	}
}

function lookForDupes(f){
	var collName = f.recordedby.value;
	var collNum = f.recordnumber.value;
	var collDate = f.eventdate.value;
	var occId = f.occid.value;
	var collId = f.collid.value;
		
	if(!collName || (!collNum && !collDate)){
		alert("Collector name and number or date must have a value to search for duplicates");
		return;
	}

	//dupWindow=open("dupesearch.php?oid="+f.occid.value+"&occids="+resObj+"&collid="+f.collid.value,"dupaid","resizable=1,scrollbars=1,width=900,height=700,left=20,top=20");
	dupWindow=open("dupesearch.php?cname="+collName+"&cnum="+collNum+"&cdate="+collDate+"&oid="+occId+"&collid="+collId,"dupaid","resizable=1,scrollbars=1,width=900,height=700,left=20,top=20");
	if(dupWindow.opener == null) dupWindow.opener = self;
	if(window.focus) {dupWindow.focus()}

	/*document.getElementById("dupedisplayspan").style.display = "none";
	document.getElementById("dupenonespan").style.display = "none";
	document.getElementById("dupesearchspan").style.display = "block";
	document.getElementById("dupespan").style.display = "block";

	//Check for matching records
	dupXmlHttp = GetXmlHttpObject();
	if(dupXmlHttp==null){
		alert ("Your browser does not support AJAX!");
  		return;
	}
	var url = "rpc/querydupes.php?cname=" + collName + "&cnum=" + collNum;
	if(collDate) url = url + "&cdate=" + collDate;
	dupXmlHttp.onreadystatechange=function(){
		if(dupXmlHttp.readyState==4 && dupXmlHttp.status==200){
			var resObj = eval('(' + dupXmlHttp.responseText + ')')
			if(resObj.length > 0){
				document.getElementById("dupesearchspan").style.display = "none";
				document.getElementById("dupedisplayspan").style.display = "block";
				dupWindow=open("dupesearch.php?oid="+f.occid.value+"&occids="+resObj+"&collid="+f.collid.value,"dupaid","resizable=1,scrollbars=1,width=900,height=700,left=20,top=20");
				if(dupWindow.opener == null) dupWindow.opener = self;
				if(window.focus) {dupWindow.focus()}
				document.getElementById("dupespan").style.display = "none";
			}
			else{
				document.getElementById("dupesearchspan").style.display = "none";
				document.getElementById("dupenonespan").style.display = "block";
			}
		}
	};
	dupXmlHttp.open("POST",url,true);
	dupXmlHttp.send(null);
	*/
}
