window.onload = function(){
	//Set eventDate to today as default 
	var monthNames = new Array(
	"January","February","March","April","May","June","July",
	"August","September","October","November","December");
	var now = new Date();
	dateStr = now.getDate() + " " +	monthNames[now.getMonth()] + " " + now.getFullYear();
	if(document.obsform.eventdate.value == ""){
		document.obsform.eventdate.value = dateStr;
	}
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

function submitObsForm(f){
    if(f.sciname.value == ""){
		window.alert("Observation must have an identification (scientific name) assigned to it, even if it is only to family rank.");
		return false;
    }
    if(f.recordedby.value == ""){
		window.alert("Observer field must have a value.");
		return false;
    }
    if(f.eventdate.value == ""){
		window.alert("Observation date must have a value.");
		return false;
    }
    if(f.locality.value == ""){
		window.alert("Locality must have a value to submit an observation.");
		return false;
    }
    if(f.decimallatitude.value == "" || f.decimallongitude.value == ""){
		window.alert("Latitude and Longitude must have a value to submit an observation. Note that one can submit an image without a locality definition through the Taxon Profile page.");
		return false;
    }
    if(isNumeric(f.decimallatitude.value) == false){
		window.alert("Latitude must be in the decimal format with numeric characters only (34.5335). ");
		return false;
    }
    if(isNumeric(f.decimallongitude.value) == false){
		window.alert("Longitude must be in the decimal format with numeric characters only. Note that the western hemisphere is represented as a negitive number (-110.5335). ");
		return false;
    }
    if(isNumeric(f.coordinateuncertaintyinmeters.value) == false){
		window.alert("Coordinate Uncertainty must be a numeric value only. ");
		return false;
    }
    if(isNumeric(f.minimumelevationinmeters.value) == false){
		window.alert("Elevation must be a numeric value only. ");
		return false;
    }
    if(f.imgfile1.value == ""){
   		window.alert("An observation submitted through this interface must be documented with an image.");
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
	if(document.obsform.family.value == ""){
		var sciNameStr = document.obsform.sciname.value;
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
					document.obsform.scientificnameauthorship.value = retObj.author;
					document.obsform.family.value = retObj.family;
				}
				else{
					document.obsform.scientificnameauthorship.value = "";
					document.obsform.family.value = "";
					alert("Taxon not found. Maybe misspelled or needs to be added to taxonomic thesaurus.");
					snXmlHttp = null;
				}
			}
		};
		snXmlHttp.open("POST",url,true);
		snXmlHttp.send(null);
	}
} 

function scinameChanged(){
	document.obsform.scientificnameauthorship.value = "";
	document.obsform.family.value = "";
}

function verifyDate(eventDateInput){
	var dateStr = eventDateInput.value;
	//test date and return mysqlformat

	
}

function openPointMap() {
    mapWindow=open("../mappointradius.php","pointradius","resizable=0,width=650,height=600,left=20,top=20");
	if (mapWindow.opener == null) mapWindow.opener = self;
}

function verifyLatValue(inputObj){
	inputIsNumeric(inputObj, 'Decimal Latitude');
	if(inputObj.value > 90 || inputObj.value < -90){
		alert('Decimal latitude value should be between -90 and 90 degrees');
	}
}

function verifyLngValue(inputObj){
	inputIsNumeric(inputObj, 'Decimal Longitude');
	if(inputObj.value > 180 || inputObj.value < -180){
		alert('Decimal longitude value should be between -180 and 180 degrees');
	}
}

function verifyElevValue(inputObj){
	inputIsNumeric(inputObj, 'Coordinate Uncertainty');
	if(inputObj.value > 4000){
		alert('Are you sure your elevation value in meters. ' + inputObj.value + ' meters is a very high elevation.');
	}
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
