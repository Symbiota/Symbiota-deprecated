<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/ImageProcessor.php');
include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/imageprocessor.'.$LANG_TAG.'.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprid = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
$fileName = array_key_exists('filename',$_REQUEST)?$_REQUEST['filename']:'';

$specManager = new SpecProcessorManager();
$specManager->setCollId($collid);

$editable = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$editable = true;
}

if($spprid) $specManager->setProjVariables($spprid);
?>
<html>
	<head>
		<title><?php echo $LANG['A'];?></title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<style type="text/css">.profileDiv{ clear:both; margin:2px 0px } </style>
		<link href="../../js/jquery-ui-1.12.1/jquery-ui.css" type="text/css" rel="Stylesheet" />	
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script>
			$(function() {
				var dialogArr = new Array("speckeypattern","patternreplace","replacestr","sourcepath","targetpath","imgurl","webpixwidth","tnpixwidth","lgpixwidth","jpgcompression");
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

			function uploadTypeChanged(){
				var uploadType = document.getElementById('projecttype').value;
				if(uploadType == 'local'){
					$("div.profileDiv").show();
					$("#sourcePathInfoIplant").hide();
					$("#chooseFileDiv").hide();
					if($("[name='sourcepath']").val() == "-- Use Default Path --") $("[name='sourcepath']").val("");
					$("#profileEditSubmit").val("Save Profile");
					$("#submitDiv").show();
				}
				else if(uploadType == 'file'){
					$("div.profileDiv").hide();
					$("#chooseFileDiv").show();
					$("#profileEditSubmit").val("Analyze Image Data File");
					$("#submitDiv").show();
				}
				else if(uploadType == 'idigbio'){
					$("div.profileDiv").hide();
					$("#specKeyPatternDiv").show();
					$("#patternReplaceDiv").show();
					$("#replaceStrDiv").show();
					if($("[name='sourcepath']").val() == "-- Use Default Path --") $("[name='sourcepath']").val("");
					$("#profileEditSubmit").val("Save Profile");
					$("#submitDiv").show();
				}
				else if(uploadType == 'iplant'){
					$("div.profileDiv").hide();
					$("#specKeyPatternDiv").show();
					$("#patternReplaceDiv").show();
					$("#replaceStrDiv").show();
					$("#sourcePathDiv").show();
					$("#sourcePathInfoIplant").show();
					if($("[name='sourcepath']").val() == "") $("[name='sourcepath']").val("-- Use Default Path --");
					$("#profileEditSubmit").val("Save Profile");
					$("#submitDiv").show();
				}
				else{
					$("div.profileDiv").hide();
				}
			}

			function validateProjectForm(f){
				if(f.projecttype.value == ""){
					alert("Image Mapping/Import type must be selected");
					return false;
				}
				if(f.projecttype.value != 'file'){
					if(f.speckeypattern.value == ""){
						alert("Pattern matching term must have a value");
						return false;
					}
					if(f.speckeypattern.value.indexOf("(") < 0 || f.speckeypattern.value.indexOf(")") < 0){
						alert("Catalog portion of pattern matching term must be enclosed in parenthesis");
						return false;
					}
				}
				if(f.projecttype.value == 'file' && f.uploadfile.value == ""){
					alert("Select a CSV file to upload");
					return false;
				}
				if(f.projecttype.value == 'local'){
					if(!isNumeric(f.webpixwidth.value)){
						alert("Central image pixel width can only be a numeric value");
						return false;
					}
					else if(!isNumeric(f.tnpixwidth.value)){
						alert("Thumbnail pixel width can only be a numeric value");
						return false;
					}
					else if(!isNumeric(f.lgpixwidth.value)){
						alert("Large image pixel width can only be a numeric value");
						return false;
					}
					else if(f.title.value == ""){
						alert("Title cannot be empty");
						return false;
					}
					else if(!isNumeric(f.jpgcompression.value) || f.jpgcompression.value < 30 || f.jpgcompression.value > 100){
						alert("JPG compression needs to be a numeric value between 30 and 100");
						return false;
					}
					else if(f.sourcepath.value == ""){
						alert("Image source path must have a value");
						return false;
					}
					else if(f.imgurl.value == ""){
						alert("Image URL base must have a value");
						return false;
					}
				}
				if(f.patternreplace.value == "-- Optional --") f.patternreplace.value = "";
				if(f.replacestr.value == "-- Optional --") f.replacestr.value = "";
				if(f.sourcepath.value == "-- Use Default Path --") f.sourcepath.value = "";
				return true;
			}
			
			function validateProcForm(f){
				if(f.projtype.value == 'idigbio'){
					if(!document.getElementById("idigbiofile").files[0]){
						alert("Select the output file from the iDigBio Image Appliance that will be uploaded into the system");
						return false;
					}
				}
				else if(f.projtype.value == 'iplant'){
					var regexObj = /^\d{4}-\d{2}-\d{2}$/;
					var startDate = f.startdate.value;
					if(startDate != "" && !regexObj.test(startDate)){
						alert("Processing Start Date needs to be in the format YYYY-MM-DD (e.g. 2015-10-18)");
						return false;
					}
				}
				if($("[name='matchcatalognumber']").prop("checked") == false && $("[name='matchothercatalognumbers']").prop("checked") == false){
					alert("At least one of the Match Term checkboxes need to be checked");
					return false;
				}
				return true;
			}

			function validateFileUploadForm(f){
				var sfArr = [];
				var tfArr = [];
				for(var i=0;i<f.length;i++){
					var obj = f.elements[i];
					if(obj.name.indexOf("tf[") == 0){
						if(tfArr.indexOf(obj.value) > -1){
							alert("ERROR: Target field names must be unique (duplicate field: "+obj.value+")");
							return false;
						}
						tfArr[tfArr.length] = obj.value;
					}
					if(obj.name.indexOf("sf[") == 0){
						if(sfArr.indexOf(obj.value) > -1){
							alert("ERROR: Source field names must be unique (duplicate field: "+obj.value+")");
							return false;
						}
						sfArr[sfArr.length] = obj.value;
					}
				}
				if(tfArr.indexOf("catalognumber") < 0 || tfArr.indexOf("originalurl") < 0){
					alert("Catalog Number and Large Image URL must both be mapped to an incoming field");
					return false;
				}
				return true;
			}
		</script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext" style="background-color:white;">
			<div style="padding:15px;">
				<?php echo $LANG['B'];?> 
				<b><a href="http://symbiota.org/docs/batch-loading-specimen-images-2/" target="_blank"></b> <?php echo $LANG['C'];?></a></b> </b> <?php echo $LANG['A12'];?>   
			</div>
			<?php 
			if($SYMB_UID){
				if($collid){
					if($fileName){
						?>
						<form name="filemappingform" action="processor.php" method="post" onsubmit="return validateFileUploadForm(this)">
							<fieldset>
								<legend><b><?php echo $LANG['E'];?></b></legend>
								<div style="margin:15px;">
									<table class="styledtable" style="width:600px;font-family:Arial;font-size:12px;">
										<tr><th><?php echo $LANG['G'];?></th><th><?php echo $LANG['F'];?></th></tr>
										<?php 
										$imgProcessor = new ImageProcessor();
										$imgProcessor->echoFileMapping($fileName);
										?>
									</table>
								</div>
								<div style="margin:15px;">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" /> 
									<input name="tabindex" type="hidden" value="1" />
									<input name="filename" type="hidden" value="<?php echo $fileName; ?>" />
									<input name="submitaction" type="submit" value="Load Image Data" />
								</div>
							</fieldset>
						</form>
						<?php
					}
					else{
						if(!$spprid){
							$specProjects = $specManager->getProjects();
							if($specProjects){
								?>
								<form name="sppridform" action="index.php" method="post">
									<fieldset>
										<legend><b><?php echo $LANG['H'];?></b></legend>
										<div style="margin:15px;">
											<?php 
											foreach($specProjects as $id => $projTitle){
												echo '<input type="radio" name="spprid" value="'.$id.'" onchange="this.form.submit()" /> '.$projTitle.'<br/>';
											}
											?>
										</div>
										<div style="margin:15px;">
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" /> 
											<input name="tabindex" type="hidden" value="1" />
										</div>
									</fieldset>
								</form>
								<?php 
							}
						}

						$projectType = $specManager->getProjectType();
						?>
						<div id="editdiv" style="display:<?php echo ($spprid?'none':'block'); ?>;position:relative;">
							<form name="editproj" action="index.php" enctype="multipart/form-data" method="post" onsubmit="return validateProjectForm(this);">
								<fieldset style="padding:15px">
									<legend><b><?php echo ($spprid?'Edit':'New'); ?> <?php echo $LANG['I'];?></b></legend>
									<?php
									if($spprid){
										?>
										<div style="position:absolute;top:10px;right:10px;" onclick="toggle('editdiv');toggle('imgprocessdiv')" title="Close Editor">
											<img src="../../images/edit.png" style="border:0px" />
										</div>
										<input name="projecttype" type="hidden" value="<?php echo $projectType; ?>" />
										<?php
									}
									else{
										?>
										<div>
											<div style="width:180px;float:left;">
												<b><?php echo $LANG['J'];?></b>
											</div>
											<div style="float:left;">
												<select name="projecttype" id="projecttype" style="width:300px;" onchange="uploadTypeChanged()" <?php echo ($spprid?'DISABLED':'');?>>
													<option value="">----------------------</option>
													<option value="local"><?php echo $LANG['K'];?></option>
													<option value="file"><?php echo $LANG['L'];?></option>
													<option value="idigbio"><?php echo $LANG['M'];?></option>
													<option value="iplant"><?php echo $LANG['N'];?></option>
												</select>
											</div>
										</div>
										<?php 
									}
									?>
									<div id="titleDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['O'];?></b>
										</div>
										<div style="float:left;">
											<input name="title" type="text" style="width:300px;" value="<?php echo $specManager->getTitle(); ?>" />
										</div>
									</div>
									<div id="specKeyPatternDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['P'];?></b> 
										</div>
										<div style="float:left;">
											<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $specManager->getSpecKeyPattern(); ?>" />
											<a id="speckeypatterninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="speckeypatterninfodialog">
												<?php echo $LANG['Q'];?>
												Google &quot;Regular Expression PHP Tutorial&quot;
											</div>
										</div>
									</div>
									<div id="patternReplaceDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['R'];?></b> 
										</div>
										<div style="float:left;">
											<input name="patternreplace" type="text" style="width:300px;" value="<?php echo ($specManager->getPatternReplace()?$specManager->getPatternReplace():'-- Optional --'); ?>" />
											<a id="patternreplaceinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="patternreplaceinfodialog">
												<?php echo $LANG['S'];?>'/^/'<?php echo $LANG['G'];?>'barcode-'<?php echo $LANG['I_1'];?> '/XYZ-/'<?php echo $LANG['J_1'];?>
											</div>
										</div>
									</div>
									<div id="replaceStrDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['T'];?></b> 
										</div>
										<div style="float:left;">
											<input name="replacestr" type="text" style="width:300px;" value="<?php echo ($specManager->getReplaceStr()?$specManager->getReplaceStr():'-- Optional --'); ?>" />
											<a id="replacestrinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="replacestrinfodialog">
												<?php echo $LANG['U'];?>
											</div>
										</div>
									</div>
									<div id="sourcePathDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'||$projectType=='iplant'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['V'];?></b>
										</div>
										<div style="float:left;"> 
											<input name="sourcepath" type="text" style="width:400px;" value="<?php echo $specManager->getSourcePath(); ?>" />
											<a id="sourcepathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="sourcepathinfodialog">
												<div id="sourcePathInfoIplant" class="profileDiv" style="display:<?php echo ($projectType == 'iplant'?'block':'none'); ?>">
													<?php echo $LANG['W'];?> 
													<?php
													echo (isset($IPLANT_IMAGE_IMPORT_PATH)?$IPLANT_IMAGE_IMPORT_PATH:'Not Activated');
													?>
												</div>
												<div id="sourcePathInfoOther" class="profileDiv" style="display:<?php echo ($projectType == 'iplant'?'none':'block'); ?>">
													<?php echo $LANG['X'];?>
												</div>
											</div>
										</div>
									</div>
									<div id="targetPathDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['Y'];?></b>
										</div>
										<div style="float:left;"> 
											<input name="targetpath" type="text" style="width:400px;" value="<?php echo ($specManager->getTargetPath()?$specManager->getTargetPath():$IMAGE_ROOT_PATH); ?>" />
											<a id="targetpathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="targetpathinfodialog">
												<?php echo $LANG['Z'];?>
											</div>
										</div>
									</div>
									<div id="urlBaseDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b>Image URL base:</b>
										</div>
										<div style="float:left;"> 
											<input name="imgurl" type="text" style="width:400px;" value="<?php echo ($specManager->getImgUrlBase()?$specManager->getImgUrlBase():$IMAGE_ROOT_URL); ?>" />
											<a id="imgurlinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="imgurlinfodialog">
												Image URL prefix that will access the target folder from the browser.
												This will be used to create the image URLs that will be stored in the database.
												If absolute URL is supplied without the domain name, the portal domain will be assumed. 
												If this field is left blank, the portal's default image url will be used ($imageRootUrl).
											</div>
										</div>
									</div>
									<div id="centralWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['A1'];?></b>
										</div>
										<div style="float:left;"> 
											<input name="webpixwidth" type="text" style="width:50px;" value="<?php echo ($specManager->getWebPixWidth()?$specManager->getWebPixWidth():$IMG_WEB_WIDTH); ?>" /> 
											<a id="webpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="webpixwidthinfodialog">
												<?php echo $LANG['A2'];?> 
											</div>
										</div>
									</div>
									<div id="thumbWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['A3'];?></b> 
										</div>
										<div style="float:left;">
											<input name="tnpixwidth" type="text" style="width:50px;" value="<?php echo ($specManager->getTnPixWidth()?$specManager->getTnPixWidth():$IMG_TN_WIDTH); ?>" /> 
											<a id="tnpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="tnpixwidthinfodialog">
												<?php echo $LANG['A4'];?> 
											</div>
										</div>
									</div>
									<div id="largeWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['A5'];?></b>
										</div>
										<div style="float:left;"> 
											<input name="lgpixwidth" type="text" style="width:50px;" value="<?php echo ($specManager->getLgPixWidth()?$specManager->getLgPixWidth():$IMG_LG_WIDTH); ?>" /> 
											<a id="lgpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="lgpixwidthinfodialog">
												<?php echo $LANG['A6'];?>. 
											</div>
										</div>
									</div>
									<div id="jpgQualityDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div style="width:180px;float:left;">
											<b><?php echo $LANG['A7'];?></b>
										</div>
										<div style="float:left;"> 
											<input name="jpgcompression" type="text" style="width:50px;" value="<?php echo $specManager->getJpgQuality(); ?>" />
											<a id="jpgcompressioninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="jpgcompressioninfodialog">
												<?php echo $LANG['A8'];?> 
											</div>
										</div>
									</div>
									<div id="thumbnailDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div>
											<b><?php echo $LANG['A9'];?></b>
											<div style="margin:5px 15px;">
												<input name="createtnimg" type="radio" value="1" <?php echo ($specManager->getCreateTnImg()==1?'CHECKED':''); ?> /> <?php echo $LANG['A10'];?><br/>
												<input name="createtnimg" type="radio" value="2" <?php echo ($specManager->getCreateTnImg()==2?'CHECKED':''); ?> /> <?php echo $LANG['B1'];?><br/>
												<input name="createtnimg" type="radio" value="3" <?php echo ($specManager->getCreateTnImg()==3?'CHECKED':''); ?> /> <?php echo $LANG['B2'];?><br/>
												<input name="createtnimg" type="radio" value="0" <?php echo (!$specManager->getCreateTnImg()?'CHECKED':''); ?> /> Exclude thumbnail <br/>
											</div>
										</div>
									</div>
									<div id="largeImageDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div>
											<b><?php echo $LANG['B3'];?></b>
											<div style="margin:5px 15px;">
												<input name="createlgimg" type="radio" value="1" <?php echo ($specManager->getCreateLgImg()==1?'CHECKED':''); ?> /> <?php echo $LANG['B4'];?><br/>
												<input name="createlgimg" type="radio" value="2" <?php echo ($specManager->getCreateLgImg()==2?'CHECKED':''); ?> /> <?php echo $LANG['B5'];?><br/>
												<input name="createlgimg" type="radio" value="3" <?php echo ($specManager->getCreateLgImg()==3?'CHECKED':''); ?> /> <?php echo $LANG['B6'];?><br/>
												<input name="createlgimg" type="radio" value="4" <?php echo ($specManager->getCreateLgImg()==4?'CHECKED':''); ?> /> <?php echo $LANG['B7'];?><br/>
												<input name="createlgimg" type="radio" value="0" <?php echo (!$specManager->getCreateLgImg()?'CHECKED':''); ?> /> <?php echo $LANG['B8'];?><br/>
											</div>
										</div>
									</div>
									<div id="chooseFileDiv" class="profileDiv" style="clear:both;padding:15px 0px;display:none">
										<b><?php echo $LANG['B9'];?></b>
										<div style="margin:5px 15px;">
											<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
											<input name='uploadfile' type='file' size='70' value="Choose File" />
										</div>
									</div>
									<div id="submitDiv" class="profileDiv" style="clear:both;padding:25px 15px;display:<?php echo ($projectType?'block':'none'); ?>">
										<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" /> 
										<input name="tabindex" type="hidden" value="1" />
										<input id="profileEditSubmit" name="submitaction" type="submit" value="Save Profile" />
									</div>
								</fieldset>
							</form>
							<?php 
							if($spprid){
								?>
								<form id="delform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this image processing profile?')" >
									<fieldset style="padding:25px">
										<legend><b><?php echo $LANG['B10'];?></b></legend>
										<div>
											<input name="sppriddel" type="hidden" value="<?php echo $spprid; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" /> 
											<input name="tabindex" type="hidden" value="1" />
											<input name="submitaction" type="submit" value="Delete Profile" />
										</div>
									</fieldset>
								</form>
								<?php 
							}
							?>
						</div>
						<?php 
						if($spprid){
							?>
							<div id="imgprocessdiv" style="position:relative;">
								<form name="imgprocessform" action="processor.php" method="post" enctype="multipart/form-data" onsubmit="return validateProcForm(this);">
									<fieldset style="padding:15px;">
										<legend><b><?php echo $specManager->getTitle(); ?></b></legend>
										<div style="position:absolute;top:10px;right:35px;" title="Show all saved profiles or add a new one...">
											<a href="index.php?tabindex=1&collid=<?php echo $collid; ?>"><img src="../../images/add.png" style="border:0px" /></a>
										</div>
										<div style="position:absolute;top:10px;right:10px;" title="Open Editor">
											<a href="#" onclick="toggle('editdiv');toggle('imgprocessdiv');return false;"><img src="../../images/edit.png" style="border:0px;width:15px;" /></a>
										</div>
										<?php
										if($projectType == 'idigbio'){
											?>
											<div style="font-weight:bold;"><?php echo $LANG['C1'];?></div>
											<div style="" title="Upload output file created by iDigBio Image Upload Appliance here.">
												<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
												<input name='idigbiofile' id='idigbiofile' type='file' size='70' value="Choose image alliance output file" />
											</div>
											<?php
										}
										elseif($projectType == 'iplant'){
											$lastRunDate = ($specManager->getLastRunDate()?$specManager->getLastRunDate():'no run date');
											?>
											<div style="margin-top:10px">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['C2'];?></b> 
												</div>
												<div style="float:left;"> 
													<?php echo $lastRunDate; ?>
												</div>
											</div>
											<div style="margin-top:10px;clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['C3'];?></b> 
												</div>
												<div style="float:left;"> 
													<input name="startdate" type="text" value="<?php echo $lastRunDate; ?>" />
												</div>
											</div>
											<?php 
										}
										?>
										<div style="margin-top:10px;clear:both;">
											<div style="width:200px;float:left;">
												<b><?php echo $LANG['C4'];?></b> 
											</div>
											<div style="float:left;"> 
												<?php echo $specManager->getSpecKeyPattern(); ?>
												<input type='hidden' name='speckeypattern' value='<?php echo $specManager->getSpecKeyPattern();?>' />
											</div>
										</div>
										<div style="clear:both;">
											<div style="width:200px;float:left;">
												<b><?php echo $LANG['C5'];?></b> 
											</div>
											<div style="float:left;">
												<input name="matchcatalognumber" type="checkbox" value="1" checked /> <?php echo $LANG['C6'];?> 
												<input name="matchothercatalognumbers" type="checkbox" value="1" style="margin-left:30px;" /> <?php echo $LANG['C7'];?>
											</div>
										</div>
										<div style="margin-top:10px;clear:both;">
											<div style="width:200px;float:left;">
												<b><?php echo $LANG['C8'];?></b> 
											</div>
											<div style="float:left;"> 
												<?php echo $specManager->getPatternReplace(); ?>
												<input type='hidden' name='patternreplace' value='<?php echo $specManager->getPatternReplace();?>' />
											</div>
										</div>
										<div style="margin-top:10px;clear:both;">
											<div style="width:200px;float:left;">
												<b><?php echo $LANG['C9'];?></b> 
											</div>
											<div style="float:left;"> 
												<?php 
												echo str_replace(' ', '&lt;space&gt;', $specManager->getReplaceStr());
												?>
												<input type='hidden' name='replacestr' value='<?php echo $specManager->getReplaceStr(); ?>' />
											</div>
										</div>
										<?php
										if($projectType != 'idigbio'){ 
											?>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D1'];?></b>
												</div>
												<div style="float:left;"> 
													<?php 
													echo '<input name="sourcepath" type="hidden" value="'.$specManager->getSourcePathDefault().'" />';
													echo $specManager->getSourcePathDefault();
													?>
												</div>
											</div>
											<?php
										}
										if($projectType != 'idigbio' && $projectType != 'iplant'){ 
											?>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D2'];?></b> 
												</div>
												<div style="float:left;"> 
													<?php echo ($specManager->getTargetPath()?$specManager->getTargetPath():$IMAGE_ROOT_PATH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D3'];?></b> 
												</div>
												<div style="float:left;"> 
													<?php echo ($specManager->getImgUrlBase()?$specManager->getImgUrlBase():$IMAGE_ROOT_URL); ?>
												</div>
											</div>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D4'];?></b> 
												</div>
												<div style="float:left;"> 
													<?php echo ($specManager->getWebPixWidth()?$specManager->getWebPixWidth():$IMG_WEB_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D5'];?></b> 
												</div>
												<div style="float:left;"> 
													<?php echo ($specManager->getTnPixWidth()?$specManager->getTnPixWidth():$IMG_TN_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D6'];?></b> 
												</div>
												<div style="float:left;"> 
													<?php echo ($specManager->getLgPixWidth()?$specManager->getLgPixWidth():$IMG_LG_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<div style="width:200px;float:left;">
													<b><?php echo $LANG['D7'];?> </b> 
												</div>
												<div style="float:left;"> 
													<?php echo ($specManager->getJpgQuality()?$specManager->getJpgQuality():80); ?>
												</div>
											</div>
											<div style="clear:both;padding-top:10px;">
												<div>
													<b><?php echo $LANG['D8'];?></b>
													<div style="margin:5px 15px"> 
														<input name="webimg" type="radio" value="1" CHECKED /> <?php echo $LANG['D9'];?><br/>
														<input name="webimg" type="radio" value="2" /> <?php echo $LANG['E1'];?><br/>
														<input name="webimg" type="radio" value="3" /> <?php echo $LANG['E2'];?><br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b><?php echo $LANG['E3'];?></b>
													<div style="margin:5px 15px"> 
														<input name="createtnimg" type="radio" value="1" <?php echo ($specManager->getCreateTnImg() == 1?'CHECKED':'') ?> /> <?php echo $LANG['E4'];?><br/>
														<input name="createtnimg" type="radio" value="2" <?php echo ($specManager->getCreateTnImg() == 2?'CHECKED':'') ?> /> <?php echo $LANG['E5'];?><br/>
														<input name="createtnimg" type="radio" value="3" <?php echo ($specManager->getCreateTnImg() == 3?'CHECKED':'') ?> /> <?php echo $LANG['E6'];?><br/>
														<input name="createtnimg" type="radio" value="0" <?php echo (!$specManager->getCreateTnImg()?'CHECKED':'') ?> /> <?php echo $LANG['E7'];?><br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b><?php echo $LANG['E8'];?></b>
													<div style="margin:5px 15px"> 
														<input name="createlgimg" type="radio" value="1" <?php echo ($specManager->getCreateLgImg() == 1?'CHECKED':'') ?> /> <?php echo $LANG['E9'];?><br/>
														<input name="createlgimg" type="radio" value="2" <?php echo ($specManager->getCreateLgImg() == 2?'CHECKED':'') ?> /> <?php echo $LANG['F1'];?><br/>
														<input name="createlgimg" type="radio" value="3" <?php echo ($specManager->getCreateLgImg() == 3?'CHECKED':'') ?> /> <?php echo $LANG['F2'];?><br/>
														<input name="createlgimg" type="radio" value="4" <?php echo ($specManager->getCreateLgImg() == 4?'CHECKED':'') ?> /> <?php echo $LANG['F3'];?><br/>
														<input name="createlgimg" type="radio" value="0" <?php echo (!$specManager->getCreateLgImg()?'CHECKED':'') ?> /> <?php echo $LANG['F4'];?><br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div title="Unable to match primary identifer with an existing database record">
													<b><?php echo $LANG['F5'];?></b> 
													<div style="margin:5px 15px"> 
														<input type="radio" name="createnewrec" value="0" /> 
														<?php echo $LANG['F6'];?><br/>
														<input type="radio" name="createnewrec" value="1" CHECKED /> 
														<?php echo $LANG['F7'];?>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div title="Image with exact same name already exists">
													<b><?php echo $LANG['F8'];?></b>
													<div style="margin:5px 15px"> 
														<input type="radio" name="imgexists" value="0" CHECKED /> 
														<?php echo $LANG['F9'];?><br/>
														<input type="radio" name="imgexists" value="1" /> 
														<?php echo $LANG['F10'];?><br/>
														<input type="radio" name="imgexists" value="2" /> 
														<?php echo $LANG['F11'];?>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b><?php echo $LANG['F12'];?></b>
													<div style="margin:5px 15px"> 
														<input type="radio" name="skeletalFileProcessing" value="0" CHECKED /> 
														<?php echo $LANG['F13'];?><br/>
														<input type="radio" name="skeletalFileProcessing" value="1" /> 
														<?php echo $LANG['F14'];?><br/>
													</div>
												</div>
											</div>
											<?php
										} 
										?>
										<div style="clear:both;padding:20px;">
											<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="projtype" type="hidden" value="<?php echo $projectType; ?>" />
											<input name="tabindex" type="hidden" value="1" />
											<input name="submitaction" type="submit" value="Process <?php echo ($projectType=='idigbio'?'Output File':'Images') ?>" />
										</div>
										<div style="margin:20px;">
											<fieldset style="padding:15px;">
												<legend><b><?php echo $LANG['F15'];?></b></legend>
												<?php 
												$logArr = $specManager->getLogListing();
												$logPath = '../../content/logs/'.($projectType == 'local'?'imgProccessing':$projectType).'/';
												if($logArr){
													foreach($logArr as $logFile){
														echo '<div><a href="'.$logPath.$logFile.'" target="_blank">'.$logFile.'</a></div>';
													}
												}
												else{
													echo '<div>No logs exist for this collection</div>';
												}
												?>
											</fieldset>
										</div>
									</fieldset>
								</form>
							</div>
							<?php
						}
					}
				}
				else{
					echo '<div>ERROR: collection identifier not defined. Contact administrator</div>';
				}
			}
			?>
		</div>
	</body>
</html>