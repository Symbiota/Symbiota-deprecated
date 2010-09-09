<?php
/*
* Author: E.E. Gilbert
* Sept 2010
*/

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ItisTaxaLoaderManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$loaderManager = new ItisTaxaLoaderManager();

$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}
	 
$status = "";
if($editable){
	if($action == "Upload File"){
		$status = $loaderManager->uploadFile();
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle; ?> ITIS Loader</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script type="text/javascript">

	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_itistaxaloaderMenu)?$taxa_admin_itistaxaloaderMenu:false);
include($serverRoot.'/header.php');
if(isset($taxa_admin_itistaxaloaderCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_itistaxaloaderCrumbs;
	echo " <b>ITIS Taxa Loader</b>"; 
	echo "</div>";
}

if($editable){
?>
<div style="margin:30px;">
	<?php 
	if($status){
		echo '<div><ul>';
		echo $status;
		echo '</ul></div><hr/>';
	}
	?>
	<form name="fileuploadform" action="itistaxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return checkFileUploadForm()">
		<fieldset style="width:450px;">
			<legend style="font-weight:bold;font-size:120%;">Upload File</legend>
			<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
			<div>
				<b>Upload File:</b>
				<div style="margin:10px;">
					<input id="uploadfile" name="uploadfile" type="file" size="40" />
				</div>
			</div>
			<div style="margin:10px;">
				<input type="submit" name="action" value="Upload File" />
			</div>
		</fieldset>
	</form>
</div>
<?php  
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		You must login and have the correct permissions to upload taxonomic data.<br />
		Please 
		<a href="<?php echo $clientRoot; ?>/profile/index.php?refurl=<?php echo $clientRoot; ?>/taxa/admin/itistaxaloader.php">
			login
		</a>!
	</div>
	<?php 
}


include($serverRoot.'/footer.php');
?>

</body>
</html>
