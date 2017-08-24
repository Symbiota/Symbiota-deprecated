<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDownload.php');
header("Content-Type: text/html; charset=".$CHARSET);

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
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

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
	'verbatimElevation'=>'Verbatim Elevation','disposition'=>'Disposition');
?>
<html>
	<head>
		<title>Occurrence Export Manager</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../js/jquery-ui-1.12.1/jquery-ui.css" type="text/css" rel="Stylesheet" />	
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script>
			
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
				if(f.newrecs && f.newrecs.checked == true && (f.processingstatus.value == "unprocessed" || f.processingstatus.value == "")){
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
												//$cSet = strtolower($charset);
												$cSet = 'iso-8859-1';
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
								only georeferencing relevant data columns for the occurrences. By default, occurrences 
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
								<?php 
								if($traitArr = $dlManager->getAttributeTraits($collid)){
									?>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b>Occurrence Trait<br/>Filter:</b>
											</div> 
										</td>
										<td>
											<div style="margin:10px;">
												<select name="traitid[]" multiple>
													<?php 
														foreach($traitArr as $traitID => $tArr){
															echo '<option value="'.$traitID.'">'.$tArr['name'].' [ID:'.$traitID.']</option>';
														}
													?>
												</select> 
											</div>
											<div style="margin:10px;">
												-- OR select a specific Attribute State --
											</div>
											<div style="margin:10px;">
												<select name="stateid[]" multiple>
													<?php 
													foreach($traitArr as $traitID => $tArr){
														$stateArr = $tArr['state'];
														foreach($stateArr as $stateID => $stateName){
															echo '<option value="'.$stateID.'">'.$tArr['name'].': '.$stateName.'</option>';
														}
													}
													?>
												</select>
											</div>
											<div style="">
												* Hold down the control (ctrl) or command button to select multiple options
											</div>
										</td>
									</tr>
									<?php 
								}
								?>
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
											<input type="checkbox" name="attributes" value="1" onchange="extensionSelected(this)" checked /> include Occurrence Trait Attributes (MeasurementOrFact extension)<br/>
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
											//$cSet = strtolower($charset);
											$cSet = 'iso-8859-1';
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
				echo '<div style="font-weight:bold;">Access denied</div>';
			}
			?>
		</div>
	</body>
</html>