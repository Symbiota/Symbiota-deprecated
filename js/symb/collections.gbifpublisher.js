function processGbifOrgKey(){
	var gbifInstOrgKey = document.getElementById("gbifInstOrgKey").value;
	var gbifOrgKey = document.getElementById("gbifOrgKey").value;
	var gbifInstKey = document.getElementById("gbifInstKey").value;
	var gbifDatasetKey = document.getElementById("gbifDataKey").value;
	var gbifEndpointKey = document.getElementById("gbifEndKey").value;
	var dwcUri = document.getElementById("dwcUri").value;
	var portalName = document.getElementById("portalname").value;
	var collName = document.getElementById("collname").value;
	if(!gbifInstKey){
		gbifInstKey = findInstKey();
	}

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
				if(gbifEndpointKey){
					document.getElementById("aggKeysStr").value = JSON.stringify({
						organizationKey: gbifOrgKey,
						installationKey: gbifInstKey,
						datasetKey: gbifDatasetKey,
						endpointKey: gbifEndpointKey
					});
				}
			}
			else{
				alert('Please create/refresh your Darwin Core Archive and try again.');
				return false;
			}
		}
		else{
			alert('Invalid Organization Key or insufficient permissions. Please recheck your Organization Key and verify that this portal can create datasets for your organization with GBIF.');
			return false;
		}
		return true;
	}
	else{
		alert('Please enter an Organization Key.');
		return false;
	}
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
		//url: "http://symbiota4.acis.ufl.edu/scan/portal/content/dwca/NAUF-CPMAB_DwC-A.zip"
		url: dwcUri
	});

	return callGbifCurl(type,url,data);
}

function startGbifCrawl(gbifDatasetKey){
	var type = 'POST';
	var url = 'http://api.gbif.org/v1/dataset/'+gbifDatasetKey+'/crawl';
	var data = '';

	callGbifCurl(type,url,data);
	alert('Your data is being updated in GBIF. Please allow 5-10 minutes for completion.')
}

function findInstKey(){
	var key;
	$.ajax({
		type: "POST",
		url: "rpc/checkgbifinstall.php",
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