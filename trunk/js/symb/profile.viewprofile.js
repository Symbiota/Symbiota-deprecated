$(document).ready(function() {
	$('#tabs').tabs(
		{ selected: tabIndex }
	);
});

function openMappingAid() {
    mapWindow=open("../tools/mappointaid.php?formname=checklistaddform&latname=ncllatcentroid&longname=ncllongcentroid","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function checkEditForm(f){
    var errorText = "";
    if(f.firstname.value.replace(/\s/g, "") == "" ){
        errorText += "\nFirst Name";
    };
    if(f.lastname.value.replace(/\s/g, "") == "" ){
        errorText += "\nLast Name";
    };
    if(f.state.value.replace(/\s/g, "") == "" ){
        errorText += "\nState";
    };
    if(f.country.value.replace(/\s/g, "") == "" ){
        errorText += "\nCountry";
    };
    if(f.email.value.replace(/\s/g, "") == "" ){
        errorText += "\nEmail";
    };

    if(errorText == ""){
        return true;
    }
    else{
        window.alert("The following fields must be filled out:\n " + errorText);
        return false;
    }
}

function checkPwdForm(f){
    var pwd1 = f.newpwd.value.replace(/\s/g, "");
    var pwd2 = f.newpwd2.value.replace(/\s/g, "");
    if(pwd1 == "" || pwd2 == ""){
        window.alert("Both password fields must contain a value.");
        return false;
    }
    if(pwd1 != pwd2){
        window.alert("Password do not match. Please enter again.");
        f.newpwd.value = "";
        f.newpwd2.value = "";
        f.newpwd.focus();
        return false;
    }
    return true;
}

function checkNewLoginForm(f){
    var pwd1 = f.newloginpwd.value.replace(/\s/g, "");
    var pwd2 = f.newloginpwd2.value.replace(/\s/g, "");
    if(pwd1 == "" || pwd2 == ""){
        window.alert("Both password fields must contain a value.");
        return false;
    }
    if(pwd1 != pwd2){
        window.alert("Password do not match. Please enter again.");
        f.newloginpwd.value = "";
        f.newloginpwd2.value = "";
        f.newloginpwd.focus();
        return false;
    }
    return true;
}

function deleteLogin(userId,login){
    if(window.confirm('Are you sure you want to delete '+login+' as a Login?')){
		dlXmlHttp = GetXmlHttpObject();
		if(dlXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return;
	  	}
		var url = "rpc/deletelogin.php";
		url=url + "?userid=" + userId + "&login=" + login;
		url=url + "&sid="+Math.random();
		document.getElementById("un-"+login).style.display = "none";
		dlXmlHttp.open("POST",url,true);
		dlXmlHttp.send(null);
    }
}

function verifyClAddForm(f){
	if(f.nclname.value == ""){
		alert("The Checklist Name field must have a value before a new checklist can be created");
		return false;
	}
	if(!isNumeric(f.ncllatcentroid.value)){
		alert("The Latitude Centriod field must contain a numeric value only");
		return false;
	}
	if(!isNumeric(f.ncllongcentroid.value)){
		alert("The Longitude Centriod field must contain a numeric value only");
		return false;
	}
	if(!isNumeric(f.nclpointradiusmeters.value)){
		alert("The Point Radius field must contain only a numeric value");
		return false;
	}
	return true;
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
	  	for(var h = 0; h < divs.length; h++) {
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
	
	  	var spans = document.getElementsByTagName("span");
	  	for(var i = 0; i < spans.length; i++) {
	  		var spanObj = spans[i];
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
	return false;
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
