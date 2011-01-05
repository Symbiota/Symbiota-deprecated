function initTabs(tabObjId){
	var dTabs=new ddtabcontent(tabObjId); 
	dTabs.setpersist(true);
	dTabs.setselectedClassTarget("link"); 
	dTabs.init();
}

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			ele.style.display="block";
  		}
	 	else {
	 		ele.style.display="none";
	 	}
	}
	else{
		var divObjs = document.getElementsByTagName("div");
	  	for (i = 0; i < divObjs.length; i++) {
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

function toggleSpan(target){
	var spanObjs = document.getElementsByTagName("span");
	var objLen = spanObjs.length;
  	for (var i = 0; i < objLen; i++) {
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
}

function toggleIdDetails(){
	toggle("idrefdiv");
	toggle("taxremdiv");
}

function submitAddImageForm(f){
    if(f.elements["imgfile"].value.replace(/\s/g, "") == "" ){
        if(f.elements["imgurl"].value.replace(/\s/g, "") == ""){
        	window.alert("Select an image file or enter a URL to an existing image");
			return false;
        }
    }
    if(isNumeric(f.sortsequence.value) == false){
		window.alert("Sort value must be a number");
		return false;
    }
    return true;
}

function initTaxonList(input){
	$(input).autocomplete({ ajax_get:getTaxonSuggs, minchars:3 });
}

function getTaxonSuggs(key,cont){ 
   	var script_name = 'rpc/getspecies.php';
   	var params = { 'q':key }
   	$.get(script_name,params,
		function(obj){
			// obj is just array of strings
			var res = [];
			for(var i=0;i<obj.length;i++){
				res.push({ id:i , value:obj[i]});
			}
			// will build suggestions list
			cont(res);
		},
	'json');
}

function verifySciName(){
	if(document.fullform.family.value == ""){
		var sciNameStr = document.fullform.sciname.value;
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
					document.fullform.scientificnameauthorship.value = retObj.author;
					document.fullform.family.value = retObj.family;
				}
				else{
					document.fullform.scientificnameauthorship.value = "";
					document.fullform.family.value = "";
					alert("Taxon not found. Maybe misspelled or needs to be added to taxonomic thesaurus.");
				}
			}
		};
		snXmlHttp.open("POST",url,true);
		snXmlHttp.send(null);
	}
} 

function scinameChanged(){
	document.fullform.scientificnameauthorship.value = "";
	document.fullform.family.value = "";
}

function verifyDate(eventDateInput){
	var dateStr = eventDateInput.value;
	//test date and return mysqlformat

	
}

function inputIsNumeric(inputObj, titleStr){
	if(!isNumeric(inputObj.value)){
		alert("Input value for " + titleStr + " must be a number value only! " );
	}
}

function isNumeric(sText){
   	var ValidChars = "0123456789-.";
   	var IsNumber = true;
   	var Char;
 
   	for(var i = 0; i < sText.length && IsNumber == true; i++){ 
	   Char = sText.charAt(i); 
		if(ValidChars.indexOf(Char) == -1){
			IsNumber = false;
			break;
      	}
   	}
	return IsNumber;
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
