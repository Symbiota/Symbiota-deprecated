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

function expandExtraImages(){
	document.getElementById("moreimages").style.display = "none";
	document.getElementById("imgextra").style.display = "block";
}

function openMapPopup(taxonVar,clid){
	var popupMap = window.open('../map/googlemap.php?maptype=taxa&taxon='+taxonVar+'&clid='+clid,'gmap','toolbar=0,scrollbars=1,width=950,height=700,left=20,top=20');
    if (popupMap.opener == null) popupMap.opener = self;
    popupMap.focus();
}

