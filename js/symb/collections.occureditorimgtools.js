var imageCnt = 1;
var ocrFragCnt = 1;

function toggleImageTdOn(){
	var imgSpan = document.getElementById("imgProcOnSpan");
	if(imgSpan){
		imgSpan.style.display = "none";
		document.getElementById("imgProcOffSpan").style.display = "block";
		var imgTdObj = document.getElementById("imgtd");
		if(imgTdObj){
			document.getElementById("imgtd").style.display = "block";
			initImageTool("activeimg-1");
			//Set cookie to tag td as open
	        document.cookie = "symbimgtd=open";
		}
	}
}

function toggleImageTdOff(){
	var imgSpan = document.getElementById("imgProcOnSpan");
	if(imgSpan){
		imgSpan.style.display = "block";
		document.getElementById("imgProcOffSpan").style.display = "none";
		var imgTdObj = document.getElementById("imgtd");
		if(imgTdObj){
			document.getElementById("imgtd").style.display = "none";
			//Set cookie to tag td closed
	        document.cookie = "symbimgtd=close";
		}
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

function nlpLbcc(nlpButton,prlid){
	document.getElementById("workingcircle_lbcc-"+prlid).style.display = "inline";
	nlpButton.disabled = true;
	var f = nlpButton.form;
	var rawOcr = f.rawtext.innerText;
	if(!rawOcr) rawOcr = f.rawtext.textContent;
	var cnumber = f.cnumber.value;
	var iUrl = f.iurl.value;
	var collid = f.collid.value;

	$.ajax({
		type: "POST",
		url: "rpc/nlplbcc.php",
		data: { rawocr: rawOcr, iurl: iUrl, collid: collid, catalognumber: cnumber }
	}).done(function( msg ) {
		pushDwcArrToForm(msg);
	});

	nlpButton.disabled = false;
	document.getElementById("workingcircle_lbcc-"+prlid).style.display = "none";
}

function nlpSalix(nlpButton,prlid){
	document.getElementById("workingcircle_salix-"+prlid).style.display = "inline";
	nlpButton.disabled = true;
	var f = nlpButton.form;
	var rawOcr = f.rawtext.innerText;
	if(!rawOcr) rawOcr = f.rawtext.textContent;
	var targetDataset = '';
	if(salixPath.indexOf("?t=") > 0){
		targetDataset = salixPath.substring(salixPath.indexOf("?t=")+3);
		if(targetDataset.indexOf("&") > 0) targetDataset = salixPath.substring(0,targetDataset.indexOf("&"));
		salixPath = salixPath.substring(0,salixPath.indexOf("?t="));
	}
	
	$.ajax({
		type: "POST",
		url: salixPath,
		crossDomain: true,
		data: { textinput: rawOcr, t: targetDataset }
	}).done(function( msg ) {
		pushDwcArrToForm(msg);
	}).error(function(  ) {
		alert('Problem accessing SALIX webservices; notify an administrator');
	});

	nlpButton.disabled = false;
	document.getElementById("workingcircle_salix-"+prlid).style.display = "none";
}

function pushDwcArrToForm(msg){
	var dwcArr = $.parseJSON(msg);
	var f = document.fullform;
	//var fieldsTransfer = "";
	//var fieldsSkip = "";
	var scinameTransferred = false;
	var verbatimElevTransferred = false;
	for(var k in dwcArr){
		try{
			var symbk = k.toLowerCase();
			if(symbk == "scientificname") symbk = "sciname";
			var elem = f.elements[symbk];
			var inVal = dwcArr[k];
			if(inVal && elem && elem.value == "" && elem.disabled == false && elem.type != "hidden"){
				if(symbk == "sciname") scinameTransferred = true;
				if(symbk == "verbatimelevation") verbatimElevTransferred = true;
				elem.value = dwcArr[k];
				elem.style.backgroundColor = "lightgreen";
				//fieldsTransfer = fieldsTransfer + ", " + k;
				fieldChanged(symbk);
			}
			else{
				//fieldsSkip = fieldsSkip + ", " + k;
			}
		}
		catch(err){
			//alert(err);
		}
	}
	if(scinameTransferred) verifyFullformSciName();
	if(verbatimElevTransferred) parseVerbatimElevation(f);
	//if(fieldsTransfer == "") fieldsTransfer = "none";
	//if(fieldsSkip == "") fieldsSkip = "none";
	//alert("Field parsed: " + fieldsTransfer + "\nFields skipped: " + fieldsSkip);
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
	imageCnt = imgCnt;
	
	return false;
}

function nextRawText(imgCnt,fragCnt){
	document.getElementById("tfdiv-"+imgCnt+"-"+(fragCnt-1)).style.display = "none";
	var fragObj = document.getElementById("tfdiv-"+imgCnt+"-"+fragCnt);
	if(!fragObj) fragObj = document.getElementById("tfdiv-"+imgCnt+"-1");
	fragObj.style.display = "block";
	ocrFragCnt = fragCnt;
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
