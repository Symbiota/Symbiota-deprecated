$(document).ready(function() {
	$("#targettaxon").autocomplete({ source: "rpc/gettaxasuggest.php" },{ minLength: 3, autoFocus: true } );
});

function toggle(target){
	var obj = document.getElementById(target);
	if(obj){
		if(obj.style.display=="none"){
			obj.style.display="block";
		}
		else {
			obj.style.display="none";
		}
	}
	else{
		var spanObjs = document.getElementsByTagName("span");
		for (i = 0; i < spanObjs.length; i++) {
			var spanObj = spanObjs[i];
			if(spanObj.getAttribute("class") == target || spanObj.getAttribute("className") == target){
				if(spanObj.style.display=="none"){
					spanObj.style.display="inline";
				}
				else {
					spanObj.style.display="none";
				}
			}
		}

		var divObjs = document.getElementsByTagName("div");
		for (var i = 0; i < divObjs.length; i++) {
			var divObj = divObjs[i];
			if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
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

function verifyEditForm(f){
    if(f.url.value.replace(/\s/g, "") == "" ){
        window.alert("ERROR: File path must be entered");
        return false;
    }
    return true;
}

function verifyChangeTaxonForm(f){
	var sciName = f.targettaxon.value.replace(/^\s+|\s+$/g, ""); 
    if(sciName == ""){
        window.alert("ERROR: Enter a taxon name to which the image will be transferred");
    }
	else{
		checkScinameExistance(sciName);
	}
    return false;	//Submit takes place in the checkScinameExistance method
}

function checkScinameExistance(sciname){
	if (sciname.length == 0){
  		return;
  	}
	cseXmlHttp=GetXmlHttpObject();
	if (cseXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/gettid.php";
	url=url+"?sciname="+sciname;
	url=url+"&sid="+Math.random();
	
	cseXmlHttp.onreadystatechange=function(){
		if(cseXmlHttp.readyState==4 && cseXmlHttp.status==200){
			renameTid = cseXmlHttp.responseText;
			if(renameTid == ""){
				alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? It may have to be added to database.");
			}
			else{
				document.getElementById("targettid").value = renameTid;
				document.changetaxonform.submit();
			}
		}
	};
	cseXmlHttp.open("POST",url,true);
	cseXmlHttp.send(null);
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

function openOccurrenceSearch(target) {
	occWindow=open("occurrencesearch.php?targetid="+target,"occsearch","resizable=1,scrollbars=1,width=530,height=500,left=20,top=20");
	if (occWindow.opener == null) occWindow.opener = self;
}

