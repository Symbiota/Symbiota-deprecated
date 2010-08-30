<?php
/*
 * Created on 26 Feb 2009
 * By E.E. Gilbert
*/
include_once('../util/symbini.php');
include_once('util/ProfileHandler.php');
include_once('util/Person.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:""; 

$pwd = ""; $username = ""; $firstname = ""; $lastname = ""; $email = "";
$title = ""; $department = ""; $institution = ""; $address = ""; $city = "";
$state = ""; $zip = ""; $country = ""; $phone = ""; $url = ""; $biography = ""; $isPublic = "";

$displayMsg = "";

if($action == "Submit Profile"){
    $pwd = array_key_exists("pwd",$_REQUEST)?$_REQUEST["pwd"]:"";
   	$username = $_REQUEST["username"];
	$firstname = $_REQUEST["firstname"];
    $lastname = $_REQUEST["lastname"];
	$title = $_REQUEST["title"];
	$institution = $_REQUEST["institution"];
	$city = $_REQUEST["city"];
	$state = $_REQUEST["state"];
	$zip = $_REQUEST["zip"];
	$country = $_REQUEST["country"];
    $email = $_REQUEST["email"];
	$url = $_REQUEST["url"];
	$biography = $_REQUEST["biography"];
	$isPublic = $_REQUEST["ispublic"];
	
	$pHandler = new ProfileHandler();
	$newPerson = new Person();
    $newPerson->setPassword($pwd);
	$newPerson->setUserName($username);
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
    $displayMsg = $pHandler->checkLogin($username, $email);
	if(!$displayMsg){
		$displayMsg = $pHandler->register($newPerson);
	}
	if(substr($displayMsg,0,7) == "SUCCESS"){
		$pHandler->authenticate($username, $pwd);
    	header("Location: viewprofile.php");
	}
}

?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> - New User Profile</title>
    <link href="../css/main.css" rel="stylesheet" type="text/css"/>
	<script language="JavaScript" type="text/javascript">
	    function checkform(f){
	        var pwd1 = f.pwd.value.replace(/\s/g, "");
	        var pwd2 = f.pwd2.value.replace(/\s/g, "");
	        if(pwd1 == "" || pwd2 == ""){
	            window.alert("Both password fields must contain a value.");
	            return false;
	        }
	        if(pwd1 != pwd2){
	            window.alert("Password do not match. Please enter again.");
	            f.pwd.value = "";
	            f.pwd2.value = "";
	            f.pwd2.focus();
	            return false;
	        }
	        if(f.username.value.replace(/\s/g, "") == ""){
	            window.alert("User Name must contain a value.");
	            return false;
	        }
	
	        var errorText = "";
	        if(f.firstname.value.replace(/\s/g, "") == "" ){
	            errorText += "\nFirst Name";
	        }
	        if(f.lastname.value.replace(/\s/g, "") == "" ){
	            errorText += "\nLast Name";
	        }
	        if(f.state.value.replace(/\s/g, "") == "" ){
	            errorText += "\nState";
	        }
	        if(f.country.value.replace(/\s/g, "") == "" ){
	            errorText += "\nCountry";
	        }
	        if(f.email.value.replace(/\s/g, "") == "" ){
	            errorText += "\nEmail";
	        }
	
	        if(errorText == ""){
	            return true;
	        }
	        else{
	            window.alert("The following fields must be filled out:\n " + errorText);
	            return false;
	        }
	    }
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_newprofileMenu)?$profile_newprofileMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($profile_newprofileCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $profile_newprofileCrumbs;
		echo " <b>Create New Profile</b>";
		echo "</div>";
	}
	?>
	<!-- inner text -->
	<div id="innertext">
	<h1>Create New Profile</h1>
	
	<?php
		echo "<div style='margin:10px;font-size:110%;font-weight:bold;color:red;'>".$displayMsg."</div>";
	?>
	<div style="margin:10px;"><b>Note:</b> Fields in red are required</div>
	
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return checkform(this);">
	<table cellspacing='3'>
	    <tr>
	        <td><span style="color:#FF0000;font-weight:bold;">First Name:</span></td>
	        <td>
            	<input id="firstname" name="firstname" size="40" value="<?php echo $firstname; ?>">
            </td>
	    </tr>
	    <tr>
	        <td><span style="color:#FF0000;font-weight:bold;">Last Name:</span></td>
	        <td>
            	<input id="lastname" name="lastname" size="40" value="<?php echo $lastname; ?>">
            </td>
	    </tr>
	    <tr>
	        <td><b>Title:</b></td>
	        <td>
            	<span class="profile"><input name="title"  size="40" value="<?php echo $title; ?>"></span>
            </td>
	    </tr>
	    <tr>
	        <td><b>Institution:</b></td>
	        <td>
				<span class="profile"><input name="institution"  size="40" value="<?php echo $institution; ?>"></span>
			</td>
	    </tr>
	    <tr>
	        <td><span style="font-weight:bold;">City:</span></td>
	        <td>
            	<span class="profile"><input id="city" name="city" size="40" value="<?php echo $city; ?>"></span>
            </td>
	    </tr>
	    <tr>
	        <td><span style="color:#FF0000;font-weight:bold;">State:</span></td>
	        <td>
            	<span class="profile"><input id="state" name="state"  size="40" value="<?php echo $state; ?>"></span>
            </td>
	    </tr>
	    <tr>
	        <td><b>Zip Code:</b></td>
	        <td>
            	<span class="profile"><input name="zip"  size="40" value="<?php echo $zip; ?>"></span>
            </td>
	    </tr>
	    <tr>
	        <td><span style="color:#FF0000;font-weight:bold;">Country:</span></td>
	        <td>
				<span class="profile"><input id="country" name="country"  size="40" value="<?php echo $country; ?>"></span>
			</td>
	    </tr>
	    <tr>
	        <td><span style="color:#FF0000;font-weight:bold;">Email Address:</span></td>
	        <td>
            	<span class="profile"><input id="email" name="email"  size="40" value="<?php echo $email; ?>"></span>
            </td>
	    </tr>
	    <tr>
	        <td><b>Url:</b></td>
	        <td>
				<span class="profile"><input name="url"  size="40" value="<?php echo $url; ?>"></span>
			</td>
	    </tr>
	    <tr>
	        <td><b>Biography:</b></td>
	        <td>
				<span class="profile">
					<textarea name="biography" rows="4" cols="40"><?php echo $biography; ?></textarea>
				</span>
			</td>
	    </tr>
	    <tr>
	        <td colspan="2">
				<span class="profile">
					<input type="checkbox" name="ispublic" value="1" <?php if($isPublic) echo "CHECKED"; ?> /> Public can view email and bio within website (e.g. photographer listing)
				</span>
			</td>
	    </tr>
        <tr>
			<td colspan='2'>
				<fieldset style='margin:10px;width:390px;'>
			    	<legend>Login Information</legend>
					<div style="margin:20px;">New User Name:<input name="username" value="<?php echo $username; ?>" size="20" style="margin-left:11px;"></div>
					<div style="">
						<div style="margin:10px 0px 0px 20px;">Enter a Password:<input name="pwd" id="pwd" value="<?php echo $pwd; ?>" size="20" style="margin-left:4px;" type="password"></div> 
						<div style="margin:3px 0px 0px 20px;">Password Again:<input id="pwd2" name="pwd2" value="<?php echo $pwd; ?>" size="20" style="margin-left:12px;" type="password"></div>
					</div>

	                <div style="margin: 1em 1em 1em 0em;">Please email 
	                    <a class='bodylink' href="mailto:<?php echo $adminEmail; ?>?subject=Contacting%20<?php echo $defaultTitle; ?>">
	                    Administration</a> with problems, questions, or concerns.
	                </div>
	            </fieldset>
            </td>
        </tr>
	    <tr>
	        <td colspan='2' align="right">
	            <input type="submit" value="Submit Profile" name="submit" id="submit">
	        </td>
	    </tr>
	  </table>
	</form>
	</div>

        <!-- end inner text -->
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>
