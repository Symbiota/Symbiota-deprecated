<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/LanguageAdmin.php');
include_once($SERVER_ROOT.'/content/lang/admin/langmanager.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../content/lang/admin/langmanager.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$refUrl = array_key_exists('refurl',$_REQUEST)?$_REQUEST['refurl']:'';

$langManager = new LanguageAdmin();

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
}

?>
<html>
	<head>
		<title>Language Variables Manager</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/bootstrap.min.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		?>
		<!--<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
			<b>Language Variable Management</b>
		</div>-->
		<!-- This is inner text! -->
		<div id="innertext">
			<div style="margin:20px"><b><?php echo $LANG['SOURCE_PATH'];?></b> <?php echo '<a href="'.$refUrl.'">'.$refUrl; ?></a></div>
			<div style="margin:20px">
				<table class="styledtable">
					<tr>
						<th><?php echo $LANG['VARCODE'];?></th>
						<th><?php echo $LANG['EN'];?></th>
						<?php
						$langArr = $langManager->getLanguageVariables($refUrl);
						$enArr = array();
						if(isset($langArr['es'])){
							$enArr = $langArr['es'];
							unset($langArr['es']);
							$otherCodes = array_keys($langArr);
							foreach($otherCodes as $code){
								echo '<th>'.$code.'</th>';
							}
						}
						?>
					</tr>
					<tr>
						<th><?php echo $LANG['VARCODE'];?></th>
						<th><?php echo $LANG['ES'];?></th>
						<?php
						$langArr = $langManager->getLanguageVariables($refUrl);
						$enArr = array();
						if(isset($langArr['en'])){
							$enArr = $langArr['en'];
							unset($langArr['en']);
							$otherCodes = array_keys($langArr);
							foreach($otherCodes as $code){
								echo '<th>'.$code.'</th>';
							}
						}
						?>
					</tr>
					<?php
					foreach($enArr as $varCode => $varValue){
						echo '<tr>';
						echo '<td>'.$varCode.'</td><td>'.$varValue.'</td>';
						foreach($otherCodes as $langCode){
							echo '<td>';
							if(isset($langArr[$langCode][$varCode])){
								echo $langArr[$langCode][$varCode];
							}
							else{
								echo '&nbsp;';
							}
							echo '</td>';
						}
						echo '</tr>';
					}
					?>
				</table>
			</div>
		</div>
		<?php
		include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
