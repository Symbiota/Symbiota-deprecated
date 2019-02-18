<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SalixUtilities.php');
include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/salix/salixhandler.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID){
	header('Location: ../../../profile/index.php?refurl=../collections/specprocessor/salix/salixhandler.php?'.$_SERVER['QUERY_STRING']);
}

$action = (isset($_REQUEST['formsubmit'])?$_REQUEST['formsubmit']:'');
$verbose = (isset($_REQUEST['verbose'])?$_REQUEST['verbose']:1);
$collid = (isset($_REQUEST['collid'])?$_REQUEST['collid']:0);
$actionType = (isset($_REQUEST['actiontype'])?$_REQUEST['actiontype']:1);
$limit = (isset($_REQUEST['limit'])?$_REQUEST['limit']:100000);

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
	}
}
?>
<!DOCTYPE html >
<html>
	<head>
		<title><?php echo $LANG['SALIX_WORDSTAT_MANAGER']; ?></title>
		<link href="<?php echo $clientRoot; ?>/css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="<?php echo $clientRoot; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="<?php echo $clientRoot; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $clientRoot; ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			function verifySalixManagerForm(this){

				return true;
			}
		</script>
		<script src="<?php echo $clientRoot; ?>/js/symb/shared.js?ver=140310" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<div class='navpath'>
			<a href="../../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
			<?php
			if($collid){
				?>
				<a href="../../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['COLLECTION_MANAGEMENT']; ?></a> &gt;&gt;
				<?php
			}
			else{
				?>
				<a href="../../../sitemap.php"><?php echo $LANG['SITEMAP']; ?></a> &gt;&gt;
				<?php
			}
			echo '<a href="salixhandler.php?collid='.$collid.'&actiontype='.$actionType.'&limit='.$limit.'">';
			echo '<b>'.$LANG['SALIX_WORDSTAT_MANAGER'].'</b>';
			echo '</a>';
			?>
		</div>

		<!-- This is inner text! -->
		<div id="innertext">
			<?php
			if($isEditor){
				$salixHanlder = new SalixUtilities();
				$salixHanlder->setVerbose($verbose);
				if($action == 'Build Wordstat Tables'){
					$salixHanlder->buildWordStats($collid,$actionType,$limit);
					echo '<div style="margin:15px;"><a href="salixhandler.php?collid='.$collid.'&actiontype='.$actionType.'&limit='.$limit.'">'.$LANG['RETURN_TO_MAIN_MENU'].'</a></div>';
				}
				else{
					?>
					<fieldset style="border:10px;">
						<legend></legend>
						<form name="salixmanagerform" action="salixhandler.php" method="post" onsubmit="return verifySalixManagerForm(this)">
							<div style="margin:15px;">
								<b><?php echo $LANG['ACTIONS'];?></b><br/>
								<input name="actiontype" type="radio" value="1" /> <?php echo $LANG['REB_RANDOM'];?><br/>
								<input name="actiontype" type="radio" value="2" /> <?php echo $LANG['REB_RECENT'];?><br/>
								<input name="actiontype" type="radio" value="3" checked /> <?php echo $LANG['APP_USE'];?> (<?php echo $salixHanlder->getLastBuildTimestamp(); ?>)<br/><br/>
								<?php echo $LANG['LIMIT_TO'];?> <input name="limit" type="text" value="100000" /> <?php echo $LANG['UNIQUE_VAL'];?>
							</div>
							<div style="margin:15px;">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input type="hidden" name="formsubmit" value="Build Wordstat Tables" />
								<input type="submit" value="<?php echo $LANG['BUILD_WORDSTAT_TABLES']; ?>" />
							</div>
						</form>
					</fieldset>
					<?php
				}
			}
			else{
				echo '<div style="margin:25px;font-weight">'.$LANG['YOU_ARE_NOT_AUTHORIZED'].'</div>';
			}
			?>
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
