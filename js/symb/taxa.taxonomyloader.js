$(document).ready(function() {
	$("#acceptedstr").autocomplete({ source: "rpc/getacceptedsuggest.php" },{ minLength: 3, autoFocus: true });
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
	if(f.parenttid.value == "" && rankId != "10"){
		alert("Parent taxon field required.");
		return false;
	}

	var accStatusObj = f.acceptstatus;
	if(accStatusObj[0].checked == false){
		var accStr = f.acceptedstr.value;
		if(accStr){
			$.ajax({
				type: "POST",
				url: "rpc/gettid.php",
				data: { sciname: accStr }
			}).done(function( msg ) {
				if(msg){
					f.tidaccepted.value = msg;
					f.submit();
				}
				else{
					alert("ERROR: Accepted taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus.");
				}
			});
		}
		else{
			alert("ERROR: Enter accepted name");
		}
		return false;
	}
	return true;
}

function parseName(f){
	var sciName = f.sciname.value;
	sciName = sciName.replace(/^\s+|\s+$/g,"");
	checkScinameExistance(sciName);
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

function checkScinameExistance(scinameTest){
	if(scinameTest){
		$.ajax({
			type: "POST",
			url: "rpc/gettid.php",
			data: { sciname: scinameTest }
		}).done(function( msg ) {
			if(msg){
				var sciName = document.getElementById("sciname").value;
				alert("INSERT FAILED: "+sciName+" ("+msg+")"+" already exists in database.");
				return false;
			}
			else{
				return true;
			}
		});
	}
	else{
		return false;
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

function checkParentExistance(f){
	var parentStr = f.parentname.value;
	if(parentStr){
		$.ajax({
			type: "POST",
			url: "rpc/gettid.php",
			data: { sciname: parentStr }
		}).done(function( msg ) {
			if(msg){
				f.parenttid.value = msg;
			}
			else{
				alert("Parent does not exist. Please first add parent to system.");
				//document.getElementById("addparentspan").style.display = "inline";
				//document.getElementById("addparentanchor").href = "taxonomyloader.php?target="+f.parentname.value;
				return false;
			}
			return true;
		});
	}
	else{
		return false;
	}
}