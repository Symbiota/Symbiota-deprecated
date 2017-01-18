function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			if(ele.id.substring(0,5) == "minus" || ele.id.substring(0,4) == "plus"){
				ele.style.display = "inline";
	  		}
			else{
				ele.style.display = "block";
			}
  		}
	 	else {
	 		ele.style.display="none";
	 	}
	}
}

function toggleCat(catid){
	toggle("minus-"+catid);
	toggle("plus-"+catid);
	toggle("cat-"+catid);
}

function togglePid(pid){
	toggle("minus-pid-"+pid);
	toggle("plus-pid-"+pid);
	toggle("pid-"+pid);
}

function selectAll(cb){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var f = cb.form;
	for(var i=0;i<f.length;i++){
		if(f.elements[i].name == "db[]" || f.elements[i].name == "cat[]") f.elements[i].checked = boxesChecked;
	}
}

function uncheckAll(){
	if(document.getElementById('dballcb')){
		document.getElementById('dballcb').checked = false;
	}
	if(document.getElementById('dballspeccb')){
		document.getElementById('dballspeccb').checked = false;
	}
	if(document.getElementById('dballobscb')){
		document.getElementById('dballobscb').checked = false;
	}
}

function selectAllCat(cb,target){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
		uncheckAll();
	}
	var inputObjs = document.getElementsByTagName("input");
  	for (i = 0; i < inputObjs.length; i++) {
  		var inputObj = inputObjs[i];
  		if(inputObj.getAttribute("class") == target || inputObj.getAttribute("className") == target){
  			inputObj.checked = boxesChecked;
  		}
  	}
}

function unselectCat(catTarget){
	var catObj = document.getElementById(catTarget);
	catObj.checked = false;
	uncheckAll();
}

function selectAllPid(cb){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var target = "pid-"+cb.value;
	var inputObjs = document.getElementsByTagName("input");
  	for (i = 0; i < inputObjs.length; i++) {
  		var inputObj = inputObjs[i];
  		if(inputObj.getAttribute("class") == target || inputObj.getAttribute("className") == target){
  			inputObj.checked = boxesChecked;
  		}
  	}
}

function verifyCollForm(f){
	var formVerified = false;
	for(var h=0;h<f.length;h++){
		if(f.elements[h].name == "db[]" && f.elements[h].checked){
			formVerified = true;
			break;
		}
		if(f.elements[h].name == "cat[]" && f.elements[h].checked){
			formVerified = true;
			break;
		}
	}
	if(!formVerified){
		alert("Please choose at least one collection!");
		return false;
	}
	else{
		for(var i=0;i<f.length;i++){
			if(f.elements[i].name == "cat[]" && f.elements[i].checked){
				//Uncheck all db input elements within cat div 
				var childrenEle = document.getElementById('cat-'+f.elements[i].value).children;
				for(var j=0;j<childrenEle.length;j++){
					if(childrenEle[j].tagName == "DIV"){
						var divChildren = childrenEle[j].children;
						for(var k=0;k<divChildren.length;k++){
							var divChildren2 = divChildren[k].children;
							for(var l=0;l<divChildren2.length;l++){
								if(divChildren2[l].tagName == "INPUT"){
									divChildren2[l].checked = false;
								}
							}
						}
					}
				}
			}
		}
	}
  	return formVerified;
}

function verifyOtherCatForm(f){
	var pidElems = document.getElementsByName("pid[]");
	for(i = 0; i < pidElems.length; i++){
		var pidElem = pidElems[i];
		if(pidElem.checked) return true;
	}
	var clidElems = document.getElementsByName("clid[]");
	for(i = 0; i < clidElems.length; i++){
		var clidElem = clidElems[i];
		if(clidElem.checked) return true;
	}
   	alert("Please choose at least one search region!");
	return false;
}

function checkKey(e){
	var key;
	if(window.event){
		key = window.event.keyCode;
	}else{
		key = e.keyCode;
	}
	if(key == 13){
		document.collections.submit();
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

function checkHarvestparamsForm(){
    var frm = document.harvestparams;

    //make sure they have filled out at least one field.
    if((frm.taxa.value == '') && (frm.country.value == '') && (frm.state.value == '') && (frm.county.value == '') &&
        (frm.locality.value == '') && (frm.upperlat.value == '') && (frm.pointlat.value == '') &&
        (frm.collector.value == '') && (frm.collnum.value == '') && (frm.eventdate.value == '')){
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
        if(parseFloat(frm.upperlat.value) < parseFloat(frm.bottomlat.value)){
            alert("Your northern latitude value is less then your southern latitude value. Please correct this.");
            return false;
        }
        if(parseFloat(frm.leftlong.value) > parseFloat(frm.rightlong.value)){
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

function addVoucherToCl(occidIn,clidIn,tidIn){
    $.ajax({
        type: "POST",
        url: "rpc/addvoucher.php",
        data: { occid: occidIn, clid: clidIn, tid: tidIn }
    }).done(function( msg ) {
        if(msg == "1"){
            alert("Success! Voucher added to checklist.");
        }
        else{
            alert(msg);
        }
    });
}

function openIndPU(occId,clid){
    var wWidth = 900;
    if(document.getElementById('maintable').offsetWidth){
        wWidth = document.getElementById('maintable').offsetWidth*1.05;
    }
    else if(document.body.offsetWidth){
        wWidth = document.body.offsetWidth*0.9;
    }
    if(wWidth > 1000) wWidth = 1000;
    newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
    if (newWindow.opener == null) newWindow.opener = self;
    return false;
}

function toggleFieldBox(target){
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

function openMapPU(){
    var url = '../map/googlemap.php?starr='+starrJson+'&jsoncollstarr='+collJson+'&maptype=occquery';
    window.open(url,'gmap','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=1150,height=900,left=20,top=20');
}

function setHarvestParamsForm(){
    var stArr = JSON.parse(starrJson);
    if(!stArr['usethes']){document.harvestparams.thes.checked = false;}
    if(stArr['taxontype']){document.harvestparams.type.value = stArr['taxontype'];}
    if(stArr['taxa']){document.harvestparams.taxa.value = stArr['taxa'];}
    if(stArr['country']){document.harvestparams.country.value = stArr['country'];}
    if(stArr['state']){document.harvestparams.state.value = stArr['state'];}
    if(stArr['county']){document.harvestparams.county.value = stArr['county'];}
    if(stArr['local']){document.harvestparams.local.value = stArr['local'];}
    if(stArr['elevlow']){document.harvestparams.elevlow.value = stArr['elevlow'];}
    if(stArr['elevhigh']){document.harvestparams.elevhigh.value = stArr['elevhigh'];}
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
    if(sessionStorage.collsearchtableview){
        document.getElementById('showtable').checked = true;
        changeTableDisplay();
    }
}

function resetHarvestParamsForm(){
    document.harvestparams.thes.checked = true;
    document.harvestparams.type.value = 1;
    document.harvestparams.taxa.value = '';
    document.harvestparams.country.value = '';
    document.harvestparams.state.value = '';
    document.harvestparams.county.value = '';
    document.harvestparams.local.value = '';
    document.harvestparams.elevlow.value = '';
    document.harvestparams.elevhigh.value = '';
    document.harvestparams.upperlat.value = '';
    document.harvestparams.bottomlat.value = '';
    document.harvestparams.leftlong.value = '';
    document.harvestparams.rightlong.value = '';
    document.harvestparams.upperlat_NS.value = 'N';
    document.harvestparams.bottomlat_NS.value = 'N';
    document.harvestparams.leftlong_EW.value = 'W';
    document.harvestparams.rightlong_EW.value = 'W';
    document.harvestparams.pointlat.value = '';
    document.harvestparams.pointlong.value = '';
    document.harvestparams.radiustemp.value = '';
    document.harvestparams.pointlat_NS.value = 'N';
    document.harvestparams.pointlong_EW.value = 'W';
    document.harvestparams.radiusunits.value = 'km';
    document.harvestparams.radius.value = '';
    document.harvestparams.collector.value = '';
    document.harvestparams.collnum.value = '';
    document.harvestparams.eventdate1.value = '';
    document.harvestparams.eventdate2.value = '';
    document.harvestparams.catnum.value = '';
    document.harvestparams.includeothercatnum.checked = true;
    document.harvestparams.typestatus.checked = false;
    document.harvestparams.hasimages.checked = false;
    sessionStorage.removeItem('jsonstarr');
    document.getElementById('showtable').checked = false;
    changeTableDisplay();
}