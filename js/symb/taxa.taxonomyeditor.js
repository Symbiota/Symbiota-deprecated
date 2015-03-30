var rankLimit;
var rankLow;
var rankHigh;
var taxAuthId;

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
		minLength: 3,
		autoFocus: true
	});

	$("#ctnafacceptedstr").autocomplete({ 
		source: "rpc/getacceptedsuggest.php",
		minLength: 3,
		autoFocus: true
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

function deleteAcceptedLink(tidAcc){
	if(tidAcc == null){
  		return;
  	}
	var dalXmlHttp=GetXmlHttpObject();
	if(dalXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/deleteacceptedlink.php";
	url=url+"?tid="+tid;
	url=url+"&tidaccepted="+tidAcc;
	url=url+"&sid="+Math.random();
	dalXmlHttp.onreadystatechange=function(){
		if(dalXmlHttp.readyState==4 && dalXmlHttp.status==200){
			status = dalXmlHttp.responseText;
			if(status == "0"){
				alert("FAILED: sorry, error while attempting to delete accepted link");
			}
			else{
				document.getElementById("acclink-"+tidAcc).style.display = "none";
			}
		}
	}
	dalXmlHttp.open("POST",url,true);
	dalXmlHttp.send(null);
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

function verifyAcceptEditsForm(f){
	if(f.acceptedstr.value == ""){
		alert("Please enter an accepted name to link this name to!");
		return false;
	}
	submitLinkToAccepted(f);
	return false;
	//Form submission will take place within the submitLinkToAccepted method
}

function verifyChangeToNotAcceptedForm(f){
	if(f.acceptedstr.value == ""){
		alert("Please enter an accepted name to link this name to!");
		return false;
	}
	submitLinkToAccepted(f);
	return false;
	//Form submission will take place within the submitLinkToAccepted method
}

function submitLinkToAccepted(f){
	//Used by more than one form
	var testStr = f.acceptedstr.value;
	var snXmlHttp=GetXmlHttpObject();
	if(snXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/gettid.php";
	url=url+"?sciname="+testStr;
	snXmlHttp.onreadystatechange=function(){
		if(snXmlHttp.readyState==4 && snXmlHttp.status==200){
			var accTid = snXmlHttp.responseText;
			if(accTid){
				f.tidaccepted.value = accTid;
				f.submit();
			}
			else{
				alert("ERROR: Accepted taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus.");
			}
		}
	};
	snXmlHttp.open("POST",url,true);
	snXmlHttp.send(null);
}

function submitTaxStatusForm(f){
	var parStr = f.parentstr.value;
	if(parStr == null){
  		f.submit();
  	}
	else{
		var snXmlHttp=GetXmlHttpObject();
		if(snXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var url="rpc/gettid.php";
		url=url+"?sciname="+parStr;
		snXmlHttp.onreadystatechange=function(){
			if(snXmlHttp.readyState==4 && snXmlHttp.status==200){
				var parentTid = snXmlHttp.responseText;
				if(parentTid){
					f.parenttid.value = parentTid;
					f.submit();
				}
				else{
					alert("ERROR: Parent taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus.");
				}
			}
		}
		snXmlHttp.open("POST",url,true);
		snXmlHttp.send(null);
	}
}
