<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyCharAdmin.php');
include_once($SERVER_ROOT.'/content/lang/ident/admin/index.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/index.php?'.$_SERVER['QUERY_STRING']);

$langId = array_key_exists('langid',$_REQUEST)?$_REQUEST['langid']:'';

$charManager = new KeyCharAdmin();
$charManager->setLangId($langId);

$charArr = $charManager->getCharacterArr();
$headingArr = $charManager->getHeadingArr();

$isEditor = false;
if($isAdmin || array_key_exists("KeyAdmin",$USER_RIGHTS)){
	$isEditor = true;
}

?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $LANG['CHARACTER_ADMIN']; ?></title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
    <link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript">
		function validateNewCharForm(f){
			if(f.charname.value == ""){
				alert(<?php echo $LANG['CHARACTER_NAME_MUST_HAVE_A_VALUE']; ?>);
				return false;
			}
			if(f.chartype.value == ""){
				alert(<?php echo $LANG['A_CHARACTER_TYPE_MUST_BE_SELECTED']; ?>);
				return false;
			}
			if(f.sortsequence.value && !isNumeric(f.sortsequence.value)){
				alert(<?php echo $LANG['SORT_SEQUENCE_MUST_BE_A_NUMERIC_VALUE_ONLY']; ?>);
				return false;
			}
			return true;
		}

		function openHeadingAdmin(){
			newWindow = window.open("headingadmin.php","headingWin","scrollbars=1,toolbar=1,resizable=1,width=800,height=600,left=50,top=50");
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
	<style type="text/css">
		input{ autocomplete: off; }
	</style>
</head>
<body>
	<?php
	include($SERVER_ROOT."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<b><?php echo $LANG['CHARACTER_MANAGEMENT']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			?>
			<div id="addeditchar">
				<div style="float:right;margin:10px;">
					<a href="#" onclick="toggle('addchardiv');">
						<img src="../../images/add.png" alt="<?php echo $LANG['CRETAE_NEW_CHARACTER']; ?>" />
					</a>
				</div>
				<div id="addchardiv" style="display:none;margin-bottom:8px;">
					<form name="newcharform" action="chardetails.php" method="post" onsubmit="return validateNewCharForm(this)">
						<fieldset style="padding:10px;">
							<legend><b><?php echo $LANG['NEW_CHARACTER'];?></b></legend>
							<div>
								<?php echo $LANG['CHARACTER_NAME'];?><br />
								<input type="text" name="charname" maxlength="255" style="width:400px;" />
							</div>
							<div style="padding-top:6px;">
								<div style="float:left;">
									<?php echo $LANG['TYPE'];?><br />
									<select name="chartype" style="width:180px;">
										<option value="UM"><?php echo $LANG['UNORDERED_MULTI_STATE']; ?></option>
									</select>
								</div>
								<div style="margin-left:30px;float:left;">
									<?php echo $LANG['DIFFICULTY'];?><br />
									<select name="difficultyrank" style="width:100px;">
										<option value="">---------------</option>
										<option value="1"><?php echo $LANG['EASY']; ?></option>
										<option value="2"><?php echo $LANG['INTERMEDIATE']; ?></option>
										<option value="3"><?php echo $LANG['ADVANCED']; ?></option>
										<option value="4"><?php echo $LANG['HIDDEN']; ?></option>
									</select>
								</div>
								<div style="margin-left:30px;float:left;">
									<?php echo $LANG['HEADING'];?><br />
									<select name="hid" style="width:125px;">
										<option value=""><?php echo $LANG['NO_HEADING']; ?></option>
										<option value="">---------------------</option>
										<?php
										$hArr = $headingArr;
										asort($hArr);
										foreach($hArr as $k => $v){
											echo '<option value="'.$k.'">'.$v['name'].'</option>';
										}
										?>
									</select>
									<a href="#" onclick="openHeadingAdmin(); return false;"><img src="../../images/edit.png" /></a>
								</div>
							</div>
							<div style="padding-top:6px;clear:both;">
								<b><?php echo $LANG['SOT_SEQUENSE'];?></b><br />
								<input type="text" name="sortsequence" />
							</div>
							<div style="width:100%;padding-top:6px;">
								<input type="hidden" name="formsubmit" value="Create" />
								<input type="submit" value="<?php echo $LANG['Create']; ?>" />
							</div>
						</fieldset>
					</form>
				</div>
				<div id="charlist" style="padding-left:10px;">
					<?php
					if($charArr){
						?>
						<h3><?php echo $LANG['CHARACTERS_BY_HEADING']; ?></h3>
						<ul>
							<?php
							foreach($headingArr as $hid => $hArr){
								if(array_key_exists($hid, $charArr)){
									?>
									<li>
										<a href="#" onclick="toggle('char-<?php echo $hid; ?>');return false;"><b><?php echo $hArr['name']; ?></b></a>
										<div id="char-<?php echo $hid; ?>" style="display:block;">
											<ul>
												<?php
												$charList = $charArr[$hid];
												foreach($charList as $cid => $charName){
													echo '<li>';
													echo '<a href="chardetails.php?cid='.$cid.'">'.$charName.'</a>';
													echo '</li>';
												}
												?>
											</ul>
										</div>
									</li>
									<?php
								}
							}
							if(array_key_exists(0, $charArr)){
								$noHeaderArr = $charArr[0];
								?>
								<li>
									<a href="#" onclick="toggle('char-0');return false;"><b><?php echo $LANG['NO_ASSIGNED_HEADER']; ?></b></a>
									<div id="char-0" style="display:block;">
										<ul>
											<?php
											foreach($noHeaderArr as $cid => $charName){
												echo '<li>';
												echo '<a href="chardetails.php?cid='.$cid.'">'.$charName.'</a>';
												echo '</li>';
											}
											?>
										</ul>
									</div>
								</li>
								<?php
							}
							?>
						</ul>
					<?php
					}
					else{
						echo '<div style="font-weight:bold;font-size:120%;">'.$LANG['NOT_CHARACTER'].'</div>';
					}
					?>
				</div>
			</div>
			<?php
		}
		else{
			echo '<h2>'.$LANG['YOU_ARE_NOT_AUTHORIZED_TO_ADD_CHARACTERS'].'</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>
