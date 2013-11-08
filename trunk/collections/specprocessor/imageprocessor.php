<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprId = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;

$specManager = new SpecProcessorManager();

$specManager->setCollId($collId);
$specManager->setSpprId($spprId);

$editable = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$specProjects = Array();
if(!$spprId && $action != 'addmode'){
	$specProjects = $specManager->getProjects();
	if(count($specProjects) == 1){
		$spprId = array_shift(array_keys($specProjects));
		$specManager->setSpprId($spprId);
	}
}
if($spprId) $specManager->setProjVariables();

?>
<html>
	<head>
		<title>Image Processor</title>
		<link href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script language="javascript">
			$(function() {
				var dialogArr = new Array("speckeypattern","speckeyretrieval","sourcepath","targetpath","imgurl","webpixwidth","tnpixwidth","lgpixwidth","jpgcompression");
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
	
			function validateCollidForm(f){
				if(f.collid.length == null){
					if(f.collid.checked) return true;
				}
				else{
					var radioCnt = f.collid.length;
					for(var counter = 0; counter < radioCnt; counter++){
						if (f.collid[counter].checked) return true; 
					}
				}
				alert("Please select a Collection Project");
				return false;
			}

			function validateSppridForm(f){
				if(f.spprid.length == null){
					if(f.spprid.checked) return true;
				}
				else{
					var radioCnt = f.spprid.length;
					for(var counter = 0; counter < radioCnt; counter++){
						if (f.spprid[counter].checked) return true; 
					}
				}
				alert("Please select a Specimen Processing Project");
				return false;
			}

			function validateProjectForm(f){
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
				else if(!isNumeric(f.jpgcompression.value) || f.jpgcompression.value < 20 || f.jpgcompression.value > 100){
					alert("JPG compression needs to be a numeric value between 20 and 100");
					return false;
				}
				return true;
			}

			function validateDelForm(f){
			}
		</script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php
			if($editable && $collId){ 
				?>
				<div style="float:right;margin:10px;">
					<a href="index.php?tabindex=1&submitaction=addmode&collid=<?php echo $collId; ?>"><img src="../../images/add.png" style="border:0px" /></a>
				</div>
				<?php
			}
			?>
			<div style="padding:15px;">
				This tool is designed to aid collection manager in batch processing specimen images and integrating them into the biodiversity portal.
				Contact portal manager for helping in setting up a batch image uploading workflow. 
				Once an upload profile has been established, the collection manager can use this form to manually trigger image processing.
				For more information, see the Symbiota documentation for 
				<b><a href="http://symbiota.org/tiki/tiki-index.php?page=Batch+Loading+Specimen+Images">recommended practices</a></b> for batch 
				loading images thorugh Symbiota.   
			</div>
			<?php 
			if($SYMB_UID){
				if($collId){
					?>
					<div id="editdiv" style="display:<?php echo ($spprId||$specProjects?'none':'block'); ?>;">
						<form name="editproj" action="index.php" method="post" onsubmit="return validateProjectForm(this);">
							<fieldset>
								<legend><b><?php echo ($spprId?'Edit':'New'); ?> Project</b></legend>
								<table>
									<tr>
										<td>
											<b>Title:</b>
										</td>
										<td>
											<input name="title" type="text" style="width:300px;" value="<?php echo $specManager->getTitle(); ?>" />
										</td>
									</tr>
									<tr>
										<td>
											<b>Regular Expression for<br/>
											Extracting Unique Identifier (PK): </b> 
										</td>
										<td style="padding-top:13px;"> 
											<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $specManager->getSpecKeyPattern(); ?>"/>
											<a id="speckeypatterninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="speckeypatterninfodialog">
												Regular expression (PHP version) needed to extract the unique identifier from source text.
												For example, regular expression /^(WIS-L-\d{7})\D*/ will extract catalog number WIS-L-0001234 
												from image file named WIS-L-0001234_a.jpg. For more information on creating regular expressions,
												Google &quot;Regular Expression PHP Tutorial&quot;
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Retrieve Identifier from:</b> 
										</td>
										<td> 
											<input name="speckeyretrieval" type="radio" value="filename" <?php echo ($specManager->getSpecKeyRetrieval()=='filename' || !$spprId?'checked':''); ?> /> Image File Name
											<a id="speckeyretrievalinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a><br/>
											<input name="speckeyretrieval" type="radio" value="ocr" <?php echo ($specManager->getSpecKeyRetrieval()=='ocr'?'checked':''); ?> /> OCR from image
											<div id="speckeyretrievalinfodialog">
												Obtaining identifier from file name is typically more reliable than using OCR. 
												The Tesseract OCR engine used in this interface can not read barcodes dirrectly.  
												The identifier must be within every image and in clear, typed face that can be OCRed.
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Image source path:</b>
										</td>
										<td> 
											<input name="sourcepath" type="text" style="width:400px;" value="<?php echo $specManager->getSourcePath(); ?>" />
											<a id="sourcepathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="sourcepathinfodialog">
												Server path to folder containing source images. 
												The web server (e.g. apache user) must have read/write access to to this folder.
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Image target path:</b>
										</td>
										<td> 
											<input name="targetpath" type="text" style="width:400px;" value="<?php echo $specManager->getTargetPath(); ?>" />
											<a id="targetpathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="targetpathinfodialog">
												Web server path to where the image derivatives will be depositied. 
												The web server (e.g. apache user) must have read/write access to to this folder.
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Image URL base:</b>
										</td>
										<td> 
											<input name="imgurl" type="text" style="width:400px;" value="<?php echo $specManager->getImgUrlBase(); ?>" />
											<a id="imgurlinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="imgurlinfodialog">
												Image URL prefix that will access the target folder from the browser (generally without the domain).
												This will be used to create the image URLs that will be stored in the database.  
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Central pixel width:</b>
										</td>
										<td> 
											<input name="webpixwidth" type="text" style="width:50px;" value="<?php echo $specManager->getWebPixWidth(); ?>" /> 
											<a id="webpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="webpixwidthinfodialog">
												Width of the standard web image. 
												If the source image is smaller than this width, the file will simply be copied over without resizing. 
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Thumbnail pixel width:</b> 
										</td>
										<td> 
											<input name="tnpixwidth" type="text" style="width:50px;" value="<?php echo $specManager->getTnPixWidth(); ?>" /> 
											<a id="tnpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="tnpixwidthinfodialog">
												Width of the image thumbnail. Width should be greater than image sizing within the thumbnail display pages. 
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Large pixel width:</b>
										</td>
										<td> 
											<input name="lgpixwidth" type="text" style="width:50px;" value="<?php echo $specManager->getLgPixWidth(); ?>" /> 
											<a id="lgpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="lgpixwidthinfodialog">
												Width of the large version of the image. 
												If the source image is smaller than this width, the file will simply be copied over without resizing. 
												Note that resizing large images may be limited by the PHP configuration settings (e.g. memory_limit).
												If this is a problem, having this value greater than the maximum width of your source images will avoid 
												errors related to resampling large images. 
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>JPG compression:</b>
										</td>
										<td> 
											<input name="jpgcompression" type="text" style="width:50px;" value="<?php echo $specManager->getJpgCompression(); ?>" />
											<a id="jpgcompressioninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="jpgcompressioninfodialog">
												
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<b>Create thumbnail:</b>
										</td>
										<td> 
											<input name="createtnimg" type="checkbox" value="1" CHECKED />
										</td>
									</tr>
									<tr>
										<td>
											<b>Create large image:</b>
										</td>
										<td> 
											<input name="createlgimg" type="checkbox" value="1" CHECKED />
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<input name="spprid" type="hidden" value="<?php echo $spprId; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
											<input name="tabindex" type="hidden" value="1" />
											<?php 
											if($spprId){
												echo '<input name="submitaction" type="submit" value="Edit Image Project" />';
											}
											else{
												echo '<input name="submitaction" type="submit" value="Add New Image Project" />';
											}
											?>
										</td>
									</tr>
								</table>
							</fieldset>
						</form>
						<?php 
						if($spprId){
							?>
							<form id="delform" action="index.php" method="post" onsubmit="return validateDelForm(this);" >
								<fieldset>
									<legend><b>Delete Project</b></legend>
									<div>
										<input name="sppriddel" type="hidden" value="<?php echo $spprId; ?>" />
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
										<input name="tabindex" type="hidden" value="1" />
										<input name="submitaction" type="submit" value="Delete Image Project" />
									</div>
								</fieldset>
							</form>
							<?php 
						}
						?>
					</div>
					<?php 
					if($spprId){
						?>
						<div style="">
							<form name="imgprocessform" action="index.php" method="post">
								<fieldset>
									<legend><b>Image Processor</b></legend>
									<div style="float:right;margin:10px;" onclick="toggle('editdiv');">
										<img src="../../images/edit.png" style="border:0px" />
									</div>
									<div style="font-size:120%;font-weight:bold;">
										<?php echo $specManager->getTitle(); ?>
									</div>
									<div style="margin:10px;">
										This process will create web quality versions of the specimen images found within 
										the &#8220;source folder&#8221;, deposit them into the &#8220;target folder&#8221;, 
										and link them to their prespective specimen record. 
									</div>
									<fieldset style="margin:15px;">
										<legend><b>Image Versions</b></legend>
										<div style="margin:0px 0px 10px 10px;">
											Web Versions:<br/>
											<input type="radio" name="mapweb" value="1" CHECKED /> 
											Create basic web images<br/>
											<input type="radio" name="mapweb" value="0" /> 
											Use existing images without resizing or compressing
											<br/><br/> 
											Thumbnails:<br/>
											<input type="radio" name="maptn" value="1" <?php echo ($specManager->getCreateTnImg()?'CHECKED':'') ?> /> 
											Create Thumbnails<br/>
											<input type="radio" name="maptn" value="0" <?php echo ($specManager->getCreateTnImg()?'':'CHECKED') ?> /> 
											Use existing thumbnail (files must end in _tn.jpg), or skip altogether
											<br/><br/> 
											Large Images:<br/>
											<input type="radio" name="maplarge" value="1" <?php echo ($specManager->getCreateLgImg()?'CHECKED':'') ?> />
											Create Large Versions<br/>
											<input type="radio" name="maplarge" value="0" <?php echo ($specManager->getCreateLgImg()?'':'CHECKED') ?> /> 
											Use existing large images (files must end in _lg.jpg), or skip altogether
										</div>
									</fieldset>
									<div style="margin:25px;">
										<b>Action if specimen record is not found:</b> 
										<div style="margin:0px 0px 10px 10px;">
											<input type="radio" name="createnewrec" value="0" /> 
											Leave image and go to next<br/>
											<input type="radio" name="createnewrec" value="1" CHECKED /> 
											Create empty record and link image
										</div>
										<b>Action if image file exists with same name:</b> 
										<div style="margin:0px 0px 10px 10px;">
											<input type="radio" name="copyoverimg" value="0" /> 
											Rename image and save<br/>
											<input type="radio" name="copyoverimg" value="1" CHECKED /> 
											Copy over existing image
										</div>
									</div>
									<fieldset style="margin:15px;">
										<legend><b>Project Variables</b></legend>
										<table>
											<tr>
												<td>
													<b>Source folder:</b>
												</td>
												<td> 
													<?php echo $specManager->getSourcePath();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>Target folder:</b> 
												</td>
												<td> 
													<?php echo $specManager->getTargetPath();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>URL prefix:</b> 
												</td>
												<td> 
													<?php echo $specManager->getImgUrlBase();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>Web image width:</b> 
												</td>
												<td> 
													<?php echo $specManager->getWebPixWidth();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>Thumbnail width:</b> 
												</td>
												<td> 
													<?php echo $specManager->getTnPixWidth();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>Large image width:</b> 
												</td>
												<td> 
													<?php echo $specManager->getLgPixWidth();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>JPG compression:</b> 
												</td>
												<td> 
													<?php echo $specManager->getJpgCompression();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>PK pattern match term:</b> 
												</td>
												<td> 
													<?php echo $specManager->getSpecKeyPattern();?><br/>
												</td>
											</tr>
											<tr>
												<td>
													<b>PK obtained from:</b> 
												</td>
												<td> 
													<?php echo $specManager->getSpecKeyRetrieval();?><br/>
												</td>
											</tr>
										</table>
									</fieldset>
									<div style="margin:15px 0px 0px 15px;">
										<input name="spprid" type="hidden" value="<?php echo $spprId; ?>" />
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
										<input name="tabindex" type="hidden" value="1" />
										<input name="submitaction" type="submit" value="Process Images" />
									</div>
									<div style="margin:20px;">
										<!-- <a href="logs/">Log Files</a>  -->
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
					elseif($specProjects){
						?> 
						<form name="sppridform" action="index.php" method="post" onsubmit="return validateSppridForm(this);">
							<fieldset>
								<legend><b>Specimen Loading Projects</b></legend>
								<div style="margin:15px;">
									<?php 
									foreach($specProjects as $spprid => $projTitle){
										echo '<input type="radio" name="spprid" value="'.$spprid.'" /> '.$projTitle.'<br/>';
									}
									?>
								</div>
								<div style="margin:15px;">
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
									<input name="tabindex" type="hidden" value="1" />
									<input type="submit" name="submitaction" value="Select Collection Project" />
								</div>
							</fieldset>
						</form>
						<?php 
					}
				}
				else{
					echo '<div>ERROR: collection identifier not defined. Contact administrator</div>';
				}
			}
			else{
				?>
				<div style='font-weight:bold;'>
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/specprocessor/index.php'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
	</body>
</html>
