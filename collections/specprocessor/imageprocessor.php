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
	//If there is one image loading profile, and only one, linked to the collection, pull that up as the default project 
	$specProjects = $specManager->getProjects();
	if(count($specProjects) == 1){
		$arrayKeys = array_keys($specProjects);
		$spprId = array_shift($arrayKeys);
		$specManager->setSpprId($spprId);
	}
}
if($spprId) $specManager->setProjVariables();

?>
<html>
	<head>
		<title>Image Processor</title>
		<link href="<?php echo $clientRoot; ?>/css/base.css" type="text/css" rel="stylesheet" />
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
				else if(!isNumeric(f.jpgcompression.value) || f.jpgcompression.value < 30 || f.jpgcompression.value > 100){
					alert("JPG compression needs to be a numeric value between 30 and 100");
					return false;
				}
				else if(f.speckeypattern.value == ""){
					alert("Regular expression field must have a value");
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
				else if(f.targetpath.value == ""){
					alert("Note that since target path is null, scripts will attempt to simply map to images using the Image URL base path");
					if(f.targetpath.value == ""){

					}
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
								<div style="float:right;margin:10px;" onclick="toggle('editdiv');toggle('imgprocessdiv')" title="Close Editor">
									<img src="../../images/edit.png" style="border:0px" />
								</div>
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
											<b>Regular Expression:</b>
										</td>
										<td style="padding-top:13px;"> 
											<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $specManager->getSpecKeyPattern(); ?>" />
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
											<b>Image source path:</b>
										</td>
										<td> 
											<input name="sourcepath" type="text" style="width:400px;" value="<?php echo $specManager->getSourcePath(); ?>" />
											<a id="sourcepathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="sourcepathinfodialog">
												Server path to folder containing source images.
												If a URL (e.g. http://) is supplied, the web server needs to be configured to list 
												all files within the directory, or the html output needs to list all images in anchor tags.
												Scripts will attempt to crawl through all child directories.
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
												The web server (e.g. apache user) must have read/write access to this directory.
												If this field is left blank, the portal's default image target (imageRootPath) will be used.
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
												Image URL prefix that will access the target folder from the browser.
												This will be used to create the image URLs that will be stored in the database.
												If absolute URL is supplied without the domain name, the portal domain will be assumed. 
												If this field is left blank, the portal's default image url will be used ($imageRootUrl).
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
											<b>JPG quality:</b>
										</td>
										<td> 
											<input name="jpgquality" type="text" style="width:50px;" value="<?php echo $specManager->getJpgQuality(); ?>" />
											<a id="jpgcompressioninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="jpgcompressioninfodialog">
												JPG quality refers to amount of compression applied. 
												Value should be numeric and range from 0 (worst quality, smaller file) to 
												100 (best quality, biggest file). 
												If null, 75 is used as the default. 
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<b>Thumbnail:</b>
											<div style="margin:5px 15px;">
												<input name="tnimg" type="radio" value="1" CHECKED /> Create new thumbnail from source image<br/>
												<input name="tnimg" type="radio" value="2" /> Import thumbnail from source location (source name with _tn.jpg suffix)<br/>
												<input name="tnimg" type="radio" value="3" /> Map to thumbnail at source location (source name with _tn.jpg suffix)<br/>
												<input name="tnimg" type="radio" value="4" /> Exclude thumbnail <br/>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<b>Large Image:</b>
											<div style="margin:5px 15px;">
												<input name="lgimg" type="radio" value="1" CHECKED /> Import source image as large version<br/>
												<input name="lgimg" type="radio" value="2" /> Map to source image as large version<br/>
												<input name="lgimg" type="radio" value="3" /> Import large version from source location (source name with _lg.jpg suffix)<br/>
												<input name="lgimg" type="radio" value="4" /> Map to large version at source location (source name with _lg.jpg suffix)<br/>
												<input name="lgimg" type="radio" value="5" /> Exclude large version<br/>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<input name="spprid" type="hidden" value="<?php echo $spprId; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
											<input name="tabindex" type="hidden" value="1" />
											<?php 
											if($spprId){
												echo '<input name="submitaction" type="submit" value="Save Image Project" />';
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
						<div id="imgprocessdiv">
							<form name="imgprocessform" action="index.php" method="post">
								<fieldset>
									<legend><b>Image Processor</b></legend>
									<div style="float:right;margin:10px;" onclick="toggle('editdiv');toggle('imgprocessdiv')" title="Open Editor">
										<img src="../../images/edit.png" style="border:0px" />
									</div>
									<div style="font-size:120%;font-weight:bold;">
										<?php echo $specManager->getTitle(); ?>
									</div>
									<fieldset style="margin:15px;">
										<legend><b>Project Variables</b></legend>
										<table>
											<tr>
												<td colspan="2">
													<b>Web Image:</b>
													<div style="margin:5px 15px"> 
														<input name="webimg" type="radio" value="1" CHECKED /> Evaluate and import source image<br/>
														<input name="webimg" type="radio" value="2" /> Import source image as is without resizing<br/>
														<input name="webimg" type="radio" value="3" /> Map to source image without importing<br/>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<b>Thumbnail:</b>
													<div style="margin:5px 15px"> 
														<input name="tnimg" type="radio" value="1" <?php echo ($specManager->getTnImg() == 1?'CHECKED':'') ?> /> Create new from source image<br/>
														<input name="tnimg" type="radio" value="2" <?php echo ($specManager->getTnImg() == 2?'CHECKED':'') ?> /> Import existing source thumbnail (source name with _tn.jpg suffix)<br/>
														<input name="tnimg" type="radio" value="3" <?php echo ($specManager->getTnImg() == 3?'CHECKED':'') ?> /> Map to existing source thumbnail (source name with _tn.jpg suffix)<br/>
														<input name="tnimg" type="radio" value="0" <?php echo (!$specManager->getTnImg()?'CHECKED':'') ?> /> Exclude thumbnail <br/>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<b>Large Image:</b>
													<div style="margin:5px 15px"> 
														<input name="lgimg" type="radio" value="1" <?php echo ($specManager->getLgImg() == 1?'CHECKED':'') ?> /> Import source image as large version<br/>
														<input name="lgimg" type="radio" value="2" <?php echo ($specManager->getLgImg() == 2?'CHECKED':'') ?> /> Map to source image as large version<br/>
														<input name="lgimg" type="radio" value="3" <?php echo ($specManager->getLgImg() == 3?'CHECKED':'') ?> /> Import existing large version (source name with _lg.jpg suffix)<br/>
														<input name="lgimg" type="radio" value="4" <?php echo ($specManager->getLgImg() == 4?'CHECKED':'') ?> /> Map to exiting large version (source name with _lg.jpg suffix)<br/>
														<input name="lgimg" type="radio" value="0" <?php echo (!$specManager->getLgImg()?'CHECKED':'') ?> /> Exclude large version<br/>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2" title="Unable to match primary identifer with an existing database record">
													<b>Missing record:</b> 
													<div style="margin:5px 15px"> 
														<input type="radio" name="createnewrec" value="0" /> 
														Skip image import and go to next<br/>
														<input type="radio" name="createnewrec" value="1" CHECKED /> 
														Create empty record and link image
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2" title="Image with exact same name already exists">
													<b>Image already exists:</b>
													<div style="margin:5px 15px"> 
														<input type="radio" name="imgexists" value="0" CHECKED /> 
														Skip import<br/>
														<input type="radio" name="imgexists" value="1" /> 
														Rename image and save both<br/>
														<input type="radio" name="imgexists" value="2" /> 
														Copy over existing image
													</div>
												</td>
											</tr>
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
													<b>JPG quality (1-100): </b> 
												</td>
												<td> 
													<?php echo $specManager->getJpgQuality();?><br/>
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
