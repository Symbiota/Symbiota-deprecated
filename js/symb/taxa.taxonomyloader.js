$(document).ready(function() {
	$("#acceptedstr").autocomplete({ 
		source: "rpc/getacceptedsuggest.php",
		minLength: 2, 
		autoFocus: true 
	});
	
	$("#parentname").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/gettaxasuggest.php", { term: request.term, rhigh: $("#rankid").val() }, response );
		},
		change: function( event, ui ) {
			checkParentExistance(document.loaderform);
		},
		minLength: 2,
		autoFocus: true
	});
});

function verifyLoadForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field required.");
		return false;
	}
	if(f.unitname1.value == ""){
		alert("Unit Name 1 (genus or uninomial) field required.");
		return false;
	}
	var rankId = f.rankid.value;
	if(rankId == ""){
		alert("Taxon rank field required.");
		return false;
	}
	if(f.parentname.value == "" && rankId > "10"){
		alert("Parent taxon required");
		return false;
	}
	if(f.parenttid.value == "" && rankId > "10"){
		if(!checkParentExistance(f)) return false;
	}
	//Verify that name doesn't already exist
	$.ajax({
		type: "POST",
		url: "rpc/gettid.php",
		async: false,
		data: { sciname: f.sciname.value, rankid: f.rankid.value, author: f.author.value }
	}).done(function( msg ) {
		if(msg != '0'){
			var sciName = document.getElementById("sciname").value;
			alert("Taxon "+sciName+" "+f.author.value+" ("+msg+") already exists in database");
			return false;
		}
	});

	//If name is not accepted, verify accetped name
	var accStatusObj = f.acceptstatus;
	if(accStatusObj[0].checked == false){
		if(f.acceptedstr.value == ""){
			alert("Accepted name needs to have a value");
			return false
		}
		if(f.tidaccepted.value == "" && checkAcceptedExistance(f) == false){
			return false;
		}
	}

	return true;
}

function parseName(f){
	var sciName = f.sciname.value;
	sciName = sciName.replace(/^\s+|\s+$/g,"");
	f.reset();
	f.sciname.value = sciName;
	var sciNameArr = new Array(); 
	var activeIndex = 0;
	var unitName1 = "";
	var unitName2 = "";
	var rankId = "";
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

function acceptanceChanged(f){
	var accStatusObj = f.acceptstatus;
	if(accStatusObj[0].checked){
		document.getElementById("accdiv").style.display = "none";
	}
	else{
		document.getElementById("accdiv").style.display = "block";
	}
}

function checkAcceptedExistance(f){
	if(f.acceptedstr.value){
		$.ajax({
			type: "POST",
			url: "rpc/gettid.php",
			async: false,
			data: { sciname: f.acceptedstr.value }
		}).done(function( msg ) {
			if(msg == 0){
				alert("Accepted does not exist. Add parent to thesaurus before adding this name.");
				return false;
			}
			else{
				if(msg.indexOf(",") == -1){
					f.tidaccepted.value = msg;
					return true;
				}
				else{
					alert("Accepted is matching two different names in the thesaurus. Please select taxon with the correct author.");
					return false;
				}
			}
		});
	}
	else{
		return false;
	}
}

function checkParentExistance(f){
	var parentStr = f.parentname.value;
	if(parentStr){
		$.ajax({
			type: "POST",
			url: "rpc/gettid.php",
			async: false,
			data: { sciname: parentStr }
		}).done(function( msg ) {
			if(msg == 0){
				alert("Parent does not exist. Please first add parent to system.");
				return false;
			}
			else{
				if(msg.indexOf(",") == -1){
					f.parenttid.value = msg;
					return true;
				}
				else{
					alert("Parent is matching two different names in the thesaurus. Please select taxon with the correct author.");
					return false;
				}
			}
		});
	}
	else{
		return false;
	}
}