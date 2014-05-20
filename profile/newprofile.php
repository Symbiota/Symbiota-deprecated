<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
$useRecaptcha = false;
if(isset($RECAPTCHA_PUBLIC_KEY) && $RECAPTCHA_PUBLIC_KEY && isset($RECAPTCHA_PRIVATE_KEY) && $RECAPTCHA_PRIVATE_KEY){
	require_once('recaptchalib.php');
	$useRecaptcha = true;
}
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:""; 

$displayMsg = "";

$refUrl = "";
if(array_key_exists("refurl",$_REQUEST)){
	$refGetStr = "";
	foreach($_GET as $k => $v){
		if($k != "refurl"){
			if($k == "attr" && is_array($v)){
				foreach($v as $v2){
					$refGetStr .= "&attr[]=".$v2;
				}
			}
			else{
				$refGetStr .= "&".$k."=".$v;
			}
		}
	}
	$refUrl = $_REQUEST["refurl"];
	if(substr($refUrl,-4) == ".php"){
		$refUrl .= "?".substr($refGetStr,1);
	}
	else{
		$refUrl .= $refGetStr;
	}
}

if($action == "Create Login"){
	$okToCreateLogin = true;
	if($useRecaptcha){
		$resp = recaptcha_check_answer ($RECAPTCHA_PRIVATE_KEY, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		if (!$resp->is_valid) {
			// What happens when the CAPTCHA was entered incorrectly
			$okToCreateLogin = false;
			$displayMsg = "The reCAPTCHA wasn't entered correctly. Go back and try it again. (reCAPTCHA said: " . $resp->error . ")";
		}
	}

	if($okToCreateLogin){
		$pHandler = new ProfileManager();
		$displayMsg = $pHandler->checkLogin($_POST["username"], $_POST["email"]);
		if(!$displayMsg){
			$displayMsg = $pHandler->register($_POST);
		}
		if(substr($displayMsg,0,7) == "SUCCESS"){
			$pHandler->authenticate($_POST["username"], $_POST["pwd"]);
			//Forward to page where user came from
			if($refUrl){
				header("Location: ".$refUrl);
			}
			else{
				header("Location: viewprofile.php");
			}
		}
	}
}

?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> - New User Profile</title>
	<link href="../css/main.css" rel="stylesheet" type="text/css"/>
	<script language="JavaScript" type="text/javascript">
		function checkform(f){
			<?php 
			if($useRecaptcha){
				?>
				if(f.recaptcha_response_field.value == ""){
					alert("Enter the re-CAPTCHA text (red box)");
					return false;
				}
				<?php 
			}
			?>
			var pwd1 = f.pwd.value.replace(/\s/g, "");
			var pwd2 = f.pwd2.value.replace(/\s/g, "");
			if(pwd1 == "" || pwd2 == ""){
				alert("Both password fields must contain a value.");
				return false;
			}
			if(pwd1.length < 7){
				alert("Password must be greater than 6 characters");
				return false;
			}
			if(pwd1 != pwd2){
				alert("Password do not match, please enter again");
				f.pwd.value = "";
				f.pwd2.value = "";
				f.pwd2.focus();
				return false;
			}
			if(f.username.value.replace(/\s/g, "") == ""){
				window.alert("User Name must contain a value");
				return false;
			}
			if(f.email.value.replace(/\s/g, "") == "" ){
				window.alert("Email address is required");
				return false;
			}
			if(f.firstname.value.replace(/\s/g, "") == ""){
				window.alert("First Name must contain a value");
				return false;
			}
			if(f.lastname.value.replace(/\s/g, "") == ""){
				window.alert("Last Name must contain a value");
				return false;
			}
			if(f.username.value.instr(" ") > 0){
				window.alert("Login cannot contain spaces");
				return false;
			}
	
			return true;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_newprofileMenu)?$profile_newprofileMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($profile_newprofileCrumbs)){
		echo "<div class='navpath'>";
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
	<fieldset style='margin:10px;width:390px;'>
		<legend><b>Login Details</b></legend>
		<form action="newprofile.php" method="post" onsubmit="return checkform(this);">
			<div style="margin:15px;">
				<table cellspacing='3'>
					<tr>
						<td style="width:120px;">
							<b>Login:</b>
						</td>
						<td>
							<input name="username" value="<?php echo (isset($_POST["username"])?$_POST["username"]:''); ?>" size="20" />
							<br/>&nbsp;
						</td>
					</tr>
					<tr>
						<td>
							<b>Password:</b>
						</td>
						<td>
							<input name="pwd" id="pwd" value="" size="20" type="password" /><br/>
						</td> 
					</tr>
					<tr>
						<td>
							<b>Password Again:</b> 
						</td>
						<td>
							<input id="pwd2" name="pwd2" value="" size="20" type="password" />
							<br/>&nbsp;
						</td> 
					</tr>
					<tr>
						<td><span style="font-weight:bold;">First Name:</span></td>
						<td>
							<input id="firstname" name="firstname" size="40" value="<?php echo (isset($_POST['firstname'])?$_POST['firstname']:''); ?>">
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">Last Name:</span></td>
						<td>
							<input id="lastname" name="lastname" size="40" value="<?php echo (isset($_POST['lastname'])?$_POST['lastname']:''); ?>">
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">Email Address:</span></td>
						<td>
							<span class="profile"><input id="email" name="email"  size="40" value="<?php echo (isset($_POST['email'])?$_POST['email']:''); ?>"></span>
						</td>
					</tr>
				</table>
				<div style="margin:15px 0px 10px 0px;"><b><u>Information below is optional, but encouraged</u></b></div>
				<table cellspacing='3'>
					<tr>
						<td><b>Title:</b></td>
						<td>
							<span class="profile"><input name="title"  size="40" value="<?php echo (isset($_POST['title'])?$_POST['title']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Institution:</b></td>
						<td>
							<span class="profile"><input name="institution"  size="40" value="<?php echo (isset($_POST['institution'])?$_POST['institution']:'') ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">City:</span></td>
						<td>
							<span class="profile"><input id="city" name="city" size="40" value="<?php echo (isset($_POST['city'])?$_POST['city']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">State:</span></td>
						<td>
							<span class="profile"><input id="state" name="state"  size="40" value="<?php echo (isset($_POST['state'])?$_POST['state']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Zip Code:</b></td>
						<td>
							<span class="profile"><input name="zip"  size="40" value="<?php echo (isset($_POST['zip'])?$_POST['zip']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">Country:</span></td>
						<td>
							<span class="profile"><input id="country" name="country"  size="40" value="<?php echo (isset($_POST['country'])?$_POST['country']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Url:</b></td>
						<td>
							<span class="profile"><input name="url"  size="40" value="<?php echo (isset($_POST['url'])?$_POST['url']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Biography:</b></td>
						<td>
							<span class="profile">
								<textarea name="biography" rows="4" cols="40"><?php echo (isset($_POST['biography'])?$_POST['biography']:''); ?></textarea>
							</span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<span class="profile">
								<input type="checkbox" name="ispublic" value="1" <?php if(isset($_POST['ispublic'])) echo "CHECKED"; ?> /> Public can view email and bio within website (e.g. photographer listing)
							</span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div style="margin:10px;">
								<?php 
								if($useRecaptcha){
									require_once('recaptchalib.php');
									echo recaptcha_get_html($RECAPTCHA_PUBLIC_KEY);
								}
								?>
							</div>
							<div style="float:right;margin:20px;">
								<input type="submit" value="Create Login" name="submit" id="submit" />
								<input type="hidden" name="refurl" value="<?php echo $refUrl; ?>" />
							</div>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</fieldset>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>