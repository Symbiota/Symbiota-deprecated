<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/InstitutionManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/institutioneditor.'.$LANG_TAG.'.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/institutioneditor.php?'.$_SERVER['QUERY_STRING']);
$iid = array_key_exists("iid",$_REQUEST)?$_REQUEST["iid"]:0;
$targetCollid = array_key_exists("targetcollid",$_REQUEST)?$_REQUEST["targetcollid"]:0;
$eMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0;
$instCodeDefault = array_key_exists("instcode",$_REQUEST)?$_REQUEST["instcode"]:'';
$formSubmit = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:"";
$instManager = new InstitutionManager();
$fullCollList = $instManager->getCollectionList();
if($iid){
	$instManager->setInstitutionId($iid);
}
//Get list of collection that are linked to this institutions
$collList = array();
foreach($fullCollList as $k => $v){
	if($v['iid'] == $iid) $collList[$k] = $v['name'];
}
$editorCode = 0;
$statusStr = '';
if($IS_ADMIN){
	$editorCode = 3;
}
elseif(array_key_exists("CollAdmin",$USER_RIGHTS)){
	$editorCode = 1;
	if($collList && array_intersect($USER_RIGHTS["CollAdmin"],array_keys($collList))){
		$editorCode = 2;
	}
}
if($editorCode){
	if($formSubmit == "Add Institution"){
		$iid = $instManager->submitInstitutionAdd($_POST);
		if($iid){
			if($targetCollid) header('Location: ../misc/collprofiles.php?collid='.$targetCollid);
		}
		else{
			$statusStr = $instManager->getErrorStr();
		}
	}
	else{
		if($editorCode > 1){
			if($formSubmit == "Update Institution Address"){
				if($instManager->submitInstitutionEdits($_POST)){
					if($targetCollid) header('Location: ../misc/collprofiles.php?collid='.$targetCollid);
				}
				else{
					$statusStr = $instManager->getErrorStr();
				}
			}
			elseif(isset($_POST['deliid'])){
				$delIid = $_POST['deliid'];
				if($instManager->deleteInstitution($delIid)){
					$statusStr = 'SUCCESS! Institution deleted.';
					$iid = 0;
				}
				else{
					$statusStr = $instManager->getErrorStr();
				}
			}
			elseif($formSubmit == "Add Collection"){
				if($instManager->addCollection($_POST['addcollid'],$iid)){
					$collList[$_POST['addcollid']] = $fullCollList[$_POST['addcollid']]['name'];
				}
				else{
					$statusStr = $instManager->getErrorStr();
				}
			}
			elseif(isset($_GET['removecollid'])){
				if($instManager->removeCollection($_GET['removecollid'])){
					$statusStr = 'SUCCESS! Institution removed';
					unset($collList[$_GET['removecollid']]);
				}
				else{
					$statusStr = $instManager->getErrorStr();
				}
			}
		}
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle . " " . $LANG['INSTITUTION_EDITOR']; ?></title>
	<link type="text/css" href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" rel="stylesheet" />
	<link type="text/css" href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" rel="stylesheet" />
	<link type="text/css" href="../../css/bootstrap.min.css" rel="stylesheet" />
	<script language=javascript>

		function toggle(target){
			var tDiv = document.getElementById(target);
			if(tDiv != null){
				if(tDiv.style.display=="none"){
					tDiv.style.display="block";
				}
				else {
					tDiv.style.display="none";
				}
			}
			else{
				var divs = document.getElementsByTagName("div");
				for (var i = 0; i < divs.length; i++) {
				var divObj = divs[i];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
						else {
							divObj.style.display="none";
						}
					}
				}
			}
		}
		function validateAddCollectionForm(f){
			if(f.addcollid.value == ""){
				alert(<? php echo $LANG['SELECT_A_COLLECTION_TO_BE_ADDED']; ?>);
				return false;
			}
			return true;
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($collections_admin_institutioneditor)?$collections_admin_institutioneditor:true);
include($serverRoot.'/header.php');
?>
<div class='navpath'>
	<a href='../../index.php'><?php echo $LANG['HOME']; ?></a> &gt;&gt;
	<?php
	if(!$targetCollid && count($collList) == 1){
		$targetCollid = key($collList);
	}
	if($targetCollid){
		echo '<a href="../misc/collprofiles.php?collid='.$targetCollid.'&emode=1">'.$collList[$targetCollid].' '.$LANG['MANAGEMENT'].'</a> &gt;&gt;';
	}
	else{
		echo '<a href="institutioneditor.php">'.$LANG['FULL_ADDRESS_LIST'].'</a> &gt;&gt;';
	}
	?>
	<b><?php echo $LANG['INSTITUTION_EDITOR']; ?></b>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($statusStr){
		?>
		<hr />
		<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green'); ?>;">
			<?php echo $statusStr; ?>
		</div>
		<hr />
		<?php
	}
	if($iid){
		if($instArr = $instManager->getInstitutionData()){
			?>
			<div style="float:right;">
				<a href="institutioneditor.php">
					<img src="<?php echo $clientRoot;?>/images/toparent.png" style="width:15px;border:0px;" title="<?php echo $LANG['RETURN_TO_INTITUTION_LIST']; ?>" />
				</a>
				<?php
				if($editorCode > 1){
					?>
					<a href="#" onclick="toggle('editdiv');">
						<img src="<?php echo $clientRoot;?>/images/edit.png" style="width:15px;border:0px;" title="<?php echo $LANG['EDIT_INSTITUTION']; ?>" />
					</a>
					<?php
				}
				?>
			</div>
			<div style="clear:both;">
				<form name="insteditform" action="institutioneditor.php" method="post">
					<fieldset style="padding:20px;">
						<legend><b><?php echo $LANG['ADDRESS_DETAILS'];?></b></legend>
						<div style="position:relative;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['COD'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutioncode']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutioncode" type="text" value="<?php echo $instArr['institutioncode']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['NAME_INST'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutionname']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutionname" type="text" value="<?php echo $instArr['institutionname']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['NAME_INST_2'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutionname2']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutionname2" type="text" value="<?php echo $instArr['institutionname2']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['DIR_1'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['address1']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="address1" type="text" value="<?php echo $instArr['address1']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['DIR_2'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['address2']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="address2" type="text" value="<?php echo $instArr['address2']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['CITY'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['city']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="city" type="text" value="<?php echo $instArr['city']; ?>" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['PROV'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['stateprovince']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="stateprovince" type="text" value="<?php echo $instArr['stateprovince']; ?>" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['COD_POS'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['postalcode']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="postalcode" type="text" value="<?php echo $instArr['postalcode']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['COUNTRY'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['country']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="country" type="text" value="<?php echo $instArr['country']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['TELF'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['phone']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="phone" type="text" value="<?php echo $instArr['phone']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['CONTACT'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['contact']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="contact" type="text" value="<?php echo $instArr['contact']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['E_MAIL'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['email']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="email" type="text" value="<?php echo $instArr['email']; ?>" style="width:150px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['PAGE_W'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<a href="<?php echo $instArr['url']; ?>" target="_blank">
									<?php echo $instArr['url']; ?>
								</a>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="url" type="text" value="<?php echo $instArr['url']; ?>" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['NOTES'];?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['notes']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="notes" type="text" value="<?php echo $instArr['notes']; ?>" style="width:400px" />
							</div>
						</div>
						<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;clear:both;margin:30px 0px 0px 20px;">
							<input type="hidden" name="formsubmit" value="Update Institution Address" />
							<input type="submit" value="<?php echo $LANG['UPDATE_INTITUTION_ADDRESS']; ?>" />
							<input name="iid" type="hidden" value="<?php echo $iid; ?>" />
							<input name="targetcollid" type="hidden" value="<?php echo $targetCollid; ?>" />
						</div>
					</fieldset>
				</form>
				<div style="clear:both;">
					<fieldset style="padding:20px;">
						<legend><b><?php echo $LANG['COL_LINK_INST'];?></b></legend>
						<div>
							<?php
							if($collList){
								foreach($collList as $id => $collName){
									echo '<div style="margin:5px;font-weight:bold;clear:both;height:15px;">';
									echo '<div style="float:left;"><a href="../misc/collprofiles.php?collid='.$id.'">'.$collName.'</a></div> ';
									if($editorCode == 3 || in_array($id,$USER_RIGHTS["CollAdmin"]))
										echo ' <div class="editdiv" style="margin-left:10px;display:'.($eMode?'':'none').'"><a href="institutioneditor.php?iid='.$iid.'&removecollid='.$id.'"><img src="../../images/del.png" style="width:15px;"/></a></div>';
									echo '</div>';
								}
							}
							else{
								echo '<div style="margin:25px;"><b>'.$LANG['NO_LINK_INST'].'</b></div>';
							}
							?>
						</div>
						<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
							<div style="margin:15px;clear:both;">* <?php echo $LANG['CLICK_ON_RED_X_TO_UNLINK_COLLECTION']; ?></div>
							<?php
							//Don't show collection that already linked and only show one that user can admin
							$addList = array();
							foreach($fullCollList as $collid => $collArr){
								if($collArr['iid'] != $iid){
									if($IS_ADMIN || (isset($USER_RIGHTS["CollAdmin"]) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
										$addList[$collid] = $collArr;
									}
								}
							}
							if($addList){
								?>
								<hr />
								<form name="addcollectionform" method="post" action="institutioneditor.php" onsubmit="return validateAddCollectionForm(this)">
									<select name="addcollid" style="width:400px;">
										<option value=""><?php echo $LANG['SELECT_COLLECTION_TO_ADD']; ?></option>
										<option value="">------------------------------------</option>
										<?php
										foreach($addList as $collid => $collArr){
											echo '<option value="'.$collid.'">'.$collArr['name'].'</option>';
										}
										?>
									</select>
									<input name="iid" type="hidden" value="<?php echo $iid; ?>" />
									<input type="hidden" name="formsubmit" value="Add Collection" />
									<input type="submit" value="<?php echo $LANG['ADD_COLLECTION']; ?>" />
								</form>
								<?php
							}
							?>
						</div>
					</fieldset>
					<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
						<fieldset style="padding:20px;">
							<legend><b><?php echo $LANG['DELET']; ?></b></legend>
							<form name="instdelform" action="institutioneditor.php" method="post" onsubmit="return confirm('<?php echo $LANG['ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_INSTITUTION']; ?>')">
								<div style="position:relative;clear:both;">
									<input type="hidden" name="formsubmit" value="Delete Institution" />
									<input type="submit" value="<?php echo $LANG['DELETE_INSTITUTION']; ?>" <?php if($collList) echo 'disabled'; ?> />
									<input name="deliid" type="hidden" value="<?php echo $iid; ?>" />
									<?php
									if($collList) echo '<div style="margin:15px;color:red;">'.$LANG['DELETION_OF_ADDRESSES_THAT_HAVE_LINKED_COLLECTIONS'].'</div>';
									?>
								</div>
							</form>
						</fieldset>
					</div>
				</div>
			</div>
			<?php
		}
	}
	else{
		if($editorCode){
			?>
			<div style="float:right;">
				<a href="#" onclick="toggle('instadddiv');">
					<img src="<?php echo $clientRoot;?>/images/add.png" style="width:15px;border:0px;" title="<?php echo $LANG['ADD_A_NEW_INSTITUTION']; ?>" />
				</a>
			</div>
			<div id="instadddiv" style="display:<?php echo ($eMode?'block':'none'); ?>;margin-bottom:8px;">
				<form name="instaddform" action="institutioneditor.php" method="post">
					<fieldset style="padding:20px;">
						<legend><b><?php echo $LANG['ADD_INSTITUTION'];?><?php echo $LANG['ADD_A_NEW_INSTITUTION']; ?></b></legend>
						<div style="position:relative;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['COD'];?>
							</div>
							<div>
								<input name="institutioncode" type="text" value="<?php echo $instCodeDefault; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['NAME_INST']; ?>
							</div>
							<div>
								<input name="institutionname" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['NAME_INST_2']; ?>
							</div>
							<div>
								<input name="institutionname2" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['DIR_1']; ?>
							</div>
							<div>
								<input name="address1" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['DIR_2"']; ?>
							</div>
							<div>
								<input name="address2" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['CITY']; ?>
							</div>
							<div>
								<input name="city" type="text" value="" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['PROV']; ?>
							</div>
							<div>
								<input name="stateprovince" type="text" value="" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['COD_POS']; ?>
							</div>
							<div>
								<input name="postalcode" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['COUNTRY']; ?>
							</div>
							<div>
								<input name="country" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['TELF']; ?>
							</div>
							<div>
								<input name="phone" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['CONTACT']; ?>
							</div>
							<div>
								<input name="contact" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['E_MAIL']; ?>
							</div>
							<div>
								<input name="email" type="text" value="" style="width:150px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['PAGE_W']; ?>
							</div>
							<div>
								<input name="url" type="text" value="" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['NOTES']; ?>
							</div>
							<div>
								<input name="notes" type="text" value="" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:255px;font-weight:bold;">
								<?php echo $LANG['LINK_TO']; ?>
							</div>
							<div>
								<select name="targetcollid" style="width:400px;">
									<option value=""><?php echo $LANG['LEAVE_ORPHANED']; ?></option>
									<option value="">--------------------------------------</option>
									<?php
									foreach($fullCollList as $collid => $collArr){
										//Don't show collection that already linked and only show one that user can admin
										if($collArr['iid'] && ($IS_ADMIN || ($USER_RIGHTS["CollAdmin"] && in_array($collid,$USER_RIGHTS["CollAdmin"])))){
											echo '<option value="'.$collid.'"'.($collid == $targetCollid?'SELECTED':'').'>'.$collArr['name'].'</option>';
										}
									}
									?>
								</select>
							</div>
						</div>
						<div style="margin:20px;clear:both;">
							<input type="hidden" name="formsubmit" value="Add Institution" />
							<input type="submit" value="<?php echo $LANG['ADD_INSTITUTION']; ?>" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php
			if(!$eMode){
				?>
				<div style="padding-left:10px;">
					<h2><?php echo $LANG['SELECT_INSTITUTION']; ?></h2>
					<ul>
						<?php
						$instList = $instManager->getInstitutionList();
						if($instList){
							foreach($instList as $iid => $iArr){
								echo '<li><a href="institutioneditor.php?iid='.$iid.'">';
								echo $iArr['institutionname'].' ('.$iArr['institutioncode'].')';
								if($editorCode == 3 || array_intersect(explode(',',$iArr['collid']),$USER_RIGHTS["CollAdmin"])){
									echo ' <a href="institutioneditor.php?emode=1&iid='.$iid.'"><img src="'.$clientRoot.'/images/edit.png" style="width:13px;" /></a>';
								}
								echo '</a></li>';
							}
						}
						else{
							echo "<div>".$LANG['THERE_ARE_NO_INSTITUTIONS_YOU_HAVE']."</div>";
						}
						?>
					</ul>
				</div>
				<?php
			}
		}
		else{
			echo "<div>".$LANG['YOU_NEED_TO_HAVE_ADMINISTRATIVE_USER']."</div>";
		}
	}
	?>
</div>
<?php
include($serverRoot.'/footer.php');
?>
</body>
</html>
