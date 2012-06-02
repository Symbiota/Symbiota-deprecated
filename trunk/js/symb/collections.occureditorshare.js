//Query form 
function submitQueryForm(qryIndex){
	var f = document.queryform;
	if(qryIndex) f.occindex.value = qryIndex;
	if(verifyQueryForm(f)) f.submit();
	return false;
}

function submitQueryEditor(f){
	f.action = "occurrenceeditor.php"
	if(verifyQueryForm(f)) f.submit();
	return true;
}

function submitQueryTable(f){
	f.action = "occurrencetabledisplay.php"
	if(verifyQueryForm(f)) f.submit();
	return true;
}

function verifyQueryForm(f){
	if(f.q_identifier.value == "" && f.q_othercatalognumbers.value == ""  
		&& f.q_recordedby.value == "" && f.q_recordnumber.value == "" && f.q_eventdate.value == ""
		&& f.q_enteredby.value == "" && f.q_processingstatus.value == "" && f.q_datelastmodified.value == "" 
		&& f.q_customvalue1.value == "" 
		&& ((f.q_observeruid.type == "hidden" && f.q_observeruid.value == "") || (f.q_observeruid.type == "checkbox" && f.q_observeruid.checked == false))){
		alert("Query form is empty! Please enter a value to query by.");
		return false;
	}

	var validformat1 = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
	var validformat2 = /^\s*\d{4}-\d{2}-\d{2} - \d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd

	var edDateStr = f.q_eventdate.value;
	if(edDateStr){
		try{
			if(!validformat1.test(edDateStr) && !validformat2.test(edDateStr)){
				alert("Event date must follow YYYY-MM-DD for a single date and YYYY-MM-DD - YYYY-MM-DD as a range");
				return false;
			}
		}
		catch(ex){
		}
	}
	
	var modDateStr = f.q_datelastmodified.value;
	if(modDateStr){
		try{
			if(!validformat1.test(modDateStr) && !validformat2.test(modDateStr)){
				alert("Date entered must follow YYYY-MM-DD for a single date and YYYY-MM-DD - YYYY-MM-DD as a range");
				return false;
			}
		}
		catch(ex){
		}
	}

	return true;
}

function resetQueryForm(f){
	f.q_identifier.value = "";
	f.q_othercatalognumbers.value = "";
	f.q_recordedby.value = "";
	f.q_recordnumber.value = "";
	f.q_eventdate.value = "";
	f.q_enteredby.value = "";
	f.q_datelastmodified.value = "";
	f.q_processingstatus.value = "";
}

function submitBatchUpdate(f){
	var fieldName = f.bufieldname.options[f.bufieldname.selectedIndex].value;
	var oldValue = f.buoldvalue.value;
	var newValue = f.bunewvalue.value;
	var collId = f.collid.value;
	var ouid = f.ouid.value;
	var buMatch = 0;
	if(f.bumatch[1].checked) buMatch = 1;
	if(!fieldName || !oldValue || !newValue){
		alert("Please select a field name and enter a value in the current and new value fields");
		return false;
	}
	xmlHttp = GetXmlHttpObject();
	if(xmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	var url = "rpc/batchupdateverify.php?collid="+collId+"&fieldname="+fieldName+"&oldvalue="+oldValue+"&bumatch="+buMatch+"&ouid="+ouid;
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var retCnt = xmlHttp.responseText;
			if(retCnt != ''){
				if(confirm("You are about to update "+retCnt+" records.\nNote that you won't be able to undo this Replace operation!\nDo you want to continue?")){
					f.submit();
				}
			}
			else{
				alert("ERROR: unable to batch update");
			}
		}
	};
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
	return false;
}

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			ele.style.display="block";
  		}
	 	else {
	 		ele.style.display="none";
	 	}
	}
	else{
		var divObjs = document.getElementsByTagName("div");
	  	for (i = 0; i < divObjs.length; i++) {
	  		var divObj = divObjs[i];
	  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
				if(divObj.style.display=="none"){
					divObj.style.display="block";
				}
			 	else {
			 		divObj.style.display="none";
			 	}
			}
		}
	}
}

function toggleSearch(){
	document.getElementById("batchupdatediv").style.display = "none";
	toggle("querydiv");
}

function toggleBatchUpdate(){
	document.getElementById("querydiv").style.display = "none";
	toggle("batchupdatediv");
}

function getCookie(cName){
	var i,x,y;
	var cookieArr = document.cookie.split(";");
	for(i=0;i<cookieArr.length;i++){
		x=cookieArr[i].substr(0,cookieArr[i].indexOf("="));
		y=cookieArr[i].substr(cookieArr[i].indexOf("=")+1);
		x=x.replace(/^\s+|\s+$/g,"");
		if (x==cName){
			return unescape(y);
		}
	}
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
