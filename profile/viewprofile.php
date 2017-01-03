<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
include_once($serverRoot.'/classes/Person.php');
include_once($serverRoot.'/classes/PersonalChecklistManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$userId = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:0;
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 

//Sanitation
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
if(!is_numeric($userId)) $userId = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;

$isSelf = 0;
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
			$updateStatus = $pHandler->changePassword($newPwd, $oldPwd, $isSelf);
		}
		else{
			$updateStatus = $pHandler->changePassword($newPwd);
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
	elseif($action == "Change Login"){
		$pwd = '';
		if($isSelf && isset($_POST["newloginpwd"])) $pwd = $_POST["newloginpwd"];
		if(!$pHandler->changeLogin($_POST["newlogin"], $pwd)){
			$statusStr = $pHandler->getErrorStr();
		}
		$person = $pHandler->getPerson();
		if($person->getIsTaxonomyEditor()) $tabIndex = 3;
		else $tabIndex = 2;
	}
    elseif($action == "Clear Tokens"){
        $statusStr = $pHandler->clearAccessTokens();
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
		$newClid = $pClManager->createChecklist($_POST);
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
<html>
<head>
	<title><?php echo $defaultTitle; ?> - View User Profile</title>
	<meta http-equiv="X-Frame-Options" content="deny">
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/tiny_mce/tiny_mce.js"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
		tinyMCE.init({
			mode : "textareas",
			theme_advanced_buttons1 : "bold,italic,underline,charmap,hr,outdent,indent,link,unlink,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : ""
		});
	</script>
	<script type="text/javascript" src="../js/symb/profile.viewprofile.js?ver=20151202"></script>
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
				if ($person->getIsTaxonomyEditor()) {
					echo '<li><a href="imagesforid.php">Images for ID</a></li>';
				}
				if( $fpEnabled) {
					$userTaxonomy = $person->getUserTaxonomy();

					foreach ($userTaxonomy as $cat => $taxonArr) {
						foreach ($taxonArr as $tid => $taxon) {
							$sciName = $taxon['sciname'];
						}
					}

					if ($person->getIsHasTaxonInterest()) {
						echo '<li><a href="taxoninterests.php?scientificName='.$sciName.'">Taxon Interests</a></li>';
					}
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
									<input name="name" type="text" maxlength="100" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Authors</b><br/>
									<input name="authors" type="text" maxlength="250" style="width:90%;" />
								</div>
								<?php 
								if(isset($GLOBALS['USER_RIGHTS']['RareSppAdmin']) || $IS_ADMIN){
									echo '<div style="margin:3px;">';
									echo '<b>Checklist Type</b><br/>';
									echo '<select name="type">';
									echo '<option value="static">General Checklist</option>';
									echo '<option value="rarespp">Rare, threatened, protected species list</option>';
									echo '</select>';
									echo '</div>';
								}
								?>
								<div style="margin:3px;">
									<b>Locality</b><br/>
									<input name="locality" type="text" maxlength="500" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Citation</b><br/>
									<input name="publication" type="text" maxlength="500" style="width:90%;" />
								</div>
								<div style="margin:3px;">
									<b>Abstract</b><br/>
									<textarea name="abstract" style="width:90%;height:60px;"></textarea>
								</div>
								<div style="margin:3px;">
									<b>Notes</b><br/>
									<input name="notes" type="text" maxlength="500" size="60" />
								</div>
								<div style="float:left;">
									<div style="float:left;margin:3px;">
										<b>Latitude<br />Centroid</b><br />
										<input id="latdec" name="latcentroid" type="text" maxlength="15" style="width:110px;"/>
									</div>
									<div style="float:left;margin:3px;">
										<b>Longitude<br />Centroid</b><br />
										<input id="lngdec" name="longcentroid" type="text" maxlength="15" style="width:110px;" />
									</div>
									<div style="float:left;margin:3px;">
										<b>Point Radius<br />(meters)</b><br />
										<input name="pointradiusmeters" type="text" maxlength="15" style="width:110px;"/>
										<div style="float:right;margin:20px 0px 0px 3px;">
											<span style="cursor:pointer;" onclick="openMappingAid();">
												<img src="../images/world.png" style="width:12px;" />
											</span>
										</div>
									</div>
								</div>
								<div style="float:left;margin-top:8px;margin-bottom:8px;margin-left:8px;">
									<fieldset style="width:175px;">
										<legend><b>Polygon Footprint</b></legend>
										<div id="polycreatebox" style="display:block;clear:both;">
											<b>Create footprint polygon.</b>
										</div>
										<div id="polysavebox" style="display:none;clear:both;">
											<b>Polygon coordinates ready to save.</b>
											<input type="hidden" id="footprintWKT" name="footprintWKT" value='' />
										</div>
										<div style="float:right;margin:8px 0px 0px 10px;cursor:pointer;" onclick="openMappingPolyAid();">
											<img src="../images/world.png" style="width:12px;" />
										</div>
									</fieldset>
								</div>
								<div style="clear:both;margin:3px;">
									<b>Parent Checklist:</b><br/>
									<select name="parentclid">
										<option value="">None Selected</option>
										<option value="">----------------------------------</option>
										<?php $pClManager->echoParentSelect(); ?>
									</select>
								</div>
								<div style="clear:both;margin:3px;">
									<div style="font-weight:bold;">
										<b>Access:</b> 
										<select name="access">
											<option value="private">Private</option>
											<option value="public">Public</option>
										</select>
									</div>
								</div>
								<div style="clear:both;margin:10px;">
									<input type="hidden" name="uid" value="<?php echo $userId;?>" />
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
								<a href="../projects/index.php?pid=<?php echo $pid; ?>&emode=0">
									<?php echo $projName; ?>
								</a>
								<a href="../projects/index.php?pid=<?php echo $pid; ?>&emode=1">
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
