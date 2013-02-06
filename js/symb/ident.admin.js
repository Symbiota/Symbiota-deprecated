$(document).ready(function() {
	if(!navigator.cookieEnabled){
		alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
	}

	$('#tabs').tabs({selected: tabIndex});

});

function selectAll(cb){
	boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var dbElements = document.getElementsByName("occid[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		dbElement.checked = boxesChecked;
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

function updateUnits(type) { 
	var type = document.getElementById('type').value;
	var units = document.getElementById('units');
	if ((type == 'IN') || (type == 'RN')) {
        document.getElementById('units').style.display = 'block';
    }else{
        document.getElementById('units').style.display = 'none';
    }
}

