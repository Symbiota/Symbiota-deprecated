<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprId = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;

$specManager;
if($action == 'Upload ABBYY File'){
	$specManager = new SpecProcessorAbbyy();
}
elseif($action == 'Process Images'){
	$specManager = new SpecProcessorImage();
}
else{
	$specManager = new SpecProcessorManager();
}

$specManager->setCollId($collId);
$specManager->setSpprId($spprId);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$status = "";
if($editable){
	if($action == 'Add New Project'){
		$specManager->addProject($_REQUEST);
	}
	elseif($action == 'Edit Project'){
		$specManager->editProject($_REQUEST);
	}
	elseif($action == 'Delete Project'){
		$specManager->deleteProject($_REQUEST['sppriddel']);
	}
}
$specProjects = Array();
if(!$spprId){
	$specProjects = $specManager->getProjects();
	if(count($specProjects) == 1){
		$spprId = array_shift(array_keys($specProjects));
		$specManager->setSpprId($spprId);
	}
}
if($spprId){
	$specManager->setProjVariables();
}

?>
<html>
	<head>
		<title>Image Processor</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<script language="javascript">
			function toggle(divName){
				divObj = document.getElementById(divName);
				if(divObj != null){
					if(divObj.style.display == "block"){
						divObj.style.display = "none";
					}
					else{
						divObj.style.display = "block";
					}
				}
				else{
					divObjs = document.getElementsByTagName("div");
					divObjLen = divObjs.length;
					for(i = 0; i < divObjLen; i++) {
						var obj = divObjs[i];
						if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
							else {
								obj.style.display="none";
							}
						}
					}
				}
			}
			
			function validateAbbyyForm(f){
				if(f.abbyyfile.value == ""){
					alert('Select an ABBYY output file to upload');
					return false;
				}
				return true;
			}

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
			
			function isNumeric(sText){
			   	var validChars = "0123456789-.";
			   	var ch;
			 
			   	for(var i = 0; i < sText.length; i++){ 
					ch = sText.charAt(i);
					if(validChars.indexOf(ch) == -1) return false;
			   	}
				return true;
			}
		</script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php
			if($editable && $collId){ 
				?>
				<div style="float:right;margin:10px;" onclick="toggle('adddiv');">
					<img src="../../images/add.png" style="border:0px" />
				</div>
				<?php
			}
			?>
			<div style="clear:both;padding:15px;">
				<?php 
				if($status){ 
					?>
					<div style='margin:20px 0px 20px 0px;'>
						<hr/>
						<?php echo $status; ?>
						<hr/>
					</div>
					<?php 
				}
				if($action == 'Upload ABBYY File'){
					$statusArr = $specManager->loadLabelFile();
					if($statusArr){
						$status = '<ul><li>'.implode('</li><li>',$statusArr).'</li></ul>';
					}
				}
				elseif($action == 'Process Images'){
					echo '<h3>Batch Processing Images</h3>'."\n";
					echo '<ul>'."\n";
					$specManager->setCreateWebImg(array_key_exists('mapweb',$_REQUEST)?$_REQUEST['mapweb']:1);
					$specManager->setCreateTnImg(array_key_exists('maptn',$_REQUEST)?$_REQUEST['maptn']:1);
					$specManager->setCreateLgImg(array_key_exists('maplarge',$_REQUEST)?$_REQUEST['maplarge']:1);
					$specManager->setCreateNewRec($_REQUEST['createnewrec']);
					$specManager->setCopyOverImg($_REQUEST['copyoverimg']);
					if(isset($useImageMagick) && $useImageMagick) $specManager->setUseImageMagick(1);
					$specManager->batchLoadImages();
					echo '</ul>'."\n";
				}
				?>
				This tool is designed to aid collection manager in processing specimen images and integrating them into the biodiversity portal. 
				Typical processing steps involve the following steps. 
				Display <a href="#" onclick="toggle('fulldetails')">full details</a> for image processing.
				<ol id="fulldetails" style="display:none;">
					<li>
						<b>Create web quality copies of the original image</b> - 
						The standard image used for the web is a compressed JPG. Three copies typically 
						created are: basic web, thumbnail, and a large version for optional download by users. 
						This application is capable of creating the web versions 
						of the image using the PHP GD image library. If managers wish to use other image resizing and compression 
						algorithms, they can process the images beforehand and then use this application for the file  
						transfer and linking steps. 
						Maximum image file size is dictated by the PHP configuration settings (e.g. memory_limit) of the web server.
					</li>
					<li>
						<b>Obtain the unique identifier for the specimen record</b> - 
						This is the identification value that uniquely identifies each specimen. 
						Ideally, the accession number or barcode serves this purpose, however, this is only possible if these values 
						are entered for each specimen and are truly unique within that particular collection. 
						There are two methods available for retrieving the identification value. The first is to retrieve 
						the value from the file name of the image. The second is to 
						attempt to use OCR to obtain the value directly from the image. In both cases, 
						pattern matching is used to locate the value.  
					</li>
					<li>
						<b>Transfer images to storage</b> - 
						Given that the images are to be displayed within the user's web browser, 
						the storage location must be accessible to the web. In general, it is a good practice to store only a 
						couple thousand images per folder. For this reason, the transfer process will attempt to 
						make use of the specimen identification value (Primary Key) to to establish a practical storage system 
						(UTC00413000 to UTC00413999 goes into folder called UTC00413). Another option is to place the unprocessed 
						images within folders using the preferred naming schema. This will trigger the transfer scripts to 
						place the processed images within folders of the same name. 
					</li>
					<li>
						<b>Integrate images into portal</b> - 
						This is done by loading image metadata into the portal's database along with links to the prospective 
						specimen record. The specimen identifier obtained in step 2 is used to locate existing specimen records. 
						If the specimen record does not yet exists, there is an option of creating a blank record 
						to which the image will be linked so that the specimen label can then be processed online using the image.  
						Note that it is important that the image URLs are stable. If an image is moved or renamed, 
						the image URL stored within the database will also have to be modified.  
					</li>
				
				</ol>
			</div>
			<?php 
			if($symbUid){
				if($collId){
					?>
					<div id="adddiv" style="display:<?php echo ($spprId||$specProjects?'none':'block'); ?>;">
						<form name="addproj" action="index.php" method="post" onsubmit="return validateProjectForm(this);">
							<fieldset>
								<legend><b>New Project</b></legend>
								<table>
									<tr>
										<td>
											<b>Title:</b>
										</td>
										<td>
											<input name="title" type="text" style="width:300px;" />
										</td>
									</tr>
									<tr>
										<td>
											<b>PK matching pattern:</b> 
										</td>
										<td> 
											<input name="speckeypattern" type="text" style="width:300px;" />
										</td>
									</tr>
									<tr>
										<td>
											<b>Retrieve PK from:</b> 
										</td>
										<td> 
											<input name="speckeyretrieval" type="radio" value="filename" checked /> Image File Name<br/>
											<input name="speckeyretrieval" type="radio" value="ocr" /> OCR from image
										</td>
									</tr>
									<tr>
										<td>
											<b>Image source path:</b>
										</td>
										<td> 
											<input name="sourcepath" type="text" style="width:400px;" />
										</td>
									</tr>
									<tr>
										<td>
											<b>Image target path:</b>
										</td>
										<td> 
											<input name="targetpath" type="text" style="width:400px;" />
										</td>
									</tr>
									<tr>
										<td>
											<b>Image URL base:</b>
										</td>
										<td> 
											<input name="imgurl" type="text" style="width:400px;" />
										</td>
									</tr>
									<tr>
										<td>
											<b>Central pixel width:</b>
										</td>
										<td> 
											<input name="webpixwidth" type="text" style="width:50px;" value="<?php echo $specManager->getWebPixWidth(); ?>" /> 
										</td>
									</tr>
									<tr>
										<td>
											<b>Thumbnail pixel width:</b> 
										</td>
										<td> 
											<input name="tnpixwidth" type="text" style="width:50px;" value="<?php echo $specManager->getTnPixWidth(); ?>" /> 
										</td>
									</tr>
									<tr>
										<td>
											<b>Large pixel width:</b>
										</td>
										<td> 
											<input name="lgpixwidth" type="text" style="width:50px;" value="<?php echo $specManager->getLgPixWidth(); ?>" /> 
										</td>
									</tr>
									<tr>
										<td>
											<b>JPG compression:</b>
										</td>
										<td> 
											<input name="jpgcompression" type="text" style="width:50px;" value="<?php echo $specManager->getJpgCompression(); ?>" />
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
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
											<input name="tabindex" type="hidden" value="1" />
											<input name="submitaction" type="submit" value="Add New Project" />
										</td>
									</tr>
								</table>
							</fieldset>
						</form>
					</div>
					<?php 
					if($spprId){
						?>
						<div style="">
							<div id="editdiv" style="display:none;">
								<form id="editform" action="index.php" method="post" onsubmit="return validateProjectForm(this);" >
									<fieldset>
										<legend><b>Edit Project</b></legend>
										<table>
											<tr>
												<td>
													<b>Title:</b>
												</td>
												<td>
													<input name="title" type="text" value="<?php echo $specManager->getTitle(); ?>" style="width:300px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>PK matching pattern:</b> 
												</td>
												<td> 
													<input name="speckeypattern" type="text" value="<?php echo $specManager->getSpecKeyPattern(); ?>" style="width:300px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Retrieve PK from:</b> 
												</td>
												<td> 
													<input name="speckeyretrieval" type="radio" value="filename" <?php echo ($specManager->getSpecKeyRetrieval()=='filename'?'checked':''); ?> /> Image File Name<br/>
													<input name="speckeyretrieval" type="radio" value="ocr" <?php echo ($specManager->getSpecKeyRetrieval()=='ocr'?'checked':''); ?> /> OCR from image
												</td>
											</tr>
											<tr>
												<td>
													<b>Image source path:</b>
												</td>
												<td> 
													<input name="sourcepath" type="text" value="<?php echo $specManager->getSourcePath(); ?>" style="width:400px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Image target path:</b>
												</td>
												<td> 
													<input name="targetpath" type="text" value="<?php echo $specManager->getTargetPath(); ?>" style="width:400px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Image URL base:</b>
												</td>
												<td> 
													<input name="imgurl" type="text" value="<?php echo $specManager->getImgUrlBase(); ?>" style="width:400px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Central pixel width:</b>
												</td>
												<td> 
													<input name="webpixwidth" type="text" value="<?php echo $specManager->getWebPixWidth(); ?>" style="width:50px;" /> 
												</td>
											</tr>
											<tr>
												<td>
													<b>Thumbnail pixel width:</b> 
												</td>
												<td> 
													<input name="tnpixwidth" type="text" value="<?php echo $specManager->getTnPixWidth(); ?>" style="width:50px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Large pixel width:</b>
												</td>
												<td> 
													<input name="lgpixwidth" type="text" value="<?php echo $specManager->getLgPixWidth(); ?>" style="width:50px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>JPG compression:</b>
												</td>
												<td> 
													<input name="jpgcompression" type="text" value="<?php echo $specManager->getJpgCompression(); ?>" style="width:50px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Create thumbnail:</b>
												</td>
												<td> 
													<input name="createtnimg" type="checkbox" value="1" <?php echo ($specManager->getCreateTnImg()?'CHECKED':''); ?> />
												</td>
											</tr>
											<tr>
												<td>
													<b>Create large image:</b>
												</td>
												<td> 
													<input name="createlgimg" type="checkbox" value="1" <?php echo ($specManager->getCreateLgImg()?'CHECKED':''); ?> />
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<input name="spprid" type="hidden" value="<?php echo $spprId; ?>" />
													<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
													<input name="tabindex" type="hidden" value="1" />
													<input name="submitaction" type="submit" value="Edit Project" />
												</td>
											</tr>
										</table>
									</fieldset>
								</form>
								<form id="delform" action="index.php" method="post" onsubmit="return validateDelForm(this);" >
									<fieldset>
										<legend><b>Delete Project</b></legend>
										<div>
											<input name="sppriddel" type="hidden" value="<?php echo $spprId; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" /> 
											<input name="submitaction" type="submit" value="Delete Project" />
										</div>
									</fieldset>
								</form>
							</div>
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
									<input type="submit" name="action" value="Select Collection Project" />
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
