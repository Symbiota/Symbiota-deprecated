$(document).ready(function() {
	$("#uppertaxonomy").autocomplete({ source: "rpc/getuppertaxonsuggest.php" },{ minLength: 3, autoFocus: true });

	$("#ctnafacceptedstr").autocomplete({ source: "rpc/getacceptedsuggest.php" },{ minLength: 3, autoFocus: true });
});

function submitLoadForm(f){
	var submitForm = true;
	var errorStr = "";
	var rankId = f.rankid.value;
	if(f.sciname.value == "") errorStr += ", Scientific Name"; 
	if(f.unitname1.value == "") errorStr += ", Unit Name 1 (genus or uninomial)"; 
	if(rankId == 0 || rankId == "") errorStr += ", Taxon Rank"; 
	if(f.parenttid.value == "" && rankId != "10") errorStr += ", Parent Taxon"; 
	if(errorStr != ""){
		alert("Following Fields Required: "+errorStr.substring(2));
		submitForm = false;
	}

	if(submitForm){
		if(rankId > 140){
			if(f.uppertaxonomy.value == "") errorStr += "Upper Taxonomy \n"; 
			if(errorStr != ""){
				submitForm = confirm("Following fields are recommended. Are you sure you want to leave them blank?\n"+errorStr);
			}
		}
	}
	if(submitForm){
		var accStr = f.acceptedstr.value;
		if(accStr){
			tXmlHttp=GetXmlHttpObject();
			if(tXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/gettid.php";
			url=url+"?sciname="+accStr;
			tXmlHttp.onreadystatechange=function(){
				if(tXmlHttp.readyState==4 && tXmlHttp.status==200){
					var accTid = tXmlHttp.responseText;
					if(accTid){
						f.tidaccepted.value = accTid;
						f.submit();
					}
					else{
						alert("ERROR: Accepted taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus.");
					}
				}
			};
			tXmlHttp.open("POST",url,true);
			tXmlHttp.send(null);
		}
		else{
			f.submit();
		}
	}
}

function parseName(f){

	var sciName = trim(f.sciname.value);
	checkScinameExistance(sciName);
	f.reset();
	f.sciname.value = sciName;
	var sciNameArr = new Array(); 
	var activeIndex = 0;
	var unitName1 = "";
	var unitName2 = "";
	var rankId = 0;
	sciNameArr = sciName.split(' ');

	if(sciNameArr[activeIndex].length == 1){
		f.unitind1.value = sciNameArr[activeIndex];
		f.unitname1.value = sciNameArr[activeIndex+1];
		unitName1 = sciNameArr[activeIndex+1];
		activeIndex = 2;
	}
	else{
		f.unitname1.value = sciNameArr[activeIndex];
		unitName1 = sciNameArr[activeIndex];
		activeIndex = 1;
	}
	if(sciNameArr.length > activeIndex){
		if(sciNameArr[activeIndex].length == 1){
			f.unitind2.value = sciNameArr[activeIndex];
			f.unitname2.value = sciNameArr[activeIndex+1];
			unitName2 = sciNameArr[activeIndex+1];
			activeIndex = activeIndex+2;
		}
		else{
			f.unitname2.value = sciNameArr[activeIndex];
			unitName2 = sciNameArr[activeIndex];
			activeIndex = activeIndex+1;
		}
		rankId = 220;
	}
	if(sciNameArr.length > activeIndex){
		if(sciNameArr[activeIndex].substring(sciNameArr[activeIndex].length-1,sciNameArr[activeIndex].length) == "." || sciNameArr[activeIndex].length == 1){
			rankName = sciNameArr[activeIndex];
			f.unitind3.value = sciNameArr[activeIndex];
			f.unitname3.value = sciNameArr[activeIndex+1];
			if(sciNameArr[activeIndex] == "ssp." || sciNameArr[activeIndex] == "subsp.") rankId = 230;
			if(sciNameArr[activeIndex] == "var.") rankId = 240;
			if(sciNameArr[activeIndex] == "f.") rankId = 260;
			if(sciNameArr[activeIndex] == "x" || sciNameArr[activeIndex] == "X") rankId = 220;
		}
		else{
			f.unitname3.value = sciNameArr[activeIndex];
			rankId = 230;
		}
	}
	if(unitName1.indexOf("aceae") == (unitName1.length - 5) || unitName1.indexOf("idae") == (unitName1.length - 4)){
		rankId = 140;
	}
	f.rankid.value = rankId;
	if(rankId >= 140){
		setUpperTaxonomy(f);
	}
	if(rankId > 180){
		setParent(f);
	}
}

function setParent(f){
	var rankId = f.rankid.value;
	var unitName1 = f.unitname1.value;
	var unitName2 = f.unitname2.value;
	var parentName = "";
	if(rankId == 220){
		parentName = unitName1; 
	}
	else if(rankId > 220){
		parentName = unitName1 + " " + unitName2; 
	}
	if(parentName){
		f.parentname.value = parentName;
		checkParentExistance(f);
	}
}			

function checkScinameExistance(sciname){
	if (sciname.length == 0){
  		return;
  	}
	cseXmlHttp=GetXmlHttpObject();
	if (cseXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/gettid.php";
	url=url+"?sciname="+sciname;
	cseXmlHttp.onreadystatechange=function(){
		if(cseXmlHttp.readyState==4 && cseXmlHttp.status==200){
			var responseStr = cseXmlHttp.responseText;
			if(responseStr != ""){
				var sciName = document.getElementById("sciname").value;
				alert("INSERT FAILED: "+sciName+" ("+responseStr+")"+" already exists in database.");
				return false;
			}
			return true;
		}
	};
	cseXmlHttp.open("POST",url,true);
	cseXmlHttp.send(null);
} 

function acceptanceChanged(f){
	accStatusObj = f.acceptstatus;
	if(accStatusObj[0].checked){
		document.getElementById("accdiv").style.display = "none";
	}
	else{
		document.getElementById("accdiv").style.display = "block";
	}
}

function setUpperTaxonomy(f){
	var genusStr = f.unitname1.value; 
	if (genusStr.length == 0){
  		return;
  	}
	sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/getuppertaxonomy.php";
	url=url+"?sciname="+genusStr;
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			var responseStr = sutXmlHttp.responseText; 
			if(responseStr){
				f.uppertaxonomy.value = responseStr;
			}
		}
	};
	sutXmlHttp.open("POST",url,true);
	sutXmlHttp.send(null);
}

function checkParentExistance(f){
	parentStr = f.parentname.value;
	if (parentStr.length == 0){
  		return;
  	}
	var cpeXmlHttp=GetXmlHttpObject();
	if(cpeXmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/gettid.php";
	url=url+"?sciname="+parentStr;
	cpeXmlHttp.onreadystatechange=function(){
		if(cpeXmlHttp.readyState==4 && cpeXmlHttp.status==200){
			var parentTid = cpeXmlHttp.responseText;
			if(parentTid){
				f.parenttid.value = parentTid;
			}
			else{
				alert("Parent does not exist. Please first add parent to system. This can be done by clicking on 'Add Parent' button to the right of parent name.");
				document.getElementById("addparentspan").style.display = "inline";
				document.getElementById("addparentanchor").href = "taxonomyloader.php?target="+f.parentname.value;
				return false;
			}
			return true;
		}
	};
	cpeXmlHttp.open("POST",url,true);
	cpeXmlHttp.send(null);
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

function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}
