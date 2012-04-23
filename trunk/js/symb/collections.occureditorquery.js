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
		&& f.q_customvalue1.value == ""){
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

