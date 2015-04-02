<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ImageCleaner.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../imagelib/admin/thumbnailbuilder.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"";

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}

$imgManager = new ImageCleaner();
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Thumbnail Builder</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_misc_buildthumbnailsMenu)?$imagelib_misc_buildthumbnailsMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_misc_buildthumbnailsCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_misc_buildthumbnailsCrumbs;
		echo " <b>Build Thumbnails</b>";
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($isEditor){
			?>
			<div style="margin:10px;">
				<fieldset>
					<legend><b>Thumbnail Builder</b></legend>
					<div style="margin:10px;">
						<b>Images w/o thumbnails:</b> <?php echo $imgManager->getMissingTnCount($collid); ?>
					</div>
					<div style="margin:10px;">
						This function will build thumbnail images for all image records that have NULL values for the thumbnail field.
					</div>
					<div style="margin:15px;">
						<?php 
						if($action == "Build Thumbnails"){
							echo '<div style="font-weight:bold;">Working on internal and external thumbnail images</div>';
							echo '<ol>';
							$imgManager->buildThumbnailImages($collid); 
							echo '</ol>';
							echo '<div style="font-weight:bold;">Finished!</div>';
						}
						?>
					</div>
					<div style="margin:10px;">
						<form name="tnbuilderform" action="thumbnailbuilder.php" method="post">
							<input type="hidden" name="collid" value="<?php echo $collid; ?>">
							<input type="submit" name="action" value="Build Thumbnails">
						</form>
					</div>
				</fieldset>
			</div>
			<?php 
		}
		else{
			echo '<div>You need to be a portal administrator to use this module</div>';
		}
		?>
			
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>