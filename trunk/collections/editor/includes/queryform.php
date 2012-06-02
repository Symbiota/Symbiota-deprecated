<?php
if(!$displayQuery && array_key_exists('displayquery',$_REQUEST)) $displayQuery = $_REQUEST['displayquery'];

$qIdentifier=''; $qOtherCatalogNumbers=''; 
$qRecordedBy=''; $qRecordNumber=''; $qEventDate=''; 
$qEnteredBy=''; $qObserverUid='';$qProcessingStatus=''; $qDateLastModified='';
$qCustomField1='';$qCustomType1='';$qCustomValue1='';
$qCustomField2='';$qCustomType2='';$qCustomValue2='';
$qCustomField3='';$qCustomType3='';$qCustomValue3='';
$qryArr = $occManager->getQueryVariables();
if($qryArr){
	$qIdentifier = (array_key_exists('id',$qryArr)?$qryArr['id']:'');
	$qOtherCatalogNumbers = (array_key_exists('ocn',$qryArr)?$qryArr['ocn']:'');
	$qRecordedBy = (array_key_exists('rb',$qryArr)?$qryArr['rb']:'');
	$qRecordNumber = (array_key_exists('rn',$qryArr)?$qryArr['rn']:'');
	$qEventDate = (array_key_exists('ed',$qryArr)?$qryArr['ed']:'');
	$qEnteredBy = (array_key_exists('eb',$qryArr)?$qryArr['eb']:'');
	$qObserverUid = (array_key_exists('ouid',$qryArr)?$qryArr['ouid']:0);
	$qProcessingStatus = (array_key_exists('ps',$qryArr)?$qryArr['ps']:'');
	$qDateLastModified = (array_key_exists('dm',$qryArr)?$qryArr['dm']:'');
	$qCustomField1 = (array_key_exists('cf1',$qryArr)?$qryArr['cf1']:'');
	$qCustomType1 = (array_key_exists('ct1',$qryArr)?$qryArr['ct1']:'');
	$qCustomValue1 = (array_key_exists('cv1',$qryArr)?$qryArr['cv1']:'');
	$qCustomField2 = (array_key_exists('cf2',$qryArr)?$qryArr['cf2']:'');
	$qCustomType2 = (array_key_exists('ct2',$qryArr)?$qryArr['ct2']:'');
	$qCustomValue2 = (array_key_exists('cv2',$qryArr)?$qryArr['cv2']:'');
	$qCustomField3 = (array_key_exists('cf3',$qryArr)?$qryArr['cf3']:'');
	$qCustomType3 = (array_key_exists('ct3',$qryArr)?$qryArr['ct3']:'');
	$qCustomValue3 = (array_key_exists('cv3',$qryArr)?$qryArr['cv3']:'');
}
?>
<div id="querydiv" style="clear:both;width:790px;display:<?php echo ($displayQuery?'block':'none'); ?>;">
	<form name="queryform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return verifyQueryForm(this)">
		<fieldset style="padding:5px;">
			<legend><b>Record Search Form</b></legend>
			<div style="margin:2px;">
				<span title="Full name of collector as entered in database. To search just on last name, place the wildcard character (%) before name (%Gentry).">
					Collector: 
					<input type="text" name="q_recordedby" value="<?php echo $qRecordedBy; ?>" />
				</span>
				<span style="margin-left:25px;">Number:</span>
				<span title="Separate multiple terms by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
					<input type="text" name="q_recordnumber" value="<?php echo $qRecordNumber; ?>" style="width:120px;" />
				</span>
				<span style="margin-left:15px;" title="Enter ranges separated by ' - ' (space before and after dash required), e.g.: 2002-01-01 - 2003-01-01">
					Date: 
					<input type="text" name="q_eventdate" value="<?php echo $qEventDate; ?>" style="width:160px" />
				</span>
			</div>
			<div style="margin:2px;">
				Catalog Number: 
				<span title="Separate multiples by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
					<input type="text" name="q_identifier" value="<?php echo $qIdentifier; ?>" />
				</span>
				<span style="margin-left:25px;">Other Catalog Numbers:</span> 
				<span title="Separate multiples by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
					<input type="text" name="q_othercatalognumbers" value="<?php echo $qOtherCatalogNumbers; ?>" />
				</span>
			</div>
			<div style="margin:2px;">
				<?php
				if($isGenObs && $isAdmin){
					?>
					<span style="margin-right:25px;">
						<input type="checkbox" name="q_observeruid" value="<?php echo $symbUid; ?>" <?php echo ($qObserverUid?'CHECKED':''); ?> />
						Only My Records
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
					Entered by: 
					<input type="text" name="q_enteredby" value="<?php echo $qEnteredBy; ?>" />
				</span>
				<span title="Enter ranges separated by ' - ' (space before and after dash required), e.g.: 2002-01-01 - 2003-01-01">
					Date entered: 
					<input type="text" name="q_datelastmodified" value="<?php echo $qDateLastModified; ?>" style="width:160px" />
				</span>
				<span style="margin-left:15px;">Status:</span> 
				<select name="q_processingstatus">
					<option value=''>All Records</option>
					<option>-------------------</option>
					<option value="unprocessed" <?php echo ($qProcessingStatus=='unprocessed'?'SELECTED':''); ?>>
						Unprocessed
					</option>
					<option value="unprocessed/OCR" <?php echo ($qProcessingStatus=='unprocessed/OCR'?'SELECTED':''); ?>>
						Unprocessed/OCR 
					</option>
					<option  value="unprocessed/NLP" <?php echo ($qProcessingStatus=='unprocessed/NLP'?'SELECTED':''); ?>>
						Unprocessed/NLP
					</option>
					<option value="stage 1" <?php echo ($qProcessingStatus=='stage 1'?'SELECTED':''); ?>>
						Stage 1
					</option>
					<option value="stage 2" <?php echo ($qProcessingStatus=='stage 2'?'SELECTED':''); ?>>
						Stage 2
					</option>
					<option value="stage 3" <?php echo ($qProcessingStatus=='stage 3'?'SELECTED':''); ?>>
						Stage 3
					</option>
					<option value="pending duplicate" <?php echo ($qProcessingStatus=='pending duplicate'?'SELECTED':''); ?>>
						Pending Duplicate
					</option>
					<option value="pending review" <?php echo ($qProcessingStatus=='pending review'?'SELECTED':''); ?>>
						Pending Review
					</option>
					<option value="expert required" <?php echo ($qProcessingStatus=='expert required'?'SELECTED':''); ?>>
						Expert Required
					</option>
					<option value="reviewed" <?php echo ($qProcessingStatus=='reviewed'?'SELECTED':''); ?>>
						Reviewed
					</option>
				</select>
			</div>
			<?php 
			$advFieldArr = array('family','sciname','identifiedBy','identificationReferences','identificationRemarks','identificationQualifier',
				'typeStatus','associatedCollectors','verbatimEventDate','habitat','substrate','occurrenceRemarks','associatedTaxa',
				'verbatimAttributes','reproductiveCondition','establishmentMeans','lifeStage','sex','individualCount','country',
				'stateProvince','county','municipality','locality','decimalLatitude','decimalLongitude','geodeticDatum',
				'coordinateUncertaintyInMeters','verbatimCoordinates','georeferencedBy','georeferenceProtocol',
				'georeferenceSources','georeferenceVerificationStatus','georeferenceRemarks','minimumElevationInMeters',
				'maximumElevationInMeters','verbatimElevation','disposition');
			sort($advFieldArr);
			?>
			<div style="margin:2px 0px;">
				Custom Field 1: 
				<select name="q_customfield1">
					<option value="">Select Field Name</option>
					<option value="">---------------------------------</option>
					<?php 
					foreach($advFieldArr as $v){
						echo '<option '.($v==$qCustomField1?'SELECTED':'').'>'.$v.'</option>';
					}
					?>
				</select>
				<select name="q_customtype1">
					<option>EQUALS</option>
					<option <?php echo ($qCustomType1=='LIKE'?'SELECTED':''); ?>>LIKE</option>
					<option <?php echo ($qCustomType1=='IS NULL'?'SELECTED':''); ?>>IS NULL</option>
				</select>
				<input name="q_customvalue1" type="text" value="<?php echo $qCustomValue1; ?>" style="width:200px;" />
				<a href="#" onclick="toggle('customdiv2');return false;">
					<img src="../../images/editplus.png" />
				</a>
			</div>
			<div id="customdiv2" style="margin:2px 0px;display:<?php echo ($qCustomValue2?'block':'none');?>;">
				Custom Field 2: 
				<select name="q_customfield2">
					<option value="">Select Field Name</option>
					<option value="">---------------------------------</option>
					<?php 
					foreach($advFieldArr as $v){
						echo '<option '.($v==$qCustomField2?'SELECTED':'').'>'.$v.'</option>';
					}
					?>
				</select>
				<select name="q_customtype2">
					<option>EQUALS</option>
					<option <?php echo ($qCustomType2=='LIKE'?'SELECTED':''); ?>>LIKE</option>
					<option <?php echo ($qCustomType2=='IS NULL'?'SELECTED':''); ?>>IS NULL</option>
				</select>
				<input name="q_customvalue2" type="text" value="<?php echo $qCustomValue2; ?>" style="width:200px;" />
				<a href="#" onclick="toggle('customdiv3');return false;">
					<img src="../../images/editplus.png" />
				</a>
			</div>
			<div id="customdiv3" style="margin:2px 0px;display:<?php echo ($qCustomValue2?'block':'none');?>;">
				Custom Field 3: 
				<select name="q_customfield3">
					<option value="">Select Field Name</option>
					<option value="">---------------------------------</option>
					<?php 
					foreach($advFieldArr as $v){
						echo '<option '.($v==$qCustomField3?'SELECTED':'').'>'.$v.'</option>';
					}
					?>
				</select>
				<select name="q_customtype3">
					<option>EQUALS</option>
					<option <?php echo ($qCustomType3=='LIKE'?'SELECTED':''); ?>>LIKE</option>
					<option <?php echo ($qCustomType3=='IS NULL'?'SELECTED':''); ?>>IS NULL</option>
				</select>
				<input name="q_customvalue3" type="text" value="<?php echo $qCustomValue3; ?>" style="width:200px;" />
			</div>
			<?php 
			$qryStr = '';
			if($qRecordedBy) $qryStr .= '&recordedby='.$qRecordedBy;
			if($qRecordNumber) $qryStr .= '&recordnumber='.$qRecordNumber;
			if($qEventDate) $qryStr .= '&eventdate='.$qEventDate;
			if($qIdentifier) $qryStr .= '&identifier='.$qIdentifier;
			if($qIdentifier) $qryStr .= '&identifier='.$qIdentifier;
			if($qEnteredBy) $qryStr .= '&recordenteredby='.$qEnteredBy;
			if($qObserverUid) $qryStr .= '&observeruid='.$qObserverUid;
			if($qDateLastModified) $qryStr .= '&datelastmodified='.$qDateLastModified;
			if($qryStr){
				?>
				<div style="float:right;margin-top:10px;" title="Go to Label Printing Module">
					<a href="../datasets/index.php?collid=<?php echo $collId.$qryStr; ?>">
						<img src="../../images/list.png" style="width:15px;" />
					</a>
				</div>
				<?php 
			}
			?>
			<div style="margin:5px;">
				<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
				<input type="hidden" name="occid" value="" />
				<input type="hidden" name="occindex" value="0" />
				<input type="hidden" name="autoprocessingstatus" value="<?php echo (isset($autoPStatus)?$autoPStatus:''); ?>" />
				<input type="button" name="submitaction" value="Display Editor" onclick="submitQueryEditor(this.form)" />
				<input type="button" name="submitaction" value="Display Table" onclick="submitQueryTable(this.form)" />
				<span style="margin-left:10px;">
					<input type="button" name="reset" value="Reset Form" onclick="resetQueryForm(this.form)" /> 
				</span>
			</div>
		</fieldset>
	</form>
</div>
