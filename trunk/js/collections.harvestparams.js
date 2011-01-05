/**
* key: input for LOOK(1)
* cont: function(res) for return of suggest results
*/ 
function getSuggs(key,cont){
	var taxonType = 1;
	var taxonTypeObj = document.getElementById("taxontype");
	if(taxonTypeObj){
		taxonType = taxonTypeObj.value;
	}
	var script_name = 'rpc/taxalist.php';
	var params = { 'q':key , 't':taxonType }
	$.get(script_name,params,
		function(obj){ 
           // obj is just array of strings
           var res = [];
           for(var i=0;i<obj.length;i++){
        	   res.push({ id:i , value:obj[i]});
           }
        // will build suggestions list
        cont(res); 
     	},
	'json');
}
		
$(document).ready(function(){
	$('input.complete').autocomplete({ ajax_get : getSuggs, multi: true});
});

function checkUpperLat(){
	if(document.harvestparams.upperlat.value != ""){
		if(document.harvestparams.upperlat_NS.value=='N'){
			document.harvestparams.upperlat.value = Math.abs(parseFloat(document.harvestparams.upperlat.value));
		}
		else{
			document.harvestparams.upperlat.value = -1*Math.abs(parseFloat(document.harvestparams.upperlat.value));
		}
	}
}
		
function checkBottomLat(){
	if(document.harvestparams.bottomlat.value != ""){
		if(document.harvestparams.bottomlat_NS.value == 'N'){
			document.harvestparams.bottomlat.value = Math.abs(parseFloat(document.harvestparams.bottomlat.value));
		}
		else{
			document.harvestparams.bottomlat.value = -1*Math.abs(parseFloat(document.harvestparams.bottomlat.value));
		}
	}
}

	function checkRightLong(){
		if(document.harvestparams.rightlong.value != ""){
			if(document.harvestparams.rightlong_EW.value=='E'){
				document.harvestparams.rightlong.value = Math.abs(parseFloat(document.harvestparams.rightlong.value));
			}
			else{
				document.harvestparams.rightlong.value = -1*Math.abs(parseFloat(document.harvestparams.rightlong.value));
			}
		}
	}

	function checkLeftLong(){
		if(document.harvestparams.leftlong.value != ""){
			if(document.harvestparams.leftlong_EW.value=='E'){
				document.harvestparams.leftlong.value = Math.abs(parseFloat(document.harvestparams.leftlong.value));
			}
			else{
				document.harvestparams.leftlong.value = -1*Math.abs(parseFloat(document.harvestparams.leftlong.value));
			}
		}
	}

	function checkPointLat(){
		if(document.harvestparams.pointlat.value != ""){
			if(document.harvestparams.pointlat_NS.value=='N'){
				document.harvestparams.pointlat.value = Math.abs(parseFloat(document.harvestparams.pointlat.value));
			}
			else{
				document.harvestparams.pointlat.value = -1*Math.abs(parseFloat(document.harvestparams.pointlat.value));
			}
		}
	}

	function checkPointLong(){
		if(document.harvestparams.pointlong.value != ""){
			if(document.harvestparams.pointlong_EW.value=='E'){
				document.harvestparams.pointlong.value = Math.abs(parseFloat(document.harvestparams.pointlong.value));
			}
			else{
				document.harvestparams.pointlong.value = -1*Math.abs(parseFloat(document.harvestparams.pointlong.value));
			}
		}
	}


function checkForm(){
	var frm = document.harvestparams;

	//make sure they have filled out at least one field.
	if((frm.taxa.value == '') && (frm.country.value == '') && (frm.state.value == '') && (frm.county.value == '') && 
		(frm.locality.value == '') && (frm.upperlat.value == '') && (frm.pointlat.value == '') && 
		(frm.collector.value == '') && (frm.collnum.value == '')){
        alert("Please fill in at least one search parameter!");
        return false;
    }
 
    if(frm.upperlat.value != '' || frm.bottomlat.value != '' || frm.leftlong.value != '' || frm.rightlong.value != ''){
        // if Lat/Long field is filled in, they all should have a value!
        if(frm.upperlat.value == '' || frm.bottomlat.value == '' || frm.leftlong.value == '' || frm.rightlong.value == ''){
			alert("Error: Please make all Lat/Long bounding box values contain a value or all are empty");
			return false;
        }

		// Check to make sure lat/longs are valid.
		if(Math.abs(frm.upperlat.value) > 90 || Math.abs(frm.bottomlat.value) > 90 || Math.abs(frm.pointlat.value) > 90){
			alert("Latitude values can not be greater than 90 or less than -90.");
			return false;
		} 
		if(Math.abs(frm.leftlong.value) > 180 || Math.abs(frm.rightlong.value) > 180 || Math.abs(frm.pointlong.value) > 180){
			alert("Longitude values can not be greater than 180 or less than -180.");
			return false;
		} 
		if(frm.upperlat.value < frm.bottomlat.value){
			alert("Your northern latitude value is less then your southern latitude value. Please correct this.");
			return false;
		}
		if(eval(frm.leftlong.value) > eval(frm.rightlong.value)){
			alert("Your western longitude value is greater then your eastern longitude value. Please correct this. Note that western hemisphere longitudes in the decimal format are negitive.");
			return false;
		}
    }

	//Same with point radius fields
    if(frm.pointlat.value != '' || frm.pointlong.value != '' || frm.radius.value != ''){
    	if(frm.pointlat.value == '' || frm.pointlong.value == '' || frm.radius.value == ''){
    		alert("Error: Please make all Lat/Long point-radius values contain a value or all are empty");
			return false;
		}
	}

    return true;
}

function updateRadius(){
	var radiusUnits = document.getElementById("radiusunits").value;
	var radiusInMiles = document.getElementById("radiustemp").value;
	if(radiusUnits == "km"){
		radiusInMiles = radiusInMiles*0.6214; 
	}
	document.getElementById("radius").value = radiusInMiles;
}

function openPointRadiusMap() {
     mapWindow=open("mappointradius.php","pointradius","resizable=0,width=650,height=600,left=20,top=20");
     if (mapWindow.opener == null) mapWindow.opener = self;
}

function openBoundingBoxMap() {
     mapWindow=open("mapboundingbox.php","boundingbox","resizable=0,width=530,height=500,left=20,top=20");
     if (mapWindow.opener == null) mapWindow.opener = self;
}
