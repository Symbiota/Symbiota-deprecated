$(document).ready(function() {

	$('#tabs').tabs({active: tabIndex});

	$("#parentstr").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/gettaxasuggest.php", { term: request.term, taid: document.taxauthidform.taxauthid.value, rhigh: document.taxoneditform.rankid.value }, response );
		},
		minLength: 3,
		autoFocus: true
	});

	$("#aefacceptedstr").autocomplete({ 
		source: "rpc/getacceptedsuggest.php",
		dataType: 'json',
		minLength: 3,
		autoFocus: true,
		change: function(event,ui) {
			if(ui.item == null && this.value.trim() != ""){
				alert("Name must be selected from list of accepted taxa currently in the system.");
				this.focus();
				this.form.tidaccepted.value = "";
			}
		},
		focus: function( event, ui ) {
			this.form.tidaccepted.value = ui.item.id;
		},
		select: function( event, ui ) {
			this.form.tidaccepted.value = ui.item.id;
		}
	});

	$("#ctnafacceptedstr").autocomplete({ 
		source: "rpc/getacceptedsuggest.php",
		dataType: 'json',
		minLength: 3,
		autoFocus: true,
		change: function(event,ui) {
			if(ui.item == null && this.value.trim() != ""){
				alert("Name must be selected from list of accepted taxa currently in the system.");
				this.focus();
				this.form.tidaccepted.value = "";
			}
		},
		focus: function( event, ui ) {
			this.form.tidaccepted.value = ui.item.id;
		},
		select: function( event, ui ) {
			this.form.tidaccepted.value = ui.item.id;
		}
	});
});

function toggleEditFields(){
  	toggle('editfield');
	toggle('kingdomdiv');
}

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			ele.style.display="";
  		}
	 	else {
	 		ele.style.display="none";
	 	}
	}
	else{
	  	var divs = document.getElementsByTagName("div");
	  	var i;
	  	for(i = 0; i < divs.length; i++) {
		  	var divObj = divs[i];
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
	  	var j;
	  	for(j = 0; j < spans.length; j++) {
		  	var spanObj = spans[j];
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
}

function validateTaxonEditForm(f){
	if(f.unitname1.value.trim() == ""){
		alert('Unitname 1 field must have a value');
		return false;
	}
	return true;
}

function verifyChangeToNotAcceptedForm(f){
	if(f.acceptedstr.value == ""){
		alert("Please enter an accepted name to which this taxon will be linked!");
		return false;
	}
	else if(f.tidaccepted.value == "" || f.tidaccepted.value == "undefined"){
		alert("Taxon entered appears not to be in thesaurus or is not listed as an accepted taxon. Name must be selected from list.");
		return false;		
	}
	$.ajax({
		type: "POST",
		url: "rpc/getchildaccepted.php",
		dataType: "json",
		data: { tid: f.tid.value, tidaccepted: f.taxauthid.value }
	}).done(function( retJSON ) {
		if(retJSON){
			alert("ERROR: Name can't be changed to non-accepted until accepted child taxa are reassigned");
			var outStr = '';
			$.each( retJSON, function(key,value){
				outStr = outStr + '<a href="taxoneditor.php?tid=' + key + '" target="_blank">' + value + ' <img src="../../images/edit.png" style="width:12px" /></a><br/>';
			});
			$("#ctnaError").html(outStr);
			$("#ctnaError").show();
		}
		else{
			f.submit();
		}
	});	
	return false;
}

function verifyLinkToAcceptedForm(f){
	if(f.acceptedstr.value == ""){
		alert("Please enter an accepted name to which this taxon will be linked!");
		return false;
	}
	else if(f.tidaccepted.value == "" || f.tidaccepted.value == "undefined"){
		alert("Taxon entered appears not to be in thesaurus or is not listed as an accepted taxon. Name must be selected from list.");
		return false;		
	}
	return true;
}

function submitTaxStatusForm(f){
	$.ajax({
		type: "POST",
		url: "rpc/gettid.php",
		data: { sciname: f.parentstr.value }
	}).done(function( msg ) {
		if(msg == 0){
			alert("ERROR: Parent taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus.");
		}
		else{
			f.parenttid.value = msg;
			f.submit();
		}
	});
}