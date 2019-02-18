<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/EOLManager.php');
include_once($SERVER_ROOT.'/content/lang/taxa/profile/eolmapper.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../taxa/profile/eolmapper.php?'.$_SERVER['QUERY_STRING']);

$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$statusStr = array_key_exists('status',$_REQUEST)?$_REQUEST['status']:'';

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}

$eolManager = new EOLManager();

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE." EOL Manager: "; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<script language=javascript>

	</script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/header.php');
?>
<!--<div class='navpath'>";
	<a href="../index.php">Home</a> &gt;&gt;
	<b>Encyclopedia of Life Manager</b>
</div>! -->
	<!-- This is inner text! -->
	<div id="innertext">
		<h1><?php echo $LANG['ENC_LIFE'];?></h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:red;margin:15px;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			if($submitAction){
				?>
				<hr/>
				<div style="margin:15px;">
					<?php
					if($submitAction == 'Map Taxa'){
						$makePrimary = 0;
						$restart = 0;
						if(array_key_exists('makeprimary',$_POST) && $_POST['makeprimary']){
							$makePrimary = 1;
						}
						if(array_key_exists('restart',$_POST) && $_POST['restart']){
							$restart = 1;
						}
						$eolManager->mapTaxa($makePrimary,$_POST['tidstart'],$restart);
					}
					elseif($submitAction == 'Map Images'){
						$restart = 0;
						if(array_key_exists('restart',$_POST) && $_POST['restart']){
							$restart = 1;
						}
						$eolManager->mapImagesForTaxa($_POST['startindex'],$restart);
					}
					?>
				</div>
				<hr/>
				<?php
			}
			?>
			<div style="color:red;margin:15px;">
				<?php echo $LANG['NOTE_PROC'];?>
			</div>
			<div style="margin:15px;">
				<fieldset style="padding:15px;">
					<legend><b><?php echo $LANG['TAXA_MAPP'];?></b></legend>
					<div>
						<?php echo $LANG['LEGEND'];?>
					</div>
					<div style="margin:10px;">
						<?php echo $LANG['NUM_TAXA'];?>
						<b><?php echo $eolManager->getEmptyIdentifierCount(); ?></b>
						<div style="margin:10px;">
							<form name="taxamappingform" action="eolmapper.php" method="post">
								<input type="hidden" name="submitaction" value="Map Taxa" />
								<input type="submit" value="<?php echo $LANG['MAP_TAX']; ?>" />
								<div style="margin:15px;">
									<?php echo $LANG['TID_ESTART'];?> <input type="text" name="tidstart" value="" /><br />
									<input type="checkbox" name="restart" value="1" CHECKED /> <?php echo $LANG['RESTART_LAST'];?><br />
									<input type="checkbox" name="makeprimary" value="1" CHECKED /> <?php echo $LANG['MAKE_EOL'];?>
								</div>
							</form>
						</div>
					</div>
				</fieldset>
				<fieldset style="margin-top:15px;padding:15px;">
					<legend><b><?php echo $LANG['IMAGE_MAPP'];?></b></legend>
					<div>
						<?php echo $LANG['LEGEND2'];?>
					</div>
					<div style="margin:10px;">
						<?php echo $LANG['NUM_ACCEPTED'];?>
						<b><?php echo $eolManager->getImageDeficiencyCount(); ?></b>
						<div style="margin:10px;">
							<form name="imagemappingform" action="eolmapper.php" method="post">
								<?php echo $LANG['TID'];?> <input type="text" name="startindex" value="" /><br/>
								<input type="checkbox" name="restart" value="1" CHECKED /> <?php echo $LANG['RESTART_LAST'];?><br />
								<input type="hidden" name="submitaction" value="Map Images" />
								<input type="submit" value="<?php echo $LANG['MAP_IMAGE']; ?>" />
							</form>
						</div>
					</div>
				</fieldset>
			</div>
			<?php
		}
		else{
			echo '<div>'.$LANG['YOU_NEED_ADMIN'].'</div>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>
