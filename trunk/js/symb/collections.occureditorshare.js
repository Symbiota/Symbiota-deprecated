//Query form 
function submitQueryForm(qryIndex){
	if(verifyLeaveForm()){
		var f = document.queryform;
		if(qryIndex) f.occindex.value = qryIndex;
		if(verifyQueryForm(f)) f.submit();
	}
	return false;
}

function verifyLeaveForm(){
	if(document.fullform && document.fullform.submitaction.disabled == false && document.fullform.submitaction.type == "submit"){
		return confirm("It appears that you didn't save your changes. Are you sure you want to leave without saving?"); 
	}
	return true;
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
	//if(f.q_identifier.value == "" && f.q_othercatalognumbers.value == ""  
	//	&& f.q_recordedby.value == "" && f.q_recordnumber.value == "" && f.q_eventdate.value == ""
	//	&& f.q_enteredby.value == "" && f.q_processingstatus.value == "" && f.q_datelastmodified.value == "" 
	//	&& (f.q_customfield1.selectedIndex == 0 && (f.q_customvalue1.value == "" || f.q_customtype1.selectedIndex != 1)) 
	//	&& ((f.q_observeruid.type == "hidden" && f.q_observeruid.value == "") || (f.q_observeruid.type == "checkbox" && f.q_observeruid.checked == false))){
	//	alert("Query form is empty! Please enter a value to query by.");
	//	return false;
	//}

	if(!verifyLeaveForm()) return false;

	var validformat1 = /^\s*[<>]{0,1}\s{0,1}\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd, >yyyy-mm-dd, <yyyy-mm-dd
	var validformat2 = /^\s*\d{4}-\d{2}-\d{2}\s{1,3}-\s{1,3}\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd - yyyy-mm-dd
	var validformat3 = /^\s*>{1}\s{0,1}\d{4}-\d{2}-\d{2}\s{1,3}AND\s{1,3}<{1}\s{0,1}\d{4}-\d{2}-\d{2}\s*$/i //Format: >yyyy-mm-dd AND <yyyy-mm-dd

	if(f.q_eventdate){
		var edDateStr = f.q_eventdate.value;
		if(edDateStr){
			try{
				if(!validformat1.test(edDateStr) && !validformat2.test(edDateStr) && !validformat3.test(edDateStr)){
					alert("Event date must one of following formats: YYYY-MM-DD, YYYY-MM-DD - YYYY-MM-DD, >YYYY-MM-DD, <YYYY-MM-DD, >YYYY-MM-DD AND <YYYY-MM-DD");
					return false;
				}
			}
			catch(ex){
			}
		}
	}
	
	if(f.q_datelastmodified){
		var modDateStr = f.q_datelastmodified.value;
		if(modDateStr){
			try{
				if(!validformat1.test(modDateStr) && !validformat2.test(modDateStr) && !validformat3.test(modDateStr)){
					alert("Date entered must one of following formats: YYYY-MM-DD, YYYY-MM-DD - YYYY-MM-DD, >YYYY-MM-DD, <YYYY-MM-DD, >YYYY-MM-DD AND <YYYY-MM-DD");
					return false;
				}
			}
			catch(ex){
			}
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
	f.q_customfield1.options[0].selected = true;
	f.q_customtype1.options[0].selected = true;
	f.q_customvalue1.value = "";
	f.q_customfield2.options[0].selected = true;
	f.q_customtype2.options[0].selected = true;
	f.q_customvalue2.value = "";
	f.q_customfield3.options[0].selected = true;
	f.q_customtype3.options[0].selected = true;
	f.q_customvalue3.value = "";
	f.q_imgonly.checked = false;
}

function submitBatchUpdate(f){
	var fieldName = f.bufieldname.options[f.bufieldname.selectedIndex].value;
	var oldValue = f.buoldvalue.value;
	var newValue = f.bunewvalue.value;
	var collId = f.collid.value;
	var ouid = f.ouid.value;
	var buMatch = 0;
	if(f.bumatch[1].checked) buMatch = 1;
	if(!fieldName){
		alert("Please select a target field name");
		return false;
	}
	if(!oldValue && !newValue){
		alert("Please enter a value in the current or new value fields");
		return false;
	}
	if(oldValue == newValue){
		alert("The values within current and new fields cannot be equal to one another");
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

function toggleCustomDiv2(){
	var f = document.queryform;
	f.q_customfield2.options[0].selected = true;
	f.q_customtype2.options[0].selected = true;
	f.q_customvalue2.value = "";
	f.q_customfield3.options[0].selected = true;
	f.q_customtype3.options[0].selected = true;
	f.q_customvalue3.value = "";
	document.getElementById('customdiv3').style.display = "none";
	toggle('customdiv2');
}

function toggleCustomDiv3(){
	var f = document.queryform;
	f.q_customfield3.options[0].selected = true;
	f.q_customtype3.options[0].selected = true;
	f.q_customvalue3.value = "";
	toggle('customdiv3');
}

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="block" || ele.style.display==""){
			ele.style.display="none";
  		}
	 	else {
	 		ele.style.display="block";
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
	if(document.getElementById("batchupdatediv")) document.getElementById("batchupdatediv").style.display = "none";
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
