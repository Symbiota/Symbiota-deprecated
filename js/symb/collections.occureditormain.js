var pauseSubmit = false;
var imgAssocCleared = false;
var voucherAssocCleared = false;
var surveyAssocCleared = false;
var pendingDataEdits = false;

var abortFormVerification = false;

$(document).ready(function() {

	if(navigator.appName == "Microsoft Internet Explorer"){
		alert("You are using Internet Explorer as your web browser. We recommend that you use Firefox or Google Chrome since these browsers are generally more reliable for editing specimen records.");
	}
	else{
		if(/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){
			var ffversion=new Number(RegExp.$1);
			if(ffversion < 7 ) alert("You are using an older version of Firefox. For best results, we recommend that you update your browser.");
		}
	}
	
	$("#occedittabs").tabs({
		select: function(event, ui) {
			statusObj = document.getElementById("statusdiv");
			if(statusObj){
				statusObj.style.display = "none";
			}
			return true;
		},
		selected: tabTarget
	});

	$("#ffsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		change: function(event, ui) {
			verifyFullformSciName();
			fieldChanged('sciname');
		}
	},
	{ minLength: 3, autoFocus: true });

	$("#ffexstitle").autocomplete({ 
		source: "rpc/getexstitlesuggest.php"
	},
	{ minLength: 3 });


	//Misc fields with lookups
	$("#ffcountry").autocomplete({ 
			source: countryArr,
			change: function(event, ui) {
				fieldChanged('country');
			}
		},
		{ minLength: 1, autoFocus: true } 
	);

	$("#ffstate").autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/lookupState.php", { term: request.term, "country": document.fullform.country.value }, response );
			},
			change: function(event, ui) {
				fieldChanged('stateprovince');
			}		
		},
		{ minLength: 2, autoFocus: true, matchContains: false }
	);

	$("#ffcounty").autocomplete({ 
			source: function( request, response ) {
				$.getJSON( "rpc/lookupCounty.php", { term: request.term, "state": document.fullform.stateprovince.value }, response );
			},
			change: function(event, ui) {
				fieldChanged('county');
			}		
		},
		{ minLength: 2, autoFocus: true, matchContains: false }
	);

	var imgTd = getCookie("symbimgtd");
	if(imgTd == "open") toggleImageTd(); 

});

window.onbeforeunload = verifyClose();

function verifyClose(){
	if(pendingDataEdits && document.fullform.editedfields.value != ""){
		return "It appears that you didn't save your changes. Are you sure you want to leave without saving?"; 
	}
}

function initDetAddAutocomplete(){
	$("#dafsciname").autocomplete({
		source: "rpc/getspeciessuggest.php",
		change: function(event, ui) {
			pauseSubmit = true;
			verifyDetSciName(document.detaddform);
		}
	},
	{ minLength: 3 });
}

function initDetEditAutocomplete(inputName){
	$("#"+inputName).autocomplete({
		source: "rpc/getspeciessuggest.php",
		change: function(event, ui) {
			pauseSubmit = true;
			verifyDetSciName(document.deteditform);
		}
	},
	{ minLength: 3 });
}

//Field changed and verification functions
function fieldChanged(fieldName){
	try{
		document.fullform.editedfields.value = document.fullform.editedfields.value + fieldName + ";";
	}
	catch(ex){
	}
	pendingDataEdits = true;
}

function catalogNumberChanged(f){
	fieldChanged('catalognumber');

	var cnValue = f.catalognumber.value;
	if(cnValue){
		cnXmlHttp = GetXmlHttpObject();
		if(cnXmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return;
		}
		var oid = f.occid.value;
		var url = "rpc/querycatalognumber.php?cn=" + cnValue + "&collid=" + collId + "&occid=" + oid;
		
		document.getElementById("dupespan").style.display = "block";
		document.getElementById("dupesearchspan").style.display = "block";
		document.getElementById("dupenonespan").style.display = "none";

		cnXmlHttp.onreadystatechange=function(){
			if(cnXmlHttp.readyState==4 && cnXmlHttp.status==200){
				var resObj = eval('(' + cnXmlHttp.responseText + ')')
				if(resObj.length > 0){
					if(confirm("Record(s) of same catalog number already exists. Do you want to view this record?")){
						occWindow=open("dupesearch.php?occidquery="+resObj+"&collid="+collId+"&oid="+oid,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
						if (occWindow.opener == null) occWindow.opener = self;
					}						
					document.getElementById("dupesearchspan").style.display = "none";
					document.getElementById("dupespan").style.display = "none";
				}
				else{
					document.getElementById("dupesearchspan").style.display = "none";
					document.getElementById("dupenonespan").style.display = "block";
					setTimeout(function () { 
						document.getElementById("dupenonespan").style.display = "none";
						document.getElementById("dupespan").style.display = "none";
						}, 3000);
				}
			}
		};
		cnXmlHttp.open("POST",url,true);
		cnXmlHttp.send(null);
	}
}

function occurrenceIdChanged(f){
	fieldChanged('occurrenceid');

	var oiValue = f.occurrenceid.value;
	if(oiValue){
		oiXmlHttp = GetXmlHttpObject();
		if(oiXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var oid = f.occid.value;
		var url = "rpc/queryoccurrenceid.php?oi=" + oiValue + "&collid=" + collId + "&occid=" + oid;

		document.getElementById("dupespan").style.display = "block";
		document.getElementById("dupesearchspan").style.display = "block";
		document.getElementById("dupenonespan").style.display = "none";
		
		oiXmlHttp.onreadystatechange=function(){
			if(oiXmlHttp.readyState==4 && oiXmlHttp.status==200){
				var resObj = eval('(' + oiXmlHttp.responseText + ')')
				if(resObj.length > 0){
					if(confirm("Record(s) using the same occurrence ID already exists. Do you want to view this record?")){
						occWindow=open("dupesearch.php?occidquery="+resObj+"&collid="+collId+"&oid="+oid,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
						if (occWindow.opener == null) occWindow.opener = self;
					}						
					document.getElementById("dupesearchspan").style.display = "none";
					document.getElementById("dupespan").style.display = "none";
				}
				else{
					document.getElementById("dupesearchspan").style.display = "none";
					document.getElementById("dupenonespan").style.display = "block";
					setTimeout(function () { 
						document.getElementById("dupenonespan").style.display = "none";
						document.getElementById("dupespan").style.display = "none";
						}, 3000);
				}
			}
		};
		oiXmlHttp.open("POST",url,true);
		oiXmlHttp.send(null);
	}
}

function otherCatalogNumbersChanged(f){
	fieldChanged('othercatalognumbers');

	var inValue = f.othercatalognumbers.value; 
	if(inValue){
		xmlHttp = GetXmlHttpObject();
		if(xmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var oid = f.occid.value;
		var url = "rpc/queryothercatalognumbers.php?invalue=" + inValue + "&collid=" + collId + "&occid=" + oid;

		document.getElementById("dupespan").style.display = "block";
		document.getElementById("dupesearchspan").style.display = "block";
		document.getElementById("dupenonespan").style.display = "none";

		xmlHttp.onreadystatechange=function(){
			if(xmlHttp.readyState==4 && xmlHttp.status==200){
				var resObj = eval('(' + xmlHttp.responseText + ')')
				if(resObj.length > 0){
					if(confirm("Record(s) using the same identifier already exists. Do you want to view this record?")){
						occWindow=open("dupesearch.php?occidquery="+resObj+"&collid="+collId+"&oid="+oid,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
						if (occWindow.opener == null) occWindow.opener = self;
					}						
					document.getElementById("dupesearchspan").style.display = "none";
					document.getElementById("dupespan").style.display = "none";
				}
				else{
					document.getElementById("dupesearchspan").style.display = "none";
					document.getElementById("dupenonespan").style.display = "block";
					setTimeout(function () { 
						document.getElementById("dupenonespan").style.display = "none";
						document.getElementById("dupespan").style.display = "none";
						}, 3000);
				}
			}
		};
		xmlHttp.open("POST",url,true);
		xmlHttp.send(null);
	}
}

function decimalLatitudeChanged(f){
	verifyDecimalLatitude(f);
	fieldChanged('decimallatitude');
}

function decimalLongitudeChanged(f){
	verifyDecimalLongitude(f);
	fieldChanged('decimallongitude');
}

function coordinateUncertaintyInMetersChanged(f){
	if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
		alert("Coordinate uncertainty field must be numeric only");
	}
	fieldChanged('coordinateuncertaintyinmeters');
}

function minimumElevationInMetersChanged(f){
	verifyMinimumElevationInMeters(f);
	fieldChanged('minimumelevationinmeters');
}

function maximumElevationInMetersChanged(f){
	verifyMaximumElevationInMeters(f);
	fieldChanged('maximumelevationinmeters');
}

//Form verification code
function verifyFullForm(f){
	if(abortFormVerification) return true;
	/*
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value. Enter closest know identification, even if it's only to family, order, or above. ");
		return false;
	}
	if(f.recordedby.value == ""){
		alert("Collector field must have a value. Enter 'unknown' if needed.");
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
	*/
	var validformat1 = /^\d{4}-\d{1,2}-\d{1,2}$/; //Format: yyyy-mm-dd
	if(f.eventdate.value && !validformat1.test(f.eventdate.value)){
		alert("Event date is invalid");
		return false;
	}
	if(!isNumeric(f.year.value)){
		alert("Collection year field must be numeric only");
		return false;
	}
	if(!isNumeric(f.month.value)){
		alert("Collection month field must be numeric only");
		return false;
	}
	if(!isNumeric(f.day.value)){
		alert("Collection day field must be numeric only");
		return false;
	}
	if(!isNumeric(f.startdayofyear.value)){
		alert("Start day of year field must be numeric only");
		return false;
	}
	if(!isNumeric(f.enddayofyear.value)){
		alert("End day of year field must be numeric only");
		return false;
	}
	if(!verifyDecimalLatitude(f)){
		return false;
	}
	if(!verifyDecimalLongitude(f)){
		return false;
	}
	if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
		alert("Coordinate uncertainty field must be numeric only");
		return false;
	}
	if(!verifyMinimumElevationInMeters(f)){
		return false;
	}
	if(!verifyMaximumElevationInMeters(f)){
		return false;
	}
	if(f.maximumelevationinmeters.value){
		if(!f.minimumelevationinmeters.value){
			alert("Maximun elevation field contains a value yet minumum does not. If elevation consists of a single value rather than a range, enter the value in the minimun field.");
			return false;
		}
		else if(parseInt(f.minimumelevationinmeters.value) > parseInt(f.maximumelevationinmeters.value)){
			alert("Maximun elevation value can not be greater than the minumum value.");
			return false;
		}
	}
	if(!isNumeric(f.duplicatequantity.value)){
		alert("Duplicate Quantity field must be numeric only");
		return false;
	}
	pendingDataEdits = false;
	return true;
}

function verifyFullFormEdits(f){
	if(f.editedfields){
		if(f.editedfields.value == ""){
			setTimeout(function () { 
				if(f.editedfields.value){
					f.submitaction.click();
				}
				else{
					alert("No fields appear to have been changed. If you have just changed the scientific name field, there may not have enough time to verify name. Try to submit again.");
				}
			}, 1000);
			return false;
		}
	}
}

function verifyGotoNew(f){
	abortFormVerification = true;
}

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
				alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus. If taxon is spelled correctly, continue entering specimen and name can be add to thesaurus afterward.");
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

function verifyDecimalLatitude(f){
	if(!isNumeric(f.decimallatitude.value)){
		alert("Input value for Decimal Latitude must be a number value only! " );
		return false;
	}
	if(parseInt(f.decimallatitude.value) > 90){
		alert("Decimal Latitude can not be greater than 90 degrees " );
		return false;
	}
	if(parseInt(f.decimallatitude.value) < -90){
		alert("Decimal Latitude can not be less than -90 degrees " );
		return false;
	}
	return true;
}

function verifyDecimalLongitude(f){
	var lngValue = f.decimallongitude.value;
	if(!isNumeric(lngValue)){
		alert("Input value for Decimal Longitude must be a number value only! " );
		return false;
	}
	if(parseInt(lngValue) > 180){
		alert("Decimal Longitude can not be greater than 180 degrees " );
		return false;
	}
	if(parseInt(lngValue) < -180){
		alert("Decimal Longitude can not be less than -180 degrees " );
		return false;
	}

	//Check to see if coordinates are within country/state
	var latValue = f.decimallatitude.value;
	if(latValue && lngValue){
		xmlHttp = GetXmlHttpObject();
		if(xmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var url = "http://ws.geonames.org/countrySubdivisionJSON?lat="+latValue+"&lng="+lngValue;
		xmlHttp.onreadystatechange=function(){
			if(xmlHttp.readyState==4 && xmlHttp.status==200){
				if(xmlHttp.responseText){
					var retArr = eval("("+xmlHttp.responseText+")");
					var cValue = retArr["countryName"];
					if(cValue && !f.country.value) f.country.value = cValue; 
					var sValue = retArr["adminName1"];
					if(sValue){
						sValue = sValue.toLowerCase();
						var currentState = f.stateprovince.value;
						if(currentState){
							currentState = currentState.toLowerCase();
							if(currentState.indexOf(sValue) == -1) alert("Is State accurate? Coordiantes map to: "+cValue+", "+sValue+" Click globe symbol to display coordinates in map.");
						}
						else{
							f.stateprovince.value = sValue;
						}
					}
				}
			}
		};
		xmlHttp.open("POST",url,true);
		xmlHttp.send(null);
	}
	
	return true;
}

function verifyMinimumElevationInMeters(f){
	if(!isNumeric(f.minimumelevationinmeters.value)){
		alert("Elevation values must be numeric only");
		return false;
	}
	if(parseInt(f.minimumelevationinmeters.value) > 8000){
		alert("Was this collection really made above the elevation of Mount Everest?" );
		return false;
	}
	return true;
}

function verifyMaximumElevationInMeters(f){
	if(!isNumeric(f.maximumelevationinmeters.value)){
		alert("Elevation values must be numeric only");
		return false;
	}
	if(parseInt(f.maximumelevationinmeters.value) > 8000){
		alert("Was this collection really made above the elevation of Mount Everest?" );
		return false;
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

function eventDateModified(eventDateInput){
	var dateStr = eventDateInput.value;
	if(dateStr == "") return true;

	var dateArr = parseDate(dateStr);
	if(dateArr['y'] == 0){
		alert("Unable to interpret Date. Please use the following formats: yyyy-mm-dd, mm/dd/yyyy, or dd mmm yyyy");
		return false;
	}
	else{
		//Check to see if date is in the future 
		try{
			var testDate = new Date(dateArr['y'],dateArr['m']-1,dateArr['d']);
			var today = new Date();
			if(testDate > today){
				alert("Was this plant really collected in the future? The date you entered has not happened yet. Please revise.");
				return false;
			}
		}
		catch(e){
		}

		//Check to see if day is valid
		if(dateArr['d'] > 28){
			if(dateArr['d'] > 31 
				|| (dateArr['d'] == 30 && dateArr['m'] == 2) 
				|| (dateArr['d'] == 31 && (dateArr['m'] == 4 || dateArr['m'] == 6 || dateArr['m'] == 9 || dateArr['m'] == 11))){
				alert("The Day (" + dateArr['d'] + ") is invalid for that month");
				return false;
			}
		}

		//Enter date into date fields
		var mStr = dateArr['m'];
		if(mStr.length == 1){
			mStr = "0" + mStr;
		}
		var dStr = dateArr['d'];
		if(dStr.length == 1){
			dStr = "0" + dStr;
		}
		eventDateInput.value = dateArr['y'] + "-" + mStr + "-" + dStr;
		if(dateArr['y'] > 0) distributeEventDate(dateArr['y'],dateArr['m'],dateArr['d']);
	}
	fieldChanged('eventdate');
	return true;
}

function distributeEventDate(y,m,d){
	var f = document.fullform;
	if(y != "0000"){
		f.year.value = y;
		fieldChanged("year");
	}
	if(m == "00"){
		f.month.value = "";
	}
	else{
		f.month.value = m;
		fieldChanged("year");
	}
	if(d == "00"){
		f.day.value = "";
	}
	else{
		f.day.value = d;
		fieldChanged("day");
	}
	f.startdayofyear.value = "";
	try{
		if(m == 0 || d == 0){
			f.startdayofyear.value = "";
		}
		else{
			eDate = new Date(y,m-1,d);
			if(eDate instanceof Date && eDate != "Invalid Date"){
				var onejan = new Date(y,0,1);
				f.startdayofyear.value = Math.ceil((eDate - onejan) / 86400000) + 1;
				fieldChanged("startdayofyear");
			}
		}
	}
	catch(e){
	}
}

function verbatimEventDateChanged(vedInput){
	fieldChanged('verbatimeventdate');

	vedValue = vedInput.value;
	var f = document.fullform;
	
	if(vedValue.indexOf(" to ") > -1){
		if(f.eventdate.value == ""){
			var startDate = vedValue.substring(0,vedValue.indexOf(" to "));
			var startDateArr = parseDate(startDate);
			var mStr = startDateArr['m'];
			if(mStr.length == 1){
				mStr = "0" + mStr;
			}
			var dStr = startDateArr['d'];
			if(dStr.length == 1){
				dStr = "0" + dStr;
			}
			f.eventdate.value = startDateArr['y'] + "-" + mStr + "-" + dStr;
			distributeEventDate(startDateArr['y'],mStr,dStr);
		}
		var endDate = vedValue.substring(vedValue.indexOf(" to ")+4);
		var endDateArr = parseDate(endDate);
		try{
			var eDate = new Date(endDateArr["y"],endDateArr["m"]-1,endDateArr["d"]);
			if(eDate instanceof Date && eDate != "Invalid Date"){
				var onejan = new Date(endDateArr["y"],0,1);
				f.enddayofyear.value = Math.ceil((eDate - onejan) / 86400000) + 1;
				fieldChanged("enddayofyear");
			}
		}
		catch(e){
		}
	}
}

function parseDate(dateStr){
	var y = 0;
	var m = 0;
	var d = 0;
	try{
		var validformat1 = /^\d{4}-\d{1,2}-\d{1,2}$/; //Format: yyyy-mm-dd
		var validformat2 = /^\d{1,2}\/\d{1,2}\/\d{2,4}$/; //Format: mm/dd/yyyy
		var validformat3 = /^\d{1,2} \D+ \d{2,4}$/; //Format: dd mmm yyyy
		if(validformat1.test(dateStr)){
			var dateTokens = dateStr.split("-");
			y = dateTokens[0];
			m = dateTokens[1];
			d = dateTokens[2];
		}
		else if(validformat2.test(dateStr)){
			var dateTokens = dateStr.split("/");
			m = dateTokens[0];
			d = dateTokens[1];
			y = dateTokens[2];
			if(y.length == 2){
				if(y < 20){
					y = "20" + y;
				}
				else{
					y = "19" + y;
				}
			}
		}
		else if(validformat3.test(dateStr)){
			var dateTokens = dateStr.split(" ");
			d = dateTokens[0];
			mText = dateTokens[1];
			y = dateTokens[2];
			if(y.length == 2){
				if(y < 15){
					y = "20" + y;
				}
				else{
					y = "19" + y;
				}
			}
			mText = mText.substring(0,3);
			mText = mText.toLowerCase();
			var mNames = new Array("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");
			m = mNames.indexOf(mText)+1;
		}
		else if(dateObj instanceof Date && dateObj != "Invalid Date"){
			var dateObj = new Date(dateStr);
			y = dateObj.getFullYear();
			m = dateObj.getMonth() + 1;
			d = dateObj.getDate();
		}
	}
	catch(ex){
	}
	var retArr = new Array();
	retArr["y"] = y.toString();
	retArr["m"] = m.toString();
	retArr["d"] = d.toString();
	return retArr;
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
				alert("WARNING: Taxon not found, perhaps misspelled or not in the taxonomic thesaurus? This is only a problem if this is the current determination or images need to be remapped to this name. If taxon is spelled correctly, continue entering specimen and name can be add to thesaurus afterward.");
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
		if(dateIdentified == "") dateIdentified = document.fullform.eventdate.value;
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
	//If sciname was changed and submit was clicked immediately afterward, wait 5 seconds so that name can be verified 
	if(pauseSubmit){
		var date = new Date();
		var curDate = null;
		do{ 
			curDate = new Date(); 
		}while(curDate - date < 5000 && pauseSubmit);
	}
	pendingDataEdits = false;
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
	pendingDataEdits = false;
	return true;
}

//Image tab form methods 
function verifyImgAddForm(f){
    if(f.elements["imgfile"].value.replace(/\s/g, "") == "" ){
        if(f.elements["imgurl"].value.replace(/\s/g, "") == ""){
        	window.alert("Select an image file or enter a URL to an existing image");
			return false;
        }
    }
	pendingDataEdits = false;
    return true;
}

function verifyImgEditForm(f){
	if(f.url.value == ""){
		alert("Web URL field must have a value");
		return false;
	}
	pendingDataEdits = false;
	return true;
}

function verifyImgDelForm(f){
	if(confirm('Are you sure you want to delete this image? Note that the physical image will be deleted from the server if checkbox is selected.')){
		return true;
	}
	return false;
}

//Misc
function dwcDoc(dcTag){
    dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
    if(dwcWindow.opener == null) dwcWindow.opener = self;
    return false;
}

function openOccurrenceSearch(target) {
	collId = document.fullform.collid.value;
	occWindow=open("imgremapaid.php?targetid="+target+"&collid="+collId,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=750,height=600,left=20,top=20");
	if (occWindow.opener == null) occWindow.opener = self;
}

function toggleIdDetails(){
	toggle("idrefdiv");
	toggle("idremdiv");
}

function toggleLocSecReason(f){
	var lsrObj = document.getElementById("locsecreason");
	if(f.localitysecurity.checked){
		lsrObj.style.display = "inline";
	}
	else{
		lsrObj.style.display = "none";
	}
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