<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprId = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;

$specManager;
if($action == 'Upload ABBYY File'){
	$specManager = new SpecProcessorAbbyy();
}
elseif($action == "Upload Images"){
	$specManager = new SpecProcessorImage();
}
else{
	$specManager = new SpecProcessorManager();
}

$specManager->setCollId($collId);
$specProjects = $specManager->getProjects($spprId);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$status = "";
if($editable){
	if($action == 'Upload ABBYY File'){
		$statusArr = $specManager->loadLabelFile();
		if($statusArr){
			$status = '<ul><li>'.implode('</li><li>',$statusArr).'</li></ul>';
		}
	}
	elseif($action == "Upload Images"){
		$specManager->batchLoadImages($mapTn,$mapLarge);
	}
	elseif($action == "Add New Project"){
		$specManager->addProject($_REQUEST);
	}
	elseif($action == "Edit Project"){
		$specManager->editProject($_REQUEST);
	}
	elseif($action == "Delete Project"){
		$specManager->deleteProject($spprId);
	}
	
}

?>
<html>
	<head>
		<title>Specimen Processor Control Panel</title>
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

			function validateAddForm(f){

				return true;
			}

			function validateEditForm(f){

				return true;
			}

			function validateDelForm(f){

				return true;
			}
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<div style="float:right;margin:10px;" onclick="toggle('adddiv');">
				<img src="../../images/add.png" style="border:0px" />
			</div>
			<div style="float:right;margin:10px;" onclick="toggle('editdiv');">
				<img src="../../images/edit.png" style="border:0px" />
			</div>
			<div style="clear:both;">
				<h1>Specimen Processor Control Panel</h1>
			</div>
			<?php 
			if($symbUid){
				if($status){ 
					?>
					<div style='margin:20px 0px 20px 0px;'>
						<hr/>
						<?php echo $status; ?>
						<hr/>
					</div>
					<?php 
				}
				if($collId){
					if(count($specProjects) <= 1){
						?>
						<div id="adddiv" style="display:<?php echo ($specProjects?'none':'block'); ?>;">
							<form name="addproj" action="index.php" method="post" onsubmit="return verifyAddForm(this);">
								<fieldset>
									<legend><b>New Project</b></legend>
									<div>
										Title: <br/>
										<input name="title" type="text" />
									</div>
									<div>
										Specimen PK matching pattern:<br/> 
										<input name="speckeypattern" type="text" /></div>
									<div>
										Retrieve specimen PK from: 
										<input name="speckeyretrieval" type="radio" value="filename" checked /> Image File Name<br/>
										<input name="speckeyretrieval" type="radio" value="ocr" /> OCR from image
									</div>
									<div>
										Image source path:<br/>
										<input name="sourcepath" type="text" />
									</div>
									<div>
										Image target path:<br/>
										<input name="targetpath" type="text" />
									</div>
									<div>
										Image URL base:<br/>
										<input name="imgurl" type="text" />
									</div>
									<div>
										Central image pixel width:<br/>
										<input name="webpixwidth" type="text" />
									</div>
									<div>
										Thumbnail pixel width:<br/>
										<input name="tnpixwidth" type="text" />
									</div>
									<div>
										Thumbnail pixel width:<br/>
										<input name="lgpixwidth" type="text" />
									</div>
									<div>
										Create thumbnail:<br/>
										<input name="createtnimg" type="text" />
									</div>
									<div>
										Create large image:<br/>
										<input name="createlgimg" type="text" />
									</div>
									<div>
										<input name="action" type="submit" value="Add New Project" />
									</div>
								</fieldset>
							</form>
						</div>
						<?php 
						if($specProjects){
							if(!$spprId) $spprId = array_shift(array_keys($specProjects));
							$projVars = array_shift($specProjects);
							?>
							<div id="editdiv" style="display:none;">
								<form id="editform" action="index.php" method="post" onsubmit="return verifyEditForm(this);" >
									<fieldset>
										<legend><b>Edit Project</b></legend>
										<div>
											Title: <br/>
											<input name="title" type="text" value="<?php echo $projVars['title']; ?>" />
										</div>
										<div>
											Specimen PK matching pattern:<br/> 
											<input name="speckeypattern" type="text" value="<?php echo $projVars['speckeypattern']; ?>" /></div>
										<div>
											Retrieve specimen PK from: 
											<input name="speckeyretrieval" type="radio" value="filename" <?php echo ($projVars['speckeyretrieval']=='filename'?'checked':''); ?> /> Image File Name<br/>
											<input name="speckeyretrieval" type="radio" value="ocr" <?php echo ($projVars['speckeyretrieval']=='ocr'?'checked':''); ?> /> OCR from image
										</div>
										<div>
											Image source path:<br/>
											<input name="sourcepath" type="text" value="<?php echo $projVars['sourcepath']; ?>" />
										</div>
										<div>
											Image target path:<br/>
											<input name="targetpath" type="text" value="<?php echo $projVars['targetpath']; ?>" />
										</div>
										<div>
											Image URL base:<br/>
											<input name="imgurl" type="text" value="<?php echo $projVars['imgurl']; ?>" />
										</div>
										<div>
											Central image pixel width:<br/>
											<input name="webpixwidth" type="text" value="<?php echo $projVars['webpixwidth']; ?>" />
										</div>
										<div>
											Thumbnail pixel width:<br/>
											<input name="tnpixwidth" type="text" value="<?php echo $projVars['tnpixwidth']; ?>" />
										</div>
										<div>
											Thumbnail pixel width:<br/>
											<input name="lgpixwidth" type="text" value="<?php echo $projVars['lgpixwidth']; ?>" />
										</div>
										<div>
											Create thumbnail:<br/>
											<input name="createtnimg" type="text" value="<?php echo $projVars['createtnimg']; ?>" />
										</div>
										<div>
											Create large image:<br/>
											<input name="createlgimg" type="text" value="<?php echo $projVars['createlgimg']; ?>" />
										</div>
										<div>
											<input name="spprid" type="hidden" value="<?php echo $spprId; ?>" />
											<input name="action" type="submit" value="Add New Project" />
										</div>
									</fieldset>
								</form>
								<form id="delform" action="index.php" method="post" onsubmit="return verifyDelForm(this);" >
									<fieldset>
										<legend><b>Delete Project</b></legend>
										<div>
											<input name="spprid" type="hidden" value="<?php echo $spprId; ?>" />
											<input name="action" type="submit" value="Delete Project" />
										</div>
									</fieldset>
								</form>
							</div>
							<div style="margin:10px;">
								<form name="imgprocessform" action="index.php" method="get">
									<fieldset>
										<legend><b>Image Processor</b></legend>
										<div style="margin:10px;">
											This process will create web quality versions of the specimen images found within 
											the &#8220;source folder&#8221;, deposit them into the &#8220;target folder&#8221;, 
											and link them to their prespective specimen record.
										</div>
										<div style="margin:15px;">
											<input type="checkbox" name="maptn" value="1" CHECKED /> 
											Create Thumbnails<br/>
											<input type="checkbox" name="maplarge" value="1" CHECKED /> 
											Create Large Versions
										</div>
										<div style="margin:15px;">
											<b>Source Folder:</b> 
											<?php echo $projVars['sourcepath'];?><br/>
											<b>Target Folder:</b> 
											<?php echo $projVars['targetpath'];?><br/>
										</div>
										<div style="margin:15px 0px 0px 15px;">
											<input type="submit" name="action" value="Process Images" />
										</div>
										<div style="margin:5px 0px 10px 80px;">
											<a href="logs/">Log Files</a>
										</div>
									</fieldset>
								</form>
							</div>
							<div style="margin:10px;">
								<form name="abbyyloaderform" action="index.php" enctype="multipart/form-data" method="post" onsubmit="return validateAbbyyForm(this);">
									<fieldset>
										<legend><b>ABBYY OCR File Loader</b></legend>
										<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
										<div style="font-weight:bold;margin:10px;">
											File: 
											<input id="abbyyfile" name="abbyyfile" type="file" size="45" />
										</div>
										<div style="margin:10px;">
											<input type="hidden" name="collid" value="<?php echo $collId; ?>" >
											<input type="submit" name="action" value="Upload ABBYY File" />
										</div>
									</fieldset>
								</form>
							</div>
							<?php
						}
					}
					elseif(count($specProjects) == 0){
						//Display form to add a project profile 
					}
					elseif(count($specProjects) == 0){
						//Display form to pick a project from list 
					}
				}
				else{
					if($collList = $specManager->getCollectionList()){
						?>
						<form name="collidform" action="index.php" method="post" onsubmit="return validateCollidForm(this);">
							<fieldset>
								<legend><b>Collection Projects</b></legend>
								<div style="margin:15px;">
									<?php 
									foreach($collList as $cId => $cName){
										echo '<input type="radio" name="collid" value="'.$cId.'" /> '.$cName.'<br/>';
									}
									?>
								</div>
								<div style="margin:15px;">
									<input type="submit" name="action" value="Select Collection Project" />
								</div>
							</fieldset>
						</form>
						<?php
					}
					else{
						echo '<div>There are no Collection Project for which you have authority to update</div>';						
					} 
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
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
