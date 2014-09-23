$(document).ready(function() {
	if(!navigator.cookieEnabled){
		alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
	}

	$('#tabs').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});
	
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	$( "#addauthorsearch" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( "rpc/authorlist.php", {
					term: extractLast( request.term ), t: function() { return document.authorform.addauthorsearch.value; }
				}, response );
			},
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.label );
				document.getElementById('refauthorid').value = ui.item.value;
				this.value = terms;
				addAuthorToRef();
				return false;
			},
			change: function (event, ui) {
				if (!ui.item) {
					this.value = '';
					if (confirm("Would you like to add a new author to the database?")) {
						openNewAuthorWindow();
					}
				}
			}
		},{});
});

function addAuthorToRef(){
	var refauthid = document.getElementById('refauthorid').value;
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/authormanager.php?refid="+refid+"&action=addauthor&refauthid="+refauthid;
	
	var authorList = '';
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			authorList = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	document.getElementById("authorlistdiv").innerHTML = authorList;
	document.getElementById("addauthorsearch").value = '';
	document.getElementById("refauthorid").value = '';
}

function deleteRefAuthor(refauthid){
	if (confirm("Are you sure you would like to remove this author from this reference?")) {
		var sutXmlHttp=GetXmlHttpObject();
		if (sutXmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return;
		}
		
		var url="rpc/authormanager.php?refid="+refid+"&action=deleterefauthor&refauthid="+refauthid;
		
		var authorList = '';
		sutXmlHttp.onreadystatechange=function(){
			if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
				authorList = sutXmlHttp.responseText;
			}
		};
		sutXmlHttp.open("POST",url,false);
		sutXmlHttp.send(null);
		document.getElementById("authorlistdiv").innerHTML = authorList;
		document.getElementById("addauthorsearch").value = '';
		document.getElementById("refauthorid").value = '';
	}
}

function deleteRefLink(table,field,type,id){
	if (confirm("Are you sure you would like to remove this link from this reference?")) {
		var sutXmlHttp=GetXmlHttpObject();
		if (sutXmlHttp==null){
			alert ("Your browser does not support AJAX!");
			return;
		}
		
		var url="rpc/authormanager.php?refid="+refid+"&action=deletereflink&table="+table+"&field="+field+"&id="+id+"&type="+type;
		
		var authorList = '';
		sutXmlHttp.onreadystatechange=function(){
			if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
				authorList = sutXmlHttp.responseText;
			}
		};
		sutXmlHttp.open("POST",url,false);
		sutXmlHttp.send(null);
		document.getElementById(table).innerHTML = authorList;
	}
}

function openNewAuthorWindow(){
	var urlStr = 'authoreditor.php?refid='+refid;
	newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width=470,height=300');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function processNewAuthor(f){
	var firstName = f.firstname.value;
	var middleName = f.middlename.value;
	var lastName = f.lastname.value;
	var sutXmlHttp=GetXmlHttpObject();
	if (sutXmlHttp==null){
		alert ("Your browser does not support AJAX!");
		return;
	}
	
	var url="rpc/authormanager.php?refid="+refid+"&action=createauthor&firstname="+firstName+"&midname="+middleName+"&lastname="+lastName;
	
	var authorList = '';
	
	sutXmlHttp.onreadystatechange=function(){
		if(sutXmlHttp.readyState==4 && sutXmlHttp.status==200){
			authorList = sutXmlHttp.responseText;
		}
	};
	sutXmlHttp.open("POST",url,false);
	sutXmlHttp.send(null);
	opener.document.getElementById("authorlistdiv").innerHTML = authorList;
	opener.document.getElementById("addauthorsearch").value = '';
	opener.document.getElementById("refauthorid").value = '';
	self.close();
}

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

function verifyNewRefForm(f){
	if(document.getElementById("newreftitle").value == ""){
		alert("Please enter the title of the reference.");
		return false;
	}
	if(document.getElementById("newreftype").selectedIndex < 2){
		alert("Please select the type of reference.");
		return false;
	}
	return true;
}

function verifyEditRefForm(f){
	if(document.getElementById("title")){
		if(document.getElementById("title").value == ""){
			alert("Please enter the title of the reference.");
			return false;
		}
	}
	if(document.getElementById("ReferenceTypeId").selectedIndex < 2){
		alert("Please select the type of reference.");
		return false;
	}
	return true;
}

function verifyRefTypeChange(){
	if(document.getElementById("ReferenceTypeId").selectedIndex > 1){
		if (confirm("Are you sure you would like to change the reference type?")) {
			document.getElementById("dynamicInput").innerHTML = '<input name="formsubmit" type="hidden" value="Edit Reference" />';
			document.getElementById("referenceeditform").submit();
		}
	}
}

function verifySearchRefForm(f){
	var titleKeyword = document.getElementById("searchtitlekeyword").value;
	var authorKeyword = document.getElementById("searchauthor").value;
	if(!titleKeyword && !authorKeyword){
		alert("Please enter either a title keyword or an author's last name to search references.");
		return false;
	}
	return true;
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