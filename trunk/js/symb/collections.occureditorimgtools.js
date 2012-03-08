var imgX1;
var imgX2;
var imgY1;
var imgY2;

function toggleImageTd(){
	toggle("imgprocondiv");
	toggle("imgprocoffdiv");
	if(document.getElementById("imgtd").style.display == "none"){
		document.getElementById("imgtd").style.display = "block";
		initImageTool(document.getElementById("activeimg-1"));
		//Set cookie to tag td as open
		
	}
	else{
		document.getElementById("imgtd").style.display = "none";
		//Set cookie to tag td closed
		
	}
}

function initImageTool(img){
	if(!img.complete){
		imgWait=setTimeout('initImageTool(document.getElementById("activeimage"))', 500);
	}
	else{
		$(function() {
			$(img).imagetool({
				maxWidth: 6000
				,viewportWidth: 400
		        ,viewportHeight: 400
		        ,imageWidth: 3500
		        ,imageHeight: 5200
		        ,change: function(event, dim) {
					//If _zoom or _pan
					imgX1 = dim.x;
					imgX2 = dim.w + dim.x;
					imgY1 = dim.y;
					imgY2 = dim.h + dim.y;
					//If img frame is resized (_handleViewPortResize), send width and height to cookies
					
					
		        }
			});
		});
	}
}

function ocrImage(ocrButton,imgCnt){
	ocrButton.disabled = true;
	document.getElementById("workingcircle-"+imgCnt).style.display = "inline";
	
	var imgObj = document.getElementById("activeimg-"+imgCnt);
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
			document.getElementById("tfeditdiv-"+imgCnt).style.display = "none";
			document.getElementById("tfadddiv-"+imgCnt).style.display = "block";
			var addform = document.getElementById("imgaddform-"+imgCnt);
			addform.rawtext.innerText = rawStr;
			addform.rawtext.textContent = rawStr;
			document.getElementById("workingcircle-"+imgCnt).style.display = "none";
			ocrButton.disabled = false;
		}
	};
	ocrXmlHttp.open("POST",url,true);
	ocrXmlHttp.send(null);
}

function nextLabelProcessingImage(imgCnt){
	document.getElementById("labeldiv-"+(imgCnt-1)).style.display = "none";
	var imgObj = document.getElementById("labeldiv-"+imgCnt);
	if(!imgObj){
		imgObj = document.getElementById("labeldiv-1");
		imgCnt = "1";
	}
	imgObj.style.display = "block";
	
	initImageTool(document.getElementById("activeimg-"+imgCnt));
	
	return false;
}

function nextRawText(imgCnt,fragCnt){
	document.getElementById("tfdiv-"+imgCnt+"-"+(fragCnt-1)).style.display = "none";
	var fragObj = document.getElementById("tfdiv-"+imgCnt+"-"+fragCnt);
	if(!fragObj) fragObj = document.getElementById("tfdiv-"+imgCnt+"-1");
	fragObj.style.display = "block";
	return false;
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
