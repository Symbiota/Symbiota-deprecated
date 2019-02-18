<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageCleaner.php');
include_once($SERVER_ROOT.'/content/lang//imagelib/admin/thumbnailbuilder.'.$LANG_TAG.'.php');
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
<title><?php echo $DEFAULT_TITLE; ?> Constructor de miniaturas</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function resetRebuildForm(f){
			f.catNumLow.value = "";
			f.catNumHigh.value = "";
			f.catNumList.value = "";
		}
	</script>
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
		<b><?php echo $LANG['THUMB_B'];?>Constructor de miniaturas</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			if($action && $action != 'none'){
				echo '<fieldset style="margin:10px;padding:15px">';
				echo '<legend><b>Processing Panel</b></legend>';
				echo '<div style="font-weight:bold;">Start processing...</div>';
				if($action == 'Build Thumbnails'){
					$imgManager->buildThumbnailImages();
				}
				elseif($action == 'Refresh Thumbnails'){
					echo '<div style="margin-bottom:10px;">Number of images to be refreshed: '.$imgManager->getProcessingCnt($_POST).'</div>';
					$imgManager->refreshThumbnails($_POST);
				}
				echo '<div style="margin-top:10px;font-weight:bold;">Finished!</div>';
				echo '</fieldset>';
			}
			?>
			<fieldset style="margin:30px 10px;padding:15px;">
				<legend><b><?php echo $LANG['THUMB_B'];?></b></legend>
				<div>
					<?php
					//if(!$action) $imgManager->resetProcessing();
					$reportArr = $imgManager->getReportArr();
					if($reportArr){
						echo '<b>Images counts without thumbnails and/or basic web image display</b> - This function will build thumbnail images for all occurrence images mapped from an external server.';
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
						echo '<div style="font-weight:bold;">'.$LANG['ALL_IMG'].'</div>';
					}
					?>
				</div>
				<div style="margin:15px;">
					<?php
					if($reportArr){
						?>
						<div style="margin:10px;">
							<form name="tnbuilderform" action="thumbnailbuilder.php" method="post">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>">
								<input name="tid" type="hidden" value="<?php echo $tid; ?>">
								<input name="action" type="submit" value="Build Thumbnails">
							</form>
						</div>
						<?php
					}
					?>
				</div>
			</fieldset>
			<?php
			if($collid){
				if($remoteImgCnt = $imgManager->getRemoteImageCnt()){
					?>
					<fieldset style="margin:30px 10px;padding:15px">
						<legend><b>Thumbnail Re-Mapper</b></legend>
						<form name="tnrebuildform" action="thumbnailbuilder.php" method="post">
							<div style="margin-bottom:20px;">
								This tool will iterate through the remotely mapped images and refresh locally stored image derivatives.
								Default action is to only rebuild derivatives when the creation date of the source image is more recent than the original build date.
								The alternative option is to force the rebuild of all images.
							</div>
							<div style="margin-bottom:10px;">
								Number images available for refresh: <?php echo $remoteImgCnt; ?>
							</div>
							<div style="margin-bottom:10px;">
								Catalog Number Range: <input name="catNumLow" type="text" value="<?php echo (isset($_POST['catNumLow'])?$_POST['catNumLow']:''); ?>" /> -
								<input name="catNumHigh" type="text" value="<?php echo (isset($_POST['catNumHigh'])?$_POST['catNumHigh']:''); ?>" />
							</div>
							<div style="margin-bottom:10px;vertical-align:top;height:90px">
								<div style="float:left">Catalog Number List: </div>
								<div style="margin-left:5px;float:left"><textarea name="catNumList" rows="5" cols="50"><?php echo (isset($_POST['catNumList'])?$_POST['catNumList']:''); ?></textarea></div>
							</div>
							<div style="margin-bottom:10px;">
								<input name="evaluate_ts" type="radio" value="1" checked /> Only process images where the source file is more recent than thumbnails<br/>
								<input name="evaluate_ts" type="radio" value="0" /> Force rebuild all images
							</div>
							<div style="margin:20px;clear:both">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="action" type="submit" value="Refresh Thumbnails" />
								<input type="button" value="Reset" onclick="resetRebuildForm(this.form)" />
							</div>
						</form>
					</fieldset>
					<?php
				}
			}
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
