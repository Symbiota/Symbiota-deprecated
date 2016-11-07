<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
header('Cache-Control: no-cache, no-cache="set-cookie", no-store, must-revalidate');
header('Pragma: no-cache'); // HTTP 1.0.
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$login = array_key_exists('login',$_POST)?$_POST['login']:'';
$emailAddr = array_key_exists('emailaddr',$_POST)?$_POST['emailaddr']:'';
$action = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:'';

$pHandler = new ProfileManager();
$displayStr = '';

//Sanitation
if($login){
	if(!$pHandler->setUserName($login)){
		$login = '';
		$displayStr = 'Invalid login name';
	}
}
if($emailAddr){
	if(!$pHandler->validateEmailAddress($emailAddr)){
		$emailAddr = '';
		$displayStr = 'Invalid login name';
	}
}
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$useRecaptcha = false;
if(isset($RECAPTCHA_PUBLIC_KEY) && $RECAPTCHA_PUBLIC_KEY && isset($RECAPTCHA_PRIVATE_KEY) && $RECAPTCHA_PRIVATE_KEY){
	$useRecaptcha = true;
}

if($action == "Create Login"){
	$okToCreateLogin = true;
	if($useRecaptcha){
		$captcha = urlencode($_POST['g-recaptcha-response']);
		if($captcha){
			//Verify with Google
			$response = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$RECAPTCHA_PRIVATE_KEY.'&response='.$captcha.'&remoteip='.$_SERVER['REMOTE_ADDR']), true);
			if($response['success'] == false){
				echo '<h2>Recaptcha verification failed</h2>';
				$okToCreateLogin = false;
			}
		}
		else{
			$okToCreateLogin = false;
			$displayStr = '<h2>Please check the the captcha form.</h2>';
		}
	}

	if($okToCreateLogin){
		if($pHandler->checkLogin($emailAddr)){
			if($pHandler->register($_POST)){
				header("Location: viewprofile.php");
			}
			else{
				$displayStr = 'FAILED: Unable to create user.<div style="margin-left:55px;">Please contact system administrator for assistance.</div>';
			}
		}
		else{
			$displayStr = $pHandler->getErrorStr();
		}
	}
}

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - New User Profile</title>
	<meta http-equiv="X-Frame-Options" content="deny">
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function validateform(f){
			<?php 
			if($useRecaptcha){
				?>
				if(grecaptcha.getResponse() == ""){
					alert("You must first check the reCAPTCHA checkbox (I'm not a robot)");
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
			if(f.login.value.replace(/\s/g, "") == ""){
				window.alert("User Name must contain a value");
				return false;
			}
		    if(f.login.value.instr(" ") > 0){
				window.alert("Login name cannot contain spaces");
				return false;
			}
			if( /[^0-9A-Za-z_!@#$-+]/.test( f.login.value ) ) {
		        alert("Login name should only contain 0-9A-Za-z_!@");
		        return false;
		    }
			if(f.emailaddr.value.replace(/\s/g, "") == "" ){
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
	
			return true;
		}
	</script>
	<?php 
	if($useRecaptcha) echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
	?>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_newprofileMenu)?$profile_newprofileMenu:"true");
	include($SERVER_ROOT.'/header.php');
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
	if($displayStr){
		echo '<div style="margin:10px;font-size:110%;font-weight:bold;color:red;">';
		if($displayStr == 'login_exists'){
			echo 'This login ('.$login.') is already being used.<br> '.
				'Please choose a different login name or visit the <a href="index.php?login='.$login.'">login page</a> if you believe this might be you';
		}
		elseif($displayStr == 'email_registered'){
			?>
			<div>
				A different login is already registered to this email address.<br/> 
				Use button below to have login emailed to <?php echo $emailAddr; ?>
				<div style="margin:15px">
					<form name="retrieveLoginForm" method="post" action="index.php">
						<input name="emailaddr" type="hidden" value="<?php echo $emailAddr; ?>" />
						<input name="action" type="submit" value="Retrieve Login" />
					</form>
				</div>
			</div>
			<?php 
		}
		elseif($displayStr == 'email_invalid'){
			echo 'Email address not valid';
		}
		else{
			echo $displayStr;
		}
		echo '</div>';
	}
	?>
	<fieldset style='margin:10px;width:95%;'>
		<legend><b>Login Details</b></legend>
		<form action="newprofile.php" method="post" onsubmit="return validateform(this);">
			<div style="margin:15px;">
				<table cellspacing='3'>
					<tr>
						<td style="width:120px;">
							<b>Login:</b>
						</td>
						<td>
							<input name="login" value="<?php echo $login; ?>" size="20" /> 
							<span style="color:red;">*</span>
							<br/>&nbsp;
						</td>
					</tr>
					<tr>
						<td>
							<b>Password:</b>
						</td>
						<td>
							<input name="pwd" id="pwd" value="" size="20" type="password" autocomplete="off" /> 
							<span style="color:red;">*</span>
						</td> 
					</tr>
					<tr>
						<td>
							<b>Password Again:</b> 
						</td>
						<td>
							<input id="pwd2" name="pwd2" value="" size="20" type="password" autocomplete="off" /> 
							<span style="color:red;">*</span>
							<br/>&nbsp;
						</td> 
					</tr>
					<tr>
						<td><span style="font-weight:bold;">First Name:</span></td>
						<td>
							<input id="firstname" name="firstname" size="40" value="<?php echo (isset($_POST['firstname'])?htmlspecialchars($_POST['firstname']):''); ?>"> 
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">Last Name:</span></td>
						<td>
							<input id="lastname" name="lastname" size="40" value="<?php echo (isset($_POST['lastname'])?htmlspecialchars($_POST['lastname']):''); ?>"> 
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">Email Address:</span></td>
						<td>
							<span class="profile"><input name="emailaddr"  size="40" value="<?php echo $emailAddr; ?>"></span> 
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><span style="color:red;">* required fields</span></td>
					</tr>
				</table>
				<div style="margin:15px 0px 10px 0px;"><b><u>Information below is optional, but encouraged</u></b></div>
				<table cellspacing='3'>
					<tr>
						<td><b>Title:</b></td>
						<td>
							<span class="profile"><input name="title"  size="40" value="<?php echo (isset($_POST['title'])?htmlspecialchars($_POST['title']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Institution:</b></td>
						<td>
							<span class="profile"><input name="institution"  size="40" value="<?php echo (isset($_POST['institution'])?htmlspecialchars($_POST['institution']):'') ?>"></span>
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
							<span class="profile"><input id="state" name="state"  size="40" value="<?php echo (isset($_POST['state'])?htmlspecialchars($_POST['state']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Zip Code:</b></td>
						<td>
							<span class="profile"><input name="zip"  size="40" value="<?php echo (isset($_POST['zip'])?htmlspecialchars($_POST['zip']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;">Country:</span></td>
						<td>
							<span class="profile"><input id="country" name="country"  size="40" value="<?php echo (isset($_POST['country'])?htmlspecialchars($_POST['country']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Url:</b></td>
						<td>
							<span class="profile"><input name="url"  size="40" value="<?php echo (isset($_POST['url'])?htmlspecialchars($_POST['url']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b>Biography:</b></td>
						<td>
							<span class="profile">
								<textarea name="biography" rows="4" cols="40"><?php echo (isset($_POST['biography'])?htmlspecialchars($_POST['biography']):''); ?></textarea>
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
								if($useRecaptcha) echo '<div class="g-recaptcha" data-sitekey="'.$RECAPTCHA_PUBLIC_KEY.'"></div>';
								?>
							</div>
							<div style="float:right;margin:20px;">
								<input type="submit" value="Create Login" name="submit" id="submit" />
							</div>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</fieldset>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>