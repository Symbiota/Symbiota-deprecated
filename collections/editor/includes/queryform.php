<?php
if(!$displayQuery && array_key_exists('displayquery',$_REQUEST)) $displayQuery = $_REQUEST['displayquery'];

$qCatalogNumber=''; $qOtherCatalogNumbers=''; 
$qRecordedBy=''; $qRecordNumber=''; $qEventDate=''; 
$qRecordEnteredBy=''; $qObserverUid='';$qDateLastModified='';$qDateEntered='';
$qProcessingStatus='';$qOrderBy='';$qOrderByDir='';
$qImgOnly='';$qWithoutImg='';$qExsiccatiId='';
$qCustomField1='';$qCustomType1='';$qCustomValue1='';
$qCustomField2='';$qCustomType2='';$qCustomValue2='';
$qCustomField3='';$qCustomType3='';$qCustomValue3='';
$qryArr = $occManager->getQueryVariables();
if($qryArr){
	$qCatalogNumber = (array_key_exists('cn',$qryArr)?$qryArr['cn']:'');
	$qOtherCatalogNumbers = (array_key_exists('ocn',$qryArr)?$qryArr['ocn']:'');
	$qRecordedBy = (array_key_exists('rb',$qryArr)?$qryArr['rb']:'');
	$qRecordNumber = (array_key_exists('rn',$qryArr)?$qryArr['rn']:'');
	$qEventDate = (array_key_exists('ed',$qryArr)?$qryArr['ed']:'');
	$qRecordEnteredBy = (array_key_exists('eb',$qryArr)?$qryArr['eb']:'');
	$qObserverUid = (array_key_exists('ouid',$qryArr)?$qryArr['ouid']:0);
	$qProcessingStatus = (array_key_exists('ps',$qryArr)?$qryArr['ps']:'');
	$qDateEntered = (array_key_exists('de',$qryArr)?$qryArr['de']:'');
	$qDateLastModified = (array_key_exists('dm',$qryArr)?$qryArr['dm']:'');
	$qExsiccatiId = (array_key_exists('exid',$qryArr)?$qryArr['exid']:'');
	$qImgOnly = (array_key_exists('io',$qryArr)?$qryArr['io']:0);
	$qWithoutImg = (array_key_exists('woi',$qryArr)?$qryArr['woi']:0);
	$qCustomField1 = (array_key_exists('cf1',$qryArr)?$qryArr['cf1']:'');
	$qCustomType1 = (array_key_exists('ct1',$qryArr)?$qryArr['ct1']:'');
	$qCustomValue1 = (array_key_exists('cv1',$qryArr)?$qryArr['cv1']:'');
	$qCustomField2 = (array_key_exists('cf2',$qryArr)?$qryArr['cf2']:'');
	$qCustomType2 = (array_key_exists('ct2',$qryArr)?$qryArr['ct2']:'');
	$qCustomValue2 = (array_key_exists('cv2',$qryArr)?$qryArr['cv2']:'');
	$qCustomField3 = (array_key_exists('cf3',$qryArr)?$qryArr['cf3']:'');
	$qCustomType3 = (array_key_exists('ct3',$qryArr)?$qryArr['ct3']:'');
	$qCustomValue3 = (array_key_exists('cv3',$qryArr)?$qryArr['cv3']:'');
	$qOcrFrag = (array_key_exists('ocr',$qryArr)?$qryArr['ocr']:'');
	$qOrderBy = (array_key_exists('orderby',$qryArr)?$qryArr['orderby']:'');
	$qOrderByDir = (array_key_exists('orderbydir',$qryArr)?$qryArr['orderbydir']:'');
}
//Set processing status  
$processingStatusArr = array();
if(isset($PROCESSINGSTATUS) && $PROCESSINGSTATUS){
	$processingStatusArr = $PROCESSINGSTATUS;
}
else{
	$processingStatusArr = array('unprocessed','unprocessed/NLP','stage 1','stage 2','stage 3','pending review','expert required','reviewed','closed');
}
?>
<div id="querydiv" style="clear:both;width:790px;display:<?php echo ($displayQuery?'block':'none'); ?>;">
	<form name="queryform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="return verifyQueryForm(this)">
		<fieldset style="padding:5px;">
			<legend><b>Record Search Form</b></legend>
			<?php 
			if(!$crowdSourceMode){
				?>
				<div style="margin:2px;">
					<span title="Full name of collector as entered in database. To search just on last name, place the wildcard character (%) before name (%Gentry).">
						<b>Collector:</b> 
						<input type="text" name="q_recordedby" value="<?php echo $qRecordedBy; ?>" onchange="setOrderBy(this)" />
					</span>
					<span style="margin-left:25px;"><b>Number:</b></span>
					<span title="Separate multiple terms by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
						<input type="text" name="q_recordnumber" value="<?php echo $qRecordNumber; ?>" style="width:120px;" onchange="setOrderBy(this)" />
					</span>
					<span style="margin-left:15px;" title="Enter ranges separated by ' - ' (space before and after dash required), e.g.: 2002-01-01 - 2003-01-01">
						<b>Date:</b> 
						<input type="text" name="q_eventdate" value="<?php echo $qEventDate; ?>" style="width:160px" onchange="setOrderBy(this)" />
					</span>
				</div>
				<?php 
			}
			?>
			<div style="margin:2px;">
				<b>Catalog Number:</b> 
				<span title="Separate multiples by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
					<input type="text" name="q_catalognumber" value="<?php echo $qCatalogNumber; ?>" onchange="setOrderBy(this)" />
				</span>
				<?php 
				if($crowdSourceMode){
					?>
					<span style="margin-left:25px;"><b>OCR Fragment:</b></span> 
					<span title="Search for term embedded within OCR block of text">
						<input type="text" name="q_ocrfrag" value="<?php echo $qOcrFrag; ?>" style="width:200px;" />
					</span>
					<?php 
				}
				else{
					?>
					<span style="margin-left:25px;"><b>Other Catalog Numbers:</b></span> 
					<span title="Separate multiples by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
						<input type="text" name="q_othercatalognumbers" value="<?php echo $qOtherCatalogNumbers; ?>" />
					</span>
					<?php
				}
				?>
			</div>
			<?php 
			if(!$crowdSourceMode){
				?>
				<div style="margin:2px;">
					<?php
					if($isGenObs && $isAdmin){
						?>
						<span style="margin-right:25px;">
							<input type="checkbox" name="q_observeruid" value="<?php echo $symbUid; ?>" <?php echo ($qObserverUid?'CHECKED':''); ?> />
							<b>Only My Records</b>
						</span>
						<?php 
					}
					else{
						?>
						<input type="hidden" name="q_observeruid" value="<?php echo $isGenObs?$symbUid:''; ?>" />
						<?php 
					}
					?>
					<span style="margin-right:15px;<?php echo ($isGenObs?'display:none':''); ?>">
						<b>Entered by:</b> 
						<input type="text" name="q_recordenteredby" value="<?php echo $qRecordEnteredBy; ?>" style="width:70px;" onchange="setOrderBy(this)" />
					</span>
					<span style="margin-right:15px;" title="Enter ranges separated by ' - ' (space before and after dash required), e.g.: 2002-01-01 - 2003-01-01">
						<b>Date entered:</b> 
						<input type="text" name="q_dateentered" value="<?php echo $qDateEntered; ?>" style="width:160px" onchange="setOrderBy(this)" />
					</span>
					<span title="Enter ranges separated by ' - ' (space before and after dash required), e.g.: 2002-01-01 - 2003-01-01">
						<b>Date modified:</b> 
						<input type="text" name="q_datelastmodified" value="<?php echo $qDateLastModified; ?>" style="width:160px" onchange="setOrderBy(this)" />
					</span>
				</div>
				<div style="margin:2px;">
					<span><b>Processing Status:</b></span> 
					<select name="q_processingstatus" onchange="setOrderBy(this)">
						<option value=''>All Records</option>
						<option>-------------------</option>
						<?php 
						foreach($processingStatusArr as $v){
							//Don't display these options is editor is crowd sourced 
							$keyOut = strtolower($v);
							echo '<option value="'.$keyOut.'" '.($qProcessingStatus==$keyOut?'SELECTED':'').'>'.ucwords($v).'</option>';
						}
						echo '<option value="isnull" '.($qProcessingStatus=='isnull'?'SELECTED':'').'>No Set Status</option>';
						if(!in_array($qProcessingStatus,$processingStatusArr)){
							echo '<option value="'.$qProcessingStatus.'" SELECTED>'.$qProcessingStatus.'</option>';
						}
						?>
					</select>
					<span style="margin-left:8px">
						<input name="q_imgonly" type="checkbox" value="1" <?php echo ($qImgOnly==1?'checked':''); ?> onchange="this.form.q_withoutimg.checked = false;" /> 
						<b>With images</b>
					</span>
					<span style="margin-left:8px">
						<input name="q_withoutimg" type="checkbox" value="1" <?php echo ($qWithoutImg==1?'checked':''); ?> onchange="this.form.q_imgonly.checked = false;" /> 
						<b>Without images</b>
					</span>
					<?php
					if($ACTIVATE_EXSICCATI){
						?>
						<span style="margin-left:15px" title="Enter Exsiccati ID (ometid)">
							<b>Exsiccati ID (ometid):</b> 
							<input type="text" name="q_exsiccatiid" id="q_exsiccatiid" value="<?php echo $qExsiccatiId; ?>" style="width:70px" onchange="" />
						</span>
						<?php
					}
					?>
				</div>
				<?php
			}
			$advFieldArr = array();
			if($crowdSourceMode){
				$advFieldArr = array('family'=>'Family','sciname'=>'Scientific Name','othercatalognumbers'=>'Other Catalog Numbers',
					'country'=>'Country','stateProvince'=>'State/Province','county'=>'County','municipality'=>'Municipality',
					'recordedby'=>'Collector','recordnumber'=>'Collector Number','eventdate'=>'Collection Date');
			}
			else{
				$advFieldArr = array('family'=>'Family','genus'=>'Genus','specificEpithet'=>'Specific Epithet','sciname'=>'Scientific Name','scientificNameAuthorship'=>'Author','identifiedBy'=>'Identified By',
					'identificationReferences'=>'Identification References','identificationRemarks'=>'Identification Remarks','taxonRemarks'=>'Taxon Remarks',
					'identificationQualifier'=>'Identification Qualifier','typeStatus'=>'Type Status',
					'catalogNumber'=>'Catalog Number','otherCatalogNumbers'=>'Other Catalog Numbers',
					'recordedBy'=>'Collector/Observer','recordNumber'=>'Collector Number','associatedCollectors'=>'Associated Collectors',
					'verbatimEventDate'=>'Verbatim Date','habitat'=>'Habitat','substrate'=>'Substrate','occurrenceRemarks'=>'Notes (Occurrence Remarks)',
					'associatedTaxa'=>'Associated Taxa','verbatimAttributes'=>'Description','reproductiveCondition'=>'Reproductive Condition',
					'establishmentMeans'=>'Establishment Means','lifeStage'=>'Life Stage','sex'=>'Sex','preparations'=>'Preparations',
					'individualCount'=>'Individual Count','samplingProtocol'=>'Sampling Protocol','country'=>'Country',
					'stateProvince'=>'State/Province','county'=>'County','municipality'=>'Municipality','locality'=>'Locality',
					'decimalLatitude'=>'Decimal Latitude','decimalLongitude'=>'Decimal Longitude','geodeticDatum'=>'Geodetic Datum',
					'coordinateUncertaintyInMeters'=>'Uncertainty (m)','verbatimCoordinates'=>'Verbatim Coordinates',
					'georeferencedBy'=>'Georeferenced By','georeferenceProtocol'=>'Georeference Protocol','georeferenceSources'=>'Georeference Sources',
					'georeferenceVerificationStatus'=>'Georeference Verification Status','georeferenceRemarks'=>'Georeference Remarks','localitySecurity'=>'Locality Security',
					'minimumElevationInMeters'=>'Elevation Minimum (m)','maximumElevationInMeters'=>'Elevation Maximum (m)',
					'verbatimElevation'=>'Verbatim Elevation','disposition'=>'Disposition','ocrFragment'=>'OCR Fragment','username'=>'Modified By','dateEntered'=>'Date Entered','basisOfRecord'=>'Basis Of Record');
			}
			//sort($advFieldArr);
			?>
			<div style="margin:2px 0px;">
				<b>Custom Field 1:</b> 
				<select name="q_customfield1" onchange="customSelectChanged(1)">
					<option value="">Select Field Name</option>
					<option value="">---------------------------------</option>
					<?php 
					foreach($advFieldArr as $k => $v){
						echo '<option value="'.$k.'" '.($k==$qCustomField1?'SELECTED':'').'>'.$v.'</option>';
					}
					?>
				</select>
				<select name="q_customtype1">
					<option>EQUALS</option>
					<option <?php echo ($qCustomType1=='NOT EQUALS'?'SELECTED':''); ?> value="NOT EQUALS">NOT EQUALS</option>
					<option <?php echo ($qCustomType1=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
					<option <?php echo ($qCustomType1=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
					<option <?php echo ($qCustomType1=='GREATER'?'SELECTED':''); ?> value="GREATER">GREATER THAN</option>
					<option <?php echo ($qCustomType1=='LESS'?'SELECTED':''); ?> value="LESS">LESS THAN</option>
					<option <?php echo ($qCustomType1=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
					<option <?php echo ($qCustomType1=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
				</select>
				<input name="q_customvalue1" type="text" value="<?php echo $qCustomValue1; ?>" style="width:200px;" />
				<a href="#" onclick="toggleCustomDiv2();return false;">
					<img src="../../images/editplus.png" />
				</a>
			</div>
			<div id="customdiv2" style="margin:2px 0px;display:<?php echo ($qCustomValue2||$qCustomType2=='NULL'||$qCustomType2=='NOTNULL'?'block':'none');?>;">
				<b>Custom Field 2:</b> 
				<select name="q_customfield2" onchange="customSelectChanged(2)">
					<option value="">Select Field Name</option>
					<option value="">---------------------------------</option>
					<?php 
					foreach($advFieldArr as $k => $v){
						echo '<option value="'.$k.'" '.($k==$qCustomField2?'SELECTED':'').'>'.$v.'</option>';
					}
					?>
				</select>
				<select name="q_customtype2">
					<option>EQUALS</option>
					<option <?php echo ($qCustomType2=='NOT EQUALS'?'SELECTED':''); ?> value="NOT EQUALS">NOT EQUALS</option>
					<option <?php echo ($qCustomType2=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
					<option <?php echo ($qCustomType2=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
					<option <?php echo ($qCustomType2=='GREATER'?'SELECTED':''); ?> value="GREATER">GREATER THAN</option>
					<option <?php echo ($qCustomType2=='LESS'?'SELECTED':''); ?> value="LESS">LESS THAN</option>
					<option <?php echo ($qCustomType2=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
					<option <?php echo ($qCustomType2=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
				</select>
				<input name="q_customvalue2" type="text" value="<?php echo $qCustomValue2; ?>" style="width:200px;" />
				<a href="#" onclick="toggleCustomDiv3();return false;">
					<img src="../../images/editplus.png" />
				</a>
			</div>
			<div id="customdiv3" style="margin:2px 0px;display:<?php echo ($qCustomValue3||$qCustomType3=='NULL'||$qCustomType3=='NOTNULL'?'block':'none');?>;">
				<b>Custom Field 3:</b> 
				<select name="q_customfield3" onchange="customSelectChanged(3)">
					<option value="">Select Field Name</option>
					<option value="">---------------------------------</option>
					<?php 
					foreach($advFieldArr as $k => $v){
						echo '<option value="'.$k.'" '.($k==$qCustomField3?'SELECTED':'').'>'.$v.'</option>';
					}
					?>
				</select>
				<select name="q_customtype3">
					<option>EQUALS</option>
					<option <?php echo ($qCustomType3=='NOT EQUALS'?'SELECTED':''); ?> value="NOT EQUALS">NOT EQUALS</option>
					<option <?php echo ($qCustomType3=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
					<option <?php echo ($qCustomType3=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
					<option <?php echo ($qCustomType3=='GREATER'?'SELECTED':''); ?> value="GREATER">GREATER THAN</option>
					<option <?php echo ($qCustomType3=='LESS'?'SELECTED':''); ?> value="LESS">LESS THAN</option>
					<option <?php echo ($qCustomType3=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
					<option <?php echo ($qCustomType3=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
				</select>
				<input name="q_customvalue3" type="text" value="<?php echo $qCustomValue3; ?>" style="width:200px;" />
			</div>
			<?php 
			if(!$crowdSourceMode){
				$qryStr = '';
				if($qRecordedBy) $qryStr .= '&recordedby='.$qRecordedBy;
				if($qRecordNumber) $qryStr .= '&recordnumber='.$qRecordNumber;
				if($qEventDate) $qryStr .= '&eventdate='.$qEventDate;
				if($qCatalogNumber) $qryStr .= '&catalognumber='.$qCatalogNumber;
				if($qOtherCatalogNumbers) $qryStr .= '&othercatalognumbers='.$qOtherCatalogNumbers;
				if($qRecordEnteredBy) $qryStr .= '&recordenteredby='.$qRecordEnteredBy;
				if($qObserverUid) $qryStr .= '&observeruid='.$qObserverUid;
				if($qDateEntered) $qryStr .= '&dateentered='.$qDateEntered;
				if($qDateLastModified) $qryStr .= '&datelastmodified='.$qDateLastModified;
				if($qryStr){
					?>
					<div style="float:right;margin-top:10px;" title="Go to Label Printing Module">
						<a href="../reports/labelmanager.php?collid=<?php echo $collId.$qryStr; ?>">
							<img src="../../images/list.png" style="width:15px;" />
						</a>
					</div>
					<?php 
				}
			}
			?>
			<div style="margin:5px;">
				<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
				<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
				<input type="hidden" name="occid" value="" />
				<input type="hidden" name="occindex" value="0" />
				<input type="button" name="submitaction" value="Display Editor" onclick="submitQueryEditor(this.form)" />
				<input type="button" name="submitaction" value="Display Table" onclick="submitQueryTable(this.form)" />
				<span style="margin-left:10px;">
					<input type="button" name="reset" value="Reset Form" onclick="resetQueryForm(this.form)" /> 
				</span>
				<span style="margin-left:10px;">
					<b>Sort by:</b> 
					<select name="orderby">
						<option value=""></option>
						<option value="recordedby" <?php echo ($qOrderBy=='recordedby'?'SELECTED':''); ?>>Collector</option>
						<option value="recordnumber" <?php echo ($qOrderBy=='recordnumber'?'SELECTED':''); ?>>Number</option>
						<option value="eventdate" <?php echo ($qOrderBy=='eventdate'?'SELECTED':''); ?>>Date</option>
						<option value="catalognumber" <?php echo ($qOrderBy=='catalognumber'?'SELECTED':''); ?>>Catalog Number</option>
						<option value="recordenteredby" <?php echo ($qOrderBy=='recordenteredby'?'SELECTED':''); ?>>Entered By</option>
						<option value="dateentered" <?php echo ($qOrderBy=='dateentered'?'SELECTED':''); ?>>Date Entered</option>
						<option value="datelastmodified" <?php echo ($qOrderBy=='datelastmodified'?'SELECTED':''); ?>>Date Last modified</option>
						<option value="processingstatus" <?php echo ($qOrderBy=='processingstatus'?'SELECTED':''); ?>>Processing Status</option>
						<option value="sciname" <?php echo ($qOrderBy=='sciname'?'SELECTED':''); ?>>Scientific Name</option>
						<option value="family" <?php echo ($qOrderBy=='family'?'SELECTED':''); ?>>Family</option>
						<option value="country" <?php echo ($qOrderBy=='country'?'SELECTED':''); ?>>Country</option>
						<option value="stateprovince" <?php echo ($qOrderBy=='stateprovince'?'SELECTED':''); ?>>State / Province</option>
						<option value="county" <?php echo ($qOrderBy=='county'?'SELECTED':''); ?>>County</option>
						<option value="municipality" <?php echo ($qOrderBy=='municipality'?'SELECTED':''); ?>>Municipality</option>
						<option value="locality" <?php echo ($qOrderBy=='locality'?'SELECTED':''); ?>>Locality</option>
						<option value="decimallatitude" <?php echo ($qOrderBy=='decimallatitude'?'SELECTED':''); ?>>Decimal Latitude</option>
						<option value="decimallongitude" <?php echo ($qOrderBy=='decimallongitude'?'SELECTED':''); ?>>Decimal Longitude</option>
						<option value="minimumelevationinmeters" <?php echo ($qOrderBy=='minimumelevationinmeters'?'SELECTED':''); ?>>Elevation Minimum</option>
						<option value="maximumelevationinmeters" <?php echo ($qOrderBy=='maximumelevationinmeters'?'SELECTED':''); ?>>Elevation Maximum</option>
					</select>
				</span>
				<span>
					<select name="orderbydir">
						<option value="ASC">ascending</option>
						<option value="DESC" <?php echo ($qOrderByDir=='DESC'?'SELECTED':''); ?>>descending</option>
					</select>
				</span>
			</div>
		</fieldset>
	</form>
</div>