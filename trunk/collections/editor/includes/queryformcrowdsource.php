<?php
if(!$displayQuery && array_key_exists('displayquery',$_REQUEST)) $displayQuery = $_REQUEST['displayquery'];

$qIdentifier='';$qProcessingStatus='';$qImgOnly='';$qOcrFrag='';
$qCustomField1='';$qCustomType1='';$qCustomValue1='';
$qryArr = $occManager->getQueryVariables();
if($qryArr){
	$qIdentifier = (array_key_exists('id',$qryArr)?$qryArr['id']:'');
	$qProcessingStatus = (array_key_exists('ps',$qryArr)?$qryArr['ps']:'');
	$qOcrFrag = (array_key_exists('ocr',$qryArr)?$qryArr['ocr']:'');
	$qImgOnly = (array_key_exists('io',$qryArr)?$qryArr['io']:0);
	$qCustomField1 = (array_key_exists('cf1',$qryArr)?$qryArr['cf1']:'');
	$qCustomType1 = (array_key_exists('ct1',$qryArr)?$qryArr['ct1']:'');
	$qCustomValue1 = (array_key_exists('cv1',$qryArr)?$qryArr['cv1']:'');
}
?>
<div id="querydiv" style="clear:both;width:790px;display:<?php echo ($displayQuery?'block':'none'); ?>;">
	<form name="queryform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return verifyQueryForm(this)">
		<fieldset style="padding:5px;">
			<legend><b>Record Search Form</b></legend>
			<div style="margin:2px;">
				Catalog Number: 
				<span title="Separate multiples by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
					<input type="text" name="q_identifier" value="<?php echo $qIdentifier; ?>" style="width:200px;" />
				</span>
				<span style="margin-left:25px;">OCR Fragment:</span> 
				<span title="Search for term embedded within OCR block of text">
					<input type="text" name="q_ocrfrag" value="<?php echo $qOcrFrag; ?>" style="width:200px;" />
				</span>
			</div>
			<?php 
			$advFieldArr = array('family'=>'Family','sciname'=>'Scientific Name','othercatalognumbers'=>'Other Catalog Numbers',
				'country'=>'Country','stateProvince'=>'State/Province','county'=>'County','municipality'=>'Municipality',
				'recordedby'=>'Collector','recordnumber'=>'Collector Number','eventdate'=>'Collection Date');
			//sort($advFieldArr);
			?>
			<div style="margin:2px 0px;">
				Custom Field 1: 
				<select name="q_customfield1">
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
					<option <?php echo ($qCustomType1=='LIKE'?'SELECTED':''); ?>>LIKE</option>
					<option <?php echo ($qCustomType1=='IS NULL'?'SELECTED':''); ?>>IS NULL</option>
				</select>
				<input name="q_customvalue1" type="text" value="<?php echo $qCustomValue1; ?>" style="width:200px;" />
			</div>
			<div style="margin:5px 120px 5px 0px;float:right;">
				<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
				<input type="hidden" name="csmode" value="1" />
				<input type="hidden" name="occid" value="" />
				<input type="hidden" name="occindex" value="0" />
				<input type="hidden" name="autoprocessingstatus" value="<?php echo (isset($autoPStatus)?$autoPStatus:''); ?>" />
				<input type="button" name="submitaction" value="Display Editor" onclick="submitQueryEditor(this.form)" />
				<input type="button" name="submitaction" value="Display Table" onclick="submitQueryTable(this.form)" />
				<span style="margin-left:10px;">
					<input type="button" name="reset" value="Reset Form" onclick="resetQueryForm(this.form)" /> 
				</span>
			</div>
			<div style="margin:5px 0px;">
				<input name="q_imgonly" type="checkbox" value="1" <?php echo ($qImgOnly==1?'checked':''); ?> /> 
				Only occurrences with images
			</div>
		</fieldset>
	</form>
</div>
