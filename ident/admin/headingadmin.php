<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyCharAdmin.php');
include_once($SERVER_ROOT.'/content/lang/ident/admin/headingadmin.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/headingadmin.php?'.$_SERVER['QUERY_STRING']);

$hid = array_key_exists('hid',$_POST)?$_POST['hid']:0;
$langId = array_key_exists('langid',$_REQUEST)?$_REQUEST['langid']:'';
$action = array_key_exists('action',$_POST)?$_POST['action']:'';

$charManager = new KeyCharAdmin();
$charManager->setLangId($langId);

$isEditor = false;
if($IS_ADMIN || array_key_exists("KeyAdmin",$USER_RIGHTS)){
	$isEditor = true;
}

$statusStr = '';
if($isEditor && $action){
	if($action == 'Create'){
		$statusStr = $charManager->addHeading($_POST['headingname'],$_POST['notes'],$_POST['sortsequence']);
	}
	elseif($action == 'Save'){
		$statusStr = $charManager->editHeading($hid,$_POST['headingname'],$_POST['notes'],$_POST['sortsequence']);
	}
	elseif($action == 'Delete'){
		$statusStr = $charManager->deleteHeading($hid);
	}
}
$headingArr = $charManager->getHeadingArr();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $LANG['HEADING_ADMINISTRATION']; ?></title>
    <link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript">
		function validateHeadingForm(f){
			if(f.headingname.value == ""){
				alert(<?php echo $LANG['HEADING_MUST_HAVE_A_TITLE']; ?>);
				return false;
			}
			return true;
		}
	</script>
	<style type="text/css">
		input{ autocomplete: off; } 
	</style>
</head>
<body>
	<!-- This is inner text! -->
	<div style="width:700px">
		<?php 
		if($statusStr){
			?>
			<hr/>
			<div style="margin:15px;color:<?php echo (strpos($statusStr,'SUCCESS')===0?'green':'red'); ?>;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		}
		if($isEditor){
			?>
			<div id="addheadingdiv">
				<form name="newheadingform" action="headingadmin.php" method="post" onsubmit="return validateHeadingForm(this)">
					<fieldset>
						<legend><b><?php echo $LANG['NEW_HEADING']; ?></b></legend>
						<div>
							<?php echo $LANG['HEADING_NAME']; ?><br />
							<input type="text" name="headingname" maxlength="255" style="width:400px;" />
						</div>
						<div style="padding-top:6px;">
							<b><?php echo $LANG['NOTES']; ?></b><br />
							<input name="notes" type="text" style="width:500px;" />
						</div>
						<div style="padding-top:6px;">
							<b><?php echo $LANG['SORT_SEQUENCE']; ?></b><br />
							<input type="text" name="sortsequence" />
						</div>
						<div style="width:100%;padding-top:6px;">
							<input type="hidden" name="action" value="Create" />
							<input type="submit" value="<?php echo $LANG['CREATE_HEADING']; ?>" />
						</div>
					</fieldset>
				</form>
			</div>
			<div>
				<?php 
				if($headingArr){
					?>
					<fieldset>
						<legend><b><?php echo $LANG['EXISTING_HEADING']; ?></b></legend>
						<ul>
							<?php 
							foreach($headingArr as $headingId => $headArr){
								echo '<li><a href="#" onclick="toggle(\'headingedit-'.$headingId.'\');">'.$headArr['name'].' <img src="../../images/edit.png" style="width:13px" /></a></li>';
								?>
								<div id="headingedit-<?php echo $headingId; ?>" style="display:none;margin:20px;">
									<fieldset style="padding:15px;">
										<legend><b><?php echo $LANG['HEADING_EDITOR']; ?></b></legend>
										<form name="headingeditform" action="headingadmin.php" method="post" onsubmit="return validateHeadingForm(this)">
											<div style="margin:2px;">
												<b><?php echo $LANG['HEADING_NAME']; ?></b><br/>
												<input name="headingname" type="text" value="<?php echo $headArr['name']; ?>" style="width:400px;" />
											</div>
											<div style="margin:2px;">
												<b><?php echo $LANG['NOTES']; ?></b><br/>
												<input name="notes" type="text" value="<?php echo $headArr['notes']; ?>" style="width:500px;" />
											</div>
											<div style="margin:2px;">
												<b><?php echo $LANG['SORT_SEQUENCE']; ?></b><br/>
												<input name="sortsequence" type="text" value="<?php echo $headArr['sortsequence']; ?>" />
											</div>
											<div>
												<input name="hid" type="hidden" value="<?php echo $headingId; ?>" />
												<input type="hidden" name="action" value="Save" />
												<button type="submit" value="<?php echo $LANG['SAVE_EDITS']; ?>">
											</div>
										</form>
									</fieldset>
									<fieldset style="padding:15px;">
										<legend><b>Delete Heading</b></legend>
										<form name="headingdeleteform" action="headingadmin.php" method="post">
											<input name="hid" type="hidden" value="<?php echo $headingId; ?>" />
											<input type="hidden" name="action" value="Delete" />
											<input type="submit" value="<?php echo $LANG['DELETE_HEADING']; ?>" />
										</form>
									</fieldset>
								</div>
								<?php 
							}
							?>
						</ul>
					</fieldset>
					<?php 
				} else {
					echo '<div style="font-weight:bold;font-size:120%;">'.$LANG['THERE_ARE_NO_EXISTING_CHARACTER_HEADINGS'].'</div>';
				}
				?>
			</div>
			<?php 
		} else {
			echo '<h2>'.$LANG['YOU_ARE_NOT_AUTHORIZED_TO_ADD_CHARACTER'].'</h2>';
		}
		?>
	</div>
</body>
</html>