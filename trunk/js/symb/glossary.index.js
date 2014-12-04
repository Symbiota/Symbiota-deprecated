$(document).ready(function() {
	if(!navigator.cookieEnabled){
		alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
	}

	$('#tabs').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});
	
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	$( "#language" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/languagelist.php", {
					term: extractLast( request.term ), t: function() { return document.termeditform.language.value; }
				}, response );
			},
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.label );
				this.value = terms;
				return false;
			},
			change: function (event, ui) {
				if (!ui.item) {
					alert("The language you entered is not currently in the database, please make sure you spelled it correctly.");
				}
			}
		},{});
		
	$( "#searchlanguage" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/languagelist.php", {
					term: extractLast( request.term ), t: function() { return document.filtertermform.searchlanguage.value; }
				}, response );
			},
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.label );
				this.value = terms;
				return false;
			}
		},{});
	
});

function verifyNewTermForm(f){
	if(document.getElementById("term").value == "" || document.getElementById("definition").value == "" || document.getElementById("language").value == ""){
		alert("Please enter the term, definition, and language.");
		return false;
	}
	return true;
}

function verifyNewImageForm(f){
	if(!document.getElementById("imgfile").files[0] && document.getElementById("imgurl").value == ""){
		alert("Please either upload an image or enter the url of an existing image.");
		return false;
	}
	return true;
}

function verifyImageEditForm(f){
	if(document.getElementById("editurl").value == ""){
		document.getElementById("editurl").value = document.getElementById("oldurl").value;
		alert("Please enter a url for the image to save.");
		return false;
	}
	return true;
}

function verifyNewTerm(f){
	var term = f.term.value;
	if(term){
		var sutXmlHttp=GetXmlHttpObject();
		if (sutXmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return;
		}
		
		var url="rpc/checkterm.php?term="+term;
		
		var termList = 'null';
		
		sutXmlHttp.onreadystatechange=function(){
			if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
				termList = JSON.parse(sutXmlHttp.responseText);
			}
		};
		sutXmlHttp.open("POST",url,false);
		sutXmlHttp.send(null);
		if(termList != 'null'){
			f.term.value = '';
			alert("Term already exists in database, please select it from the list below to edit.");
		}
	}
}

function openTermPopup(glossid){
	var urlStr = 'individual.php?glossid='+glossid;
	newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width=710,height=400,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function leaveTermPopup(urlStr){
	self.close();
	opener.document.location = urlStr;
	return false;
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