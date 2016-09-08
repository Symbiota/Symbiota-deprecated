$(document).ready(function() {
	$('#tabs').tabs({ 
		active: tabIndex,
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});

});

function initTaxonAutoComplete(){
	$( "#taxoninput" ).autocomplete({
		source: "rpc/taxasuggest.php",
		minLength: 4,
		autoFocus: true
	});
}

function verifyAddTaxonomyForm(f){
	if(f.editorstatus.value == ""){
		alert("Select the Scope of Relationship");
		return false;
	}
	if(f.taxoninput.value == ""){
		alert("Select the Taxonomic Name");
		return false;
	}
	return true;
}

function openMappingAid() {
    mapWindow=open("../tools/mappointaid.php?formname=checklistaddform&latname=latcentroid&longname=longcentroid","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function openMappingPolyAid() {
	mapWindow=open("../tools/mappolyaid.php?formname=checklistaddform&latname=latcentroid&longname=longcentroid","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if(mapWindow.opener == null) mapWindow.opener = self;
}

function verifyEditProfileForm(f){
    if(f.firstname.value.replace(/\s/g, "") == "" ){
        window.alert("First Name field must have a value ");
        return false;
    };
    if(f.lastname.value.replace(/\s/g, "") == "" ){
        window.alert("Last Name field must have a value ");
        return false;
    };
    if(f.email.value.replace(/\s/g, "") == "" ){
        window.alert("Email field must have a value ");
        return false;
    };
    return true;
}

function verifyPwdForm(f){
    var pwd1 = f.newpwd.value;
    var pwd2 = f.newpwd2.value;
    if(pwd1 == "" || pwd2 == ""){
        window.alert("Both password fields must contain a value.");
        return false;
    }
	if(pwd1.charAt(0) == " " || pwd1.slice(-1) == " "){
		alert("Password cannot start or end with a space, but they can include spaces within the password");
		return false;
	}
	if(pwd1.length < 7){
		alert("Password must be greater than 6 characters");
		return false;
	}
    if(pwd1 != pwd2){
        window.alert("Password do not match. Please enter again.");
        f.newpwd.value = "";
        f.newpwd2.value = "";
        f.newpwd.focus();
        return false;
    }
    return true;
}

function verifyModifyLoginForm(f){
    var newLogin = f.newlogin.value;
    if(newLogin == ""){
        window.alert("New login must have a value");
        return false;
    }
	if( /[^0-9A-Za-z_!@#$-+]/.test( newLogin ) ) {
        alert("Login name should only contain 0-9A-Za-z_!@ (spaces are not allowed)");
        return false;
    }
    if(f.newloginpwd){
		if(f.newloginpwd.value == "") {
			window.alert("Enter your password");
			return false;
		}
	}
    return true;
}

function verifyClAddForm(f){
	if(f.name.value == ""){
		alert("The Checklist Name field must have a value before a new checklist can be created");
		return false;
	}
	if(!isNumeric(f.latcentroid.value)){
		alert("The Latitude Centriod field must contain a numeric value only");
		return false;
	}
	if(!isNumeric(f.longcentroid.value)){
		alert("The Longitude Centriod field must contain a numeric value only");
		return false;
	}
	if(!isNumeric(f.pointradiusmeters.value)){
		alert("The Point Radius field must contain only a numeric value");
		return false;
	}
	if(f.type){ 
		if(f.type.value == "rarespp" && f.locality.value == ""){
			alert("Rare species checklists must have a state value entered into the locality field");
			return false;
		}
	}
	return true;
}

function toggleEditingTools(targetStr){
	document.getElementById("logineditdiv").style.display = "none";
	document.getElementById("pwdeditdiv").style.display = "none";
	document.getElementById("profileeditdiv").style.display = "none";
	toggle(targetStr);
}