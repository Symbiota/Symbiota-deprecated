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
    mapWindow=open("../tools/mappointaid.php?formname=checklistaddform&latname=ncllatcentroid&longname=ncllongcentroid","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
}

function checkEditForm(f){
    var errorText = "";
    if(f.firstname.value.replace(/\s/g, "") == "" ){
        errorText += "\nFirst Name";
    };
    if(f.lastname.value.replace(/\s/g, "") == "" ){
        errorText += "\nLast Name";
    };
    if(f.state.value.replace(/\s/g, "") == "" ){
        errorText += "\nState";
    };
    if(f.country.value.replace(/\s/g, "") == "" ){
        errorText += "\nCountry";
    };
    if(f.email.value.replace(/\s/g, "") == "" ){
        errorText += "\nEmail";
    };

    if(errorText == ""){
        return true;
    }
    else{
        window.alert("The following fields must be filled out:\n " + errorText);
        return false;
    }
}

function checkPwdForm(f){
    var pwd1 = f.newpwd.value.replace(/\s/g, "");
    var pwd2 = f.newpwd2.value.replace(/\s/g, "");
    if(pwd1 == "" || pwd2 == ""){
        window.alert("Both password fields must contain a value.");
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

function checkNewLoginForm(f){
    var pwd1 = f.newloginpwd.value.replace(/\s/g, "");
    var pwd2 = f.newloginpwd2.value.replace(/\s/g, "");
    if(pwd1 == "" || pwd2 == ""){
        window.alert("Both password fields must contain a value.");
        return false;
    }
    if(pwd1 != pwd2){
        window.alert("Password do not match. Please enter again.");
        f.newloginpwd.value = "";
        f.newloginpwd2.value = "";
        f.newloginpwd.focus();
        return false;
    }
    return true;
}

function verifyClAddForm(f){
	if(f.nclname.value == ""){
		alert("The Checklist Name field must have a value before a new checklist can be created");
		return false;
	}
	if(!isNumeric(f.ncllatcentroid.value)){
		alert("The Latitude Centriod field must contain a numeric value only");
		return false;
	}
	if(!isNumeric(f.ncllongcentroid.value)){
		alert("The Longitude Centriod field must contain a numeric value only");
		return false;
	}
	if(!isNumeric(f.nclpointradiusmeters.value)){
		alert("The Point Radius field must contain only a numeric value");
		return false;
	}
	return true;
}
