<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDownload.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverOccurrence.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$displayMode = array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($displayMode)) $displayMode = 0;

$customField1 = array_key_exists('customfield1',$_REQUEST)?$_REQUEST['customfield1']:'';
$customType1 = array_key_exists('customtype1',$_REQUEST)?$_REQUEST['customtype1']:'';
$customValue1 = array_key_exists('customvalue1',$_REQUEST)?$_REQUEST['customvalue1']:'';
$customField2 = array_key_exists('customfield2',$_REQUEST)?$_REQUEST['customfield2']:'';
$customType2 = array_key_exists('customtype2',$_REQUEST)?$_REQUEST['customtype2']:'';
$customValue2 = array_key_exists('customvalue2',$_REQUEST)?$_REQUEST['customvalue2']:'';
$customField3 = array_key_exists('customfield3',$_REQUEST)?$_REQUEST['customfield3']:'';
$customType3 = array_key_exists('customtype3',$_REQUEST)?$_REQUEST['customtype3']:'';
$customValue3 = array_key_exists('customvalue3',$_REQUEST)?$_REQUEST['customvalue3']:'';

$dlManager = new OccurrenceDownload();
$collMeta = $dlManager->getCollectionMetadata($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
 	$isEditor = true;
}
//CA: Bookmark
$advFieldArr = array('family'=>'Family','sciname'=>'Scientific Name','identifiedBy'=>'Identified By','typeStatus'=>'Type Status',
	'catalogNumber'=>'Catalog Number','otherCatalogNumbers'=>'Other Catalog Numbers','occurrenceId'=>'Occurrence ID (GUID)',
	'recordedBy'=>'Collector/Observer','recordNumber'=>'Collector Number','associatedCollectors'=>'Associated Collectors',
	'eventDate'=>'Collection Date','verbatimEventDate'=>'Verbatim Date','habitat'=>'Habitat','substrate'=>'Substrate','occurrenceRemarks'=>'Occurrence Remarks',
	'associatedTaxa'=>'Associated Taxa','verbatimAttributes'=>'Description','reproductiveCondition'=>'Reproductive Condition',
	'establishmentMeans'=>'Establishment Means','lifeStage'=>'Life Stage','sex'=>'Sex',
	'individualCount'=>'Individual Count','samplingProtocol'=>'Sampling Protocol','country'=>'Country',
	'stateProvince'=>'State/Province','county'=>'County','municipality'=>'Municipality','locality'=>'Locality',
	'decimalLatitude'=>'Decimal Latitude','decimalLongitude'=>'Decimal Longitude','geodeticDatum'=>'Geodetic Datum',
	'coordinateUncertaintyInMeters'=>'Uncertainty (m)','verbatimCoordinates'=>'Verbatim Coordinates',
	'georeferencedBy'=>'Georeferenced By','georeferenceProtocol'=>'Georeference Protocol','georeferenceSources'=>'Georeference Sources',
	'georeferenceVerificationStatus'=>'Georeference Verification Status','georeferenceRemarks'=>'Georeference Remarks',
	'minimumElevationInMeters'=>'Elevation Minimum (m)','maximumElevationInMeters'=>'Elevation Maximum (m)',
	'verbatimElevation'=>'Verbatim Elevation','disposition'=>'Disposition','idCollaboratorIndigenous'=>'Collaborator ID','sexCollaboratorIndigenous'=>'Sex','dobCollaboratorIndigenous'=>'Date of Birth','verbatimIndigenous'=>'Verbatim Name','validIndigenous'=>'Valid Name','linkLanguageCollaboratorIndigenous'=>'Link Name','familyLanguageCollaboratorIndigenous'=>'Family','groupLanguageCollaboratorIndigenous'=>'Group','subgroupLanguageCollaboratorIndigenous'=>'Subgroup','villageCollaboratorIndigenous'=>'Village','municipalityCollaboratorIndigenous'=>'Municipality','stateCollaboratorIndigenous'=>'State','countryCollaboratorIndigenous'=>'Country','isoLanguageCollaboratorIndigenous'=>'ISO','vernacularLexiconIndigenous'=>'Vernacular','glossLexiconIndigenous'=>'Gloss','parseLexiconIndigenous'=>'Parse','parentTaxaLexiconIndigenous'=>'Parent Taxonomy','siblingTaxaLexiconIndigenous'=>'Sibling Taxonomy','childTaxaLexiconIndigenous'=>'Child Taxonomy','otherTaxaUseIndigenous'=>'Other Taxonomy','typologyLexiconIndigenous'=>'Typology Name','semanticsLexiconIndigenous'=>'Semantics Name','notesLexiconIndigenous'=>'Discussion of Name','categoryUseIndigenous'=>'Category Use','specificUseIndigenous'=>'Specific Use','partUseIndigenous'=>'Part Used','notesUseIndigenous'=>'Discussion Use');


?>
<html>
	<head>
		<title>Occurrence Export Manager</title>
		<link href="<?php echo $clientRoot; ?>/css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script>
			var cogeUrl = "https://www.museum.tulane.edu/coge/symbiota/";
			var t;
			var t2;
			var datasetList = {};
			
			$(function() {
				var dialogArr = new Array("schemanative","schemadwc","newrecs");
				var dialogStr = "";
				for(i=0;i<dialogArr.length;i++){
					dialogStr = dialogArr[i]+"info";
					$( "#"+dialogStr+"dialog" ).dialog({
						autoOpen: false,
						modal: true,
						position: { my: "left top", at: "right bottom", of: "#"+dialogStr }
					});
	
					$( "#"+dialogStr ).click(function() {
						$( "#"+this.id+"dialog" ).dialog( "open" );
					});
				}
	
			});

			function validateDownloadForm(f){
				if(f.newrecs.checked == true && (f.processingstatus.value == "unprocessed" || f.processingstatus.value == "")){
					alert("New records cannot have an unprocessed or undefined processing status. Please select a valid processing status.");
					return false;
				}
				return true;
			}

			function extensionSelected(obj){
				if(obj.checked == true){
					obj.form.zip.checked = true;
				}
			}

			function zipChanged(cbObj){
				if(cbObj.checked == false){
					cbObj.form.identifications.checked = false;
					cbObj.form.images.checked = false;
				}
			}

			//CoGe GeoLocate functions
			function cogeCheckAuthentication(){
				//$("#cogeStatus").html("");
				$("#coge-status").css('color', 'orange');
				$("#coge-status").html("Checking status...");

				$.ajax({
					type: "GET",
					url: cogeUrl,
					crossDomain: true,
					xhrFields: { withCredentials: true },
					dataType: 'json'
				}).done(function( response ) {
					var result = response.result;
					if(result == "authentication required"){
						$("#coge-status").html("Unauthorized");
						$("#coge-status").css("color", "red");
						$("#builddwcabutton").prop("disabled",true);
						$("#coge-commlist").html('<span style="color:orange;">Login to GeoLocate and click check status button to list available communities</span>');
					}
					else{
						clearInterval(t);
						$("#coge-status").css('color', 'green');
						$("#coge-status").html("Connected");
						$("#builddwcabutton").prop("disabled",false);
						cogeGetUserCommunityList();
					}
				}).fail(function(jqXHR, textStatus, errorThrown ){
					$("#coge-status").html("Unauthorized");
					$("#coge-status").css("color", "red");
					alert( "ERROR: it may be that GeoLocate has not been configured to automatically accept files from this Symbiota portal. Please contact your portal adminstrator to setup automated GeoLocate submissions. " );
					clearInterval(t);
				});
			}

			function startAuthMonitoring(){
				//every 3 seconds, check authenication
				t = setInterval(cogeCheckAuthentication,3000);
			}

			function cogePublishDwca(f){
				if($("#countdiv").html() == 0){
					alert("No records exist matching search criteria");
					return false;
				}
				if($('input[name=cogecomm]:checked').length == 0) {
					alert("You must select a target community");
					return false;
				}
				if(f.cogename.value == ""){
					alert("You must enter a data source identifier");
					return false;
				}
				$("#builddwcabutton").prop("disabled",true);
				$("#coge-download").show();
				$.ajax({
					type: "POST",
					url: "rpc/coge_build_dwca.php",
					dataType: "json",
					data: { 
						collid: f.collid.value, 
						ps: f.processingstatus.value, 
						cf1: f.customfield1.value, 
						ct1: f.customtype1.value,
						cv1: f.customvalue1.value,
						cf2: f.customfield2.value, 
						ct2: f.customtype2.value,
						cv2: f.customvalue2.value,
						cogecomm: f.cogecomm.value,
						cogename: f.cogename.value,
						cogedescr: f.cogedescr.value
					}
				}).done(function( response ) {
					var result = response.result;
					$("#coge-download").hide();
					if(result == "ERROR"){
						alert(result);
					}
					else{
						var dwcaPath =  result.path;
						if(dwcaPath){
							$("#coge-dwcalink").html("<u>Data package (DwC-Archive)</u>: <a href='"+dwcaPath+"'>"+dwcaPath+"</a>");
							cogeSubmitData(dwcaPath);
						}
						else{

						}
					}
				});
			}

			function cogeUpdateCount(formObj){
				var f = formObj.form;
				var objName = formObj.name;
				if(objName == "customtype1" || objName == "customvalue1"){
					if(f.customfield1.value == '') return false;
					if(f.customtype1.value == "EQUALS" || f.customtype1.value == "STARTS" || f.customtype1.value == "LIKE"){
						if(objName == "customtype1" && f.customvalue1.value == '') return false;
					}
				}
				if(objName == "customtype2" || objName == "customvalue2"){
					if(f.customfield2.value == '') return false;
					if(f.customtype2.value == "EQUALS" || f.customtype2.value == "STARTS" || f.customtype2.value == "LIKE"){
						if(objName == "customtype2" && f.customvalue2.value == '') return false;
					}
				}
				$("#recalspan").show();
				$.ajax({
					type: "POST",
					url: "rpc/coge_getCount.php",
					dataType: "json",
					data: { 
						collid: f.collid.value, 
						ps: f.processingstatus.value, 
						cf1: f.customfield1.value, 
						ct1: f.customtype1.value,
						cv1: f.customvalue1.value,
						cf2: f.customfield2.value, 
						ct2: f.customtype2.value,
						cv2: f.customvalue2.value
					}
				}).done(function( response ) {
					if(response == 0) f.builddwcabutton.disalbed = true;
					$("#countdiv").html(response);
					$("#recalspan").hide();
				});
			}

			function cogeSubmitData(dwcaPath){
				$("#coge-push2coge").show();
				$.ajax({
					type: "GET",
					url: cogeUrl,
					crossDomain: true,
					xhrFields: { withCredentials: true },
					dataType: 'json',
					data: { t: "import", q: dwcaPath }
				}).done(function( response ) {
					//{"result":{"datasourceId":"7ab8ffb8-032a-4f7a-8968-a012ce287c2d"}}
					var result = response.result;
					var dataSourceGuid = result.datasourceId;
					if(dataSourceGuid){
						$("#coge-push2coge").hide();
						$("#coge-guid").html("<u>Dataset identifier</u>: " + dataSourceGuid);
						window.setTimeout(cogeCheckStatus(dataSourceGuid),2000);
					}
				});
			}		

			function cogeCheckStatus(id){
				$.ajax({
					type: "GET",
					url: cogeUrl,
					crossDomain: true,
					xhrFields: { withCredentials: true },
					dataType: 'json',
					data: { t: "importstatus", q: id }
				}).done(function( response ) {
					//{"result":{"importProgess":{"state":"portal interation required"}}}
					var result = response.result;
					if(result == "authentication required"){
						$("#coge-status").html("Unauthorized");
						$("#coge-status").css("color", "red");
						alert("Authentication Required! Login may have timed out, please login back into GeoLocate website");
						t2 = setInterval(cogeCheckStatus(id),3000);
					}
					else {
						clearInterval(t2);
						var iStatus = result.importStatus.state;
						if(iStatus == "portal_interaction_required"){
							$("#coge-importcomplete").show();
							//Default import status will be displayed in #coge-importstatus
							$("#coge-importstatus").show();
							cogeGetUserCommunityList();
						}
						else if(iStatus == "ready"){
							$("#coge-importstatus").html("Dataset ready for processing");
							$("#coge-importstatus").show();
						}
						else if(iStatus == "unspecified"){
							$("#coge-importstatus").html("Unbable to locate dataset");
							$("#coge-importstatus").show();
						}
						else if(iStatus == "retrieval" || iStatus == "extraction" || iStatus == "discovery" || iStatus == "datasource_creation"){
							//Import is still processing
							window.setTimeout(cogeCheckStatus(id),2000);
						}
						else{
							alert(iStatus);
							$("#coge-importstatus").html("Unknown Error: Visit GeoLocate for details");
							$("#coge-importstatus").show();
						}
					}
				});
			}

			function cogeGetUserCommunityList(){
				$.ajax({
					type: "GET",
					url: cogeUrl,
					crossDomain: true,
					xhrFields: { withCredentials: true },
					dataType: 'json',
					data: { t: "comlist" }
				}).done(function( response ) {
					/*
					{"result":[{"name":"Phoenix","description":"General Areas around Phoenix that need coordinates","role":"Owner",
					"dataSources":[{"name":"Fabaceae test","description":"","uploadedBy":"egbott","uploadType":"csv"},
					{"guid":"95b7fdb7-8667-469f-88c5-ad1bf3a6ea29","name":"Arizona Fabaceae","description":"","uploadedBy":"egbott","uploadType":"Symbiota (DwCA)"},
					{"guid":"19e68aae-b870-4f81-aa08-ab17a827985e","name":"Fabaceae","description":"test upload of Fabaceae","uploadedBy":"egbott","uploadType":"Symbiota (DwCA)"}]}]}
					*/
					var result = response.result;
					if(result == "authentication required"){
						alert("Authentication Required! Login may have timed out, please login back into GeoLocate website");
					}
					else{
						$("#coge-communities").show();
						var htmlOut = "";
						for(var i in result){
							var role = result[i].role;
							if(role == "Owner" || role == "Admin" || role == "Reviewer"){
								htmlOut = htmlOut + '<div style="margin:5px">';
								var name = result[i].name;
								htmlOut = htmlOut + '<input name="cogecomm" type="radio" value="'+name+'" onclick="verifyDataSourceIdentifier(this.form)" />';
								htmlOut = htmlOut + "<u>"+name+"</u>";
								htmlOut = htmlOut + " (" + role + ")";
								var descr = result[i].description;
								if(descr) htmlOut = htmlOut + ": " + descr;
								var dataSources = result[i].dataSources;
								if(dataSources){
									htmlOut = htmlOut + '<fieldset style="margin:0px 30px;padding:10px"><legend><b>Datasets</b></legend>';
									datasetList[name] = {};
									for(var j in dataSources){
										datasetList[name][j] = dataSources[j].name;
										htmlOut = htmlOut + "<div><b>" + dataSources[j].name + "</b> (";
										
										var uploadType = dataSources[j].uploadType;
										if(uploadType == "csv"){
											htmlOut = htmlOut + "manual CSV upload";
										}
										else{
											if(uploadType == "Symbiota (DwCA)"){
												var guid = dataSources[j].guid;
												htmlOut = htmlOut + 'Symbiota upload [<a href="#" onclick="cogeCheckGeorefStatus(\''+guid+'\');return false;">check status</a>]';
											}
										}
										var uploadedBy = dataSources[j].uploadedBy;
										if(uploadedBy) htmlOut = htmlOut + "; " + uploadedBy;

										htmlOut = htmlOut + ")";
										var dsDescr = dataSources[j].description;
										if(dsDescr) htmlOut = htmlOut + ": " + dsDescr;
										htmlOut = htmlOut + "</div>";
										if(uploadType == "Symbiota (DwCA)"){
											htmlOut = htmlOut + '<div id="coge-'+guid+'" style="margin-left:10px;"></div>';
										}
									} 
									htmlOut = htmlOut + '</fieldset>';
								}
								htmlOut = htmlOut + '</div>';
								$("#coge-commlist").html(htmlOut);
							}
						}
					}
				});
			}

			function cogeCheckGeorefStatus(id){
				$.ajax({
					type: "GET",
					url: cogeUrl,
					crossDomain: true,
					xhrFields: { withCredentials: true },
					dataType: 'json',
					data: { t: "dsstatus", q: id }
				}).done(function( response ) {
					//{"result":{"datasource":"0a289c73-5317-45f1-9486-656597f98626","stats":{"specimens":{"total":48004,"corrected":774,"skipped":0},"localities":{"total":18876,"corrected":226,"skipped":0}}}}
					var result = response.result;
					if(result == "authentication required"){
						alert("Authentication Required! Login may have timed out, please login back into GeoLocate website");
					}
					else{
						var specStats = result.stats.specimens;
						var localStats = result.stats.localities;
						var htmlOut = '<div style="border:1px solid black">';
						htmlOut = htmlOut + "<div>Specimens: total: " + specStats.total + ", corrected: " + specStats.corrected + ", skipped: " + specStats.skipped;
						if(specStats.total == 0 && specStats.corrected == 0 && specStats.skipped == 0){
							htmlOut = htmlOut + "<span style=\"margin-left:30px;color:orange;\">GeoLocate interaction may be required to activate data</span>";
						}
						htmlOut = htmlOut + "</div>";
						htmlOut = htmlOut + "<div>Localities: total: " + specStats.total + ", corrected: " + specStats.corrected + ", skipped: " + specStats.skipped;
						htmlOut = htmlOut + "</div></div>";
						$("#coge-"+id).html(htmlOut);
					}
				});
			}

			function verifyDataSourceIdentifier(f){
				var newProjName = $("input[name=cogename]").val();
				if(newProjName != "" && $('input[name=cogecomm]:checked').size() > 0){
					if($('input[name=cogecomm]:checked').val() in datasetList){
						var projList = datasetList[$('input[name=cogecomm]:checked').val()];
						for(var h in projList){
							if(projList[h] == newProjName){
								alert("Dataset name already exists for selected community");
								return false;
							}
						}
					}
				}
			}
		</script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext" style="background-color:white;">
			<div style="float:right;width:165px;margin-right:30px">
				<fieldset>
					<legend><b>Export Type</b></legend>
					<form name="submenuForm" method="post" action="index.php">
						<select name="displaymode" onchange="this.form.submit()">
							<option value="0">Custom Export</option>
							<option value="1" <?php echo ($displayMode==1?'selected':''); ?>>Georeference Export</option>
							<?php 
							if($ACTIVATE_GEOLOCATE_TOOLKIT) echo '<option value="2" '.($displayMode==2?'selected':'').'>GeoLocate Toolkit</option>';
							?>
						</select>
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="tabindex" type="hidden" value="5" />
					</form>
				</fieldset>
			</div>
			<div style="padding:15px 0px;">
				This download module is designed to aid collection managers in extracting specimen data
				for import into local management or research systems.
				<?php 
				if($collMeta['manatype'] == 'Snapshot'){
					?> 
					<a href="#" onclick="toggle('moreinfodiv');this.style.display = 'none';return false;" style="font-size:90%">more info...</a> 
					<span id="moreinfodiv" style="display:none;">
						The export module is particularly useful for extracting data that has been added 
						using the digitization tools built into the web portal (crowdsourcing, OCR/NLP, basic data entry, etc). 
						Records imported from a local database are linked to the primary record
						through a specimen unique identifier (barcode, primary key, UUID, etc). 
						This identifier is stored in the web portal database and gives collection managers the ability to update local records 
						with information added within the web portal.
						New records digitized directly into the web portal (e.g. image to record data entry workflow) will have a null unique identifier, 
						which identifies the record as new and not yet synchronized to the central database.
						When new records are extracted from the portal, imported into the central database, 
						and then the portal's data snapshot is refreshed, the catalog number will be used to automatically synchronized
						the portal specimen records with those in the central database. Note that synchronization will only work if the primary identifier is 
						enforced as unique (e.g. no duplicates) within the local, central database.
					</span>
					<?php
				}
				?>
			</div>
			<?php 
			if($collid && $isEditor){
				echo '<div style="clear:both;">';
				if($displayMode == 1){ 
					if($collMeta['manatype'] == 'Snapshot'){
						?>
						<form name="exportgeorefform" action="../download/downloadhandler.php" method="post" onsubmit="return validateExportGeorefForm(this);">
							<fieldset>
								<legend><b>Export Batch Georeferenced Data</b></legend>
								<div style="margin:15px;">
									This module extracts coordinate data only for the records that have been georeferenced using the 
									<a href="../georef/batchgeoreftool.php?collid=<?php echo $collid; ?>" target="_blank">batch georeferencing tools</a> 
									or the GeoLocate Community tools. 
									These downloads are particularly tailored for importing the new coordinates into their local database. 
									If no records have been georeferenced within the portal, the output file will be empty.
								</div>
								<table>
									<tr>
										<td>
											<div style="margin:10px;">
												<b>Processing Status:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<select name="processingstatus">
													<option value="">All Records</option>
													<?php 
													$statusArr = $dlManager->getProcessingStatusList($collid);
													foreach($statusArr as $v){
														echo '<option value="'.$v.'">'.ucwords($v).'</option>';
													}
													?>
												</select>
											</div> 
										</td>
									</tr>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b>Compression:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<input type="checkbox" name="zip" value="1" checked /> Archive Data Package (ZIP file)<br/>
											</div>
										</td>
									</tr>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b>File Format:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<input type="radio" name="format" value="csv" CHECKED /> Comma Delimited (CSV)<br/>
												<input type="radio" name="format" value="tab" /> Tab Delimited<br/>
											</div>
										</td>
									</tr>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b>Character Set:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<?php 
												$cSet = strtolower($charset);
												?>
												<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
												<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div style="margin:10px;">
												<input name="customfield1" type="hidden" value="georeferenceSources" />
												<input name="customtype1" type="hidden" value="STARTS" />
												<input name="customvalue1" type="hidden" value="georef batch tool" />
												<input name="targetcollid" type="hidden" value="<?php echo $collid; ?>" />
												<input name="schema" type="hidden" value="georef" />
												<input name="extended" type="hidden" value="1" />
												<input name="submitaction" type="submit" value="Download Records" />
											</div>
										</td>
									</tr>
								</table>							
							</fieldset>
						</form>
						<?php
					}
					//Export for georeferencing (e.g. GeoLocate)
					?>
					<form name="expgeoform" action="../download/downloadhandler.php" method="post" onsubmit="return validateExpGeoForm(this);">
						<fieldset>
							<legend><b>Export Specimens Lacking Georeferencing Data</b></legend>
							<div style="margin:15px;">
								This module extracts specimens that lack decimal coordinates or have coordinates that needs to be verified.
								This download will result in a Darwin Core Archive containing a UTF-8 encoded CSV file containing 
								only georeferencing relavent data columns for the occurrences. By default, occurrences 
								will be limited to records containing locality information but no decimal coordinates. 
								This output is particularly useful for creating data extracts that will georeferenced using external tools. 
							</div>
							<table>
								<tr>
									<td>
										<div style="margin:10px;">
											<b>Processing Status:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="processingstatus">
												<option value="">All Records</option>
												<?php 
												$statusArr = $dlManager->getProcessingStatusList($collid);
												foreach($statusArr as $v){
													echo '<option value="'.$v.'">'.ucwords($v).'</option>';
												}
												?>
											</select>
										</div> 
									</td>
								</tr>
								<tr>
									<td>
										<div style="margin:10px;">
											<b>Coordinates:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input name="customtype2" type="radio" value="NULL" checked /> are empty (is null)<br/>
											<input name="customtype2" type="radio" value="NOTNULL" /> have values (e.g. need verification)
											<input name="customfield2" type="hidden" value="decimallatitude" />
										</div> 
									</td>
								</tr>
								<tr>
									<td>
										<div style="margin:10px;">
											<b>Additional<br/>Filters:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="customfield1" style="width:200px">
												<option value="">Select Field Name</option>
												<option value="">---------------------------------</option>
												<?php 
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField1?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype1">
												<option value="EQUALS">EQUALS</option>
												<option <?php echo ($customType1=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
												<option <?php echo ($customType1=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
												<option <?php echo ($customType1=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
												<option <?php echo ($customType1=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
											</select>
											<input name="customvalue1" type="text" value="<?php echo $customValue1; ?>" style="width:200px;" />
										</div> 
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div style="margin:10px;">
											<input name="customfield3" type="hidden" value="locality" />
											<input name="customtype3" type="hidden" value="NOTNULL" />
											<input name="format" type="hidden" value="csv" />
											<input name="cset" type="hidden" value="utf-8" />
											<input name="zip" type="hidden" value="1" />
											<input name="targetcollid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="schema" type="hidden" value="dwc" />
											<input name="extended" type="hidden" value="1" />
											<input name="submitaction" type="submit" value="Download Records" />
										</div>
									</td>
								</tr>
							</table>							
						</fieldset>
					</form>
					<?php 
				}
				elseif($displayMode == 2){
					if($ACTIVATE_GEOLOCATE_TOOLKIT){
						//GeoLocate tools
						?>
						<form name="expgeolocateform" action="../download/downloadhandler.php" method="post" onsubmit="">
							<fieldset>
								<legend><b>GeoLocate Community Toolkit</b></legend>
								<div style="margin:15px;">
									This module extracts specimen records that have text locality details but lack decimal coordinates.  
									These specimens are packaged and delivered directly into the GeoLocate Community Tools.
								</div>
								<table>
									<tr>
										<td>
											<div style="margin:10px;">
												<b>Processing Status:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<select name="processingstatus" onchange="cogeUpdateCount(this)">
													<option value="">All Records</option>
													<?php 
													$statusArr = $dlManager->getProcessingStatusList($collid);
													foreach($statusArr as $v){
														echo '<option value="'.$v.'">'.ucwords($v).'</option>';
													}
													?>
												</select>
											</div> 
										</td>
									</tr>
	 								<tr>
										<td>
											<div style="margin:10px;">
												<b>Additional<br/>Filters:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<select name="customfield1" style="width:200px">
													<option value="">Select Field Name</option>
													<option value="">---------------------------------</option>
													<?php 
													foreach($advFieldArr as $k => $v){
														echo '<option value="'.$k.'" '.($k==$customField1?'SELECTED':'').'>'.$v.'</option>';
													}
													?>
												</select>
												<select name="customtype1" onchange="cogeUpdateCount(this)">
													<option value="EQUALS">EQUALS</option>
													<option <?php echo ($customType1=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
													<option <?php echo ($customType1=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
													<option <?php echo ($customType1=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
													<option <?php echo ($customType1=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
												</select>
												<input name="customvalue1" type="text" value="<?php echo $customValue1; ?>" style="width:200px;" onchange="cogeUpdateCount(this)" />
											</div> 
											<div style="margin:10px 0px;">
												<select name="customfield2" style="width:200px">
													<option value="">Select Field Name</option>
													<option value="">---------------------------------</option>
													<?php 
													foreach($advFieldArr as $k => $v){
														echo '<option value="'.$k.'" '.($k==$customField2?'SELECTED':'').'>'.$v.'</option>';
													}
													?>
												</select>
												<select name="customtype2" onchange="cogeUpdateCount(this)">
													<option value="EQUALS">EQUALS</option>
													<option <?php echo ($customType2=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
													<option <?php echo ($customType2=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
													<option <?php echo ($customType2=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
													<option <?php echo ($customType2=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
												</select>
												<input name="customvalue2" type="text" value="<?php echo $customValue2; ?>" style="width:200px;" onchange="cogeUpdateCount(this)" />
											</div> 
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<fieldset style="margin:10px;padding:20px;">
												<legend><b>CoGe Status</b></legend>
												<div>
													<b>Match Count:</b> 
													<?php 
													$dwcaHandler = new DwcArchiverOccurrence();
													$dwcaHandler->setCollArr($collid);
													$dwcaHandler->setVerbose(0);
													$dwcaHandler->addCondition('decimallatitude','NULL');
													$dwcaHandler->addCondition('decimallongitude','NULL');
													$dwcaHandler->addCondition('locality','NOTNULL');
													$dwcaHandler->addCondition('catalognumber','NOTNULL');
													echo '<span id="countdiv">'.$dwcaHandler->getOccurrenceCnt().'</span> records'; 
													?>
													<span id="recalspan" style="color:orange;display:none;">recalculating... <img src="../../images/workingcircle.gif" style="width:13px;" /></span>
												</div>
												<div>
													<b>CoGe Authentication:</b>
													<span id="coge-status" style="width:150px;color:red;">Disconnected</span>
													<span style="margin-left:40px"><input type="button" name="cogeCheckStatusButton" value="Check Status" onclick="cogeCheckAuthentication()" /></span>
													<span style="margin-left:40px"><a href="https://www.museum.tulane.edu/coge/" target="_blank" onclick="startAuthMonitoring()">Login to CoGe</a></span>
												</div>
											</fieldset>
											<fieldset id="coge-communities" style="margin:10px;padding:10px;">
												<legend style="font-weight:bold">Available Communities</legend>
												<div style="margin:10px;">
													To import data into an existing geoLocate community, login to GeoLocate (see above), select the target community, 
													provide a required identifier, an optional descriptive name, and then click the Push Data to GeoLocate button. 
												</div>
												<div style="margin:10px;">
													<div id="coge-commlist" style="margin:15px 0px;padding:15px;border:1px solid orange;">
														<span style="color:orange;">Login to GeoLocate and click check status button to list available communities</span>
													</div>
													<div style="margin:5px;clear:both;">
														<div style="float:left;">Data source identifier (primary name):</div>
														<div style="margin-left:250px;"><input name="cogename" type="text" style="width:300px" onchange="verifyDataSourceIdentifier(this.form)" /></div>
													</div>
													<div style="margin:5px;clear:both;">
														<div style="float:left;">Description:</div>
														<div style="margin-left:250px;"><input name="cogedescr" type="text" style="width:300px" /></div>
													</div>
												</div>
											</fieldset>
											<div style="margin:20px;clear:both;">
												<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
												<input name="format" type="hidden" value="csv" />
												<input name="schema" type="hidden" value="coge" />
												<div style="margin:5px">
													<input id="builddwcabutton" name="builddwcabutton" type="button" value="Push Data to GeoLocate CoGe" onclick="cogePublishDwca(this.form)" disabled /> 
													<span id="coge-download" style="display:none;color:orange">Creating data package... <img src="../../images/workingcircle.gif" style="width:13px;" /></span>
													<span id="coge-push2coge" style="display:none;color:orange">Pushing data to CoGe... <img src="../../images/workingcircle.gif" style="width:13px;" /></span>
													<span id="coge-importcomplete" style="display:none;color:green">
														Success! GeoLocate action required (see message below)
													</span>
												</div>
												<div style="margin-left:15px">
													<div id="coge-dwcalink"></div>
													<div id="coge-guid"></div>
													<div id="coge-importstatus" style="color:orange;display:none;">
														Data import complete! Go to GeoLocate website and open dataset within selected community, 
														then click Update Cache button to index and integrate data into community. 
														After processing step completes, remember to finalize the import process by clicking the save button.
													</div>
												</div>
												<div style="margin:5px">
													<input name="submitaction" type="submit" value="Download Records Locally" />
												</div>
											</div>
											<div style="float:right;">
												<a href="../editor/editreviewer.php?collid=<?php echo $collid; ?>&display=2">Review and Approve Edits</a>
											</div>
											<div style="margin-left:20px;">
												<b>* Default query criteria: catalogNumber and locality are NOT NULL, decimalLatitude is NULL, decimalLongitude is NULL</b>
											</div>
										</td>
									</tr>
								</table>							
							</fieldset>
						</form>
						<?php 
					}
				}
				else{
					?>
					<form name="downloadform" action="../download/downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
						<fieldset>
							<legend><b>Download Specimen Records</b></legend>
							<table>
								<tr>
									<td>
										<div style="margin:10px;">
											<b>Processing Status:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="processingstatus">
												<option value="">All Records</option>
												<?php 
												$statusArr = $dlManager->getProcessingStatusList($collid);
												foreach($statusArr as $v){
													echo '<option value="'.$v.'">'.ucwords($v).'</option>';
												}
												?>
											</select>
										</div> 
									</td>
								</tr>
								<?php 
								if($collMeta['manatype'] == 'Snapshot'){
									?>
									<tr>
										<td>
											<div style="margin:10px;">
												<b>New Records Only:</b> 
											</div> 
										</td>
										<td>
											<div style="margin:10px 0px;">
												<input type="checkbox" name="newrecs" value="1" /> (e.g. records processed within portal)
												<a id="newrecsinfo" href="#" onclick="return false" title="More Information">
													<img src="../../images/info.png" style="width:13px;" />
												</a>
												<div id="newrecsinfodialog">
													Limit to new records entered and processed directly within the 
													portal which have not yet imported into and synchonized with 
													the central database. Avoid importing unprocessed skeletal records since 
													future imports will involve more complex data coordination. 
												</div>
											</div>
										</td>
									</tr>
									<?php 
								}
								?>
								<tr>
									<td>
										<div style="margin:10px;">
											<b>Additional<br/>Filters:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="customfield1" style="width:200px">
												<option value="">Select Field Name</option>
												<option value="">---------------------------------</option>
												<?php 
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField1?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype1">
												<option value="EQUALS">EQUALS</option>
												<option <?php echo ($customType1=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
												<option <?php echo ($customType1=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
												<option <?php echo ($customType1=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
												<option <?php echo ($customType1=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
											</select>
											<input name="customvalue1" type="text" value="<?php echo $customValue1; ?>" style="width:200px;" />
										</div> 
										<div style="margin:10px 0px;">
											<select name="customfield2" style="width:200px">
												<option value="">Select Field Name</option>
												<option value="">---------------------------------</option>
												<?php 
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField2?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype2">
												<option value="EQUALS">EQUALS</option>
												<option <?php echo ($customType2=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
												<option <?php echo ($customType2=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
												<option <?php echo ($customType2=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
												<option <?php echo ($customType2=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
											</select>
											<input name="customvalue2" type="text" value="<?php echo $customValue2; ?>" style="width:200px;" />
										</div> 
										<div style="margin:10px 0px;">
											<select name="customfield3" style="width:200px">
												<option value="">Select Field Name</option>
												<option value="">---------------------------------</option>
												<?php 
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField3?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype3">
												<option value="EQUALS">EQUALS</option>
												<option <?php echo ($customType3=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
												<option <?php echo ($customType3=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
												<option <?php echo ($customType3=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
												<option <?php echo ($customType3=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
											</select>
											<input name="customvalue3" type="text" value="<?php echo $customValue3; ?>" style="width:200px;" />
										</div> 
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b>Structure:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="radio" name="schema" value="symbiota" CHECKED /> 
											Symbiota Native
											<a id="schemanativeinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:13px;" />
											</a><br/>
											<div id="schemanativeinfodialog">
												Symbiota native is very similar to Darwin Core except with the addtion of a few fields
												such as substrate, associated collectors, verbatim description.
											</div>
											<input type="radio" name="schema" value="dwc" /> 
											Darwin Core
											<a id="schemainfodwc" href="#" target="" title="More Information">
												<img src="../../images/info.png" style="width:13px;" />
											</a><br/>
											<div id="schemadwcinfodialog">
												Darwin Core is a TDWG endorsed exchange standard specifically for biodiversity datasets. 
												For more information, visit the <a href="">Darwin Core Documentation</a> website.
											</div>
											<!--  <input type="radio" name="schema" value="specify" /> Specify -->
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b>Data Extensions:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="checkbox" name="identifications" value="1" onchange="extensionSelected(this)" checked /> include Determination History<br/>
											<input type="checkbox" name="images" value="1" onchange="extensionSelected(this)" checked /> include Image Records<br/>
											*Output must be a compressed archive 
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b>Compression:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="checkbox" name="zip" value="1" onchange="zipChanged(this)" checked /> Archive Data Package (ZIP file)<br/>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b>File Format:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="radio" name="format" value="csv" CHECKED /> Comma Delimited (CSV)<br/>
											<input type="radio" name="format" value="tab" /> Tab Delimited<br/>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b>Character Set:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<?php 
											$cSet = strtolower($charset);
											?>
											<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
											<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div style="margin:10px;">
											<input name="targetcollid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="extended" type="hidden" value="1" />
											<input name="submitaction" type="submit" value="Download Records" />
										</div>
									</td>
								</tr>
							</table>							
						</fieldset>
					</form>
					<?php
				}
				echo '</div>';
			}
			else{
				if(!$collid){
					echo '<div>ERROR: collection identifier not defined. Contact administrator</div>';
				}
				else{
					echo '<div style="font-weight:bold;">Access denied</div>';
				}
			}
			?>
		</div>
	</body>
</html>