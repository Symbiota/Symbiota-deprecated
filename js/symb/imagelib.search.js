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
	return verifyCollForm(document.getElementById('imagesearchform'));
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
		}
		else{
            document.getElementById("dballcb").checked = false;
		}
		if(f.elements[h].name == "cat[]" && f.elements[h].checked){
			formVerified = true;
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
	newWindow = window.open('../collections/individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
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
	newWindow = window.open("../taxa/index.php?taxon="+tid,'taxon'+tid,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
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
	newWindow = window.open("imgdetails.php?imgid="+imageId,'image'+imageId,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}