var activeImgIndex = 1;
var ocrFragIndex = 1;

$(document).ready(function() {
	//Remember image popout status 
	var imgTd = getCookie("symbimgtd");
	if(imgTd != "close") toggleImageTdOn();
	//if(imgTd == "open" || csMode == 1) toggleImageTdOn();
	initImgRes();
});

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

function setPortXY(portWidth,portHeight){
	document.cookie = "symbimgport=" + portWidth + ":" + portHeight;
}

function initImgRes(){
	var imgObj = document.getElementById("activeimg-"+activeImgIndex);
	if(imgObj){
		if(imgLgArr[activeImgIndex]){
			var imgRes = getCookie("symbimgres");
			if(imgRes == 'lg'){
				changeImgRes('lg');
			}
		}
		else{
			imgObj.src = imgArr[activeImgIndex];
			document.getElementById("imgresmed").checked = true;
			var imgResLgRadio = document.getElementById("imgreslg");
			imgResLgRadio.disabled = true;
			imgResLgRadio.title = "Large resolution image not available";
		}
		if(imgArr[activeImgIndex]){
			//Do nothing
		}
		else{
			if(imgLgArr[activeImgIndex]){
				imgObj.src = imgLgArr[activeImgIndex];
				document.getElementById("imgreslg").checked = true;
				var imgResMedRadio = document.getElementById("imgresmed");
				imgResMedRadio.disabled = true;
				imgResMedRadio.title = "Medium resolution image not available";
			}
		}
	}
}

function changeImgRes(resType){
	var imgObj = document.getElementById("activeimg-"+activeImgIndex);
	var oldSrc = imgObj.src;
	if(resType == 'lg'){
        document.cookie = "symbimgres=lg";
    	if(imgLgArr[activeImgIndex]){
    		imgObj.src = imgLgArr[activeImgIndex];
    		document.getElementById("imgreslg").checked = true;
    	}
	}
	else{
        document.cookie = "symbimgres=med";
    	if(imgArr[activeImgIndex]){
    		imgObj.src = imgArr[activeImgIndex];
    		document.getElementById("imgresmed").checked = true;
    	}
	}
	if(oldSrc.indexOf("rotate=") > -1){
		oldSrc = oldSrc.substring(0,oldSrc.indexOf('&format='));
		oldSrc = oldSrc.substring(oldSrc.indexOf('rotate=')+7);
		var currentSrc = imgObj.src;
		currentSrc = currentSrc.substring(0,currentSrc.indexOf('&format='));
		imgObj.src = currentSrc + '&rotate=' + oldSrc + '&format=jpeg';
	}
}

function rotateiPlantImage(rotationAngle){
	var imgObj = document.getElementById("activeimg-"+activeImgIndex);
	var imgSrc = imgObj.src;
	if(imgSrc.indexOf("bisque.cyverse") > -1){
		var angle = 0;
		imgSrc = imgSrc.substring(0,imgSrc.indexOf('&format='));
		if(imgSrc.indexOf("rotate=") > -1){
			var last3 = imgSrc.substr(-3);
			if(last3 == "=90"){
				angle = 90;
			}
			else if(last3 == "-90"){
				angle = -90;
			}
			else if(last3 == "180"){
				angle = 180;
			}
			imgSrc = imgSrc.substring(0,imgSrc.indexOf('&rotate='));
		}
		angle = angle + rotationAngle;
		if(angle == -180){
			angle = 180;
		}
		else if(angle == 270){
			angle = -90;
		}
		if(angle == 0){
			imgObj.src = imgSrc + "&format=jpeg";
		}
		else{
			imgObj.src = imgSrc + "&rotate="+angle+"&format=jpeg";
		}
		
		var img = document.getElementById("activeimg-"+activeImgIndex);
		$(img).imagetool("option","src",imgObj.src);
		$(img).imagetool("reset");
	}
}

function ocrImage(ocrButton,imgidVar,imgCnt){
	ocrButton.disabled = true;
	document.getElementById("workingcircle-"+imgCnt).style.display = "inline";
	
	var imgObj = document.getElementById("activeimg-"+imgCnt);

	var xVar = 0;
	var yVar = 0;
	var wVar = 1;
	var hVar = 1;
	var ocrBestVar = 0;
	
	if(document.getElementById("ocrfull").checked == false){
		xVar = $(imgObj).imagetool('properties').x;
		yVar = $(imgObj).imagetool('properties').y;
		wVar = $(imgObj).imagetool('properties').w;
		hVar = $(imgObj).imagetool('properties').h;
	}
	if(document.getElementById("ocrbest").checked == true){
		ocrBestVar = 1;
	}

	$.ajax({
		type: "POST",
		url: "rpc/ocrimage.php",
		data: { imgid: imgidVar, ocrbest: ocrBestVar, x: xVar, y: yVar, w: wVar, h: hVar }
	}).done(function( msg ) {
		var rawStr = msg;
		document.getElementById("tfeditdiv-"+imgCnt).style.display = "none";
		document.getElementById("tfadddiv-"+imgCnt).style.display = "block";
		var addform = document.getElementById("ocraddform-"+imgCnt);
		addform.rawtext.innerText = rawStr;
		addform.rawtext.textContent = rawStr;
		//Add OCR source with date
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();
		if(dd<10) dd='0'+dd;
		if(mm<10) mm='0'+mm;
		addform.rawsource.value = "Tesseract: "+yyyy+"-"+mm+"-"+dd;
		
		document.getElementById("workingcircle-"+imgCnt).style.display = "none";
		ocrButton.disabled = false;
	});
}

function nlpLbcc(nlpButton,prlid){
	document.getElementById("workingcircle_lbcc-"+prlid).style.display = "inline";
	nlpButton.disabled = true;
	var f = nlpButton.form;
	var rawOcr = f.rawtext.innerText;
	if(!rawOcr) rawOcr = f.rawtext.textContent;
	var cnumber = f.cnumber.value;
	var collid = f.collid.value;

	$.ajax({
		type: "POST",
		url: "rpc/nlplbcc.php",
		data: { rawocr: rawOcr, collid: collid, catnum: cnumber }
	}).done(function( msg ) {
		pushDwcArrToForm(msg,"lightgreen");
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
	$.ajax({
		type: "POST",
		url: "rpc/nlpsalix.php",
		data: { rawocr: rawOcr }
	}).done(function( msg ) {
		pushDwcArrToForm(msg,"lightgreen");
	});

	nlpButton.disabled = false;
	document.getElementById("workingcircle_salix-"+prlid).style.display = "none";
}

function pushDwcArrToForm(msg,bgColor){
	var dwcArr = $.parseJSON(msg);
	var f = document.fullform;
	//var fieldsTransfer = "";
	//var fieldsSkip = "";
	var scinameTransferred = false;
	var verbatimElevTransferred = false;
	for(var k in dwcArr){
		try{
			if(k != 'family' && k != 'scientificnameauthorship'){
				var elem = f.elements[k];
				var inVal = dwcArr[k];
				if(inVal && elem && elem.value == "" && elem.disabled == false && elem.type != "hidden"){
					if(k == "sciname") scinameTransferred = true;
					if(k == "verbatimelevation") verbatimElevTransferred = true;
					elem.value = inVal;
					elem.style.backgroundColor = bgColor;
					//fieldsTransfer = fieldsTransfer + ", " + k;
					fieldChanged(k);
				}
				else{
					//fieldsSkip = fieldsSkip + ", " + k;
				}
			}
		}
		catch(err){
			//alert(err);
		}
	}
	if(scinameTransferred) verifyFullFormSciName();
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
	activeImgIndex = imgCnt;
	
	return false;
}

function nextRawText(imgCnt,fragCnt){
	document.getElementById("tfdiv-"+imgCnt+"-"+(fragCnt-1)).style.display = "none";
	var fragObj = document.getElementById("tfdiv-"+imgCnt+"-"+fragCnt);
	if(!fragObj) fragObj = document.getElementById("tfdiv-"+imgCnt+"-1");
	fragObj.style.display = "block";
	ocrFragIndex = fragCnt;
	return false;
}