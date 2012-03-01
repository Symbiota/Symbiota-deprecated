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
	//var imgObj = document.getElementById("activeimage");
	//var imgUrl = imgObj.src;
	var imgUrl = activeImageArr[activeImageIndex];
	var imgId = activeImageKeys[activeImageIndex];
	var ocrXmlHttp = GetXmlHttpObject();
	if(ocrXmlHttp == null){
		alert ("Your browser does not support AJAX!");
		return false;
	}
	var url="rpc/ocrimage.php?url="+imgUrl+"&imgX1="+imgX1+"&imgX2="+imgX2+"&imgY1="+imgY1+"&imgY2="+imgX1;
	ocrXmlHttp.onreadystatechange=function(){
		if(ocrXmlHttp.readyState==4 && ocrXmlHttp.status==200){
			var rawStr = ocrXmlHttp.responseText;
			document.getElementById("tfeditdiv").style.display = "none";
			var addform = document.getElementById("imgaddform-"+imgId);
			addform.style.display = "block";
			addform.rawtext.innerText = rawStr;
			addform.rawtext.textContent = rawStr;
		}
	};
	ocrXmlHttp.open("POST",url,true);
	ocrXmlHttp.send(null);
}

function nextLabelProcessingImage(){
	activeImageIndex++;
	if(activeImageIndex >= activeImageArr.length){
		activeImageIndex = 0;
	}
	var activeImageSrc = activeImageArr[activeImageIndex];
	if(activeImageSrc.substring(0,4)!="http") activeImageSrc = activeImageSrc;
	document.getElementById("activeimage").src = activeImageSrc;
	document.getElementById("imageindex").innerHTML = activeImageIndex + 1;
	document.getElementById("tfadddiv").style.display = "none";
	//Advance text fragments to match image 
	var tfEditDiv = document.getElementById("tfeditdiv");
	tfEditDiv.style.display = "block";
	var tfDivs = tfEditDiv.getElementsByTagName("div");
	for(i = 0; i < tfDivs.length; i++){
		var tfId = tfDivs[i].id;
		if(tfId && tfId.substring(0,10) == "tfeditdiv-"){
			if(tfId == "tfeditdiv-"+activeImageKeys[activeImageIndex]){
				tfDivs[i].style.display = "block";
			}
			else{
				tfDivs[i].style.display = "none";
			}
		}
	}
	return false;
}

function nextRawText(startId,targetId){
	document.getElementById("tfdiv-"+startId).style.display = "none";
	document.getElementById("tfdiv-"+targetId).style.display = "block";
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
