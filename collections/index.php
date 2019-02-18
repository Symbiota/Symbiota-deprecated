<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!is_numeric($catId)) $catId = 0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$collManager = new OccurrenceManager();
//$collManager->reset();

$collList = $collManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

$otherCatArr = $collManager->getOccurVoucherProjects();
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['PAGE_TITLE']; ?>

		</title>

		<!-- CSS bootstrap -->

	<link href="../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<!--inicio favicon -->
	<link rel="shortcut icon" href="../images/favicon.png" type="image/x-icon">

		<script src="../js/jquery.js" type="text/javascript"></script>
		<script src="../js/jquery-ui.js" type="text/javascript"></script>
		<script src="../js/symb/collections.index.js?ver=1" type="text/javascript"></script>
		<script type="text/javascript">
			<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
		</script>
	</head>
	<body>

	<?php
	$displayLeftMenu = (isset($collections_indexMenu)?$collections_indexMenu:false);
	include($SERVER_ROOT."/header.php");
	if(isset($collections_indexCrumbs)){
		if($collections_indexCrumbs){
			echo "<div class='navpath'>";
			echo $collections_indexCrumbs;
			echo ' <b>'.$LANG['NAV_COLLECTIONS'].'</b>';
			echo "</div>";
		}
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../index.php">'.$LANG['NAV_HOME'].'</a> &gt;&gt; ';
		echo '<b>'.$LANG['NAV_COLLECTIONS'].'</b>';
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
        <div id="tabs" style="margin:0px;">
			<ul>
				<?php
				if($specArr && $obsArr) echo '<li><a href="#specobsdiv">'.$LANG['TAB_1'].'</a></li>';
				if($specArr) echo '<li><a href="#specimendiv">'.$LANG['TAB_2'].'</a></li>';
				if($obsArr) echo '<li><a href="#observationdiv">'.$LANG['TAB_3'].'</a></li>';
				if($otherCatArr) echo '<li><a href="#otherdiv">'.$LANG['TAB_4'].'</a></li>';
				?>
			</ul>
			<?php
			if($specArr && $obsArr){
				?>
				<div id="specobsdiv">
					<form name="collform1" action="harvestparams.php" method="post" onsubmit="return verifyCollForm(this)">
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" checked />
					 		<?php echo $LANG['SELECT_ALL']; ?>
						</div>
						<?php
						$collManager->outputFullCollArr($specArr, $catId);
						if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
						$collManager->outputFullCollArr($obsArr, $catId);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
			<?php
			}
			if($specArr){
				?>
				<div id="specimendiv">
					<form name="collform2" action="harvestparams.php" method="post" onsubmit="return verifyCollForm(this)">
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballspeccb" name="db[]" class="spec" value='allspec' type="checkbox" onclick="selectAll(this);" checked />
					 		<?php echo $LANG['SELECT_ALL']; ?>
						</div>
						<?php
						$collManager->outputFullCollArr($specArr, $catId);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
				<?php
			}
			if($obsArr){
				?>
				<div id="observationdiv">
					<form name="collform3" action="harvestparams.php" method="post" onsubmit="return verifyCollForm(this)">
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballobscb" name="db[]" class="obs" value='allobs' type="checkbox" onclick="selectAll(this);" checked />
					 		<?php echo $LANG['SELECT_ALL']; ?>
						</div>
						<?php
						$collManager->outputFullCollArr($obsArr, $catId);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
				<?php
			}
			if($otherCatArr && isset($otherCatArr['titles'])){
				$catTitleArr = $otherCatArr['titles']['cat'];
				asort($catTitleArr);
				?>
				<div id="otherdiv">
					<form id="othercatform" action="harvestparams.php" method="post" onsubmit="return verifyOtherCatForm(this)">
						<?php
						foreach($catTitleArr as $catPid => $catTitle){
							?>
							<fieldset style="margin:10px;padding:10px;">
								<legend style="font-weight:bold;"><?php echo $catTitle; ?></legend>
								<div style="margin:0px 15px;float:right;">
									<input type="submit" class="nextbtn searchcollnextbtn" value="<?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?>" />
								</div>
								<?php
								$projTitleArr = $otherCatArr['titles'][$catPid]['proj'];
								asort($projTitleArr);
								foreach($projTitleArr as $pid => $projTitle){
									?>
									<div>
										<a href="#" onclick="togglePid('<?php echo $pid; ?>');return false;"><img id="plus-pid-<?php echo $pid; ?>" src="../images/plus_sm.png" /><img id="minus-pid-<?php echo $pid; ?>" src="../images/minus_sm.png" style="display:none;" /></a>
										<input name="pid[]" type="checkbox" value="<?php echo $pid; ?>" onchange="selectAllPid(this);" />
										<b><?php echo $projTitle; ?></b>
									</div>
									<div id="pid-<?php echo $pid; ?>" style="margin:10px 15px;display:none;">
										<?php
										$clArr = $otherCatArr[$pid];
										asort($clArr);
										foreach($clArr as $clid => $clidName){
											?>
											<div>
												<input name="clid[]" class="pid-<?php echo $pid; ?>" type="checkbox" value="<?php echo $clid; ?>" />
												<?php echo $clidName; ?>
											</div>
											<?php
										}
										?>
									</div>
									<?php
								}
								?>
							</fieldset>
							<?php
						}
						?>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT."/footer.php");
	?>
	</body>
</html>
