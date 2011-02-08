<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;

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
	
}

?>
<html>
	<head>
		<title>Specimen Processor Control Panel</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<script language="javascript">
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
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Specimen Processor Control Panel</h1>
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
			if($collId){ 
				?>
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
								<?php echo $specManager->getProjVarible('sourcepath');?><br/>
								<b>Target Folder:</b> 
								<?php echo $specManager->getProjVarible('targetbase');?><br/>
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
			else{
				if($symbUid){
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
				else{
					?>
					<div style='font-weight:bold;'>
						Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/specprocessor/index.php'>login</a>!
					</div>
					<?php 
				}
			}
			?>
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
