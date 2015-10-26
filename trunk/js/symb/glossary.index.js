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
					alert("The language you entered is not currently in the database, you can enter it as a new language but please make sure you spelled it correctly.");
				}
			}
		},{});
	
	$( "#newtranslanguage" )
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
					term: extractLast( request.term ), t: function() { return document.transnewform.newtranslanguage.value; }
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
					alert("The language you entered is not currently in the database, you can enter it as a new language but please make sure you spelled it correctly.");
				}
			}
		},{});
		
	$( "#taxagroup" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/taxalist.php", {
					term: extractLast( request.term ), t: function() { return document.termeditform.taxagroup.value; }
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
				document.getElementById('tid').value = ui.item.value;
				this.value = terms;
				return false;
			},
			change: function (event, ui) {
				if (!ui.item) {
					this.value = '';
					document.getElementById('tid').value = '';
					alert("You must select a name from the list.");
				}
			}
		},{});
});

function verifyNewTermForm(f){
	var term = document.getElementById("term").value;
	var language = document.getElementById("language").value;
	var tid = document.getElementById("tid").value;
	if(!term || !language || !tid){
		alert("Please enter at least the term, language, and taxonomic group.");
		return false;
	}
	if(document.getElementById("origterm")){
		var origterm = document.getElementById("origterm").value;
		var origlanguage = document.getElementById("origlang").value;
		var origtid = document.getElementById("origtid").value;
		if((origterm == term) && (origlanguage == language) && (origtid == tid)){
			return true;
		}
	}
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/checkterm.php?term="+term+"&language="+language+"&tid="+tid;
	
	var termList = 'null';
	
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			termList = JSON.parse(sutXmlHttp.responseText);
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	if(termList != 'null'){
		alert("Term already exists in database in that language and for that taxonomic group.");
		return false;
	}
	else{
		return true;
	}
}

function lookupNewsynonym(f){
	var term = document.getElementById("newsynterm").value;
	var language = document.getElementById("newsynlanguage").value;
	var tid = document.getElementById("newsyntid").value;
	var origterm = document.getElementById("synorigterm").value;
	
	if(term == origterm){
		document.getElementById("newsynterm").value = '';
		alert("Term must be different than current term.");
	}
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/checkrelation.php?term="+term+"&language="+language+"&tid="+tid;
	
	var termArr = [];
	
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			termArr = JSON.parse(sutXmlHttp.responseText);
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	if(termArr['glossid'] != null){
		document.getElementById("newsyndefinition").value = termArr['definition'];
		document.getElementById("newsyndefinition").disabled = false;
		document.getElementById("newsynsource").value = termArr['source'];
		document.getElementById("newsynsource").disabled = false;
		document.getElementById("newsynnotes").value = termArr['notes'];
		document.getElementById("newsynnotes").disabled = false;
		document.getElementById("synglossid").value = termArr['glossid'];
		document.getElementById("newsynglossgrpid").value = termArr['glossgrpid'];
	}
	else{
		document.getElementById("newsyndefinition").disabled = false;
		document.getElementById("newsynsource").disabled = false;
		document.getElementById("newsynnotes").disabled = false;
	}
}

function lookupNewtranslation(f){
	var term = document.getElementById("newtransterm").value;
	var language = document.getElementById("newtranslanguage").value;
	var tid = document.getElementById("newtranstid").value;
	var origlanguage = document.getElementById("transoriglanguage").value;
	
	if(language == origlanguage){
		document.getElementById("newtranslanguage").value = '';
		alert("This language is the same as the original term. If it is a synonym you are entering, please enter it under the Synonym tab.");
	}
	if(term && language){
		var sutXmlHttp=GetXmlHttpObject();
		if (sutXmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return;
		}
		
		var url="rpc/checkrelation.php?term="+term+"&language="+language+"&tid="+tid;
		
		var termArr = [];
		
		sutXmlHttp.onreadystatechange=function(){
			if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
				termArr = JSON.parse(sutXmlHttp.responseText);
			}
		};
		sutXmlHttp.open("POST",url,false);
		sutXmlHttp.send(null);
		if(termArr['glossid'] != null){
			document.getElementById("newtransdefinition").value = termArr['definition'];
			document.getElementById("newtransdefinition").disabled = false;
			document.getElementById("newtranssource").value = termArr['source'];
			document.getElementById("newtranssource").disabled = false;
			document.getElementById("newtransnotes").value = termArr['notes'];
			document.getElementById("newtransnotes").disabled = false;
			document.getElementById("transglossid").value = termArr['glossid'];
			document.getElementById("newtransglossgrpid").value = termArr['glossgrpid'];
		}
		else{
			document.getElementById("newtransdefinition").disabled = false;
			document.getElementById("newtranssource").disabled = false;
			document.getElementById("newtransnotes").disabled = false;
		}
	}
}

function verifyNewSynForm(f){
	var term = document.getElementById("newsynterm").value;
	if(!term){
		alert("Please enter at least the term.");
		return false;
	}
	return true;
}

function verifySearchForm(f){
	var language = document.getElementById("searchlanguage").value;
	var taxon = document.getElementById("searchtaxa").value;
	if(!language || !taxon){
		alert("Please select a language and taxonomic group to see term list.");
		return false;
	}
	return true;
}

function verifyNewTransForm(f){
	var term = document.getElementById("newtransterm").value;
	var language = document.getElementById("newtranslanguage").value;
	if(!term){
		alert("Please enter at least the term and language.");
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

function openTermPopup(glossid){
	var urlStr = 'individual.php?glossid='+glossid;
	newWindow = window.open(urlStr,'popup','toolbar=1,status=1,scrollbars=1,width=650,height=450,left=20,top=20');
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