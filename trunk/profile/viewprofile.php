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
$isEditable = 0;
if(isset($symbUid) && $symbUid){
	if(!$userId){
		$userId = $symbUid;
	}
	if($userId == $symbUid){
		$isSelf = 1;
	}
	if($isSelf || $isAdmin){
		$isEditable = 1;
	}
}
$displayMsg = "";

$pHandler = new ProfileManager();
$pClManager = new PersonalChecklistManager();

if($isEditable){
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
	        $displayMsg = "Profile update failed!";
	    }
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
		    $displayMsg = "<span color='green'>Password update successful!</span>";
	    }
	    else{
	    	$displayMsg = "Password update failed! Are you sure you typed the old password correctly?";
	    }
	}
	elseif($action == "Create Login"){
	    $newLogin = $_REQUEST["newlogin"];
		$newPwd = $_REQUEST["newloginpwd"];
	    $displayMsg = $pHandler->createNewLogin($userId, $newLogin, $newPwd);
	}
	elseif($action == "Delete Profile"){
	    if($pHandler->deleteProfile($userId, $isSelf)){
	    	header("Location: ../index.php");
	    }
	    else{
		    $displayMsg = "Profile deletion failed! Please contact the system administrator";
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
	elseif(array_key_exists('cliddel',$_POST)){
	    if(!$pClManager->deleteChecklist($_POST["cliddel"])){
		    $displayMsg = "Checklist deletion failed! Please contact the system administrator";
	    }
	}
}
?>

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
</head>
<body onload="initTabs('profiletabs');">
<?php
$displayLeftMenu = (isset($profile_viewprofileMenu)?$profile_viewprofileMenu:"true");
include($serverRoot.'/header.php');
if(isset($profile_viewprofileCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $profile_viewprofileCrumbs;
	echo " <b>User Profile</b>"; 
	echo "</div>";
}
?>
	<!-- inner text -->
	<div id="innertext">
	<?php 
	if($isEditable){
		if($displayMsg){
		    echo "<div style='color:#FF0000;margin:10px 0px 10px 10px;'>".$displayMsg."</div>";
		}
		$person = $pHandler->getPersonByUid($userId);
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
		        <li><a href="#profilediv">Profile Details</a></li>
		    </ul>
		    <?php
		    if($floraModIsActive){ 
		    	?>
				<div id="checklistdiv">
					<fieldset style="margin:10px;padding:20px;">
						<legend><b>Management</b></legend>
						<?php 
						$listArr = $pClManager->getManagementLists($userId);
						echo '<div style="font-weight:bold;font:bold 14pt;">Checklists</div>'."\n";
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
									<a href="../checklists/checklist.php?cl=<?php echo $kClid; ?>&emode=1">
										<img src="../images/edit.png" style="width:15px;border:0px;" title="Edit Checklist" />
									</a>
									<form action="viewprofile.php" method="post" style="display:inline;" onsubmit="return window.confirm('Are you sure you want to delete <?php echo $vName; ?>?');">
										<input type="hidden" name="cliddel" value="<?php echo $kClid; ?>">
										<input type="hidden" name="userid" value="<?php echo $userId;?>" />
										<input type="image" src="../images/del.gif" name="action" value="DeleteChecklist" title="Delete Checklist" style="width:15px;" />
									</form> 
								</li>
								<?php 
							}
							?>
							</ul>
							<?php 
						}
						else{
							echo '<div style="margin:10px;">You have no personal checklists</div>';
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
					</fieldset>
					<form id="checklistaddform" name="checklistaddform" action="viewprofile.php" method="get" style="margin:10px;" onsubmit="return verifyClAddForm(this);">
						<fieldset>
							<legend style="font-weight:bold;">Create a New Checklist</legend>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Checklist Name:
								</div>
								<div style="float:left;">
									<input name="nclname" type="text" maxlength="50" size="60" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Authors:
								</div>
								<div style="float:left;">
									<input name="nclauthors" type="text" maxlength="250" size="60" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Locality:
								</div>
								<div style="float:left;">
									<input name="ncllocality" type="text" maxlength="500" size="60" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Publication:
								</div>
								<div style="float:left;">
									<input name="nclpublication" type="text" maxlength="500" size="60" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Abstract:
								</div>
								<div style="float:left;">
									<textarea name="nclabstract" rows="4" cols="45"></textarea>
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Parent Checklist:
								</div>
								<div style="float:left;">
									<select name="nclparentclid">
										<option value="">Select a Parent checklist</option>
										<option value="">----------------------------------</option>
										<?php $pClManager->echoParentSelect(); ?>
									</select>
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Notes:
								</div>
								<div style="float:left;">
									<input name="nclnotes" type="text" maxlength="500" size="60" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Latitude Centroid:
								</div>
								<div style="float:left;">
									<input id="latdec" name="ncllatcentroid" type="text" maxlength="15" size="10" />
									<span style="cursor:pointer;" onclick="openMappingAid();">
										<img src="../images/world40.gif" style="width:12px;" />
									</span>
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Longitude Centroid:
								</div>
								<div style="float:left;">
									<input id="lngdec" name="ncllongcentroid" type="text" maxlength="15" size="10" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Point Radius (meters):
								</div>
								<div style="float:left;">
									<input name="nclpointradiusmeters" type="text" maxlength="15" size="10" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Public Access:
								</div>
								<div style="float:left;">
									<select name="nclaccess">
										<option value="private">Private</option>
										<option value="public">Public</option>
									</select>
								</div>
							</div>
							<div style="clear:both;">
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								<div style="margin-left:20px;">
									<input name="action" type="submit" value="Create Checklist" />
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<?php 
		    }
			?>
			<div id="profilediv">
				<form id="editprofileform" name="editprofile" action="viewprofile.php" method="post" onsubmit="return checkEditForm(this);">
					<fieldset>
						<legend><b>User Profile</b></legend>
						<table cellspacing='1' style="width:100%;">
						    <tr>
						        <td><b>First Name:</b></td>
						        <td>
									<div style="float:right;margin:3px;cursor:pointer;" onclick="toggle('editdiv');" title="Toggle editing Controls">
										<img style='border:0px;' src='../images/edit.png' />
									</div>
									<div class="editdiv" style="float:left;">
										<?php echo $person->getFirstName();?>
									</div>
									<div class="editdiv" style="display:none;float:left;">
										<input id="firstname" name="firstname" size="40" value="<?php echo $person->getFirstName();?>">
									</div>
					            </td>
						    </tr>
						    <tr>
						        <td><b>Last Name:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getLastName();?>
									</div>
									<div class="editdiv" style="display:none;">
										<input id="lastname" name="lastname" size="40" value="<?php echo $person->getLastName();?>">
									</div>
					            </td>
						    </tr>
						    <tr>
						        <td><b>Title:</b></td>
						        <td>
									<div class="editdiv">
										<?php echo $person->getTitle(); ?>
									</div>
									<div class="editdiv" style="display:none;">
										<input name="title"  size="40" value="<?php echo $person->getTitle();?>">
									</div>
								</td>
						    </tr>
						    <tr>
						        <td><b>Institution:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getInstitution();?>
									</div>
									<div class="editdiv" style="display:none;">
										<input name="institution"  size="40" value="<?php echo $person->getInstitution();?>">
									</div>
								</td>
						    </tr>
						    <tr>
						        <td><b>City:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getCity();?>
									</div>
									<div class="editdiv" style="display:none;">
						            	<input id="city" name="city" size="40" value="<?php echo $person->getCity();?>">
									</div>
					            </td>
						    </tr>
						    <tr>
						        <td><b>State:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getState();?>
									</div>
									<div class="editdiv" style="display:none;">
							            <input id="state" name="state" size="40" value="<?php echo $person->getState();?>">
									</div>
					            </td>
						    </tr>
						    <tr>
						        <td><b>Zip Code:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getZip();?>
									</div>
									<div class="editdiv" style="display:none;">
							            <input name="zip" size="40" value="<?php echo $person->getZip();?>">
									</div>
					            </td>
						    </tr>
						    <tr>
						        <td><b>Country:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getCountry();?>
									</div>
									<div class="editdiv" style="display:none;">
										<input id="country" name="country" size="40" value="<?php echo $person->getCountry();?>">
									</div>
								</td>
						    </tr>
						    <tr>
						        <td><b>Email Address:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getEmail();?>
									</div>
									<div class="editdiv" style="display:none;">
							            <input id="email" name="email" size="40" value="<?php echo $person->getEmail();?>">
									</div>
					            </td>
						    </tr>
						    <tr>
						        <td><b>Url:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getUrl();?>
									</div>
									<div class="editdiv" style="display:none;">
										<input name="url"  size="40" value="<?php echo $person->getUrl();?>">
									</div>
	
								</td>
						    </tr>
						    <tr>
						        <td><b>Biography:</b></td>
						        <td>
									<div class="editdiv">
							        	<?php echo $person->getBiography();?>
									</div>
									<div class="editdiv" style="display:none;">
										<textarea name="biography" rows="4" cols="40"><?php echo $person->getBiography();?></textarea>
									</div>
								</td>
						    </tr>
						    <tr>
						        <td><b>Logins:</b></td>
						        <td>
									<div class="editdiv">
										<?php 
										$loginArr = $person->getLoginArr();
										if($loginArr){
											$isFirst = true;
											foreach($loginArr as $login){
												echo '<span class="editdiv" id="un-'.$login.'">'.($isFirst?'':'; ').$login;
												echo '<span style="display:none;" onclick="deleteLogin('.$userId.',"'.$login.'");"> ';
												echo '<img src="../images/del.gif" title="Delete '.$login.'" />';
												echo '</span></span>';
											}
										}
										else{
											echo "No logins are registered";
										}
										?>
									</div>
								</td>
						    </tr>
						    <tr>
						        <td colspan="2">
						        	<div class="editdiv">
						        		<?php 
					        			if($person->getIsPublic()){
											echo "User information is displayable to public (e.g. photographer listing)";
					        			}
					        			else{
					        				echo "User information is hidden from public";
					        			}
						        		?>
						        	</div>	
									<div class="editdiv" style="display:none;">
										<input type="checkbox" name="ispublic" value="1" <?php if($person->getIsPublic()) echo "CHECKED"; ?> /> 
										Make user information displayable to public  
					        		</div>
								</td>
						    </tr>
						    <tr>
								<td colspan="2">
									<div class="editdiv" style="margin:10px;display:none;">
										<input type="hidden" name="userid" value="<?php echo $userId;?>" />
										<input type="submit" name="action" value="Submit Edits" id="editprofile">
									</div>
								</td>
							</tr>
						</table>
					</fieldset>
				</form>

				<div class="editdiv" style="display:none;">
					<form id="changepwd" name="changepwd" action="viewprofile.php" method="post" onsubmit="return checkPwdForm(this);">
						<fieldset style='padding:15px;width:500px;'>
					    	<legend><b>Change Password</b></legend>
					    	<table>
								<?php if($isSelf){ ?>
					    		<tr>
					    			<td>
						            	<b>Current Password:</b>
						            </td>
						            <td> 
						            	<input id="oldpwd" name="oldpwd" type="password"/>
					    			</td>
					    		</tr>
								<?php }?>
					    		<tr>
					    			<td>
						            	<b>New Password:</b> 
						            </td>
						            <td> 
						            	<input id="newpwd" name="newpwd" type="password"/>
					    			</td>
					    		</tr>
					    		<tr>
					    			<td>
										<b>New Password Again:</b> 
						            </td>
						            <td> 
										<input id="newpwd2" name="newpwd2" type="password"/>
						    		</td>
						    	</tr>
					    		<tr>
					    			<td colspan="2">
										<input type="hidden" name="userid" value="<?php echo $userId;?>" />
										<input type="submit" name="action" value="Change Password" id="editpwd"/>
					    			</td>
					    		</tr>
							</table>
						</fieldset>
					</form>
	<!-- 
					<form id="newloginform" name="newloginform" action="viewprofile.php" method="post" onsubmit="return checkNewLoginForm(this);">
						<fieldset style='margin:5px;width:200px;'>
					    	<legend>Create New Login</legend>
				            <div style="font-weight:bold;">
				            	New Login (no spaces): 
				            	<input id="newlogin" name="newlogin" type="text">
				            </div> 
				            <div style="font-weight:bold;">
				            	Choose a New Password: 
				            	<input id="newloginpwd" name="newloginpwd" type="password">
				            </div> 
							<div style="font-weight:bold;">
								New Password Again: 
								<input id="newloginpwd2" name="newloginpwd2" type="password">
							</div>
							<div>
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								<input type="submit" name="action" value="Create Login" id="newloginsubmit">
							</div>
						</fieldset>
					</form>
	 -->
					<form action="viewprofile.php" method="post" onsubmit="return window.confirm('Are you sure you want to delete profile?');">
						<fieldset style='padding:15px;width:200px;'>
					    	<legend><b>Delete Profile</b></legend>
							<input type="hidden" name="userid" value="<?php echo $userId;?>" />
				    		<input type="submit" name="action" value="Delete Profile" id="submitdelete" />
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	<?php 
	}
	else{
		echo "<div style='color:#FF0000;margin:10px;'>You must login or have administrator rights to view profile.</div>";
	}
	?>
	</div>

<?php
	include($serverRoot.'/footer.php');
?>

</body>
</html>
