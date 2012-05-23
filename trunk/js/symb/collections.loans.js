$(document).ready(function() {
	if(!navigator.cookieEnabled){
		alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
	}

	$('#tabs').tabs();

});

function selectAll(cb){
	boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var dbElements = document.getElementsByName("occid[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		dbElement.checked = boxesChecked;
	}
}

function toggle(target){
	var objDiv = document.getElementById(target);
	if(objDiv){
		if(objDiv.style.display=="none"){
			objDiv.style.display = "block";
		}
		else{
			objDiv.style.display = "none";
		}
	}
	else{
	  	var divs = document.getElementsByTagName("div");
	  	for (var h = 0; h < divs.length; h++) {
	  	var divObj = divs[h];
			if(divObj.className == target){
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

function ProcessReport(){
	if(document.pressed == 'invoice'){
		document.reportsform.action ="reports/defaultinvoice.php";
	}
	else if(document.pressed == 'spec'){
		document.reportsform.action ="reports/defaultspecimenlist.php";
	}
	else if(document.pressed == 'label'){
		document.reportsform.action ="reports/defaultmailinglabel.php";
	}
	else if(document.pressed == 'envelope'){
		document.reportsform.action ="reports/defaultenvelope.php";
	}
	return true;
}

function displayNewLoanOut(){
	toggle('newloanoutdiv');
	var f = document.newloanoutform;
	if(f.loanidentifierown.value == ""){
		generateNewId(f.collid.value,f.loanidentifierown,"out");
	}
}

function displayNewLoanIn(){
	toggle('newloanindiv');
	var f = document.newloaninform;
	if(f.loanidentifierborr.value == ""){
		generateNewId(f.collid.value,f.loanidentifierborr,"in");
	}
}

function displayNewExchange(){
	toggle('newexchangediv');
	var f = document.newexchangegiftform;
	if(f.identifier.value == ""){
		generateNewId(f.collid.value,f.identifier,"ex");
	}
}

function generateNewId(collId,targetObj,idType){
	xmlHttp=GetXmlHttpObject();
	if (xmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return false;
	}
	var url="rpc/generatenextid.php?idtype="+idType+"&collid="+collId;
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			targetObj.value = xmlHttp.responseText;
		}
	};
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
}

function verfifyLoanOutAddForm(f){
	if(f.reqinstitution.options[f.reqinstitution.selectedIndex].value == 0){
		alert("Select an institution");
		return false;
	}
	if(f.loanidentifierown.value == ""){
		alert("Enter a loan identifier");
		return false;
	}
	return true;
}

function verifyLoanInAddForm(f){
	if(f.iidowner.options[f.iidowner.selectedIndex].value == 0){
		alert("Select an institution");
		return false;
	}
	if(f.loanidentifierborr.value == ""){
		alert("Enter a loan identifier");
		return false;
	}
	return true;
}

function verfifyExchangeAddForm(f){
	if(f.iid.options[f.iid.selectedIndex].value == 0){
		alert("Select an institution");
		return false;
	}
	if(f.identifier.value == ""){
		alert("Enter a loan identifier");
		return false;
	}
	return true;
}

function verifySpecEditForm(f){
	//Make sure at least on specimen checkbox is checked
	var cbChecked = false;
	var dbElements = document.getElementsByName("occid[]");
	for(i = 0; i < dbElements.length; i++){
		var dbElement = dbElements[i];
		if(dbElement.checked){
			cbChecked = true;
			break;
		}
	}
	if(!cbChecked){
		alert("Please select specimens to which you wish to apply the action");
		return false;
	}

	//If task equals delete, confirm action
	var applyTaskObj = f.applytask;
	var l = applyTaskObj.length;
	var applyTaskValue = "";
	for(var i = 0; i < l; i++) {
		if(applyTaskObj[i].checked) {
			applyTaskValue = applyTaskObj[i].value;
		}
	}
	if(applyTaskValue == "delete"){
		return confirm("Are you sure you want to remove selected specimens from this loan?");
	}

	return true;
}

function addSpecimen(f){ 
	var catalogNumber = f.catalognumber.value;
	var loanid = f.loanid.value;
	var collid = f.collid.value;
	if(!catalogNumber){
		alert("Please enter a catalog number!");
		return false;
	}
	else{
		xmlHttp=GetXmlHttpObject();
		if (xmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return false;
		}
		var url="rpc/insertloanspecimens.php";
		url=url+"?loanid="+loanid;
		url=url+"&catalognumber="+catalogNumber;
		url=url+"&collid="+collid;
		xmlHttp.onreadystatechange=function(){
			if(xmlHttp.readyState==4 && xmlHttp.status==200){
				responseCode = xmlHttp.responseText;
				if(responseCode == "0"){
					document.getElementById("addspecsuccess").style.display = "none";
					document.getElementById("addspecerr1").style.display = "block";
					document.getElementById("addspecerr2").style.display = "none";
					document.getElementById("addspecerr3").style.display = "none";
					setTimeout(function () { 
						document.getElementById("addspecerr1").style.display = "none";
						}, 5000);
					//alert("ERROR: Specimen record not found in database.");
				}
				else if(responseCode == "2"){
					document.getElementById("addspecsuccess").style.display = "none";
					document.getElementById("addspecerr1").style.display = "none";
					document.getElementById("addspecerr2").style.display = "block";
					document.getElementById("addspecerr3").style.display = "none";
					setTimeout(function () { 
						document.getElementById("addspecerr2").style.display = "none";
						}, 5000);
					//alert("ERROR: More than one specimen with that catalog number.");
				}
				else if(responseCode == "3"){
					document.getElementById("addspecsuccess").style.display = "none";
					document.getElementById("addspecerr1").style.display = "none";
					document.getElementById("addspecerr2").style.display = "none";
					document.getElementById("addspecerr3").style.display = "block";
					setTimeout(function () { 
						document.getElementById("addspecerr3").style.display = "none";
						}, 5000);
					//alert("ERROR: More than one specimen with that catalog number.");
				}
				else{
					f.catalognumber.value = "";
					document.getElementById("addspecsuccess").style.display = "block";
					document.getElementById("addspecerr1").style.display = "none";
					document.getElementById("addspecerr2").style.display = "none";
					document.getElementById("addspecerr3").style.display = "none";
					setTimeout(function () { 
						document.getElementById("addspecsuccess").style.display = "none";
						}, 5000);
					//alert("SUCCESS: Specimen added to loan.");
				}
			}
		};
		xmlHttp.open("POST",url,true);
		xmlHttp.send(null);
	}
	return false;
}

function openOccurrenceDetails(occid){
	occWindow=open("../individual/index.php?occid="+occid,"occdetails","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
	if(occWindow.opener == null) occWindow.opener = self;
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

function verifyDate(eventDateInput){
	//test date and return mysqlformat
	var dateStr = eventDateInput.value;
	if(dateStr == "") return true;

	var dateArr = parseDate(dateStr);
	if(dateArr['y'] == 0){
		alert("Unable to interpret Date. Please use the following formats: yyyy-mm-dd, mm/dd/yyyy, or dd mmm yyyy");
		return false;
	}
	else{
		//Check to see if date is in the future 
		try{
			var testDate = new Date(dateArr['y'],dateArr['m']-1,dateArr['d']);
			var today = new Date();
			if(testDate > today){
				alert("The date you entered has not happened yet. Please revise.");
				return false;
			}
		}
		catch(e){
		}

		//Check to see if day is valid
		if(dateArr['d'] > 28){
			if(dateArr['d'] > 31 
				|| (dateArr['d'] == 30 && dateArr['m'] == 2) 
				|| (dateArr['d'] == 31 && (dateArr['m'] == 4 || dateArr['m'] == 6 || dateArr['m'] == 9 || dateArr['m'] == 11))){
				alert("The Day (" + dateArr['d'] + ") is invalid for that month");
				return false;
			}
		}

		//Enter date into date fields
		var mStr = dateArr['m'];
		if(mStr.length == 1){
			mStr = "0" + mStr;
		}
		var dStr = dateArr['d'];
		if(dStr.length == 1){
			dStr = "0" + dStr;
		}
		eventDateInput.value = dateArr['y'] + "-" + mStr + "-" + dStr;
	}
	return true;
}

function parseDate(dateStr){
	var y = 0;
	var m = 0;
	var d = 0;
	try{
		var validformat1 = /^\d{4}-\d{1,2}-\d{1,2}$/ //Format: yyyy-mm-dd
		var validformat2 = /^\d{1,2}\/\d{1,2}\/\d{2,4}$/ //Format: mm/dd/yyyy
		var validformat3 = /^\d{1,2} \D+ \d{2,4}$/ //Format: dd mmm yyyy
		if(validformat1.test(dateStr)){
			var dateTokens = dateStr.split("-");
			y = dateTokens[0];
			m = dateTokens[1];
			d = dateTokens[2];
		}
		else if(validformat2.test(dateStr)){
			var dateTokens = dateStr.split("/");
			m = dateTokens[0];
			d = dateTokens[1];
			y = dateTokens[2];
			if(y.length == 2){
				if(y < 20){
					y = "20" + y;
				}
				else{
					y = "19" + y;
				}
			}
		}
		else if(validformat3.test(dateStr)){
			var dateTokens = dateStr.split(" ");
			d = dateTokens[0];
			mText = dateTokens[1];
			y = dateTokens[2];
			if(y.length == 2){
				if(y < 15){
					y = "20" + y;
				}
				else{
					y = "19" + y;
				}
			}
			mText = mText.substring(0,3);
			mText = mText.toLowerCase();
			var mNames = new Array("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");
			m = mNames.indexOf(mText)+1;
		}
		else if(dateObj instanceof Date && dateObj != "Invalid Date"){
			var dateObj = new Date(dateStr);
			y = dateObj.getFullYear();
			m = dateObj.getMonth() + 1;
			d = dateObj.getDate();
		}
	}
	catch(ex){
	}
	var retArr = new Array();
	retArr["y"] = y.toString();
	retArr["m"] = m.toString();
	retArr["d"] = d.toString();
	return retArr;
}
