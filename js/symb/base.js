function setLanguage(selObj){
	var langVal = selObj.value;
	var d = new Date();
	d.setMonth( d.getMonth() + 1 );
	document.cookie = "lang="+langVal+"; path=/ ; expires="+ d.toUTCString();
	location.reload(true);
}