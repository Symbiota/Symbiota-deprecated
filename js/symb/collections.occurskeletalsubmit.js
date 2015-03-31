var sec = 0;
var count = 0;

$(document).ready(function() {

	$("#fsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		autoFocus: true,
		change: function(event, ui) {
			$( "#ftidinterpreted" ).val("");
			$( '#fscientificnameauthorship' ).val("");
			$( '#ffamily' ).val("");
			$( '#flocalitysecurity' ).prop('checked', false);
			if($( "#fsciname" ).val()){
				verifySciName();
			}
		}
	});

	//Misc fields with lookups
	$("#fcountry").autocomplete({
		source: "rpc/lookupCountry.php", 
		minLength: 2,
		autoFocus: true
	});

	$("#fstateprovince").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/lookupState.php", { term: request.term, country: document.defaultform.country.value }, response );
		},
		minLength: 2,
		autoFocus: true
	});

	$("#fcounty").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/lookupCounty.php", { term: request.term, "state": document.defaultform.stateprovince.value }, response );
		},
		minLength: 2,
		autoFocus: true
	});

	//Initiate timer
	setInterval( function(){
		$("#seconds").html(pad(++sec%60));
		$("#minutes").html(pad(parseInt(sec/60,10)));
	}, 1000);
});

//Field changed and verification functions
function verifySciName(){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: $( "#fsciname" ).val() }
	}).done(function( data ) {
		if(data){
			$( "#ftidinterpreted" ).val(data.tid);
			$( '#ffamily' ).val(data.family);
			$( '#fscientificnameauthorship' ).val(data.author);
			if(data.status == 1){ 
				$( '#flocalitysecurity' ).prop('checked', true);
			}
			else{
				if(data.tid){
					var stateVal = $( '#fstateprovince' ).val();
					if(stateVal != ""){
						localitySecurityCheck($( "#faultform" ));
					}
				}
			}
		}
		else{
			alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus. You can continue entering specimen and name will be add to thesaurus later.");
		}
	});
}

function localitySecurityCheck(f){
	var tidIn = $( "#ftidinterpreted" ).val();
	var stateIn = $( "#stateprovince" ).val();
	if(tidIn != "" && stateIn != ""){
		$.ajax({
			type: "POST",
			url: "rpc/localitysecuritycheck.php",
			dataType: "json",
			data: { tid: tidIn, state: stateIn }
		}).done(function( data ) {
			if(data == "1"){
				$( '#flocalitysecurity' ).prop('checked', true);
			}
		});
	}
}

function stateProvinceChanged(stateVal){ 
	var tidVal = $( "#ftidinterpreted" ).val();
	if(tidVal != "" && stateVal != ""){
		localitySecurityCheck($( "#faultform" ));
	}
}

function submitDefaultForm(f){
	if($( "#fcatalognumber" ).val() != ""){
		//Add new occurrence 
		$.ajax({
			type: "POST",
			url: "rpc/occuradd.php",
			data: { 
				sciname: $( "#fsciname" ).val(), 
				scientificnameauthorship: $( "#fscientificnameauthorship" ).val(), 
				family: $( "#ffamily" ).val(), 
				localitysecurity: $( "#flocalitysecurity" ).val(),
				country: $( "#fcountry" ).val(), 
				stateprovince: $( "#fstateprovince" ).val(), 
				county: $( "#fcounty" ).val(), 
				processingstatus: $( "#fprocessingstatus" ).val(), 
				catalognumber: $( "#fcatalognumber" ).val(),
				collid: $( "#fcollid" ).val()
			}
		}).done(function( retStr ) {
			if(isNumeric(retStr)){
				var newDiv = createOccurDiv($( "#fcatalognumber" ).val(), retStr);

				var listElem = document.getElementById("occurlistdiv");
				//listElem.appendChild(newDiv);
				listElem.insertBefore(newDiv,listElem.childNodes[0]);

				incrementCount();
				catalognumber: $( "#fcatalognumber" ).val("");
			}
			else if(retStr.substring(0,6) == "dupcat"){
				if(confirm("Another record exists with the same catalog number, which is not allowed. Do you want to view the other record(s)?")){
					openEditPopup(retStr.substring(7));
				}
			}
			else{
				alert(retStr);
			}
		});
	}
	
	$( "#fcatalognumber" ).focus();
	return false;
}

function createOccurDiv(catalogNumber, occid){

	var newAnchor = document.createElement('a');
	newAnchor.setAttribute("id", "a-"+occid);
	newAnchor.setAttribute("href", "#");
	newAnchor.setAttribute("onclick", "openEditPopup("+occid+");return false;");
	var newText = document.createTextNode(catalogNumber);
	newAnchor.appendChild(newText);

	var newDiv = document.createElement('div');
	newDiv.setAttribute("id", "o-"+occid);
	newDiv.appendChild(newAnchor);

	//Image submission  
	/*
	var newInput = document.createElement('input');
	newInput.setAttribute("type", "hidden");
	newInput.setAttribute("name", "occid[]");
	newInput.setAttribute("value", occid);
	*/
	return newDiv;
}

function deleteOccurrence(occid){
	if(imgAssocCleared && voucherAssocCleared && surveyAssocCleared){
		var elem = document.getElementById("delapprovediv");
		elem.style.display = "block";
	}
}

function openEditPopup(occidStr){
	collid = $( "#fcollid" ).val();
	var urlStr = "occurrenceeditor.php?collid="+collid+"&q_catalognumber=occid"+occidStr+"&occindex=0";
	var wWidth = 900;
	if(document.getElementById('maintable').offsetWidth){
		wWidth = document.getElementById('maintable').offsetWidth*1.05;
	}
	else if(document.body.offsetWidth){
		wWidth = document.body.offsetWidth*0.9;
	}
	var newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if(newWindow != null){
		if (newWindow.opener == null) newWindow.opener = self;
	}
	else{
		alert("Unable to display record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
	return false;
}

//Misc functions
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

function pad( val ){ 
	return val > 9 ? val : "0" + val; 
}

function incrementCount(){
	$("#count").html(++count);
	$("#rate").html(Math.round(3660*count/sec));
}