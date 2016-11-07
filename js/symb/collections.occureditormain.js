var pauseSubmit = false;
var imgAssocCleared = false;
var voucherAssocCleared = false;
var abortFormVerification = false;

$(document).ready(function() {

	if(/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){
		var ffversion=new Number(RegExp.$1);
		if(ffversion < 7 ) alert("You are using an older version of Firefox. For best results, we recommend that you update your browser.");
	}

	$("#occedittabs").tabs({
		select: function(event, ui) {
			if(verifyLeaveForm()){
				document.fullform.submitaction.disabled = true;
			}
			else{
				return false;
			}
			var statusObj = document.getElementById("statusdiv");
			if(statusObj){
				statusObj.style.display = "none";
			}
			return true;
		},
		active: tabTarget,
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});

	$( "#exstitleinput" ).autocomplete({
		source: "rpc/exsiccatisuggest.php",
		minLength: 2,
		autoFocus: true,
		select: function( event, ui ) {
			if(ui.item){
				$( "#ometidinput" ).val(ui.item.id);
				fieldChanged('ometid');
			}
			else{
				$( "#ometidinput" ).val("");
				fieldChanged('ometid');
			}
		},
		change: function( event, ui ) {
			if($( this ).val() == ""){
				$( "#ometidinput" ).val("");
			}
			else{
				if($( "#ometidinput" ).val() == ""){
					$.ajax({
						type: "POST",
						url: "rpc/exsiccativalidation.php",
						data: { term: $( this ).val() }
					}).done(function( msg ) {
						if(msg == ""){
							alert("Exsiccati title not found within system");
						}
						else{
							$( "#ometidinput" ).val(msg);
							fieldChanged('ometid');
						}
					});
				}
			}
		}
	});

	$("#ffsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		change: function(event, ui) {
			$( "#tidinterpreted" ).val("");
			$( 'input[name=scientificnameauthorship]' ).val("");
			$( 'input[name=family]' ).val("");
			$( 'input[name=localitysecurity]' ).prop('checked', false);
			fieldChanged('sciname');
			fieldChanged('tidinterpreted');
			fieldChanged('scientificnameauthorship');
			fieldChanged('family');
			fieldChanged('localitysecurity');
			if($( "#ffsciname" ).val()){
				verifyFullFormSciName();
			}
			else{
				$( "#tidinterpreted" ).val("");
				$( 'input[name=scientificnameauthorship]' ).val("");
				$( 'input[name=family]' ).val("");
				$( 'input[name=localitysecurity]' ).prop('checked', false);
			}
		}
	});

	//Misc fields with lookups
	$("#ffcountry").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/lookupCountry.php", { term: request.term }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("country");
		}
	});

	$("#ffstate").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/lookupState.php", { term: request.term, "country": document.fullform.country.value }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("stateprovince");
		}
	});

	$("#ffcounty").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/lookupCounty.php", { term: request.term, "state": document.fullform.stateprovince.value }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("county");
		}
	});

	$("#ffmunicipality").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/lookupMunicipality.php", { term: request.term, "state": document.fullform.stateprovince.value }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("municipality");
		}
	});

	$("#catalognumber").keydown(function(evt){
		var evt  = (evt) ? evt : ((event) ? event : null);
		if ((evt.keyCode == 13)) { return false; }
	});
	
	if(document.getElementById('hostDiv')){
		$("#quickhost").autocomplete({
			source: function( request, response ) {
				var name = request.term.replace(" ","+");
				$.getJSON( "rpc/getcolspeciessuggest.php", { term: name }, response );
			},
			minLength: 4,
			autoFocus: true,
			change: function(event, ui){
				fieldChanged("host");
			}
		});
	}
	
	//Remember Auto Processing Status
	var apstatus = getCookie("autopstatus");
	if(getCookie("autopstatus")) document.fullform.autoprocessingstatus.value = apstatus;
	//Remember Auto Duplicate search status 
	if(getCookie("autodupe") == 1) document.fullform.autodupe.checked = true; 
});

function toggleStyle(){
	var cssObj = document.getElementById('editorCssLink');
	if(cssObj.href == "../../css/occureditorcrowdsource.css?ver=20150402"){
		cssObj.href = "../../css/occureditor.css?ver=20150402";
	}
	else{
		cssObj.href = "../../css/occureditorcrowdsource.css?ver=20150402";
	}
}

function toggleQueryForm(){
	toggle("querydiv");
	var statusDiv = document.getElementById('statusdiv');
	if(statusDiv) statusDiv.style.display = 'none';
}

//Field changed and verification functions
function verifyFullFormSciName(){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: $( "#ffsciname" ).val() }
	}).done(function( data ) {
		if(data){
			$( "#tidinterpreted" ).val(data.tid);
			$( 'input[name=family]' ).val(data.family);
			$( 'input[name=scientificnameauthorship]' ).val(data.author);
			if(data.rankid < 220){
				$( 'select[name=confidenceranking]' ).val(2);
			}
			else{
				$( 'select[name=confidenceranking]' ).val(8);
			}
			if(data.status == 1){ 
				$( 'input[name=localitysecurity]' ).prop('checked', true);
			}
			else{
				if(data.tid){
					var stateVal = $( 'input[name=stateprovince]' ).val();
					if(stateVal != ""){
						localitySecurityCheck();
					}
				}
			}
		}
		else{
			$( 'select[name=confidenceranking]' ).val(5);
			alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus. You can continue entering specimen and name will be add to thesaurus later.");
		}
	});
}

function localitySecurityCheck(){
	var tidIn = $( "input[name=tidinterpreted]" ).val();
	var stateIn = $( "input[name=stateprovince]" ).val();
	if(tidIn != "" && stateIn != ""){
		$.ajax({
			type: "POST",
			url: "rpc/localitysecuritycheck.php",
			dataType: "json",
			data: { tid: tidIn, state: stateIn }
		}).done(function( data ) {
			if(data == "1"){
				$( 'input[name=localitysecurity]' ).prop('checked', true);
			}
		});
	}
}

function fieldChanged(fieldName){
	try{
		document.fullform.editedfields.value = document.fullform.editedfields.value + fieldName + ";";
	}
	catch(ex){
	}
	document.fullform.submitaction.disabled = false;
}

function recordNumberChanged(){
	fieldChanged('recordnumber');
	autoDupeSearch();
}

function stateProvinceChanged(stateVal){ 
	fieldChanged('stateprovince');
	var tidVal = $( "#tidinterpreted" ).val();
	if(tidVal != "" && stateVal != ""){
		localitySecurityCheck();
	}
}

function decimalLatitudeChanged(f){
	verifyDecimalLatitude(f);
	//verifyCoordinates(f);
	fieldChanged('decimallatitude');
}

function decimalLongitudeChanged(f){
	verifyDecimalLongitude(f);
	verifyCoordinates(f);
	fieldChanged('decimallongitude');
}

function coordinateUncertaintyInMetersChanged(f){
	if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
		alert("Coordinate uncertainty field must be numeric only");
	}
	fieldChanged('coordinateuncertaintyinmeters');
}

function footPrintWktChanged(formObj){
	fieldChanged('footprintwkt');
	if(formObj.value.length > 65000){
		formObj.value = "";
		alert("WKT footprint is too large to save in the database");
	}
}

function minimumElevationInMetersChanged(f){
	verifyMinimumElevationInMeters(f);
	fieldChanged('minimumelevationinmeters');
}

function maximumElevationInMetersChanged(f){
	verifyMaximumElevationInMeters(f);
	fieldChanged('maximumelevationinmeters');
}

function verbatimElevationChanged(f){
	if(!f.minimumelevationinmeters.value){
		parseVerbatimElevation(f);
	}
	fieldChanged("verbatimelevation");
}

function parseVerbatimElevation(f){
	if(f.verbatimelevation.value){
		var min = "";
		var max = "";
		var verbElevStr = f.verbatimelevation.value;
		verbElevStr = verbElevStr.replace(/,/g ,"");
		
		var regEx1 = /(\d+)\s*-\s*(\d+)\s*[ft|feet|']/i; 
		var regEx2 = /(\d+)\s*[ft|feet|']/i; 
		var regEx3 = /(\d+)\s*-\s*(\d+)\s{0,1}m{1}/i; 
		var regEx4 = /(\d+)\s{0,1}-\s{0,1}(\d+)\s{0,1}m{1}/i; 
		var regEx5 = /(\d+)\s{0,1}m{1}/i; 
		var extractStr = "";
		if(extractArr = regEx1.exec(verbElevStr)){
			min = Math.round(extractArr[1]*.3048);
			max = Math.round(extractArr[2]*.3048);
		}
		else if(extractArr = regEx2.exec(verbElevStr)){
			min = Math.round(extractArr[1]*.3048);
		}
		else if(extractArr = regEx3.exec(verbElevStr)){
			min = extractArr[1];
			max = extractArr[2];
		}
		else if(extractArr = regEx4.exec(verbElevStr)){
			min = extractArr[1];
			max = extractArr[2];
		}
		else if(extractArr = regEx5.exec(verbElevStr)){
			min = extractArr[1];
		}

		if(min){
			f.minimumelevationinmeters.value = min;
			fieldChanged("minimumelevationinmeters");
			if(max){
				f.maximumelevationinmeters.value = max;
				fieldChanged("maximumelevationinmeters");
			}
		}
	}
}

function verbatimCoordinatesChanged(f){
	if(!f.decimallatitude.value){
		parseVerbatimCoordinates(f,0);
	}
	fieldChanged("verbatimcoordinates");
}

function parseVerbatimCoordinates(f,verbose){
	if(f.verbatimcoordinates.value){
		var latDec = null;
		var lngDec = null;
		var verbCoordStr = f.verbatimcoordinates.value;
		verbCoordStr = verbCoordStr.replace(/’/g,"'");
		
		var tokenArr = verbCoordStr.split(" ");
		
		var z = null;
		var e = null;
		var n = null;
		var zoneEx = /^\D{0,1}(\d{1,2})\D*$/;
		var eEx1 = /^(\d{6,7})E/i;
		var nEx1 = /^(\d{7})N/i;
		var eEx2 = /^E(\d{6,7})\D*$/i;
		var nEx2 = /^N(\d{4,7})\D*$/i;
		var eEx3 = /^0{0,1}(\d{6})\D*$/i;
		var nEx3 = /^(\d{7})\D*$/i;
		for(var i = 0; i < tokenArr.length; i++) {
			if(extractArr = zoneEx.exec(tokenArr[i])){
				z = extractArr[1];
			}
			else if(extractArr = eEx1.exec(tokenArr[i])){
				e = extractArr[1];
			}
			else if(extractArr = nEx1.exec(tokenArr[i])){
				n = extractArr[1];
			}
			else if(extractArr = eEx2.exec(tokenArr[i])){
				e = extractArr[1];
			}
			else if(extractArr = nEx2.exec(tokenArr[i])){
				n = extractArr[1];
			}
			else if(extractArr = eEx3.exec(tokenArr[i])){
				e = extractArr[1];
			}
			else if(extractArr = nEx3.exec(tokenArr[i])){
				n = extractArr[1];
			}
		}
		
		if(z && e && n){
			var datum = f.geodeticdatum.value
			var llStr = utm2LatLng(z, e, n, datum);
			if(llStr){
				var llArr = llStr.split(",");
				if(llArr.length == 2){
					latDec = Math.round(llArr[0]*1000000)/1000000;
					lngDec = Math.round(llArr[1]*1000000)/1000000;
				}
			}
		}
		//Check to see if there are embedded lat/lng
		if(!latDec || !lngDec){
			var llEx1 = /(\d{1,2})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*(\d{1,2}\.{0,1}\d*)['"s]{1,2}\s*([NS]{0,1})[\D\s]*(\d{1,3})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*(\d{1,2}\.{0,1}\d*)['"s]{1,2}\s*([EW]{0,1})/i 
			var llEx2 = /(\d{1,2})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*([NS]{0,1})\D*\s*(\d{1,3})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*([EW]{0,1})/i 
			if(extractArr = llEx1.exec(verbCoordStr)){
				var latDeg = parseInt(extractArr[1]);
				var latMin = parseInt(extractArr[2]);
				var latSec = parseFloat(extractArr[3]);
				if(latDeg > 90){
					alert("Latitude degrees cannot be greater than 90");
					return '';
				}
				if(latMin > 60){
					alert("Latitude minutes cannot be greater than 60");
					return '';
				}
				if(latSec > 60){
					alert("Latitude seconds cannot be greater than 60");
					return '';
				}
				var lngDeg = parseInt(extractArr[5]);
				var lngMin = parseInt(extractArr[6]);
				var lngSec = parseFloat(extractArr[7]);
				if(lngDeg > 180){
					alert("Longitude degrees cannot be greater than 180");
					return '';
				}
				if(lngMin > 60){
					alert("Longitude minutes cannot be greater than 60");
					return '';
				}
				if(lngSec > 60){
					alert("Longitude seconds cannot be greater than 60");
					return '';
				}
				//Convert to decimal format
				latDec = latDeg+(latMin/60)+(latSec/3600);
				lngDec = lngDeg+(lngMin/60)+(lngSec/3600);
				if((extractArr[4] == "S" || extractArr[4] == "s") && latDec > 0) latDec = latDec*-1;
				if(lngDec > 0 && extractArr[8] != "E" && extractArr[8] != "e") lngDec = lngDec*-1;
			}
			else if(extractArr = llEx2.exec(verbCoordStr)){
				var latDeg = parseInt(extractArr[1]);
				var latMin = parseFloat(extractArr[2]);
				if(latDeg > 90){
					alert("Latitude degrees cannot be greater than 90");
					return '';
				}
				if(latMin > 60){
					alert("Latitude minutes cannot be greater than 60");
					return '';
				}
				var lngDeg = parseInt(extractArr[4]);
				var lngMin = parseFloat(extractArr[5]);
				if(lngDeg > 180){
					alert("Longitude degrees cannot be greater than 180");
					return '';
				}
				if(lngMin > 60){
					alert("Longitude minutes cannot be greater than 60");
					return '';
				}
				//Convert to decimal format
				latDec = latDeg+(latMin/60);
				lngDec = lngDeg+(lngMin/60);
				if((extractArr[3] == "S" || extractArr[3] == "s") && latDec > 0) latDec = latDec*-1;
				if(lngDec > 0 && extractArr[6] != "E" && extractArr[6] != "e") lngDec = lngDec*-1;
			}
		}

		if(latDec && lngDec){
			f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
			f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
			decimalLatitudeChanged(f);
			decimalLongitudeChanged(f);
		}
		else{
			if(verbose) alert("Unable to parse coordinates");
		}
	}
}

//Form verification code
function verifyFullForm(f){
	f.submitaction.focus();
	if(abortFormVerification) return true;

	if(searchDupesCatalogNumber(f,false)) return false;
	var validformat1 = /^\d{4}-[0]{1}[0-9]{1}-\d{1,2}$/; //Format: yyyy-mm-dd
	var validformat2 = /^\d{4}-[1]{1}[0-2]{1}-\d{1,2}$/; //Format: yyyy-mm-dd
	if(f.eventdate.value && !(validformat1.test(f.eventdate.value) || validformat2.test(f.eventdate.value))){
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
	if(f.ometid && ((f.ometid.value != "" && f.exsnumber.value == "") || (f.ometid.value == "" && f.exsnumber.value != ""))){
		alert("You must have both an exsiccati title and exsiccati number or neither");
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
	return true;
}

function verifyFullFormEdits(f){
	if(f.editedfields && f.editedfields.value == ""){
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
	return true;
}

function verifyGotoNew(f){
	abortFormVerification = true;
	f.gotomode.value = 1;
	f.submit();
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
	return true;
}

function verifyCoordinates(f){
	//Check to see if coordinates are within country/state/county
	var lngValue = f.decimallongitude.value;
	var latValue = f.decimallatitude.value;
	if(latValue && lngValue){
		var url = "http://maps.googleapis.com/maps/api/geocode/json?latlng="+latValue+","+lngValue+"&sensor=false";
		
		$.ajax({
			type: "GET",
			url: "http://maps.googleapis.com/maps/api/geocode/json?sensor=false",
			dataType: "json",
			data: { latlng: latValue+","+lngValue }
		}).done(function( data ) {
			if(data){
				if(data.status != "ZERO_RESULTS"){
					var result = data.results[0];
					if(result.address_components){
						var compArr = result.address_components;
						var coordCountry = "";
						var coordState = "";
						var coordCounty = "";
						for (var p1 in compArr) {
							var compObj = compArr[p1];
							if(compObj.long_name && compObj.types){
								var longName = compObj.long_name;
								var types = compObj.types;
								if(types[0] == "country"){
									var coordCountry = longName;
								}
								else if(types[0] == "administrative_area_level_1"){
									var coordState = longName;
								}
								else if(types[0] == "administrative_area_level_2"){
									var coordCounty = longName;
								}
							}
						}
						var coordValid = true;
						if(f.country.value != ""){
							//if(f.country.value.toLowerCase().indexOf(coordCountry.toLowerCase()) == -1) coordValid = false;
						}
						else if(coordCountry != ""){
							f.country.value = coordCountry;
						}
						if(coordState != ""){
							if(f.stateprovince.value != ""){
								if(f.stateprovince.value.toLowerCase().indexOf(coordState.toLowerCase()) == -1) coordValid = false;
							}
							else{
								f.stateprovince.value = coordState;
							}
						}
						if(coordCounty != ""){
							var coordCountyIn = coordCounty.replace(" County","");
							coordCountyIn = coordCountyIn.replace(" Parish","");
							if(f.county.value != ""){
								var fCounty = f.county.value;
								if(f.county.value.toLowerCase().indexOf(coordCountyIn.toLowerCase()) == -1){
									if(f.county.value.toLowerCase() != coordCountyIn.toLowerCase()){
										coordValid = false;
									}
								}
							}
							else{
								f.county.value = coordCountyIn;
							}
						}
						if(!coordValid){
							var msg = "Are coordinates accurate? They currently map to: "+coordCountry+", "+coordState;
							if(coordCounty) msg = msg + ", " + coordCounty;
							msg = msg + ". Click globe symbol to display coordinates in map.";
							alert(msg);
						}
					}
				}
				else{
					if(f.country.value != ""){
						alert("Unable to identify country! Are coordinates accurate? Click globe symbol to display coordinates in map.");
					}
				}
			}
		});
	}
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
}

function verifyAssocImages(occidIn){
	$.ajax({
		type: "POST",
		url: "rpc/getassocimgcnt.php",
		dataType: "json",
		data: { occid: occidIn }
	}).done(function( imgCnt ) {
		document.getElementById("delverimgspan").style.display = "none";
		if(imgCnt > 0){
			document.getElementById("delimgfailspan").style.display = "block";
		}
		else{
			document.getElementById("delimgappdiv").style.display = "block";
		}
		imgAssocCleared = true;
		displayDeleteSubmit();
	});
}

function verifyAssocVouchers(occidIn){
	$.ajax({
		type: "POST",
		url: "rpc/getassocvouchers.php",
		dataType: "json",
		data: { occid: occidIn }
	}).done(function( vList ) {
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
	});
}

function displayDeleteSubmit(){
	if(imgAssocCleared && voucherAssocCleared){
		var elem = document.getElementById("delapprovediv");
		elem.style.display = "block";
	}
}

function eventDateChanged(eventDateInput){
	var dateStr = eventDateInput.value;
	if(dateStr != ""){
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
	
			//Invalid format is month > 12
			if(dateArr['m'] > 12){
				alert("Month cannot be greater than 12. Note that the format should be YYYY-MM-DD");
				return false;
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
	}
	fieldChanged('eventdate');
	var f = eventDateInput.form;
	if(!eventDateInput.form.recordnumber.value && f.recordedby.value) autoDupeSearch();
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

function verbatimEventDateChanged(vedObj){
	fieldChanged('verbatimeventdate');

	var f = vedObj.form;

	var vedValue = vedObj.value;
	vedValue = vedValue.replace(" - "," to ");
	vedValue = vedValue.replace(" / "," to ");
	
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
function initDetAutocomplete(f){
	$( f.sciname ).autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		change: function(event, ui) {
			if(f.sciname.value){
				pauseSubmit = true;
				verifyDetSciName(f);
			}
			else{
				f.scientificnameauthorship.value = "";
				f.family.value = "";
				f.tidtoadd.value = "";
			}				
		}
	});
}

function verifyDetSciName(f){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: f.sciname.value }
	}).done(function( data ) {
		if(data){
			f.scientificnameauthorship.value = data.author;
			f.family.value = data.family;
			f.tidtoadd.value = data.tid;
		}
		else{
			alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus. You can continue entering specimen and name will be add to thesaurus later.");
			f.scientificnameauthorship.value = "";
			f.family.value = "";
			f.tidtoadd.value = "";
		}
	});
} 

function detDateChanged(f){
	var isNew = false;
	var newDateStr = f.dateidentified.value;
	if(newDateStr){
		var dateIdentified = document.fullform.dateidentified.value;
		if(dateIdentified == "") dateIdentified = document.fullform.eventdate.value;
		if(dateIdentified){
			var yearPattern = /[1,2]{1}\d{3}/;
			var newYear = newDateStr.match(yearPattern);
			var curYear = dateIdentified.match(yearPattern);
			if(curYear && newYear && newYear[0] > curYear[0]){
				isNew = true;
			}
		}
		else{
			isNew = true;
		}
	}
	f.makecurrent.checked = isNew;
}

function verifyDetForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value");
		return false;
	}
	if(f.identifiedby.value == ""){
		alert("Determiner field must have a value (enter 'unknown' if not defined)");
		return false;
	}
	if(f.dateidentified.value == ""){
		alert("Determination Date field must have a value (enter 'unknown' if not defined)");
		return false;
	}
	if(f.sortsequence && !isNumeric(f.sortsequence.value)){
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

//Image tab form methods 
function verifyImgAddForm(f){
    if(f.elements["imgfile"].value.replace(/\s/g, "") == ""){
    	var imgUrl = f.elements["imgurl"].value.replace(/\s/g, "");
    	if(imgUrl == ""){
        	alert("Select an image file or enter a URL to an existing image");
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

function verifyImgRemapForm(f){
	if(f.targetoccid.value == ''){
		alert("Enter the occurrence record identifier (occid) of the occurrence record you want to transfer to");
		return false;
	}
	return true;
}

//Misc
function dwcDoc(dcTag){
	dwcWindow=open("http://symbiota.org/docs/symbiota-occurrence-data-fields-2/#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	//dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	if(dwcWindow.opener == null) dwcWindow.opener = self;
	dwcWindow.focus();
	return false;
}

function openOccurrenceSearch(target) {
	collId = document.fullform.collid.value;
	occWindow=open("../misc/occurrencesearch.php?targetid="+target+"&collid="+collId,"occsearch","resizable=1,scrollbars=1,toolbar=1,width=750,height=600,left=20,top=20");
	occWindow.focus();
	if (occWindow.opener == null) occWindow.opener = self;
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

function autoProcessingStatusChanged(selectObj){
	var selValue = selectObj.value;
	if(selValue){
		document.cookie = "autopstatus=" + selValue;
	}
	else{
		document.cookie = "autopstatus=;expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	}
}

function autoDupeChanged(dupeCbObj){
	if(dupeCbObj.checked){
		document.cookie = "autodupe=1";
	}
	else{
		document.cookie = "autodupe=;expires=Thu, 01 Jan 1970 00:00:01 GMT;";
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

function getCookie(cName){
	var i,x,y;
	var cookieArr = document.cookie.split(";");
	for(i=0;i<cookieArr.length;i++){
		x=cookieArr[i].substr(0,cookieArr[i].indexOf("="));
		y=cookieArr[i].substr(cookieArr[i].indexOf("=")+1);
		x=x.replace(/^\s+|\s+$/g,"");
		if (x==cName){
			return unescape(y);
		}
	}
}