jQuery(document).ready(function($) {
	$('#photographer').autocomplete({

	});
});

function openIndPU(occId,clid){
	openPopup("../collections/individual/index.php?occid="+occId, "indspec" + occId);
	return false;
}

function openTaxonPopup(tid){
	openPopup("../taxa/index.php?taxon="+tid, 'taxon'+tid);
	return false;
}

function openImagePopup(imageId){
	openPopup("imgdetails.php?imgid="+imageId, 'image'+imageId);
	return false;
}

function openPopup(url,nameStr){
	var wWidth = 900;
	if(document.getElementById('maintable').offsetWidth){
		wWidth = document.getElementById('maintable').offsetWidth*1.05;
	}
	else if(document.body.offsetWidth){
		wWidth = document.body.offsetWidth*0.9;
	}
	if(wWidth > 1000) wWidth = 1000;
	newWindow = window.open(url,nameStr,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}