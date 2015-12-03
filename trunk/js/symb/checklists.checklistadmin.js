$('html').hide();
$(document).ready(function() {
	$('html').show();
});

$(document).ready(function() {
	$('#tabs').tabs({ 
		active: tabIndex,
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});
});

function openMappingAid() {
	mapWindow=open("../tools/mappointaid.php?formname=editclmatadata&latname=latcentroid&longname=longcentroid","mapaid","resizable=0,width=800,height=700,left=20,top=20");
    if(mapWindow.opener == null) mapWindow.opener = self;
}

function openMappingPolyAid() {
	mapWindow=open("../tools/mappolyaid.php?formname=editclmatadata&latname=latcentroid&longname=longcentroid","mapaid","resizable=0,width=800,height=700,left=20,top=20");
    if(mapWindow.opener == null) mapWindow.opener = self;
}

function openPointAid(latDef,lngDef) {
	var tid = document.pointaddform.pointtid.value;
	pointWindow=open("mappointaid.php?latcenter="+latDef+"&lngcenter="+lngDef+"&tid="+tid,"pointaid","resizable=0,width=800,height=700,left=20,top=20");
    if(pointWindow.opener == null) pointWindow.opener = self;
}

function validateMetadataForm(f){
	if(f.latcentroid.value != ""){
		if(f.longcentroid.value == ""){
			alert("If longitude has a value, latitude must also have a value");
			return false;
		}
		if(!isNumeric(f.latcentroid.value)){
			alert("Latitude must be strictly numeric (decimal format: e.g. 34.2343)");
			return false;
		}
		if(Math.abs(f.latcentroid.value) > 90){
			alert("Latitude values can not be greater than 90 or less than -90.");
			return false;
		} 
	} 
	if(f.longcentroid.value != ""){
		if(f.latcentroid.value == ""){
			alert("If latitude has a value, longitude must also have a value");
			return false;
		}
		if(!isNumeric(f.longcentroid.value)){
			alert("Longitude must be strictly numeric (decimal format: e.g. -112.2343)");
			return false;
		}
		if(Math.abs(f.longcentroid.value) > 180){
			alert("Longitude values can not be greater than 180 or less than -180.");
			return false;
		}
	} 
	if(!isNumeric(f.pointradiusmeters.value)){
		alert("Point radius must be a numeric value only");
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

function togglePoint(f){
	var objDiv = document.getElementById('pointlldiv');
	if(objDiv){
		if(f.pointtid.value == ""){
			objDiv.style.display = "none";
		}
		else{
			objDiv.style.display = "block";
		}
	}
}

function verifyPointAddForm(f){
	if(f.pointtid.value == ""){
		alert("Please select a taxon");
		return false;
	}
	if(f.pointlat.value == "" || f.pointlng.value == ""){
		alert("Please enter coordinates");
		return false;
	}
	return true;
}

function showImagesDefaultChecked(f){
	if(f.dimages.checked){
		f.dvouchers.checked = false;
		f.dvouchers.disabled = true;
		f.dauthors.checked = false;
		f.dauthors.disabled = true;
	}
	else{
		f.dvouchers.disabled = false; 
		f.dauthors.disabled = false; 
	}
}

function validateAddChildForm(f){
	
}