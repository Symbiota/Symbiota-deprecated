function processGbifOrgKey(f){
	var status = true;
	$("#workingcircle").show();

	var gbifInstOrgKey = f.gbifInstOrgKey.value;
	var gbifOrgKey = f.gbifOrgKey.value;
	var gbifInstKey = f.gbifInstKey.value;
	var gbifDatasetKey = f.gbifDataKey.value;
	var gbifEndpointKey = f.gbifEndKey.value;
	var dwcUri = f.dwcUri.value;
	var portalName = f.portalname.value;
	var collName = f.collname.value;

	if(gbifInstOrgKey && gbifOrgKey){
		if(!gbifInstKey){
			gbifInstKey = createGbifInstallation(gbifInstOrgKey,portalName);
		}
		if(!gbifDatasetKey){
			gbifDatasetKey = createGbifDataset(gbifInstKey,gbifOrgKey,collName);
		}
		if(gbifDatasetKey){
			if(dwcUri){
				gbifEndpointKey = createGbifEndpoint(gbifDatasetKey, dwcUri);
			}
			else{
				alert('Please create/refresh your Darwin Core Archive and try again.');
			}
		}
		else{
			alert('Invalid Organization Key or insufficient permissions. Please recheck your Organization Key and verify that this portal can create datasets for your organization with GBIF.');
		}
		f.aggKeysStr.value = JSON.stringify({ organizationKey: gbifOrgKey, installationKey: gbifInstKey, datasetKey: gbifDatasetKey, endpointKey: gbifEndpointKey });
		status = true;
	}
	else{
		alert('Please enter an Organization Key.');
		status = false;
	}
	document.getElementById("workingcircle").style.display = "none";
	return status;
}

function createGbifInstallation(gbifOrgKey,collName){
	var type = 'POST';
	var url = 'http://api.gbif.org/v1/installation';
	var data = JSON.stringify({
		organizationKey: gbifOrgKey,
		type: "SYMBIOTA_INSTALLATION",
		title: collName
	});

	return callGbifCurl(type,url,data);
}

function createGbifDataset(gbifInstKey,gbifOrgKey,collName){
	var type = 'POST';
	var url = 'http://api.gbif.org/v1/dataset';
	var data = JSON.stringify({
		installationKey: gbifInstKey,
		publishingOrganizationKey: gbifOrgKey,
		title: collName,
		type: "OCCURRENCE"
	});

	return callGbifCurl(type,url,data);
}

function createGbifEndpoint(gbifDatasetKey,dwcUri){
	var type = 'POST';
	var url = 'http://api.gbif.org/v1/dataset/'+gbifDatasetKey+'/endpoint';
	var data = JSON.stringify({
		type: "DWC_ARCHIVE",
		url: dwcUri
	});

	return callGbifCurl(type,url,data);
}

function callGbifCurl(type,url,data){
	var key;
	$.ajax({
		type: "POST",
		url: "rpc/getgbifcurl.php",
		data: {type: type, url: url, data: data},
		async: false,
		success: function(response) {
			key = response;
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			alert(errorThrown);
		}
	});
	return key;
}