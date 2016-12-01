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

function setOrderBy(formObject){
	if(formObject.value != ""){
		var inputName = formObject.name;
		inputName.substring(2)
		if(formObject.form.orderby.value == "") formObject.form.orderby.value = inputName.substring(2);
	}
}

function detectBatchUpdateField(){
	var fieldSelected = document.getElementById('bufieldname').value;
	if(fieldSelected == "processingstatus"){
		var buNewValue = '<select name="bunewvalue">';
		buNewValue += '<option value="unprocessed">Unprocessed</option>';
		buNewValue += '<option value="unprocessed/nlp">Unprocessed/NLP</option>';
		buNewValue += '<option value="stage 1">Stage 1</option>';
		buNewValue += '<option value="stage 2">Stage 2</option>';
		buNewValue += '<option value="stage 3">Stage 3</option>';
		buNewValue += '<option value="pending review">Pending Review</option>';
		buNewValue += '<option value="expert required">Expert Required</option>';
		buNewValue += '<option value="reviewed">Reviewed</option>';
		buNewValue += '<option value="closed">Closed</option>';
		buNewValue += '</select>';
		document.getElementById("bunewvaluediv").innerHTML = buNewValue;
	}
	else if(!$("input[name='bunewvalue']").val()){
		document.getElementById("bunewvaluediv").innerHTML = '<input name="bunewvalue" type="text" value="" />';
	}
}

function verifyQueryForm(f){
	//if(f.q_catalognumber.value == "" && f.q_othercatalognumbers.value == ""  
	//	&& f.q_recordedby.value == "" && f.q_recordnumber.value == "" && f.q_eventdate.value == ""
	//	&& f.q_recordenteredby.value == "" && f.q_processingstatus.value == "" && f.q_datelastmodified.value == "" 
	//	&& (f.q_customfield1.selectedIndex == 0 && (f.q_customvalue1.value == "" || f.q_customtype1.selectedIndex != 1)) 
	//	&& ((f.q_observeruid.type == "hidden" && f.q_observeruid.value == "") || (f.q_observeruid.type == "checkbox" && f.q_observeruid.checked == false))){
	//	alert("Query form is empty! Please enter a value to query by.");
	//	return false;
	//}

	if(!verifyLeaveForm()) return false;

	var validformat1 = /^\s*[<>]{0,1}\s{0,1}\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd, >yyyy-mm-dd, <yyyy-mm-dd
	var validformat2 = /^\s*\d{4}-\d{2}-\d{2}\s{1,3}-\s{1,3}\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd - yyyy-mm-dd
	var validformat3 = /^\s*\d{4}-\d{2}-\d{2}\s{1,3}to\s{1,3}\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd to yyyy-mm-dd
	var validformat4 = /^\s*>{1}\s{0,1}\d{4}-\d{2}-\d{2}\s{1,3}AND\s{1,3}<{1}\s{0,1}\d{4}-\d{2}-\d{2}\s*$/i //Format: >yyyy-mm-dd AND <yyyy-mm-dd

	if(f.q_eventdate){
		var edDateStr = f.q_eventdate.value;
		if(edDateStr){
			try{
				if(!validformat1.test(edDateStr) && !validformat2.test(edDateStr) && !validformat3.test(edDateStr) && !validformat4.test(edDateStr)){
					alert("Event date must follow formats: YYYY-MM-DD, YYYY-MM-DD - YYYY-MM-DD, YYYY-MM-DD to YYYY-MM-DD, >YYYY-MM-DD, <YYYY-MM-DD, >YYYY-MM-DD AND <YYYY-MM-DD");
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
				if(!validformat1.test(modDateStr) && !validformat2.test(modDateStr) && !validformat3.test(modDateStr) && !validformat4.test(edDateStr)){
					alert("Date last modified must follow formats: YYYY-MM-DD, YYYY-MM-DD - YYYY-MM-DD, YYYY-MM-DD to YYYY-MM-DD, >YYYY-MM-DD, <YYYY-MM-DD, >YYYY-MM-DD AND <YYYY-MM-DD");
					return false;
				}
			}
			catch(ex){
			}
		}
	}
	if(f.q_dateentered){
		var dateEnteredStr = f.q_dateentered.value;
		if(dateEnteredStr){
			try{
				if(!validformat1.test(dateEnteredStr) && !validformat2.test(dateEnteredStr) && !validformat3.test(dateEnteredStr) && !validformat4.test(edDateStr)){
					alert("Date entered must follow formats: YYYY-MM-DD, YYYY-MM-DD - YYYY-MM-DD, YYYY-MM-DD to YYYY-MM-DD, >YYYY-MM-DD, <YYYY-MM-DD, >YYYY-MM-DD AND <YYYY-MM-DD");
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
	f.q_catalognumber.value = "";
	f.q_othercatalognumbers.value = "";
	f.q_recordedby.value = "";
	f.q_recordnumber.value = "";
	f.q_eventdate.value = "";
	f.q_recordenteredby.value = "";
	f.q_dateentered.value = "";
	f.q_datelastmodified.value = "";
	f.q_processingstatus.value = "";
	if(document.getElementById("q_exsiccatiid")){
		f.q_exsiccatiid.value = "";
	}
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
	f.q_withoutimg.checked = false;
	f.orderby.value = "";
	f.orderbydir.value = "ASC";
}

function submitBatchUpdate(f){
	var fieldName = f.bufieldname.options[f.bufieldname.selectedIndex].value;
	var oldValue = f.buoldvalue.value;
	var newValue = f.bunewvalue.value;
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

	$.ajax({
		type: "POST",
		url: "rpc/batchupdateverify.php",
		dataType: "json",
		data: { collid: f.collid.value, fieldname: fieldName, oldvalue: oldValue, bumatch: buMatch, ouid: f.ouid.value }
	}).done(function( retCnt ) {
		if(confirm("You are about to update "+retCnt+" records.\nNote that you won't be able to undo this Replace operation!\nDo you want to continue?")){
			f.submit();
		}
	});
}

function customSelectChanged(targetSelect){
	var sourceObj = document.queryform.q_customfield1;
	var targetObj = document.queryform.q_customtype1;
	if(targetSelect == 2){
		sourceObj = document.queryform.q_customfield2;
		targetObj = document.queryform.q_customtype2;
	}
	else if(targetSelect == 3){
		sourceObj = document.queryform.q_customfield3;
		targetObj = document.queryform.q_customtype3;
	}
	if(sourceObj.value == "ocrFragment"){
		targetObj.value = "LIKE";
	}
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
		if(ele.style.display=="none" || ele.style.display==""){
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
					divObj.style.display="";
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