<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDownload.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;

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
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script src="../../js/symb/geolocate.js?ver=1.0" type="text/javascript"></script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext" style="background-color:white;">
			<?php 
			if($collid && $isEditor){
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
												$dwcaHandler = new DwcArchiverCore();
												$dwcaHandler->setCollArr($collid);
												$dwcaHandler->setVerboseMode(0);
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
												<div id="coge-fieldDiv" style="display:none">
													<div style="margin:5px;clear:both;">
														<div style="float:left;">Data source identifier (primary name):</div>
														<div style="margin-left:250px;"><input name="cogename" type="text" style="width:300px" onchange="verifyDataSourceIdentifier(this.form)" /></div>
													</div>
													<div style="margin:5px;clear:both;">
														<div style="float:left;">Description:</div>
														<div style="margin-left:250px;"><input name="cogedescr" type="text" style="width:300px" /></div>
													</div>
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
											<div style="margin:5px">
												<input name="resetbutton" type="button" value="Reset Page" onclick="cogeCheckAuthentication(); return false;" />
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
				echo '<div style="font-weight:bold;">Access denied</div>';
			}
			?>
		</div>
	</body>
</html>