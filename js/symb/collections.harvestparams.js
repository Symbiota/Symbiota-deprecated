$(document).ready(function() {
    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

    $( "#taxa" )
    	// don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function( request, response ) {
                $.getJSON( "rpc/taxalist.php", {
                    term: extractLast( request.term ), t: function() { return document.harvestparams.taxontype.value; }
                }, response );
            },
            search: function() {
                // custom minLength
                var term = extractLast( this.value );
                if ( term.length < 4 ) {
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
                terms.push( ui.item.value );
                this.value = terms.join( ", " );
                return false;
            }
        },{});
});

	

function changeTableDisplay(){
    if(document.getElementById("showtable").checked==true){
        document.harvestparams.action = "listtabledisplay.php";
        sessionStorage.collsearchtableview = true;
    }
    else{
        document.harvestparams.action = "list.php";
        sessionStorage.removeItem('collsearchtableview');
    }
}

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

function updateRadius(){
    var radiusUnits = document.getElementById("radiusunits").value;
    var radiusInMiles = document.getElementById("radiustemp").value;
    if(radiusUnits == "km"){
        radiusInMiles = radiusInMiles*0.6214;
    }
    document.getElementById("radius").value = radiusInMiles;
}

function setHarvestParamsForm(){
    var stArr = JSON.parse(starrJson);
    if(!stArr['usethes']){document.harvestparams.thes.checked = false;}
    if(stArr['taxontype']){document.harvestparams.type.value = stArr['taxontype'];}
    if(stArr['taxa']){document.harvestparams.taxa.value = stArr['taxa'];}
    if(stArr['country']){
    	countryStr = stArr['country'];
    	countryArr = countryStr.split(";");
    	if(countryArr.indexOf('USA') > -1 || countryArr.indexOf('usa') > -1) countryStr = countryArr[0];
    	//if(countryStr.indexOf('United States') > -1) countryStr = 'United States';
    	document.harvestparams.country.value = countryStr;
    }
    if(stArr['state']){document.harvestparams.state.value = stArr['state'];}
    if(stArr['county']){document.harvestparams.county.value = stArr['county'];}
    if(stArr['local']){document.harvestparams.local.value = stArr['local'];}
    if(stArr['elevlow']){document.harvestparams.elevlow.value = stArr['elevlow'];}
    if(stArr['elevhigh']){document.harvestparams.elevhigh.value = stArr['elevhigh'];}
    if(stArr['assochost']){document.harvestparams.assochost.value = stArr['assochost'];}
    if(stArr['llbound']){
        var coordArr = stArr['llbound'].split(';');
        document.harvestparams.upperlat.value = coordArr[0];
        document.harvestparams.bottomlat.value = coordArr[1];
        document.harvestparams.leftlong.value = coordArr[2];
        document.harvestparams.rightlong.value = coordArr[3];
    }
    if(stArr['llpoint']){
        var coordArr = stArr['llpoint'].split(';');
        document.harvestparams.pointlat.value = coordArr[0];
        document.harvestparams.pointlong.value = coordArr[1];
        document.harvestparams.radiustemp.value = coordArr[2];
        document.harvestparams.radius.value = coordArr[2]*0.6214;
    }
    if(stArr['collector']){document.harvestparams.collector.value = stArr['collector'];}
    if(stArr['collnum']){document.harvestparams.collnum.value = stArr['collnum'];}
    if(stArr['eventdate1']){document.harvestparams.eventdate1.value = stArr['eventdate1'];}
    if(stArr['eventdate2']){document.harvestparams.eventdate2.value = stArr['eventdate2'];}
    if(stArr['catnum']){document.harvestparams.catnum.value = stArr['catnum'];}
    //if(!stArr['othercatnum']){document.harvestparams.includeothercatnum.checked = false;}
    if(stArr['typestatus']){document.harvestparams.typestatus.checked = true;}
    if(stArr['hasimages']){document.harvestparams.hasimages.checked = true;}
    if(stArr['hasgenetic']){document.harvestparams.hasgenetic.checked = true;}
    if(sessionStorage.collsearchtableview){
        document.getElementById('showtable').checked = true;
        changeTableDisplay();
    }
}

function resetHarvestParamsForm(f){
	f.thes.checked = true;
	f.type.value = 1;
	f.taxa.value = '';
	f.country.value = '';
	f.state.value = '';
	f.county.value = '';
	f.local.value = '';
	f.elevlow.value = '';
	f.elevhigh.value = '';
    if(f.assochost){f.assochost.value = '';}
	f.upperlat.value = '';
	f.bottomlat.value = '';
	f.leftlong.value = '';
	f.rightlong.value = '';
	f.upperlat_NS.value = 'N';
	f.bottomlat_NS.value = 'N';
	f.leftlong_EW.value = 'W';
	f.rightlong_EW.value = 'W';
	f.pointlat.value = '';
	f.pointlong.value = '';
	f.radiustemp.value = '';
	f.pointlat_NS.value = 'N';
	f.pointlong_EW.value = 'W';
	f.radiusunits.value = 'km';
	f.radius.value = '';
	f.collector.value = '';
	f.collnum.value = '';
	f.eventdate1.value = '';
	f.eventdate2.value = '';
	f.catnum.value = '';
	f.includeothercatnum.checked = true;
	f.typestatus.checked = false;
	f.hasimages.checked = false;
    sessionStorage.removeItem('jsonstarr');
    document.getElementById('showtable').checked = false;
    changeTableDisplay();
}

function openPointRadiusMap() {
    mapWindow=open("mappointradius.php","pointradius","resizable=0,width=700,height=630,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
    mapWindow.focus();
}

function openBoundingBoxMap() {
    mapWindow=open("mapboundingbox.php","boundingbox","resizable=0,width=700,height=630,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
    mapWindow.focus();
}