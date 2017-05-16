<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../imagelib/admin/thumbnailbuilder.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"";
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";

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
$imgManager->setCollid($collid);
$imgManager->setTid($tid);
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Thumbnail Builder</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
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
						if(!$action) $imgManager->resetProcessing();
						$reportArr = $imgManager->getReportArr();
						if($reportArr){
							echo '<b>Images counts without thumbnails and/or basic web image display</b>';
							if($tid) echo '<div style="margin:5px 25px">Taxa Filter: '.$imgManager->getSciname().' (tid: '.$tid.')</div>';
							echo '<ul>';
							foreach($reportArr as $id => $retArr){
								echo '<li>';
								echo '<a href="thumbnailbuilder.php?collid='.$id.'&tid='.$tid.'&action=none">';
								echo $retArr['name'];
								echo '</a>';
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
					<div style="margin:25px 10px;">
						<?php 
						if($action == "Build Thumbnails"){
							echo '<div style="font-weight:bold;">Start processing...</div>';
							$imgManager->buildThumbnailImages(); 
							echo '<div style="font-weight:bold;">Finished!</div>';
							echo '<div style="margin-top:20px"><a href="thumbnailbuilder.php?collid='.$collid.'&tid='.$tid.'&action=none">Return to Main Menus</a></div>';
						}
						else{
							if($reportArr){
								?>
								<form name="tnbuilderform" action="thumbnailbuilder.php" method="post">
									<input type="hidden" name="collid" value="<?php echo $collid; ?>">
									<input type="hidden" name="tid" value="<?php echo $tid; ?>">
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