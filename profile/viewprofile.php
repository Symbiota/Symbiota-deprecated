<?php
/*
 * Created on 26 Feb 2009
 * By E.E. Gilbert
*/
include_once('../config/symbini.php');
include_once('util/ProfileHandler.php');
include_once('util/Person.php');
Header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$userId = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:0;

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

$pHandler = new ProfileHandler();
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
		$pClManager->createChecklist($newClArr);
	}
	elseif($action == "DeleteChecklist"){
	    if(!$pClManager->deleteChecklist($_REQUEST["cliddel"])){
		    $displayMsg = "Checklist deletion failed! Please contact the system administrator";
	    }
	}
}
?>

<html>
<head>
	<title><?php echo $defaultTitle; ?> - View User Profile</title>
	<link href="../css/main.css" rel="stylesheet" type="text/css"/>

	<link rel="stylesheet" type="text/css" href="../css/tabcontent.css" />
	<script type="text/javascript" src="../js/tabcontent.js"></script>
	<script type="text/javascript" language="JavaScript">
		var dlXmlHttp;

		function openPointMap() {
		    mapWindow=open("../checklists/tools/mappointaid.php?formid=checklistaddform","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
		    if (mapWindow.opener == null) mapWindow.opener = self;
		}

		function initTabs(tabObjId){
			var dTabs=new ddtabcontent(tabObjId); 
			dTabs.setpersist(true);
			dTabs.setselectedClassTarget("link"); 
			dTabs.init();
		}
		
		function checkEditForm(f){
	        var errorText = "";
	        if(f.firstname.value.replace(/\s/g, "") == "" ){
	            errorText += "\nFirst Name";
	        };
	        if(f.lastname.value.replace(/\s/g, "") == "" ){
	            errorText += "\nLast Name";
	        };
	        if(f.state.value.replace(/\s/g, "") == "" ){
	            errorText += "\nState";
	        };
	        if(f.country.value.replace(/\s/g, "") == "" ){
	            errorText += "\nCountry";
	        };
	        if(f.email.value.replace(/\s/g, "") == "" ){
	            errorText += "\nEmail";
	        };
	
	        if(errorText == ""){
	            return true;
	        }
	        else{
	            window.alert("The following fields must be filled out:\n " + errorText);
	            return false;
	        }
	    }
	    
	    function checkPwdForm(f){
	        var pwd1 = f.newpwd.value.replace(/\s/g, "");
	        var pwd2 = f.newpwd2.value.replace(/\s/g, "");
	        if(pwd1 == "" || pwd2 == ""){
	            window.alert("Both password fields must contain a value.");
	            return false;
	        }
	        if(pwd1 != pwd2){
	            window.alert("Password do not match. Please enter again.");
	            f.newpwd.value = "";
	            f.newpwd2.value = "";
	            f.newpwd.focus();
	            return false;
	        }
	        return true;
	    }

	    function checkNewLoginForm(f){
	        var pwd1 = f.newloginpwd.value.replace(/\s/g, "");
	        var pwd2 = f.newloginpwd2.value.replace(/\s/g, "");
	        if(pwd1 == "" || pwd2 == ""){
	            window.alert("Both password fields must contain a value.");
	            return false;
	        }
	        if(pwd1 != pwd2){
	            window.alert("Password do not match. Please enter again.");
	            f.newloginpwd.value = "";
	            f.newloginpwd2.value = "";
	            f.newloginpwd.focus();
	            return false;
	        }
	        return true;
	    }

	    function deleteLogin(userId,login){
	        if(window.confirm('Are you sure you want to delete '+login+' as a Login?')){
				dlXmlHttp = GetXmlHttpObject();
				if(dlXmlHttp==null){
			  		alert ("Your browser does not support AJAX!");
			  		return;
			  	}
				var url = "rpc/deletelogin.php";
				url=url + "?userid=" + userId + "&login=" + login;
				url=url + "&sid="+Math.random();
				document.getElementById("un-"+login).style.display = "none";
				dlXmlHttp.open("POST",url,true);
				dlXmlHttp.send(null);
	        }
		} 
		
		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
		  		xmlHttp=new XMLHttpRequest();
		  	}
			catch (e){
		  		// Internet Explorer
		  		try{
		    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		    	}
		  		catch(e){
		    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		    	}
		  	}
			return xmlHttp;
		}
	</script>
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
		<div style="margin:10px;">
		    <ul id="profiletabs" class="shadetabs">
		        <li><a href="#" rel="viewprofilediv" class="selected">View Profile</a></li>
		        <li><a href="#" rel="editprofilediv">Edit Profile</a></li>
		        <li><a href="#" rel="editpassworddiv">Edit Password</a></li>
		        <li><a href="#" rel="checklistdiv">Personal Checklists</a></li>
		    </ul>
			<div style="border:1px solid gray; width:450px; margin-bottom: 1em; padding: 10px">
				<div id="viewprofilediv" class="tabcontent" style="margin:10px;">
					<table cellspacing='3'>
					    <tr>
					        <td><b>First Name:</b></td>
					        <td>
					        	<?php echo $person->getFirstName();?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>Last Name:</b></td>
					        <td>
					        	<?php echo $person->getLastName();?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>Title:</b></td>
					        <td>
					        	<?php echo $person->getTitle(); ?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>Institution:</b></td>
					        <td>
					        	<?php echo $person->getInstitution();?>
							</td>
					    </tr>
					    <tr>
					        <td><b>City:</b></td>
					        <td>
					        	<?php echo $person->getCity();?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>State:</b></td>
					        <td>
					        	<?php echo $person->getState();?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>Zip Code:</b></td>
					        <td>
					        	<?php echo $person->getZip();?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>Country:</b></td>
					        <td>
					        	<?php echo $person->getCountry();?>
							</td>
					    </tr>
					    <tr>
					        <td><b>Email Address:</b></td>
					        <td>
					        	<?php echo $person->getEmail();?>
				            </td>
					    </tr>
					    <tr>
					        <td><b>Url:</b></td>
					        <td>
					        	<?php echo $person->getUrl();?>
							</td>
					    </tr>
					    <tr>
					        <td><b>Biography:</b></td>
					        <td>
					        	<?php echo $person->getBiography();?>
							</td>
					    </tr>
					    <tr>
					        <td><b>Logins:</b></td>
					        <td>
								<?php 
									$loginArr = $person->getLoginArr();
									if($loginArr){
										$isFirst = true;
										foreach($loginArr as $login){
											echo ($isFirst?"":"; ").$login;
											$isFirst = false;
										}
									}
									else{
										echo "No logins are registered";
									}
								?>
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
							</td>
					    </tr>
					    <tr>
					        <td colspan="2">
				        		<?php 
				        			if($person->getIsPublic()){
										echo "User information is displayable to public (e.g. photographer listing)";
				        			}
				        			else{
				        				echo "User information is hidden from public";
				        			}
				        		?>	
							</td>
					    </tr>
					</table>
				</div>
				<div id="editprofilediv" class="tabcontent">
					<form id="editprofileform" name="editprofile" action="viewprofile.php" method="post" onsubmit="return checkEditForm(this);">
						<table cellspacing='3'>
						    <tr>
						        <td><b>First Name:</b></td>
						        <td>
						            <input id="firstname" name="firstname" size="40" value="<?php echo $person->getFirstName();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>Last Name:</b></td>
						        <td>
						            <input id="lastname" name="lastname" size="40" value="<?php echo $person->getLastName();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>Title:</b></td>
						        <td>
						            <input name="title"  size="40" value="<?php echo $person->getTitle();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>Institution:</b></td>
						        <td>
									<input name="institution"  size="40" value="<?php echo $person->getInstitution();?>">
								</td>
						    </tr>
						    <tr>
						        <td><b>City:</b></td>
						        <td>
					            	<input id="city" name="city" size="40" value="<?php echo $person->getCity();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>State:</b></td>
						        <td>
						            <input id="state" name="state" size="40" value="<?php echo $person->getState();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>Zip Code:</b></td>
						        <td>
						            <input name="zip" size="40" value="<?php echo $person->getZip();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>Country:</b></td>
						        <td>
									<input id="country" name="country" size="40" value="<?php echo $person->getCountry();?>">
								</td>
						    </tr>
						    <tr>
						        <td><b>Email Address:</b></td>
						        <td>
						            <input id="email" name="email" size="40" value="<?php echo $person->getEmail();?>">
					            </td>
						    </tr>
						    <tr>
						        <td><b>Url:</b></td>
						        <td>
									<input name="url"  size="40" value="<?php echo $person->getUrl();?>">
								</td>
						    </tr>
						    <tr>
						        <td><b>Biography:</b></td>
						        <td>
									<textarea name="biography" rows="4" cols="40"><?php echo $person->getBiography();?></textarea>
								</td>
						    </tr>
						    <tr>
						        <td><b>Logins:</b></td>
						        <td>
									<?php 
										$loginArr = $person->getLoginArr();
										if($loginArr){
											$isFirst = true;
											foreach($loginArr as $login){
												echo "<span id='un-".$login."'>".($isFirst?"":"; ").$login;
												echo "<span onclick=\"deleteLogin($userId,'$login');\"> ";
												echo "<img src='../images/del.gif' title='Delete $login' />";
												echo "</span></span>";
												$isFirst = false;
											}
										}
										else{
											echo "No logins are registered";
										}
									?>
								</td>
						    </tr>
						    <tr>
						        <td colspan="2">
									<input type="checkbox" name="ispublic" value="1" <?php if($person->getIsPublic()) echo "CHECKED"; ?> /> 
									Make user information displayable to public  
								</td>
						    </tr>
						    <tr>
								<td colspan='2' align="right">
									<div style="margin:10px;">
										<input type="hidden" name="userid" value="<?php echo $userId;?>" />
										<input type="submit" name="action" value="Submit Edits" id="editprofile">
									</div>
								</td>
							</tr>
						</table>
					</form>
				</div>
				<div id="editpassworddiv" class="tabcontent">
					<form id="changepwd" name="changepwd" action="viewprofile.php" method="post" onsubmit="return checkPwdForm(this);">
						<fieldset style='margin:5px;width:200px;'>
					    	<legend>Change Password:</legend>
							<?php if($isSelf){ ?>
				            <div style="font-weight:bold;">
				            	Current Password: 
				            	<input id="oldpwd" name="oldpwd" type="password"/>
				            </div> 
							<?php }?>
				            <div style="font-weight:bold;">
				            	Choose a New Password: 
				            	<input id="newpwd" name="newpwd" type="password"/>
				            </div> 
							<div style="font-weight:bold;">
								New Password Again: 
								<input id="newpwd2" name="newpwd2" type="password"/>
							</div>
							<div>
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								<input type="submit" name="action" value="Change Password" id="editpwd"/>
							</div>
							<?php if($isSelf){ ?>
							<div>
								* Will change password for all logins
							</div>
							<?php }?>
						</fieldset>
					</form>

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

					<form action="viewprofile.php" method="post" onsubmit="return window.confirm('Are you sure you want to delete profile?');">
						<fieldset style='margin:5px;width:200px;'>
					    	<legend>Delete Profile:</legend>
							<input type="hidden" name="userid" value="<?php echo $userId;?>" />
				    		<input type="submit" name="action" value="Delete Profile" id="submitdelete" />
						</fieldset>
					</form>
				</div>
				<div id="checklistdiv" class="tabcontent">
					<div style="margin:10px;" class="fieldset">
						<div class="legend">Available Checklists</div>
						<ul>
						<?php 
							$clArr = $pClManager->getChecklists($userId);
							if($clArr){
								foreach($clArr as $kClid => $vName){
									?>
									<li>
										<a href="../checklists/checklist.php?cl=<?php echo $kClid; ?>&emode=0">
											<?php echo $vName; ?>
										</a>
										<a href="../checklists/checklist.php?cl=<?php echo $kClid; ?>&emode=1">
											<img src="../images/edit.png" style="width:15px;border:0px;" title="Edit Checklist" />
										</a>
										<form action="viewprofile.php" method="get" style="display:inline;" onsubmit="return window.confirm('Are you sure you want to delete <?php echo $vName; ?>?');">
											<input type="hidden" name="cliddel" value="<?php echo $kClid; ?>">
											<input type="hidden" name="userid" value="<?php echo $userId;?>" />
											<input type="image" src="../images/del.gif" name="action" value="DeleteChecklist" title="Delete Checklist" style="width:15px;" />
										</form> 
									</li>
									
									<?php 
								}
							}
							else{
								echo "<h3>You have no personal checklists</h3>";
							}
						?>
						</ul>
					</div>
					<form id="checklistaddform" action="viewprofile.php" method="get" style="margin:10px;" onsubmit="checkClCreateForm();">
						<fieldset>
							<legend style="font-weight:bold;">Create a New Checklist</legend>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Checklist Name:
								</div>
								<div style="float:left;">
									<input name="nclname" type="text" maxlength="50" size="42" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Authors:
								</div>
								<div style="float:left;">
									<input name="nclauthors" type="text" maxlength="250" size="42" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Locality:
								</div>
								<div style="float:left;">
									<input name="ncllocality" type="text" maxlength="500" size="42" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Publication:
								</div>
								<div style="float:left;">
									<input name="nclpublication" type="text" maxlength="500" size="42" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Abstract:
								</div>
								<div style="float:left;">
									<textarea name="nclabstract" rows="4" cols="32"></textarea>
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
									<input name="nclnotes" type="text" maxlength="500" size="42" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="width:130px;float:left;">
									Latitude Centroid:
								</div>
								<div style="float:left;">
									<input id="latdec" name="ncllatcentroid" type="text" maxlength="15" size="10" />
									<span style="cursor:pointer;" onclick="openPointMap();">
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
										<option value="public limited">Public Limited</option>
									<?php if($isAdmin){ ?>
										<option value="public">Public Research Grade</option>
									<?php } ?>
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
<?php 
class PersonalChecklistManager{

	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function getChecklists($uid){
		$returnArr = Array();
		$sql = "SELECT c.clid, c.name FROM fmchecklists c WHERE uid = ".$uid;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		return $returnArr;
	}
	
	public function createChecklist($newClArr){
		$sqlInsert = "";
		$sqlValues = "";
		foreach($newClArr as $k => $v){
			$sqlInsert .= ",".$k;
			if($v){
				$sqlValues .= ",\"".$v."\"";
			}
			else{
				$sqlValues .= ",NULL";
			}
		}
		$sql = "INSERT INTO fmchecklists (".substr($sqlInsert,1).") VALUES (".substr($sqlValues,1).")";
		//echo $sql;
		$this->conn->query($sql);
	}

	public function deleteChecklist($clidDel){
		$sql = "DELETE FROM fmchklsttaxalink WHERE clid = ".$clidDel;
		$this->conn->query($sql);
		$sql = "DELETE FROM fmchecklists WHERE clid = ".$clidDel;
		//echo $sql;
		return $this->conn->query($sql);
	}
	
	public function echoParentSelect(){
		$sql = "SELECT c.clid, c.name FROM fmchecklists c ORDER BY c.name";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."'>".$row->name."</option>";
		}
		$rs->close();
	}
}


?>