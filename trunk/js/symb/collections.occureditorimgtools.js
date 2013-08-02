function toggleImageTdOn(){
	document.getElementById("imgProcOnSpan").style.display = "none";
	document.getElementById("imgProcOffSpan").style.display = "block";
	var imgTdObj = document.getElementById("imgtd");
	if(imgTdObj){
		document.getElementById("imgtd").style.display = "block";
		initImageTool("activeimg-1");
		//Set cookie to tag td as open
        document.cookie = "symbimgtd=open";
	}
}

function toggleImageTdOff(){
	document.getElementById("imgProcOnSpan").style.display = "block";
	document.getElementById("imgProcOffSpan").style.display = "none";
	var imgTdObj = document.getElementById("imgtd");
	if(imgTdObj){
		document.getElementById("imgtd").style.display = "none";
		//Set cookie to tag td closed
        document.cookie = "symbimgtd=close";
	}
}

function initImageTool(imgId){
	var img = document.getElementById(imgId);
	if(!img.complete){
		imgWait=setTimeout(function(){initImageTool(imgId)}, 500);
	}
	else{
		var portWidth = 400;
		var portHeight = 400;
		var portXyCookie = getCookie("symbimgport");
		if(portXyCookie){
			portWidth = parseInt(portXyCookie.substr(0,portXyCookie.indexOf(":")));
			portHeight = parseInt(portXyCookie.substr(portXyCookie.indexOf(":")+1));
		}
		$(function() {
			$(img).imagetool({
				maxWidth: 6000
				,viewportWidth: portWidth
		        ,viewportHeight: portHeight
			});
		});
	}
}

function ocrImage(ocrButton,imgCnt){
	ocrButton.disabled = true;
	document.getElementById("workingcircle-"+imgCnt).style.display = "inline";
	
	var imgObj = document.getElementById("activeimg-"+imgCnt);
	var imgUrl = imgObj.src;

	var x = $(imgObj).imagetool('properties').x;
	var y = $(imgObj).imagetool('properties').y;
	var w = $(imgObj).imagetool('properties').w;
	var h = $(imgObj).imagetool('properties').h;

	var ocrXmlHttp = GetXmlHttpObject();
	if(ocrXmlHttp == null){
		alert ("Your browser does not support AJAX!");
		return false;
	}
	var url="rpc/ocrimage.php?url="+imgUrl;
	if(document.getElementById("ocrfull").checked == false){
		url = url+"&x="+x+"&y="+y+"&w="+w+"&h="+h;
	}
	if(document.getElementById("ocrbest").checked == true){
		url = url+"&ocrbest=1";
	}
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

function nlpText(imgCnt){
	nlpButton.disabled = true;
	document.getElementById("workingcircle-"+imgCnt).style.display = "inline";
	
	var rawStr = document.getElementById("activeimg-"+imgCnt);

	var xmlHttp = GetXmlHttpObject();
	if(xmlHttp == null){
		alert ("Your browser does not support AJAX!");
		return false;
	}
	var url="rpc/nlptext.php?rawstr="+rawStr;
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var dcArr = xmlHttp.responseText;


		}
	};
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
}

function salixText(f){
	f.salixocr.disabled = true;
	
	$.ajax({
		type: "POST",
		url: salixPath,
		data: { textinput: f.rawtext.value, charset: csDefault },
		dataType: json
	}).done(function( msg ) {
		var dwcArr = $.parseJSON(msg);
		var ff = document.fullform;
		if(dwcArr['recordedBy'] && !ff.recordedBy.value) ff.recordedBy.value = dwcArr['recordedBy'];
		
	});
}

function nextLabelProcessingImage(imgCnt){
	document.getElementById("labeldiv-"+(imgCnt-1)).style.display = "none";
	var imgObj = document.getElementById("labeldiv-"+imgCnt);
	if(!imgObj){
		imgObj = document.getElementById("labeldiv-1");
		imgCnt = "1";
	}
	imgObj.style.display = "block";
	
	initImageTool("activeimg-"+imgCnt);
	
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