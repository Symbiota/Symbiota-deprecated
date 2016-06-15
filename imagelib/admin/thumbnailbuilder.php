<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../imagelib/admin/thumbnailbuilder.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"";

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}
elseif($collid){
	if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
		$isEditor = true;
	}
}

$imgManager = new ImageCleaner();
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Thumbnail Builder</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?> 
	<div class="navpath">
		<a href="../../index.php">Home</a> &gt;&gt;
		<?php 
		if($collid){
			echo '<a href="../../collections/misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Management Menu</a> &gt;&gt;';
		}
		else{
			echo '<a href="../../sitemap.php">Sitemap</a> &gt;&gt;';
		}
		?>
		<b>Thumbnail Builder</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($isEditor){
			?>
			<div style="margin:10px;">
				<fieldset>
					<legend><b>Thumbnail Builder</b></legend>
					<div style="margin:10px;">
						<?php 
						$reportArr = $imgManager->getReportArr($collid);
						if($reportArr){
							echo '<b>Images without thumbnails</b>';
							echo '<ul>';
							foreach($reportArr as $id => $retArr){
								echo '<li>';
								if($id) echo '<a href="../../collections/misc/collprofiles.php?collid='.$id.'" target="_blank">';
								echo $retArr['name'];
								if($id) echo '</a>';
								echo ': '.$retArr['cnt'].' images';
								echo '</li>';
							}
							echo '</ul>';
						}
						else{
							echo '<div style="font-weight:bold;">All images have properly mapped thumbnails. Nothing needs to be done.</div>';
						}
						?>
					</div>
					<div style="margin:15px;">
						<?php 
						if($action == "Build Thumbnails"){
							echo '<div style="font-weight:bold;">Starting processing...</div>';
							$imgManager->buildThumbnailImages($collid); 
							echo '<div style="font-weight:bold;">Finished!</div>';
							echo '<div style="margin-top:20px"><a href="thumbnailbuilder.php?collid='.$collid.'">Return to Main Menus</a></div>';
						}
						else{
							if($reportArr){
								?>
								<form name="tnbuilderform" action="thumbnailbuilder.php" method="post">
									<input type="hidden" name="collid" value="<?php echo $collid; ?>">
									<input type="submit" name="action" value="Build Thumbnails">
								</form>
								<div style="margin:10px;">
									* This function will build thumbnail images for all image records that have NULL values for the thumbnail field.
								</div>
								<?php
							}
						}
						?>
					</div>
				</fieldset>
			</div>
			<?php 
		}
		else{
			echo '<div><b>ERROR: improper permissions</b></div>';
		}
		?>
	</div>
	<?php 
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>