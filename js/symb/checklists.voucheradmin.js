$('html').hide();
$(document).ready(function() {
	$('html').show();
});

$(document).ready(function() {
	$('#tabs').tabs({ 
		active: tabIndex,
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});
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

function initAutoComplete(formElem){
	$("#"+formElem).autocomplete({
		source: function( request, response ){
			$.ajax({
				url: "rpc/clspeciessuggest.php",
				dataType: "json",
				data: {
					term : request.term,
					cl : $('#clvalue').val() 
				},
				success: function(data) {
					response(data);
				}
			});
        },
		minLength: 3 
	});
}

function linkVoucher(occidIn, clidIn){
	$.ajax({
		type: "POST",
		url: "rpc/linkvoucher.php",
		data: { clid: clidIn, occid: occidIn, sciname: document.getElementById("tid-"+occidIn).value }
	}).done(function( msg ) {
		if(msg == 1){
			alert("Voucher linked successfully!");
		}
		else if(msg == 2){
			alert("Specimen already a voucher for checklist");
		}
		else{
			alert("Voucher link failed: "+msg);
		}
	});
}

//Validate form functions
function validateSqlFragForm(f){
	if(!isNumeric(f.latnorth.value) || !isNumeric(f.latsouth.value) || !isNumeric(f.lngwest.value) || !isNumeric(f.lngeast.value)){
		alert("Latitude and longitudes values muct be numeric values only");
		return false;
	}
	return true;
}

function validateBatchNonVoucherForm(f){
	var dbElements = document.getElementsByName("occids[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		if(dbElement.checked) return true;
	}
   	alert("Please select at least one specimen to link as a voucher!");
  	return false;
}

function validateBatchMissingForm(f){
	var dbElements = document.getElementsByName("occids[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		if(dbElement.checked) return true;
	}
   	alert("Please select at least one specimen to link as a voucher!");
  	return false;
}


//Misc functions
function selectAll(cb){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var cName = cb.className;
	var dbElements = document.getElementsByName("occids[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		if(dbElement.className == cName){
			dbElement.checked = boxesChecked;
		}
		else{
			dbElement.checked = false;
		}
	}
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
