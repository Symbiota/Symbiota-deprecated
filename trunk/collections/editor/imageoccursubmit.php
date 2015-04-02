<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorImages.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/imageoccursubmit.php?'.$_SERVER['QUERY_STRING']);

$collid  = $_REQUEST["collid"];
$action = array_key_exists("formaction",$_REQUEST)?$_REQUEST["formaction"]:"";

$occurManager = new OccurrenceEditorImages();
$occurManager->setCollid($collid);
$collMap = $occurManager->getCollMap();

$statusStr = '';
$isEditor = 0;
if($collid){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
}
if($isEditor){
	if($action == 'Submit Occurrence'){
		if($occurManager->addImageOccurrence($_POST)){
			$occid = $occurManager->getOccid();
			$statusStr = $occurManager->getErrorStr();
			if($occid && isset($_POST['gotomode']) && $_POST['gotomode']) header('Location: occurrenceeditor.php?occid='.$occid);
		}
	}
}
if($collid && file_exists('includes/config/occurVarColl'.$collid.'.php')){
	//Specific to particular collection
	include('includes/config/occurVarColl'.$collid.'.php');
}
elseif(file_exists('includes/config/occurVarDefault.php')){
	//Specific to Default values for portal
	include('includes/config/occurVarDefault.php');
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Image Submission</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />	
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.imageoccursubmit.js?ver=141119" type="text/javascript"></script>
	<script src="../../js/symb/shared.js?ver=141119" type="text/javascript"></script>
	<script type="text/javascript">
	function validateImgOccurForm(f){

		return true;
	}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
		<b>Occurrence Image Submission</b>
	</div>
	<!-- inner text -->
	<div id="innertext">
		<h1><?php echo $collMap['collectionname']; ?></h1>
		<?php 
		if($statusStr){
			echo '<div style="margin:15px;color:red;">'.$statusStr.'</div>';
		}
		if($isEditor){
			?>
			<form id='imgoccurform' name='imgoccurform' action='imageoccursubmit.php' method='post' enctype='multipart/form-data' onsubmit="return validateImgOccurForm(this)">
				<fieldset style="padding:15px;">
					<legend><b>Manual Occurrence Image Upload</b></legend>
					<div class="targetdiv">
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<div>
							<input name='imgfile' type='file' size='70' />
						</div>
						<div id="newimagediv"></div>
						<div style="margin:10px 0px;">
							* Uploading web-ready images recommended. Upload image size can not be greater than 10MB
						</div>
					</div>
					<div class="targetdiv" style="display:none;">
						<div style="margin-bottom:10px;">
							Enter a URL to an image already located on a web server. 
							If the image is larger than a typical web image, the url will be saved as the large version 
							and a basic web derivative will be created. 
						</div>
						<div>
							<b>Image URL:</b><br/> 
							<input type='text' name='imgurl' size='70' />
						</div>
						<div>
							<input type="checkbox" name="copytoserver" value="1" <?php echo (isset($_POST['copytoserver'])&&$_POST['copytoserver']?'checked':''); ?> /> 
							Copy large image to server (if left unchecked, source URL will server as large version)
						</div>
					</div>
					<div style="float:right;text-decoration:underline;font-weight:bold;">
						<div class="targetdiv">
							<a href="#" onclick="toggle('targetdiv');return false;">Enter URL</a>
						</div>
						<div class="targetdiv" style="display:none;">
							<a href="#" onclick="toggle('targetdiv');return false;">Upload Local Image</a>
						</div>
					</div>
					<div>
						<input type="checkbox" name="nolgimage" value="1" <?php echo (isset($_POST['nolgimage'])&&$_POST['nolgimage']?'checked':''); ?> /> 
						Do not map large version of image (when applicable) 
					</div>
				</fieldset>
				<fieldset style="padding:15px;">
					<legend><b>Skeletal Data</b></legend>
					<div style="margin:3px;">
						<b>Catalog Number:</b> 
						<input name="catalognumber" type="text" onchange="<?php if(!defined('CATNUMDUPECHECK') || CATNUMDUPECHECK) echo 'searchDupesCatalogNumber(this.form)'; ?>" />
					</div>
					<div style="margin:3px;">
						<b>Scientific Name:</b> 
						<input id="sciname" name="sciname" type="text" value="<?php echo (isset($_POST['sciname'])?$_POST['sciname']:''); ?>" style="width:300px"/> 
						<input name="scientificnameauthorship" type="text" value="<?php echo (isset($_POST['scientificnameauthorship'])?$_POST['scientificnameauthorship']:''); ?>" /><br/>
						<input type="hidden" id="tidinterpreted" name="tidinterpreted" value="<?php echo (isset($_POST['tidinterpreted'])?$_POST['tidinterpreted']:''); ?>" />
						<b>Family:</b> <input name="family" type="text" value="<?php echo (isset($_POST['family'])?$_POST['family']:''); ?>" />
						<input type="checkbox" name="localitysecurity" tabindex="0" value="1" <?php echo (isset($occArr['localitysecurity'])&&$occArr['localitysecurity']?'CHECKED':''); ?> />
						Protect locality details from general public
					</div>
					<div> 
						<div style="float:left;margin:3px;">
							<b>Country:</b><br/> 
							<input id="country" name="country" type="text" value="<?php echo (isset($_POST['country'])?$_POST['country']:''); ?>" />
						</div> 
						<div style="float:left;margin:3px;">
							<b>State/Province:</b><br/>
							<input id="state" name="stateprovince" type="text" value="<?php echo (isset($_POST['stateprovince'])?$_POST['stateprovince']:''); ?>" onchange="localitySecurityCheck(this.form)" />
						</div> 
						<div style="float:left;margin:3px;">
							<b>County:</b><br/>
							<input id="county" name="county" type="text" value="<?php echo (isset($_POST['county'])?$_POST['county']:''); ?>" />
						</div> 
					</div>
					<div style="clear:both;margin:3px;">
						<div style="float:left;">
							<input name="tessocr" type="checkbox" value=1 <?php if(isset($_POST['tessocr'])) echo 'checked'; ?> /> 
							OCR Text using Tesseract OCR engine
						</div>
						<div style="float:left;margin:8px 0px 0px 20px;">(<a href="#" onclick="toggle('manualocr')">Manually add OCR</a>)</div>
					</div>
					<div id="manualocr" style="clear:both;display:none;margin:3px;">
						<b>OCR Text</b><br/>
						<textarea name="ocrblock" style="width:100%;height:100px;"></textarea>
					</div>
				</fieldset>
				<div style="margin:10px;clear:both;">
					<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
					<input type="submit" name="formaction" value="Submit Occurrence" />
					<input type="reset" name="reset" value="Reset Form" />
					<div style="margin-top:10px;">
						<b>Follow-up Action:</b>
					</div>
					<div style="margin-left:20px;">
						<input type="radio" name="gotomode" value="0" <?php echo (isset($_POST['gotomode'])&&$_POST['gotomode']?'':'CHECKED'); ?> /> Add Another Image<br/>
						<input type="radio" name="gotomode" value="1" <?php echo (isset($_POST['gotomode'])&&$_POST['gotomode']?'CHECKED':''); ?> /> Go to Editor 
					</div>
				</div>
			</form>
			<?php 
		}
		else{
			echo 'You are not authorized to submit to an observation. ';
			echo '<br/><b>Please contact an administrator to obtain the necessary permissions.</b> ';
		}
		?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>
</body>
</html>