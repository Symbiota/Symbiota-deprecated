$('html').hide();
$(document).ready(function() {
	$('html').show();
});


jQuery(document).ready(function($) {
	$('#taxa').manifest({
		marcoPolo: {
			url: 'rpc/imagesearchautofill.php',
			data: {
				t: 'taxa'
			},
			formatItem: function (data) {
				return data.name;
			},
			onSelect: function (){
				$("#imagedisplay").val("thumbnail");
			}
		}
	});
	
	$('#common').manifest({
		marcoPolo: {
			url: 'rpc/imagesearchautofill.php',
			data: {
				t: 'common'
			},
			formatItem: function (data) {
				return data.name;
			}
		}
	});
	
	$('#country').manifest({
		marcoPolo: {
			url: 'rpc/imagesearchautofill.php',
			data: {
				t: 'country'
			},
			formatItem: function (data) {
				return data.name;
			}
		}
	});
	
	$('#state').manifest({
		marcoPolo: {
			url: 'rpc/imagesearchautofill.php',
			data: {
				t: 'state'
			},
			formatItem: function (data) {
				return data.name;
			}
		}
	});
	
	$('#keywords').manifest({
		marcoPolo: {
			url: 'rpc/imagesearchautofill.php',
			data: {
				t: 'keywords'
			},
			formatItem: function (data) {
				return data.name;
			}
		}
	});
});

function submitImageForm(){
	var taxavals = $('#taxa').manifest('values');
	var commonvals = $('#common').manifest('values');
	var countryvals = $('#country').manifest('values');
	var statevals = $('#state').manifest('values');
	var keywordsvals = $('#keywords').manifest('values');
	var criteria = 0;
	if(taxavals.length > 0){
		var taxastr = taxavals.join();
		document.getElementById('taxastr').value = taxastr;
		criteria = 1;
	}
	else if(commonvals.length > 0){
		var taxastr = commonvals.join();
		document.getElementById('taxastr').value = taxastr;
		criteria = 1;
	}
	else{
		document.getElementById('taxastr').value = '';
	}
	if(countryvals.length > 0){
		var countrystr = countryvals.join();
		document.getElementById('countrystr').value = countrystr;
		criteria = 1;
	}
	else{
		document.getElementById('countrystr').value = '';
	}
	if(statevals.length > 0){
		var statestr = statevals.join();
		document.getElementById('statestr').value = statestr;
		criteria = 1;
	}
	else{
		document.getElementById('statestr').value = '';
	}
	if(keywordsvals.length > 0){
		var keywordstr = keywordsvals.join();
		document.getElementById('keywordstr').value = keywordstr;
		criteria = 1;
	}
	else{
		document.getElementById('keywordstr').value = '';
	}
	if(phArr.length > 0){
		var phids = [];
		for(i = 0; i < phArr.length; i++){
			phids.push(phArr[i].id);
		}
		var phidstr = phids.join();
		document.getElementById('phuidstr').value = phidstr;
		document.getElementById('phjson').value = JSON.stringify(phArr);
		criteria = 1;
	}
	else{
		document.getElementById('phuidstr').value = '';
		document.getElementById('phjson').value = '';
	}
	return true;
	/*if(criteria){
		return true;
	}
	else{
		alert("Please specify either a scientific name, common name, photographer, or keyword for which you would like to search images for.");
		return false;
	}*/
}

function imageDisplayChanged(f){
	if(f.imagedisplay.value == "taxalist" && $('#taxa').manifest('values') != ""){
		f.imagedisplay.value = "thumbnail";
		alert("Only the thumbnail display is allowed when searching for a scientific name");
	}
}

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

function checkTaxonType(){
	var newtaxontype = document.getElementById('taxontype').value;
	var oldtaxontype = document.getElementById('taxtp').value;
	if(newtaxontype==1||newtaxontype==2){
		if(oldtaxontype==3){
			var vals = $('#common').manifest('values');
			for (i = 0; i < vals.length; i++) {
				$('#common').manifest('remove', i);
			}
			document.getElementById('thesdiv').style.display = "block";
			document.getElementById('commonbox').style.display = "none";
			document.getElementById('taxabox').style.display = "block";
			document.getElementById('taxtp').value = newtaxontype;
		}
	
	}
	if(newtaxontype==3){
		if(oldtaxontype==1||oldtaxontype==2){
			var vals = $('#taxa').manifest('values');
			for (i = 0; i < vals.length; i++) {
				$('#taxa').manifest('remove', i);
			}
			document.getElementById('commonbox').style.display = "block";
			document.getElementById('taxabox').style.display = "none";
			document.getElementById('thesdiv').style.display = "none";
			document.getElementById('thes').checked = false;
			document.getElementById('taxtp').value = newtaxontype;
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

function openIndPU(occId,clid){
	var wWidth = 900;
	if(document.getElementById('maintable').offsetWidth){
		wWidth = document.getElementById('maintable').offsetWidth*1.05;
	}
	else if(document.body.offsetWidth){
		wWidth = document.body.offsetWidth*0.9;
	}
	if(wWidth > 1000) wWidth = 1000;
	newWindow = window.open('../collections/individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function openTaxonPopup(tid){
	var wWidth = 900;
	if(document.getElementById('maintable').offsetWidth){
		wWidth = document.getElementById('maintable').offsetWidth*1.05;
	}
	else if(document.body.offsetWidth){
		wWidth = document.body.offsetWidth*0.9;
	}
	if(wWidth > 1000) wWidth = 1000;
	newWindow = window.open("../taxa/index.php?taxon="+tid,'taxon'+tid,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function openImagePopup(imageId){
	var wWidth = 900;
	if(document.getElementById('maintable').offsetWidth){
		wWidth = document.getElementById('maintable').offsetWidth*1.05;
	}
	else if(document.body.offsetWidth){
		wWidth = document.body.offsetWidth*0.9;
	}
	if(wWidth > 1000) wWidth = 1000;
	newWindow = window.open("imgdetails.php?imgid="+imageId,'image'+imageId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function changeImagePage(taxonIn,viewIn,starrIn,pageIn){
	document.getElementById("imagebox").innerHTML = "<p>Loading...</p>";
	
	$.ajax( {
		url: "rpc/changeimagepage.php",
		method: "POST",
		data: { 
			starr: starrIn, 
			page: pageIn, 
			view: viewIn,
			taxon: taxonIn
		},
		success: function( data ) {
			var newImageList = JSON.parse(data);
			document.getElementById("imagebox").innerHTML = newImageList;
			if(viewIn == 'thumb'){
				document.getElementById("imagetab").innerHTML = 'Images';
			}
			else{
				document.getElementById("imagetab").innerHTML = 'Taxa List';
			}
        }
	});

}

function changeFamily(taxon){
	selectedFamily = taxon;
}