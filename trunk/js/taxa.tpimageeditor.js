var targetImg = "";

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

function expandImages(){
	var divCnt = 0;
	var divObjs = document.getElementsByTagName("div");
	for (i = 0; i < divObjs.length; i++) {
		var obj = divObjs[i];
		if(obj.getAttribute("class") == "extraimg" || obj.getAttribute("className") == "extraimg"){
			if(obj.style.display=="none"){
				obj.style.display="inline";
				divCnt++;
				if(divCnt >= 5) break;
			}
		}
	}
}

function submitAddForm(f){
	var imgUploadPath = f.elements["userfile"].value.replace(/\s/g, "");
	if(imgUploadPath == "" ){
        if(f.elements["filepath"].value.replace(/\s/g, "") == ""){
			alert("File path must be entered");
			return false;
        }
    }
	if((imgUploadPath.indexOf(".jpg") == -1) && (imgUploadPath.indexOf(".JPG") == -1)){
		alert("Image file upload must be a JPG file (with a .jpg extension)");
		return false;
	}
    if(f.elements["photographeruid"].value.replace(/\s/g, "") == "" ){
        if(f.elements["photographer"].value.replace(/\s/g, "") == ""){
			alert("Please select the photographer from the pulldown or enter an override value");
			return false;
        }
    }
    if(isNumeric(f.sortsequence.value) == false){
		alert("Sort value must be a number");
		return false;
    }
    return true;
}

function submitEditForm(f){
    var errorText = "";

    if(f.elements["url"].value.replace(/\s/g, "") == "" ){
        errorText += "\nFile path must be entered";
    }
    if(errorText != ""){
        window.alert("Errors:\n " + errorText);
        return false;
    }
    return true;
}

function submitChangeTaxonForm(f){
	var sciName = f.elements["targettaxon"].value.replace(/^\s+|\s+$/g, ""); 
    if(sciName == ""){
        window.alert("Error: Enter a taxon name to which the image will be transferred");
    }
	else{
		checkScinameExistance(sciName);
	}
    return false;	//Submit takes place in the checkScinameExistance method
}

function initChangeTaxonList(input,tImg){
	targetImg = tImg;
	$(input).autocomplete({ ajax_get:getChangeTaxonList, minchars:3 });
}

function getChangeTaxonList(key,cont){ 
   	var script_name = 'rpc/gettaxonlist.php';
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
				document.getElementById("targettid-"+targetImg).value = renameTid;
				document.forms["changetaxonform-"+targetImg].submit();
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
		
