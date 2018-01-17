<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("submitaction",$_POST)?$_POST["submitaction"]:"";
$collid = $_REQUEST['collid'];

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}

$imgManager = new ImageCleaner();

if($isEditor){
	if($action == 'remove_images'){
		if($_POST['target_imgid']){
			$imgManager->setCollid($collid);
			$imgManager->recycleImagesFromStr($_POST['target_imgid']);
		}
		else{
			//Get image ids from input fields
		}
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Image Recycler</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function verifyRecycleForm(f){
			return true;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php">Homepage</a> &gt;&gt;
		<a href="../../collections/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management Menu</a> &gt;&gt;
		<b>Bulk Image Recycler</b>
	</div>
	<div id="innertext">
		<form name="imgdelform" action="imagerecycler.php" method="post" enctype="multipart/form-data" onsubmit="return verifyRecycleForm(this)">
			<fieldset style="width:90%;">
				<legend style="font-weight:bold;font-size:120%;">Batch Image Remover</legend>
				<div style="margin:10px;">
					This tool will batch delete images based on submission of multiple image identifiers.
				</div>
				<div style="margin:10px;">
					<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
					<input name="uploadfile" type="file" size="40" />
				</div>
				<div style="margin:10px;">
					<b>Image Identifiers</b><br/>
					<textarea name="target_imgid" style="width:300px;height:100px;"></textarea>
				</div>
				<div style="margin:20px;">
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<button type="submit" name="submitaction" value="remove_images">Bulk Remove Image Files</button>
				</div>
			</fieldset>
		</form>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>