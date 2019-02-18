<?php
if(!$displayQuery && array_key_exists('displayquery',$_REQUEST)) $displayQuery = $_REQUEST['displayquery'];

$qCatalogNumber=''; $qOtherCatalogNumbers=''; 
$qRecordedBy=''; $qRecordNumber=''; $qEventDate=''; 
$qRecordEnteredBy=''; $qObserverUid='';$qDateLastModified='';$qDateEntered='';
$qProcessingStatus='';$qOrderBy='';$qOrderByDir='';
$qImgOnly='';$qWithoutImg='';$qExsiccatiId='';
$qCustomOpenParen1='';$qCustomField1='';$qCustomType1='';$qCustomValue1='';$qCustomCloseParen1='';
$qCustomAndOr2='';$qCustomOpenParen2='';$qCustomField2='';$qCustomType2='';$qCustomValue2='';$qCustomCloseParen2='';
$qCustomAndOr3='';$qCustomOpenParen3='';$qCustomField3='';$qCustomType3='';$qCustomValue3='';$qCustomCloseParen3='';
$qCustomAndOr4='';$qCustomOpenParen4='';$qCustomField4='';$qCustomType4='';$qCustomValue4='';$qCustomCloseParen4='';
$qCustomAndOr5='';$qCustomOpenParen5='';$qCustomField5='';$qCustomType5='';$qCustomValue5='';$qCustomCloseParen5='';
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
	$qExsiccatiId = (array_key_exists('exsid',$qryArr)?$qryArr['exsid']:'');
	$qImgOnly = (array_key_exists('io',$qryArr)?$qryArr['io']:0);
	$qWithoutImg = (array_key_exists('woi',$qryArr)?$qryArr['woi']:0);
    $qCustomOpenParen1 = (array_key_exists('cop1',$qryArr)?$qryArr['cop1']:'');
    $qCustomField1 = (array_key_exists('cf1',$qryArr)?$qryArr['cf1']:'');
	$qCustomType1 = (array_key_exists('ct1',$qryArr)?$qryArr['ct1']:'');
	$qCustomValue1 = (array_key_exists('cv1',$qryArr)?htmlentities($qryArr['cv1']):'');
    $qCustomCloseParen1 = (array_key_exists('ccp1',$qryArr)?$qryArr['ccp1']:'');
    $qCustomAndOr2 = (array_key_exists('cao2',$qryArr)?$qryArr['cao2']:'');
    $qCustomOpenParen2 = (array_key_exists('cop2',$qryArr)?$qryArr['cop2']:'');
	$qCustomField2 = (array_key_exists('cf2',$qryArr)?$qryArr['cf2']:'');
	$qCustomType2 = (array_key_exists('ct2',$qryArr)?$qryArr['ct2']:'');
	$qCustomValue2 = (array_key_exists('cv2',$qryArr)?htmlentities($qryArr['cv2']):'');
    $qCustomCloseParen2 = (array_key_exists('ccp2',$qryArr)?$qryArr['ccp2']:'');
    $qCustomAndOr3 = (array_key_exists('cao3',$qryArr)?$qryArr['cao3']:'');
    $qCustomOpenParen3 = (array_key_exists('cop3',$qryArr)?$qryArr['cop3']:'');
	$qCustomField3 = (array_key_exists('cf3',$qryArr)?$qryArr['cf3']:'');
	$qCustomType3 = (array_key_exists('ct3',$qryArr)?$qryArr['ct3']:'');
	$qCustomValue3 = (array_key_exists('cv3',$qryArr)?htmlentities($qryArr['cv3']):'');
    $qCustomCloseParen3 = (array_key_exists('ccp3',$qryArr)?$qryArr['ccp3']:'');
    $qCustomAndOr4 = (array_key_exists('cao4',$qryArr)?$qryArr['cao4']:'');
    $qCustomOpenParen4 = (array_key_exists('cop4',$qryArr)?$qryArr['cop4']:'');
    $qCustomField4 = (array_key_exists('cf4',$qryArr)?$qryArr['cf4']:'');
    $qCustomType4 = (array_key_exists('ct4',$qryArr)?$qryArr['ct4']:'');
    $qCustomValue4 = (array_key_exists('cv4',$qryArr)?htmlentities($qryArr['cv4']):'');
    $qCustomCloseParen4 = (array_key_exists('ccp4',$qryArr)?$qryArr['ccp4']:'');
    $qCustomAndOr5 = (array_key_exists('cao5',$qryArr)?$qryArr['cao5']:'');
    $qCustomOpenParen5 = (array_key_exists('cop5',$qryArr)?$qryArr['cop5']:'');
    $qCustomField5 = (array_key_exists('cf5',$qryArr)?$qryArr['cf5']:'');
    $qCustomType5 = (array_key_exists('ct5',$qryArr)?$qryArr['ct5']:'');
    $qCustomValue5 = (array_key_exists('cv5',$qryArr)?htmlentities($qryArr['cv5']):'');
    $qCustomCloseParen5 = (array_key_exists('ccp5',$qryArr)?$qryArr['ccp5']:'');
    $qOcrFrag = (array_key_exists('ocr',$qryArr)?htmlentities($qryArr['ocr']):'');
	$qOrderBy = (array_key_exists('orderby',$qryArr)?$qryArr['orderby']:'');
	$qOrderByDir = (array_key_exists('orderbydir',$qryArr)?$qryArr['orderbydir']:'');
}

//Set processing status  
$processingStatusArr = array();
if(isset($PROCESSINGSTATUS) && $PROCESSINGSTATUS){
	$processingStatusArr = $PROCESSINGSTATUS;
}
else{
	$processingStatusArr = array('unprocessed','unprocessed/NLP','stage 1','stage 2','stage 3','pending review-nfn','pending review','expert required','reviewed','closed');
}
?>
<div id="querydiv" style="clear:both;width:830px;display:<?php echo ($displayQuery?'block':'none'); ?>;">
	<form name="queryform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return verifyQueryForm(this)">
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
						if($qProcessingStatus && $qProcessingStatus != 'isnull' && !in_array($qProcessingStatus,$processingStatusArr)){
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
				</div>
				<?php
				if($ACTIVATE_EXSICCATI){
					if($exsList = $occManager->getExsiccatiList()){
						?>
						<div style="margin:2px;" title="Enter Exsiccati Title">
							<b>Exsiccati Title:</b>
							<select name="q_exsiccatiid" style="width:650px">
								<option value=""></option> 
								<option value="">-------------------------</option> 
								<?php 
								foreach($exsList as $exsID => $exsTitle){
									echo '<option value="'.$exsID.'" '.($qExsiccatiId==$exsID?'SELECTED':'').'>'.$exsTitle.'</option>';
								}
								?>
							</select>
						</div>
						<?php
					}
				}
			}
			$advFieldArr = array();
			if($crowdSourceMode){
				$advFieldArr = array('family'=>'Family','sciname'=>'Scientific Name','othercatalognumbers'=>'Other Catalog Numbers',
					'country'=>'Country','stateProvince'=>'State/Province','county'=>'County','municipality'=>'Municipality',
					'recordedby'=>'Collector','recordnumber'=>'Collector Number','eventdate'=>'Collection Date');
			}
			else{
				$advFieldArr = array('associatedCollectors'=>'Associated Collectors','associatedOccurrences'=>'Associated Occurrences',
					'associatedTaxa'=>'Associated Taxa','attributes'=>'Attributes','scientificNameAuthorship'=>'Author',
					'basisOfRecord'=>'Basis Of Record','behavior'=>'Behavior','catalogNumber'=>'Catalog Number','collectionCode'=>'Collection Code (override)','recordNumber'=>'Collection Number',
					'recordedBy'=>'Collector/Observer','coordinateUncertaintyInMeters'=>'Coordinate Uncertainty (m)','country'=>'Country',
					'county'=>'County','cultivationStatus'=>'Cultivation Status','dataGeneralizations'=>'Data Generalizations','eventDate'=>'Date',
					'dateEntered'=>'Date Entered','dateLastModified'=>'Date Last Modified','`day`'=>'Day','dbpk'=>'dbpk','decimalLatitude'=>'Decimal Latitude',
					'decimalLongitude'=>'Decimal Longitude','maximumDepthInMeters'=>'Depth Maximum (m)','minimumDepthInMeters'=>'Depth Minimum (m)',
					'verbatimAttributes'=>'Description','disposition'=>'Disposition','dynamicProperties'=>'Dynamic Properties',
					'maximumElevationInMeters'=>'Elevation Maximum (m)','minimumElevationInMeters'=>'Elevation Minimum (m)',
					'establishmentMeans'=>'Establishment Means','family'=>'Family','fieldNotes'=>'Field Notes','fieldnumber'=>'Field Number',
					'genus'=>'Genus','geodeticDatum'=>'Geodetic Datum','georeferenceProtocol'=>'Georeference Protocol',
					'georeferenceRemarks'=>'Georeference Remarks','georeferenceSources'=>'Georeference Sources',
					'georeferenceVerificationStatus'=>'Georeference Verification Status','georeferencedBy'=>'Georeferenced By','habitat'=>'Habitat',
					'identificationQualifier'=>'Identification Qualifier','identificationReferences'=>'Identification References',
					'identificationRemarks'=>'Identification Remarks','identifiedBy'=>'Identified By','individualCount'=>'Individual Count',
					'informationWithheld'=>'Information Withheld','institutionCode'=>'Institution Code (override)','labelProject'=>'Label Project',
					'lifeStage'=>'Life Stage','locality'=>'Locality',
					'localitySecurity'=>'Locality Security','localitySecurityReason'=>'Locality Security Reason','locationRemarks'=>'Location Remarks',
					'username'=>'Modified By','`month`'=>'Month','municipality'=>'Municipality','occurrenceRemarks'=>'Notes (Occurrence Remarks)','ocrFragment'=>'OCR Fragment',
					'otherCatalogNumbers'=>'Other Catalog Numbers','ownerInstitutionCode'=>'Owner Code','preparations'=>'Preparations',
					'reproductiveCondition'=>'Reproductive Condition','samplingEffort'=>'Sampling Effort','samplingProtocol'=>'Sampling Protocol',
					'sciname'=>'Scientific Name','sex'=>'Sex','specificEpithet'=>'Specific Epithet','stateProvince'=>'State/Province',
					'substrate'=>'Substrate','taxonRemarks'=>'Taxon Remarks','typeStatus'=>'Type Status','verbatimCoordinates'=>'Verbatim Coordinates',
					'verbatimEventDate'=>'Verbatim Date','verbatimDepth'=>'Verbatim Depth','verbatimElevation'=>'Verbatim Elevation','`year`'=>'Year');
				if($QUICK_HOST_ENTRY_IS_ACTIVE){
                    $insertIndex = array_search('habitat', array_keys($advFieldArr));
                    $advFieldArr = array_merge(array_slice($advFieldArr,0,$insertIndex+1),array('verbatimsciname'=>'Host'),array_slice($advFieldArr,$insertIndex+1,null));
                }
			}
			//sort($advFieldArr);
			?>
			<div style="margin:2px 0px;">
				<b>Custom Field 1:</b>
                <select name="q_customopenparen1" onchange="customSelectChanged(1)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomOpenParen1=='('?'SELECTED':''); ?> value="(">(</option>
                </select>
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
                <select name="q_customcloseparen1" onchange="customSelectChanged(1)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomCloseParen1==')'?'SELECTED':''); ?> value=")">)</option>
                </select>
				<a href="#" onclick="toggleCustomDiv2();return false;">
					<img src="../../images/editplus.png" />
				</a>
			</div>
			<div id="customdiv2" style="margin:2px 0px;display:<?php echo ($qCustomValue2||$qCustomType2=='NULL'||$qCustomType2=='NOTNULL'?'block':'none');?>;">
				<b>Custom Field 2:</b>
                <select name="q_customandor2" onchange="customSelectChanged(2)">
                    <option>AND</option>
                    <option <?php echo ($qCustomAndOr2=='OR'?'SELECTED':''); ?> value="OR">OR</option>
                </select>
                <select name="q_customopenparen2" onchange="customSelectChanged(2)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomOpenParen2=='('?'SELECTED':''); ?> value="(">(</option>
                </select>
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
                <select name="q_customcloseparen2" onchange="customSelectChanged(2)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomCloseParen2==')'?'SELECTED':''); ?> value=")">)</option>
                </select>
				<a href="#" onclick="toggleCustomDiv3();return false;">
					<img src="../../images/editplus.png" />
				</a>
			</div>
			<div id="customdiv3" style="margin:2px 0px;display:<?php echo ($qCustomValue3||$qCustomType3=='NULL'||$qCustomType3=='NOTNULL'?'block':'none');?>;">
				<b>Custom Field 3:</b>
                <select name="q_customandor3" onchange="customSelectChanged(3)">
                    <option>AND</option>
                    <option <?php echo ($qCustomAndOr3=='OR'?'SELECTED':''); ?> value="OR">OR</option>
                </select>
                <select name="q_customopenparen3" onchange="customSelectChanged(3)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomOpenParen3=='('?'SELECTED':''); ?> value="(">(</option>
                </select>
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
                <select name="q_customcloseparen3" onchange="customSelectChanged(3)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomCloseParen3==')'?'SELECTED':''); ?> value=")">)</option>
                </select>
                <a href="#" onclick="toggleCustomDiv4();return false;">
                    <img src="../../images/editplus.png" />
                </a>
			</div>
            <div id="customdiv4" style="margin:2px 0px;display:<?php echo ($qCustomValue4||$qCustomType4=='NULL'||$qCustomType4=='NOTNULL'?'block':'none');?>;">
                <b>Custom Field 4:</b>
                <select name="q_customandor4" onchange="customSelectChanged(4)">
                    <option>AND</option>
                    <option <?php echo ($qCustomAndOr4=='OR'?'SELECTED':''); ?> value="OR">OR</option>
                </select>
                <select name="q_customopenparen4" onchange="customSelectChanged(4)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomOpenParen4=='('?'SELECTED':''); ?> value="(">(</option>
                </select>
                <select name="q_customfield4" onchange="customSelectChanged(4)">
                    <option value="">Select Field Name</option>
                    <option value="">---------------------------------</option>
                    <?php
                    foreach($advFieldArr as $k => $v){
                        echo '<option value="'.$k.'" '.($k==$qCustomField4?'SELECTED':'').'>'.$v.'</option>';
                    }
                    ?>
                </select>
                <select name="q_customtype4">
                    <option>EQUALS</option>
                    <option <?php echo ($qCustomType4=='NOT EQUALS'?'SELECTED':''); ?> value="NOT EQUALS">NOT EQUALS</option>
                    <option <?php echo ($qCustomType4=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
                    <option <?php echo ($qCustomType4=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
                    <option <?php echo ($qCustomType4=='GREATER'?'SELECTED':''); ?> value="GREATER">GREATER THAN</option>
                    <option <?php echo ($qCustomType4=='LESS'?'SELECTED':''); ?> value="LESS">LESS THAN</option>
                    <option <?php echo ($qCustomType4=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
                    <option <?php echo ($qCustomType4=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
                </select>
                <input name="q_customvalue4" type="text" value="<?php echo $qCustomValue4; ?>" style="width:200px;" />
                <select name="q_customcloseparen4" onchange="customSelectChanged(4)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomCloseParen4==')'?'SELECTED':''); ?> value=")">)</option>
                </select>
                <a href="#" onclick="toggleCustomDiv5();return false;">
                    <img src="../../images/editplus.png" />
                </a>
            </div>
            <div id="customdiv5" style="margin:2px 0px;display:<?php echo ($qCustomValue5||$qCustomType5=='NULL'||$qCustomType5=='NOTNULL'?'block':'none');?>;">
                <b>Custom Field 5:</b>
                <select name="q_customandor5" onchange="customSelectChanged(5)">
                    <option>AND</option>
                    <option <?php echo ($qCustomAndOr5=='OR'?'SELECTED':''); ?> value="OR">OR</option>
                </select>
                <select name="q_customopenparen5" onchange="customSelectChanged(5)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomOpenParen5=='('?'SELECTED':''); ?> value="(">(</option>
                </select>
                <select name="q_customfield5" onchange="customSelectChanged(5)">
                    <option value="">Select Field Name</option>
                    <option value="">---------------------------------</option>
                    <?php
                    foreach($advFieldArr as $k => $v){
                        echo '<option value="'.$k.'" '.($k==$qCustomField5?'SELECTED':'').'>'.$v.'</option>';
                    }
                    ?>
                </select>
                <select name="q_customtype5">
                    <option>EQUALS</option>
                    <option <?php echo ($qCustomType5=='NOT EQUALS'?'SELECTED':''); ?> value="NOT EQUALS">NOT EQUALS</option>
                    <option <?php echo ($qCustomType5=='STARTS'?'SELECTED':''); ?> value="STARTS">STARTS WITH</option>
                    <option <?php echo ($qCustomType5=='LIKE'?'SELECTED':''); ?> value="LIKE">CONTAINS</option>
                    <option <?php echo ($qCustomType5=='GREATER'?'SELECTED':''); ?> value="GREATER">GREATER THAN</option>
                    <option <?php echo ($qCustomType5=='LESS'?'SELECTED':''); ?> value="LESS">LESS THAN</option>
                    <option <?php echo ($qCustomType5=='NULL'?'SELECTED':''); ?> value="NULL">IS NULL</option>
                    <option <?php echo ($qCustomType5=='NOTNULL'?'SELECTED':''); ?> value="NOTNULL">IS NOT NULL</option>
                </select>
                <input name="q_customvalue5" type="text" value="<?php echo $qCustomValue5; ?>" style="width:200px;" />
                <select name="q_customcloseparen5" onchange="customSelectChanged(5)">
                    <option value="">--</option>
                    <option <?php echo ($qCustomCloseParen5==')'?'SELECTED':''); ?> value=")">)</option>
                </select>
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
						<option value="">Select Sort Field</option>
                        <option value="catalognumber" <?php echo ($qOrderBy=='catalognumber'?'SELECTED':''); ?>>Catalog Number</option>
						<option value="recordedby" <?php echo ($qOrderBy=='recordedby'?'SELECTED':''); ?>>Collector</option>
                        <option value="country" <?php echo ($qOrderBy=='country'?'SELECTED':''); ?>>Country</option>
                        <option value="county" <?php echo ($qOrderBy=='county'?'SELECTED':''); ?>>County</option>
                        <option value="eventdate" <?php echo ($qOrderBy=='eventdate'?'SELECTED':''); ?>>Date</option>
                        <option value="dateentered" <?php echo ($qOrderBy=='dateentered'?'SELECTED':''); ?>>Date Entered</option>
                        <option value="datelastmodified" <?php echo ($qOrderBy=='datelastmodified'?'SELECTED':''); ?>>Date Last modified</option>
                        <option value="decimallatitude" <?php echo ($qOrderBy=='decimallatitude'?'SELECTED':''); ?>>Decimal Latitude</option>
                        <option value="decimallongitude" <?php echo ($qOrderBy=='decimallongitude'?'SELECTED':''); ?>>Decimal Longitude</option>
                        <option value="maximumelevationinmeters" <?php echo ($qOrderBy=='maximumelevationinmeters'?'SELECTED':''); ?>>Elevation Maximum</option>
                        <option value="minimumelevationinmeters" <?php echo ($qOrderBy=='minimumelevationinmeters'?'SELECTED':''); ?>>Elevation Minimum</option>
                        <option value="recordenteredby" <?php echo ($qOrderBy=='recordenteredby'?'SELECTED':''); ?>>Entered By</option>
                        <option value="family" <?php echo ($qOrderBy=='family'?'SELECTED':''); ?>>Family</option>
                        <option value="locality" <?php echo ($qOrderBy=='locality'?'SELECTED':''); ?>>Locality</option>
                        <option value="municipality" <?php echo ($qOrderBy=='municipality'?'SELECTED':''); ?>>Municipality</option>
						<option value="recordnumber" <?php echo ($qOrderBy=='recordnumber'?'SELECTED':''); ?>>Number</option>
						<option value="processingstatus" <?php echo ($qOrderBy=='processingstatus'?'SELECTED':''); ?>>Processing Status</option>
						<option value="sciname" <?php echo ($qOrderBy=='sciname'?'SELECTED':''); ?>>Scientific Name</option>
						<option value="stateprovince" <?php echo ($qOrderBy=='stateprovince'?'SELECTED':''); ?>>State / Province</option>
					</select>
				</span>
				<span>
					<select name="orderbydir">
						<option value="ASC">Ascending</option>
						<option value="DESC" <?php echo ($qOrderByDir=='DESC'?'SELECTED':''); ?>>Descending</option>
					</select>
				</span>
			</div>
		</fieldset>
	</form>
</div>