<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
include_once($serverRoot.'/classes/Person.php');
include_once($serverRoot.'/classes/PersonalChecklistManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$userId = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:0;
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 

$isSelf = 0;
$isEditor = 0;
$isEditor = 0;
if(isset($SYMB_UID) && $SYMB_UID){
	if(!$userId){
		$userId = $SYMB_UID;
	}
	if($userId == $SYMB_UID){
		$isSelf = 1;
	}
	if($isSelf || $isAdmin){
		$isEditor = 1;
	}
}
if(!$userId) header('Location: index.php?refurl=viewprofile.php');

$statusStr = "";

$pHandler = new ProfileManager();
$pHandler->setUid($userId);
$pClManager = new PersonalChecklistManager();

$person = null;
if($isEditor){
	// ******************************  editing a profile  ************************************//
	if($action == "Submit Edits"){
	    $firstname = $_REQUEST["firstname"];
	    $lastname = $_REQUEST["lastname"];
	    $email = $_REQUEST["email"];
	    
		$title = array_key_exists("title",$_REQUEST)?$_REQUEST["title"]:"";
		$institution = array_key_exists("institution",$_REQUEST)?$_REQUEST["institution"]:"";
		$city = array_key_exists("city",$_REQUEST)?$_REQUEST["city"]:"";
		$state = array_key_exists("state",$_REQUEST)?$_REQUEST["state"]:"";
		$zip = array_key_exists("zip",$_REQUEST)?$_REQUEST["zip"]:"";
		$country = array_key_exists("country",$_REQUEST)?$_REQUEST["country"]:"";
		$url = array_key_exists("url",$_REQUEST)?$_REQUEST["url"]:"";
		$biography = array_key_exists("biography",$_REQUEST)?$_REQUEST["biography"]:"";
		$isPublic = array_key_exists("ispublic",$_REQUEST)?$_REQUEST["ispublic"]:"";
		
		$newPerson = new Person();
		$newPerson->setUid($userId);
		$newPerson->setFirstName($firstname);
	    $newPerson->setLastName($lastname);
	    $newPerson->setTitle($title);
	    $newPerson->setInstitution($institution);
	    $newPerson->setCity($city);
	    $newPerson->setState($state);
	    $newPerson->setZip($zip);
	    $newPerson->setCountry($country);
	    $newPerson->setEmail($email);
	    $newPerson->setUrl($url);
	    $newPerson->setBiography($biography);
	    $newPerson->setIsPublic($isPublic);
	    
	    if(!$pHandler->updateProfile($newPerson)){
	        $statusStr = "Profile update failed!";
	    }
		$person = $pHandler->getPerson();
	    if($person->getIsTaxonomyEditor()) $tabIndex = 3;
		else $tabIndex = 2;
	}
	elseif($action == "Change Password"){
	    $newPwd = $_REQUEST["newpwd"];
		$updateStatus = false;
	    if($isSelf){
		    $oldPwd = $_REQUEST["oldpwd"];
		    $updateStatus = $pHandler->changePassword($paramsArr["un"], $newPwd, $oldPwd, $isSelf);
	    }
	    else{
	    	$updateStatus = $pHandler->changePassword($userId, $newPwd);
	    }
	    if($updateStatus){
		    $statusStr = "<span color='green'>Password update successful!</span>";
	    }
	    else{
	    	$statusStr = "Password update failed! Are you sure you typed the old password correctly?";
	    }
		$person = $pHandler->getPerson();
	    if($person->getIsTaxonomyEditor()) $tabIndex = 3;
		else $tabIndex = 2;
	}
	elseif($action == "Create Login"){
	    $newLogin = $_REQUEST["newlogin"];
		$newPwd = $_REQUEST["newloginpwd"];
	    $statusStr = $pHandler->createNewLogin($userId, $newLogin, $newPwd);
		$person = $pHandler->getPerson();
	    if($person->getIsTaxonomyEditor()) $tabIndex = 3;
		else $tabIndex = 2;
	}
	elseif($action == "Delete Profile"){
	    if($pHandler->deleteProfile($userId, $isSelf)){
	    	header("Location: ../index.php");
	    }
	    else{
		    $statusStr = "Profile deletion failed! Please contact the system administrator";
	    }
	}
	elseif($action == "Create Checklist"){
		$newClArr = Array();
		foreach($_REQUEST as $k => $v){
			if(substr($k,0,3) == "ncl"){
				$newClArr[substr($k,3)] = $_REQUEST[$k];
			}
		}
		$newClArr["uid"] = $_REQUEST["userid"];
		$newClid = $pClManager->createChecklist($newClArr);
		header("Location: ".$clientRoot."/checklists/checklist.php?cl=".$newClid."&emode=1");
	}
	elseif($action == "delusertaxonomy"){
		$statusStr = $pHandler->deleteUserTaxonomy($_GET['utid']);
		$person = $pHandler->getPerson();
		if($person->getIsTaxonomyEditor()) $tabIndex = 3;
		else $tabIndex = 2;
	}
	elseif($action == "Add Taxonomic Relationship"){
		$statusStr = $pHandler->addUserTaxonomy($_POST['taxon'], $_POST['editorstatus'], $_POST['geographicscope'], $_POST['notes']);
		$person = $pHandler->getPerson();
		if($person->getIsTaxonomyEditor()) $tabIndex = 3;
		else $tabIndex = 2;
	}
	
	if(!$person) $person = $pHandler->getPerson();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $defaultTitle; ?> - View User Profile</title>
	<link href="../css/main.css" rel="stylesheet" type="text/css"/>
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/symb/profile.viewprofile.js"></script>
	<script type="text/javascript" src="../js/symb/shared.js"></script>
</head>
<body>
<?php
$displayLeftMenu = (isset($profile_viewprofileMenu)?$profile_viewprofileMenu:"true");
include($serverRoot.'/header.php');
if(isset($profile_viewprofileCrumbs)){
	echo "<div class='navpath'>";
	echo $profile_viewprofileCrumbs;
	echo " <b>User Profile</b>"; 
	echo "</div>";
}
?>
	<!-- inner text -->
	<div id="innertext">
	<?php 
	if($isEditor){
		if($statusStr){
		    echo "<div style='color:#FF0000;margin:10px 0px 10px 10px;'>".$statusStr."</div>";
		}
		?>
		<div id="tabs" style="margin:10px;">
			<ul>
			    <?php
			    if($floraModIsActive){ 
			    	?>
			        <li><a href="#checklistdiv">Species Checklists</a></li>
			        <?php
			    } 
			    ?>
		        <li><a href="personalspecmenu.php">Specimen Management</a></li>
		        <?php
		        if ($person->getIsTaxonomyEditor()) { 
		            echo '<li><a href="specimenstoid.php?userid='.$userId.'&action='.$action.'">IDs Needed</a></li>';
		        }
		        ?>
                <?php
                if ($person->getIsTaxonomyEditor()) {
                    echo '<li><a href="imagesforid.php">IDs Needed</a></li>';
                }
                ?>
		        <li><a href="userprofile.php?userid=<?php echo $userId; ?>">User Profile</a></li>
		    </ul>
		    <?php
		    if($floraModIsActive){ 
		    	?>
				<div id="checklistdiv" style="padding:25px;">
					<div style="font-weight:bold;font:bold 14pt;">
						Checklists assigned to your account
						<a href="#" onclick="toggle('claddformdiv')" title="Create a New Checklist"><img src="../images/add.png" /></a>
					</div>
					<div id="claddformdiv" style="margin:10px;display:none;">
						<form id="checklistaddform" name="checklistaddform" action="viewprofile.php" method="post" onsubmit="return verifyClAddForm(this);">
							<fieldset>
								<legend style="font-weight:bold;">Create a New Checklist</legend>
								<div style="margin:3px;">
									<b>Checklist Name</b><br/>
									<input name="nclname" type="text" maxlength="50" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Authors</b><br/>
									<input name="nclauthors" type="text" maxlength="250" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Locality</b><br/>
									<input name="ncllocality" type="text" maxlength="500" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Publication</b><br/>
									<input name="nclpublication" type="text" maxlength="500" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Abstract</b><br/>
									<textarea name="nclabstract" style="width:90%;height:60px;"></textarea>
								</div>
								<div style="margin:3px;">
									<b>Notes</b><br/>
									<input name="nclnotes" type="text" maxlength="500" size="60" />
								</div>
								<div style="float:left;margin:3px;">
									<b>Latitude Centroid</b><br/>
									<input id="latdec" name="ncllatcentroid" type="text" maxlength="15" style="width:110px;"/>
								</div>
								<div style="float:left;margin:3px;">
									<b>Longitude Centroid</b><br/>
									<input id="lngdec" name="ncllongcentroid" type="text" maxlength="15" style="width:110px;" />
								</div>
								<div style="float:left;margin:3px;">
									<b>Point Radius (meters)</b><br/>
									<input name="nclpointradiusmeters" type="text" maxlength="15" style="width:110px;"/>
								</div>
								<div style="float:left;margin:20px 0px 0px 3px;">
									<span style="cursor:pointer;" onclick="openMappingAid();">
										<img src="../images/world40.gif" style="width:12px;" />
									</span>
								</div>
								<div style="clear:both;margin:3px;">
									<b>Parent Checklist:</b><br/> 
									<select name="nclparentclid">
										<option value="">Select a Parent checklist</option>
										<option value="">----------------------------------</option>
										<?php $pClManager->echoParentSelect(); ?>
									</select>
								</div>
								<div style="clear:both;margin:3px;">
									<div style="font-weight:bold;">
										<b>Access:</b> 
										<select name="nclaccess">
											<option value="private">Private</option>
											<option value="public">Public</option>
										</select>
									</div>
								</div>
								<div style="clear:both;margin:10px;">
									<input type="hidden" name="userid" value="<?php echo $userId;?>" />
									<div style="margin-left:20px;">
										<input name="action" type="submit" value="Create Checklist" />
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<?php 
					$listArr = $pClManager->getManagementLists($userId);
					if(array_key_exists('cl',$listArr)){
						$clArr = $listArr['cl'];
						?>
						<ul>
						<?php 
						foreach($clArr as $kClid => $vName){
							?>
							<li>
								<a href="../checklists/checklist.php?cl=<?php echo $kClid; ?>&emode=0">
									<?php echo $vName; ?>
								</a>
								<a href="../checklists/checklistadmin.php?clid=<?php echo $kClid; ?>&emode=1">
									<img src="../images/edit.png" style="width:15px;border:0px;" title="Edit Checklist" />
								</a>
							</li>
							<?php 
						}
						?>
						</ul>
						<?php 
					}
					else{
						?>
						<div style="margin:10px;">
							<div>You have no personal checklists</div>
							<div>
								<a href="#" onclick="toggle('claddformdiv')">Create a New Checklist</a>
							</div>
						</div>
						<?php 
					}

					echo '<div style="font-weight:bold;font:bold 14pt;margin-top:25px;">Inventory Project Administration</div>'."\n";
					if(array_key_exists('proj',$listArr)){
						$projArr = $listArr['proj'];
						?>
						<ul>
						<?php 
						foreach($projArr as $pid => $projName){
							?>
							<li>
								<a href="../projects/index.php?proj=<?php echo $pid; ?>&emode=0">
									<?php echo $projName; ?>
								</a>
								<a href="../projects/index.php?proj=<?php echo $pid; ?>&emode=1">
									<img src="../images/edit.png" style="width:15px;border:0px;" title="Edit Project" />
								</a>
							</li>
							<?php 
						}
						?>
						</ul>
						<?php 
					}
					else{
						echo '<div style="margin:10px;">There are no Projects for which you have administrative permissions</div>';
					}
					?>
				</div>
				<?php 
		    }
			?>
		</div>
		<?php 
	}
	?>
	</div>
<?php
include($serverRoot.'/footer.php');
?>

</body>
</html>
