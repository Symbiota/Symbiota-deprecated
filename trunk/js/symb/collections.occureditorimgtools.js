
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
			});
		});
	}
}

function ocrImage(){
	var imgUrl = document.getElementById("activeimage").src;
	var ocrXmlHttp = GetXmlHttpObject();
	if(ocrXmlHttp == null){
		alert ("Your browser does not support AJAX!");
		return false;
	}
	var url="rpc/ocrimage.php?url="+imgUrl;
	ocrXmlHttp.onreadystatechange=function(){
		if(ocrXmlHttp.readyState==4 && ocrXmlHttp.status==200){
			var rawTxtObj = document.getElementById("txtfrag");
			var rawStr = ocrXmlHttp.responseText;
			rawTxtObj.innerText = rawStr;
			rawTxtObj.textContent = rawStr;
		}
	};
	ocrXmlHttp.open("POST",url,true);
	ocrXmlHttp.send(null);
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
