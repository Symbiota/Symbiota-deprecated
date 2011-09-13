var imageArr = new Array();
var imgCnt = 0;

$(document).ready(function() {
	$('#desctabs').tabs();
});

function toggle(target){
	var spanObjs = document.getElementsByTagName("span");
	for (i = 0; i < spanObjs.length; i++) {
		var obj = spanObjs[i];
		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
			if(obj.style.display=="none"){
				obj.style.display="inline";
			}
			else {
				obj.style.display="none";
			}
		}
	}

	var divObjs = document.getElementsByTagName("div");
	for (i = 0; i < divObjs.length; i++) {
		var obj = divObjs[i];
		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
			if(obj.style.display=="none"){
				obj.style.display="inline";
			}
			else {
				obj.style.display="none";
			}
		}
	}
}

function toggleMap(mapObj){
	var roi = mapObj.value;
	var mapObjs = getElementByTagName("div");
	for(x=0;x<mapObjs.length;x++){
		var mObj = mapObjs[x];
		if(mObj.classname == "mapdiv"){
			if(mObj == mapObj){
				mObj.style.display = "block";
			}
			else{
				mObj.style.display = "none";
			}
		}
	}
}

function toggleImgInfo(target, anchorObj){
	//close all imgpopup divs
	var divs = document.getElementsByTagName("div");
	for(x=0;x<divs.length;x++){
		var d = divs[x];
		if(d.getAttribute("class") == "imgpopup" || d.getAttribute("className") == "imgpopup"){
			d.style.display = "none";
		}
	}

	//Open and place target imgpopup
	var obj = document.getElementById(target);
	var pos = findPos(anchorObj);
	var posLeft = pos[0];
	if(posLeft > 550){
		posLeft = 550;
	}
	obj.style.left = posLeft;
	obj.style.top = pos[1];
	if(obj.style.display=="block"){
		obj.style.display="none";
	}
	else {
		obj.style.display="block";
	}
	var targetStr = "document.getElementById('" + target + "').style.display='none'";
	var t=setTimeout(targetStr,10000);
}

function findPos(obj){
	var curleft = 0; 
	var curtop = 0;
	curleft = obj.offsetLeft;
	curtop = obj.offsetTop;
	return [curleft,curtop];
}	

function expandImages(){
	eiObj = document.getElementById("imgextra");
	eiObj.style.display = "block";
	mpObj = document.getElementById("morephotos");
	mpObj.style.display = "none";
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
