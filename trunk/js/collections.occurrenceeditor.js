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

function toggleIdDetails(){
	toggle("idrefdiv");
	toggle("idremdiv");
}

function openUtmPopup() {

}

//Form verification code
function submitFullForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value. Enter closest know identification, even if it's only to family, order, or above. ");
		return false;
	}
	if(f.recordedby.value == ""){
		alert("Collector field must have a value. Enter 'unknown' if needed.");
		return false;
	}
	if(!verifyDate(f.eventdate)){
		return false;
	}
	if(f.country.value == ""){
		alert("Country field must have a value");
		return false;
	}
	if(f.stateprovince.value == ""){
		alert("State field must have a value");
		return false;
	}
	if(f.locality.value == ""){
		alert("Locality field must have a value");
		return false;
	}

	return true;
}

function submitDetEditForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value");
		return false;
	}
	if(f.identifiedby.value == ""){
		alert("Determiner field must have a value");
		return false;
	}
	if(f.dateidentified.value == ""){
		alert("Determination Data field must have a value");
		return false;
	}
	if(!isNumeric(f.sortsequence.value)){
		alert("Sort Sequence field must be a numeric value only");
		return false;
	}
	return true;
}

function submitImgAddForm(f){
    if(f.elements["imgfile"].value.replace(/\s/g, "") == "" ){
        if(f.elements["imgurl"].value.replace(/\s/g, "") == ""){
        	window.alert("Select an image file or enter a URL to an existing image");
			return false;
        }
    }
    return true;
}

function submitImgEditForm(f){
	if(f.url.value == ""){
		alert("Web URL field must have a value");
		return false;
	}
	return true;
}

function submitImgDelForm(f){
	if(confirm('Are you sure you want to delete this image? Note that the physical image will be deleted from the server if checkbox is selected.')){
		return true;
	}
	return false;
}

//Occurrence field checks
function verifySciName(f){
	if(f.scientificnameauthorship.value == ""){
		var sciNameStr = f.sciname.value;
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
					f.scientificnameauthorship.value = retObj.author;
					f.family.value = retObj.family;
				}
				else{
					f.scientificnameauthorship.value = "";
					f.family.value = "";
					f.identifiedby.focus();
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
	var validformat=/^\d{4}-\d{2}-\d{2}$/ //Format: yyyy-mm-dd
	if(!validformat.test(dateStr)){
		alert("Invalid Date Format. Please correct to follow this format: yyyy-mm-dd");
		return false;
	}
	return true;
}

function inputIsNumeric(inputObj, titleStr){
	if(!isNumeric(inputObj.value)){
		alert("Input value for " + titleStr + " must be a number value only! " );
	}
}

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


//Misc
function initTaxonList(input){
	$(input).autocomplete({ ajax_get:getTaxonSuggs, minchars:3 });
}

function initDetTaxonList(input){
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
