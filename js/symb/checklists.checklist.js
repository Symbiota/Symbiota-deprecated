$(document).ready(function() {
	//Filter autocomplete
	$("#taxonfilter").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/clsearchsuggest.php", { term: request.term, cl: clid }, response );
		}
	},
	{ minLength: 3 });

	//Species add form
	$("#speciestoadd").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/speciessuggest.php", { term: request.term, cl: clid }, response );
		}
	},{ minLength: 4, }
	);

});

function toggleVoucherDiv(tid){
	toggle("voucdiv-"+tid);
	toggle("morevouch-"+tid);
	toggle("lessvouch-"+tid);
	//anchorObj.style.display = "none";
	return false;
}

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			ele.style.display="";
  		}
	 	else{
	 		ele.style.display="none";
	 	}
	}
	else{
		var divObjs = document.getElementsByTagName("div");
	  	for (i = 0; i < divObjs.length; i++) {
	  		var divObj = divObjs[i];
	  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
				if(divObj.style.display=="none"){
					divObj.style.display="";
				}
			 	else {
			 		divObj.style.display="none";
			 	}
			}
		}
		var spanObjs = document.getElementsByTagName("span");
	  	for (i = 0; i < spanObjs.length; i++) {
	  		var spanObj = spanObjs[i];
	  		if(spanObj.getAttribute("class") == target || spanObj.getAttribute("className") == target){
				if(spanObj.style.display=="none"){
					spanObj.style.display="";
				}
			 	else {
			 		spanObj.style.display="none";
			 	}
			}
		}
	}
}

function openIndividualPopup(occid){
	var indUrl = "../collections/individual/index.php?occid="+occid;
	openPopup(indUrl,"indwindow");
	return false;
}

function openPopup(urlStr,windowName){
	newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=1,resizable=1,width=1000,height=800,left=400,top=40');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}
	
function showImagesChecked(f){
	if(f.showimages.checked){
		document.getElementById("wordicondiv").style.display = "none";
		f.showvouchers.checked = false;
		document.getElementById("showvouchersdiv").style.display = "none"; 
		f.showauthors.checked = false;
		document.getElementById("showauthorsdiv").style.display = "none"; 
	}
	else{
		document.getElementById("wordicondiv").style.display = "block";
		document.getElementById("showvouchersdiv").style.display = "block"; 
		document.getElementById("showauthorsdiv").style.display = "block"; 
	}
}

function validateAddSpecies(f){ 
	var sciName = f.speciestoadd.value;
	if(sciName == ""){
		alert("Enter the scientific name of species you wish to add");
		return false;
	}
	else{
		cseXmlHttp=GetXmlHttpObject();
		if (cseXmlHttp==null){
	  		alert ("Your browser does not support AJAX!");
	  		return false;
	  	}
		var url="rpc/gettid.php";
		url=url+"?sciname="+sciName;
		url=url+"&sid="+Math.random();
		cseXmlHttp.onreadystatechange=function(){
			if(cseXmlHttp.readyState==4 && cseXmlHttp.status==200){
				testTid = cseXmlHttp.responseText;
				if(testTid == ""){
					alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, contact your data administrator to add this species to the Taxonomic Thesaurus.");
				}
				else{
					f.tidtoadd.value = testTid;
					f.submit();
				}
			}
		};
		cseXmlHttp.open("POST",url,true);
		cseXmlHttp.send(null);
		return false;
	}
}

function changeOptionFormAction(action,target){
	document.optionform.action = action;
	document.optionform.target = target;
}

//Misc functions
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

/*Array.prototype.unique = function() {
	var a = [];
	var l = this.length;
    for(var i=0; i<l; i++) {
		for(var j=i+1; j<l; j++) {
		if (this[i] === this[j]) j = ++i;
	}
	a.push(this[i]);
	}
	return a;
};*/

//Game menu 
var timeout	= 500;
var closetimer	= 0;
var ddmenuitem	= 0;

// open hidden layer
function mopen(id)
{	
	// cancel close timer
	mcancelclosetime();

	// close old layer
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';

	// get new layer and show it
	ddmenuitem = document.getElementById(id);
	ddmenuitem.style.visibility = 'visible';

}
// close showed layer
function mclose()
{
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
}

// go close timer
function mclosetime()
{
	closetimer = window.setTimeout(mclose, timeout);
}

// cancel close timer
function mcancelclosetime()
{
	if(closetimer)
	{
		window.clearTimeout(closetimer);
		closetimer = null;
	}
}

// close layer when click-out
document.onclick = mclose; 
