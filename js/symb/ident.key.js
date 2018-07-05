function toggleAll(){
	toggleChars("dynam");
	toggleChars("dynamControl");
}

function toggleChars(name){
  var chars = document.getElementsByTagName("div");
  for (i = 0; i < chars.length; i++) {
  	var obj = chars[i];
		if(obj.className == name){
			if(obj.style.display=="none"){
				obj.style.display="block";
				setCookie("all");
			}
		 	else {
		 		obj.style.display="none";
				setCookie("limited");
		 	}
		}
  }
}

function setCookie(status){
	document.cookie = "showchars=" + status;		
}

function getCookie(name){
	var pos = document.cookie.indexOf(name + "=");
	if(pos == -1){
		return null;
	} else {
		var pos2 = document.cookie.indexOf(";", pos);
		if(pos2 == -1){
			return unescape(document.cookie.substring(pos + name.length + 1));
		}else{
			return unescape(document.cookie.substring(pos + name.length + 1, pos2));
		}
	}
}

function setDisplayStatus(){
	var showStatus = getCookie("showchars");
	if(showStatus == "all"){
		toggleAll();
	} else {
		//If everything is hid, show all; if everything is not hid, do nothing
		if(allClosed()) toggleAll();
	}
}

function allClosed(){
  var objs = document.getElementsByTagName("div");
  for (i = 0; i < objs.length; i++) {
  	var obj = objs[i]; 
		if(obj.id != "showall" && obj.style.display != "none"){
			return false;
		}
	}
	return true;
}

function setLang(list){
  var langName = list.options[list.selectedIndex].value;
  var objs = document.getElementsByTagName("span");
  for (i = 0; i < objs.length; i++) {
  	var obj = objs[i]; 
		if(obj.lang == langName){
			obj.style.display="";
		}
		else if(obj.lang != ""){
	 		obj.style.display="none";
		}
	}
}
