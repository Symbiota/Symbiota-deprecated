var imgX1;
var imgX2;
var imgY1;
var imgY2;

function toggleImageTd(){
	toggle("imgprocondiv");
	toggle("imgprocoffdiv");
	if(document.getElementById("imgtd").style.display == "none"){
		document.getElementById("imgtd").style.display = "block";
		initImageTool(document.getElementById("activeimage"));
	}
	else{
		document.getElementById("imgtd").style.display = "none";
	}
}

function initImageTool(img){
	if(!img.complete){
		imgWait=setTimeout('initImageTool(document.getElementById("activeimage"))', 500);
	}
	else{
		$(function() {
			$("#labelimagediv img").imagetool({
				maxWidth: 6000
				,viewportWidth: 400
		        ,viewportHeight: 400
		        ,imageWidth: 3500
		        ,imageHeight: 5200
		        ,change: function(event, dim) {
					imgX1 = dim.x;
					imgX2 = dim.w + dim.x;
					imgY1 = dim.y;
					imgY2 = dim.h + dim.y;
		        }
			});
		});
	}
}

function ocrImage(){
	var imgObj = document.getElementById("activeimage");
	var imgUrl = imgObj.src;
	var ocrXmlHttp = GetXmlHttpObject();
	if(ocrXmlHttp == null){
		alert ("Your browser does not support AJAX!");
		return false;
	}
	var url="rpc/ocrimage.php?url="+imgUrl+"&imgX1="+imgX1+"&imgX2="+imgX2+"&imgY1="+imgY1+"&imgY2="+imgX1;
	ocrXmlHttp.onreadystatechange=function(){
		if(ocrXmlHttp.readyState==4 && ocrXmlHttp.status==200){
			var rawStr = ocrXmlHttp.responseText;
			var rawTxtObj = document.getElementById("tfdiv-add");
			rawTxtObj.innerText = rawStr;
			rawTxtObj.textContent = rawStr;
		}
	};
	ocrXmlHttp.open("POST",url,true);
	ocrXmlHttp.send(null);
}

function nextRawText(imgId){
	var imgElem = document.getElementById("tfdiv-"+imgId);
	var fragElemArr = imgElem.getElementsByTagName("div");
	var fragLength = fragElemArr.length - 1;
	var fragIndex = 0;
  	for (i = 0; i < fragLength; i++) {
  		var divObj = fragElemArr[i];
  		if(divObj.style.display=="block"){
  			divObj.style.display = "none";
  			fragIndex = i +1;
  			if(fragIndex == fragLength){
  				fragIndex = 0;
  			}
			fragElemArr[fragIndex].style.display = "block";
			break;
  		}
  	}
  	document.getElementById("tfindex-"+imgId).innerHTML = fragIndex + 1;
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
