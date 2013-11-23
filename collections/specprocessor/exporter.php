<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownloadManager.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;

$customField1 = array_key_exists('customfield1',$_REQUEST)?$_REQUEST['customfield1']:'';
$customType1 = array_key_exists('customtype1',$_REQUEST)?$_REQUEST['customtype1']:'';
$customValue1 = array_key_exists('customvalue1',$_REQUEST)?$_REQUEST['customvalue1']:'';
$customField2 = array_key_exists('customfield2',$_REQUEST)?$_REQUEST['customfield2']:'';
$customType2 = array_key_exists('customtype2',$_REQUEST)?$_REQUEST['customtype2']:'';
$customValue2 = array_key_exists('customvalue2',$_REQUEST)?$_REQUEST['customvalue2']:'';

$dlManager = new OccurrenceDownloadManager();
$collMeta = $dlManager->getCollectionMetadata($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
 	$isEditor = true;
}

$advFieldArr = array('family'=>'Family','sciname'=>'Scientific Name','identifiedBy'=>'Identified By','typeStatus'=>'Type Status',
	'catalogNumber'=>'Catalog Number','otherCatalogNumbers'=>'Other Catalog Numbers','occurrenceId'=>'Occurrence ID (GUID)',
	'recordedBy'=>'Collector/Observer','recordNumber'=>'Collector Number','associatedCollectors'=>'Associated Collectors',
	'verbatimEventDate'=>'Verbatim Date','habitat'=>'Habitat','substrate'=>'Substrate','occurrenceRemarks'=>'Occurrence Remarks',
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
		<link href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script language="javascript">
			$(function() {
				var dialogArr = new Array("schema","");
				var dialogStr = "";
				for(i=0;i<dialogArr.length;i++){
					dialogStr = dialogArr[i]+"info";
					$( "#"+dialogStr+"dialog" ).dialog({
						autoOpen: false,
						modal: true
					});
	
					$( "#"+dialogStr ).click(function() {
						$( "#"+this.id+"dialog" ).dialog( "open" );
					});
				}
	
			});
	
			function validateDownloadForm(f){

				return true;
			}

		</script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<div style="padding:15px;">
				This download module is designed to aid collection managers in extracting specimen data
				for import into local systems.
				This feature is particularly useful for extracting records that have been processed 
				using the digitization tools built into the portal 
				(crowdsourcing, OCR/NLP, basic data entry, etc). 
			</div>
			<?php 
			if($collMeta['manatype'] == 'Snapshot'){
				?>
				<div style="padding:15px;">
					Records imported from a central database will be linked to the primary record
					through a specimen unique identifier (barcode, primary key, etc) 
					which is stored in the portal database. 
					New records that are digitized directly 
					in the data portal due to image/skeletal records ingestion 
					antoher batch processing workflow will have a null unique identifier, 
					which will identify the record as new and not yet synchronized to the central database.
					When new records are extracted from the portal, imported into the central database, 
					and then the portal's data snapshot is refreshed, these records should be synchronized
					with the central records.
				</div>
				<?php
			}
			if($isEditor){
				if($collid){
					?>
					<form name="downloadform" action="index.php" method="post" onsubmit="return validateDownloadForm(this);">
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
											<select name="Processing Status">
												<option value="all">All Records</option>
												<?php 
												$statusArr = $dlManager->getProcessingStatusList($collid);
												foreach($statusArr as $v){
													echo '<option value="'.$v.'" '.($v == 'unprocessed'?'selected':'').'>'.ucwords($v).'</option>';
												}
												?>
											</select>
										</div> 
									</td>
								</tr>
								<tr>
									<td>
										<div style="margin:10px;">
											<b>Additional Filter:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="customfield1">
												<option value="">Select Field Name</option>
												<option value="">---------------------------------</option>
												<?php 
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField1?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype1">
												<option>EQUALS</option>
												<option <?php echo ($customType1=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
												<option <?php echo ($customType1=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
												<option <?php echo ($customType1=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
												<option <?php echo ($customType1=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
											</select>
											<input name="customvalue1" type="text" value="<?php echo $customValue1; ?>" style="width:200px;" />
										</div> 
										<div style="margin:10px 0px;">
											<select name="customfield2">
												<option value="">Select Field Name</option>
												<option value="">---------------------------------</option>
												<?php 
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField2?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype2">
												<option>EQUALS</option>
												<option <?php echo ($customType2=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
												<option <?php echo ($customType2=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
												<option <?php echo ($customType2=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
												<option <?php echo ($customType2=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
											</select>
											<input name="customvalue2" type="text" value="<?php echo $customValue2; ?>" style="width:200px;" />
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
											<input type="radio" name="schema" value="dwc" /> Darwin Core
											<a href="" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a><br/>
											<input type="radio" name="schema" value="symbiota" CHECKED /> Symbiota Native
											<!--  <input type="radio" name="schema" value="specify" /> Specify -->
											<a id="schemainfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="schemainfodialog">
												Symbiota native is very similar to Darwin Core except with the addtion of a few fields
												such as substrate, associated collectors, verbatim description.
											</div>
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
									<td valign="top">
										<div style="margin:10px;">
											<b>Additional Data:</b>
										</div> 
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="checkbox" name="identifications" value="1" onchange="this.form.zip.checked = true" /> Determination History<br/>
											<input type="checkbox" name="images" value="1" onchange="this.form.zip.checked = true" /> Image Records
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
											<input type="checkbox" name="zip" value="1" checked /> Archive File (ZIP file)<br/>
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
												<input type="checkbox" name="newrecs" value="1" CHECKED />
												<a id="newrecsinfo" href="#" onclick="return false" title="More Information">
													<img src="../../images/info.png" style="width:15px;" />
												</a>
												<div id="newrecsinfodialog">
													New recorded entered and processed directly within the 
													portal which have not yet imported into and synchonized with 
													the central database.
												</div>
											</div>
										</td>
									</tr>
									<?php 
								}
								?>
								<tr>
									<td colspan="2">
										<div style="margin:10px;">
											<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
											<input type="submit" name="submitaction" value="Download Specimen Records" />
										</div>
									</td>
								</tr>
							</table>							
						</fieldset>
					</form>
					<?php 
				}
				else{
					echo '<div>ERROR: collection identifier not defined. Contact administrator</div>';
				}
			}
			else{
				?>
				<div style='font-weight:bold;'>
					Access denied
				</div>
				<?php 
			}
			?>
		</div>
	</body>
</html>