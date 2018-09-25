<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/PermissionsManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$permManager = new PermissionsManager();

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
		$isEditor = 1;
	}
}

if($isEditor){
	if(array_key_exists('deladmin',$_GET)){
		$permManager->deletePermission($_GET['deladmin'],'CollAdmin',$collId);
	}
	elseif(array_key_exists('deleditor',$_GET)){
		$permManager->deletePermission($_GET['deleditor'],'CollEditor',$collId);
	}
	elseif(array_key_exists('delrare',$_GET)){
		$permManager->deletePermission($_GET['delrare'],'RareSppReader',$collId);
	}
	elseif(array_key_exists('delidenteditor',$_GET)){
		$permManager->deletePermission($_GET['delidenteditor'],'CollTaxon',$collId,$_GET['utid']);
		if(is_numeric($_GET['utid'])){
			$permManager->deletePermission($_GET['delidenteditor'],'CollTaxon',$collId,'all');
		}
	}
	elseif($action == 'Add Permissions for User'){
		$rightType = $_POST['righttype'];
		if($rightType == 'admin'){
			$permManager->addPermission($_POST['uid'],"CollAdmin",$collId);
		}
		elseif($rightType == 'editor'){
			$permManager->addPermission($_POST['uid'],"CollEditor",$collId);
		}
		elseif($rightType == 'rare'){
			$permManager->addPermission($_POST['uid'],"RareSppReader",$collId);
		}
	}
	elseif($action == 'Add Identification Editor'){
		$identEditor = $_POST['identeditor'];
		$pTokens = explode(':',$identEditor);
		$permManager->addPermission($pTokens[0],'CollTaxon',$collId,$pTokens[1]);
		//$permManager->addPermission($pTokens[0],'CollTaxon-'.$collId.':'.$pTokens[1]);
	}
	elseif($action == 'Sponsor User'){
		$permManager->addPermission($_POST['uid'],'CollEditor',$_POST['persobscollid']);
	}
	elseif(array_key_exists('delpersobs',$_GET)){
		$permManager->deletePermission($_GET['delpersobs'],'CollEditor',$_GET['persobscollid']);
	}
}
$collMetadata = current($permManager->getCollectionMetadata($collId));
$isGenObs = 0;
if($collMetadata['colltype'] == 'General Observations') $isGenObs = 1;
?>
<html>
<head>
	<title><?php echo $collMetadata['collectionname']; ?> Collection Permissions</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script>
		function verifyAddRights(f){
			if(f.uid.value == ""){
				alert("Please select a user from list");
				return false;
			}
			else if(f.righttype && f.righttype.value == ""){
				alert("Please select the permissions you wish to assign this user");
				return false;
			}
			else if(f.persobscollid && f.persobscollid.value == ""){
				alert("Please select a Personal Observation Management project");
				return false;
			}
			return true;
		}
	</script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collpermissionsMenu)?$collections_misc_collpermissionsMenu:true);
	include($SERVER_ROOT.'/header.php');
	if(isset($collections_misc_collpermissionsCrumbs)){
		if($collections_misc_collpermissionsCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
			echo $collections_misc_collpermissionsCrumbs;
			echo " <b>".($collMetadata['collectionname']?$collMetadata['collectionname']:"Collection Profiles")."</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt;
			<a href='collprofiles.php?emode=1&collid=<?php echo $collId; ?>'>Collection Management</a> &gt;&gt;
			<b><?php echo $collMetadata['collectionname'].' Permissions'; ?></b>
		</div>
		<?php
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			$collPerms = $permManager->getCollectionEditors($collId);
			if(!$isGenObs){
				?>
				<fieldset style="margin:15px;padding:15px;">
					<legend><b>Administrators</b></legend>
					<?php
					if(array_key_exists('admin',$collPerms)){
						?>
						<ul>
						<?php
						$adminArr = $collPerms['admin'];
						foreach($adminArr as $uid => $uName){
							?>
							<li>
								<?php echo $uName; ?>
								<a href="collpermissions.php?collid=<?php echo $collId.'&deladmin='.$uid; ?>" onclick="return confirm('Are you sure you want to remove administrative rights for this user?');" title="Delete permissions for this user">
									<img src="../../images/drop.png" style="width:12px;" />
								</a>
							</li>
							<?php
						}
						?>
						</ul>
						<?php
					}
					else{
						echo '<div style="font-weight:bold;">';
						echo 'There are no administrative permissions (excluding Super Admins)';
						echo '</div>';
					}
					?>
				</fieldset>
				<?php
			}
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend><b>Editors</b></legend>
				<?php
				if(array_key_exists('editor',$collPerms)){
					?>
					<ul>
					<?php
					$editorArr = $collPerms['editor'];
					foreach($editorArr as $uid => $uName){
						?>
						<li>
							<?php echo $uName; ?>
							<a href="collpermissions.php?collid=<?php echo $collId.'&deleditor='.$uid; ?>" onclick="return confirm('Are you sure you want to remove editing rights for this user?');" title="Delete permissions for this user">
								<img src="../../images/drop.png" style="width:12px;" />
							</a>
						</li>
						<?php
					}
					?>
					</ul>
					<?php
				}
				else{
					echo '<div style="font-weight:bold;">';
					echo 'There are no general Editor permissions';
					echo '</div>';
				}
				?>
				<div style="margin:10px">
					*Administrators automatically inherit editing rights
				</div>
			</fieldset>
			<?php
			if(!$isGenObs){
				?>
				<fieldset style="margin:15px;padding:15px;">
					<legend><b>Rare Species Readers</b></legend>
					<?php
					if(array_key_exists('rarespp',$collPerms)){
						?>
						<ul>
						<?php
						$rareArr = $collPerms['rarespp'];
						foreach($rareArr as $uid => $uName){
							?>
							<li>
								<?php echo $uName; ?>
								<a href="collpermissions.php?collid=<?php echo $collId.'&delrare='.$uid; ?>" onclick="return confirm('Are you sure you want to remove user rights to view locality details for rare species?');" title="Delete permissions for this user">
									<img src="../../images/drop.png" style="width:12px;" />
								</a>
							</li>
							<?php
						}
						?>
						</ul>
						<?php
					}
					else{
						echo '<div style="font-weight:bold;">';
						echo 'There are no Sensitive Species Reader permissions';
						echo '</div>';
					}
					?>
					<div style="margin:10px">
						*Administrators and editors automatically inherit protected species viewing rights
					</div>
				</fieldset>
				<?php
			}
			$userArr = $permManager->getUsers();
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend><b>Add a New Admin/Editor/Reader</b></legend>
				<form name="addrights" action="collpermissions.php" method="post" onsubmit="return verifyAddRights(this)">
					<div>
						<select name="uid">
							<option value="">Select User</option>
							<option value="">-----------------------------------</option>
							<?php
							foreach($userArr as $uid => $uName){
								echo '<option value="'.$uid.'">'.$uName.'</option>';
							}
							?>
						</select>
					</div>
					<div style="margin:5px 0px 5px 0px;">
						<?php
						if($isGenObs){
							?>
							<input name="righttype" type="hidden" value="editor" />
							<?php
						}
						else{
							?>
							<input name="righttype" type="radio" value="admin" /> Administrator <br/>
							<input name="righttype" type="radio" value="editor" /> Editor <br/>
							<input name="righttype" type="radio" value="rare" /> Rare Species Reader<br/>
							<?php
						}
						?>
					</div>
					<div style="margin:15px;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input name="action" type="submit" value="Add Permissions for User" />
					</div>
				</form>
			</fieldset>
			<?php
			//Personal specimen management sponsorship
			$genObsArr = $permManager->getGeneralObservationCollidArr();
			if(!$isGenObs && $genObsArr){
				?>
				<fieldset style="margin:15px;padding:15px;">
					<legend><b>Personal Observation Management Sponsorship</b></legend>
					<div style="margin:10px">
						Collection administrators listed above can sponsor users for Personal Observation Management.
						This allows users to enter field data as observations that are linked directly to their user profile, print labels,
						and later collection data can be transferred once specimens are donated to this collection.
						Listed below are all users that have been given such rights by one of the collection administrators listed above.
					</div>
					<ul>
						<?php
						$persManagementArr = $permManager->getPersonalObservationManagementArr($collId, $genObsArr);
						if($persManagementArr){
							foreach($persManagementArr as $uid => $pmArr){
								echo '<li>';
								$titleStr = 'Assigned by '.$pmArr['uab'].' on '.$pmArr['ts'];
								if(count($genObsArr) > 1) $titleStr .= ' access to '.$genObsArr[$pmArr['persobscollid']];
								echo '<span title="'.$titleStr.'">'.$pmArr['name'].'</span> ';
								if($SYMB_UID == $pmArr['uidab']){
									echo '<a href="collpermissions.php?collid='.$collId.'&delpersobs='.$uid.'&persobscollid='.$pmArr['persobscollid'].'" onclick="return confirm(\'Are you sure you want to delete these permissions?\');" title="Delete permissions for this user">';
									echo '<img src="../../images/drop.png" style="width:12px;" />';
									echo '</a>';
								}
								echo '</li>';
							}
						}
						else{
							echo '<li>No users have yet been sponsored</li>';
						}
						?>
					</ul>
					<?php
					if((array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
						?>
						<fieldset style="margin:40px 15px 0px 15px;padding:15px;">
							<legend><b>New Sponsorship</b></legend>
							<form name="addpersobsman" action="collpermissions.php" method="post" onsubmit="return verifyAddRights(this)">
								<div>
									<select name="uid">
										<option value="">Select User</option>
										<option value="">-----------------------------------</option>
										<?php
										foreach($userArr as $uid => $uName){
											echo '<option value="'.$uid.'">'.$uName.'</option>';
										}
										?>
									</select>
								</div>
								<div>
									<?php
									if(count($genObsArr) == 1){
										echo '<input name="persobscollid" type="hidden" value="'.key($genObsArr).'" />';
									}
									else{
										echo '<select name="persobscollid">';
										echo '<option value="">Select Personal Observation Project</option>';
										echo '<option value="">-----------------------------------</option>';
										foreach($genObsArr as $persObsCollid => $perObsName){
											echo '<option value="'.$persObsCollid.'">'.$perObsName.'</option>';
										}
										echo '</select>';
									}
									?>
								</div>
								<div style="margin:15px;">
									<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
									<input name="action" type="submit" value="Sponsor User" />
								</div>
							</form>
						</fieldset>
						<?php
					}
					?>
				</fieldset>
				<?php
			}
			//Identification Editors
			$taxonEditorArr = $permManager->getTaxonEditorArr($collId,1);
			$taxonSelectArr = $permManager->getTaxonEditorArr($collId,0);
			if($taxonEditorArr || $taxonSelectArr){
				?>
				<fieldset style="margin:15px;padding:15px;">
					<legend><b>Identification Editors</b></legend>
					<div style="float:right;" title="Add a new user">
						<a href="#" onclick="toggle('addUserDiv');return false;">
							<img style='border:0px;width:15px;' src='../../images/add.png'/>
						</a>
					</div>
					<div id="addUserDiv" style="display:none;">
						<fieldset style="margin:15px;padding:15px;">
							<legend><b>Add Identification Editor</b></legend>
							<div style="margin:0px 20px 10px 10px;">
								The user list below contains only Identification Editors that been approved by a portal manager.
								Contact your portal manager to request the addition of a new user.
							</div>
							<div style="margin:10px;">
								<form name="addidenteditor" action="collpermissions.php" method="post" onsubmit="return verifyAddIdentEditor(this)">
									<div>
										<b>User</b><br/>
										<select name="identeditor">
											<option value="">Select User</option>
											<option value="">--------------------------</option>
											<?php
											foreach($taxonSelectArr as $uid => $uArr){
												$username = $uArr['username'];
												unset($uArr['username']);
												if(!isset($taxonEditorArr[$uid]['all'])) echo '<option value="'.$uid.':all">'.$username.' - All Approved Taxonomy</option>';
												unset($uArr['all']);
												foreach($uArr as $utid => $sciname){
													if(!isset($taxonEditorArr[$uid]['utid'][$utid])) echo '<option value="'.$uid.':'.$utid.'">'.$username.' - '.$sciname.'</option>';
												}
											}
											?>
										</select>
									</div>
									<div style="margin:15px 0px">
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input name="action" type="submit" value="Add Identification Editor" />
									</div>
								</form>
							</div>
						</fieldset>
					</div>
					<div style="margin:10px;">
						Following users have permission to edit occurrence records that are
						insignificantly identified to a taxon that is within the scope of their taxonomic interest
						and has an identification confidence ranking value of less than 6.
						Identification Editors can also edit occurrence records that are only identified to
						order or above or lack an identification altogether.
					</div>
					<?php
					if($taxonEditorArr){
						?>
						<ul>
						<?php
						foreach($taxonEditorArr as $uid => $uArr){
							$username = $uArr['username'];
							unset($uArr['username']);
							$hasAll = false;
							if(array_key_exists('all',$uArr)){
								$hasAll = true;
								unset($uArr['all']);
								?>
								<li>
									<?php echo $username.' (All approved taxonomic ranges listed below)'; ?>
									<a href="collpermissions.php?collid=<?php echo $collId.'&delidenteditor='.$uid.'&utid=all'; ?>" onclick="return confirm('Are you sure you want to remove identification editing rights for this user?');" title="Delete permissions for this user">
										<img src="../../images/drop.png" style="width:12px;" />
									</a>
								</li>
								<?php
							}
							foreach($uArr as $utid => $sciname){
								?>
								<li>
									<?php
									echo $username.' ('.$sciname.')';
									if(!$hasAll){
										?>
										<a href="collpermissions.php?collid=<?php echo $collId.'&delidenteditor='.$uid.'&utid='.$utid; ?>" onclick="return confirm('Are you sure you want to remove identification editing rights for this user?');" title="Delete permissions for this user">
											<img src="../../images/drop.png" style="width:12px;" />
										</a>
										<?php
									}
									?>
								</li>
								<?php
							}
						}
						?>
						</ul>
						<?php
					}
					else{
						echo '<div style="font-weight:bold;margin:20px">';
						echo 'There are no Identification Editor permissions';
						echo '</div>';
					}
					?>
				</fieldset>
				<?php
			}
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">';
			echo 'Unauthorized to view this page. You must have administrative right for this collection.';
			echo '</div>';
		}
		?>
	</div>
	<?php
		include($SERVER_ROOT.'/footer.php');
	?>

</body>
</html>