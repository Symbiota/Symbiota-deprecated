function processGbifOrgKey(f){
	var status = true;
	$("#workingcircle").show();

	var gbifInstOrgKey = f.gbifInstOrgKey.value;
	var portalName = f.portalname.value;
	var collName = f.collname.value;
	var datasetKey = f.datasetKey.value;
	var organizationKey = f.organizationKey.value;
	var installationKey = f.installationKey.value;
	var dwcUri = f.dwcUri.value;

	if(gbifInstOrgKey && organizationKey){
		var submitForm = false;
		if(!installationKey){
			installationKey = createGbifInstallation(gbifInstOrgKey,portalName);
			f.installationKey.value = installationKey;
			submitForm = true;
		}
		if(!datasetKey){
			datasetExists(f);
			if(f.datasetKey.value){
				alert("Dataset already appears to exist. Updating database.");
				submitForm = true;
			}
			else{
				datasetKey = createGbifDataset(installationKey, organizationKey, collName);
				f.datasetKey.value = datasetKey;
				if(datasetKey){
					if(dwcUri){
						f.endpointKey.value = createGbifEndpoint(datasetKey, dwcUri);
					}
					else{
						alert('Please create/refresh your Darwin Core Archive and try again.');
					}
					submitForm = true;
				}
				else{
					alert('Invalid Organization Key or insufficient permissions. Please recheck your Organization Key and verify that this portal can create datasets for your organization with GBIF.');
				}
			}
		}
		if(submitForm) f.submit();
		status = true;
	}
	else{
		alert('Please enter an Organization Key.');
		status = false;
	}
	$("#workingcircle").hide();
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

function datasetExists(f){
	if(f.dwcUri.value != ""){
		var urlStr = f.dwcUri.value;
		if(urlStr.indexOf("/content/") > 0){
			urlStr = urlStr.substring(0,urlStr.indexOf("/content/"));
			urlStr = "http://api.gbif.org/v1/dataset?identifier=" + urlStr + "/collections/misc/collprofiles.php?collid=" + f.collid.value;
			$.ajax({
				method: "GET",
				async: false,
				dateType: "json",
				url: urlStr
			})
			.done(function( retJson ) {
				if(retJson.count > 0){
					f.datasetKey.value = retJson.results[0].key;
					f.endpointKey.value = retJson.results[0].endpoints[0].key;
					return true;
				}
				else{
					return false;
				}
			})
			.fail(function() {
				alert("General error querying datasets. Is your connection to the network stable?");
				return false;
			});
		}
	}
}