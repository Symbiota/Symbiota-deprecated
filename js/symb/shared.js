function isNumeric(inStr){
   	var validChars = "0123456789-.";
   	var isNumber = true;
   	var charVar;

   	for(var i = 0; i < inStr.length && isNumber == true; i++){ 
   		charVar = inStr.charAt(i); 
		if(validChars.indexOf(charVar) == -1){
			isNumber = false;
			break;
      	}
   	}
	return isNumber;
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
