$(document).ready(function() {
	if(!navigator.cookieEnabled){
		alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
	}
	
	$('#tabs').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});

	resetLanguageSelect(document.searchform);

	$( "#taxagroup" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "ui-autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				var reqTerm = request.term;
				reqTerm = reqTerm.split( /,\s*/ );
				reqTerm = reqTerm.pop();
				$.getJSON( "rpc/taxalist.php", {
					term: reqTerm, t: 'single'
				}, response );
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = this.value.split( /,\s*/ );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.label );
				document.getElementById('tid').value = ui.item.value;
				this.value = terms;
				return false;
			},
			change: function (event, ui) {
				if(!ui.item && this.value != "") {
					document.getElementById('tid').value = '';
					alert("You must select a name from the list.");
				}
				else if (document.getElementById(ui.item.label)) {
					this.value = '';
					document.getElementById('tid').value = '';
					alert("Taxonomic group has already been added.");
				}
			}
		},{});
});

function resetLanguageSelect(f){
	if($("#searchlanguage").is('select')){ 
		var tid = f.searchtaxa.value;
		if(tid == '') tid = 0;
		var oldLang = $("#searchlanguage").val();
		$("#searchlanguage").empty();
		$.each(langArr[tid], function(key,value) {
			$("#searchlanguage").append($("<option></option>").attr("value", value).text(value));
		});
		$("#searchlanguage").val(oldLang);
	}
}

function addNewLang(f){
	var newLangStr = f.newlang.value;
	var langObjId = f.language.id;
	if(newLangStr){ 
		$("#" + langObjId).append($("<option></option>").attr("value", newLangStr).text(newLangStr));
		$("#" + langObjId).val(newLangStr);
	}
	toggle('addLangDiv');
}

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
	}
}
