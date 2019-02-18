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
		imgUploadPath = f.elements["filepath"].value.replace(/\s/g, "");
        if(imgUploadPath == ""){
			alert("File path must be entered");
			return false;
        }
    }
	if((imgUploadPath.indexOf(".jpg") == -1) && (imgUploadPath.indexOf(".JPG") == -1) && (imgUploadPath.indexOf(".jpeg") == -1) && (imgUploadPath.indexOf(".JPEG") == -1)){
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
		
